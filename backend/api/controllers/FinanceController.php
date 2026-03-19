<?php
/**
 * API FinanceController
 * Handles Tithes, Offerings, and Financial summary
 */

require_once PROJECT_ROOT . '/backend/models/Tithe.php';
require_once PROJECT_ROOT . '/backend/models/Offering.php';
require_once PROJECT_ROOT . '/backend/models/Member.php';

class FinanceController {
    private $titheModel;
    private $offeringModel;
    private $memberModel;

    public function __construct() {
        $this->titheModel = new Tithe();
        $this->offeringModel = new Offering();
        $this->memberModel = new Member();
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

        $required = ['member_id', 'amount', 'tithe_date'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                json_error("Champ '{$field}' requis", 400);
            }
        }

        $data = [
            'member_id' => $input['member_id'],
            'amount' => $input['amount'],
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
}
