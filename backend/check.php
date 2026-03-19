<?php
/**
 * Diagnostic PHP - Vérifier les extensions requises
 */

echo "═══════════════════════════════════════════════════════════════════\n";
echo "DIAGNOSTIC PHP - MALOTY\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

// Informations PHP
echo "ℹ️  VERSION PHP\n";
echo "   PHP: " . phpversion() . "\n";
echo "   OS: " . php_uname() . "\n";
echo "   SAPI: " . php_sapi_name() . "\n\n";

// Vérifier les extensions requises
echo "🔍 EXTENSIONS REQUISES\n";

$required = [
    'PDO' => 'Extension PDO (obligatoire)',
    'pdo_mysql' => 'Pilote PDO MySQL (obligatoire)',
    'mysqli' => 'MySQLi (optionnel, alternative à PDO)',
    'json' => 'JSON (obligatoire)',
    'mbstring' => 'Multibyte String (obligatoire)',
    'curl' => 'cURL (optionnel)',
];

$missing = [];

foreach ($required as $ext => $desc) {
    if (extension_loaded($ext)) {
        echo "   ✅ {$ext} - {$desc}\n";
    } else {
        echo "   ❌ {$ext} - {$desc}\n";
        $missing[] = $ext;
    }
}

echo "\n";

// Afficher le php.ini
echo "📄 FICHIER CONFIGURATION PHP (php.ini)\n";
$iniFile = php_ini_loaded_file();
echo "   Fichier: {$iniFile}\n\n";

// Vérifier les fonctions désactivées
echo "🔒 FONCTIONS DÉSACTIVÉES\n";
$disabled = ini_get('disable_functions');
if (empty($disabled)) {
    echo "   ✅ Aucune fonction désactivée\n";
} else {
    echo "   ⚠️  Fonctions désactivées: {$disabled}\n";
}

echo "\n";

// Solutions
if (!empty($missing)) {
    echo "═══════════════════════════════════════════════════════════════════\n";
    echo "🔧 SOLUTIONS\n";
    echo "═══════════════════════════════════════════════════════════════════\n\n";
    
    if (in_array('pdo_mysql', $missing)) {
        echo "ACTIVER PDO_MYSQL SOUS XAMPP/WAMP:\n\n";
        echo "1. Ouvrez le fichier php.ini (généralement dans XAMPP/php/php.ini)\n";
        echo "2. Décommentez les lignes (enlevez le ;):\n";
        echo "   ;extension=pdo_mysql\n";
        echo "   devient:\n";
        echo "   extension=pdo_mysql\n\n";
        echo "3. Redémarrez Apache et MySQL dans XAMPP Control Panel\n";
        echo "4. Testez à nouveau\n\n";
    }
    
    if (in_array('PDO', $missing)) {
        echo "ACTIVER PDO SOUS XAMPP/WAMP:\n\n";
        echo "1. Ouvrez php.ini\n";
        echo "2. Décommentez:\n";
        echo "   ;extension=pdo\n";
        echo "   devient:\n";
        echo "   extension=pdo\n\n";
        echo "3. Redémarrez les services\n\n";
    }
}

// Tests de connexion
echo "═══════════════════════════════════════════════════════════════════\n";
echo "🗄️  TEST DE CONNEXION MYSQL\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

if (extension_loaded('pdo_mysql')) {
    try {
        $pdo = new PDO(
            'mysql:host=localhost;port=3306',
            'root',
            '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "✅ Connexion MySQL réussie!\n";
        echo "   Host: localhost\n";
        echo "   User: root\n";
        
        // Vérifier les bases de données
        $result = $pdo->query("SHOW DATABASES");
        $databases = $result->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('eglise_m', $databases)) {
            echo "   ✅ Base 'eglise_m' trouvée\n";
        } else {
            echo "   ⚠️  Base 'eglise_m' non trouvée (normal si pas encore créée)\n";
        }
    } catch (PDOException $e) {
        echo "❌ Erreur de connexion MySQL:\n";
        echo "   " . $e->getMessage() . "\n\n";
        echo "Vérifiez:\n";
        echo "1. MySQL est en cours d'exécution\n";
        echo "2. L'host est correct (localhost:3306)\n";
        echo "3. L'utilisateur root existe\n";
        echo "4. Le mot de passe est correct (laissez vide si aucun)\n";
    }
} else {
    echo "❌ Extension PDO_MySQL non trouvée!\n";
}

echo "\n";

// Fichiers obligatoires
echo "═══════════════════════════════════════════════════════════════════\n";
echo "📁 FICHIERS OBLIGATOIRES\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

$requiredFiles = [
    'config/config.php',
    'config/database.php',
    '.env.php',
    'database.sql',
    'models/BaseModel.php',
    'controllers/BaseController.php',
    'views/layout.php',
];

$projectRoot = dirname(__DIR__);

foreach ($requiredFiles as $file) {
    $path = $projectRoot . '/' . $file;
    if (file_exists($path)) {
        echo "   ✅ {$file}\n";
    } else {
        echo "   ❌ {$file} - MANQUANT!\n";
    }
}

echo "\n";

?>
