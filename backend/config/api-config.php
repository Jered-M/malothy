<?php
/**
 * Configuration Backend API
 */

// Démarrer les sessions
if (session_status() === PHP_SESSION_NONE) {
    // Si un token est passé via Authorization Header, l'utiliser comme session_id
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
        $token = trim($matches[1]);
        if (!empty($token) && $token !== 'null' && $token !== 'undefined') {
            session_id($token);
        }
    }
    
    session_start();
}

// Charger les dépendances
require_once PROJECT_ROOT . '/backend/config/database.php';

// Headers CORS (Optimisé pour production et local)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed_origins = ['http://localhost:8000', 'https://malothy.onrender.com'];

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
} else if (empty($origin)) {
    // Requête de même origine (Same-origin), pas d'en-tête nécessaire mais on peut laisser passer pour le dev local sans Origin header
} else {
    // En dernier recours pour le dev, mais plus de Credentials
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gestion CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Fonctions utilitaires
function json_response($data, $status = 200) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error($message, $status = 400) {
    json_response(['error' => $message], $status);
}

function get_input() {
    return json_decode(file_get_contents('php://input'), true) ?? $_POST;
}

// Récupérer l'utilisateur depuis la session
function get_authenticated_user() {
    if (!isset($_SESSION['user_id'])) {
        json_error('Non authentifié', 401);
    }
    return [
        'id' => $_SESSION['user_id'],
        'role' => $_SESSION['user_role'] ?? 'secrétaire'
    ];
}

/**
 * Middleware de vérification de rôle
 * @param array $allowedRoles Liste des rôles autorisés (ex: ['admin', 'trésorier'])
 */
function checkRole($allowedRoles) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Non authentifié']);
        exit;
    }

    $roleRaw = $_SESSION['user_role'] ?? 'invité';
    // Normaliser au maximum : minuscule, sans accents (si possible), et purger les caractères chelous
    $roleNorm = strtolower($roleRaw);
    $roleNorm = str_replace(['é', 'è', 'ê', 'ë'], 'e', $roleNorm);
    
    $authorized = false;
    foreach ($allowedRoles as $roleOption) {
        $allowed = strtolower($roleOption);
        $allowed = str_replace(['é', 'è', 'ê', 'ë'], 'e', $allowed);
        $allowed = str_replace(['à', 'â', 'ä'], 'a', $allowed);
        
        // Match flexible
        if (strpos($roleNorm, $allowed) !== false || strpos($allowed, $roleNorm) !== false) {
            $authorized = true; break;
        }
        
        // Fallbacks explicites pour rôles manglés
        if ($allowed === 'tresorier' && (strpos($roleNorm, 'sorier') !== false || strpos($roleNorm, 'tr') === 0)) {
            $authorized = true; break;
        }
        if ($allowed === 'admin' && (strpos($roleNorm, 'adm') !== false)) {
            $authorized = true; break;
        }
        if ($allowed === 'secretaire' && (strpos($roleNorm, 'ecretaire') !== false || strpos($roleNorm, 'sec') === 0)) {
            $authorized = true; break;
        }
    }

    if (!$authorized) {
        http_response_code(403);
        echo json_encode(['error' => "Accès refusé. Rôle '{$roleRaw}' non autorisé."]);
        exit;
    }

    return [
        'id' => $_SESSION['user_id'],
        'role' => $roleNorm,
        'name' => $_SESSION['user_name'] ?? 'Utilisateur'
    ];
}

/**
 * Utilitaires pour charger les vues de manière propre
 */
function view($path, $data = []) {
    extract($data);
    require_once PROJECT_ROOT . "/frontend/views/{$path}.php";
}

/**
 * Récupère l'utilisateur actuel (compatibilité BaseModel)
 */
function getCurrentUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'role' => $_SESSION['user_role'] ?? 'invité',
        'name' => $_SESSION['user_name'] ?? 'Utilisateur'
    ];
}

