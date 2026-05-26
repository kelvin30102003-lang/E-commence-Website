<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_layout.php';

admin_start_session();
$admin = admin_require_auth('adminLogin.php');

$pdo = admin_db();
admin_ensure_tables($pdo);

$csrfToken = admin_bootstrap_csrf_token();
$flash = null;

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
    try {
        if (!admin_validate_csrf_token((string)($_POST['csrf_token'] ?? ''))) {
            throw new InvalidArgumentException('Invalid request token. Refresh and try again.');
        }

        $action = trim((string)($_POST['action'] ?? ''));
        if ($action === 'update_profile') {
            $admin = admin_profile_page_update($pdo, $admin);
            admin_profile_page_refresh_session($admin);
            admin_log_activity($pdo, (int)$admin['id'], 'admin.profile_update', 'Admin updated profile details.');
            $flash = ['type' => 'success', 'message' => 'Profile updated successfully.'];
        } elseif ($action === 'change_password') {
            admin_profile_page_change_password($pdo, $admin);
            admin_log_activity($pdo, (int)$admin['id'], 'admin.password_change', 'Admin changed password.');
            $flash = ['type' => 'success', 'message' => 'Password changed successfully.'];
        } else {
            throw new InvalidArgumentException('Unknown profile action.');
        }
    } catch (Throwable $exception) {
        $flash = ['type' => 'error', 'message' => $exception->getMessage()];
    }
}

