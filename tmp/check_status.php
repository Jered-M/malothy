<?php
require_once __DIR__ . '/../backend/config/database.php';
$db = Database::getInstance();
$pdo = $db->getConnection();
$stmt = $pdo->query("SELECT status, count(*) as count FROM expenses GROUP BY status");
$summary = $stmt->fetchAll();
print_r($summary);
