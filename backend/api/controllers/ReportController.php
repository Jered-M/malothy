<?php
/**
 * API ReportController
 * Handles Financial Reports and Data Exports (PDF/JSON/CSV)
 */

require_once PROJECT_ROOT . '/backend/models/Tithe.php';
require_once PROJECT_ROOT . '/backend/models/Offering.php';
require_once PROJECT_ROOT . '/backend/models/Expense.php';

class ReportController {
    private $titheModel;
    private $offeringModel;
    private $expenseModel;

    public function __construct() {
        $this->titheModel = new Tithe();
        $this->offeringModel = new Offering();
        $this->expenseModel = new Expense();
    }

    /**
     * GET /api/report/balance-sheet
     * Total Income vs Expenses for a period
     */
    public function balance_sheet() {
        get_authenticated_user();
        
        $year = $_GET['year'] ?? date('Y');
        $month = $_GET['month'] ?? date('m');

        if ($month === 'all') {
            $income_tithes = $this->titheModel->getYearlyTotal($year);
            $income_offerings = $this->offeringModel->getYearlyTotal($year);
            $expenses = $this->expenseModel->getYearlyTotal($year);
            $period = "AnnÃ©e $year";
        } else {
            $income_tithes = $this->titheModel->getMonthlyTotal($year, $month);
            $income_offerings = $this->offeringModel->getMonthlyTotal($year, $month);
            $expenses = $this->expenseModel->getMonthlyTotal($year, $month);
            $period = "Mois $month/$year";
        }

        $total_income = $income_tithes + $income_offerings;
        $balance = $total_income - $expenses;

        json_response([
            'success' => true,
            'data' => [
                'period' => $period,
                'tithes' => (float)$income_tithes,
                'offerings' => (float)$income_offerings,
                'total_income' => (float)$total_income,
                'expenses' => (float)$expenses,
                'balance' => (float)$balance,
                'currency' => 'CDF'
            ]
        ]);
    }

