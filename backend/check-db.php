<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1', 'root', '');
    $result = $pdo->query('SHOW DATABASES');
    $dbs = $result->fetchAll(PDO::FETCH_COLUMN);
    echo "Bases de données:\n";
    print_r($dbs);
    
    if (in_array('eglise_m', $dbs)) {
        echo "\n✅ Base eglise_m trouvée\n";
        $pdo->exec('USE eglise_m');
        $result = $pdo->query('SHOW TABLES');
        $tables = $result->fetchAll(PDO::FETCH_COLUMN);
        echo "Tables: " . implode(', ', $tables) . "\n";
        echo "Total: " . count($tables) . " tables\n";
    } else {
        echo "\n❌ Base eglise_m NON trouvée\n";
    }
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
}
?>
