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
    private $memberModel;
    private $titheModel;
    private $offeringModel;
    private $expenseModel;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->memberModel = new Member();
        $this->titheModel = new Tithe();
        $this->offeringModel = new Offering();
        $this->expenseModel = new Expense();
    }

    /**
     * GET /api/dashboard
     */
    public function index() {
        $user = get_authenticated_user();

        // Statistiques
        $stats = $this->getStats();
        $chartData = $this->getChartData(6);

        json_response([
            'success' => true,
            'stats' => $stats,
            'chartData' => $chartData
        ]);
    }

    private function getStats() {
        $db = Database::getInstance()->getConnection();

        // Total membres (PostgreSQL: guillemets simples pour 'actif')
        $membersActive = $db->query("SELECT COUNT(*) as count FROM members WHERE status = 'actif'")->fetch();
        $membersTotal = $db->query("SELECT COUNT(*) as count FROM members")->fetch();
        
        // Dîmes ce mois (PostgreSQL: EXTRACT)
        $tithes = $db->query("SELECT SUM(amount) as total FROM tithes WHERE EXTRACT(YEAR FROM tithe_date) = EXTRACT(YEAR FROM CURRENT_DATE) AND EXTRACT(MONTH FROM tithe_date) = EXTRACT(MONTH FROM CURRENT_DATE)")->fetch();
        
        // Offrandes ce mois
        $offerings = $db->query("SELECT SUM(amount) as total FROM offerings WHERE EXTRACT(YEAR FROM offering_date) = EXTRACT(YEAR FROM CURRENT_DATE) AND EXTRACT(MONTH FROM offering_date) = EXTRACT(MONTH FROM CURRENT_DATE)")->fetch();
        
        // Dépenses ce mois
        $expenses = $db->query("SELECT SUM(amount) as total FROM expenses WHERE EXTRACT(YEAR FROM expense_date) = EXTRACT(YEAR FROM CURRENT_DATE) AND EXTRACT(MONTH FROM expense_date) = EXTRACT(MONTH FROM CURRENT_DATE)")->fetch();

        return [
            'totalMembers' => (int)($membersTotal['count'] ?? 0),
            'activeMembers' => (int)($membersActive['count'] ?? 0),
            'monthlyTithes' => (float)($tithes['total'] ?? 0),
            'monthlyOfferings' => (float)($offerings['total'] ?? 0),
            'monthlyExpenses' => (float)($expenses['total'] ?? 0),
        ];
    }

    private function getChartData($months = 6) {
        $labels = [];
        $tithes = [];
        $offerings = [];
        $expenses = [];

        $current = new DateTimeImmutable('first day of this month');

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = $current->modify("-{$i} months");
            $year = $month->format('Y');
            $monthNum = $month->format('m');

            $labels[] = $month->format('M');
            $tithes[] = (float)$this->titheModel->getMonthlyTotal($year, $monthNum);
            $offerings[] = (float)$this->offeringModel->getMonthlyTotal($year, $monthNum);
            $expenses[] = (float)$this->expenseModel->getMonthlyTotal($year, $monthNum);
        }

        return [
            'labels' => $labels,
            'tithes' => $tithes,
            'offerings' => $offerings,
            'expenses' => $expenses
        ];
    }
}
