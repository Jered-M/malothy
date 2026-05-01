<?php
/**
 * Configuration Sandbox pour Paiements Locaux
 * 
 * Permet de tester l'API de paiement sur réseau local sans dépendances externes
 */

// ============================================
// MODE SANDBOX
// ============================================

// Activer/Désactiver le mode sandbox
define('SANDBOX_MODE', getenv('SANDBOX_MODE') ?: true);

// Configuration du réseau local
define('SANDBOX_CONFIG', [
    'mode' => SANDBOX_MODE ? 'test' : 'production',
    
    // Réseau local autorisé
    'allowed_networks' => [
        '127.0.0.1',           // localhost
        '192.168.1.0/24',      // Réseau local (adapter selon votre réseau)
        'localhost',
        gethostname(),         // Nom de la machine
    ],
    
    // Préfixes de test
    'test_prefixes' => [
        'payment_ref' => 'TEST-PAY-',  // TEST-PAY-2026-ABC123
        'confirmation_code' => 'TEST-',  // TEST-A1B2-C3D4-E5F6
    ],
    
    // Paramètres de test
    'test_settings' => [
        'auto_confirm' => false,        // Confirmer automatiquement après délai
        'auto_confirm_delay' => 5,      // Secondes avant auto-confirmation
        'skip_email_validation' => true, // Ne pas valider les emails en test
        'skip_phone_validation' => true, // Ne pas valider les téléphones en test
        'allow_duplicate_refs' => false, // Permettre les références dupliquées
        'mock_payment_methods' => [      // Méthodes de paiement simulées
            'cash',
            'mobilebanking',
            'manual_transfer',
            'simulation'
        ],
    ],
    
    // Données de test mock
    'mock_data' => [
        'users' => [
            ['id' => 1, 'name' => 'Test User 1', 'email' => 'test1@local.dev'],
            ['id' => 2, 'name' => 'Test User 2', 'email' => 'test2@local.dev'],
            ['id' => 3, 'name' => 'Test User 3', 'email' => 'test3@local.dev'],
        ],
        'payment_types' => ['tithe', 'offering', 'donation', 'deposit'],
        'amounts' => [1000, 5000, 10000, 50000, 100000],
        'currencies' => ['CDF', 'USD', 'EUR'],
    ],
    
    // Délai d'expiration en test
    'expiration' => [
        'minutes' => 30,  // 30 minutes en test
        'seconds' => 1800,
    ],
    
    // Webhooks de test
    'webhooks' => [
        'on_payment_created' => 'http://localhost/MALOTY/backend/api/webhooks/payment-created.php',
        'on_payment_confirmed' => 'http://localhost/MALOTY/backend/api/webhooks/payment-confirmed.php',
        'on_payment_expired' => 'http://localhost/MALOTY/backend/api/webhooks/payment-expired.php',
    ],
    
    // Logs de test
    'logging' => [
        'enabled' => true,
        'path' => PROJECT_ROOT . '/tmp/sandbox-logs',
        'level' => 'debug',  // debug, info, warning, error
    ],
]);

// ============================================
// VÉRIFIER SI ON EST EN RÉSEAU LOCAL
// ============================================

function isLocalNetwork() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $allowed = SANDBOX_CONFIG['allowed_networks'];
    
    // Vérification simple
    foreach ($allowed as $network) {
        if ($ip === $network || $ip === gethostname()) {
            return true;
        }
        
        // Vérification CIDR pour les plages
        if (strpos($network, '/') !== false) {
            list($subnet, $bits) = explode('/', $network);
            $ip_long = ip2long($ip);
            $subnet_long = ip2long($subnet);
            $mask = -1 << (32 - $bits);
            
            if (($ip_long & $mask) === ($subnet_long & $mask)) {
                return true;
            }
        }
    }
    
    return false;
}

// ============================================
// LOG DE SANDBOX
// ============================================

function sandboxLog($message, $level = 'info', $data = []) {
    if (!SANDBOX_CONFIG['logging']['enabled']) {
        return;
    }
    
    $log_dir = SANDBOX_CONFIG['logging']['path'];
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] [$level] $message";
    
    if (!empty($data)) {
        $log_message .= " | " . json_encode($data);
    }
    
    $log_file = $log_dir . '/sandbox-' . date('Y-m-d') . '.log';
    file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
}

// ============================================
// VÉRIFIER L'ACCÈS AU SANDBOX
// ============================================

function verifySandboxAccess() {
    if (!SANDBOX_MODE) {
        http_response_code(403);
        die(json_encode(['error' => 'Sandbox mode disabled']));
    }
    
    if (!isLocalNetwork()) {
        http_response_code(403);
        die(json_encode(['error' => 'Access denied - Not on local network']));
    }
    
    sandboxLog('Sandbox access granted', 'info', ['ip' => $_SERVER['REMOTE_ADDR']]);
}

// ============================================
// GÉNÉRER DES DONNÉES DE TEST
// ============================================

function generateTestPaymentData() {
    $mock = SANDBOX_CONFIG['mock_data'];
    
    $user = $mock['users'][array_rand($mock['users'])];
    $type = $mock['payment_types'][array_rand($mock['payment_types'])];
    $amount = $mock['amounts'][array_rand($mock['amounts'])];
    $currency = $mock['currencies'][array_rand($mock['currencies'])];
    
    return [
        'type' => $type,
        'amount' => $amount,
        'currency' => $currency,
        'donor_name' => $user['name'],
        'donor_email' => $user['email'],
        'donor_phone' => '+223' . random_int(600000000, 699999999),
        'member_id' => $user['id'],
        'description' => ucfirst($type) . ' de test - ' . date('Y-m-d'),
        'test_mode' => true,
    ];
}

// ============================================
// PRÉFIXE DE TEST
// ============================================

function getTestPrefix($type = 'payment_ref') {
    return SANDBOX_CONFIG['test_prefixes'][$type] ?? 'TEST-';
}

?>
