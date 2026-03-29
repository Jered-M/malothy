<?php
define('PROJECT_ROOT', __DIR__);
require_once __DIR__ . '/backend/config/api-config.php';
require_once __DIR__ . '/backend/models/User.php';

header('Content-Type: application/json');

$userModel = new User();
$email = 'admin@maloty.com';
$password = 'admin123';

try {
    // 1. Vérifer si la base répond
    $db = Database::getInstance()->getConnection();
    echo json_encode([
        'status' => 'DB_CONNECTED',
        'checking_user' => $email,
        'app_debug' => defined('APP_DEBUG') ? APP_DEBUG : 'not defined'
    ], JSON_PRETTY_PRINT);

    // 2. Tenter de trouver l'utilisateur
    $stmt = $db->prepare("SELECT id, email, password, status FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $verify = password_verify($password, $user['password']);
        
        // TEST BRUT SANS LA BASE (pour comparer)
        $test_hash = password_hash('admin123', PASSWORD_BCRYPT);
        $test_verify = password_verify('admin123', $user['password']);

        echo json_encode([
            'user_found' => true,
            'user_status' => $user['status'],
            'password_match_from_db' => $verify,
            'password_match_test_brute' => $test_verify,
            'hash_in_db' => $user['password'],
            'new_valid_hash_from_server' => $test_hash
        ], JSON_PRETTY_PRINT);
    } else {
        echo json_encode(['user_found' => false, 'error' => 'Utilisateur non trouvé dans la table users'], JSON_PRETTY_PRINT);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'ERROR', 'message' => $e->getMessage()], JSON_PRETTY_PRINT);
}
