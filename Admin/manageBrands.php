<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/admin_layout.php';

admin_start_session();
$admin = admin_require_auth('adminLogin.php');

$pdo = admin_db();
admin_ensure_tables($pdo);
ensureBrandsTable($pdo);

$csrfToken = admin_bootstrap_csrf_token();

$queryFilters = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? '')),
    'per_page' => max(5, min(100, (int)($_GET['per_page'] ?? 10))),
    'page' => max(1, (int)($_GET['page'] ?? 1)),
];

if (!in_array($queryFilters['status'], ['', 'active', 'inactive'], true)) {
    $queryFilters['status'] = '';
}

$mode = trim((string)($_GET['mode'] ?? ''));
$editId = max(0, (int)($_GET['edit_id'] ?? 0));

handleBrandPostActions($pdo, $admin);

$flash = pullBrandFlash();
$stats = fetchBrandStats($pdo);

$listData = fetchBrandList($pdo, $queryFilters);
$brands = $listData['rows'];
$totalRows = (int)$listData['total'];
$totalPages = max(1, (int)ceil($totalRows / $queryFilters['per_page']));
$currentPage = min($queryFilters['page'], $totalPages);

if ($currentPage !== $queryFilters['page']) {
    $queryFilters['page'] = $currentPage;
    $listData = fetchBrandList($pdo, $queryFilters);
    $brands = $listData['rows'];
}

$editingBrand = null;
if ($mode === 'edit' && $editId > 0) {
    $editingBrand = fetchBrandById($pdo, $editId);
    if ($editingBrand === null) {
        setBrandFlash('error', 'Brand not found for editing.');
        redirectManageBrands(buildBrandFilterQuery($queryFilters));
    }
}

$showForm = ($mode === 'create') || ($mode === 'edit' && $editingBrand !== null);
$baseQuery = buildBrandFilterQuery($queryFilters);

$startRow = $totalRows === 0 ? 0 : (($queryFilters['page'] - 1) * $queryFilters['per_page']) + 1;
$endRow = $totalRows === 0 ? 0 : min($totalRows, $queryFilters['page'] * $queryFilters['per_page']);

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Brand Management | LuvShop Admin</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&amp;family=Quicksand:wght@600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <style>
        body {
            font-family: "Plus Jakarta Sans", sans-serif;
            background: #fbf9f8;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .soft-shadow {
            box-shadow: 0 10px 30px -10px rgba(120, 85, 94, 0.1);
        }
    </style>
</head>
<body class="bg-[#fbf9f8] text-slate-900 min-h-screen">
<?php admin_render_sidebar($admin, 'brands'); ?>

