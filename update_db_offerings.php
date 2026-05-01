<?php
$db = new PDO('pgsql:host=localhost;port=6543;dbname=postgres', 'postgres.jxlhjeqyrtrnhziuizlw', 'DW,%%pXKh4tS*Xc');
try {
    $db->exec("ALTER TABLE offerings ADD COLUMN IF NOT EXISTS member_id INTEGER REFERENCES members(id) ON DELETE SET NULL");
    echo "Colonne 'member_id' ajoutée à la table 'offerings'\n";
    
    // Also add 'membre' to user_role if failed before
    try {
        $db->exec("ALTER TYPE user_role ADD VALUE IF NOT EXISTS 'membre'");
        echo "Rôle 'membre' ajouté au type ENUM\n";
    } catch (Exception $e) {}

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
