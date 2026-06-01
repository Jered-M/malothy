<?php
require 'backend/config/database.php';
try {
    $db = Database::getInstance()->getConnection();
    foreach (['tithes', 'offerings', 'expenses'] as $t) {
        $q = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = '$t'");
        echo "=== $t ===\n";
        foreach ($q->fetchAll() as $r) {
            echo "  " . $r['column_name'] . " (" . $r['data_type'] . ")\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
