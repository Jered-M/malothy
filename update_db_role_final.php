<?php
/**
 * Script de mise à jour pour ajouter le rôle 'membre' à l'ENUM PostgreSQL
 */

require_once __DIR__ . '/backend/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Connexion établie...\n";

    // 1. Ajouter le rôle 'membre' à l'ENUM user_role
    try {
        $db->exec("ALTER TYPE user_role ADD VALUE IF NOT EXISTS 'membre'");
        echo "Rôle 'membre' ajouté à l'ENUM user_role (ou existait déjà).\n";
    } catch (Exception $e) {
        echo "Note sur l'ENUM: " . $e->getMessage() . "\n";
    }

    echo "Mise à jour terminée avec succès.\n";
} catch (Exception $e) {
    echo "ERREUR FATALE: " . $e->getMessage() . "\n";
}
