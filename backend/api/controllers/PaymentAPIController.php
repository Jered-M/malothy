<?php
/**
 * Contrôleur API de Paiement Local
 * 
 * Endpoints :
 * POST /api/payments/create - Créer une demande de paiement
 * POST /api/payments/confirm - Confirmer avec le code
 * GET /api/payments/status/{ref} - Vérifier le statut
 * GET /api/payments/list - Lister les paiements (Admin)
 * GET /api/payments/stats - Statistiques (Admin)
 */

header('Content-Type: application/json');

// Charger le service
require_once __DIR__ . '/../services/LocalPaymentService.php';

class PaymentAPIController {
    
    private $paymentService;
    private $method;
    private $action;
    private $data;

    public function __construct() {
        $db = Database::getInstance()->getConnection();
        $this->paymentService = new LocalPaymentService($db);
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->action = $_GET['action'] ?? '';
        $this->data = json_decode(file_get_contents('php://input'), true) ?? $_REQUEST;
    }

    public function handle() {
        try {
            // Routes publiques (pas besoin de login)
            if ($this->action === 'create' && $this->method === 'POST') {
                return $this->createPaymentRequest();
            }

            if ($this->action === 'confirm' && $this->method === 'POST') {
                return $this->confirmPayment();
            }

            if ($this->action === 'status' && $this->method === 'GET') {
                return $this->getPaymentStatus();
            }

            // Routes protégées (Admin uniquement)
            if (!$this->isAdmin()) {
                http_response_code(403);
                return $this->jsonResponse(['status' => 'error', 'message' => 'Accès refusé']);
            }

            if ($this->action === 'list' && $this->method === 'GET') {
                return $this->listPayments();
            }

            if ($this->action === 'stats' && $this->method === 'GET') {
                return $this->getStats();
            }

            if ($this->action === 'init' && $this->method === 'POST') {
                return $this->initializeDatabase();
            }

            http_response_code(404);
            return $this->jsonResponse(['status' => 'error', 'message' => 'Action non trouvée']);

        } catch (Exception $e) {
            http_response_code(500);
            return $this->jsonResponse(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * POST /api/payments/create
     * Créer une demande de paiement
     */
    private function createPaymentRequest() {
        // Validation de base
        if (empty($this->data['amount'])) {
            http_response_code(400);
            return $this->jsonResponse(['status' => 'error', 'message' => 'Montant requis']);
        }

        $result = $this->paymentService->createPaymentRequest([
            'type' => $this->data['type'] ?? 'tithe',
            'amount' => $this->data['amount'],
            'currency' => $this->data['currency'] ?? 'CDF',
            'donor_name' => $this->data['donor_name'] ?? 'Anonyme',
            'donor_email' => $this->data['donor_email'] ?? '',
            'donor_phone' => $this->data['donor_phone'] ?? '',
            'member_id' => $this->data['member_id'] ?? null,
            'description' => $this->data['description'] ?? ''
        ]);

        if ($result['status'] === 'success') {
            http_response_code(201);
        } else {
            http_response_code(400);
        }

        return $this->jsonResponse($result);
    }

    /**
     * POST /api/payments/confirm
     * Confirmer un paiement avec le code de confirmation
     */
    private function confirmPayment() {
        if (empty($this->data['payment_ref']) || empty($this->data['confirmation_code'])) {
            http_response_code(400);
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Référence et code de confirmation requis'
            ]);
        }

        $result = $this->paymentService->confirmPayment(
            $this->data['payment_ref'],
            $this->data['confirmation_code'],
            'user'
        );

        if ($result['status'] === 'success') {
            http_response_code(200);
        } else {
            http_response_code(400);
        }

        return $this->jsonResponse($result);
    }

    /**
     * GET /api/payments/status?ref=PAY-2026-ABC123
     * Vérifier le statut d'un paiement
     */
    private function getPaymentStatus() {
        $ref = $_GET['ref'] ?? '';
        
        if (empty($ref)) {
            http_response_code(400);
            return $this->jsonResponse(['status' => 'error', 'message' => 'Référence requise']);
        }

        $result = $this->paymentService->getPaymentDetails($ref);
        
        if ($result['status'] === 'success') {
            $payment = $result['payment'];
            return $this->jsonResponse([
                'status' => 'success',
                'payment_ref' => $payment['payment_ref'],
                'payment_status' => $payment['status'],
                'amount' => $payment['amount'],
                'currency' => $payment['currency'],
                'donor_name' => $payment['donor_name'],
                'created_at' => $payment['created_at'],
                'confirmed_at' => $payment['confirmed_at'],
                'expires_at' => $payment['expires_at']
            ]);
        }

        http_response_code(404);
        return $this->jsonResponse($result);
    }

    /**
     * GET /api/payments/list?status=confirmed
     * Lister les paiements (Admin)
     */
    private function listPayments() {
        $filters = [
            'status' => $_GET['status'] ?? '',
            'type' => $_GET['type'] ?? '',
            'member_id' => $_GET['member_id'] ?? '',
            'start_date' => $_GET['start_date'] ?? '',
            'end_date' => $_GET['end_date'] ?? ''
        ];

        // Nettoyer les filtres vides
        $filters = array_filter($filters);

        $result = $this->paymentService->listPayments($filters);
        return $this->jsonResponse($result);
    }

    /**
     * GET /api/payments/stats
     * Statistiques des paiements (Admin)
     */
    private function getStats() {
        $filters = [
            'start_date' => $_GET['start_date'] ?? '',
            'end_date' => $_GET['end_date'] ?? ''
        ];

        $filters = array_filter($filters);
        $result = $this->paymentService->getPaymentStats($filters);
        
        return $this->jsonResponse($result);
    }

    /**
     * POST /api/payments/init
     * Initialiser la table de paiements (Admin - Une seule fois)
     */
    private function initializeDatabase() {
        $result = $this->paymentService->initializeTable();
        
        if ($result['status'] === 'success') {
            http_response_code(201);
        }

        return $this->jsonResponse($result);
    }

    /**
     * Vérifier si l'utilisateur est admin
     */
    private function isAdmin() {
        if (!isset($_SESSION['user'])) {
            return false;
        }

        return $_SESSION['user']['role'] === 'admin';
    }

    /**
     * Retourner une réponse JSON
     */
    private function jsonResponse($data) {
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return;
    }
}

// Instancier et exécuter
$controller = new PaymentAPIController();
$controller->handle();
