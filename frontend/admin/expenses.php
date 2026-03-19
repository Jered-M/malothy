<?php
define('PROJECT_ROOT', dirname(__DIR__, 2));
require_once PROJECT_ROOT . '/backend/config/api-config.php';
$user = checkRole(['admin']);
$title = "Gestion des Dépenses";
ob_start();
?>
<div id="app" data-role="administrateur"></div>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        if (window.app) window.app.navigate('expenses');
    });
</script>
<?php
$content = ob_get_clean();
require_once PROJECT_ROOT . '/frontend/layouts/admin_layout.php';
