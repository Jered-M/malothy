<?php
require_once __DIR__ . '/../backend/config/database.php';
$db = Database::getInstance();
$pdo = $db->getConnection();

$id = 6; 
$status = 'approuvee';

$sql = "UPDATE expenses SET status = ? WHERE id = ?";
$stmt = $pdo->prepare($sql);
$result = $stmt->execute([$status, $id]);

echo "Update result: " . ($result ? "Success" : "Failure") . "\n";

$stmt = $pdo->prepare("SELECT status FROM expenses WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();
echo "New status: [" . $row['status'] . "]\n";
