<?php
/**
 * Contrôleur pour le dashboard
 */

require_once PROJECT_ROOT . '/controllers/BaseController.php';
require_once PROJECT_ROOT . '/models/Member.php';
require_once PROJECT_ROOT . '/models/Tithe.php';
require_once PROJECT_ROOT . '/models/Offering.php';
require_once PROJECT_ROOT . '/models/Expense.php';

class DashboardController extends BaseController {
    private $memberModel;
    private $titheModel;
    private $offeringModel;
    private $expenseModel;

    public function __construct() {
        $this->memberModel = new Member();
        $this->titheModel = new Tithe();
        $this->offeringModel = new Offering();
        $this->expenseModel = new Expense();
    }

    /**
     * Afficher le tableau de bord
     */
    public function index() {
        $this->requireLogin();

        $today = date('Y-m-d');
        $year = date('Y');
        $month = date('m');
        $startOfMonth = date('Y-m-01');

        // Récupérer les statistiques
        $stats = [
            'total_members' => $this->memberModel->getActiveMembersCount(),
            'tithes_month' => $this->titheModel->getMonthlyTotal($year, $month),
            'offerings_month' => $this->offeringModel->getMonthlyTotal($year, $month),
            'expenses_month' => $this->expenseModel->getMonthlyTotal($year, $month),
            'tithes_year' => $this->titheModel->getYearlyTotal($year),
            'offerings_year' => $this->offeringModel->getYearlyTotal($year),
            'expenses_year' => $this->expenseModel->getYearlyTotal($year),
        ];

        // Calculer le solde
        $income = $stats['tithes_year'] + $stats['offerings_year'];
        $expense = $stats['expenses_year'];
        $stats['balance'] = $income - $expense;

        // Données pour les graphiques
        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyData[$m] = [
                'tithes' => $this->titheModel->getMonthlyTotal($year, $m),
                'offerings' => $this->offeringModel->getMonthlyTotal($year, $m),
                'expenses' => $this->expenseModel->getMonthlyTotal($year, $m),
            ];
        }

        $chartData = [
            'labels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
            'tithes' => array_map(fn($m) => $m['tithes'], $monthlyData),
            'offerings' => array_map(fn($m) => $m['offerings'], $monthlyData),
            'expenses' => array_map(fn($m) => $m['expenses'], $monthlyData),
        ];

        // Récentes transactions
        $recentTithes = array_slice($this->titheModel->search(), 0, 5);
        $recentOfferings = array_slice($this->offeringModel->search(), 0, 5);
        $recentExpenses = array_slice($this->expenseModel->search(), 0, 5);

        $user = getCurrentUser();

        $this->view('dashboard/index', [
            'stats' => $stats,
            'chartData' => $chartData,
            'recentTithes' => $recentTithes,
            'recentOfferings' => $recentOfferings,
            'recentExpenses' => $recentExpenses,
            'user' => $user
        ]);
    }

    /**
     * Obtenir les statistiques du dashboard en JSON (via API)
     */
    public function getStats() {
        $this->requireLogin();

        $year = date('Y');
        $month = date('m');

        // Récupérer les statistiques
        $stats = [
            'total_members' => $this->memberModel->getActiveMembersCount(),
            'tithes_month' => $this->titheModel->getMonthlyTotal($year, $month),
            'offerings_month' => $this->offeringModel->getMonthlyTotal($year, $month),
            'expenses_month' => $this->expenseModel->getMonthlyTotal($year, $month),
            'tithes_year' => $this->titheModel->getYearlyTotal($year),
            'offerings_year' => $this->offeringModel->getYearlyTotal($year),
            'expenses_year' => $this->expenseModel->getYearlyTotal($year),
        ];

        // Calculer le solde
        $income = $stats['tithes_year'] + $stats['offerings_year'];
        $expense = $stats['expenses_year'];
        $stats['balance'] = $income - $expense;

        // Données pour les graphiques
        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyData[$m] = [
                'tithes' => $this->titheModel->getMonthlyTotal($year, $m),
                'offerings' => $this->offeringModel->getMonthlyTotal($year, $m),
                'expenses' => $this->expenseModel->getMonthlyTotal($year, $m),
            ];
        }

        $chartData = [
            'labels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
            'tithes' => array_map(fn($m) => $m['tithes'], $monthlyData),
            'offerings' => array_map(fn($m) => $m['offerings'], $monthlyData),
            'expenses' => array_map(fn($m) => $m['expenses'], $monthlyData),
        ];

        // Récentes transactions
        $recentTithes = array_slice($this->titheModel->search(), 0, 5);
        $recentOfferings = array_slice($this->offeringModel->search(), 0, 5);
        $recentExpenses = array_slice($this->expenseModel->search(), 0, 5);

        $this->json([
            'success' => true,
            'stats' => $stats,
            'chartData' => $chartData,
            'recentTransactions' => [
                'tithes' => $recentTithes,
                'offerings' => $recentOfferings,
                'expenses' => $recentExpenses
            ]
        ]);
    }
}
?>
