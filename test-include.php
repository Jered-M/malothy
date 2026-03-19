<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('PROJECT_ROOT', 'c:\Users\HP\Documents\site\MALOTY');

echo "PROJECT_ROOT: " . PROJECT_ROOT . "\n";
echo "Requesting User.php from:\n";
echo PROJECT_ROOT . '/backend/models/User.php' . "\n";

if (!file_exists(PROJECT_ROOT . '/backend/models/User.php')) {
    die("User.php NOT FOUND");
}

try {
    require_once PROJECT_ROOT . '/backend/models/User.php';
    echo "User.php loaded OK\n";
} catch (Exception $e) {
    echo "User.php ERROR: " . $e->getMessage() . "\n";
    die();
}

try {
    require_once PROJECT_ROOT . '/backend/api/controllers/AuthController.php';
    echo "AuthController.php loaded OK\n";
} catch (Exception $e) {
    echo "AuthController.php ERROR: " . $e->getMessage() . "\n";
    die();
}

// Test class exists
if (class_exists('AuthController')) {
    echo "AuthController CLASS EXISTS\n";
} else {
    echo "AuthController CLASS NOT FOUND\n";
}

?>
