<?php
require_once __DIR__ . '/../backend/config/database.php';
$pdo = Database::getInstance()->getConnection();

echo "Standardizing roles in users table...\n";
$pdo->exec("UPDATE users SET role = 'tresorier' WHERE role LIKE '%sorier%'");
$pdo->exec("UPDATE users SET role = 'secretaire' WHERE role LIKE '%ecretaire%'");
$pdo->exec("UPDATE users SET role = 'admin' WHERE role = 'administrateur' OR role = 'Admin'");

echo "Roles updated.\n";

$users = $pdo->query("SELECT id, name, role FROM users")->fetchAll();
foreach ($users as $u) {
    echo "ID: {$u['id']} | Name: {$u['name']} | Role: {$u['role']}\n";
}
