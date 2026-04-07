<?php
/**
 * Service de messagerie (SMTP simple)
 */

class MailerService {
    private $db;
    private $host;
    private $port;
    private $username;
    private $password;
    private $fromEmail;
    private $fromName;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->loadSettings();
    }

    private function loadSettings() {
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp_%'");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        // Par defaut un SMTP gratuit populaire comme Sendinblue(Brevo) ou SSL Gmail
        $this->host = trim((string)($settings['smtp_host'] ?? 'smtp-relay.brevo.com'));
        $this->port = (int)($settings['smtp_port'] ?? 587);
        $this->username = trim((string)($settings['smtp_username'] ?? ''));
        $this->password = (string)($settings['smtp_password'] ?? '');

        $configuredFromEmail = trim((string)($settings['smtp_from_email'] ?? ''));
        if (filter_var($configuredFromEmail, FILTER_VALIDATE_EMAIL)) {
            $this->fromEmail = $configuredFromEmail;
        } elseif (filter_var($this->username, FILTER_VALIDATE_EMAIL)) {
            $this->fromEmail = $this->username;
        } else {
            $this->fromEmail = 'contact@malothy-church.org';
        }

        $this->fromName = trim((string)($settings['smtp_from_name'] ?? '')) ?: 'Eglise MALOTY';
    }

    public function sendEmail($to, $subject, $htmlBody, $options = []) {
        $replyTo = trim((string)($options['reply_to'] ?? ''));
        // Mode Simulation Locale : Si les identifiants SMTP ne sont pas configurés
        if (empty($this->username) || empty($this->password)) {
            $logFile = PROJECT_ROOT . '/tmp/emails_debug.log';
            $date = date('Y-m-d H:i:s');
            $recipient = is_array($to) ? implode(', ', $to) : $to;
            $logEntry = "[$date] SIMULATION D'ENVOI D'EMAIL\n";
            $logEntry .= "À : $recipient\nSujet : $subject\n";
            $logEntry .= "Message :\n$htmlBody\n";
            $logEntry .= str_repeat('-', 50) . "\n";
            file_put_contents($logFile, $logEntry, FILE_APPEND);
            return true; // Simule un succès pour le Frontend
        }

        $crlf = "\r\n";
        $headers = [
            'From: "' . $this->fromName . '" <' . $this->fromEmail . '>',
            'To: ' . (is_array($to) ? implode(', ', $to) : $to),
            'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8'
        ];
        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $headers[] = 'Reply-To: ' . $replyTo;
        }

        $payload = implode($crlf, $headers) . $crlf . $crlf . $htmlBody . $crlf . '.';

        $transportHost = $this->port === 465 ? 'ssl://' . $this->host : $this->host;
        $socket = fsockopen($transportHost, $this->port, $errno, $errstr, 15);
        if (!$socket) {
            throw new Exception("Connexion SMTP échouée: $errstr ($errno)");
        }

        $this->readSmtpResponse($socket);

        // EHLO
        fwrite($socket, "EHLO maloty" . $crlf);
        $this->readSmtpResponse($socket);

        // STARTTLS
        if ($this->port == 587) {
            fwrite($socket, "STARTTLS" . $crlf);
            $this->readSmtpResponse($socket);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("Activation STARTTLS echouee.");
            }
            fwrite($socket, "EHLO maloty" . $crlf);
            $this->readSmtpResponse($socket);
        }

        // AUTH
        fwrite($socket, "AUTH LOGIN" . $crlf);
        $this->readSmtpResponse($socket);
        fwrite($socket, base64_encode($this->username) . $crlf);
        $this->readSmtpResponse($socket);
        fwrite($socket, base64_encode($this->password) . $crlf);
        $this->readSmtpResponse($socket);

        // MAIL FROM
        fwrite($socket, "MAIL FROM:<" . $this->fromEmail . ">" . $crlf);
        $this->readSmtpResponse($socket);

        // RCPT TO
        $recipients = is_array($to) ? $to : explode(',', $to);
        foreach ($recipients as $recipient) {
            fwrite($socket, "RCPT TO:<" . trim($recipient) . ">" . $crlf);
            $this->readSmtpResponse($socket);
        }

        // DATA
        fwrite($socket, "DATA" . $crlf);
        $this->readSmtpResponse($socket);
        
        fwrite($socket, $payload . $crlf);
        $res = $this->readSmtpResponse($socket);
        
        // QUIT
        fwrite($socket, "QUIT" . $crlf);
        fclose($socket);

        if (strpos($res, '250') === false) {
            throw new Exception("Échec de l'envoi du message: $res");
        }

        return true;
    }

    private function readSmtpResponse($socket) {
        $response = "";
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == " ") {
                break;
            }
        }
        return $response;
    }
}
