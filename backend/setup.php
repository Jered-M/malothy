<?php
/**
 * MALOTY - Script d'installation
 * Exécutez ce script une seule fois pour initialiser l'application
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

// Constantes
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', __DIR__);
}

// Configuration
$dbHost = '127.0.0.1';
$dbUser = 'root';
$dbPassword = '';
$dbName = 'eglise_m';
$dbPort = 3306;

echo "═══════════════════════════════════════════════════════════════════\n";
echo "MALOTY - Script d'Installation\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

// Étape 1: Connexion MySQL
echo "1️⃣  Connexion à MySQL...\n";
try {
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort}",
        $dbUser,
        $dbPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "✅ Connexion MySQL réussie\n\n";
} catch (PDOException $e) {
    echo "❌ Erreur de connexion: " . $e->getMessage() . "\n";
    die("Vérifiez vos paramètres MySQL et réessayez.\n");
}

// Étape 2: Créer la base de données
echo "2️⃣  Création de la base de données...\n";
try {
    $pdo->exec("DROP DATABASE IF EXISTS `{$dbName}`");
    $pdo->exec("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbName}`");
    echo "✅ Base de données créée\n\n";
} catch (PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    die();
}

// Étape 3: Importer la structure SQL
echo "3️⃣  Importation de la structure de base de données...\n";
$sqlFile = PROJECT_ROOT . '/database.sql';

if (!file_exists($sqlFile)) {
    die("❌ Fichier database.sql non trouvé!\n");
}

$sqlContent = file_get_contents($sqlFile);

// Nettoyer le contenu SQL
$sqlContent = preg_replace('/DROP DATABASE IF EXISTS.*?;/is', '', $sqlContent);
$sqlContent = preg_replace('/CREATE DATABASE.*?;/is', '', $sqlContent);
$sqlContent = preg_replace('/USE `?.*?`?;/is', '', $sqlContent);
// Enlever les commentaires SQL (-- jusqu'à la fin de ligne)
$sqlContent = preg_replace('/--.*$/m', '', $sqlContent);
// Enlever les commentaires multi-lignes /* */
$sqlContent = preg_replace('/\/\*.*?\*\//s', '', $sqlContent);

// Diviser par points-virgules
$statements = array_filter(
    array_map('trim', explode(';', $sqlContent)),
    fn($s) => !empty($s) && strlen($s) > 5
);

echo "📊 Total de statements SQL à exécuter: " . count($statements) . "\n";

$successCount = 0;
foreach ($statements as $index => $statement) {
    try {
        $pdo->exec($statement);
        $successCount++;
    } catch (PDOException $e) {
        echo "❌ Statement #" . ($index + 1) . " ERREUR:\n";
        echo "   SQL: " . substr($statement, 0, 100) . "...\n";
        echo "   Erreur: " . $e->getMessage() . "\n";
    }
}

echo "✅ Structure importée ($successCount statements exécutés)\n\n";

// Étape 4: Générer les mots de passe hashés
echo "4️⃣  Génération des mots de passe hashés...\n";
$passwords = [
    'admin' => 'admin123',
    'trésorier' => 'treas123',
    'secrétaire' => 'sec123'
];

foreach ($passwords as $role => $password) {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    echo "   Role: {$role}, MDP: {$password}, Hash: " . substr($hash, 0, 20) . "...\n";
}
echo "\n";

// Étape 5: Mettre à jour les utilisateurs de démo
echo "5️⃣  Mise à jour des utilisateurs de démo...\n";
$users = [
    ['email' => 'admin@maloty.com', 'password' => 'admin123', 'role' => 'admin', 'name' => 'Administrateur'],
    ['email' => 'treasure@maloty.com', 'password' => 'treas123', 'role' => 'trésorier', 'name' => 'Trésorier'],
    ['email' => 'secretary@maloty.com', 'password' => 'sec123', 'role' => 'secrétaire', 'name' => 'Secrétaire'],
];

try {
    // Supprimer les anciens utilisateurs
    $pdo->exec("DELETE FROM users");
    
    // Insérer les nouveaux
    foreach ($users as $user) {
        $hash = password_hash($user['password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare(
            "INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, 'actif')"
        );
        $stmt->execute([$user['name'], $user['email'], $hash, $user['role']]);
        echo "   ✅ {$user['name']} ({$user['email']})\n";
    }
} catch (PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    die();
}

echo "\n";

// Étape 6: Créer les répertoires uploads
echo "6️⃣  Création des répertoires d'upload...\n";
$uploadDirs = [
    PROJECT_ROOT . '/uploads',
    PROJECT_ROOT . '/uploads/members',
    PROJECT_ROOT . '/uploads/expenses',
];

foreach ($uploadDirs as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "   ✅ {$dir}\n";
        } else {
            echo "   ⚠️  Impossible de créer {$dir}\n";
        }
    } else {
        echo "   ℹ️  {$dir} existe déjà\n";
    }
}

echo "\n";

// Résumé
echo "═══════════════════════════════════════════════════════════════════\n";
echo "✅ INSTALLATION TERMINÉE AVEC SUCCÈS!\n";
echo "═══════════════════════════════════════════════════════════════════\n";

echo "\n🔑 Identifiants de connexion:\n";
echo "┌─────────────────────────────────────────────────────────────────┐\n";
echo "│ ADMINISTRATEUR                                                  │\n";
echo "│ Email    : admin@maloty.com                                     │\n";
echo "│ Password : admin123                                             │\n";
echo "├─────────────────────────────────────────────────────────────────┤\n";
echo "│ TRÉSORIER                                                       │\n";
echo "│ Email    : treasure@maloty.com                                  │\n";
echo "│ Password : treas123                                             │\n";
echo "├─────────────────────────────────────────────────────────────────┤\n";
echo "│ SECRÉTAIRE                                                      │\n";
echo "│ Email    : secretary@maloty.com                                 │\n";
echo "│ Password : sec123                                               │\n";
echo "└─────────────────────────────────────────────────────────────────┘\n";

echo "\n📝 Prochaines étapes:\n";
echo "   1. Accédez à http://localhost/index.php\n";
echo "   2. Connectez-vous avec l'un des identifiants ci-dessus\n";
echo "   3. Changez les mots de passe dans les paramètres\n";
echo "   4. Supprimez ce fichier setup.php\n\n";

echo "📚 Documentation:\n";
echo "   - Consultez README.md pour les instructions détaillées\n";
echo "   - Consultez INSTALLATION.md pour l'installation avancée\n\n";

?>
