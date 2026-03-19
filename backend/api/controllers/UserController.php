<?php
/**
 * API UserController
 * Manage users (create, update, delete) - Admin Only
 */

require_once PROJECT_ROOT . '/backend/models/User.php';

class UserController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    /**
     * GET /api/users
     */
    public function index() {
        checkRole(['admin']);
        $users = $this->userModel->getAllActive();
        json_response(['success' => true, 'data' => $users]);
    }

    /**
     * POST /api/users
     */
    public function create() {
        checkRole(['admin']);
        $input = get_input();

        $required = ['name', 'email', 'password', 'role'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                json_error("Champ '{$field}' requis", 400);
            }
        }

        // Check if email already exists
        if ($this->userModel->findByEmail($input['email'])) {
            json_error("Cet email est déjà utilisé", 400);
        }

        $data = [
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'role' => $input['role'],
            'status' => 'actif'
        ];

        $id = $this->userModel->create($data);
        json_response(['success' => true, 'id' => $id, 'message' => 'Utilisateur créé'], 201);
    }

    /**
     * PUT /api/users/:id
     */
    public function update($id) {
        checkRole(['Administrateur']);
        $input = get_input();

        $this->userModel->updateUser($id, $input);
        json_response(['success' => true, 'message' => 'Utilisateur mis à jour']);
    }

    /**
     * DELETE /api/users/:id
     */
    public function delete($id) {
        checkRole(['Administrateur']);
        
        // Logical delete
        $this->userModel->update($id, ['status' => 'inactif']);
        json_response(['success' => true, 'message' => 'Utilisateur désactivé']);
    }
}
