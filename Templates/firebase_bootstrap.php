<?php
$firebaseConfigPath = __DIR__ . '/../Auth/firebase_config.php';

if (!file_exists($firebaseConfigPath)) {
    return;
}

$firebaseConfig = require $firebaseConfigPath;

if (!is_array($firebaseConfig)) {
    return;
}

$requiredKeys = ['apiKey', 'authDomain', 'projectId', 'storageBucket', 'messagingSenderId', 'appId'];

foreach ($requiredKeys as $requiredKey) {
    if (empty($firebaseConfig[$requiredKey])) {
        return;
    }
}
?>
<script>
window.__LUVSHOP_FIREBASE_CONFIG__ = <?= json_encode($firebaseConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
</script>
<script defer src="https://www.gstatic.com/firebasejs/10.12.5/firebase-app-compat.js"></script>
<script defer src="https://www.gstatic.com/firebasejs/10.12.5/firebase-auth-compat.js"></script>
<script defer src="../Assect/js/firebase-auth.js"></script>
