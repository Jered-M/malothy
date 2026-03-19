<?php
if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MALOTY - <?php echo $title ?? 'Gestion'; ?></title>
    
    <!-- Scripts & Styles -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/frontend/public/css/style.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0f7ff', 100: '#e0effe', 200: '#b9dffe', 300: '#7cc2fd', 
                            400: '#36a4f9', 500: '#0c87eb', 600: '#0069c7', 700: '#0054a1', 
                            800: '#034885', 900: '#083c6f', 950: '#052649'
                        },
                    },
                    borderRadius: {
                        '3xl': '1.5rem', '4xl': '2rem', '5xl': '2.5rem',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 text-slate-900 font-sans">
