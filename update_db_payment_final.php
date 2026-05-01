<?php
/**
 * Script de mise à jour pour garantir l'existence de payment_status et member_id (pour offerings)
 */

require_once __DIR__ . '/backend/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Connexion établie...\n";

    // 1. Ajouter payment_status et currency à tithes si absent
    try {
        $db->exec("ALTER TABLE tithes ADD COLUMN IF NOT EXISTS payment_status VARCHAR(20) DEFAULT 'success'");
        $db->exec("ALTER TABLE tithes ADD COLUMN IF NOT EXISTS currency VARCHAR(3) DEFAULT 'CDF'");
        echo "Colonnes 'payment_status' et 'currency' vérifiées dans table 'tithes'.\n";
    } catch (Exception $e) { echo "Note Tithes columns: " . $e->getMessage() . "\n"; }

    // 2. Ajouter payment_status et currency à offerings si absent
    try {
        $db->exec("ALTER TABLE offerings ADD COLUMN IF NOT EXISTS payment_status VARCHAR(20) DEFAULT 'success'");
        $db->exec("ALTER TABLE offerings ADD COLUMN IF NOT EXISTS currency VARCHAR(3) DEFAULT 'CDF'");
        echo "Colonnes 'payment_status' et 'currency' vérifiées dans table 'offerings'.\n";
    } catch (Exception $e) { echo "Note Offerings columns: " . $e->getMessage() . "\n"; }

    // 3. Ajouter member_id à offerings si absent
    try {
        $db->exec("ALTER TABLE offerings ADD COLUMN IF NOT EXISTS member_id INTEGER DEFAULT NULL");
        echo "Colonne 'member_id' vérifiée dans table 'offerings'.\n";
    } catch (Exception $e) { echo "Note Offerings Member: " . $e->getMessage() . "\n"; }

    // 4. Mettre à jour les anciennes données pour qu'elles soient 'success' par défaut
    $db->exec("UPDATE tithes SET payment_status = 'success' WHERE payment_status IS NULL");
    $db->exec("UPDATE offerings SET payment_status = 'success' WHERE payment_status IS NULL");

    echo "Mise à jour terminée avec succès.\n";
} catch (Exception $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
}
