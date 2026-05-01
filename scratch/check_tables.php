<?php
require 'backend/config/database.php';
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found: " . implode(', ', $tables) . "\n";
    
    if (in_array('payments', $tables)) {
        echo "Table 'payments' exists.\n";
    } else {
        echo "Table 'payments' is MISSING.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
