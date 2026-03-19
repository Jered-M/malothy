<?php
require_once __DIR__ . '/config/database.php';

$users = [
    'admin@maloty.com' => 'admin123',
    'treasure@maloty.com' => 'treas123',
    'secretary@maloty.com' => 'sec123'
];

$db = Database::getInstance();
$pdo = $db->getConnection();

echo "Mise à jour des mots de passe des utilisateurs de démo...\n\n";

foreach ($users as $email => $password) {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE email = ?');
    $stmt->execute([$hash, $email]);
    echo "✅ $email → mot de passe réinitialisé\n";
}

echo "\n";
$stmt = $pdo->query('SELECT id, email, password FROM users');
$rows = $stmt->fetchAll();
echo "Utilisateurs actuels:\n";
foreach ($rows as $row) {
    echo "  - {$row['email']}\n";
}

echo "\n✅ Vous pouvez maintenant vous connecter avec:\n";
echo "  Email: admin@maloty.com\n";
echo "  Mot de passe: admin123\n";
?>
