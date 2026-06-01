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

        // Simple file-cache to reduce DB load for frequent dashboard refreshes
        $cacheFile = PROJECT_ROOT . '/tmp/dashboard_cache.json';
        $cacheTtl = 30; // seconds
        if (file_exists($cacheFile)) {
            $cached = @json_decode(@file_get_contents($cacheFile), true);
            if (!empty($cached['ts']) && (time() - $cached['ts'] < $cacheTtl)) {
                json_response([
                    'success' => true,
                    'stats' => $cached['stats'],
                    'chartData' => $cached['chartData'],
                    'cached' => true
                ]);
                return;
            }
        }

        $stats = $this->getStats();
        $chartData = $this->getChartData(6);

        // write cache (best-effort)
        @file_put_contents($cacheFile, json_encode(['ts' => time(), 'stats' => $stats, 'chartData' => $chartData]));

        json_response([
            'success' => true,
            'stats' => $stats,
            'chartData' => $chartData,
            'cached' => false
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
        $titheCurrencyExpr = $this->hasColumn('tithes', 'currency') ? "CASE WHEN currency = 'USD' THEN amount * 2800 ELSE amount END" : "amount";
        // Use BETWEEN start and end dates to leverage possible index on tithe_date
        $titheSql = "
            SELECT 
                EXTRACT(YEAR FROM tithe_date) as yr, 
                EXTRACT(MONTH FROM tithe_date) as mon, 
                SUM({$titheCurrencyExpr}) as total
            FROM tithes 
            WHERE tithe_date BETWEEN ? AND ?
        ";
        if ($this->hasColumn('tithes', 'payment_status')) {
            $titheSql .= " AND (payment_status IS NULL OR payment_status NOT IN ('failed', 'cancelled', 'rejected', 'error'))";
        }
        $titheSql .= " GROUP BY yr, mon";
        
        $endDate = $current->format('Y-m-t');
        $stmt = $this->db->prepare($titheSql);
        $stmt->execute([$startDate, $endDate]);
        while ($row = $stmt->fetch()) {
            $monthObj = new DateTime("{$row['yr']}-{$row['mon']}-01");
            $diff = $current->diff($monthObj);
            $index = ($months - 1) - ($diff->y * 12 + $diff->m);
            if ($index >= 0 && $index < $months) {
                $tithes[$index] = (float)$row['total'];
            }
        }

        // 2. Récupérer toutes les offrandes groupées par mois
        $offeringCurrencyExpr = $this->hasColumn('offerings', 'currency') ? "CASE WHEN currency = 'USD' THEN amount * 2800 ELSE amount END" : "amount";
        $offeringSql = "
            SELECT 
                EXTRACT(YEAR FROM offering_date) as yr, 
                EXTRACT(MONTH FROM offering_date) as mon, 
                SUM({$offeringCurrencyExpr}) as total
            FROM offerings 
            WHERE offering_date BETWEEN ? AND ?
        ";
        if ($this->hasColumn('offerings', 'payment_status')) {
            $offeringSql .= " AND (payment_status IS NULL OR payment_status NOT IN ('failed', 'cancelled', 'rejected', 'error'))";
        }
        $offeringSql .= " GROUP BY yr, mon";

        $stmt = $this->db->prepare($offeringSql);
        $stmt->execute([$startDate, $endDate]);
        while ($row = $stmt->fetch()) {
            $monthObj = new DateTime("{$row['yr']}-{$row['mon']}-01");
            $diff = $current->diff($monthObj);
            $index = ($months - 1) - ($diff->y * 12 + $diff->m);
            if ($index >= 0 && $index < $months) {
                $offerings[$index] = (float)$row['total'];
            }
        }

        // 3. Récupérer toutes les dépenses groupées par mois
        $expenseCurrencyExpr = $this->hasColumn('expenses', 'currency') ? "CASE WHEN currency = 'USD' THEN amount * 2800 ELSE amount END" : "amount";
        $expenseSql = "
            SELECT 
                EXTRACT(YEAR FROM expense_date) as yr, 
                EXTRACT(MONTH FROM expense_date) as mon, 
                SUM({$expenseCurrencyExpr}) as total
            FROM expenses 
            WHERE expense_date BETWEEN ? AND ?
        ";
        if ($this->hasColumn('expenses', 'status')) {
            $expenseSql .= " AND (status IS NULL OR status::text != 'rejetee')";
        }
        $expenseSql .= " GROUP BY yr, mon";

        $stmt = $this->db->prepare($expenseSql);
        $stmt->execute([$startDate, $endDate]);
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

    private static $cachedSchema = [];

    private function hasColumn($table, $column) {
        if (empty(self::$cachedSchema)) {
            try {
                $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
                $tables = ['tithes', 'offerings', 'expenses'];
                $placeholders = implode(',', array_fill(0, count($tables), '?'));
                
                if ($driver === 'pgsql') {
                    $sql = "SELECT table_name, column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name IN ($placeholders)";
                } else {
                    $sql = "SELECT table_name, column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name IN ($placeholders)";
                }
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute($tables);
                while ($row = $stmt->fetch()) {
                    self::$cachedSchema[$row['table_name'] . '.' . $row['column_name']] = true;
                }
            } catch (Exception $e) {
                // En cas d'erreur, on laisse le cache vide
            }
        }
        
        return isset(self::$cachedSchema["$table.$column"]);
    }

    private function getValidatedTitheTotal($year = null, $month = null, $memberId = null) {
        $currencyExpr = $this->hasColumn('tithes', 'currency') ? "CASE WHEN currency = 'USD' THEN amount * 2800 ELSE amount END" : "amount";
        $sql = "SELECT COALESCE(SUM({$currencyExpr}), 0) FROM tithes WHERE 1=1";
        $params = [];

        if ($memberId !== null) {
            $sql .= ' AND member_id = ?';
            $params[] = $memberId;
        }

        if ($year !== null && $month !== null) {
            // Use date range for faster queries (can use index on tithe_date)
            $start = sprintf('%04d-%02d-01', $year, $month);
            $end = date('Y-m-d', strtotime($start . ' +1 month'));
            $sql .= ' AND tithe_date >= ? AND tithe_date < ?';
            $params[] = $start;
            $params[] = $end;
        } elseif ($year !== null) {
            $sql .= ' AND EXTRACT(YEAR FROM tithe_date) = ?';
            $params[] = $year;
        }

        if ($this->hasColumn('tithes', 'payment_status')) {
            $sql .= " AND (payment_status IS NULL OR payment_status NOT IN ('failed', 'cancelled', 'rejected', 'error'))";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (float)$stmt->fetchColumn();
    }

    private function getValidatedOfferingTotal($year = null, $month = null, $memberId = null) {
        if ($memberId !== null && !$this->hasColumn('offerings', 'member_id')) {
            return 0.0;
        }
        $currencyExpr = $this->hasColumn('offerings', 'currency') ? "CASE WHEN currency = 'USD' THEN amount * 2800 ELSE amount END" : "amount";
        $sql = "SELECT COALESCE(SUM({$currencyExpr}), 0) FROM offerings WHERE 1=1";
        $params = [];

        if ($memberId !== null) {
            $sql .= ' AND member_id = ?';
            $params[] = $memberId;
        }

        if ($year !== null && $month !== null) {
            $start = sprintf('%04d-%02d-01', $year, $month);
            $end = date('Y-m-d', strtotime($start . ' +1 month'));
            $sql .= ' AND offering_date >= ? AND offering_date < ?';
            $params[] = $start;
            $params[] = $end;
        } elseif ($year !== null) {
            $sql .= ' AND EXTRACT(YEAR FROM offering_date) = ?';
            $params[] = $year;
        }

        if ($this->hasColumn('offerings', 'payment_status')) {
            $sql .= " AND (payment_status IS NULL OR payment_status NOT IN ('failed', 'cancelled', 'rejected', 'error'))";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (float)$stmt->fetchColumn();
    }

    private function getApprovedExpenseTotal($year = null, $month = null) {
        $currencyExpr = $this->hasColumn('expenses', 'currency') ? "CASE WHEN currency = 'USD' THEN amount * 2800 ELSE amount END" : "amount";
        $sql = "SELECT COALESCE(SUM({$currencyExpr}), 0) FROM expenses WHERE 1=1";
        $params = [];

        if ($year !== null && $month !== null) {
            $start = sprintf('%04d-%02d-01', $year, $month);
            $end = date('Y-m-d', strtotime($start . ' +1 month'));
            $sql .= ' AND expense_date >= ? AND expense_date < ?';
            $params[] = $start;
            $params[] = $end;
        } elseif ($year !== null) {
            $sql .= ' AND EXTRACT(YEAR FROM expense_date) = ?';
            $params[] = $year;
        }

        if ($this->hasColumn('expenses', 'status')) {
            $sql .= " AND (status IS NULL OR status::text != 'rejetee')";
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
            $titheSql .= " AND (payment_status IS NULL OR payment_status NOT IN ('failed', 'cancelled', 'rejected', 'error'))";
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
                $offeringSql .= " AND (payment_status IS NULL OR payment_status NOT IN ('failed', 'cancelled', 'rejected', 'error'))";
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
