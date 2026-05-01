<?php
$file = 'frontend/public/js/pages.js';
$content = file_get_contents($file);

// On cherche le bloc MaishaPay qu'on a ajouté
$pattern = '/<div>\s*<h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2"><i class="fas fa-mobile-screen-button text-brand-500"><\/i> Passerelle MaishaPay \(Mobile Money\)<\/h3>.*?<\/div>\s*<hr class="border-slate-100">/s';

if (preg_match($pattern, $content)) {
    $content = preg_replace($pattern, '', $content);
    file_put_contents($file, $content);
    echo "MaishaPay UI removed successfully\n";
} else {
    echo "MaishaPay UI not found or pattern mismatch\n";
}
