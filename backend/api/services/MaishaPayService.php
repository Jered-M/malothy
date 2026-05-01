<?php
/**
 * MaishaPay Service Client
 * Intégration pour Mobile Money (M-Pesa, Airtel Money, Orange Money, etc.)
 * 
 * MODES :
 * - TEST (0/Sandbox): Pour développement/TFC, aucun argent réel débité
 * - LIVE (1/Production): Pour production seulement, argent réel
 * 
 * DOCUMENTATION:
 * @see https://maishapay.net/documentation
 * @see https://maishapay.net/developers
 */

class MaishaPayService {
    private $apiKey;
    private $secretKey;
    private $gatewayMode; // 0 for Test/Sandbox, 1 for Live/Production
    private $baseUrl;

    public function __construct() {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'maishapay_%'");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $this->apiKey = $settings['maishapay_public_key'] ?? (defined('MAISHAPAY_PUBLIC_KEY') ? MAISHAPAY_PUBLIC_KEY : '');
            $this->secretKey = $settings['maishapay_secret_key'] ?? (defined('MAISHAPAY_SECRET_KEY') ? MAISHAPAY_SECRET_KEY : '');
            $this->gatewayMode = isset($settings['maishapay_gateway_mode']) ? (int)$settings['maishapay_gateway_mode'] : (defined('MAISHAPAY_GATEWAY_MODE') ? (int)MAISHAPAY_GATEWAY_MODE : 0);
        } catch (Exception $e) {
            // Fallback to constants if DB fails
            $this->apiKey = defined('MAISHAPAY_PUBLIC_KEY') ? MAISHAPAY_PUBLIC_KEY : '';
            $this->secretKey = defined('MAISHAPAY_SECRET_KEY') ? MAISHAPAY_SECRET_KEY : '';
            $this->gatewayMode = defined('MAISHAPAY_GATEWAY_MODE') ? (int)MAISHAPAY_GATEWAY_MODE : 0;
        }
        
        // URL de base selon le mode
        $this->baseUrl = $this->gatewayMode === 0 
            ? "https://sandbox.maishapay.net" 
            : "https://maishapay.net";
    }

    /**
     * Générer l'URL de paiement Checkout
     * 
     * @param array $data Données du paiement
     *   - amount: montant (requis)
     *   - currency: devise (default: 'CDF')
     *   - order_id: identifiant de commande
     *   - description: description du paiement
     *   - success_url: URL de redirection après succès (requis)
     *   - cancel_url: URL de redirection après annulation
     *   - customer_name: nom du client
     *   - customer_email: email du client
     *   - customer_phone: téléphone du client
     * @return array ['status' => 'success|error', 'payment_url' => '...', 'order_id' => '...']
     */
    public function generateCheckoutLink($data) {
        // Validation de base
        if (empty($data['amount']) || !is_numeric($data['amount'])) {
            return [
                'status' => 'error',
                'message' => 'Montant invalide',
                'payment_url' => null
            ];
        }

        if (empty($data['success_url']) || empty($data['cancel_url'])) {
            return [
                'status' => 'error',
                'message' => 'URLs de redirection requises',
                'payment_url' => null
            ];
        }

        // Paramètres requis par MaishaPay
        $orderId = $data['order_id'] ?? uniqid('MALOTY_');
        $params = [
            'gatewayMode' => $this->gatewayMode,
            'api_key' => $this->apiKey,
            'amount' => floatval($data['amount']),
            'currency' => $data['currency'] ?? 'CDF',
            'order_id' => $orderId,
            'description' => $data['description'] ?? 'Don à l\'église MALOTY',
            'success_url' => $data['success_url'],
            'cancel_url' => $data['cancel_url'],
            'customer_name' => $data['customer_name'] ?? 'Donateur Anonyme',
            'customer_email' => $data['customer_email'] ?? 'contact@maloty.com',
            'customer_phone' => $data['customer_phone'] ?? ''
        ];

        // Construire l'URL du checkout
        $checkoutUrl = $this->baseUrl . "/checkout";
        $paymentUrl = $checkoutUrl . '?' . http_build_query($params);
        
        return [
            'status' => 'success',
            'payment_url' => $paymentUrl,
            'order_id' => $orderId,
            'mode' => $this->gatewayMode === 0 ? 'TEST (Sandbox)' : 'LIVE (Production)',
            'message' => $this->gatewayMode === 0 ? '🧪 Mode TEST - Aucun argent réel ne sera débité' : '✅ Mode PRODUCTION'
        ];
    }


    /**
     * Vérifier le statut d'une transaction
     * 
     * @param string $transactionId ID de la transaction Maishapay
     * @return array Statut de la transaction
     * 
     * IMPORTANT: Pour les webhooks en mode TEST, Maishapay envoie les données directement
     * Voir PaymentWebhookController pour la gestion des callbacks
     */
    public function verifyTransaction($transactionId) {
        // En mode TEST (Sandbox): simulation de vérification
        if ($this->gatewayMode === 0) {
            return [
                'status' => 'test_pending',
                'transaction_id' => $transactionId,
                'message' => 'ℹ️ Mode TEST - Vérification simulée (vérifiez dans le tableau de bord Maishapay Sandbox)',
                'mode' => 'TEST'
            ];
        }
        
        // En mode LIVE: appel API réel à Maishapay
        // Endpoint: GET {API_ENDPOINT}/v1/transaction/{transactionId}
        // Avec header: Authorization: Bearer {secretKey}
        
        // TODO: Implémenter l'appel à l'API réelle de Maishapay
        // Voir documentation: https://maishapay.net/api/documentation
        
        return [
            'status' => 'completed',
            'transaction_id' => $transactionId,
            'amount' => 0,
            'currency' => 'CDF',
            'mode' => 'LIVE'
        ];
    }

    /**
     * Obtenir le mode actuel (pour débogage/logs)
     */
    public function getMode() {
        return [
            'mode' => $this->gatewayMode === 0 ? 'TEST (Sandbox)' : 'LIVE (Production)',
            'gateway_mode' => $this->gatewayMode,
            'base_url' => $this->baseUrl,
            'api_key_configured' => !empty($this->apiKey),
            'secret_key_configured' => !empty($this->secretKey)
        ];
    }
}
