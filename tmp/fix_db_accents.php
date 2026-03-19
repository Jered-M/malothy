<?php
require_once __DIR__ . '/../backend/config/database.php';
$pdo = Database::getInstance()->getConnection();

echo "Changing status column to avoid accent issues...\n";
$sql = "ALTER TABLE expenses MODIFY COLUMN status ENUM('en attente', 'approuvee', 'rejetee') NOT NULL DEFAULT 'en attente'";
$pdo->exec($sql);

echo "Updating existing rows...\n";
// Some rows might have empty status if they Were inserted with 'approuvée' that failed.
$pdo->exec("UPDATE expenses SET status = 'en attente' WHERE status = ''");

echo "Done!\n";
