<?php
require_once __DIR__ . '/../backend/config/database.php';
$pdo = Database::getInstance()->getConnection();
$stmt = $pdo->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 20");
$logs = $stmt->fetchAll();
foreach ($logs as $log) {
    echo "ID: {$log['id']} | Action: {$log['action']} | Table: {$log['table_name']} | UserID: " . ($log['user_id'] ?? 'NULL') . " | Time: {$log['created_at']}\n";
}
