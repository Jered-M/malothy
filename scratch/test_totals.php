<?php
define('PROJECT_ROOT', dirname(__DIR__));
require_once dirname(__DIR__) . '/backend/config/database.php';

$db = Database::getInstance()->getConnection();
$year = 2026; $month = 6;

$tithes = $db->query(
    "SELECT COALESCE(SUM(CASE WHEN currency='USD' THEN amount*2800 ELSE amount END),0) as tot
     FROM tithes
     WHERE EXTRACT(YEAR FROM tithe_date)=$year AND EXTRACT(MONTH FROM tithe_date)=$month
     AND (payment_status IS NULL OR payment_status NOT IN ('failed','cancelled','rejected','error'))"
)->fetchColumn();

$offerings = $db->query(
    "SELECT COALESCE(SUM(CASE WHEN currency='USD' THEN amount*2800 ELSE amount END),0) as tot
     FROM offerings
     WHERE EXTRACT(YEAR FROM offering_date)=$year AND EXTRACT(MONTH FROM offering_date)=$month
     AND (payment_status IS NULL OR payment_status NOT IN ('failed','cancelled','rejected','error'))"
)->fetchColumn();

$expenses = $db->query(
    "SELECT COALESCE(SUM(CASE WHEN currency='USD' THEN amount*2800 ELSE amount END),0) as tot
     FROM expenses
     WHERE EXTRACT(YEAR FROM expense_date)=$year AND EXTRACT(MONTH FROM expense_date)=$month
     AND (status IS NULL OR status::text != 'rejetee')"
)->fetchColumn();

echo "Dimes  ce mois : $tithes CDF" . PHP_EOL;
echo "Offr.  ce mois : $offerings CDF" . PHP_EOL;
echo "Dep.   ce mois : $expenses CDF" . PHP_EOL;
echo "Solde          : " . ($tithes + $offerings - $expenses) . " CDF" . PHP_EOL;
