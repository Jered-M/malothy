<?php
/**
 * Script de diagnostic PHP pour XAMPP
 */

echo "═══════════════════════════════════════════════════════════════════\n";
echo "DIAGNOSTIC PHP - MALOTY\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

// Version PHP
echo "📌 Version PHP\n";
echo "   PHP: " . phpversion() . "\n\n";

// Extensions chargées
echo "📌 Extensions PDO\n";
$extensions = get_loaded_extensions();
$pdoExtensions = array_filter($extensions, fn($ext) => stripos($ext, 'pdo') !== false);

if (empty($pdoExtensions)) {
    echo "   ❌ Aucune extension PDO détectée!\n";
} else {
    foreach ($pdoExtensions as $ext) {
        echo "   ✅ " . $ext . "\n";
    }
}

echo "\n";

// PDO MySQL spécifiquement
echo "📌 Pilotes PDO disponibles\n";
$drivers = PDO::getAvailableDrivers();
if (in_array('mysql', $drivers)) {
    echo "   ✅ mysql\n";
} else {
    echo "   ❌ mysql (NON DISPONIBLE)\n";
}

echo "\n";

// php.ini location
echo "📌 Fichier php.ini\n";
$phpiniPath = php_ini_loaded_file();
echo "   Chemin: " . $phpiniPath . "\n\n";

if ($phpiniPath) {
    echo "📌 Extensions à vérifier dans php.ini:\n";
    
    $content = file_get_contents($phpiniPath);
    
    $patterns = [
        'extension=php_pdo_mysql.dll' => 'pdo_mysql (Windows DLL)',
        'extension=pdo_mysql' => 'pdo_mysql',
        'extension=php_mysql.dll' => 'mysql (Windows DLL)',
        'extension=mysql' => 'mysql',
    ];
    
    foreach ($patterns as $pattern => $name) {
        if (stripos($content, $pattern) !== false) {
            $line = null;
            $lines = explode("\n", $content);
            foreach ($lines as $i => $l) {
                if (stripos($l, $pattern) !== false) {
                    $line = $i + 1;
                    $commented = strpos(trim($l), ';') === 0;
                    if ($commented) {
                        echo "   ⚠️  Ligne {$line}: " . trim($l) . " (COMMENTÉE - À DÉCOMMENTER)\n";
                    } else {
                        echo "   ✅ Ligne {$line}: " . trim($l) . " (ACTIVE)\n";
                    }
                    break;
                }
            }
        }
    }
}

echo "\n";

// Instructions XAMPP
echo "═══════════════════════════════════════════════════════════════════\n";
echo "INSTRUC TIONS POUR XAMPP\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

echo "🔧 Étapes pour activer PDO MySQL:\n\n";

echo "1️⃣  Ouvrez le fichier php.ini:\n";
echo "   Chemin: C:\\xampp\\php\\php.ini\n\n";

echo "2️⃣  Trouvez et décommenter la ligne:\n";
echo "   Cherchez: ;extension=pdo_mysql\n";
echo "   Changez en: extension=pdo_mysql\n";
echo "   (Supprimez le point-virgule au début)\n\n";

echo "3️⃣  Redémarrez Apache depuis XAMPP Control Panel\n\n";

echo "4️⃣  Vérifiez que le changement a fonctionné en visitant:\n";
echo "   http://localhost/phpmyadmin\n\n";

echo "5️⃣  Exécutez le script de diagnostic à nouveau pour confirmer.\n\n";

echo "═══════════════════════════════════════════════════════════════════\n";

?>
