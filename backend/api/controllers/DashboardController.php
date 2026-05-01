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
    private $schemaCache = [];

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
        checkRole(['admin', 'tresorier', 'secretaire']);

        json_response([
            'success' => true,
            'stats' => $this->getStats(),
            'chartData' => $this->getChartData(6)
        ]);
    }

    private function getStats() {
        $membersActive = $this->db->query("SELECT COUNT(*) as count FROM members WHERE status = 'actif'")->fetch();
        $membersTotal = $this->db->query("SELECT COUNT(*) as count FROM members")->fetch();
        $year = date('Y');
        $month = date('m');

        return [
            'totalMembers' => (int)($membersTotal['count'] ?? 0),
            'activeMembers' => (int)($membersActive['count'] ?? 0),
            'monthlyTithes' => $this->getValidatedTitheTotal($year, $month),
            'monthlyOfferings' => $this->getValidatedOfferingTotal($year, $month),
            'monthlyExpenses' => $this->getApprovedExpenseTotal($year, $month),
        ];
    }

    private function getChartData($months = 6) {
        $labels = [];
        $tithes = array_fill(0, $months, 0);
        $offerings = array_fill(0, $months, 0);
        $expenses = array_fill(0, $months, 0);
        
        $current = new DateTimeImmutable('first day of this month');
        $startDate = $current->modify("-" . ($months - 1) . " months")->format('Y-m-01');

        // Préparer les labels
        for ($i = $months - 1; $i >= 0; $i--) {
            $labels[] = $current->modify("-{$i} months")->format('M');
        }

        // 1. Récupérer toutes les dîmes groupées par mois
        $titheSql = "
            SELECT 
                EXTRACT(YEAR FROM tithe_date) as yr, 
                EXTRACT(MONTH FROM tithe_date) as mon, 
                SUM(amount) as total
            FROM tithes 
            WHERE tithe_date >= ?
        ";
        if ($this->hasColumn('tithes', 'payment_status')) {
            $titheSql .= " AND payment_status IN ('paid', 'success', 'confirmed')";
        }
        $titheSql .= " GROUP BY yr, mon";
        
        $stmt = $this->db->prepare($titheSql);
        $stmt->execute([$startDate]);
        while ($row = $stmt->fetch()) {
            $monthObj = new DateTime("{$row['yr']}-{$row['mon']}-01");
            $diff = $current->diff($monthObj);
            $index = ($months - 1) - ($diff->y * 12 + $diff->m);
            if ($index >= 0 && $index < $months) {
                $tithes[$index] = (float)$row['total'];
            }
        }

        // 2. Récupérer toutes les offrandes groupées par mois
        $offeringSql = "
            SELECT 
                EXTRACT(YEAR FROM offering_date) as yr, 
                EXTRACT(MONTH FROM offering_date) as mon, 
                SUM(amount) as total
            FROM offerings 
            WHERE offering_date >= ?
        ";
        if ($this->hasColumn('offerings', 'payment_status')) {
            $offeringSql .= " AND payment_status IN ('paid', 'success', 'confirmed')";
        }
        $offeringSql .= " GROUP BY yr, mon";

        $stmt = $this->db->prepare($offeringSql);
        $stmt->execute([$startDate]);
        while ($row = $stmt->fetch()) {
            $monthObj = new DateTime("{$row['yr']}-{$row['mon']}-01");
            $diff = $current->diff($monthObj);
            $index = ($months - 1) - ($diff->y * 12 + $diff->m);
            if ($index >= 0 && $index < $months) {
                $offerings[$index] = (float)$row['total'];
            }
        }

        // 3. Récupérer toutes les dépenses groupées par mois
        $expenseSql = "
            SELECT 
                EXTRACT(YEAR FROM expense_date) as yr, 
                EXTRACT(MONTH FROM expense_date) as mon, 
                SUM(amount) as total
            FROM expenses 
            WHERE expense_date >= ?
        ";
        if ($this->hasColumn('expenses', 'status')) {
            $expenseSql .= " AND status = 'approuvee'";
        }
        $expenseSql .= " GROUP BY yr, mon";

        $stmt = $this->db->prepare($expenseSql);
        $stmt->execute([$startDate]);
        while ($row = $stmt->fetch()) {
            $monthObj = new DateTime("{$row['yr']}-{$row['mon']}-01");
            $diff = $current->diff($monthObj);
            $index = ($months - 1) - ($diff->y * 12 + $diff->m);
            if ($index >= 0 && $index < $months) {
                $expenses[$index] = (float)$row['total'];
            }
        }

        return [
            'labels' => $labels,
            'tithes' => $tithes,
            'offerings' => $offerings,
            'expenses' => $expenses
        ];
    }

    /**
     * GET /api/dashboard/member
     */
    public function member() {
        $user = get_authenticated_user();
        $email = $user['email'] ?? '';

        if ($email === '') {
            json_response([
                'success' => true,
                'is_member' => false,
                'message' => 'Aucun email n est lie a cette session utilisateur.',
                'stats' => [
                    'totalTithes' => 0,
                    'totalOfferings' => 0,
                    'lastContributions' => []
                ]
            ]);
            return;
        }

        $stmtMember = $this->db->prepare('SELECT * FROM members WHERE email = ?');
        $stmtMember->execute([$email]);
        $member = $stmtMember->fetch();

        if (!$member) {
            json_response([
                'success' => true,
                'is_member' => false,
                'message' => 'Aucun profil membre lie a ce compte email.',
                'stats' => [
                    'totalTithes' => 0,
                    'totalOfferings' => 0,
                    'lastContributions' => []
                ]
            ]);
            return;
        }

        $memberId = $member['id'];

        json_response([
            'success' => true,
            'is_member' => true,
            'member' => $member,
            'stats' => [
                'totalTithes' => $this->getValidatedTitheTotal(null, null, $memberId),
                'totalOfferings' => $this->getValidatedOfferingTotal(null, null, $memberId),
                'lastContributions' => $this->getRecentMemberContributions($memberId)
            ]
        ]);
    }

    private static $cachedColumns = [];

    private function hasColumn($table, $column) {
        $cacheKey = $table . '.' . $column;
        if (array_key_exists($cacheKey, self::$cachedColumns)) {
            return self::$cachedColumns[$cacheKey];
        }

        try {
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'pgsql') {
                $sql = "
                    SELECT COUNT(*) FROM information_schema.columns
                    WHERE table_schema = 'public' AND table_name = ? AND column_name = ?
                ";
            } else {
                $sql = "
                    SELECT COUNT(*) FROM information_schema.columns
                    WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?
                ";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$table, $column]);
            $exists = (int)$stmt->fetchColumn() > 0;
            self::$cachedColumns[$cacheKey] = $exists;
            return $exists;
        } catch (Exception $e) {
            return false;
        }
    }

    private function getValidatedTitheTotal($year = null, $month = null, $memberId = null) {
        $sql = 'SELECT COALESCE(SUM(amount), 0) FROM tithes WHERE 1=1';
        $params = [];

        if ($memberId !== null) {
            $sql .= ' AND member_id = ?';
            $params[] = $memberId;
        }

        if ($year !== null) {
            $sql .= ' AND EXTRACT(YEAR FROM tithe_date) = ?';
            $params[] = $year;
        }

        if ($month !== null) {
            $sql .= ' AND EXTRACT(MONTH FROM tithe_date) = ?';
            $params[] = (int)$month;
        }

        if ($this->hasColumn('tithes', 'payment_status')) {
            $sql .= " AND payment_status IN ('paid', 'success')";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (float)$stmt->fetchColumn();
    }

    private function getValidatedOfferingTotal($year = null, $month = null, $memberId = null) {
        $sql = 'SELECT COALESCE(SUM(amount), 0) FROM offerings WHERE 1=1';
        $params = [];

        if ($memberId !== null) {
            if (!$this->hasColumn('offerings', 'member_id')) {
                return 0.0;
            }
            $sql .= ' AND member_id = ?';
            $params[] = $memberId;
        }

        if ($year !== null) {
            $sql .= ' AND EXTRACT(YEAR FROM offering_date) = ?';
            $params[] = $year;
        }

        if ($month !== null) {
            $sql .= ' AND EXTRACT(MONTH FROM offering_date) = ?';
            $params[] = (int)$month;
        }

        if ($this->hasColumn('offerings', 'payment_status')) {
            $sql .= " AND payment_status IN ('paid', 'success')";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (float)$stmt->fetchColumn();
    }

    private function getApprovedExpenseTotal($year = null, $month = null) {
        $sql = 'SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE 1=1';
        $params = [];

        if ($year !== null) {
            $sql .= ' AND EXTRACT(YEAR FROM expense_date) = ?';
            $params[] = $year;
        }

        if ($month !== null) {
            $sql .= ' AND EXTRACT(MONTH FROM expense_date) = ?';
            $params[] = (int)$month;
        }

        if ($this->hasColumn('expenses', 'status')) {
            $sql .= " AND status = 'approuvee'";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (float)$stmt->fetchColumn();
    }

    private function getRecentMemberContributions($memberId) {
        $entries = [];
        $titheCurrencyExpr = $this->hasColumn('tithes', 'currency') ? "COALESCE(currency, 'CDF')" : "'CDF'";
        $titheSql = "
            SELECT 'Dime' as type, amount, {$titheCurrencyExpr} as currency, tithe_date as date_val
            FROM tithes
            WHERE member_id = ?
        ";

        if ($this->hasColumn('tithes', 'payment_status')) {
            $titheSql .= " AND payment_status IN ('paid', 'success')";
        }

        $titheSql .= ' ORDER BY tithe_date DESC LIMIT 10';
        $stmtTithes = $this->db->prepare($titheSql);
        $stmtTithes->execute([$memberId]);
        $entries = array_merge($entries, $stmtTithes->fetchAll());

        if ($this->hasColumn('offerings', 'member_id')) {
            $offeringCurrencyExpr = $this->hasColumn('offerings', 'currency') ? "COALESCE(currency, 'CDF')" : "'CDF'";
            $offeringSql = "
                SELECT 'Offrande' as type, amount, {$offeringCurrencyExpr} as currency, offering_date as date_val
                FROM offerings
                WHERE member_id = ?
            ";

            if ($this->hasColumn('offerings', 'payment_status')) {
                $offeringSql .= " AND payment_status IN ('paid', 'success')";
            }

            $offeringSql .= ' ORDER BY offering_date DESC LIMIT 10';
            $stmtOfferings = $this->db->prepare($offeringSql);
            $stmtOfferings->execute([$memberId]);
            $entries = array_merge($entries, $stmtOfferings->fetchAll());
        }

        usort($entries, function ($left, $right) {
            return strcmp((string)($right['date_val'] ?? ''), (string)($left['date_val'] ?? ''));
        });

        return array_slice($entries, 0, 10);
    }
}
