<?php
define('PROJECT_ROOT', dirname(__DIR__, 2));
require_once PROJECT_ROOT . '/backend/config/api-config.php';
$user = checkRole(['admin']);
$title = "Paramètres Système";
ob_start();
?>
<div id="app" data-role="admin"></div>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        if (window.app) window.app.navigate('settings');
    });
</script>
<?php
$content = ob_get_clean();
require_once PROJECT_ROOT . '/frontend/layouts/admin_layout.php';
