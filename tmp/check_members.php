<?php
require_once __DIR__ . '/../backend/config/database.php';

$db = Database::getInstance();
$stmt = $db->query("SELECT * FROM members");
$members = $stmt->fetchAll();

echo json_encode($members, JSON_PRETTY_PRINT);
