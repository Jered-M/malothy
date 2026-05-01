#!/usr/bin/env php
<?php
/**
 * Script de Vérification du Sandbox
 * 
 * Vérifie que tout est correctement configuré
 * 
 * Utilisation:
 *   php verify-sandbox.php
 */

define('PROJECT_ROOT', __DIR__);

echo "\n🔍 VÉRIFICATION DE LA CONFIGURATION SANDBOX\n";
echo "============================================\n\n";

$checks = [];

// ============================================
// 1. Vérifier les fichiers
// ============================================

echo "1️⃣  Vérification des fichiers...\n";

$requiredFiles = [
    'backend/config/sandbox-config.php' => 'Configuration Sandbox',
    'backend/api/controllers/PaymentSandboxController.php' => 'Contrôleur Sandbox',
    'frontend/test-sandbox.html' => 'Interface de Test',
    'start-sandbox.ps1' => 'Script PowerShell',
    'start-sandbox.bat' => 'Script Batch',
];

foreach ($requiredFiles as $file => $description) {
    $path = PROJECT_ROOT . '/' . $file;
    if (file_exists($path)) {
        echo "   ✅ $description\n";
        $checks['files'][] = true;
    } else {
        echo "   ❌ $description - MANQUANT: $file\n";
        $checks['files'][] = false;
    }
}

// ============================================
// 2. Vérifier PHP et Extensions
// ============================================

echo "\n2️⃣  Vérification PHP et Extensions...\n";

echo "   ✅ PHP " . PHP_VERSION . "\n";

$extensions = ['pdo', 'pdo_mysql', 'pdo_pgsql', 'json'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✅ Extension: $ext\n";
        $checks['extensions'][] = true;
    } else {
        if ($ext === 'pdo_pgsql') {
            echo "   ⚠️  Extension optionnelle: $ext\n";
        } else {
            echo "   ⚠️  Extension: $ext (optionnelle)\n";
        }
    }
}

// ============================================
// 3. Vérifier la Configuration
// ============================================

echo "\n3️⃣  Vérification de la Configuration...\n";

require_once PROJECT_ROOT . '/backend/config/sandbox-config.php';

if (defined('SANDBOX_MODE')) {
    echo "   ✅ SANDBOX_MODE défini: " . (SANDBOX_MODE ? 'true' : 'false') . "\n";
    $checks['config'][] = true;
} else {
    echo "   ❌ SANDBOX_MODE non défini\n";
    $checks['config'][] = false;
}

if (defined('SANDBOX_CONFIG') && is_array(SANDBOX_CONFIG)) {
    echo "   ✅ SANDBOX_CONFIG configuré\n";
    echo "      - Mode: " . SANDBOX_CONFIG['mode'] . "\n";
    echo "      - Réseaux autorisés: " . count(SANDBOX_CONFIG['allowed_networks']) . "\n";
    $checks['config'][] = true;
} else {
    echo "   ❌ SANDBOX_CONFIG non disponible\n";
    $checks['config'][] = false;
}

// ============================================
// 4. Vérifier les Répertoires
// ============================================

echo "\n4️⃣  Vérification des Répertoires...\n";

$logDir = PROJECT_ROOT . '/tmp/sandbox-logs';
if (!file_exists($logDir)) {
    @mkdir($logDir, 0755, true);
}

if (is_writable($logDir)) {
    echo "   ✅ Répertoire logs accessible: $logDir\n";
    $checks['directories'][] = true;
} else {
    echo "   ⚠️  Répertoire logs existe mais pas accessible: $logDir\n";
}

// ============================================
// 5. Vérifier la Base de Données
// ============================================

echo "\n5️⃣  Vérification de la Base de Données...\n";

