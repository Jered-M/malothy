<?php
/**
 * API DashboardController
 */

require_once PROJECT_ROOT . '/backend/models/Member.php';
require_once PROJECT_ROOT . '/backend/models/Tithe.php';
require_once PROJECT_ROOT . '/backend/models/Offering.php';
require_once PROJECT_ROOT . '/backend/models/Expense.php';

class DashboardController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * GET /api/dashboard
     */
    public function index() {
        $user = get_authenticated_user();

        // Statistiques
        $stats = $this->getStats();

        json_response([
            'success' => true,
            'stats' => $stats
        ]);
    }

    private function getStats() {
        $db = Database::getInstance()->getConnection();

        // Total membres
        $members = $db->query('SELECT COUNT(*) as count FROM members WHERE status = "actif"')->fetch();
        
        // Dîmes ce mois
        $tithes = $db->query('SELECT SUM(amount) as total FROM tithes WHERE YEAR(tithe_date) = YEAR(NOW()) AND MONTH(tithe_date) = MONTH(NOW())')->fetch();
        
        // Offrandes ce mois
        $offerings = $db->query('SELECT SUM(amount) as total FROM offerings WHERE YEAR(offering_date) = YEAR(NOW()) AND MONTH(offering_date) = MONTH(NOW())')->fetch();
        
        // Dépenses ce mois
        $expenses = $db->query('SELECT SUM(amount) as total FROM expenses WHERE YEAR(expense_date) = YEAR(NOW()) AND MONTH(expense_date) = MONTH(NOW())')->fetch();

        return [
            'totalMembers' => (int)($members['count'] ?? 0),
            'monthlyTithes' => (float)($tithes['total'] ?? 0),
            'monthlyOfferings' => (float)($offerings['total'] ?? 0),
            'monthlyExpenses' => (float)($expenses['total'] ?? 0),
        ];
    }
}
