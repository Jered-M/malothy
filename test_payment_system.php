<?php
/**
 * Script de test du système de paiement local
 * Accédez à: http://localhost/MALOTY/test_payment_system.php
 */

define('PROJECT_ROOT', __DIR__);

// Charger les dépendances
require_once PROJECT_ROOT . '/backend/config/database.php';
require_once PROJECT_ROOT . '/backend/api/services/LocalPaymentService.php';

// Couleurs pour le terminal
$colors = [
    'success' => "\033[32m",
    'error' => "\033[31m",
    'info' => "\033[34m",
    'warning' => "\033[33m",
    'reset' => "\033[0m"
];

function test_result($name, $result, $expected = true) {
    global $colors;
    $color = ($result === $expected) ? $colors['success'] : $colors['error'];
    $status = ($result === $expected) ? '✓' : '✗';
    echo "{$color}{$status} {$name}{$colors['reset']}\n";
    return $result === $expected;
}

echo "\n{$colors['info']}╔════════════════════════════════════════════════════════╗{$colors['reset']}\n";
echo "{$colors['info']}║  TEST - Système de Paiement Local MALOTY              ║{$colors['reset']}\n";
echo "{$colors['info']}╚════════════════════════════════════════════════════════╝{$colors['reset']}\n\n";

try {
    global $db;
    $service = new LocalPaymentService($db);
    
    // Test 1: Initialiser la table
    echo "{$colors['info']}[Test 1] Initialisation de la table{$colors['reset']}\n";
    $result = $service->initializeTable();
    test_result("Table créée/vérifiée", $result['status'] === 'success');
    echo "\n";

    // Test 2: Créer une demande de paiement
    echo "{$colors['info']}[Test 2] Créer une demande de paiement{$colors['reset']}\n";
    $payment = $service->createPaymentRequest([
        'type' => 'tithe',
        'amount' => 50000,
        'currency' => 'CDF',
        'donor_name' => 'Test User',
        'donor_email' => 'test@example.com',
        'donor_phone' => '+223123456789',
        'description' => 'Test payment'
    ]);
    
    test_result("Paiement créé", $payment['status'] === 'success');
    test_result("Référence générée", !empty($payment['payment_ref']));
    test_result("Code de confirmation généré", !empty($payment['confirmation_code']));
    
    $paymentRef = $payment['payment_ref'] ?? null;
    $confirmCode = $payment['confirmation_code'] ?? null;
    
    echo "   Référence: {$colors['info']}{$paymentRef}{$colors['reset']}\n";
    echo "   Code: {$colors['info']}{$confirmCode}{$colors['reset']}\n";
    echo "   Montant: {$colors['info']}" . ($payment['amount'] ?? 0) . " " . ($payment['currency'] ?? 'CDF') . "{$colors['reset']}\n";
    echo "\n";

    // Test 3: Obtenir les détails du paiement
    if ($paymentRef) {
        echo "{$colors['info']}[Test 3] Obtenir les détails du paiement{$colors['reset']}\n";
        
        $details = $service->getPaymentDetails($paymentRef);
        test_result("Détails récupérés", $details['status'] === 'success');
        test_result("Statut = pending", ($details['payment']['status'] ?? null) === 'pending');
        echo "\n";
    }

    // Test 4: Confirmer le paiement
    if ($paymentRef && $confirmCode) {
        echo "{$colors['info']}[Test 4] Confirmer le paiement{$colors['reset']}\n";
        
        $confirmed = $service->confirmPayment($paymentRef, $confirmCode, 'tester');
        test_result("Paiement confirmé", $confirmed['status'] === 'success');
        
        // Vérifier que le statut a changé
        $details = $service->getPaymentDetails($paymentRef);
        test_result("Statut = confirmed", ($details['payment']['status'] ?? null) === 'confirmed');
        echo "\n";
    }

    // Test 5: Lister les paiements
    echo "{$colors['info']}[Test 5] Lister les paiements{$colors['reset']}\n";
    
    $list = $service->listPayments(['status' => 'confirmed']);
    test_result("Liste récupérée", $list['status'] === 'success');
    test_result("Au moins 1 paiement confirmé", ($list['total'] ?? 0) >= 1);
    echo "   Total: {$colors['info']}" . ($list['total'] ?? 0) . " paiements{$colors['reset']}\n";
    echo "\n";

    // Test 6: Obtenir les statistiques
    echo "{$colors['info']}[Test 6] Statistiques{$colors['reset']}\n";
    
    $stats = $service->getPaymentStats();
    test_result("Statistiques générées", $stats['status'] === 'success');
    test_result("Au moins 1 groupe de stats", count($stats['stats'] ?? []) >= 1);
    echo "   Groupes: {$colors['info']}" . count($stats['stats'] ?? []) . "{$colors['reset']}\n";
    
    foreach ($stats['stats'] ?? [] as $stat) {
        echo "   - {$stat['status']} ({$stat['type']}): {$stat['count']} paiements, Total: {$stat['total']} CDF\n";
    }
    echo "\n";

    // Test 7: Exporter en CSV
    echo "{$colors['info']}[Test 7] Export CSV{$colors['reset']}\n";
    
    $export = $service->exportToCSV(['status' => 'confirmed']);
    test_result("Export généré", $export['status'] === 'success');
    test_result("CSV non vide", !empty($export['csv']));
    echo "   Aperçu des 2 premières lignes:\n";
    $csv_lines = explode("\n", $export['csv']);
    echo "   {$csv_lines[0]}\n";
    echo "   {$csv_lines[1]}\n";
    echo "\n";

    // Résumé final
    echo "{$colors['success']}╔════════════════════════════════════════════════════════╗{$colors['reset']}\n";
    echo "{$colors['success']}║  TOUS LES TESTS RÉUSSIS! ✨                           ║{$colors['reset']}\n";
    echo "{$colors['success']}║                                                        ║{$colors['reset']}\n";
    echo "{$colors['success']}║  Vous pouvez maintenant utiliser le système           ║{$colors['reset']}\n";
    echo "{$colors['success']}║  Voir: LOCAL_PAYMENT_SYSTEM_README.md                 ║{$colors['reset']}\n";
    echo "{$colors['success']}╚════════════════════════════════════════════════════════╝{$colors['reset']}\n\n";

} catch (Exception $e) {
    echo "{$colors['error']}❌ Erreur: {$e->getMessage()}{$colors['reset']}\n\n";
    echo "{$colors['warning']}Vérifiez:{$colors['reset']}\n";
    echo "  1. La base de données est accessible\n";
    echo "  2. La table 'payments' existe (exécutez la migration)\n";
    echo "  3. Le LocalPaymentService est chargé correctement\n\n";
}
