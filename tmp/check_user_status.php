<?php
require_once __DIR__ . '/../backend/config/database.php';
$pdo = Database::getInstance()->getConnection();
$users = $pdo->query("SELECT id, name, role, status FROM users")->fetchAll();
foreach ($users as $u) {
    echo "ID: {$u['id']} | Name: {$u['name']} | Role: [" . $u['role'] . "] | Status: [" . $u['status'] . "]\n";
}