    /**
     * GET /api/report/export-json
     * Full database export for backup
     */
    public function export_json() {
        $user = get_authenticated_user();
        if (strtolower($user['role']) !== 'admin' && strtolower($user['role']) !== 'administrateur') {
            json_error('Seul un administrateur peut exporter la base complÃ¨te', 403);
        }

        $db = Database::getInstance()->getConnection();
        $tables = ['users', 'members', 'tithes', 'offerings', 'expenses', 'audit_logs', 'settings'];
        $backup = [];

        foreach ($tables as $table) {
            $backup[$table] = $db->query("SELECT * FROM $table")->fetchAll();
        }

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="backup_' . date('Y-m-d_H-i') . '.json"');
        echo json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * GET /api/report/member_tithes
     * Detailed tithe report for a specific member
     */
    public function member_tithes($memberId) {
        get_authenticated_user();
        
        $year = $_GET['year'] ?? date('Y');
        $tithes = $this->titheModel->search($memberId, "$year-01-01", "$year-12-31");
        $stats = $this->titheModel->getTithesByMember($memberId, $year);

        json_response([
            'success' => true,
            'data' => [
                'member_id' => $memberId,
                'year' => $year,
                'tithes' => $tithes,
                'total_amount' => $stats['total'],
                'count' => $stats['count']
            ]
        ]);
    }

    /**
     * GET /api/report/export-pdf
     * Generate PDF report (balance sheet or list)
     */
    public function export_pdf() {
        $user = get_authenticated_user();
        $role = strtolower($user['role'] ?? '');
        if ($role !== 'admin' && $role !== 'administrateur') {
            json_error('Seul un administrateur peut gÃ©nÃ©rer des PDF', 403);
        }

        $type = $_GET['type'] ?? 'balance';
        $year = $_GET['year'] ?? date('Y');
        $month = $_GET['month'] ?? null;

        // Generate HTML content
        $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport ' . $type . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
        h1 { color: #0054a1; text-align: center; }
        p { text-align: center; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #0054a1; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .summary { margin-top: 20px; padding: 15px; background: #f0f7ff; border-left: 4px solid #0054a1; }
        .total-row { font-weight: bold; background-color: #e8f2ff; }
    </style>
</head>
<body>';

        if ($type === 'balance') {
            $html .= $this->generateBalanceSheetHTML($year, $month);
        } elseif ($type === 'tithes') {
            $html .= $this->generateTithesHTML($year, $month);
        } elseif ($type === 'offerings') {
            $html .= $this->generateOfferingsHTML($year, $month);
        } elseif ($type === 'expenses') {
            $html .= $this->generateExpensesHTML($year, $month);
        }

        $html .= '<p style="margin-top: 40px; font-size: 10px; color: #999;">
                    GÃ©nÃ©rÃ© par MALOTY - ' . date('d/m/Y H:i') . '
                  </p>
            </body></html>';

        // Send as downloadable HTML (browsers can "save as PDF")
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="rapport_' . $type . '_' . date('Y-m-d') . '.html"');
        echo $html;
        exit;
    }

    /**
     * GET /api/report/export-csv
     * Export data as CSV (members, tithes, offerings, expenses)
     */
    public function export_csv() {
        get_authenticated_user();
        
        $type = $_GET['type'] ?? 'members'; // members, tithes, offerings, expenses
        $year = $_GET['year'] ?? date('Y');
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="export_' . $type . '_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel

        if ($type === 'members') {
            $this->exportMembersCSV($output);
        } elseif ($type === 'tithes') {
            $this->exportTithesCSV($output, $year);
        } elseif ($type === 'offerings') {
            $this->exportOfferingsCSV($output, $year);
        } elseif ($type === 'expenses') {
            $this->exportExpensesCSV($output, $year);
        }

        fclose($output);
        exit;
    }

    /**
     * GET /api/report/export-sql
     * Export database as SQL dump
     */
    public function export_sql() {
        $user = get_authenticated_user();
        $role = strtolower($user['role'] ?? '');
        if ($role !== 'admin' && $role !== 'administrateur') {
            json_error('Seul un administrateur peut exporter la base SQL', 403);
        }

        $db = Database::getInstance()->getConnection();
        $tables = ['users', 'members', 'tithes', 'offerings', 'expenses', 'audit_logs', 'settings'];
        
        $sql = "-- MALOTY Database Backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- by Admin: " . $user['email'] . "\n\n";

        foreach ($tables as $table) {
            $result = $db->query("SELECT * FROM $table");
            $sql .= $this->tableToSQL($table, $result->fetchAll());
        }

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="backup_' . date('Y-m-d_H-i-s') . '.sql"');
        echo $sql;
        exit;
    }

    // ===================== CSV Export Helpers =====================

    private function writeCsvRow($output, array $row, string $delimiter = ';') {
        fputcsv($output, $row, $delimiter, '"', '\\');
    }

    private function exportMembersCSV($output) {
        $this->writeCsvRow($output, ['ID', 'Prenom', 'Nom', 'Email', 'Telephone', 'Adresse', 'Departement', 'Date Adhesion', 'Statut']);
        
        require_once PROJECT_ROOT . '/backend/models/Member.php';
        $memberModel = new Member();
        $members = $memberModel->findAll();

        foreach ($members as $m) {
            $this->writeCsvRow($output, [
                $m['id'],
                $m['first_name'] ?? '',
                $m['last_name'] ?? '',
                $m['email'] ?? '',
                $m['phone'] ?? '',
                $m['address'] ?? '',
                $m['department'] ?? '',
                $m['join_date'] ?? '',
                $m['status'] ?? 'active'
            ]);
        }
    }

    private function exportTithesCSV($output, $year) {
        $this->writeCsvRow($output, ['ID', 'Membre', 'Montant', 'Date', 'Enregistre par', 'Commentaire']);
        
        if ($year === 'all') {
            $tithes = $this->titheModel->search();
        } else {
            $tithes = $this->titheModel->search(null, "$year-01-01", "$year-12-31");
        }

        foreach ($tithes as $t) {
            $this->writeCsvRow($output, [
                $t['id'],
                ($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? ''),
                (float)$t['amount'],
                $t['tithe_date'] ?? '',
                $t['recorded_by_id'] ?? '',
                $t['notes'] ?? ''
            ]);
        }
    }

    private function exportOfferingsCSV($output, $year) {
        $this->writeCsvRow($output, ['ID', 'Type', 'Montant', 'Date', 'Culte/Evenement', 'Commentaire']);
        
        if ($year === 'all') {
            $offerings = $this->offeringModel->search();
        } else {
            $offerings = $this->offeringModel->search(null, "$year-01-01", "$year-12-31");
        }

        foreach ($offerings as $o) {
            $this->writeCsvRow($output, [
                $o['id'],
                $o['type'] ?? '',
                (float)$o['amount'],
                $o['offering_date'] ?? '',
                $o['event'] ?? '',
                $o['notes'] ?? ''
            ]);
        }
    }

    private function exportExpensesCSV($output, $year) {
        $this->writeCsvRow($output, ['ID', 'Categorie', 'Montant', 'Date', 'Description', 'Statut', 'Approuve par']);
        
        if ($year === 'all') {
            $expenses = $this->expenseModel->search();
        } else {
            $expenses = $this->expenseModel->search(null, "$year-01-01", "$year-12-31");
        }

        foreach ($expenses as $e) {
            $this->writeCsvRow($output, [
                $e['id'],
                $e['category'] ?? '',
                (float)$e['amount'],
                $e['expense_date'] ?? '',
                $e['description'] ?? '',
                $e['status'] ?? 'pending',
                $e['approved_by_id'] ?? ''
            ]);
        }
    }

    // ===================== HTML Report Generators =====================

    private function generateBalanceSheetHTML($year, $month) {
        if ($month === 'all' || !$month) {
            $income_tithes = $this->titheModel->getYearlyTotal($year);
            $income_offerings = $this->offeringModel->getYearlyTotal($year);
            $expenses = $this->expenseModel->getYearlyTotal($year);
            $period = "AnnÃ©e $year";
        } else {
            $income_tithes = $this->titheModel->getMonthlyTotal($year, $month);
            $income_offerings = $this->offeringModel->getMonthlyTotal($year, $month);
            $expenses = $this->expenseModel->getMonthlyTotal($year, $month);
            $period = "Mois " . str_pad($month, 2, '0', STR_PAD_LEFT) . "/$year";
        }

        $total_income = $income_tithes + $income_offerings;
        $balance = $total_income - $expenses;

        return '<h1>MALOTY - Bilan Financier</h1>
                <p>PÃ©riode: ' . htmlspecialchars($period) . '</p>
                <table>
                    <tr><th>Type</th><th>Montant (CDF)</th></tr>
                    <tr><td>DÃ®mes</td><td>' . number_format($income_tithes, 0, ',', ' ') . '</td></tr>
                    <tr><td>Offrandes</td><td>' . number_format($income_offerings, 0, ',', ' ') . '</td></tr>
                    <tr class="total-row"><td>Total EntrÃ©es</td><td>' . number_format($total_income, 0, ',', ' ') . '</td></tr>
                    <tr><td>DÃ©penses</td><td>' . number_format($expenses, 0, ',', ' ') . '</td></tr>
                    <tr class="total-row"><td>SOLDE</td><td>' . number_format($balance, 0, ',', ' ') . '</td></tr>
                </table>';
    }

    private function generateTithesHTML($year, $month) {
        if ($month) {
            $tithes = $this->titheModel->search(null, "$year-$month-01", "$year-$month-31");
            $title = "DÃ®mes du mois " . str_pad($month, 2, '0', STR_PAD_LEFT) . "/$year";
        } else {
            $tithes = $this->titheModel->search(null, "$year-01-01", "$year-12-31");
            $title = "DÃ®mes de l'annÃ©e $year";
        }

        $html = '<h1>MALOTY - ' . htmlspecialchars($title) . '</h1>';
        $html .= '<table><tr><th>Membre</th><th>Montant (CDF)</th><th>Date</th></tr>';
        
        $total = 0;
        foreach ($tithes as $t) {
            $html .= '<tr><td>' . htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) . '</td>';
            $html .= '<td>' . number_format((float)$t['amount'], 0, ',', ' ') . '</td>';
            $html .= '<td>' . $t['tithe_date'] . '</td></tr>';
            $total += (float)$t['amount'];
        }
        
        $html .= '<tr class="total-row"><td colspan="1">TOTAL</td><td>' . number_format($total, 0, ',', ' ') . '</td><td></td></tr></table>';
        return $html;
    }

    private function generateOfferingsHTML($year, $month) {
        if ($month) {
            $offerings = $this->offeringModel->search(null, "$year-$month-01", "$year-$month-31");
            $title = "Offrandes du mois " . str_pad($month, 2, '0', STR_PAD_LEFT) . "/$year";
        } else {
            $offerings = $this->offeringModel->search(null, "$year-01-01", "$year-12-31");
            $title = "Offrandes de l'annÃ©e $year";
        }

        $html = '<h1>MALOTY - ' . htmlspecialchars($title) . '</h1>';
        $html .= '<table><tr><th>Type</th><th>Montant (CDF)</th><th>Ã‰vÃ©nement</th><th>Date</th></tr>';
        
        $total = 0;
        foreach ($offerings as $o) {
            $html .= '<tr><td>' . htmlspecialchars($o['type']) . '</td>';
            $html .= '<td>' . number_format((float)$o['amount'], 0, ',', ' ') . '</td>';
            $html .= '<td>' . htmlspecialchars($o['event'] ?? '-') . '</td>';
            $html .= '<td>' . $o['offering_date'] . '</td></tr>';
            $total += (float)$o['amount'];
        }
        
        $html .= '<tr class="total-row"><td colspan="2">TOTAL</td><td>' . number_format($total, 0, ',', ' ') . '</td><td></td></tr></table>';
        return $html;
    }

    private function generateExpensesHTML($year, $month) {
        if ($month) {
            $expenses = $this->expenseModel->search(null, "$year-$month-01", "$year-$month-31");
            $title = "DÃ©penses du mois " . str_pad($month, 2, '0', STR_PAD_LEFT) . "/$year";
        } else {
            $expenses = $this->expenseModel->search(null, "$year-01-01", "$year-12-31");
            $title = "DÃ©penses de l'annÃ©e $year";
        }

        $html = '<h1>MALOTY - ' . htmlspecialchars($title) . '</h1>';
        $html .= '<table><tr><th>CatÃ©gorie</th><th>Montant (CDF)</th><th>Description</th><th>Statut</th><th>Date</th></tr>';
        
        $total = 0;
        foreach ($expenses as $e) {
            $html .= '<tr><td>' . htmlspecialchars($e['category']) . '</td>';
            $html .= '<td>' . number_format((float)$e['amount'], 0, ',', ' ') . '</td>';
            $html .= '<td>' . htmlspecialchars($e['description']) . '</td>';
            $html .= '<td>' . strtoupper($e['status']) . '</td>';
            $html .= '<td>' . $e['expense_date'] . '</td></tr>';
            $total += (float)$e['amount'];
        }
        
        $html .= '<tr class="total-row"><td colspan="1">TOTAL</td><td>' . number_format($total, 0, ',', ' ') . '</td><td colspan="3"></td></tr></table>';
        return $html;
    }

    // SQL Export Helper
    private function tableToSQL($table, $rows) {
        $sql = "\n-- Table: $table\n";
        $sql .= "DELETE FROM $table;\n";

        if (empty($rows)) return $sql;

        $columns = array_keys((array)$rows[0]);
        $sql .= "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES\n";

        $values = [];
        foreach ($rows as $row) {
            $vals = [];
            foreach ($columns as $col) {
                $val = $row[$col] ?? null;
                if (is_null($val)) {
                    $vals[] = 'NULL';
                } else {
                    $vals[] = "'" . str_replace("'", "''", $val) . "'";
                }
            }
            $values[] = "(" . implode(', ', $vals) . ")";
        }

        $sql .= implode(",\n", $values) . ";\n";
        return $sql;
    }
}


