<?php
/**
 * API ContactController
 * Gere le formulaire de contact public
 */

require_once PROJECT_ROOT . '/backend/api/services/MailerService.php';

class ContactController {
    public function create() {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            json_error('Methode non autorisee', 405);
        }

        $input = get_input();

        $firstName = trim(strip_tags((string)($input['first_name'] ?? '')));
        $lastName = trim(strip_tags((string)($input['last_name'] ?? '')));
        $email = filter_var(trim((string)($input['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        $message = trim(strip_tags((string)($input['message'] ?? '')));

        if (!$email || $firstName === '' || $message === '') {
            json_error('Veuillez remplir le prenom, l email et le message.', 400);
        }

        try {
            $db = Database::getInstance()->getConnection();
            $recipientEmail = $this->resolveRecipientEmail($db);

            $subject = 'Nouveau message de contact';
            $fullName = trim($firstName . ' ' . $lastName);
            $safeFullName = htmlspecialchars($fullName !== '' ? $fullName : $firstName, ENT_QUOTES, 'UTF-8');
            $safeEmail = htmlspecialchars((string)$email, ENT_QUOTES, 'UTF-8');
            $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

            $htmlBody = "
                <h2>Nouveau message de contact</h2>
                <p><strong>Nom :</strong> {$safeFullName}</p>
                <p><strong>Email :</strong> {$safeEmail}</p>
                <hr>
                <p>{$safeMessage}</p>
            ";

            $mailer = new MailerService();
            $mailer->sendEmail($recipientEmail, $subject, $htmlBody, [
                'reply_to' => (string)$email
            ]);

            json_response([
                'success' => true,
                'message' => 'Votre message a ete envoye avec succes.'
            ]);
        } catch (Exception $e) {
            json_error("Erreur lors de l'envoi du message : " . $e->getMessage(), 500);
        }
    }

    private function resolveRecipientEmail(PDO $db) {
        $settings = [];
        $stmt = $db->query("
            SELECT setting_key, setting_value
            FROM settings
            WHERE setting_key IN ('contact_recipient_email', 'smtp_from_email', 'smtp_username')
        ");

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = trim((string)($row['setting_value'] ?? ''));
        }

        $candidates = [
            $settings['contact_recipient_email'] ?? '',
            $settings['smtp_from_email'] ?? '',
            $settings['smtp_username'] ?? '',
            'contact@malothy-church.org'
        ];

        foreach ($candidates as $candidate) {
            if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return $candidate;
            }
        }

        throw new Exception('Aucune adresse de reception valide n est configuree.');
    }
}
