<?php
/**
 * Controller pour gérer les retours de MaishaPay (Callbacks et Webhooks)
 */

require_once PROJECT_ROOT . '/backend/api/services/MaishaPayService.php';
require_once PROJECT_ROOT . '/backend/api/services/LocalPaymentService.php';
require_once PROJECT_ROOT . '/backend/models/Tithe.php';
require_once PROJECT_ROOT . '/backend/models/Offering.php';

class MaishaPayController {
    private $maishaPayService;
    private $localPaymentService;
    private $titheModel;
    private $offeringModel;

    public function __construct() {
        $db = Database::getInstance()->getConnection();
        $this->maishaPayService = new MaishaPayService();
        $this->localPaymentService = new LocalPaymentService($db);
        $this->titheModel = new Tithe();
        $this->offeringModel = new Offering();
    }

    /**
     * GET /api/maishapay/callback
     * URL de retour après paiement réussi
     */
    public function callback() {
        $status = $_GET['status'] ?? '';
        $ref = $_GET['order_id'] ?? $_GET['ref'] ?? '';
        
        // Rediriger vers la page de contribution avec le statut
        $url = APP_URL . "/contribute?status=" . ($status === 'success' ? 'success' : 'cancel') . "&ref=" . $ref;
        header("Location: $url");
        exit;
    }

    /**
     * POST /api/maishapay/webhook
     * Appelé par MaishaPay pour confirmer le paiement
     */
    public function webhook() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            $data = $_POST;
        }

        $orderId = $data['order_id'] ?? '';
        $status = $data['status'] ?? '';
        $transactionId = $data['transaction_id'] ?? '';

        if (empty($orderId)) {
            json_error("Order ID manquant", 400);
        }

        // Si le statut est succès, confirmer le paiement dans notre système
        if ($status === 'success' || $status === 'completed' || $status === 'paid') {
            // 1. Confirmer dans le LocalPaymentService
            // Note: En mode Sandbox, on simule la confirmation
            $this->localPaymentService->confirmPayment($orderId, 'MAISHAPAY_WEBHOOK', 'MaishaPay Webhook');

            // 2. Mettre à jour le statut dans tithes ou offerings
            $this->updateTransactionStatus($orderId, 'confirmed');

            json_response(['success' => true, 'message' => 'Paiement confirmé']);
        }

        json_response(['success' => false, 'message' => 'Statut non géré']);
    }

    private function updateTransactionStatus($ref, $status) {
        $db = Database::getInstance()->getConnection();
        // Mettre à jour dans tithes
        $stmt = $db->prepare("UPDATE tithes SET payment_status = ? WHERE id IN (SELECT id FROM tithes WHERE comment LIKE ?)");
        $stmt->execute([$status, "%$ref%"]);

        // Mettre à jour dans offerings
        $stmt = $db->prepare("UPDATE offerings SET payment_status = ? WHERE description LIKE ?");
        $stmt->execute([$status, "%$ref%"]);
    }
}
