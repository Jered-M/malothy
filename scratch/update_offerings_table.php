<?php
require_once __DIR__ . '/../backend/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if donation_mode column exists in offerings table
    $stmt = $db->query("
        SELECT COUNT(*) 
        FROM information_schema.columns 
        WHERE table_name = 'offerings' AND column_name = 'donation_mode'
    ");
    $exists = $stmt->fetchColumn();

    if (!$exists) {
        $db->exec("ALTER TABLE offerings ADD COLUMN donation_mode VARCHAR(20) DEFAULT 'espece'");
        echo "Successfully added 'donation_mode' column to 'offerings' table.\n";
    } else {
        echo "'donation_mode' column already exists in 'offerings' table.\n";
    }

} catch (Exception $e) {
    echo "Error updating offerings table: " . $e->getMessage() . "\n";
}
