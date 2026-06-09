<?php
/**
 * Service de messagerie SMTP (Brevo, Gmail, etc.)
 */

class MailerService {
    private $host;
    private $port;
    private $username;
    private $password;
    private $fromEmail;
    private $fromName;

    public function __construct() {
        $this->loadSettings();
    }

    private function loadSettings() {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp_%'");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

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
            $this->fromEmail = 'contact@source-eau-vive.org';
        }

        $this->fromName = trim((string)($settings['smtp_from_name'] ?? '')) ?: "Eglise SOURCE D'EAU VIVE";
    }

    public function isConfigured() {
        return $this->host !== '' && $this->username !== '' && $this->password !== '';
    }

    public function sendEmail($to, $subject, $htmlBody, $options = []) {
        $replyTo = trim((string)($options['reply_to'] ?? ''));

        if (!$this->isConfigured()) {
            throw new Exception(
                'SMTP non configure : renseignez le login et le mot de passe SMTP (cle Brevo) dans Parametres > Services Tiers.'
            );
        }

        $recipients = is_array($to) ? $to : array_map('trim', explode(',', (string)$to));
        $recipients = array_values(array_filter($recipients, static function ($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        }));

        if (!$recipients) {
            throw new Exception('Aucun destinataire email valide.');
        }

        $socket = $this->connect();
        try {
            $this->authenticate($socket);
            $this->sendCommand($socket, 'MAIL FROM:<' . $this->fromEmail . '>', [250]);

            foreach ($recipients as $recipient) {
                $this->sendCommand($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
            }

            $this->sendCommand($socket, 'DATA', [354]);

            $headers = [
                'From: ' . $this->encodeAddress($this->fromEmail, $this->fromName),
                'To: ' . implode(', ', $recipients),
                'Subject: ' . $this->encodeHeader($subject),
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
                'Date: ' . date('r'),
                'Message-ID: <' . uniqid('maloty.', true) . '@' . preg_replace('/[^a-z0-9.-]/i', '', $this->host) . '>',
            ];

            if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $headers[] = 'Reply-To: ' . $replyTo;
            }

            $message = implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody . "\r\n";
            $message = preg_replace("/\r\n\./", "\r\n..", $message);

            fwrite($socket, $message . "\r\n.\r\n");
            $this->expectSmtpCodes($this->readSmtpResponse($socket), [250]);
            $this->sendCommand($socket, 'QUIT', [221]);
        } finally {
            if (is_resource($socket)) {
                fclose($socket);
            }
        }

        return true;
    }

    private function connect() {
        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client(
            'tcp://' . $this->host . ':' . $this->port,
            $errno,
            $errstr,
            20,
            STREAM_CLIENT_CONNECT
        );

        if (!$socket) {
            throw new Exception("Connexion SMTP impossible vers {$this->host}:{$this->port} ($errstr)");
        }

        stream_set_timeout($socket, 20);
        $this->expectSmtpCodes($this->readSmtpResponse($socket), [220]);

        $ehloHost = 'localhost';
        if (!empty($_SERVER['SERVER_NAME'])) {
            $ehloHost = preg_replace('/[^a-z0-9.-]/i', '', (string)$_SERVER['SERVER_NAME']) ?: $ehloHost;
        }

        $this->sendCommand($socket, 'EHLO ' . $ehloHost, [250]);

        if ($this->port === 587) {
            $this->sendCommand($socket, 'STARTTLS', [220]);
            $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }
            if (!stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                throw new Exception('Activation STARTTLS echouee.');
            }
            $this->sendCommand($socket, 'EHLO ' . $ehloHost, [250]);
        } elseif ($this->port === 465) {
            throw new Exception('Le port 465 n est pas supporte. Utilisez le port 587 avec STARTTLS.');
        }

        return $socket;
    }

    private function authenticate($socket) {
        $this->sendCommand($socket, 'AUTH LOGIN', [334]);
        $this->sendCommand($socket, base64_encode($this->username), [334]);
        $this->sendCommand($socket, base64_encode($this->password), [235]);
    }

    private function sendCommand($socket, $command, array $expectedCodes) {
        fwrite($socket, $command . "\r\n");
        $response = $this->readSmtpResponse($socket);
        $this->expectSmtpCodes($response, $expectedCodes);
        return $response;
    }

    private function expectSmtpCodes($response, array $expectedCodes) {
        $firstLine = strtok((string)$response, "\r\n");
        $code = (int)substr((string)$firstLine, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            $hint = '';
            if ($code === 535 || $code === 534) {
                $hint = ' Verifiez le login SMTP et la cle SMTP Brevo (pas le mot de passe du compte).';
            } elseif ($code === 550 || $code === 553) {
                $hint = " Verifiez que l email expediteur ({$this->fromEmail}) est valide et autorise chez votre fournisseur SMTP.";
            }
            throw new Exception('Erreur SMTP (' . $code . '): ' . trim((string)$response) . $hint);
        }
    }

    private function readSmtpResponse($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $response;
    }

    private function encodeHeader($value) {
        return '=?UTF-8?B?' . base64_encode((string)$value) . '?=';
    }

    private function encodeAddress($email, $name = '') {
        $email = trim((string)$email);
        $name = trim((string)$name);
        if ($name === '') {
            return $email;
        }
        return $this->encodeHeader($name) . ' <' . $email . '>';
    }
}
