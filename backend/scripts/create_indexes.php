<?php
/**
 * Script d'optimisation : création d'indexes recommandés.
 * Exécuter : php backend/scripts/create_indexes.php
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../backend/lib/Database.php';
$db = Database::getInstance()->getConnection();

$queries = [
    // Tithes
    "CREATE INDEX IF NOT EXISTS idx_tithes_tithe_date ON tithes(tithe_date);",
    "CREATE INDEX IF NOT EXISTS idx_tithes_payment_status ON tithes(payment_status);",
    "CREATE INDEX IF NOT EXISTS idx_tithes_member_id ON tithes(member_id);",
    // Offerings
    "CREATE INDEX IF NOT EXISTS idx_offerings_offering_date ON offerings(offering_date);",
    "CREATE INDEX IF NOT EXISTS idx_offerings_payment_status ON offerings(payment_status);",
    "CREATE INDEX IF NOT EXISTS idx_offerings_member_id ON offerings(member_id);",
    // Expenses
    "CREATE INDEX IF NOT EXISTS idx_expenses_expense_date ON expenses(expense_date);",
    "CREATE INDEX IF NOT EXISTS idx_expenses_status ON expenses(status);",
    // Payments
    "CREATE INDEX IF NOT EXISTS idx_payments_ref ON payments(payment_ref);",
    "CREATE INDEX IF NOT EXISTS idx_payments_status ON payments(status);",
    "CREATE INDEX IF NOT EXISTS idx_members_status ON members(status);",
];

foreach ($queries as $q) {
    try {
        $db->exec($q);
        echo "OK: $q\n";
    } catch (Exception $e) {
        echo "ERR: " . $e->getMessage() . "\n";
    }
}

echo "Finished.\n";
