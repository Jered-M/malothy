<?php
/**
 * Configuration générale de l'application
 */

require_once __DIR__ . '/database.php';

// Démarrer la session (seulement si pas déjà commencée et aucun output envoyé)
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

// Fonction helper pour afficher les erreurs
function dd($var, $die = true) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
    if ($die) die();
}

// Fonction helper pour redirection
function redirect($url) {
    header("Location: $url");
    exit;
}

// Fonction pour vérifier la connexion
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fonction pour obtenir l'utilisateur connecté
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return $_SESSION['user'] ?? null;
}

// Fonction pour vérifier le rôle
function hasRole($role) {
    $user = getCurrentUser();
    return $user && $user['role'] === $role;
}

// Fonction pour vérifier les rôles multiples
function hasRoles($roles) {
    $user = getCurrentUser();
    if (!$user) return false;
    return in_array($user['role'], $roles);
}

// Fonction pour protéger une page (nécessite authentification)
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/index.php?controller=auth&action=login');
    }
}

// Fonction pour protéger par rôle
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            http_response_code(403);
            echo json_encode(['error' => 'Accès non autorisé']);
            exit;
        }
        redirect('/index.php?controller=auth&action=forbidden');
    }
}

// Fonctions utilitaires
function sanitize($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function formatDate($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

function formatMoney($amount) {
    return number_format($amount, 2, ',', ' ') . ' €';
}

// Rôles disponibles
define('ROLE_ADMIN', 'admin');
define('ROLE_TREASURER', 'trésorier');
define('ROLE_SECRETARY', 'secrétaire');

// Statuts des membres
define('STATUS_ACTIVE', 'actif');
define('STATUS_INACTIVE', 'inactif');
define('STATUS_SUSPENDED', 'suspendu');

// Catégories de dépenses
define('EXPENSE_CATEGORIES', [
    'loyer' => 'Loyer',
    'salaire' => 'Salaires',
    'mission' => 'Missions',
    'entretien' => 'Entretien',
    'communion' => 'Article de communion',
    'autre' => 'Autre'
]);

// Types d'offrandes
define('OFFERING_TYPES', [
    'culte' => 'Culte',
    'evenement' => 'Événement',
    'mission' => 'Mission',
    'autre' => 'Autre'
]);
?>
