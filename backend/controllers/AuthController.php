<?php
/**
 * Contrôleur pour l'authentification
 */

require_once PROJECT_ROOT . '/controllers/BaseController.php';
require_once PROJECT_ROOT . '/models/User.php';

class AuthController extends BaseController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    /**
     * Afficher la page de connexion
     */
    public function login() {
        // Si déjà connecté, rediriger vers le dashboard
        if (isLoggedIn()) {
            $this->redirect('/index.php?controller=dashboard&action=index');
        }

        $flash = $this->getFlash();
        $this->view('auth/login', ['flash' => $flash]);
    }

    /**
     * Traiter la connexion
     */
    public function loginProcess() {
        $this->validateRequest();

        $email = $this->getPost('email');
        $password = $this->getPost('password');

        // Valider les données
        $errors = $this->validate(
            ['email' => $email, 'password' => $password],
            ['email' => 'required|email', 'password' => 'required']
        );

        if (!empty($errors)) {
            $this->setFlash('Données invalides', 'error');
            $this->redirect('/index.php?controller=auth&action=login');
        }

        // Authentifier
        $user = $this->userModel->authenticate($email, $password);

        if (!$user) {
            $this->setFlash('Email ou mot de passe incorrect', 'error');
            $this->redirect('/index.php?controller=auth&action=login');
        }

        // Créer la session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        $this->setFlash('Connexion réussie', 'success');
        $this->redirect('/index.php?controller=dashboard&action=index');
    }

    /**
     * Déconnexion
     */
    public function logout() {
        $this->validateRequest();
        
        session_destroy();
        $this->setFlash('Déconnexion réussie', 'success');
        $this->redirect('/index.php?controller=auth&action=login');
    }

    /**
     * Afficher la page de changement de mot de passe
     */
    public function changePassword() {
        $this->requireLogin();

        $flash = $this->getFlash();
        $this->view('auth/change-password', ['flash' => $flash]);
    }

    /**
     * Traiter le changement de mot de passe
     */
    public function changePasswordProcess() {
        $this->requireLogin();
        $this->validateRequest();

        $userId = $_SESSION['user_id'];
        $currentPassword = $this->getPost('current_password');
        $newPassword = $this->getPost('new_password');
        $confirmPassword = $this->getPost('confirm_password');

        // Valider les données
        $errors = $this->validate(
            [
                'current_password' => $currentPassword,
                'new_password' => $newPassword,
                'confirm_password' => $confirmPassword
            ],
            [
                'current_password' => 'required',
                'new_password' => 'required|min:6',
                'confirm_password' => 'required'
            ]
        );

        if (!empty($errors)) {
            $this->setFlash('Données invalides', 'error');
            $this->redirect('/index.php?controller=auth&action=changePassword');
        }

        if ($newPassword !== $confirmPassword) {
            $this->setFlash('Les mots de passe ne correspondent pas', 'error');
            $this->redirect('/index.php?controller=auth&action=changePassword');
        }

        // Vérifier le mot de passe actuel
        $user = $this->userModel->findById($userId);
        if (!password_verify($currentPassword, $user['password'])) {
            $this->setFlash('Mot de passe actuel incorrect', 'error');
            $this->redirect('/index.php?controller=auth&action=changePassword');
        }

        // Changer le mot de passe
        $this->userModel->changePassword($userId, $newPassword);

        $this->setFlash('Mot de passe changé avec succès', 'success');
        $this->redirect('/index.php?controller=dashboard&action=index');
    }

    /**
     * Page d'accès refusé
     */
    public function forbidden() {
        http_response_code(403);
        $this->view('errors/forbidden');
    }

    /**
     * Page non trouvée
     */
    public function notFound() {
        http_response_code(404);
        $this->view('errors/404');
    }
}
?>
