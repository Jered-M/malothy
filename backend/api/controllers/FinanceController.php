<?php
/**
 * API FinanceController
 * Handles Tithes, Offerings, and Financial summary
 */

require_once PROJECT_ROOT . '/backend/models/Tithe.php';
require_once PROJECT_ROOT . '/backend/models/Offering.php';
require_once PROJECT_ROOT . '/backend/models/Member.php';
require_once PROJECT_ROOT . '/backend/api/services/MaishaPayService.php';

class FinanceController {
    private $titheModel;
    private $offeringModel;
    private $memberModel;
    private $maishaPay;

    public function __construct() {
        $this->titheModel = new Tithe();
        $this->offeringModel = new Offering();
        $this->memberModel = new Member();
        $this->maishaPay = new MaishaPayService();
    }

    /**
     * GET /api/finance/tithes
     */
    public function tithes() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->record_tithe();
        }
        checkRole(['admin', 'Trésorier']);
        
        $memberId = $_GET['member_id'] ?? null;
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;

        $data = $this->titheModel->search($memberId, $startDate, $endDate);
        
        json_response([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * POST /api/finance/record_tithe
     */
    public function record_tithe() {
        $user = checkRole(['admin', 'Trésorier']);
        $input = get_input();

        $required = ['amount', 'tithe_date'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                json_error("Champ '{$field}' requis", 400);
            }
        }

        $data = [
            'member_id' => !empty($input['member_id']) && $input['member_id'] !== 'null' ? $input['member_id'] : null,
            'amount' => $input['amount'],
            'currency' => $input['currency'] ?? 'CDF',
            'tithe_date' => $input['tithe_date'],
            'comment' => $input['comment'] ?? '',
            'recorded_by' => $user['id'] ?? null
        ];

        $id = $this->titheModel->recordTithe($data);

        json_response([
            'success' => true,
            'message' => 'Dîme enregistrée avec succès',
            'id' => $id
        ], 201);
    }

    /**
     * GET /api/finance/offerings
     */
    public function offerings() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->record_offering();
        }
        checkRole(['admin', 'Trésorier']);
        
        $type = $_GET['type'] ?? null;
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;

        $data = $this->offeringModel->search($type, $startDate, $endDate);
        
        json_response([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * POST /api/finance/record_offering
     */
    public function record_offering() {
        $user = checkRole(['admin', 'Trésorier']);
        $input = get_input();

        $required = ['type', 'amount', 'offering_date'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                json_error("Champ '{$field}' requis", 400);
            }
        }

        $data = [
            'type' => $input['type'],
            'amount' => $input['amount'],
            'currency' => $input['currency'] ?? 'CDF',
            'offering_date' => $input['offering_date'],
            'description' => $input['description'] ?? '',
            'recorded_by' => $user['id'] ?? null
        ];

        $id = $this->offeringModel->recordOffering($data);

        json_response([
            'success' => true,
            'message' => 'Offrande enregistrée avec succès',
            'id' => $id
        ], 201);
    }

    /**
     * GET /api/finance/summary
     */
    public function summary() {
        checkRole(['admin', 'Trésorier']);
        
        $year = $_GET['year'] ?? date('Y');
        $month = $_GET['month'] ?? date('m');

        $tithes = $this->titheModel->getMonthlyTotal($year, $month);
        $offerings = $this->offeringModel->getMonthlyTotal($year, $month);

        json_response([
            'success' => true,
            'data' => [
                'tithes_total' => $tithes,
                'offerings_total' => $offerings,
                'grand_total' => $tithes + $offerings
            ]
        ]);
    }

    /**
     * GET /api/finance/public_members
     * Liste publique simplifiée des membres pour sélection
     */
    public function public_members() {
        $members = $this->memberModel->findAll('first_name ASC');
        $simplified = array_map(function($m) {
            return [
                'id' => $m['id'],
                'name' => $m['first_name'] . ' ' . $m['last_name']
            ];
        }, $members);

        json_response([
            'success' => true,
            'data' => $simplified
        ]);
    }

    /**
     * POST /api/finance/public_tithe
     * Enregistrement d'une dîme par un membre (sans authentification staff)
     */
    public function public_tithe() {
        $input = get_input();
        $required = ['amount', 'tithe_date'];
        
        foreach ($required as $field) {
            if (empty($input[$field])) {
                json_error("Champ '{$field}' requis", 400);
            }
        }

        $memberId = $input['member_id'] ?? null;
        $memberName = $input['member_name'] ?? 'Donateur Anonyme';

        $data = [
            'member_id' => $memberId,
            'amount' => $input['amount'],
            'currency' => $input['currency'] ?? 'CDF',
            'tithe_date' => $input['tithe_date'],
            'payment_status' => 'pending',
            'comment' => "Don par: {$memberName} | " . ($input['comment'] ?? '') . ' (MaishaPay Pending)',
            'recorded_by' => null 
        ];

        $id = $this->titheModel->recordTithe($data);

        // Préparer le lien MaishaPay
        $paymentData = $this->maishaPay->generateCheckoutLink([
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'description' => "Dîme de {$memberName}",
            'customer_name' => $memberName,
            'success_url' => APP_URL . '/contribute?status=success&id=' . $id,
            'cancel_url' => APP_URL . '/contribute?status=cancel'
        ]);

        json_response([
            'success' => true,
            'message' => 'Redirection vers le paiement...',
            'payment_url' => $paymentData['payment_url'],
            'id' => $id
        ], 201);
    }

    /**
     * POST /api/finance/public_offering
     * Enregistrement d'une offrande par un membre (sans authentification staff)
     */
    public function public_offering() {
        $input = get_input();
        $required = ['type', 'amount', 'offering_date'];
        
        foreach ($required as $field) {
            if (empty($input[$field])) {
                json_error("Champ '{$field}' requis", 400);
            }
        }

        $memberName = $input['member_name'] ?? 'Donateur Anonyme';

        $data = [
            'type' => $input['type'],
            // Pas de member_id ici car la table offerings ne possède pas cette colonne dans le schéma actuel
            'amount' => $input['amount'],
            'currency' => $input['currency'] ?? 'CDF',
            'offering_date' => $input['offering_date'],
            'payment_status' => 'pending',
            'description' => ($input['description'] ?? "Don de {$memberName}") . ' (MaishaPay Pending)',
            'recorded_by' => null // Public
        ];

        $id = $this->offeringModel->recordOffering($data);

        // Préparer le lien MaishaPay
        $paymentData = $this->maishaPay->generateCheckoutLink([
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'description' => "Offrande de {$memberName}",
            'customer_name' => $memberName,
            'success_url' => APP_URL . '/contribute?status=success&id=' . $id,
            'cancel_url' => APP_URL . '/contribute?status=cancel'
        ]);

        json_response([
            'success' => true,
            'message' => 'Redirection vers le paiement...',
            'payment_url' => $paymentData['payment_url'],
            'id' => $id
        ], 201);
    }
}
