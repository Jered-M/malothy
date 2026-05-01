<?php
/**
 * Contrôleur API Sandbox pour Paiements
 * 
 * Endpoints de test pour développer localement
 * Toutes les routes commencent par ?controller=payment_sandbox
 * 
 * Routes disponibles :
 * POST /api?controller=payment_sandbox&action=test-create      - Créer un paiement de test
 * POST /api?controller=payment_sandbox&action=test-confirm     - Confirmer un paiement
 * GET  /api?controller=payment_sandbox&action=test-status      - Vérifier le statut
 * POST /api?controller=payment_sandbox&action=test-simulate    - Simuler un workflow complet
 * GET  /api?controller=payment_sandbox&action=test-list        - Lister tous les paiements de test
 * POST /api?controller=payment_sandbox&action=reset-all        - Réinitialiser toutes les données
 */

header('Content-Type: application/json');

// Charger les configs
require_once __DIR__ . '/../config/sandbox-config.php';
require_once __DIR__ . '/../services/LocalPaymentService.php';

class PaymentSandboxController {
    
    private $paymentService;
    private $method;
    private $action;
    private $data;

    public function __construct() {
        // Vérifier l'accès sandbox
        verifySandboxAccess();
        
        global $db;
        $this->paymentService = new LocalPaymentService($db);
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->action = $_GET['action'] ?? '';
        $this->data = json_decode(file_get_contents('php://input'), true) ?? $_REQUEST;
    }