try {
    // Essayer de charger la configuration DB
    require_once PROJECT_ROOT . '/backend/config/database.php';
    
    $db = Database::getInstance()->getConnection();
    
    echo "   ✅ Connexion BD établie\n";
    
    // Vérifier la table payments
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM payments LIMIT 1");
        $count = $stmt->fetchColumn();
        echo "   ✅ Table 'payments' existe ($count enregistrements)\n";
        $checks['database'][] = true;
    } catch (Exception $e) {
        echo "   ❌ Table 'payments' N'EXISTE PAS\n";
        echo "      Solution: Exécutez backend/database_payments_migration.sql\n";
        $checks['database'][] = false;
    }
    
} catch (Exception $e) {
    echo "   ⚠️  Connexion BD: " . $e->getMessage() . "\n";
    echo "      Assurez-vous que la BD est configurée dans backend/config/database.php\n";
}

// ============================================
// 6. Vérifier les Fonctions de Sandbox
// ============================================

echo "\n6️⃣  Vérification des Fonctions Sandbox...\n";

if (function_exists('isLocalNetwork')) {
    echo "   ✅ Fonction isLocalNetwork() disponible\n";
    $checks['functions'][] = true;
} else {
    echo "   ❌ Fonction isLocalNetwork() non disponible\n";
    $checks['functions'][] = false;
}

if (function_exists('generateTestPaymentData')) {
    echo "   ✅ Fonction generateTestPaymentData() disponible\n";
    $checks['functions'][] = true;
} else {
    echo "   ❌ Fonction generateTestPaymentData() non disponible\n";
    $checks['functions'][] = false;
}

if (function_exists('sandboxLog')) {
    echo "   ✅ Fonction sandboxLog() disponible\n";
    $checks['functions'][] = true;
} else {
    echo "   ❌ Fonction sandboxLog() non disponible\n";
    $checks['functions'][] = false;
}

// ============================================
// 7. Vérifier le Contrôleur
// ============================================

echo "\n7️⃣  Vérification du Contrôleur...\n";

$controllerFile = PROJECT_ROOT . '/backend/api/controllers/PaymentSandboxController.php';
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    
    if (class_exists('PaymentSandboxController')) {
        echo "   ✅ Classe PaymentSandboxController disponible\n";
        
        $methods = get_class_methods('PaymentSandboxController');
        $testMethods = ['testCreatePayment', 'testConfirmPayment', 'testSimulateWorkflow', 'testListPayments'];
        
        foreach ($testMethods as $method) {
            if (in_array($method, $methods)) {
                echo "      ✅ Méthode: $method()\n";
            }
        }
        
        $checks['controller'][] = true;
    } else {
        echo "   ❌ Classe PaymentSandboxController introuvable\n";
        $checks['controller'][] = false;
    }
} else {
    echo "   ❌ Fichier PaymentSandboxController.php introuvable\n";
    $checks['controller'][] = false;
}

// ============================================
// Résumé
// ============================================

echo "\n" . str_repeat("=", 50) . "\n";

$totalChecks = array_reduce($checks, function($carry, $item) {
    return $carry + count($item);
}, 0);

$passedChecks = array_reduce($checks, function($carry, $item) {
    return $carry + array_sum($item);
}, 0);

$percentage = round(($passedChecks / $totalChecks) * 100);

echo "📊 RÉSUMÉ\n";
echo "   Vérifications: $passedChecks/$totalChecks (" . $percentage . "%)\n";

if ($percentage === 100) {
    echo "\n✅ TOUT EST CONFIGURÉ! Vous pouvez démarrer le sandbox.\n";
    echo "\nCommandes pour démarrer:\n";
    echo "   PowerShell: powershell -ExecutionPolicy Bypass -File start-sandbox.ps1\n";
    echo "   CMD:        start-sandbox.bat\n";
    echo "   Ou:         php -S localhost:8000\n";
    echo "\nAccédez à: http://localhost:8000/MALOTY/frontend/test-sandbox.html\n";
} elseif ($percentage >= 80) {
    echo "\n⚠️  PRESQUE PRÊT! Il y a quelques points à corriger.\n";
    echo "   Consultez la liste ci-dessus pour les détails.\n";
} else {
    echo "\n❌ CONFIGURATION INCOMPLÈTE\n";
    echo "   Merci de suivre les instructions d'installation.\n";
}

echo "\n";
?>
