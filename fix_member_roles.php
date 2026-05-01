<?php
/**
 * Script pour corriger les rôles des comptes membres existants
 */

require_once __DIR__ . '/backend/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Connexion établie...\n";

    // Mettre à jour les utilisateurs dont le nom correspond à un membre
    // Pour simplifier, on va mettre à jour tous ceux qui n'ont pas un email système (admin, treasury, secretary)
    $stmt = $db->prepare("
        UPDATE users 
        SET role = 'membre' 
        WHERE email NOT IN ('admin@maloty.com', 'treasure@maloty.com', 'secretary@maloty.com')
        AND role = 'secrétaire'
    ");
    
    $stmt->execute();
    $count = $stmt->rowCount();
    
    echo "Mise à jour terminée : $count comptes mis en rôle 'membre'.\n";
} catch (Exception $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
}
