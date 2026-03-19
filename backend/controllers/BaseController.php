<?php
/**
 * Classe de base pour tous les contrôleurs
 */

abstract class BaseController {
    protected $data = [];

    /**
     * Charger une vue
     */
    protected function view($viewName, $data = []) {
        $this->data = array_merge($this->data, $data);
        
        $viewPath = PROJECT_ROOT . "/views/{$viewName}.php";
        
        if (!file_exists($viewPath)) {
            die('Vue non trouvée: ' . $viewName);
        }

        extract($this->data);
        include $viewPath;
    }

    /**
     * Redirection JSON pour requêtes AJAX
     */
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirection simple
     */
    protected function redirect($url) {
        header("Location: $url");
        exit;
    }

    /**
     * Ajouter un message flash
     */
    protected function setFlash($message, $type = 'success') {
        $_SESSION['flash'] = [
            'message' => $message,
            'type' => $type
        ];
    }

    /**
     * Récupérer et nettoyer le message flash
     */
    protected function getFlash() {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }

    /**
     * Valider une requête POST
     */
    protected function validateRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Méthode non autorisée');
        }
    }

    /**
     * Obtenir les données POST
     */
    protected function getPostData() {
        return $_POST;
    }

    /**
     * Obtenir une valeur POST
     */
    protected function getPost($key, $default = null) {
        return $_POST[$key] ?? $default;
    }

    /**
     * Obtenir une valeur GET
     */
    protected function getQuery($key, $default = null) {
        return $_GET[$key] ?? $default;
    }

    /**
     * Vérifier les autorisations
     */
    protected function requireLogin() {
        if (!isLoggedIn()) {
            $this->setFlash('Veuillez vous connecter', 'error');
            $this->redirect('/index.php?controller=auth&action=login');
        }
    }

    protected function requireRole($role) {
        $this->requireLogin();
        if (!hasRole($role)) {
            $this->setFlash('Accès non autorisé', 'error');
            $this->redirect('/index.php?controller=auth&action=forbidden');
        }
    }

    /**
     * Valider les données d'entrée
     */
    protected function validate($data, $rules) {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;

            if (strpos($rule, 'required') !== false && empty($value)) {
                $errors[$field] = "Le champ {$field} est requis";
            }

            if (strpos($rule, 'email') !== false && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = "L'email {$field} n'est pas valide";
            }

            if (strpos($rule, 'numeric') !== false && !empty($value) && !is_numeric($value)) {
                $errors[$field] = "Le champ {$field} doit être numérique";
            }

            if (strpos($rule, 'min:') !== false && !empty($value)) {
                preg_match('/min:(\d+)/', $rule, $matches);
                if (strlen($value) < $matches[1]) {
                    $errors[$field] = "Le champ {$field} doit avoir au moins {$matches[1]} caractères";
                }
            }
        }

        return $errors;
    }
}
?>
