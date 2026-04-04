<?php
if (!defined('PROJECT_ROOT')) define('PROJECT_ROOT', dirname(__DIR__));
require_once PROJECT_ROOT . '/backend/config/api-config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if member_id exists in offerings
    $stmt = $db->query("SHOW COLUMNS FROM offerings LIKE 'member_id'");
    if (!$stmt->fetch()) {
        echo "Adding member_id to offerings table...\n";
        $db->exec("ALTER TABLE offerings ADD COLUMN member_id INT DEFAULT NULL AFTER id");
        $db->exec("ALTER TABLE offerings ADD FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL");
        echo "Successfully added member_id column.\n";
    } else {
        echo "member_id already exists in offerings table.\n";
    }

    // Check for payment_status in tithes and offerings
    $tables = ['tithes', 'offerings'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW COLUMNS FROM {$table} LIKE 'payment_status'");
        if (!$stmt->fetch()) {
            echo "Adding payment_status to {$table} table...\n";
            $db->exec("ALTER TABLE {$table} ADD COLUMN payment_status VARCHAR(20) DEFAULT 'completed'");
            echo "Successfully added payment_status column to {$table}.\n";
        }
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
