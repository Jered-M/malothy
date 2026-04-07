<?php
/**
 * API AuthController
 * Endpoints: /api/auth/login, /api/auth/logout, etc.
 */

require_once PROJECT_ROOT . '/backend/models/User.php';

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    /**
     * POST /api/auth/login
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('Method POST required', 405);
        }

        $input = get_input();
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        if (!$email || !$password) {
            json_error('Email et mot de passe requis', 400);
        }

        $user = $this->userModel->authenticate($email, $password);
        
        if (!$user) {
            json_error('Identifiants invalides', 401);
        }

        // Créer une session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = strtolower($user['role']);
        $_SESSION['user_name'] = $user['name'];
        
        // Générer un token simple (session ID)
        $token = session_id();

        // Déterminer la redirection (Tous vers le SPA)
        $redirect = '/dashboard';

        json_response([
            'success' => true,
            'message' => 'Connecté avec succès',
            'token' => $token,
            'redirect' => $redirect,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
    }

    /**
     * POST /api/auth/logout
     */
    public function logout() {
        session_destroy();
        json_response(['success' => true, 'message' => 'Déconnexion réussie']);
    }

    /**
     * GET /api/auth/profile
     */
    public function profile() {
        $authUser = get_authenticated_user();
        $userId = $authUser['id'];
        $user = $this->userModel->queryOne('SELECT id, name, email, role FROM users WHERE id = ?', [$userId]);
        
        if (!$user) {
            json_error('Utilisateur non trouvé', 404);
        }

        json_response([
            'success' => true,
            'user' => $user
        ]);
    }
}
