<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin_auth.php';

admin_start_session();

$pdo = null;
$databaseError = null;
$errorMessage = null;
$successMessage = null;

try {
    $pdo = admin_db();
    admin_ensure_tables($pdo);
} catch (Throwable $exception) {
    $databaseError = $exception->getMessage();
}

if ($databaseError === null && admin_current() !== null) {
    header('Location: adminDashboard.php');
    exit;
}

$hasAdminAccount = true;
if ($pdo instanceof PDO) {
    $hasAdminAccount = admin_count_accounts($pdo) > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $databaseError === null && $pdo instanceof PDO) {
    $csrfToken = (string)($_POST['csrf_token'] ?? '');

    if (!admin_validate_csrf_token($csrfToken)) {
        $errorMessage = 'Invalid request token. Refresh and try again.';
    } elseif ($hasAdminAccount) {
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $errorMessage = 'Email and password are required.';
        } else {
            $admin = admin_find_account_by_email($pdo, $email);

            if ($admin === null || !password_verify($password, (string)$admin['password_hash'])) {
                $errorMessage = 'Invalid email or password.';
                admin_log_activity($pdo, null, 'admin.login_failed', 'Failed login for email: ' . strtolower($email));
            } elseif ((int)$admin['is_active'] !== 1) {
                $errorMessage = 'This admin account is inactive.';
            } else {
                admin_record_login($pdo, (int)$admin['id']);
                admin_login($admin);
                admin_log_activity($pdo, (int)$admin['id'], 'admin.login_success', 'Admin logged in successfully.');

                header('Location: adminDashboard.php');
                exit;
            }
        }
    } else {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
            $errorMessage = 'All fields are required.';
        } elseif ($password !== $confirmPassword) {
            $errorMessage = 'Password confirmation does not match.';
        } else {
            try {
                $admin = admin_create_account($pdo, $name, $email, $password);
                admin_record_login($pdo, (int)$admin['id']);
                admin_login($admin);
                admin_log_activity($pdo, (int)$admin['id'], 'admin.bootstrap_created', 'First admin account created.');
                $successMessage = 'First admin account created. Redirecting...';

                header('Refresh: 1; url=adminDashboard.php');
            } catch (Throwable $exception) {
                $errorMessage = $exception->getMessage();
            }
        }
    }
}

$csrfToken = admin_bootstrap_csrf_token();
$pageTitle = $hasAdminAccount ? 'Admin Login' : 'Create First Admin';
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= admin_html($pageTitle) ?> | LuvShop</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
    <main class="min-h-screen flex items-center justify-center p-6">
        <section class="w-full max-w-md bg-white border border-slate-200 rounded-2xl shadow-sm p-8 space-y-6">
            <div class="space-y-1 text-center">
                <h1 class="text-2xl font-semibold text-slate-900"><?= admin_html($pageTitle) ?></h1>
                <?php if ($hasAdminAccount): ?>
                    <p class="text-sm text-slate-500">Sign in with your admin account.</p>
                <?php else: ?>
                    <p class="text-sm text-slate-500">No admin exists yet. Create the first one now.</p>
                <?php endif; ?>
            </div>

            <?php if ($databaseError !== null): ?>
                <div class="rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm px-4 py-3">
                    Database error: <?= admin_html($databaseError) ?>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage !== null): ?>
                <div class="rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm px-4 py-3">
                    <?= admin_html($errorMessage) ?>
                </div>
            <?php endif; ?>

            <?php if ($successMessage !== null): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 text-sm px-4 py-3">
                    <?= admin_html($successMessage) ?>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= admin_html($csrfToken) ?>" />

                <?php if (!$hasAdminAccount): ?>
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-slate-700" for="name">Name</label>
                        <input id="name" name="name" type="text" required class="w-full rounded-xl border-slate-300 focus:border-slate-500 focus:ring-slate-500" value="<?= admin_html((string)($_POST['name'] ?? '')) ?>" />
                    </div>
                <?php endif; ?>

                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700" for="email">Email</label>
                    <input id="email" name="email" type="email" required class="w-full rounded-xl border-slate-300 focus:border-slate-500 focus:ring-slate-500" value="<?= admin_html((string)($_POST['email'] ?? '')) ?>" />
                </div>

                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700" for="password">Password</label>
                    <input id="password" name="password" type="password" minlength="8" required class="w-full rounded-xl border-slate-300 focus:border-slate-500 focus:ring-slate-500" />
                </div>

                <?php if (!$hasAdminAccount): ?>
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-slate-700" for="confirm_password">Confirm Password</label>
                        <input id="confirm_password" name="confirm_password" type="password" minlength="8" required class="w-full rounded-xl border-slate-300 focus:border-slate-500 focus:ring-slate-500" />
                    </div>
                <?php endif; ?>

                <button type="submit" class="w-full rounded-xl bg-slate-900 text-white py-3 font-medium hover:bg-slate-800 transition-colors">
                    <?= $hasAdminAccount ? 'Sign In' : 'Create Admin Account' ?>
                </button>
            </form>

            <div class="text-center text-sm text-slate-500">
                <a href="../Users/Home.php" class="hover:underline">Back to store</a>
            </div>
        </section>
    </main>
</body>
</html>
