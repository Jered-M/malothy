<?php
define('PROJECT_ROOT', dirname(__DIR__));
require_once dirname(__DIR__) . '/backend/config/database.php';

$db = Database::getInstance()->getConnection();

// Voir les valeurs de l'enum expense_status
$rows = $db->query("
    SELECT e.enumlabel
    FROM pg_type t
    JOIN pg_enum e ON t.oid = e.enumtypid
    WHERE t.typname = 'expense_status'
    ORDER BY e.enumsortorder
")->fetchAll(PDO::FETCH_COLUMN);
echo "Enum expense_status values: " . implode(', ', $rows) . PHP_EOL;

// Voir les valeurs distinctes de payment_status dans tithes
$rows2 = $db->query("SELECT DISTINCT payment_status FROM tithes")->fetchAll(PDO::FETCH_COLUMN);
echo "Tithes payment_status distinct: " . implode(', ', $rows2) . PHP_EOL;

// Voir les valeurs distinctes de payment_status dans offerings
$rows3 = $db->query("SELECT DISTINCT payment_status FROM offerings")->fetchAll(PDO::FETCH_COLUMN);
echo "Offerings payment_status distinct: " . implode(', ', $rows3) . PHP_EOL;

// Voir les valeurs distinctes de status dans expenses
$rows4 = $db->query("SELECT DISTINCT status FROM expenses")->fetchAll(PDO::FETCH_COLUMN);
echo "Expenses status distinct: " . implode(', ', $rows4) . PHP_EOL;
