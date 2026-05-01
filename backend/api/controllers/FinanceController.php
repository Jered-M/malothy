<?php
/**
 * API FinanceController
 * Handles Tithes, Offerings, and Financial summary
 */

require_once PROJECT_ROOT . '/backend/models/Tithe.php';
require_once PROJECT_ROOT . '/backend/models/Offering.php';
require_once PROJECT_ROOT . '/backend/models/Member.php';
require_once PROJECT_ROOT . '/backend/api/services/FlutterwaveService.php';
require_once PROJECT_ROOT . '/backend/api/services/LocalPaymentService.php';
require_once PROJECT_ROOT . '/backend/api/services/MaishaPayService.php';

class FinanceController {
    private $titheModel;
    private $offeringModel;
    private $memberModel;
    private $flutterwaveService;
    private $localPaymentService;
    private $maishaPayService;

    public function __construct() {
        $db = Database::getInstance()->getConnection();
        $this->titheModel = new Tithe();
        $this->offeringModel = new Offering();
        $this->memberModel = new Member();
        $this->flutterwaveService = new FlutterwaveService();
        $this->localPaymentService = new LocalPaymentService($db);
        $this->maishaPayService = new MaishaPayService();
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
     * 
     * Utilise le système de paiement LOCAL (sans dépendance Flutterwave)
     * Retourne une référence de paiement + code de confirmation
     */
    public function public_tithe() {
        try {
            $input = get_input();
            $required = ['amount', 'tithe_date'];
            
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    json_error("Champ '{$field}' requis", 400);
                }
            }

            $memberId = $input['member_id'] ?? null;
            $memberName = $input['member_name'] ?? 'Donateur Anonyme';

            // 1. Enregistrer la dîme en DB
            $data = [
                'member_id' => $memberId,
                'amount' => $input['amount'],
                'currency' => $input['currency'] ?? 'CDF',
                'tithe_date' => $input['tithe_date'],
                'payment_status' => 'pending',
                'comment' => ($input['comment'] ?? '') . " | Enregistré via formulaire public",
                'recorded_by' => null 
            ];

            $id = $this->titheModel->recordTithe($data);

            // 2. Créer une demande de paiement local
            $paymentRequest = $this->localPaymentService->createPaymentRequest([
                'type' => 'tithe',
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'donor_name' => $memberName,
                'donor_email' => $input['donor_email'] ?? '',
                'donor_phone' => $input['donor_phone'] ?? '',
                'member_id' => $memberId,
                'description' => "Dîme de {$memberName} - " . date('d/m/Y')
            ]);

            if ($paymentRequest['status'] !== 'success') {
                json_error('Erreur lors de la création de la demande de paiement : ' . ($paymentRequest['message'] ?? 'Erreur inconnue'), 500);
            }

            json_response([
                'success' => true,
                'message' => 'Demande de paiement créée. Veuillez communiquer votre code de confirmation.',
                'payment' => [
                    'reference' => $paymentRequest['payment_ref'],
                    'confirmation_code' => $paymentRequest['confirmation_code'],
                    'amount' => $paymentRequest['amount'],
                    'currency' => $paymentRequest['currency'],
                    'expires_at' => $paymentRequest['expires_at'],
                    'instructions' => [
                        '1. Communiquez votre référence de paiement au trésorier',
                        '2. Effectuez votre paiement via Mobile Money (M-Pesa, Airtel, Orange)',
                        '3. Confirmez avec le code ci-dessus',
                        '4. Le trésorier marquera votre paiement comme confirmé'
                    ]
                ],
                'id' => $id
            ], 201);
        } catch (Exception $e) {
            json_error('Exception dans public_tithe: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/finance/public_offering
     * Enregistrement d'une offrande par un membre (sans authentification staff)
     * 
     * Utilise le système de paiement LOCAL (sans dépendance Flutterwave)
     * Retourne une référence de paiement + code de confirmation
     */
    public function public_offering() {
        try {
            $input = get_input();
            $required = ['type', 'amount', 'offering_date'];
            
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    json_error("Champ '{$field}' requis", 400);
                }
            }

            $memberId = $input['member_id'] ?? null;
            $memberName = $input['member_name'] ?? 'Donateur Anonyme';

            // 1. Enregistrer l'offrande en DB
            $data = [
                'type' => $input['type'],
                'member_id' => $memberId,
                'amount' => $input['amount'],
                'currency' => $input['currency'] ?? 'CDF',
                'offering_date' => $input['offering_date'],
                'payment_status' => 'pending',
                'description' => ($input['description'] ?? "Offrande de {$memberName}") . ' | Enregistré via formulaire public',
                'recorded_by' => null // Public
            ];

            $id = $this->offeringModel->recordOffering($data);

            // 2. Créer une demande de paiement local
            $paymentRequest = $this->localPaymentService->createPaymentRequest([
                'type' => 'offering',
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'donor_name' => $memberName,
                'donor_email' => $input['donor_email'] ?? '',
                'donor_phone' => $input['donor_phone'] ?? '',
                'member_id' => $memberId,
                'description' => "Offrande ({$input['type']}) de {$memberName} - " . date('d/m/Y')
            ]);

            if ($paymentRequest['status'] !== 'success') {
                json_error('Erreur lors de la création de la demande de paiement : ' . ($paymentRequest['message'] ?? 'Erreur inconnue'), 500);
            }

            json_response([
                'success' => true,
                'message' => 'Demande de paiement créée. Veuillez communiquer votre code de confirmation.',
                'payment' => [
                    'reference' => $paymentRequest['payment_ref'],
                    'confirmation_code' => $paymentRequest['confirmation_code'],
                    'amount' => $paymentRequest['amount'],
                    'currency' => $paymentRequest['currency'],
                    'expires_at' => $paymentRequest['expires_at'],
                    'instructions' => [
                        '1. Communiquez votre référence de paiement au trésorier',
                        '2. Effectuez votre paiement via Mobile Money (M-Pesa, Airtel, Orange)',
                        '3. Confirmez avec le code ci-dessus',
                        '4. Le trésorier marquera votre paiement comme confirmé'
                    ]
                ],
                'id' => $id
            ], 201);
        } catch (Exception $e) {
            json_error('Exception dans public_offering: ' . $e->getMessage(), 500);
        }
    }
}
