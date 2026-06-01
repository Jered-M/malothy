<?php
/**
 * Debug : vérification des dîmes, offrandes et dépenses en base
 * Accès : http://localhost:8000/debug_finance.php
 */
define('PROJECT_ROOT', __DIR__);
require_once __DIR__ . '/backend/config/database.php';

header('Content-Type: text/html; charset=utf-8');
$db = Database::getInstance()->getConnection();

$year  = date('Y');
$month = date('m');

echo "<pre style='font-family:monospace;font-size:13px;padding:20px'>\n";
echo "=== DIAGNOSTIC FINANCES (" . date('Y-m-d H:i:s') . ") ===\n\n";

/* ---- Schéma ---- */
$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
echo "Driver : $driver\n\n";

/* ---- Dîmes ---- */
echo "--- DÎMES ---\n";
$r = $db->query("SELECT COUNT(*) as n, MIN(tithe_date) as min_d, MAX(tithe_date) as max_d FROM tithes")->fetch();
echo "Total rows : {$r['n']} | Date min : {$r['min_d']} | Date max : {$r['max_d']}\n";

$rows = $db->query("SELECT id, amount, tithe_date, payment_status, currency FROM tithes ORDER BY tithe_date DESC LIMIT 10")->fetchAll();
foreach ($rows as $row) {
    echo "  id={$row['id']} amount={$row['amount']} currency={$row['currency']} payment_status=".
         ($row['payment_status'] ?? 'NULL')." date={$row['tithe_date']}\n";
}

// Totaux avec et sans filtre
$t1 = $db->query("SELECT COALESCE(SUM(CASE WHEN currency='USD' THEN amount*2800 ELSE amount END),0) as tot FROM tithes")->fetchColumn();
$t2 = $db->query("SELECT COALESCE(SUM(CASE WHEN currency='USD' THEN amount*2800 ELSE amount END),0) as tot FROM tithes WHERE EXTRACT(YEAR FROM tithe_date)=$year AND EXTRACT(MONTH FROM tithe_date)=$month")->fetchColumn();
$t3 = $db->query("SELECT COALESCE(SUM(CASE WHEN currency='USD' THEN amount*2800 ELSE amount END),0) as tot FROM tithes WHERE payment_status IN ('paid','success')")->fetchColumn();
echo "  Total ALL   : $t1 CDF\n";
echo "  Total ce mois ($year-$month) : $t2 CDF\n";
echo "  Total avec filtre 'paid/success' : $t3 CDF\n\n";

/* ---- Offrandes ---- */
echo "--- OFFRANDES ---\n";
$r = $db->query("SELECT COUNT(*) as n FROM offerings")->fetch();
echo "Total rows : {$r['n']}\n";
$rows = $db->query("SELECT id, amount, offering_date, payment_status, currency FROM offerings ORDER BY offering_date DESC LIMIT 10")->fetchAll();
foreach ($rows as $row) {
    echo "  id={$row['id']} amount={$row['amount']} currency={$row['currency']} payment_status=".
         ($row['payment_status'] ?? 'NULL')." date={$row['offering_date']}\n";
}
$o1 = $db->query("SELECT COALESCE(SUM(CASE WHEN currency='USD' THEN amount*2800 ELSE amount END),0) FROM offerings")->fetchColumn();
$o2 = $db->query("SELECT COALESCE(SUM(CASE WHEN currency='USD' THEN amount*2800 ELSE amount END),0) FROM offerings WHERE EXTRACT(YEAR FROM offering_date)=$year AND EXTRACT(MONTH FROM offering_date)=$month")->fetchColumn();
echo "  Total ALL   : $o1 CDF\n";
echo "  Total ce mois ($year-$month) : $o2 CDF\n\n";

/* ---- Dépenses ---- */
echo "--- DÉPENSES ---\n";
$r = $db->query("SELECT COUNT(*) as n FROM expenses")->fetch();
echo "Total rows : {$r['n']}\n";
$rows = $db->query("SELECT id, amount, expense_date, status, currency FROM expenses ORDER BY expense_date DESC LIMIT 10")->fetchAll();
foreach ($rows as $row) {
    echo "  id={$row['id']} amount={$row['amount']} currency={$row['currency']} status=".
         ($row['status'] ?? 'NULL')." date={$row['expense_date']}\n";
}
$e1 = $db->query("SELECT COALESCE(SUM(CASE WHEN currency='USD' THEN amount*2800 ELSE amount END),0) FROM expenses")->fetchColumn();
$e2 = $db->query("SELECT COALESCE(SUM(CASE WHEN currency='USD' THEN amount*2800 ELSE amount END),0) FROM expenses WHERE EXTRACT(YEAR FROM expense_date)=$year AND EXTRACT(MONTH FROM expense_date)=$month")->fetchColumn();
echo "  Total ALL   : $e1 CDF\n";
echo "  Total ce mois ($year-$month) : $e2 CDF\n\n";

echo "=== FIN DIAGNOSTIC ===\n";
echo "</pre>\n";