    public function handle() {
        try {
            sandboxLog("Sandbox request: {$this->action}", 'info', $this->data);
            
            $result = null;
            
            switch ($this->action) {
                // ===== CRÉATION DE PAIEMENT DE TEST =====
                case 'test-create':
                case 'testCreate':
                    $result = $this->testCreatePayment();
                    break;
                
                // ===== CONFIRMATION DE PAIEMENT =====
                case 'test-confirm':
                case 'testConfirm':
                    $result = $this->testConfirmPayment();
                    break;
                
                // ===== VÉRIFICATION DE STATUT =====
                case 'test-status':
                case 'testStatus':
                    $result = $this->testGetStatus();
                    break;
                
                // ===== SIMULATION COMPLÈTE =====
                case 'test-simulate':
                case 'testSimulate':
                    $result = $this->testSimulateWorkflow();
                    break;
                
                // ===== LISTER LES PAIEMENTS =====
                case 'test-list':
                case 'testList':
                    $result = $this->testListPayments();
                    break;
                
                // ===== RÉINITIALISER LES DONNÉES =====
                case 'reset-all':
                case 'resetAll':
                    $result = $this->testResetAll();
                    break;
                
                // ===== GÉNÉRER DES DONNÉES DE TEST =====
                case 'generate-test-data':
                case 'generateTestData':
                    $result = $this->testGenerateTestData();
                    break;
                
                // ===== INFORMATIONS DE STATUT =====
                case 'status':
                    $result = $this->getSandboxStatus();
                    break;
                
                default:
                    http_response_code(404);
                    $result = ['error' => 'Action not found', 'available_actions' => [
                        'test-create', 'test-confirm', 'test-status', 
                        'test-simulate', 'test-list', 'reset-all', 
                        'generate-test-data', 'status'
                    ]];
            }
            
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (Exception $e) {
            sandboxLog("Error: " . $e->getMessage(), 'error');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // ============================================
    // CRÉER UN PAIEMENT DE TEST
    // ============================================
    private function testCreatePayment() {
        // Générer des données de test
        $testData = array_merge(
            generateTestPaymentData(),
            $this->data
        );
        
        // Créer via le service
        $result = $this->paymentService->createPaymentRequest($testData);
        
        if ($result['status'] === 'success') {
            http_response_code(201);
            sandboxLog("Payment created", 'info', $result);
        } else {
            http_response_code(400);
        }
        
        return $result;
    }

    // ============================================
    // CONFIRMER UN PAIEMENT
    // ============================================
    private function testConfirmPayment() {
        $paymentRef = $this->data['payment_ref'] ?? null;
        $confirmationCode = $this->data['confirmation_code'] ?? null;
        
        if (!$paymentRef || !$confirmationCode) {
            http_response_code(400);
            return [
                'error' => 'payment_ref and confirmation_code required',
                'example' => [
                    'payment_ref' => 'TEST-PAY-2026-ABC123',
                    'confirmation_code' => 'TEST-A1B2-C3D4-E5F6'
                ]
            ];
        }
        
        // Confirmer via le service
        $result = $this->paymentService->confirmPayment($paymentRef, $confirmationCode);
        
        if ($result['status'] === 'success') {
            http_response_code(200);
            sandboxLog("Payment confirmed", 'info', $result);
        } else {
            http_response_code(400);
        }
        
        return $result;
    }

    // ============================================
    // VÉRIFIER LE STATUT
    // ============================================
    private function testGetStatus() {
        $paymentRef = $this->data['payment_ref'] ?? $_GET['ref'] ?? null;
        
        if (!$paymentRef) {
            http_response_code(400);
            return ['error' => 'payment_ref required'];
        }
        
        $result = $this->paymentService->getPaymentStatus($paymentRef);
        
        if ($result['status'] === 'success') {
            http_response_code(200);
        } else {
            http_response_code(404);
        }
        
        return $result;
    }

    // ============================================
    // SIMULER UN WORKFLOW COMPLET
    // ============================================
    private function testSimulateWorkflow() {
        $workflow = [];
        $delay = $this->data['delay'] ?? 2; // Délai entre étapes
        
        try {
            // ÉTAPE 1 : Créer un paiement
            $workflow['step_1_create'] = 'Création d\'un paiement de test...';
            $createData = generateTestPaymentData();
            $createResult = $this->paymentService->createPaymentRequest($createData);
            
            if ($createResult['status'] !== 'success') {
                throw new Exception('Création échouée');
            }
            
            $paymentRef = $createResult['payment_ref'];
            $confirmCode = $createResult['confirmation_code'];
            
            $workflow['step_1_result'] = $createResult;
            sleep($delay);
            
            // ÉTAPE 2 : Vérifier le statut (pending)
            $workflow['step_2_check_pending'] = 'Vérification du statut (pending)...';
            $statusBefore = $this->paymentService->getPaymentStatus($paymentRef);
            $workflow['step_2_result'] = $statusBefore;
            sleep($delay);
            
            // ÉTAPE 3 : Confirmer le paiement
            $workflow['step_3_confirm'] = 'Confirmation du paiement...';
            $confirmResult = $this->paymentService->confirmPayment($paymentRef, $confirmCode);
            
            if ($confirmResult['status'] !== 'success') {
                throw new Exception('Confirmation échouée');
            }
            
            $workflow['step_3_result'] = $confirmResult;
            sleep($delay);
            
            // ÉTAPE 4 : Vérifier le statut final (confirmed)
            $workflow['step_4_check_confirmed'] = 'Vérification du statut final (confirmed)...';
            $statusAfter = $this->paymentService->getPaymentStatus($paymentRef);
            $workflow['step_4_result'] = $statusAfter;
            
            $workflow['summary'] = [
                'status' => 'success',
                'message' => 'Workflow de test complet réussi ✅',
                'payment_ref' => $paymentRef,
                'test_duration' => ($delay * 4) . ' secondes',
            ];
            
            http_response_code(200);
            sandboxLog("Workflow completed", 'info', $workflow);
            
        } catch (Exception $e) {
            $workflow['error'] = $e->getMessage();
            http_response_code(400);
            sandboxLog("Workflow failed: " . $e->getMessage(), 'error');
        }
        
        return $workflow;
    }

    // ============================================
    // LISTER LES PAIEMENTS
    // ============================================
    private function testListPayments() {
        global $db;
        
        try {
            $sql = "SELECT * FROM payments WHERE payment_ref LIKE ? ORDER BY created_at DESC LIMIT 50";
            $stmt = $db->prepare($sql);
            $stmt->execute([getTestPrefix() . '%']);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => 'success',
                'total' => count($payments),
                'payments' => $payments
            ];
        } catch (Exception $e) {
            http_response_code(500);
            return ['error' => $e->getMessage()];
        }
    }

    // ============================================
    // GÉNÉRER DES DONNÉES DE TEST
    // ============================================
    private function testGenerateTestData() {
        $count = $this->data['count'] ?? 5;
        $results = [];
        
        for ($i = 0; $i < $count; $i++) {
            try {
                $testData = generateTestPaymentData();
                $result = $this->paymentService->createPaymentRequest($testData);
                
                if ($result['status'] === 'success') {
                    $results[] = [
                        'success' => true,
                        'payment_ref' => $result['payment_ref'],
                        'confirmation_code' => $result['confirmation_code']
                    ];
                } else {
                    $results[] = [
                        'success' => false,
                        'error' => $result['message'] ?? 'Unknown error'
                    ];
                }
            } catch (Exception $e) {
                $results[] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return [
            'status' => 'success',
            'generated' => count(array_filter($results, fn($r) => $r['success'])),
            'total' => count($results),
            'results' => $results
        ];
    }

    // ============================================
    // RÉINITIALISER LES DONNÉES
    // ============================================
    private function testResetAll() {
        global $db;
        
        $confirm = $this->data['confirm'] ?? false;
        
        if (!$confirm) {
            http_response_code(400);
            return [
                'warning' => 'Cela supprimera tous les paiements de test',
                'instruction' => 'Passez "confirm": true pour confirmer'
            ];
        }
        
        try {
            $sql = "DELETE FROM payments WHERE payment_ref LIKE ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([getTestPrefix() . '%']);
            
            $deleted = $stmt->rowCount();
            
            sandboxLog("Reset all test payments - $deleted deleted", 'warning');
            
            return [
                'status' => 'success',
                'deleted' => $deleted,
                'message' => "$deleted paiements de test supprimés"
            ];
        } catch (Exception $e) {
            http_response_code(500);
            return ['error' => $e->getMessage()];
        }
    }

    // ============================================
    // STATUT DU SANDBOX
    // ============================================
    private function getSandboxStatus() {
        return [
            'status' => 'success',
            'sandbox_mode' => SANDBOX_MODE,
            'local_network' => isLocalNetwork(),
            'client_ip' => $_SERVER['REMOTE_ADDR'],
            'config' => [
                'mode' => SANDBOX_CONFIG['mode'],
                'logging' => SANDBOX_CONFIG['logging']['enabled'],
                'test_mode_settings' => SANDBOX_CONFIG['test_settings'],
            ],
            'endpoints_available' => [
                'POST /api?controller=payment_sandbox&action=test-create',
                'POST /api?controller=payment_sandbox&action=test-confirm',
                'GET  /api?controller=payment_sandbox&action=test-status?ref=TEST-PAY-...',
                'POST /api?controller=payment_sandbox&action=test-simulate',
                'GET  /api?controller=payment_sandbox&action=test-list',
                'POST /api?controller=payment_sandbox&action=generate-test-data',
                'POST /api?controller=payment_sandbox&action=reset-all',
                'GET  /api?controller=payment_sandbox&action=status',
            ]
        ];
    }
}

// Traiter la requête
$controller = new PaymentSandboxController();
$controller->handle();

?>
