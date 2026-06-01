<?php
require 'backend/config/database.php';
try {
    $db = Database::getInstance()->getConnection();
    
    // Add currency column to expenses if it does not exist
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'pgsql') {
        $db->exec("ALTER TABLE expenses ADD COLUMN IF NOT EXISTS currency VARCHAR(3) DEFAULT 'CDF'");
        echo "PostgreSQL: currency column added or verified in expenses table.\n";
    } else {
        // MySQL fallback
        try {
            $db->exec("ALTER TABLE expenses ADD COLUMN currency VARCHAR(3) DEFAULT 'CDF'");
            echo "MySQL: currency column added to expenses table.\n";
        } catch (Exception $e) {
            echo "MySQL Notice (likely already exists): " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
