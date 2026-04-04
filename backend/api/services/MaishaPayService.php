<?php
/**
 * MaishaPay Service Client
 * Intégration pour Mobile Money (M-Pesa, Airtel Money, Orange Money, etc.)
 */

class MaishaPayService {
    private $apiKey;
    private $secretKey;
    private $gatewayMode; // 0 for Test, 1 for Live

    public function __construct() {
        $this->apiKey = defined('MAISHAPAY_PUBLIC_KEY') ? MAISHAPAY_PUBLIC_KEY : '';
        $this->secretKey = defined('MAISHAPAY_SECRET_KEY') ? MAISHAPAY_SECRET_KEY : '';
        $this->gatewayMode = defined('MAISHAPAY_GATEWAY_MODE') ? MAISHAPAY_GATEWAY_MODE : 0;
    }

    /**
     * Générer l'URL de paiement Checkout
     */
    public function generateCheckoutLink($data) {
        // Paramètres requis par MaishaPay
        $params = [
            'gatewayMode' => $this->gatewayMode,
            'api_key' => $this->apiKey,
            'secret_key' => $this->secretKey,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'CDF',
            'order_id' => $data['order_id'] ?? uniqid('MALOTY_'),
            'description' => $data['description'] ?? 'Don à l\'église MALOTY',
            'success_url' => $data['success_url'],
            'cancel_url' => $data['cancel_url'],
            'customer_name' => $data['customer_name'] ?? 'Donateur Anonyme',
            'customer_email' => $data['customer_email'] ?? 'contact@maloty.com',
            'customer_phone' => $data['customer_phone'] ?? ''
        ];

        // Construction du formulaire d'auto-redirection (Méthode simple Checkout)
        // MaishaPay utilise généralement une redirection via POST ou une URL formatée
        // Ici on simule l'initiation de la transaction
        
        $url = "https://maishapay.net/checkout";
        
        // Note: Certains SDKs MaishaPay demandent un hachage ou un appel API préalable
        // Pour cette démo, on prépare le lien retourné au frontend
        
        return [
            'status' => 'success',
            'payment_url' => $url . '?' . http_build_query($params),
            'order_id' => $params['order_id']
        ];
    }

    /**
     * Vérifier le statut d'une transaction (Webhook/Callback)
     */
    public function verifyTransaction($transactionId) {
        // Simulation d'une vérification API
        // Normalement: GET maishapay.net/api/v1/transaction/status/{id}
        return [
            'status' => 'completed',
            'transaction_id' => $transactionId,
            'amount' => 0,
            'currency' => 'CDF'
        ];
    }
}
