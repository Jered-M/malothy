<?php
define('PROJECT_ROOT', __DIR__);
require_once __DIR__ . '/backend/config/api-config.php';
require_once __DIR__ . '/backend/models/User.php';

header('Content-Type: application/json');

$email = 'admin@maloty.com';
$newPassword = 'admin123';
$hash = password_hash($newPassword, PASSWORD_BCRYPT);

try {
    $db = Database::getInstance()->getConnection();
    
    // Vérifier si l'utilisateur existe
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Mise à jour si existe
        $stmt = $db->prepare("UPDATE users SET password = ?, status = 'actif', role = 'admin' WHERE id = ?");
        $stmt->execute([$hash, $user['id']]);
        echo json_encode(['success' => true, 'message' => "Mot de passe de l'admin mis à jour avec succès !", 'new_hash' => $hash]);
    } else {
        // Création si n'existe pas
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Administrateur', $email, $hash, 'admin', 'actif']);
        echo json_encode(['success' => true, 'message' => "Administrateur créé avec succès !", 'new_hash' => $hash]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
