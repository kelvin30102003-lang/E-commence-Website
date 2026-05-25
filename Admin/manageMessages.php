<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_layout.php';

admin_start_session();
$admin = admin_require_auth('adminLogin.php');

$pdo = admin_db();
admin_ensure_tables($pdo);
ensureAdminContactMessageTables($pdo);

$csrfToken = admin_bootstrap_csrf_token();

$queryFilters = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? '')),
];
if ($queryFilters['status'] !== '' && !in_array($queryFilters['status'], ['new', 'read', 'resolved'], true)) {
    $queryFilters['status'] = '';
}

handleAdminMessagesPostActions($pdo, $admin);

$flash = pullAdminMessagesFlash();
$contactStats = fetchContactMessageStats($pdo);

$contactMessages = fetchContactMessages($pdo, $queryFilters);
$selectedContactId = max(0, (int)($_GET['contact_id'] ?? 0));
if ($selectedContactId <= 0 && count($contactMessages) > 0) {
    $selectedContactId = (int)$contactMessages[0]['id'];
}
$selectedContact = null;
if ($selectedContactId > 0) {
    $selectedContact = fetchContactMessageById($pdo, $selectedContactId);
    if ($selectedContact !== null && (string)($selectedContact['status'] ?? '') === 'new') {
        updateContactMessageStatus($pdo, $selectedContactId, 'read');
        $selectedContact['status'] = 'read';
        foreach ($contactMessages as &$contactMessage) {
            if ((int)($contactMessage['id'] ?? 0) === $selectedContactId) {
                $contactMessage['status'] = 'read';
                break;
            }
        }
        unset($contactMessage);
        $contactStats = fetchContactMessageStats($pdo);
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Messages | LuvShop Admin</title>
    <?php admin_render_critical_css(); ?>
    <?php $adminCssHref = admin_css_href(); ?>
<?php if ($adminCssHref !== null): ?>
    <link href="<?= admin_html($adminCssHref) ?>" rel="stylesheet"/>
<?php endif; ?>
    <link href="<?= admin_html(admin_material_symbols_href()) ?>" rel="stylesheet"/>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .soft-shadow {
            box-shadow: 0 10px 30px -5px rgba(120, 85, 94, 0.08);
        }
        .message-preview {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-surface text-on-surface">
<?php admin_render_sidebar($admin, 'messages'); ?>

<main class="ml-64 min-h-screen" id="app-main">
    <?php
    admin_render_header($admin, [
        'search_action' => 'manageMessages.php',
        'search_method' => 'get',
        'search_name' => 'q',
        'search_value' => $queryFilters['q'],
        'search_placeholder' => 'Search contact messages...',
        'search_hidden' => [
            'status' => $queryFilters['status'],
        ],
    ]);
    ?>

    <div class="p-6 space-y-6 max-w-[1440px] mx-auto">
        <?php if ($flash !== null): ?>
            <div class="rounded-lg px-4 py-3 border <?= $flash['type'] === 'error' ? 'bg-error-container border-red-200 text-on-error-container' : 'bg-secondary-container border-green-200 text-on-secondary-container' ?>">
                <?= admin_html((string)$flash['message']) ?>
            </div>
        <?php endif; ?>

        <section class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold text-primary">Messages</h2>
                <p class="text-on-surface-variant mt-1">Review Contact Us submissions.</p>
            </div>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-surface-container-lowest p-4 rounded-lg soft-shadow border border-surface-container">
                <span class="text-xs uppercase tracking-wider text-on-surface-variant">New Contact</span>
                <p class="text-2xl font-bold mt-1"><?= number_format((int)$contactStats['new']) ?></p>
            </div>
            <div class="bg-surface-container-lowest p-4 rounded-lg soft-shadow border border-surface-container">
                <span class="text-xs uppercase tracking-wider text-on-surface-variant">Resolved Contact</span>
                <p class="text-2xl font-bold mt-1"><?= number_format((int)$contactStats['resolved']) ?></p>
            </div>
        </section>

        <section class="bg-surface-container-lowest rounded-lg soft-shadow border border-surface-container p-4">
            <form class="flex flex-wrap items-center gap-3" method="get">
                <input class="flex-1 min-w-[260px] rounded-full bg-surface border-none" name="q" placeholder="Search by name, email, subject, or message..." type="text" value="<?= admin_html($queryFilters['q']) ?>"/>
                <select class="rounded-lg bg-surface border-none min-w-[170px]" name="status">
                    <option value="" <?= $queryFilters['status'] === '' ? 'selected' : '' ?>>All Messages</option>
                    <option value="new" <?= $queryFilters['status'] === 'new' ? 'selected' : '' ?>>New</option>
                    <option value="read" <?= $queryFilters['status'] === 'read' ? 'selected' : '' ?>>Read</option>
                    <option value="resolved" <?= $queryFilters['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                </select>
                <button class="px-4 py-2 rounded-lg bg-primary text-on-primary font-semibold" type="submit">Apply</button>
                <a class="px-4 py-2 rounded-lg border border-outline-variant hover:bg-surface-container-low" href="manageMessages.php">Clear</a>
            </form>
        </section>

        <section class="grid grid-cols-1 xl:grid-cols-[420px_1fr] gap-6">
            <div class="bg-surface-container-lowest rounded-lg soft-shadow border border-surface-container overflow-hidden">
                <div class="px-5 py-4 border-b border-surface-container bg-surface-container-low">
                    <h3 class="font-semibold text-primary">Inbox</h3>
                </div>
                <div class="divide-y divide-surface-container max-h-[680px] overflow-y-auto">
                    <?php if (count($contactMessages) === 0): ?>
                        <p class="p-5 text-on-surface-variant">No contact messages found.</p>
                    <?php endif; ?>
                    <?php foreach ($contactMessages as $message): ?>
                        <?php
                        $messageId = (int)$message['id'];
                        $isSelected = $selectedContact !== null && (int)$selectedContact['id'] === $messageId;
                        ?>
                        <a class="block p-4 transition-colors <?= $isSelected ? 'bg-primary-container/45' : 'hover:bg-surface-container-low/70' ?>" href="manageMessages.php<?= admin_html(buildAdminMessagesQuery(array_merge($queryFilters, ['contact_id' => $messageId]))) ?>">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-primary truncate"><?= admin_html((string)$message['subject']) ?></p>
                                    <p class="text-sm text-on-surface-variant truncate"><?= admin_html((string)$message['full_name']) ?> - <?= admin_html((string)$message['email']) ?></p>
                                </div>
                                <span class="shrink-0 px-2 py-1 rounded-full text-xs font-semibold <?= adminMessageStatusClass((string)$message['status']) ?>">
                                    <?= admin_html((string)$message['status']) ?>
                                </span>
                            </div>
                            <p class="message-preview text-sm text-on-surface-variant mt-2"><?= admin_html((string)$message['message']) ?></p>
                            <p class="text-xs text-outline mt-2"><?= admin_html(formatAdminMessagesDate((string)$message['created_at'])) ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bg-surface-container-lowest rounded-lg soft-shadow border border-surface-container min-h-[520px]">
                <?php if ($selectedContact === null): ?>
                    <div class="h-full min-h-[520px] flex items-center justify-center text-on-surface-variant">
                        Select a contact message to view it.
                    </div>
                <?php else: ?>
                    <?php
                    $replySubject = 'Re: ' . (string)$selectedContact['subject'];
                    $mailtoHref = 'mailto:' . rawurlencode((string)$selectedContact['email']) . '?subject=' . rawurlencode($replySubject);
                    ?>
                    <div class="p-6 border-b border-surface-container flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-2xl font-bold text-primary break-words"><?= admin_html((string)$selectedContact['subject']) ?></h3>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold <?= adminMessageStatusClass((string)$selectedContact['status']) ?>">
                                    <?= admin_html((string)$selectedContact['status']) ?>
                                </span>
                            </div>
                            <p class="text-sm text-on-surface-variant mt-2">
                                <?= admin_html((string)$selectedContact['full_name']) ?> -
                                <a class="text-primary hover:underline" href="<?= admin_html($mailtoHref) ?>"><?= admin_html((string)$selectedContact['email']) ?></a>
                            </p>
                            <p class="text-xs text-outline mt-1"><?= admin_html(formatAdminMessagesDate((string)$selectedContact['created_at'])) ?></p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a class="px-4 py-2 rounded-lg bg-secondary-container text-on-secondary-container font-semibold" href="<?= admin_html($mailtoHref) ?>">
                                Reply by Email
                            </a>
                            <form class="flex gap-2" method="post">
                                <input name="csrf_token" type="hidden" value="<?= admin_html($csrfToken) ?>"/>
                                <input name="action" type="hidden" value="update_contact_status"/>
                                <input name="message_id" type="hidden" value="<?= (int)$selectedContact['id'] ?>"/>
                                <input name="return_query" type="hidden" value="<?= admin_html(buildAdminMessagesQuery(array_merge($queryFilters, ['contact_id' => (int)$selectedContact['id']]))) ?>"/>
                                <select class="rounded-lg bg-surface border-outline-variant" name="status">
                                    <?php foreach (['new', 'read', 'resolved'] as $statusOption): ?>
                                        <option value="<?= $statusOption ?>" <?= (string)$selectedContact['status'] === $statusOption ? 'selected' : '' ?>><?= ucfirst($statusOption) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="px-4 py-2 rounded-lg bg-primary text-on-primary font-semibold" type="submit">Save</button>
                            </form>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="rounded-lg border border-surface-container bg-surface p-5 whitespace-pre-wrap leading-7"><?= admin_html((string)$selectedContact['message']) ?></div>
                        <?php if (trim((string)$selectedContact['ip_address']) !== '' || trim((string)$selectedContact['user_agent']) !== ''): ?>
                            <div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-4 text-sm">
                                <div class="rounded-lg bg-surface-container-low p-4">
                                    <span class="block text-xs text-on-surface-variant uppercase tracking-wider mb-1">IP Address</span>
                                    <?= admin_html((string)($selectedContact['ip_address'] ?? '')) ?>
                                </div>
                                <div class="rounded-lg bg-surface-container-low p-4 min-w-0">
                                    <span class="block text-xs text-on-surface-variant uppercase tracking-wider mb-1">Browser</span>
                                    <span class="break-words"><?= admin_html((string)($selectedContact['user_agent'] ?? '')) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>
</body>
</html>

<?php

function ensureAdminContactMessageTables(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS contact_messages (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(150) NOT NULL,
            email VARCHAR(255) NOT NULL,
            subject VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('new','read','resolved') NOT NULL DEFAULT 'new',
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_contact_messages_status (status),
            KEY idx_contact_messages_created_at (created_at),
            KEY idx_contact_messages_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function handleAdminMessagesPostActions(PDO $pdo, array $currentAdmin): void
{
    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
        return;
    }

    if (!admin_validate_csrf_token((string)($_POST['csrf_token'] ?? ''))) {
        setAdminMessagesFlash('error', 'Invalid form token. Please refresh and try again.');
        redirectAdminMessages((string)($_POST['return_query'] ?? ''));
    }

    $action = trim((string)($_POST['action'] ?? ''));

    try {
        if ($action === 'update_contact_status') {
            $messageId = max(0, (int)($_POST['message_id'] ?? 0));
            $status = trim((string)($_POST['status'] ?? ''));
            updateContactMessageStatus($pdo, $messageId, $status);
            admin_log_activity($pdo, (int)$currentAdmin['id'], 'contact_message.status', 'Updated contact message #' . $messageId . ' to ' . $status);
            setAdminMessagesFlash('success', 'Contact message status updated.');
            redirectAdminMessages((string)($_POST['return_query'] ?? ''));
        }

        setAdminMessagesFlash('error', 'Unknown message action.');
        redirectAdminMessages((string)($_POST['return_query'] ?? ''));
    } catch (Throwable $exception) {
        setAdminMessagesFlash('error', $exception->getMessage());
        redirectAdminMessages((string)($_POST['return_query'] ?? ''));
    }
}

function fetchContactMessages(PDO $pdo, array $filters): array
{
    $where = ['1=1'];
    $params = [];
    $status = trim((string)($filters['status'] ?? ''));
    if (in_array($status, ['new', 'read', 'resolved'], true)) {
        $where[] = 'status = :status';
        $params[':status'] = $status;
    }

    $query = trim((string)($filters['q'] ?? ''));
    if ($query !== '') {
        $where[] = '(full_name LIKE :q_full_name OR email LIKE :q_email OR subject LIKE :q_subject OR message LIKE :q_message)';
        $queryLike = '%' . $query . '%';
        $params[':q_full_name'] = $queryLike;
        $params[':q_email'] = $queryLike;
        $params[':q_subject'] = $queryLike;
        $params[':q_message'] = $queryLike;
    }

    $sql = 'SELECT id, full_name, email, subject, message, status, created_at
            FROM contact_messages
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY created_at DESC, id DESC
            LIMIT 80';
    $statement = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $statement->bindValue($key, $value);
    }
    $statement->execute();
    $rows = $statement->fetchAll();
    return is_array($rows) ? $rows : [];
}

function fetchContactMessageById(PDO $pdo, int $messageId): ?array
{
    if ($messageId <= 0) {
        return null;
    }

    $statement = $pdo->prepare('SELECT * FROM contact_messages WHERE id = :id LIMIT 1');
    $statement->execute([':id' => $messageId]);
    $row = $statement->fetch();
    return is_array($row) ? $row : null;
}

function updateContactMessageStatus(PDO $pdo, int $messageId, string $status): void
{
    if ($messageId <= 0) {
        throw new InvalidArgumentException('Contact message is required.');
    }
    if (!in_array($status, ['new', 'read', 'resolved'], true)) {
        throw new InvalidArgumentException('Unsupported contact message status.');
    }

    $statement = $pdo->prepare(
        'UPDATE contact_messages
         SET status = :status,
             updated_at = NOW()
         WHERE id = :id'
    );
    $statement->execute([
        ':status' => $status,
        ':id' => $messageId,
    ]);
}

function fetchContactMessageStats(PDO $pdo): array
{
    $stats = [
        'new' => 0,
        'read' => 0,
        'resolved' => 0,
    ];

    $rows = $pdo->query('SELECT status, COUNT(*) AS total FROM contact_messages GROUP BY status')->fetchAll();
    if (is_array($rows)) {
        foreach ($rows as $row) {
            $status = (string)($row['status'] ?? '');
            if (array_key_exists($status, $stats)) {
                $stats[$status] = (int)($row['total'] ?? 0);
            }
        }
    }

    return $stats;
}

function setAdminMessagesFlash(string $type, string $message): void
{
    $_SESSION['admin_messages_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function pullAdminMessagesFlash(): ?array
{
    if (!isset($_SESSION['admin_messages_flash']) || !is_array($_SESSION['admin_messages_flash'])) {
        return null;
    }

    $flash = $_SESSION['admin_messages_flash'];
    unset($_SESSION['admin_messages_flash']);
    return $flash;
}

function redirectAdminMessages(string $returnQuery): never
{
    header('Location: manageMessages.php' . sanitizeAdminMessagesReturnQuery($returnQuery));
    exit;
}

function sanitizeAdminMessagesReturnQuery(string $query): string
{
    $query = trim($query);
    if ($query === '') {
        return '';
    }

    parse_str(ltrim($query, '?'), $parsed);
    if (!is_array($parsed)) {
        return '';
    }

    $safe = [
        'q' => trim((string)($parsed['q'] ?? '')),
        'status' => trim((string)($parsed['status'] ?? '')),
    ];

    if (isset($parsed['contact_id'])) {
        $safe['contact_id'] = max(0, (int)$parsed['contact_id']);
    }

    if (!in_array($safe['status'], ['', 'new', 'read', 'resolved'], true)) {
        $safe['status'] = '';
    }

    return buildAdminMessagesQuery($safe);
}

function buildAdminMessagesQuery(array $filters): string
{
    $query = [
        'q' => (string)($filters['q'] ?? ''),
        'status' => (string)($filters['status'] ?? ''),
    ];

    if (isset($filters['contact_id']) && (int)$filters['contact_id'] > 0) {
        $query['contact_id'] = (int)$filters['contact_id'];
    }

    return '?' . http_build_query($query);
}

function adminMessageStatusClass(string $status): string
{
    return match ($status) {
        'new' => 'bg-error-container text-on-error-container',
        'resolved' => 'bg-secondary-container text-on-secondary-container',
        default => 'bg-surface-container text-on-surface-variant',
    };
}

function formatAdminMessagesDate(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return 'Never';
    }

    try {
        return (new DateTimeImmutable($value))->format('M d, Y h:i A');
    } catch (Throwable) {
        return $value;
    }
}
