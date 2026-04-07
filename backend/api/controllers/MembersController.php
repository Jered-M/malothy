<?php
/**
 * API MembersController
 */

require_once PROJECT_ROOT . '/backend/models/Member.php';
require_once PROJECT_ROOT . '/backend/api/services/MailerService.php';
require_once PROJECT_ROOT . '/backend/config/mime.php';

class MembersController {
    private $memberModel;
    private $db;

    public function __construct() {
        $this->memberModel = new Member();
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * GET /api/members
     */
    public function index() {
        checkRole(['admin', 'secretaire']);

        $members = $this->db
            ->query('SELECT id, first_name, last_name, email, phone, department, status FROM members ORDER BY first_name')
            ->fetchAll();

        json_response([
            'success' => true,
            'data' => $members
        ]);
    }

    /**
     * GET /api/members/:id
     */
    public function show($id) {
        checkRole(['admin', 'secretaire']);

        $stmt = $this->db->prepare('SELECT * FROM members WHERE id = ?');
        $stmt->execute([$id]);
        $member = $stmt->fetch();

        if (!$member) {
            json_error('Membre non trouve', 404);
        }

        json_response([
            'success' => true,
            'data' => $member
        ]);
    }

    /**
     * POST /api/members
     */
    public function create() {
        checkRole(['admin', 'secretaire']);

        $input = get_input();

        $required = ['first_name', 'last_name', 'phone', 'email', 'join_date'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                json_error("Le champ '{$field}' est requis", 400);
            }
        }

        $memberEmail = trim((string)$input['email']);
        $memberPhone = trim((string)$input['phone']);

        $stmtCheck = $this->db->prepare('SELECT id FROM members WHERE phone = ? OR email = ?');
        $stmtCheck->execute([$memberPhone, $memberEmail]);
        if ($stmtCheck->fetch()) {
            json_error('Un membre avec ce numero de telephone ou cet email existe deja.', 409);
        }

        $stmtUserCheck = $this->db->prepare('SELECT id FROM users WHERE email = ?');
        $stmtUserCheck->execute([$memberEmail]);
        if ($stmtUserCheck->fetch()) {
            json_error('Cet email est deja utilise par un compte utilisateur.', 409);
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare('
                INSERT INTO members (first_name, last_name, email, phone, address, department, join_date, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ');

            $stmt->execute([
                trim((string)$input['first_name']),
                trim((string)$input['last_name']),
                $memberEmail,
                $memberPhone,
                $input['address'] ?? null,
                $input['department'] ?? null,
                $input['join_date'],
                'actif'
            ]);

            $memberId = $this->db->lastInsertId();
            $rawPassword = !empty($input['account_password'])
                ? (string)$input['account_password']
                : str_pad((string)rand(0, 999999), 6, '0', STR_PAD_LEFT);

            $stmtUser = $this->db->prepare('
                INSERT INTO users (name, email, password, role, status)
                VALUES (?, ?, ?, ?, ?)
            ');

            $stmtUser->execute([
                trim((string)$input['first_name'] . ' ' . (string)$input['last_name']),
                $memberEmail,
                password_hash($rawPassword, PASSWORD_DEFAULT),
                'membre',
                'actif'
            ]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            json_error("Erreur lors de la creation : " . $e->getMessage(), 500);
        }

        audit_log(
            'CREATE',
            'members',
            $memberId,
            "Membre cree: {$input['first_name']} {$input['last_name']} (Compte Email: {$memberEmail})"
        );

        $passwordNotification = $this->notifyPasswordRecipient(
            trim((string)$input['first_name'] . ' ' . (string)$input['last_name']),
            $memberEmail,
            $rawPassword,
            'create'
        );

        json_response([
            'success' => true,
            'message' => 'Membre et compte crees avec succes',
            'id' => $memberId,
            'account' => [
                'username' => $memberEmail,
                'password' => $rawPassword
            ],
            'password_notification' => $passwordNotification
        ], 201);
    }

    /**
     * PUT /api/members/:id
     */
    public function update($id) {
        checkRole(['admin', 'secretaire']);

        $input = get_input();

        $stmtOld = $this->db->prepare('SELECT first_name, last_name, email FROM members WHERE id = ?');
        $stmtOld->execute([$id]);
        $oldMember = $stmtOld->fetch();

        if (!$oldMember) {
            json_error('Membre non trouve', 404);
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare('
                UPDATE members SET
                    first_name = COALESCE(?, first_name),
                    last_name = COALESCE(?, last_name),
                    email = COALESCE(?, email),
                    phone = COALESCE(?, phone),
                    address = COALESCE(?, address),
                    department = COALESCE(?, department),
                    status = COALESCE(?, status),
                    updated_at = NOW()
                WHERE id = ?
            ');

            $stmt->execute([
                $input['first_name'] ?? null,
                $input['last_name'] ?? null,
                $input['email'] ?? null,
                $input['phone'] ?? null,
                $input['address'] ?? null,
                $input['department'] ?? null,
                $input['status'] ?? null,
                $id
            ]);

            if (!empty($oldMember['email'])) {
                $nextFirstName = trim((string)($input['first_name'] ?? $oldMember['first_name']));
                $nextLastName = trim((string)($input['last_name'] ?? $oldMember['last_name']));
                $nextName = trim($nextFirstName . ' ' . $nextLastName);
                $nextEmail = trim((string)($input['email'] ?? $oldMember['email']));

                $passwordSql = '';
                $params = [$nextName !== '' ? $nextName : null, $nextEmail !== '' ? $nextEmail : null];

                if (!empty($input['account_password'])) {
                    $passwordSql = ', password = ?';
                    $params[] = password_hash((string)$input['account_password'], PASSWORD_DEFAULT);
                }

                $params[] = $oldMember['email'];

                $stmtUserUpdate = $this->db->prepare("
                    UPDATE users SET
                        name = COALESCE(?, name),
                        email = COALESCE(?, email)
                        {$passwordSql}
                    WHERE email = ?
                ");

                $stmtUserUpdate->execute($params);
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            json_error("Erreur lors de la mise a jour : " . $e->getMessage(), 500);
        }

        audit_log('UPDATE', 'members', $id, 'Membre et compte utilisateur mis a jour');

        $passwordNotification = null;
        if (!empty($input['account_password'])) {
            $memberFirstName = trim((string)($input['first_name'] ?? $oldMember['first_name'] ?? ''));
            $memberLastName = trim((string)($input['last_name'] ?? $oldMember['last_name'] ?? ''));
            $memberName = trim($memberFirstName . ' ' . $memberLastName);
            $memberEmail = trim((string)($input['email'] ?? $oldMember['email'] ?? ''));

            $passwordNotification = $this->notifyPasswordRecipient(
                $memberName,
                $memberEmail,
                (string)$input['account_password'],
                'reset'
            );
        }

        json_response([
            'success' => true,
            'message' => 'Membre mis a jour avec succes',
            'password_notification' => $passwordNotification
        ]);
    }

    /**
     * DELETE /api/members/:id
     */
    public function delete($id) {
        checkRole(['admin', 'secretaire']);

        $stmtEmail = $this->db->prepare('SELECT email FROM members WHERE id = ?');
        $stmtEmail->execute([$id]);
        $member = $stmtEmail->fetch();

        if (!$member) {
            json_error('Membre non trouve', 404);
        }

        $this->db->beginTransaction();

        try {
            if (!empty($member['email'])) {
                $stmtUser = $this->db->prepare('DELETE FROM users WHERE email = ?');
                $stmtUser->execute([$member['email']]);
            }

            $stmt = $this->db->prepare('DELETE FROM members WHERE id = ?');
            $stmt->execute([$id]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            json_error("Erreur lors de la suppression : " . $e->getMessage(), 500);
        }

        audit_log('DELETE', 'members', $id, "Membre supprime: (Email: {$member['email']})");

        json_response([
            'success' => true,
            'message' => 'Membre et compte utilisateur supprimes avec succes'
        ]);
    }

    /**
     * POST /api/members/:id/photo
     */
    public function upload_photo($id) {
        checkRole(['admin', 'secretaire']);

        $stmt = $this->db->prepare('SELECT id FROM members WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            json_error('Membre non trouve', 404);
        }

        if (empty($_FILES['photo'])) {
            json_error('Aucune photo fournie', 400);
        }

        $file = $_FILES['photo'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($file['type'], $allowed, true)) {
            json_error('Format non supporte. Formats acceptes: JPG, PNG, WEBP', 400);
        }

        if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
            json_error('Photo trop volumineuse (max 5MB)', 400);
        }

        $uploadDir = PROJECT_ROOT . '/uploads/members/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = pathinfo((string)$file['name'], PATHINFO_EXTENSION);
        $filename = 'member_' . $id . '_' . time() . '.' . $ext;
        $filepath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            json_error('Erreur lors du telechargement', 500);
        }

        $stmt = $this->db->prepare('UPDATE members SET photo = ? WHERE id = ?');
        $stmt->execute(['uploads/members/' . $filename, $id]);

        audit_log('UPDATE', 'members', $id, 'Photo mise a jour');

        json_response([
            'success' => true,
            'message' => 'Photo mise a jour',
            'photo' => 'uploads/members/' . $filename
        ]);
    }

    /**
     * GET /api/members/:id/photo
     */
    public function get_photo($id) {
        $stmt = $this->db->prepare('SELECT photo FROM members WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch();

        if (!$result || empty($result['photo'])) {
            json_error('Photo non trouvee', 404);
        }

        $filepath = PROJECT_ROOT . '/' . $result['photo'];
        if (!file_exists($filepath)) {
            json_error("Fichier photo n'existe pas", 404);
        }

        $mimeType = detect_mime_type($filepath, 'image/jpeg');
        header('Content-Type: ' . $mimeType);
        header('Cache-Control: max-age=86400');
        readfile($filepath);
        exit;
    }

    /**
     * DELETE /api/members/:id/photo
     */
    public function delete_photo($id) {
        checkRole(['admin', 'secretaire']);

        $stmt = $this->db->prepare('SELECT photo FROM members WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch();

        if ($result && !empty($result['photo'])) {
            $filepath = PROJECT_ROOT . '/' . $result['photo'];
            if (file_exists($filepath)) {
                @unlink($filepath);
            }
        }

        $stmt = $this->db->prepare('UPDATE members SET photo = NULL WHERE id = ?');
        $stmt->execute([$id]);

        audit_log('UPDATE', 'members', $id, 'Photo supprimee');

        json_response([
            'success' => true,
            'message' => 'Photo supprimee'
        ]);
    }

    private function notifyPasswordRecipient($memberName, $memberEmail, $rawPassword, $action) {
        $recipientEmail = $this->resolvePasswordNotificationEmail();

        try {
            $mailer = new MailerService();
            $safeRecipient = htmlspecialchars($recipientEmail, ENT_QUOTES, 'UTF-8');
            $safeMemberName = htmlspecialchars($memberName !== '' ? $memberName : 'Membre', ENT_QUOTES, 'UTF-8');
            $safeMemberEmail = htmlspecialchars($memberEmail, ENT_QUOTES, 'UTF-8');
            $safePassword = htmlspecialchars($rawPassword, ENT_QUOTES, 'UTF-8');
            $safeAction = $action === 'reset' ? 'Reinitialisation' : 'Creation';

            $subject = $action === 'reset'
                ? 'Reinitialisation mot de passe membre'
                : 'Nouveau mot de passe membre';

            $htmlBody = "
                <h2>{$safeAction} de mot de passe membre</h2>
                <p><strong>Destinataire notification :</strong> {$safeRecipient}</p>
                <p><strong>Membre :</strong> {$safeMemberName}</p>
                <p><strong>Email de connexion :</strong> {$safeMemberEmail}</p>
                <p><strong>Mot de passe :</strong> {$safePassword}</p>
            ";

            $options = [];
            if (filter_var($memberEmail, FILTER_VALIDATE_EMAIL)) {
                $options['reply_to'] = $memberEmail;
            }

            $mailer->sendEmail($recipientEmail, $subject, $htmlBody, $options);

            return [
                'sent' => true,
                'email' => $recipientEmail
            ];
        } catch (Exception $e) {
            return [
                'sent' => false,
                'email' => $recipientEmail,
                'warning' => $e->getMessage()
            ];
        }
    }

    private function resolvePasswordNotificationEmail() {
        $settings = [];
        $stmt = $this->db->query("
            SELECT setting_key, setting_value
            FROM settings
            WHERE setting_key IN (
                'password_notification_email',
                'contact_recipient_email',
                'smtp_from_email',
                'smtp_username'
            )
        ");

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = trim((string)($row['setting_value'] ?? ''));
        }

        $candidates = [
            $settings['password_notification_email'] ?? '',
            'minonojered7@gmail.com',
            $settings['contact_recipient_email'] ?? '',
            $settings['smtp_from_email'] ?? '',
            $settings['smtp_username'] ?? ''
        ];

        foreach ($candidates as $candidate) {
            if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return $candidate;
            }
        }

        return 'minonojered7@gmail.com';
    }
}
