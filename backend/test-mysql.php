<?php
// Test détaillé de la connexion MySQL

echo "═══════════════════════════════════════════════════════\n";
echo "TEST DE CONNEXION MYSQL\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Test 1: Vérifier si PDO est disponible
echo "1️⃣  PDO disponible: ";
echo extension_loaded('pdo') ? "✅ OUI\n" : "❌ NON\n";

// Test 2: Vérifier si mysqli est disponible
echo "2️⃣  MySQLi disponible: ";
echo extension_loaded('mysqli') ? "✅ OUI\n" : "❌ NON\n";

// Test 3: Vérifier les pilotes PDO disponibles
echo "\n3️⃣  Pilotes PDO disponibles:\n";
$drivers = PDO::getAvailableDrivers();
print_r($drivers);

// Test 4: Essayer de charger pdo_mysql manuellement
echo "\n4️⃣  Tentative de connexion PDO MySQL:\n";
try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;dbname=eglise_m',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );
    echo "✅ Connexion PDO réussie!\n";
    
    // Tester une requête
    $result = $pdo->query('SELECT 1');
    echo "✅ Requête SELECT réussie!\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur PDO: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}

// Test 5: Essayer avec MySQLi
echo "\n5️⃣  Tentative de connexion MySQLi:\n";
try {
    $mysqli = new mysqli('127.0.0.1', 'root', '', 'eglise_m');
    if ($mysqli->connect_error) {
        echo "❌ Erreur MySQLi: " . $mysqli->connect_error . "\n";
    } else {
        echo "✅ Connexion MySQLi réussie!\n";
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "❌ Exception MySQLi: " . $e->getMessage() . "\n";
}

echo "\n═══════════════════════════════════════════════════════\n";
?>
