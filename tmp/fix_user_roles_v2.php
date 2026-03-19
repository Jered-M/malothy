<?php
require_once __DIR__ . '/../backend/config/database.php';
$pdo = Database::getInstance()->getConnection();

echo "Forcing roles by ID...\n";
$pdo->exec("UPDATE users SET role = 'admin' WHERE id = 1");
$pdo->exec("UPDATE users SET role = 'tresorier' WHERE id = 2");
$pdo->exec("UPDATE users SET role = 'secretaire' WHERE id = 3");

echo "Checking result...\n";
$users = $pdo->query("SELECT id, name, role FROM users")->fetchAll();
foreach ($users as $u) {
    echo "ID: {$u['id']} | Name: {$u['name']} | Role: {$u['role']}\n";
}