<main class="ml-64 min-h-screen" id="app-main">
    <?php
    admin_render_header($admin, [
        'search_action' => 'manageBrands.php',
        'search_method' => 'get',
        'search_name' => 'q',
        'search_value' => trim((string)$queryFilters['q']),
        'search_placeholder' => 'Search brands...',
        'search_hidden' => [
            'status' => (string)$queryFilters['status'],
            'per_page' => (string)$queryFilters['per_page'],
            'page' => '1',
        ],
    ]);
    ?>

    <section class="p-6 max-w-[1280px] mx-auto space-y-6">
        <?php if ($flash !== null): ?>
            <div class="rounded-xl border px-4 py-3 <?= $flash['type'] === 'error' ? 'bg-red-100 border-red-200 text-red-700' : 'bg-green-100 border-green-200 text-green-700' ?>">
                <?= admin_html($flash['message']) ?>
            </div>
        <?php endif; ?>

        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-[#78555e]">Brands</h1>
                <p class="text-slate-500 mt-1">Create and manage brand identities used in your catalog.</p>
            </div>
            <div class="flex items-center gap-2">
                <a class="px-4 py-2 rounded-full bg-white border border-slate-300 text-slate-700 hover:bg-slate-50" href="manageBrands.php<?= admin_html($baseQuery) ?>">
                    Refresh
                </a>
                <a class="px-4 py-2 rounded-full bg-[#78555e] text-white hover:opacity-90" href="manageBrands.php<?= admin_html($baseQuery) ?>&mode=create">
                    Create Brand
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl soft-shadow p-5">
                <p class="text-sm text-slate-500">Total Brands</p>
                <p class="text-3xl font-semibold mt-1"><?= number_format((int)$stats['total']) ?></p>
            </div>
            <div class="bg-white rounded-2xl soft-shadow p-5">
                <p class="text-sm text-slate-500">Active</p>
                <p class="text-3xl font-semibold mt-1 text-emerald-600"><?= number_format((int)$stats['active']) ?></p>
            </div>
            <div class="bg-white rounded-2xl soft-shadow p-5">
                <p class="text-sm text-slate-500">Inactive</p>
                <p class="text-3xl font-semibold mt-1 text-slate-600"><?= number_format((int)$stats['inactive']) ?></p>
            </div>
        </div>

        <form class="bg-white rounded-2xl soft-shadow p-4 flex flex-wrap items-center gap-3" method="get">
            <input name="q" type="hidden" value="<?= admin_html((string)$queryFilters['q']) ?>"/>
            <input name="page" type="hidden" value="1"/>

            <div>
                <label class="block text-xs text-slate-500 mb-1">Status</label>
                <select class="rounded-xl border-slate-300 min-w-[180px]" name="status">
                    <option value="" <?= $queryFilters['status'] === '' ? 'selected' : '' ?>>All</option>
                    <option value="active" <?= $queryFilters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $queryFilters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Rows</label>
                <select class="rounded-xl border-slate-300 min-w-[100px]" name="per_page">
                    <?php foreach ([10, 20, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= (int)$queryFilters['per_page'] === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="mt-5 px-4 py-2 rounded-xl bg-[#78555e] text-white hover:opacity-90" type="submit">
                Apply
            </button>
            <a class="mt-5 px-4 py-2 rounded-xl text-[#78555e] hover:bg-[#ffd1dc]" href="manageBrands.php">
                Clear
            </a>
        </form>

        <?php if ($showForm): ?>
            <section class="bg-white rounded-2xl soft-shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-[#78555e]"><?= $editingBrand !== null ? 'Edit Brand' : 'Create Brand' ?></h2>
                    <a class="text-slate-500 hover:text-[#78555e]" href="manageBrands.php<?= admin_html($baseQuery) ?>">Close</a>
                </div>

                <form enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" method="post">
                    <input name="csrf_token" type="hidden" value="<?= admin_html($csrfToken) ?>"/>
                    <input name="action" type="hidden" value="<?= $editingBrand !== null ? 'update_brand' : 'create_brand' ?>"/>
                    <input name="return_query" type="hidden" value="<?= admin_html($baseQuery) ?>"/>
                    <?php if ($editingBrand !== null): ?>
                        <input name="brand_id" type="hidden" value="<?= (int)$editingBrand['id'] ?>"/>
                    <?php endif; ?>

                    <div>
                        <label class="block text-sm font-semibold mb-1">Brand Name</label>
                        <input class="w-full rounded-xl border-slate-300" maxlength="150" name="name" required type="text" value="<?= admin_html((string)($editingBrand['name'] ?? '')) ?>"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Slug (optional)</label>
                        <input class="w-full rounded-xl border-slate-300" maxlength="180" name="slug" type="text" value="<?= admin_html((string)($editingBrand['slug'] ?? '')) ?>"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Logo</label>
                        <input accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml" class="w-full rounded-xl border-slate-300" name="logo" type="file"/>
                        <p class="text-xs text-slate-400 mt-1">JPG, PNG, WEBP, GIF, SVG up to 5MB.</p>
                    </div>

                    <div class="md:col-span-2 lg:col-span-3">
                        <label class="block text-sm font-semibold mb-1">Description</label>
                        <textarea class="w-full rounded-xl border-slate-300 min-h-[100px]" maxlength="4000" name="description" placeholder="Brand description (optional)"><?= admin_html((string)($editingBrand['description'] ?? '')) ?></textarea>
                    </div>

                    <?php if ($editingBrand !== null && trim((string)$editingBrand['logo']) !== ''): ?>
                        <div class="md:col-span-2 lg:col-span-3">
                            <p class="text-sm font-semibold mb-2">Current Logo</p>
                            <img alt="<?= admin_html((string)$editingBrand['name']) ?>" class="h-16 w-16 rounded-xl object-cover border border-slate-200" src="<?= admin_html((string)$editingBrand['logo']) ?>"/>
                        </div>
                    <?php endif; ?>

                    <div class="md:col-span-2 lg:col-span-3 flex items-center gap-4">
                        <label class="inline-flex items-center gap-2">
                            <input class="rounded border-slate-300 text-[#78555e]" name="is_active" type="checkbox" value="1" <?= ((int)($editingBrand['is_active'] ?? 1) === 1) ? 'checked' : '' ?>/>
                            <span class="text-sm font-semibold">Active brand</span>
                        </label>
                    </div>

                    <div class="md:col-span-2 lg:col-span-3 flex items-center gap-2 pt-2">
                        <button class="px-5 py-2 rounded-xl bg-[#78555e] text-white hover:opacity-90" type="submit">
                            <?= $editingBrand !== null ? 'Update Brand' : 'Create Brand' ?>
                        </button>
                        <a class="px-5 py-2 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50" href="manageBrands.php<?= admin_html($baseQuery) ?>">
                            Cancel
                        </a>
                    </div>
                </form>
            </section>
        <?php endif; ?>

        <section class="bg-white rounded-2xl soft-shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px]">
                    <thead class="bg-slate-100 border-b border-slate-200">
                    <tr class="text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3 text-left">Brand</th>
                        <th class="px-4 py-3 text-left">Slug</th>
                        <th class="px-4 py-3 text-left">Products</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Updated</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                    <?php if (count($brands) === 0): ?>
                        <tr>
                            <td class="px-4 py-8 text-center text-slate-500" colspan="6">No brands found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($brands as $brand): ?>
                        <tr>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl overflow-hidden border border-slate-200 bg-slate-100 flex items-center justify-center">
                                        <?php if (trim((string)$brand['logo']) !== ''): ?>
                                            <img alt="<?= admin_html((string)$brand['name']) ?>" class="w-full h-full object-cover" src="<?= admin_html((string)$brand['logo']) ?>"/>
                                        <?php else: ?>
                                            <span class="text-sm font-semibold text-slate-600"><?= admin_html(brandInitial((string)$brand['name'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-[#78555e]"><?= admin_html((string)$brand['name']) ?></p>
                                        <p class="text-xs text-slate-500"><?= admin_html(trim((string)$brand['description']) === '' ? 'No description' : truncateText((string)$brand['description'], 72)) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-slate-600"><?= admin_html((string)$brand['slug']) ?></td>
                            <td class="px-4 py-4 text-slate-600"><?= number_format((int)$brand['total_products']) ?></td>
                            <td class="px-4 py-4">
                                <span class="<?= (int)$brand['is_active'] === 1 ? 'inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700' : 'inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-200 text-slate-700' ?>">
                                    <?= (int)$brand['is_active'] === 1 ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 text-slate-600"><?= admin_html(formatBrandDateTime((string)$brand['updated_at'])) ?></td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end items-center gap-2">
                                    <a class="px-3 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm hover:bg-slate-200" href="manageBrands.php<?= admin_html($baseQuery) ?>&mode=edit&edit_id=<?= (int)$brand['id'] ?>">
                                        Edit
                                    </a>
                                    <form method="post">
                                        <input name="csrf_token" type="hidden" value="<?= admin_html($csrfToken) ?>"/>
                                        <input name="action" type="hidden" value="toggle_brand_active"/>
                                        <input name="brand_id" type="hidden" value="<?= (int)$brand['id'] ?>"/>
                                        <input name="is_active" type="hidden" value="<?= (int)$brand['is_active'] === 1 ? '0' : '1' ?>"/>
                                        <input name="return_query" type="hidden" value="<?= admin_html($baseQuery) ?>"/>
                                        <button class="px-3 py-2 rounded-lg text-sm <?= (int)$brand['is_active'] === 1 ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' ?>" type="submit">
                                            <?= (int)$brand['is_active'] === 1 ? 'Set Inactive' : 'Set Active' ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-slate-200 bg-slate-50 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <p class="text-sm text-slate-500">Showing <?= number_format($startRow) ?> to <?= number_format($endRow) ?> of <?= number_format($totalRows) ?> brands</p>
                <div class="flex items-center gap-2">
                    <?php
                    $prevPage = max(1, $queryFilters['page'] - 1);
                    $nextPage = min($totalPages, $queryFilters['page'] + 1);
                    ?>
                    <a class="w-9 h-9 rounded-full border border-slate-300 flex items-center justify-center <?= $queryFilters['page'] <= 1 ? 'pointer-events-none opacity-40' : 'hover:bg-slate-100' ?>" href="manageBrands.php<?= admin_html(buildBrandFilterQuery(array_merge($queryFilters, ['page' => $prevPage]))) ?>">
                        <span class="material-symbols-outlined text-sm">chevron_left</span>
                    </a>
                    <?php
                    $pageStart = max(1, $queryFilters['page'] - 2);
                    $pageEnd = min($totalPages, $queryFilters['page'] + 2);
                    for ($page = $pageStart; $page <= $pageEnd; $page++):
                        $isCurrent = $page === (int)$queryFilters['page'];
                        ?>
                        <a class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold <?= $isCurrent ? 'bg-[#78555e] text-white' : 'border border-slate-300 hover:bg-slate-100' ?>" href="manageBrands.php<?= admin_html(buildBrandFilterQuery(array_merge($queryFilters, ['page' => $page]))) ?>">
                            <?= $page ?>
                        </a>
                    <?php endfor; ?>
                    <a class="w-9 h-9 rounded-full border border-slate-300 flex items-center justify-center <?= $queryFilters['page'] >= $totalPages ? 'pointer-events-none opacity-40' : 'hover:bg-slate-100' ?>" href="manageBrands.php<?= admin_html(buildBrandFilterQuery(array_merge($queryFilters, ['page' => $nextPage]))) ?>">
                        <span class="material-symbols-outlined text-sm">chevron_right</span>
                    </a>
                </div>
            </div>
        </section>
    </section>
</main>
</body>
</html>

<?php

function ensureBrandsTable(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS brands (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            slug VARCHAR(180) NOT NULL UNIQUE,
            logo VARCHAR(255) NULL,
            description TEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_brands_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function handleBrandPostActions(PDO $pdo, array $admin): void
{
    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
        return;
    }

    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    if (!admin_validate_csrf_token($csrfToken)) {
        setBrandFlash('error', 'Invalid form token. Please refresh and try again.');
        redirectManageBrands((string)($_POST['return_query'] ?? ''));
    }

    $action = trim((string)($_POST['action'] ?? ''));
    $returnQuery = (string)($_POST['return_query'] ?? '');

    try {
        switch ($action) {
            case 'create_brand':
                $uploadedLogo = storeUploadedBrandLogo('logo');
                try {
                    $name = trim((string)($_POST['name'] ?? ''));
                    if ($name === '') {
                        throw new InvalidArgumentException('Brand name is required.');
                    }

                    $slugInput = trim((string)($_POST['slug'] ?? ''));
                    $slug = buildUniqueBrandSlug($pdo, $slugInput !== '' ? $slugInput : $name, null);

                    $statement = $pdo->prepare(
                        'INSERT INTO brands (name, slug, logo, description, is_active, created_at, updated_at)
                         VALUES (:name, :slug, :logo, :description, :is_active, NOW(), NOW())'
                    );
                    $statement->execute([
                        ':name' => $name,
                        ':slug' => $slug,
                        ':logo' => $uploadedLogo !== null ? (string)$uploadedLogo['public_path'] : null,
                        ':description' => nullIfEmpty((string)($_POST['description'] ?? '')),
                        ':is_active' => (int)($_POST['is_active'] ?? 0) === 1 ? 1 : 0,
                    ]);
                } catch (Throwable $exception) {
                    cleanupUploadedBrandLogo($uploadedLogo);
                    throw $exception;
                }

                setBrandFlash('success', 'Brand created.');
                admin_log_activity($pdo, (int)$admin['id'], 'brand.create', 'Created brand ' . $name);
                redirectManageBrands($returnQuery);
                break;

            case 'update_brand':
                $brandId = max(0, (int)($_POST['brand_id'] ?? 0));
                if ($brandId <= 0) {
                    throw new InvalidArgumentException('Invalid brand ID.');
                }

                $existing = fetchBrandById($pdo, $brandId);
                if ($existing === null) {
                    throw new RuntimeException('Brand not found.');
                }

                $uploadedLogo = storeUploadedBrandLogo('logo');
                $oldLogoPath = trim((string)$existing['logo']);
                try {
                    $name = trim((string)($_POST['name'] ?? ''));
                    if ($name === '') {
                        throw new InvalidArgumentException('Brand name is required.');
                    }

                    $slugInput = trim((string)($_POST['slug'] ?? ''));
                    $slug = buildUniqueBrandSlug($pdo, $slugInput !== '' ? $slugInput : $name, $brandId);
                    $newLogoPath = $uploadedLogo !== null ? (string)$uploadedLogo['public_path'] : $oldLogoPath;

                    $statement = $pdo->prepare(
                        'UPDATE brands
                         SET name = :name,
                             slug = :slug,
                             logo = :logo,
                             description = :description,
                             is_active = :is_active,
                             updated_at = NOW()
                         WHERE id = :id'
                    );
                    $statement->execute([
                        ':name' => $name,
                        ':slug' => $slug,
                        ':logo' => $newLogoPath === '' ? null : $newLogoPath,
                        ':description' => nullIfEmpty((string)($_POST['description'] ?? '')),
                        ':is_active' => (int)($_POST['is_active'] ?? 0) === 1 ? 1 : 0,
                        ':id' => $brandId,
                    ]);

                    if ($uploadedLogo !== null && $oldLogoPath !== '' && $oldLogoPath !== $newLogoPath) {
                        deleteBrandLogoFileIfLocal($oldLogoPath);
                    }
                } catch (Throwable $exception) {
                    cleanupUploadedBrandLogo($uploadedLogo);
                    throw $exception;
                }

                setBrandFlash('success', 'Brand updated.');
                admin_log_activity($pdo, (int)$admin['id'], 'brand.update', 'Updated brand #' . $brandId);
                redirectManageBrands($returnQuery);
                break;

            case 'toggle_brand_active':
                $brandId = max(0, (int)($_POST['brand_id'] ?? 0));
                if ($brandId <= 0) {
                    throw new InvalidArgumentException('Invalid brand ID.');
                }

                $isActive = (int)($_POST['is_active'] ?? 0) === 1 ? 1 : 0;
                $statement = $pdo->prepare('UPDATE brands SET is_active = :is_active, updated_at = NOW() WHERE id = :id');
                $statement->execute([
                    ':is_active' => $isActive,
                    ':id' => $brandId,
                ]);

                setBrandFlash('success', $isActive === 1 ? 'Brand set active.' : 'Brand set inactive.');
                admin_log_activity($pdo, (int)$admin['id'], 'brand.toggle', 'Brand #' . $brandId . ' active=' . $isActive);
                redirectManageBrands($returnQuery);
                break;

            default:
                throw new InvalidArgumentException('Unknown brand action.');
        }
    } catch (Throwable $exception) {
        $message = $exception->getMessage();
        if ($exception instanceof PDOException && str_contains(strtolower($message), 'duplicate')) {
            $message = 'Brand slug already exists. Please use a different slug.';
        }
        setBrandFlash('error', $message);
        redirectManageBrands($returnQuery);
    }
}

function fetchBrandStats(PDO $pdo): array
{
    $stats = [
        'total' => 0,
        'active' => 0,
        'inactive' => 0,
    ];

    $row = $pdo->query(
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive
         FROM brands"
    )->fetch();

    if (!is_array($row)) {
        return $stats;
    }

    $stats['total'] = (int)($row['total'] ?? 0);
    $stats['active'] = (int)($row['active'] ?? 0);
    $stats['inactive'] = (int)($row['inactive'] ?? 0);
    return $stats;
}

function fetchBrandList(PDO $pdo, array $filters): array
{
    $params = [];
    $where = ['1=1'];

    if ($filters['q'] !== '') {
        $where[] = '(b.name LIKE :q OR b.slug LIKE :q)';
        $params[':q'] = '%' . $filters['q'] . '%';
    }

    if ($filters['status'] === 'active') {
        $where[] = 'b.is_active = 1';
    } elseif ($filters['status'] === 'inactive') {
        $where[] = 'b.is_active = 0';
    }

    $whereSql = implode(' AND ', $where);
    $offset = max(0, ((int)$filters['page'] - 1) * (int)$filters['per_page']);

    $countSql = "SELECT COUNT(*) FROM brands b WHERE {$whereSql}";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $productsTableExists = admin_table_exists($pdo, 'products');
    $productJoin = $productsTableExists
        ? "LEFT JOIN (
               SELECT brand_id, COUNT(*) AS total_products
               FROM products
               WHERE brand_id IS NOT NULL
               GROUP BY brand_id
           ) bp ON bp.brand_id = b.id"
        : '';
    $productCountExpression = $productsTableExists ? 'COALESCE(bp.total_products, 0)' : '0';

    $sql = "
        SELECT
            b.id,
            b.name,
            b.slug,
            b.logo,
            b.description,
            b.is_active,
            b.created_at,
            b.updated_at,
            {$productCountExpression} AS total_products
        FROM brands b
        {$productJoin}
        WHERE {$whereSql}
        ORDER BY b.updated_at DESC, b.id DESC
        LIMIT :limit OFFSET :offset
    ";

    $statement = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $statement->bindValue($key, $value);
    }
    $statement->bindValue(':limit', (int)$filters['per_page'], PDO::PARAM_INT);
    $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
    $statement->execute();
    $rows = $statement->fetchAll();

    return ['rows' => is_array($rows) ? $rows : [], 'total' => $total];
}

function fetchBrandById(PDO $pdo, int $brandId): ?array
{
    if ($brandId <= 0) {
        return null;
    }

    $statement = $pdo->prepare(
        'SELECT id, name, slug, logo, description, is_active, created_at, updated_at
         FROM brands
         WHERE id = :id
         LIMIT 1'
    );
    $statement->execute([':id' => $brandId]);
    $row = $statement->fetch();

    return is_array($row) ? $row : null;
}

function buildUniqueBrandSlug(PDO $pdo, string $rawValue, ?int $excludeId): string
{
    $slug = slugify($rawValue);
    if ($slug === '') {
        throw new InvalidArgumentException('Brand slug is invalid.');
    }

    $candidate = $slug;
    $suffix = 2;
    while (brandSlugExists($pdo, $candidate, $excludeId)) {
        $candidate = $slug . '-' . $suffix;
        $suffix++;
    }

    return $candidate;
}

function brandSlugExists(PDO $pdo, string $slug, ?int $excludeId): bool
{
    $sql = 'SELECT COUNT(*) FROM brands WHERE slug = :slug';
    if ($excludeId !== null) {
        $sql .= ' AND id <> :exclude_id';
    }

    $statement = $pdo->prepare($sql);
    $statement->bindValue(':slug', $slug);
    if ($excludeId !== null) {
        $statement->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
    }
    $statement->execute();

    return ((int)$statement->fetchColumn()) > 0;
}

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}

function nullIfEmpty(string $value): ?string
{
    $value = trim($value);
    return $value === '' ? null : $value;
}

function storeUploadedBrandLogo(string $fieldName): ?array
{
    if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
        return null;
    }

    $file = $_FILES[$fieldName];
    if (is_array($file['error'] ?? null)) {
        throw new InvalidArgumentException('Please upload a single brand logo file.');
    }

    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Brand logo upload failed.');
    }

    $tmpPath = (string)($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        throw new RuntimeException('Invalid uploaded brand logo file.');
    }

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0) {
        throw new InvalidArgumentException('Brand logo file is empty.');
    }
    if ($size > 5 * 1024 * 1024) {
        throw new InvalidArgumentException('Brand logo must be 5MB or smaller.');
    }

    $mimeType = '';
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = strtolower((string)$finfo->file($tmpPath));
    }
    if ($mimeType === '') {
        $imageInfo = @getimagesize($tmpPath);
        if (is_array($imageInfo) && isset($imageInfo['mime'])) {
            $mimeType = strtolower((string)$imageInfo['mime']);
        }
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/svg+xml' => 'svg',
    ];
    if (!isset($allowed[$mimeType])) {
        $originalName = strtolower((string)($file['name'] ?? ''));
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'], true)) {
            $mimeType = match ($extension) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                'gif' => 'image/gif',
                default => 'image/svg+xml',
            };
        }
    }
    if (!isset($allowed[$mimeType])) {
        throw new InvalidArgumentException('Allowed logo formats: JPG, PNG, WEBP, GIF, SVG.');
    }

    $uploadDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Assect' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'brands';
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
        throw new RuntimeException('Unable to prepare brand logo directory.');
    }

    $fileName = gmdate('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mimeType];
    $absolutePath = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;
    if (!move_uploaded_file($tmpPath, $absolutePath)) {
        throw new RuntimeException('Unable to save uploaded brand logo.');
    }

    return [
        'public_path' => '../Assect/uploads/brands/' . $fileName,
        'absolute_path' => $absolutePath,
    ];
}

