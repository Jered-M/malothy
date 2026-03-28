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

        // Total membres (PostgreSQL: guillemets simples pour 'actif')
        $members = $db->query("SELECT COUNT(*) as count FROM members WHERE status = 'actif'")->fetch();
        
        // Dîmes ce mois (PostgreSQL: EXTRACT)
        $tithes = $db->query("SELECT SUM(amount) as total FROM tithes WHERE EXTRACT(YEAR FROM tithe_date) = EXTRACT(YEAR FROM CURRENT_DATE) AND EXTRACT(MONTH FROM tithe_date) = EXTRACT(MONTH FROM CURRENT_DATE)")->fetch();
        
        // Offrandes ce mois
        $offerings = $db->query("SELECT SUM(amount) as total FROM offerings WHERE EXTRACT(YEAR FROM offering_date) = EXTRACT(YEAR FROM CURRENT_DATE) AND EXTRACT(MONTH FROM offering_date) = EXTRACT(MONTH FROM CURRENT_DATE)")->fetch();
        
        // Dépenses ce mois
        $expenses = $db->query("SELECT SUM(amount) as total FROM expenses WHERE EXTRACT(YEAR FROM expense_date) = EXTRACT(YEAR FROM CURRENT_DATE) AND EXTRACT(MONTH FROM expense_date) = EXTRACT(MONTH FROM CURRENT_DATE)")->fetch();

        return [
            'totalMembers' => (int)($members['count'] ?? 0),
            'monthlyTithes' => (float)($tithes['total'] ?? 0),
            'monthlyOfferings' => (float)($offerings['total'] ?? 0),
            'monthlyExpenses' => (float)($expenses['total'] ?? 0),
        ];
    }
}
