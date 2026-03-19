<?php
require_once __DIR__ . '/../backend/config/database.php';
$db = Database::getInstance();
$pdo = $db->getConnection();
$stmt = $pdo->query("SELECT * FROM expenses");
$expenses = $stmt->fetchAll();
header('Content-Type: application/json');
echo json_encode($expenses, JSON_PRETTY_PRINT);
