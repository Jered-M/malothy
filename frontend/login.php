<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__));
}
require_once PROJECT_ROOT . '/backend/config/api-config.php';

// La redirection est gérée par le JS (app.js) pour synchroniser localStorage avec la session PHP

$title = "Connexion";
require_once __DIR__ . '/layouts/common_head.php';
?>
<div id="app"></div>

<script src="/frontend/public/js/api.js"></script>
<script src="/frontend/public/js/components.js"></script>
<script src="/frontend/public/js/pages.js"></script>
<script src="/frontend/public/js/app.js"></script>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        if (window.app) window.app.navigate('login');
    });
</script>
</body>
</html>
