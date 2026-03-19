<?php
require_once __DIR__ . '/../backend/config/database.php';
$pdo = Database::getInstance()->getConnection();
$stmt = $pdo->query("SHOW COLUMNS FROM expenses LIKE 'status'");
$row = $stmt->fetch();
echo "Type: [" . $row['Type'] . "]\n";
echo "Default: [" . $row['Default'] . "]\n";
