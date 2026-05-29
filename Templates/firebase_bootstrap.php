<?php
require_once __DIR__ . '/../Auth/firebase_config_loader.php';

$firebaseConfig = luvshop_firebase_config();

if (!luvshop_firebase_config_is_complete($firebaseConfig)) {
    return;
}
?>
<script>
window.__LUVSHOP_FIREBASE_CONFIG__ = <?= json_encode($firebaseConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
</script>
<script defer src="https://www.gstatic.com/firebasejs/10.12.5/firebase-app-compat.js"></script>
<script defer src="https://www.gstatic.com/firebasejs/10.12.5/firebase-auth-compat.js"></script>
<script defer src="../Assect/js/firebase-auth.js"></script>
