<?php
require 'backend/config/database.php';
require 'backend/api/services/LocalPaymentService.php';

try {
    $db = Database::getInstance()->getConnection();
    $service = new LocalPaymentService($db);
    $result = $service->initializeTable();
    
    echo "Result: " . $result['status'] . "\n";
    echo "Message: " . $result['message'] . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
