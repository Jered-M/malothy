<?php
define('PROJECT_ROOT', dirname(__DIR__, 2));
require_once PROJECT_ROOT . '/backend/config/api-config.php';

// Security: Check role
$user = checkRole(['admin']);

$title = "Dashboard Admin";
ob_start();
?>
<!-- The actual content will be rendered by JS (app.js) in id="app" -->
<div id="app" data-role="administrateur">
    <div class="flex items-center justify-center p-20">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-500"></div>
    </div>
</div>

<script>
    window.addEventListener('DOMContentLoaded', () => {
        if (window.app) {
            window.app.navigate('dashboard');
        }
    });
</script>
<?php
$content = ob_get_clean();
require_once PROJECT_ROOT . '/frontend/layouts/admin_layout.php';
