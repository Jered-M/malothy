<?php
require_once __DIR__ . '/common_head.php';
?>
<!-- Unified SPA Wrapper (Removed PHP Sidebar to avoid duplicates) -->
<div id="app">
    <?php if (isset($content)) echo $content; ?>
</div>

<script src="/frontend/public/js/api.js"></script>
<script src="/frontend/public/js/components.js"></script>
<script src="/frontend/public/js/pages.js"></script>
<script src="/frontend/public/js/app.js"></script>
</body>
</html>