function cleanupUploadedBrandLogo(?array $uploadedLogo): void
{
    if ($uploadedLogo === null) {
        return;
    }

    $absolutePath = (string)($uploadedLogo['absolute_path'] ?? '');
    if ($absolutePath !== '' && is_file($absolutePath)) {
        @unlink($absolutePath);
    }
}

function deleteBrandLogoFileIfLocal(string $logoPath): void
{
    $logoPath = trim($logoPath);
    if ($logoPath === '') {
        return;
    }

    if (!str_starts_with($logoPath, '../Assect/uploads/brands/')) {
        return;
    }

    $relative = substr($logoPath, strlen('../'));
    if ($relative === '') {
        return;
    }

    $absolutePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (is_file($absolutePath)) {
        @unlink($absolutePath);
    }
}

function brandInitial(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return 'B';
    }
    return strtoupper(substr($name, 0, 1));
}

function truncateText(string $value, int $length): string
{
    $value = trim($value);
    if ($value === '' || strlen($value) <= $length) {
        return $value;
    }
    return substr($value, 0, $length - 3) . '...';
}

function formatBrandDateTime(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return 'N/A';
    }

    try {
        $date = new DateTimeImmutable($value);
        return $date->format('M d, Y h:i A');
    } catch (Throwable) {
        return $value;
    }
}

