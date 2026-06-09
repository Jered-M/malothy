<?php
require_once __DIR__ . '/common_head.php';
?>
<!-- Unified SPA Wrapper (Removed PHP Sidebar to avoid duplicates) -->
<div id="app">
    <?php if (isset($content)) echo $content; ?>
</div>

<script src="/frontend/public/js/api.js?v=20260609-2"></script>
<script src="/frontend/public/js/components.js?v=20260609-2"></script>
<script src="/frontend/public/js/pages.js?v=20260609-2"></script>
<script src="/frontend/public/js/app.js?v=20260609-2"></script>
</body>
</html>
