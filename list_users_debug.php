<?php
require 'backend/config/database.php';
$db = Database::getInstance()->getConnection();
$stmt = $db->query('SELECT name, email, role FROM users');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode($row) . PHP_EOL;
}