$admin = admin_find_account_by_id($pdo, (int)$admin['id']) ?? $admin;
$adminName = trim((string)($admin['name'] ?? 'Admin'));
$adminEmail = trim((string)($admin['email'] ?? ''));
$initial = strtoupper(substr($adminName !== '' ? $adminName : $adminEmail, 0, 1));
if ($initial === '') {
    $initial = 'A';
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Admin Profile | LuvShop Admin</title>
    <?php admin_render_critical_css(); ?>
    <?php $adminCssHref = admin_css_href(); ?>
<?php if ($adminCssHref !== null): ?>
    <link href="<?= admin_html($adminCssHref) ?>" rel="stylesheet"/>
<?php endif; ?>
    <link href="<?= admin_html(admin_material_symbols_href()) ?>" rel="stylesheet"/>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .soft-shadow { box-shadow: 0 10px 30px -5px rgba(120, 85, 94, 0.08); }
    </style>
</head>
<body class="bg-surface text-on-surface">
<?php admin_render_sidebar($admin, ''); ?>

<main class="ml-64 min-h-screen bg-surface" id="app-main">
    <?php admin_render_header($admin, ['search_enabled' => false]); ?>

    <div class="px-6 py-8 max-w-[1280px] mx-auto">
        <section class="mb-6">
            <h1 class="text-3xl font-bold text-[#78555e]">My Profile</h1>
            <p class="text-slate-600">Update your account details and password.</p>
        </section>

        <?php if ($flash !== null): ?>
            <div class="mb-6 rounded-xl px-4 py-3 border <?= $flash['type'] === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700' ?>">
                <?= admin_html((string)$flash['message']) ?>
            </div>
        <?php endif; ?>

        <section class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <aside class="lg:col-span-4">
                <div class="bg-white rounded-2xl p-6 soft-shadow border border-slate-200 sticky top-24">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-full overflow-hidden border border-slate-200 bg-[#ffd1dc] flex items-center justify-center text-[#78555e] text-2xl font-bold">
                            <?= admin_html($initial) ?>
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900 truncate"><?= admin_html($adminName !== '' ? $adminName : 'Admin') ?></p>
                            <p class="text-sm text-slate-500 truncate"><?= admin_html($adminEmail) ?></p>
                        </div>
                    </div>
                    <div class="mt-5 pt-5 border-t border-slate-200 text-sm text-slate-600">
                        <p>Account managed inside LuvShop Admin.</p>
                    </div>
                    <a class="mt-5 flex items-center justify-center gap-2 rounded-full bg-[#78555e] px-4 py-2 text-sm font-semibold text-white hover:opacity-90" href="adminLogout.php">
                        <span class="material-symbols-outlined text-[18px]">logout</span>
                        Logout
                    </a>
                </div>
            </aside>

            <div class="lg:col-span-8 space-y-6">
                <section class="bg-white rounded-2xl p-6 soft-shadow border border-slate-200">
                    <h2 class="text-2xl font-semibold text-[#78555e] mb-5">Account Details</h2>
                    <form class="space-y-4" method="post">
                        <input type="hidden" name="csrf_token" value="<?= admin_html($csrfToken) ?>"/>
                        <input type="hidden" name="action" value="update_profile"/>
                        <div>
                            <label class="block text-sm font-semibold mb-1" for="admin-profile-name">Name</label>
                            <input id="admin-profile-name" name="name" maxlength="150" type="text" value="<?= admin_html($adminName) ?>" required/>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1" for="admin-profile-email">Email</label>
                            <input id="admin-profile-email" name="email" maxlength="255" type="email" value="<?= admin_html($adminEmail) ?>" required/>
                        </div>
                        <button class="rounded-full bg-[#78555e] px-6 py-2 font-semibold text-white hover:opacity-90" type="submit">Save Changes</button>
                    </form>
                </section>

                <section class="bg-white rounded-2xl p-6 soft-shadow border border-slate-200">
                    <h2 class="text-2xl font-semibold text-[#78555e] mb-4">Change Password</h2>
                    <p class="mb-4 text-sm text-slate-600">Current password is required to set a new password.</p>
                    <form class="space-y-4" method="post">
                        <input type="hidden" name="csrf_token" value="<?= admin_html($csrfToken) ?>"/>
                        <input type="hidden" name="action" value="change_password"/>
                        <div>
                            <label class="block text-sm font-semibold mb-1" for="admin-current-password">Current Password</label>
                            <input id="admin-current-password" name="current_password" minlength="8" type="password" required/>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1" for="admin-new-password">New Password</label>
                            <input id="admin-new-password" name="new_password" minlength="8" type="password" required/>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1" for="admin-confirm-password">Confirm New Password</label>
                            <input id="admin-confirm-password" name="confirm_password" minlength="8" type="password" required/>
                        </div>
                        <button class="rounded-full bg-[#78555e] px-6 py-2 font-semibold text-white hover:opacity-90" type="submit">Change Password</button>
                    </form>
                </section>
            </div>
        </section>
    </div>
</main>
</body>
</html>

<?php

function admin_profile_page_update(PDO $pdo, array $admin): array
{
    $adminId = (int)($admin['id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));

    if ($adminId <= 0) {
        throw new InvalidArgumentException('Admin session is invalid.');
    }
    if ($name === '') {
        throw new InvalidArgumentException('Name is required.');
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Valid email is required.');
    }

    $duplicate = admin_find_account_by_email($pdo, $email);
    if (is_array($duplicate) && (int)$duplicate['id'] !== $adminId) {
        throw new InvalidArgumentException('This email is already used by another admin.');
    }

    $statement = $pdo->prepare(
        'UPDATE admin_accounts
         SET name = :name, email = :email, updated_at = NOW()
         WHERE id = :id'
    );
    $statement->execute([
        ':name' => $name,
        ':email' => $email,
        ':id' => $adminId,
    ]);

    $updatedAdmin = admin_find_account_by_id($pdo, $adminId);
    if (!is_array($updatedAdmin)) {
        throw new RuntimeException('Updated admin account could not be loaded.');
    }

    return $updatedAdmin;
}

function admin_profile_page_change_password(PDO $pdo, array $admin): void
{
    $adminId = (int)($admin['id'] ?? 0);
    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($adminId <= 0) {
        throw new InvalidArgumentException('Admin session is invalid.');
    }
    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        throw new InvalidArgumentException('All password fields are required.');
    }
    if (strlen($newPassword) < 8) {
        throw new InvalidArgumentException('New password must be at least 8 characters.');
    }
    if ($newPassword !== $confirmPassword) {
        throw new InvalidArgumentException('Password confirmation does not match.');
    }

    $currentAdmin = admin_find_account_by_id($pdo, $adminId);
    if (!is_array($currentAdmin) || !password_verify($currentPassword, (string)$currentAdmin['password_hash'])) {
        throw new InvalidArgumentException('Current password is incorrect.');
    }

    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    if ($passwordHash === false) {
        throw new RuntimeException('Failed to secure password.');
    }

    $statement = $pdo->prepare(
        'UPDATE admin_accounts
         SET password_hash = :password_hash, updated_at = NOW()
         WHERE id = :id'
    );
    $statement->execute([
        ':password_hash' => $passwordHash,
        ':id' => $adminId,
    ]);
}

function admin_profile_page_refresh_session(array $admin): void
{
    admin_start_session();
    $_SESSION[ADMIN_SESSION_KEY] = [
        'id' => (int)$admin['id'],
        'name' => (string)$admin['name'],
        'email' => (string)$admin['email'],
        'logged_in_at' => (string)($_SESSION[ADMIN_SESSION_KEY]['logged_in_at'] ?? gmdate('c')),
    ];
}
