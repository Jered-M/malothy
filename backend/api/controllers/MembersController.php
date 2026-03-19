<?php
/**
 * API MembersController
 */

require_once PROJECT_ROOT . '/backend/models/Member.php';

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
        checkRole(['admin', 'Secrétaire']);

        $members = $this->db->query('SELECT id, first_name, last_name, email, phone, department, status FROM members ORDER BY first_name')->fetchAll();

        json_response([
            'success' => true,
            'data' => $members
        ]);
    }

    /**
     * GET /api/members/:id
     */
    public function show($id) {
        checkRole(['admin', 'Secrétaire']);

        $stmt = $this->db->prepare('SELECT * FROM members WHERE id = ?');
        $stmt->execute([$id]);
        $member = $stmt->fetch();

        if (!$member) {
            json_error('Membre non trouvé', 404);
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
        checkRole(['admin', 'Secrétaire']);

        $input = get_input();
        
        $required = ['first_name', 'last_name', 'phone', 'join_date'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                json_error("Le champ '{$field}' est requis", 400);
            }
        }

        $stmt = $this->db->prepare('
            INSERT INTO members (first_name, last_name, email, phone, address, department, join_date, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ');

        $stmt->execute([
            $input['first_name'],
            $input['last_name'],
            $input['email'] ?? null,
            $input['phone'],
            $input['address'] ?? null,
            $input['department'] ?? null,
            $input['join_date'],
            'actif'
        ]);

        json_response([
            'success' => true,
            'message' => 'Membre créé avec succès',
            'id' => $this->db->lastInsertId()
        ], 201);
    }

    /**
     * PUT /api/members/:id
     */
    public function update($id) {
        checkRole(['admin', 'Secrétaire']);

        $input = get_input();

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

        json_response([
            'success' => true,
            'message' => 'Membre mis à jour'
        ]);
    }

    /**
     * DELETE /api/members/:id
     */
    public function delete($id) {
        checkRole(['Administrateur']);

        $stmt = $this->db->prepare('DELETE FROM members WHERE id = ?');
        $stmt->execute([$id]);

        json_response([
            'success' => true,
            'message' => 'Membre supprimé'
        ]);
    }

    /**
     * POST /api/members/:id/photo
     * Upload member profile photo
     */
    public function upload_photo($id) {
        checkRole(['admin', 'Secrétaire']);

        // Verify member exists
        $member = $this->memberModel->search(['id' => $id]);
        if (empty($member)) {
            json_error('Membre non trouvé', 404);
        }

        if (empty($_FILES['photo'])) {
            json_error('Aucune photo fournie', 400);
        }

        $file = $_FILES['photo'];
        
        // Validate file
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file['type'], $allowed)) {
            json_error('Format non supporté. Formats acceptés: JPG, PNG, WEBP', 400);
        }

        if ($file['size'] > 5 * 1024 * 1024) { // 5MB max
            json_error('Photo trop volumineuse (max 5MB)', 400);
        }

        // Create upload directory
        $uploadDir = PROJECT_ROOT . '/uploads/members/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'member_' . $id . '_' . time() . '.' . $ext;
        $filepath = $uploadDir . $filename;

        // Move file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            json_error('Erreur lors du téléchargement', 500);
        }

        // Update database
        $stmt = $this->db->prepare('UPDATE members SET photo = ? WHERE id = ?');
        $stmt->execute(['uploads/members/' . $filename, $id]);

        json_response([
            'success' => true,
            'message' => 'Photo mise à jour',
            'photo' => 'uploads/members/' . $filename
        ]);
    }

    /**
     * GET /api/members/:id/photo
     * Retrieve member photo
     */
    public function get_photo($id) {
        $stmt = $this->db->prepare('SELECT photo FROM members WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch();

        if (!$result || !$result['photo']) {
            json_error('Photo non trouvée', 404);
        }

        $filepath = PROJECT_ROOT . '/' . $result['photo'];
        if (!file_exists($filepath)) {
            json_error('Fichier photo n\'existe pas', 404);
        }

        // Determine MIME type
        $mimeType = mime_content_type($filepath) ?: 'image/jpeg';
        header('Content-Type: ' . $mimeType);
        header('Cache-Control: max-age=86400');
        readfile($filepath);
        exit;
    }

    /**
     * DELETE /api/members/:id/photo
     * Delete member photo
     */
    public function delete_photo($id) {
        checkRole(['admin', 'Secrétaire']);

        $stmt = $this->db->prepare('SELECT photo FROM members WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch();

        if ($result && $result['photo']) {
            $filepath = PROJECT_ROOT . '/' . $result['photo'];
            if (file_exists($filepath)) {
                @unlink($filepath);
            }
        }

        $stmt = $this->db->prepare('UPDATE members SET photo = NULL WHERE id = ?');
        $stmt->execute([$id]);

        json_response([
            'success' => true,
            'message' => 'Photo supprimée'
        ]);
    }
}
