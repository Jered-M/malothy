<?php
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

echo "═══════════════════════════════════════════════════════\n";
echo "INSERTION DE DONNÉES DE DÉMO RÉALISTES\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Vider les tables existantes
echo "🗑️  Nettoyage des données existantes...\n";
$pdo->exec('DELETE FROM expenses');
$pdo->exec('DELETE FROM offerings');
$pdo->exec('DELETE FROM tithes');
$pdo->exec('DELETE FROM members');

// Insérer les membres
echo "👥 Création de 8 membres...\n";
$members = [
    ['Jean', 'Dupont', 'jean.dupont@email.com', '06 12 34 56 78', '15 rue de la Paix, Paris', 'Ministère de louange', '2020-03-15'],
    ['Marie', 'Martin', 'marie.martin@email.com', '06 23 45 67 89', '22 avenue des Fleurs, Lyon', 'Diaconie', '2019-06-20'],
    ['Pierre', 'Bernard', 'pierre.bernard@email.com', '06 34 56 78 90', '8 rue de l\'Église, Marseille', 'Intercession', '2021-01-10'],
    ['Sophie', 'Lefevre', 'sophie.lefevre@email.com', '06 45 67 89 01', '45 boulevard Saint-Michel, Toulouse', 'Enseignement', '2018-11-05'],
    ['Luc', 'Moreau', 'luc.moreau@email.com', '06 56 78 90 12', '33 rue des Arts, Nice', 'Accueil', '2022-02-14'],
    ['Nathalie', 'Petit', 'nathalie.petit@email.com', '06 67 89 01 23', '9 chemin du Bois, Nantes', 'Administration', '2019-08-30'],
    ['Marc', 'Richard', 'marc.richard@email.com', '06 78 90 12 34', '12 allée des Roses, Strasbourg', 'Technique', '2021-05-22'],
    ['Isabelle', 'Dubois', 'isabelle.dubois@email.com', '06 89 01 23 45', '56 rue de la Liberté, Bordeaux', 'Finances', '2020-09-11'],
];

$stmt = $pdo->prepare('INSERT INTO members (first_name, last_name, email, phone, address, department, join_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
$memberIds = [];
foreach ($members as $member) {
    $member[] = 'actif';
    $stmt->execute($member);
    $memberIds[] = $pdo->lastInsertId();
}

// Insérer les dîmes
echo "💰 Création de 15 dîmes...\n";
$tithes = [
    ['2026-03-15', 250.00, 'Dîme régulière'],
    ['2026-03-14', 180.00, ''],
    ['2026-03-10', 320.50, 'Bénédiction spéciale'],
    ['2026-03-08', 145.00, ''],
    ['2026-03-01', 210.00, 'Dîme mensuelle'],
    ['2026-02-28', 175.50, ''],
    ['2026-02-22', 290.00, 'Engagement missionnaire'],
    ['2026-02-15', 165.00, ''],
    ['2026-02-10', 240.00, ''],
    ['2026-02-01', 195.00, 'Dîme mensuelle'],
    ['2026-01-30', 155.00, ''],
    ['2026-01-25', 310.00, 'Campagne de prière'],
    ['2026-01-15', 190.00, ''],
    ['2026-01-10', 225.00, ''],
    ['2026-01-01', 200.00, 'Dîme mensuelle'],
];

$stmt = $pdo->prepare('INSERT INTO tithes (member_id, amount, tithe_date, comment, recorded_by) VALUES (?, ?, ?, ?, 1)');
foreach ($tithes as $tithe) {
    $memberId = $memberIds[array_rand($memberIds)];
    $stmt->execute([$memberId, $tithe[1], $tithe[0], $tithe[2]]);
}

// Insérer les offrandes
echo "🙏 Création de 12 offrandes...\n";
$offerings = [
    ['culte', 450.00, '2026-03-15', 'Culte du dimanche'],
    ['evenement', 600.00, '2026-03-14', 'Pique-nique familial'],
    ['mission', 800.00, '2026-03-10', 'Soutien mission à Madagascar'],
    ['culte', 380.00, '2026-03-08', 'Culte du dimanche'],
    ['autre', 250.00, '2026-03-05', 'Don spécial construction'],
    ['culte', 420.00, '2026-03-01', 'Culte du dimanche'],
    ['mission', 750.00, '2026-02-28', 'Parrainage enfant orphelin'],
    ['evenement', 550.00, '2026-02-20', 'Concert de bénéfice'],
    ['culte', 390.00, '2026-02-15', 'Culte du dimanche'],
    ['autre', 300.00, '2026-02-10', 'Fonds d\'aide social'],
    ['culte', 410.00, '2026-02-01', 'Culte du dimanche'],
    ['mission', 900.00, '2026-01-25', 'Projet puits eau en Afrique'],
];

$stmt = $pdo->prepare('INSERT INTO offerings (type, amount, offering_date, description, recorded_by) VALUES (?, ?, ?, ?, 1)');
foreach ($offerings as $offering) {
    $stmt->execute($offering);
}

// Insérer les dépenses
echo "📋 Création de 11 dépenses...\n";
$expenses = [
    ['loyer', 1200.00, '2026-03-01', 'Loyer bâtiment église', 'en attente'],
    ['salaire', 800.00, '2026-03-01', 'Salaire pasteur', 'en attente'],
    ['electricite', 350.00, '2026-02-28', 'Facture électricité', 'en attente'],
    ['entretien', 420.00, '2026-02-25', 'Entretien chauffage', 'en attente'],
    ['communion', 120.00, '2026-02-20', 'Pain et vin communion', 'en attente'],
    ['materiel', 550.00, '2026-02-15', 'Chaises et tables', 'en attente'],
    ['loyer', 1200.00, '2026-02-01', 'Loyer bâtiment église', 'en attente'],
    ['salaire', 800.00, '2026-02-01', 'Salaire pasteur', 'en attente'],
    ['chauffage', 280.00, '2026-01-25', 'Chauffage janvier', 'en attente'],
    ['entretien', 180.00, '2026-01-20', 'Réparation portes', 'en attente'],
    ['livres', 95.00, '2026-01-15', 'Bibles et livres chrétiens', 'en attente'],
];

$stmt = $pdo->prepare('INSERT INTO expenses (category, amount, expense_date, description, status, approved_by) VALUES (?, ?, ?, ?, ?, 1)');
foreach ($expenses as $expense) {
    $stmt->execute($expense);
}

echo "\n✅ DONNÉES DE DÉMO INSÉRÉES AVEC SUCCÈS!\n";
echo "═══════════════════════════════════════════════════════\n";
echo "\n📊 RÉSUMÉ:\n";
echo "  • 8 membres créés\n";
echo "  • 15 dîmes enregistrées\n";
echo "  • 12 offrandes enregistrées\n";
echo "  • 12 dépenses enregistrées\n";
echo "\nAccédez à: http://localhost/maloty/\n";
echo "Identifiants:\n";
echo "  Email: admin@maloty.com\n";
echo "  Mot de passe: admin123\n";
?>
