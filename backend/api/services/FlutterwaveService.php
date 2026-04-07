<?php
/**
 * Flutterwave Service Configuration
 */

class FlutterwaveService {
    private $secretKey;
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        // Load dynamically from settings
        $stmt = $this->db->query("SELECT setting_value FROM settings WHERE setting_key = 'flutterwave_secret_key'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->secretKey = $result ? $result['setting_value'] : (defined('FLUTTERWAVE_SECRET_KEY') ? FLUTTERWAVE_SECRET_KEY : '');
    }

    /**
     * Générer l'URL de paiement Flutterwave Checkout
     */
    public function generateCheckoutLink($data) {
        $url = "https://api.flutterwave.com/v3/payments";
        $order_id = $data['order_id'] ?? uniqid('MALOTY_');
        
        $payload = [
            "tx_ref" => $order_id,
            "amount" => $data['amount'],
            "currency" => $data['currency'] ?? "CDF",
            "redirect_url" => $data['success_url'],
            "customer" => [
                "email" => "contact@maloty.com", // Default fallback if not provided
                "phonenumber" => "",
                "name" => $data['customer_name'] ?? 'Donateur Anonyme'
            ],
            "customizations" => [
                "title" => "MALOTY",
                "description" => $data['description'] ?? 'Paiement',
                "logo" => APP_URL . "/assets/images/logo.png" // Provide a proper logo URL if possible
            ]
        ];

        // Ensure we handle missing secret key gracefully or test environment differences
        if (empty($this->secretKey)) {
            throw new Exception("Clé secrète Flutterwave manquante. Veuillez la configurer dans les paramètres.");
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $this->secretKey,
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new Exception("Erreur cURL: " . $err);
        }

        $res = json_decode($response, true);
        if (isset($res['status']) && $res['status'] === 'success') {
            return [
                'status' => 'success',
                'payment_url' => $res['data']['link'],
                'order_id' => $order_id
            ];
        } else {
            $errorMsg = $res['message'] ?? 'Erreur lors de la génération du lien de paiement';
            throw new Exception($errorMsg);
        }
    }
}