function setBrandFlash(string $type, string $message): void
{
    $_SESSION['admin_brands_flash'] = ['type' => $type, 'message' => $message];
}

function pullBrandFlash(): ?array
{
    if (!isset($_SESSION['admin_brands_flash']) || !is_array($_SESSION['admin_brands_flash'])) {
        return null;
    }

    $flash = $_SESSION['admin_brands_flash'];
    unset($_SESSION['admin_brands_flash']);
    return $flash;
}

function redirectManageBrands(string $returnQuery): never
{
    $safeQuery = sanitizeBrandReturnQuery($returnQuery);
    header('Location: manageBrands.php' . $safeQuery);
    exit;
}

function sanitizeBrandReturnQuery(string $query): string
{
    $query = trim($query);
    if ($query === '') {
        return '';
    }
    if ($query[0] !== '?') {
        $query = '?' . ltrim($query, '?');
    }
    return $query;
}

function buildBrandFilterQuery(array $filters): string
{
    $query = http_build_query([
        'q' => (string)($filters['q'] ?? ''),
        'status' => (string)($filters['status'] ?? ''),
        'per_page' => (int)($filters['per_page'] ?? 10),
        'page' => (int)($filters['page'] ?? 1),
    ]);

    return $query === '' ? '' : '?' . $query;
}
