<?php

declare(strict_types=1);

require_once __DIR__ . '/../../DB/railway_mysql.php';
require_once __DIR__ . '/../../DB/redis_cache.php';

if (!defined('SHOP_CART_SESSION_KEY')) {
    define('SHOP_CART_SESSION_KEY', 'luvshop_cart');
}
if (!defined('SHOP_CART_FLASH_KEY')) {
    define('SHOP_CART_FLASH_KEY', 'luvshop_cart_flash');
}
if (!defined('SHOP_USER_SESSION_KEY')) {
    define('SHOP_USER_SESSION_KEY', 'luvshop_user');
}

function shop_db(): PDO
{
    return railway_mysql_db();
}

function shop_is_ajax_request(): bool
{
    if ((string)($_GET['ajax'] ?? '') === '1') {
        return true;
    }

    $requestedWith = strtolower(trim((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')));
    return $requestedWith === 'xmlhttprequest';
}

function shop_cache_key(string $namespace, array $payload = []): string
{
    $encoded = serialize($payload);
    return 'luvshop:' . $namespace . ':' . hash('sha256', $encoded);
}

function shop_file_cache_path(string $key): string
{
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.shop_cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir . DIRECTORY_SEPARATOR . hash('sha256', $key) . '.cache';
}

function shop_file_cache_fetch(string $key, mixed &$payload): bool
{
    $payload = null;
    $path = shop_file_cache_path($key);
    if (!is_file($path)) {
        return false;
    }

    $raw = @file_get_contents($path);
    if (!is_string($raw) || $raw === '') {
        return false;
    }

    $decoded = @unserialize($raw, ['allowed_classes' => false]);
    if (!is_array($decoded) || ($decoded['__shop_file_cache'] ?? 0) !== 1) {
        return false;
    }

    if ((int)($decoded['expires_at'] ?? 0) < time()) {
        @unlink($path);
        return false;
    }

    $payload = $decoded['payload'] ?? null;
    return true;
}

function shop_file_cache_store(string $key, mixed $payload, int $ttlSeconds): void
{
    $wrapped = [
        '__shop_file_cache' => 1,
        'expires_at' => time() + max(1, $ttlSeconds),
        'payload' => $payload,
    ];
    @file_put_contents(shop_file_cache_path($key), serialize($wrapped), LOCK_EX);
}

function shop_cache_fetch(string $key, mixed &$payload): bool
{
    $payload = null;

    $redis = railway_redis_client();
    if (!$redis instanceof Redis) {
        return shop_file_cache_fetch($key, $payload);
    }

    try {
        $raw = $redis->get($key);
    } catch (Throwable) {
        return shop_file_cache_fetch($key, $payload);
    }

    if (!is_string($raw) || $raw === '') {
        return shop_file_cache_fetch($key, $payload);
    }

    $decoded = @unserialize($raw, ['allowed_classes' => false]);
    if (!is_array($decoded) || ($decoded['__shop_cache'] ?? 0) !== 1 || !array_key_exists('payload', $decoded)) {
        // Backward compatibility for old JSON cache entries.
        $decoded = json_decode($raw, true);
    }
    if (!is_array($decoded) || ($decoded['__shop_cache'] ?? 0) !== 1 || !array_key_exists('payload', $decoded)) {
        return shop_file_cache_fetch($key, $payload);
    }

    $payload = $decoded['payload'];
    return true;
}

function shop_cache_store(string $key, mixed $payload, int $ttlSeconds): void
{
    $redis = railway_redis_client();
    if (!$redis instanceof Redis) {
        shop_file_cache_store($key, $payload, $ttlSeconds);
        return;
    }

    $ttl = max(1, $ttlSeconds);
    $wrapped = [
        '__shop_cache' => 1,
        'payload' => $payload,
    ];
    $encoded = serialize($wrapped);

    try {
        $redis->setex($key, $ttl, $encoded);
    } catch (Throwable) {
        shop_file_cache_store($key, $payload, $ttlSeconds);
    }
}

function shop_metadata_cache_ttl(): int
{
    return 3600;
}

function shop_local_asset_path(string $assetPath): string
{
    $assetPath = trim($assetPath);
    if ($assetPath === '') {
        return '';
    }

    $normalized = str_replace('\\', '/', $assetPath);
    $usersDir = dirname(__DIR__);
    $projectRoot = dirname($usersDir);

    $candidates = [];
    if (preg_match('/^[A-Za-z]:\//', $normalized) === 1) {
        $candidates[] = $normalized;
    } elseif (str_starts_with($normalized, '/')) {
        $candidates[] = $projectRoot . '/' . ltrim($normalized, '/');
    } elseif (str_starts_with($normalized, '../') || str_starts_with($normalized, './')) {
        $candidates[] = $usersDir . '/' . $normalized;
    } else {
        $candidates[] = $usersDir . '/' . $normalized;
        $candidates[] = $projectRoot . '/' . $normalized;
    }

    foreach ($candidates as $candidate) {
        $filesystemPath = str_replace('/', DIRECTORY_SEPARATOR, $candidate);
        if (is_file($filesystemPath)) {
            return $filesystemPath;
        }
    }

    return '';
}

function shop_prefer_webp_image(string $imagePath): string
{
    static $cache = [];

    $imagePath = trim($imagePath);
    if ($imagePath === '') {
        return '';
    }
    if (array_key_exists($imagePath, $cache)) {
        return $cache[$imagePath];
    }

    if (preg_match('#^(?:https?:)?//#i', $imagePath) === 1) {
        $cache[$imagePath] = $imagePath;
        return $imagePath;
    }

    $queryPos = strpos($imagePath, '?');
    $pathPart = $queryPos === false ? $imagePath : substr($imagePath, 0, $queryPos);
    $queryPart = $queryPos === false ? '' : substr($imagePath, $queryPos);

    $extension = strtolower(pathinfo($pathPart, PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
        $cache[$imagePath] = $imagePath;
        return $imagePath;
    }

    $webpPath = preg_replace('/\.(?:jpe?g|png)$/i', '.webp', $pathPart);
    if (!is_string($webpPath) || trim($webpPath) === '') {
        $cache[$imagePath] = $imagePath;
        return $imagePath;
    }

    $webpFile = shop_local_asset_path($webpPath);
    if ($webpFile !== '') {
        $resolved = $webpPath . $queryPart;
        $cache[$imagePath] = $resolved;
        return $resolved;
    }

    $cache[$imagePath] = $imagePath;
    return $imagePath;
}

function shop_table_exists(PDO $pdo, string $tableName): bool
{
    static $cache = [];
    static $allTablesLoaded = false;

    $loadAllTables = static function () use ($pdo, &$cache, &$allTablesLoaded): void {
        if ($allTablesLoaded) {
            return;
        }

        $allTablesLoaded = true;
        $listCacheKey = shop_cache_key('meta_table_list', ['v' => 1]);
        $cachedTableList = null;
        if (shop_cache_fetch($listCacheKey, $cachedTableList) && is_array($cachedTableList)) {
            foreach ($cachedTableList as $cachedTableName) {
                $normalizedName = strtolower(trim((string)$cachedTableName));
                if ($normalizedName !== '') {
                    $cache[$normalizedName] = true;
                }
            }
            return;
        }

        try {
            $statement = $pdo->query(
                'SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()'
            );
            $rows = $statement->fetchAll();
            $tableList = [];
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $normalizedName = strtolower(trim((string)($row['table_name'] ?? '')));
                    if ($normalizedName === '') {
                        continue;
                    }
                    $cache[$normalizedName] = true;
                    $tableList[] = $normalizedName;
                }
            }
            if (count($tableList) > 0) {
                shop_cache_store($listCacheKey, array_values(array_unique($tableList)), shop_metadata_cache_ttl());
            }
        } catch (Throwable) {
            // Fall back to one-table lookup below if metadata preload fails.
            $allTablesLoaded = false;
        }
    };

    $loadAllTables();

    $key = strtolower(trim($tableName));
    if ($key === '') {
        return false;
    }

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $cacheKey = shop_cache_key('meta_table_exists', ['table' => $key, 'v' => 1]);
    $cached = null;
    if (shop_cache_fetch($cacheKey, $cached)) {
        $exists = (bool)$cached;
        $cache[$key] = $exists;
        return $exists;
    }

    $exists = false;
    try {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name'
        );
        $statement->execute([':table_name' => $tableName]);
        $exists = ((int)$statement->fetchColumn()) > 0;
    } catch (Throwable) {
        $exists = false;
    }
    $cache[$key] = $exists;
    shop_cache_store($cacheKey, $exists, shop_metadata_cache_ttl());
    return $exists;
}

function shop_table_has_column(PDO $pdo, string $tableName, string $columnName): bool
{
    static $cache = [];
    static $columnsByTable = [];

    $loadTableColumns = static function (string $normalizedTableName, string $originalTableName) use ($pdo, &$columnsByTable): void {
        if (array_key_exists($normalizedTableName, $columnsByTable)) {
            return;
        }

        $columnsByTable[$normalizedTableName] = [];
        $listCacheKey = shop_cache_key('meta_table_columns', [
            'table' => $normalizedTableName,
            'v' => 1,
        ]);
        $cachedColumns = null;
        if (shop_cache_fetch($listCacheKey, $cachedColumns) && is_array($cachedColumns)) {
            $normalizedColumns = [];
            foreach ($cachedColumns as $cachedColumnName) {
                $normalizedColumn = strtolower(trim((string)$cachedColumnName));
                if ($normalizedColumn !== '') {
                    $normalizedColumns[] = $normalizedColumn;
                }
            }
            $columnsByTable[$normalizedTableName] = array_values(array_unique($normalizedColumns));
            return;
        }

        try {
            $statement = $pdo->prepare(
                'SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table_name'
            );
            $statement->execute([':table_name' => $originalTableName]);
            $rows = $statement->fetchAll();

            $columnList = [];
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $normalizedColumn = strtolower(trim((string)($row['column_name'] ?? '')));
                    if ($normalizedColumn !== '') {
                        $columnList[] = $normalizedColumn;
                    }
                }
            }
            $columnList = array_values(array_unique($columnList));
            $columnsByTable[$normalizedTableName] = $columnList;
            if (count($columnList) > 0) {
                shop_cache_store($listCacheKey, $columnList, shop_metadata_cache_ttl());
            }
        } catch (Throwable) {
            $columnsByTable[$normalizedTableName] = [];
        }
    };

    $table = strtolower(trim($tableName));
    $column = strtolower(trim($columnName));
    if ($table === '' || $column === '') {
        return false;
    }

    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $cacheKey = shop_cache_key('meta_table_column_exists', [
        'table' => $table,
        'column' => $column,
        'v' => 1,
    ]);
    $cached = null;
    if (shop_cache_fetch($cacheKey, $cached)) {
        $exists = (bool)$cached;
        $cache[$key] = $exists;
        return $exists;
    }

    if (!shop_table_exists($pdo, $tableName)) {
        $cache[$key] = false;
        shop_cache_store($cacheKey, false, shop_metadata_cache_ttl());
        return false;
    }

    $loadTableColumns($table, $tableName);
    $tableColumns = $columnsByTable[$table] ?? [];
    if (is_array($tableColumns) && count($tableColumns) > 0) {
        $exists = in_array($column, $tableColumns, true);
        $cache[$key] = $exists;
        shop_cache_store($cacheKey, $exists, shop_metadata_cache_ttl());
        return $exists;
    }

    $exists = false;
    try {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table_name AND column_name = :column_name'
        );
        $statement->execute([
            ':table_name' => $tableName,
            ':column_name' => $columnName,
        ]);
        $exists = ((int)$statement->fetchColumn()) > 0;
    } catch (Throwable) {
        $exists = false;
    }
    $cache[$key] = $exists;
    shop_cache_store($cacheKey, $exists, shop_metadata_cache_ttl());
    return $exists;
}

function shop_normalize_filters(array $input): array
{
    $sort = trim((string)($input['sort'] ?? 'newest'));
    $allowedSorts = ['newest', 'price_low', 'price_high', 'name_az'];
    if (!in_array($sort, $allowedSorts, true)) {
        $sort = 'newest';
    }

    return [
        'q' => trim((string)($input['q'] ?? '')),
        'category' => trim((string)($input['category'] ?? '')),
        'sort' => $sort,
        'page' => max(1, (int)($input['page'] ?? 1)),
        'per_page' => max(8, min(48, (int)($input['per_page'] ?? 12))),
    ];
}

function shop_fetch_categories(PDO $pdo): array
{
    $cacheKey = shop_cache_key('categories', ['active_only' => 1, 'v' => 1]);
    $cached = null;
    if (shop_cache_fetch($cacheKey, $cached) && is_array($cached)) {
        return $cached;
    }

    if (!shop_table_exists($pdo, 'categories') || !shop_table_exists($pdo, 'products')) {
        return [];
    }

    $sql = "
        SELECT
            c.id,
            c.name,
            c.slug,
            COALESCE(COUNT(DISTINCT p.id), 0) AS product_count
        FROM categories c
        LEFT JOIN products p
            ON p.category_id = c.id
           AND p.status = 'active'
        WHERE c.is_active = 1
        GROUP BY c.id, c.name, c.slug
        ORDER BY c.sort_order ASC, c.name ASC
    ";

    $rows = $pdo->query($sql)->fetchAll();
    $result = is_array($rows) ? $rows : [];
    shop_cache_store($cacheKey, $result, 180);
    return $result;
}

function shop_fetch_products(PDO $pdo, array $filters): array
{
    if (!shop_table_exists($pdo, 'products')) {
        return ['rows' => [], 'total' => 0];
    }

    $cachePayload = [
        'q' => (string)($filters['q'] ?? ''),
        'category' => (string)($filters['category'] ?? ''),
        'sort' => (string)($filters['sort'] ?? 'newest'),
        'page' => (int)($filters['page'] ?? 1),
        'per_page' => (int)($filters['per_page'] ?? 12),
        'v' => 1,
    ];
    $cacheKey = shop_cache_key('products', $cachePayload);
    $cached = null;
    if (shop_cache_fetch($cacheKey, $cached) && is_array($cached)) {
        $cachedRows = $cached['rows'] ?? null;
        $cachedTotal = $cached['total'] ?? null;
        if (is_array($cachedRows)) {
            return [
                'rows' => $cachedRows,
                'total' => (int)$cachedTotal,
            ];
        }
    }

    $hasCategories = shop_table_exists($pdo, 'categories');
    $hasBrands = shop_table_exists($pdo, 'brands');
    $hasVariants = shop_table_exists($pdo, 'product_variants');
    $hasImages = shop_table_exists($pdo, 'product_images');

    $params = [];
    $where = ["p.status = 'active'"];

    if ($filters['q'] !== '') {
        $searchValue = '%' . $filters['q'] . '%';
        $where[] = '(p.name LIKE :q_name OR p.short_description LIKE :q_short OR p.slug LIKE :q_slug OR c.name LIKE :q_category OR b.name LIKE :q_brand)';
        $params[':q_name'] = $searchValue;
        $params[':q_short'] = $searchValue;
        $params[':q_slug'] = $searchValue;
        $params[':q_category'] = $searchValue;
        $params[':q_brand'] = $searchValue;
    }

    if ($filters['category'] !== '') {
        $where[] = 'c.slug = :category_slug';
        $params[':category_slug'] = $filters['category'];
    }

    if ($hasCategories) {
        $where[] = '(c.id IS NULL OR c.is_active = 1)';
    }
    if ($hasBrands) {
        $where[] = '(b.id IS NULL OR b.is_active = 1)';
    }

    $whereSql = implode(' AND ', $where);

    $categoryJoin = $hasCategories
        ? 'LEFT JOIN categories c ON c.id = p.category_id'
        : 'LEFT JOIN (SELECT NULL AS id, NULL AS name, NULL AS slug, 1 AS is_active) c ON 1 = 0';
    $brandJoin = $hasBrands
        ? 'LEFT JOIN brands b ON b.id = p.brand_id'
        : 'LEFT JOIN (SELECT NULL AS id, NULL AS name, 1 AS is_active) b ON 1 = 0';
    $variantJoin = $hasVariants
        ? "LEFT JOIN (
                SELECT
                    product_id,
                    MIN(price) AS min_price,
                    MAX(compare_price) AS compare_price,
                    SUM(stock_quantity) AS stock_quantity
                FROM product_variants
                WHERE is_active = 1
                GROUP BY product_id
            ) pv ON pv.product_id = p.id"
        : 'LEFT JOIN (SELECT NULL AS product_id, 0 AS min_price, NULL AS compare_price, 0 AS stock_quantity) pv ON 1 = 0';
    $imageJoin = $hasImages
        ? "LEFT JOIN (
                SELECT product_id, MIN(image) AS image
                FROM product_images
                GROUP BY product_id
            ) pi ON pi.product_id = p.id"
        : 'LEFT JOIN (SELECT NULL AS product_id, NULL AS image) pi ON 1 = 0';

    $countSql = "
        SELECT COUNT(*)
        FROM products p
        {$categoryJoin}
        {$brandJoin}
        WHERE {$whereSql}
    ";
    $countStatement = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStatement->bindValue($key, $value);
    }
    $countStatement->execute();
    $total = (int)$countStatement->fetchColumn();

    $orderBy = match ($filters['sort']) {
        'price_low' => 'COALESCE(pv.min_price, 0) ASC, p.updated_at DESC, p.id DESC',
        'price_high' => 'COALESCE(pv.min_price, 0) DESC, p.updated_at DESC, p.id DESC',
        'name_az' => 'p.name ASC, p.id DESC',
        default => 'p.updated_at DESC, p.id DESC',
    };

    $offset = max(0, ($filters['page'] - 1) * $filters['per_page']);

    $sql = "
        SELECT
            p.id,
            p.name,
            p.slug,
            p.short_description,
            p.description,
            COALESCE(c.name, '') AS category_name,
            COALESCE(c.slug, '') AS category_slug,
            COALESCE(c.id, 0) AS category_id,
            COALESCE(b.name, '') AS brand_name,
            COALESCE(pi.image, '') AS image,
            COALESCE(pv.min_price, 0) AS price,
            pv.compare_price,
            COALESCE(pv.stock_quantity, 0) AS stock_quantity
        FROM products p
        {$categoryJoin}
        {$brandJoin}
        {$variantJoin}
        {$imageJoin}
        WHERE {$whereSql}
        ORDER BY {$orderBy}
        LIMIT :limit OFFSET :offset
    ";

    $statement = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $statement->bindValue($key, $value);
    }
    $statement->bindValue(':limit', $filters['per_page'], PDO::PARAM_INT);
    $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
    $statement->execute();
    $rows = $statement->fetchAll();
    $rows = is_array($rows) ? $rows : [];
    shop_hydrate_products_with_images($pdo, $rows);

    $result = [
        'rows' => $rows,
        'total' => $total,
    ];
    shop_cache_store($cacheKey, $result, 600);
    return $result;
}

function shop_hydrate_products_with_images(PDO $pdo, array &$rows, int $maxImagesPerProduct = 8): void
{
    if (count($rows) === 0) {
        return;
    }

    $maxImagesPerProduct = max(1, min(20, $maxImagesPerProduct));

    foreach ($rows as &$row) {
        $primary = shop_prefer_webp_image(trim((string)($row['image'] ?? '')));
        $row['image'] = $primary;
        $row['images'] = $primary !== '' ? [$primary] : [];
    }
    unset($row);

    if (!shop_table_exists($pdo, 'product_images')) {
        return;
    }

    $productIds = array_values(array_unique(array_map(
        static fn (array $row): int => (int)($row['id'] ?? 0),
        $rows
    )));
    $productIds = array_values(array_filter($productIds, static fn (int $id): bool => $id > 0));

    if (count($productIds) === 0) {
        return;
    }

    $placeholders = implode(', ', array_fill(0, count($productIds), '?'));
    $statement = $pdo->prepare(
        "SELECT product_id, image
         FROM product_images
         WHERE product_id IN ({$placeholders})
           AND image IS NOT NULL
           AND image <> ''
         ORDER BY product_id ASC, id ASC"
    );
    foreach ($productIds as $index => $productId) {
        $statement->bindValue($index + 1, $productId, PDO::PARAM_INT);
    }
    $statement->execute();
    $imageRows = $statement->fetchAll();

    $imagesByProductId = [];
    if (is_array($imageRows)) {
        foreach ($imageRows as $imageRow) {
            $productId = (int)($imageRow['product_id'] ?? 0);
            $image = trim((string)($imageRow['image'] ?? ''));
            if ($productId <= 0 || $image === '') {
                continue;
            }
            $image = shop_prefer_webp_image($image);
            if (!isset($imagesByProductId[$productId])) {
                $imagesByProductId[$productId] = [];
            }
            $imagesByProductId[$productId][] = $image;
        }
    }

    foreach ($rows as &$row) {
        $productId = (int)($row['id'] ?? 0);
        $gallery = is_array($row['images'] ?? null) ? $row['images'] : [];
        if (isset($imagesByProductId[$productId])) {
            $gallery = array_merge($gallery, $imagesByProductId[$productId]);
        }
        $gallery = array_values(array_unique(array_filter($gallery, static fn (string $src): bool => trim($src) !== '')));
        if (count($gallery) > $maxImagesPerProduct) {
            $gallery = array_slice($gallery, 0, $maxImagesPerProduct);
        }
        $row['images'] = $gallery;
    }
    unset($row);
}

function shop_featured_products(PDO $pdo, int $limit = 8): array
{
    $limit = max(1, min($limit, 20));
    $filters = [
        'q' => '',
        'category' => '',
        'sort' => 'newest',
        'page' => 1,
        'per_page' => $limit,
    ];
    return shop_fetch_products($pdo, $filters)['rows'];
}

function shop_product_detail_url(array $product): string
{
    $slug = trim((string)($product['slug'] ?? ''));
    if ($slug !== '') {
        return 'productDetail.php?slug=' . rawurlencode($slug);
    }

    $id = (int)($product['id'] ?? 0);
    if ($id > 0) {
        return 'productDetail.php?id=' . $id;
    }

    return 'shop.php';
}

function shop_fetch_product_detail(PDO $pdo, string $slug = '', int $id = 0): ?array
{
    if (!shop_table_exists($pdo, 'products')) {
        return null;
    }

    $slug = trim($slug);
    $id = max(0, $id);
    if ($slug === '' && $id <= 0) {
        return null;
    }

    $cacheKey = shop_cache_key('product_detail', [
        'slug' => $slug,
        'id' => $id,
        'v' => 1,
    ]);
    $cached = null;
    if (shop_cache_fetch($cacheKey, $cached)) {
        return is_array($cached) ? $cached : null;
    }

    $hasCategories = shop_table_exists($pdo, 'categories');
    $hasBrands = shop_table_exists($pdo, 'brands');
    $hasVariants = shop_table_exists($pdo, 'product_variants');
    $hasImages = shop_table_exists($pdo, 'product_images');

    $categoryJoin = $hasCategories
        ? 'LEFT JOIN categories c ON c.id = p.category_id'
        : 'LEFT JOIN (SELECT NULL AS id, NULL AS name, NULL AS slug, 1 AS is_active) c ON 1 = 0';
    $brandJoin = $hasBrands
        ? 'LEFT JOIN brands b ON b.id = p.brand_id'
        : 'LEFT JOIN (SELECT NULL AS id, NULL AS name, 1 AS is_active) b ON 1 = 0';
    $variantJoin = $hasVariants
        ? "LEFT JOIN (
                SELECT
                    product_id,
                    MIN(price) AS min_price,
                    MAX(compare_price) AS compare_price,
                    SUM(stock_quantity) AS stock_quantity
                FROM product_variants
                WHERE is_active = 1
                GROUP BY product_id
            ) pv ON pv.product_id = p.id"
        : 'LEFT JOIN (SELECT NULL AS product_id, 0 AS min_price, NULL AS compare_price, 0 AS stock_quantity) pv ON 1 = 0';
    $imageJoin = $hasImages
        ? "LEFT JOIN (
                SELECT product_id, MIN(image) AS image
                FROM product_images
                GROUP BY product_id
            ) pi ON pi.product_id = p.id"
        : 'LEFT JOIN (SELECT NULL AS product_id, NULL AS image) pi ON 1 = 0';

    $where = ["p.status = 'active'"];
    $params = [];

    if ($slug !== '') {
        $where[] = 'p.slug = :slug';
        $params[':slug'] = $slug;
    } else {
        $where[] = 'p.id = :id';
        $params[':id'] = $id;
    }

    if ($hasCategories) {
        $where[] = '(c.id IS NULL OR c.is_active = 1)';
    }
    if ($hasBrands) {
        $where[] = '(b.id IS NULL OR b.is_active = 1)';
    }

    $sql = "
        SELECT
            p.id,
            p.name,
            p.slug,
            p.short_description,
            p.description,
            p.status,
            COALESCE(c.id, 0) AS category_id,
            COALESCE(c.name, '') AS category_name,
            COALESCE(c.slug, '') AS category_slug,
            COALESCE(b.id, 0) AS brand_id,
            COALESCE(b.name, '') AS brand_name,
            COALESCE(pi.image, '') AS image,
            COALESCE(pv.min_price, 0) AS price,
            pv.compare_price,
            COALESCE(pv.stock_quantity, 0) AS stock_quantity
        FROM products p
        {$categoryJoin}
        {$brandJoin}
        {$variantJoin}
        {$imageJoin}
        WHERE " . implode(' AND ', $where) . "
        ORDER BY p.id DESC
        LIMIT 1
    ";

    $statement = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        if ($key === ':id') {
            $statement->bindValue($key, (int)$value, PDO::PARAM_INT);
        } else {
            $statement->bindValue($key, (string)$value);
        }
    }
    $statement->execute();
    $row = $statement->fetch();
    if (!is_array($row)) {
        shop_cache_store($cacheKey, null, 30);
        return null;
    }

    $productId = (int)$row['id'];
    $primaryImage = trim((string)($row['image'] ?? ''));
    $primaryImage = shop_prefer_webp_image($primaryImage);
    $row['image'] = $primaryImage;
    $row['images'] = shop_fetch_product_images($pdo, $productId, $primaryImage);
    $row['variants'] = shop_fetch_product_variants($pdo, $productId);

    shop_cache_store($cacheKey, $row, 90);
    return $row;
}

function shop_fetch_product_images(PDO $pdo, int $productId, string $primaryImage = ''): array
{
    $images = [];

    if (shop_table_exists($pdo, 'product_images')) {
        $statement = $pdo->prepare(
            'SELECT image FROM product_images WHERE product_id = :product_id AND image IS NOT NULL AND image <> "" ORDER BY id ASC'
        );
        $statement->execute([':product_id' => $productId]);
        $rows = $statement->fetchAll();
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $image = trim((string)($row['image'] ?? ''));
                if ($image !== '') {
                    $images[] = shop_prefer_webp_image($image);
                }
            }
        }
    }

    $primaryImage = trim($primaryImage);
    if ($primaryImage !== '') {
        array_unshift($images, shop_prefer_webp_image($primaryImage));
    }

    $images = array_values(array_unique($images));
    return $images;
}

function shop_fetch_product_variants(PDO $pdo, int $productId): array
{
    if (!shop_table_exists($pdo, 'product_variants')) {
        return [];
    }

    $statement = $pdo->prepare(
        "SELECT
            id,
            sku,
            variant_name,
            price,
            compare_price,
            stock_quantity,
            low_stock_threshold,
            is_active
         FROM product_variants
         WHERE product_id = :product_id AND is_active = 1
         ORDER BY price ASC, id ASC"
    );
    $statement->execute([':product_id' => $productId]);
    $rows = $statement->fetchAll();
    return is_array($rows) ? $rows : [];
}

function shop_fetch_related_products(PDO $pdo, int $productId, int $categoryId, int $limit = 4): array
{
    if (!shop_table_exists($pdo, 'products')) {
        return [];
    }

    $limit = max(1, min($limit, 12));
    $cacheKey = shop_cache_key('related_products', [
        'product_id' => $productId,
        'category_id' => $categoryId,
        'limit' => $limit,
        'v' => 1,
    ]);
    $cached = null;
    if (shop_cache_fetch($cacheKey, $cached) && is_array($cached)) {
        return $cached;
    }

    $hasCategories = shop_table_exists($pdo, 'categories');
    $hasBrands = shop_table_exists($pdo, 'brands');
    $hasVariants = shop_table_exists($pdo, 'product_variants');
    $hasImages = shop_table_exists($pdo, 'product_images');

    $categoryJoin = $hasCategories
        ? 'LEFT JOIN categories c ON c.id = p.category_id'
        : 'LEFT JOIN (SELECT NULL AS id, NULL AS name, NULL AS slug, 1 AS is_active) c ON 1 = 0';
    $brandJoin = $hasBrands
        ? 'LEFT JOIN brands b ON b.id = p.brand_id'
        : 'LEFT JOIN (SELECT NULL AS id, NULL AS name, 1 AS is_active) b ON 1 = 0';
    $variantJoin = $hasVariants
        ? "LEFT JOIN (
                SELECT
                    product_id,
                    MIN(price) AS min_price,
                    MAX(compare_price) AS compare_price,
                    SUM(stock_quantity) AS stock_quantity
                FROM product_variants
                WHERE is_active = 1
                GROUP BY product_id
            ) pv ON pv.product_id = p.id"
        : 'LEFT JOIN (SELECT NULL AS product_id, 0 AS min_price, NULL AS compare_price, 0 AS stock_quantity) pv ON 1 = 0';
    $imageJoin = $hasImages
        ? "LEFT JOIN (
                SELECT product_id, MIN(image) AS image
                FROM product_images
                GROUP BY product_id
            ) pi ON pi.product_id = p.id"
        : 'LEFT JOIN (SELECT NULL AS product_id, NULL AS image) pi ON 1 = 0';

    $sql = "
        SELECT
            p.id,
            p.name,
            p.slug,
            p.short_description,
            COALESCE(c.name, '') AS category_name,
            COALESCE(c.slug, '') AS category_slug,
            COALESCE(c.id, 0) AS category_id,
            COALESCE(b.name, '') AS brand_name,
            COALESCE(pi.image, '') AS image,
            COALESCE(pv.min_price, 0) AS price,
            pv.compare_price,
            COALESCE(pv.stock_quantity, 0) AS stock_quantity
        FROM products p
        {$categoryJoin}
        {$brandJoin}
        {$variantJoin}
        {$imageJoin}
        WHERE p.status = 'active'
          AND p.id <> :product_id
        ORDER BY
          CASE WHEN :category_id_check > 0 AND p.category_id = :category_id_match THEN 1 ELSE 0 END DESC,
          p.updated_at DESC,
          p.id DESC
        LIMIT :limit
    ";

    $statement = $pdo->prepare($sql);
    $statement->bindValue(':product_id', $productId, PDO::PARAM_INT);
    $statement->bindValue(':category_id_check', $categoryId, PDO::PARAM_INT);
    $statement->bindValue(':category_id_match', $categoryId, PDO::PARAM_INT);
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();
    $rows = $statement->fetchAll();
    $result = is_array($rows) ? $rows : [];
    foreach ($result as &$row) {
        $row['image'] = shop_prefer_webp_image(trim((string)($row['image'] ?? '')));
    }
    unset($row);
    shop_cache_store($cacheKey, $result, 90);
    return $result;
}

function shop_fetch_product_reviews(PDO $pdo, int $productId, int $limit = 6): array
{
    if ($productId <= 0 || !shop_table_exists($pdo, 'reviews')) {
        return [];
    }
    if (!shop_table_has_column($pdo, 'reviews', 'product_id')) {
        return [];
    }

    $limit = max(1, min($limit, 20));
    $cacheKey = shop_cache_key('product_reviews', [
        'product_id' => $productId,
        'limit' => $limit,
        'v' => 1,
    ]);
    $cached = null;
    if (shop_cache_fetch($cacheKey, $cached) && is_array($cached)) {
        return $cached;
    }

    $hasUsers = shop_table_exists($pdo, 'users');
    $reviewerNameSelect = "'Anonymous'";
    if ($hasUsers) {
        $nameColumns = [];
        foreach (['full_name', 'name', 'username', 'email'] as $candidateColumn) {
            if (shop_table_has_column($pdo, 'users', $candidateColumn)) {
                $nameColumns[] = "NULLIF(TRIM(u.{$candidateColumn}), '')";
            }
        }
        if (count($nameColumns) > 0) {
            $reviewerNameSelect = 'COALESCE(' . implode(', ', $nameColumns) . ", 'Anonymous')";
        }
    }

    $userJoin = $hasUsers
        ? 'LEFT JOIN users u ON u.id = r.user_id'
        : 'LEFT JOIN (SELECT NULL AS id, NULL AS user_id) u ON 1 = 0';

    $statusFilter = '';
    $statusValue = '';
    if (shop_table_has_column($pdo, 'reviews', 'status')) {
        $statusFilter = ' AND r.status = :status';
        $statusValue = 'approved';
    }

    $sql = "
        SELECT
            r.id,
            COALESCE(r.rating, 0) AS rating,
            COALESCE(r.comment, '') AS comment,
            r.created_at,
            {$reviewerNameSelect} AS reviewer_name
        FROM reviews r
        {$userJoin}
        WHERE r.product_id = :product_id{$statusFilter}
        ORDER BY r.created_at DESC, r.id DESC
        LIMIT :limit
    ";

    $statement = $pdo->prepare($sql);
    $statement->bindValue(':product_id', $productId, PDO::PARAM_INT);
    if ($statusFilter !== '') {
        $statement->bindValue(':status', $statusValue);
    }
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();
    $rows = $statement->fetchAll();
    $result = is_array($rows) ? $rows : [];
    shop_cache_store($cacheKey, $result, 300);
    return $result;
}

function shop_ensure_contact_tables(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS contact_settings (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            support_email VARCHAR(255) NOT NULL,
            support_phone VARCHAR(80) NOT NULL,
            support_address VARCHAR(255) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_contact_settings_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

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

function shop_fetch_contact_settings(PDO $pdo): array
{
    shop_ensure_contact_tables($pdo);

    $statement = $pdo->query(
        'SELECT support_email, support_phone, support_address FROM contact_settings WHERE is_active = 1 ORDER BY id DESC LIMIT 1'
    );
    $row = $statement->fetch();

    if (!is_array($row)) {
        $insert = $pdo->prepare(
            'INSERT INTO contact_settings (support_email, support_phone, support_address, is_active) VALUES (:support_email, :support_phone, :support_address, 1)'
        );
        $insert->execute([
            ':support_email' => 'hello@luvshop.com',
            ':support_phone' => '+95 786917400',
            ':support_address' => '145/4, Yangon',
        ]);

        $statement = $pdo->query(
            'SELECT support_email, support_phone, support_address FROM contact_settings WHERE is_active = 1 ORDER BY id DESC LIMIT 1'
        );
        $row = $statement->fetch();
    }

    return [
        'support_email' => trim((string)($row['support_email'] ?? 'hello@luvshop.com')),
        'support_phone' => trim((string)($row['support_phone'] ?? '+95 786917400')),
        'support_address' => trim((string)($row['support_address'] ?? '145/4, Yangon')),
    ];
}

function shop_save_contact_message(PDO $pdo, array $payload): int
{
    shop_ensure_contact_tables($pdo);

    $fullName = trim((string)($payload['full_name'] ?? ''));
    $email = strtolower(trim((string)($payload['email'] ?? '')));
    $subject = trim((string)($payload['subject'] ?? ''));
    $message = trim((string)($payload['message'] ?? ''));

    if ($fullName === '' || $email === '' || $subject === '' || $message === '') {
        throw new InvalidArgumentException('Please fill all contact form fields.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Please enter a valid email address.');
    }
    if (strlen($fullName) > 150) {
        throw new InvalidArgumentException('Name is too long.');
    }
    if (strlen($email) > 255) {
        throw new InvalidArgumentException('Email is too long.');
    }
    if (strlen($subject) > 200) {
        throw new InvalidArgumentException('Subject is too long.');
    }
    if (strlen($message) > 5000) {
        throw new InvalidArgumentException('Message is too long.');
    }

    $ipAddress = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    $userAgent = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if ($ipAddress === '') {
        $ipAddress = null;
    }
    if ($userAgent === '') {
        $userAgent = null;
    } elseif (strlen($userAgent) > 255) {
        $userAgent = substr($userAgent, 0, 255);
    }

    $statement = $pdo->prepare(
        'INSERT INTO contact_messages (full_name, email, subject, message, ip_address, user_agent) VALUES (:full_name, :email, :subject, :message, :ip_address, :user_agent)'
    );
    $statement->execute([
        ':full_name' => $fullName,
        ':email' => $email,
        ':subject' => $subject,
        ':message' => $message,
        ':ip_address' => $ipAddress,
        ':user_agent' => $userAgent,
    ]);

    return (int)$pdo->lastInsertId();
}

function shop_h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function shop_money(float $amount): string
{
    return '$' . number_format($amount, 2);
}

function shop_placeholder_gradient(int $index): string
{
    $gradients = [
        'from-rose-100 to-pink-200',
        'from-amber-100 to-yellow-200',
        'from-teal-100 to-emerald-200',
        'from-sky-100 to-blue-200',
    ];
    return $gradients[$index % count($gradients)];
}

function shop_short_text(string $value, int $length = 86): string
{
    $value = trim($value);
    if ($value === '' || strlen($value) <= $length) {
        return $value;
    }
    return substr($value, 0, $length - 3) . '...';
}

function shop_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    if (headers_sent()) {
        return;
    }

    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);

    @session_start([
        'cookie_httponly' => true,
        'cookie_secure' => $isSecure,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

function shop_close_session_if_active(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}

function shop_current_user(): ?array
{
    shop_start_session();

    $user = $_SESSION[SHOP_USER_SESSION_KEY] ?? null;
    if (!is_array($user)) {
        shop_close_session_if_active();
        return null;
    }

    $id = max(0, (int)($user['id'] ?? 0));
    $firebaseUid = trim((string)($user['firebase_uid'] ?? ''));
    $email = trim((string)($user['email'] ?? ''));

    if ($id <= 0 && $firebaseUid === '' && $email === '') {
        shop_close_session_if_active();
        return null;
    }

    $currentUser = [
        'id' => $id,
        'firebase_uid' => $firebaseUid,
        'email' => $email,
        'name' => trim((string)($user['name'] ?? '')),
        'avatar' => trim((string)($user['avatar'] ?? '')),
        'role' => trim((string)($user['role'] ?? 'customer')),
    ];
    shop_close_session_if_active();
    return $currentUser;
}

function shop_user_is_logged_in(): bool
{
    return shop_current_user() !== null;
}

function shop_safe_login_return_path(string $returnPath): string
{
    $returnPath = trim(str_replace('\\', '/', $returnPath));
    if ($returnPath === '') {
        return '../Users/Home.php';
    }

    if (
        preg_match('/^[a-z][a-z0-9+.-]*:/i', $returnPath) === 1
        || str_starts_with($returnPath, '//')
        || str_starts_with($returnPath, '/')
    ) {
        return '../Users/Home.php';
    }

    return $returnPath;
}

function shop_login_url(string $returnPath = '../Users/checkout.php'): string
{
    return '../Auth/login.php?redirect=' . rawurlencode(shop_safe_login_return_path($returnPath));
}

function shop_require_checkout_login(string $returnPath = '../Users/checkout.php'): void
{
    if (shop_user_is_logged_in()) {
        return;
    }

    header('Location: ' . shop_login_url($returnPath), true, 302);
    exit;
}

function shop_cart_line_key(int $productId, int $variantId): string
{
    return 'p' . $productId . ':v' . max(0, $variantId);
}

function shop_cart_storage(): array
{
    shop_start_session();

    $raw = $_SESSION[SHOP_CART_SESSION_KEY] ?? [];
    if (!is_array($raw)) {
        shop_close_session_if_active();
        return [];
    }

    $normalized = [];
    foreach ($raw as $item) {
        if (!is_array($item)) {
            continue;
        }

        $productId = max(0, (int)($item['product_id'] ?? 0));
        $variantId = max(0, (int)($item['variant_id'] ?? 0));
        $quantity = max(1, (int)($item['quantity'] ?? 1));
        if ($productId <= 0) {
            continue;
        }

        $lineKey = shop_cart_line_key($productId, $variantId);
        $normalized[$lineKey] = [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => $quantity,
            'added_at' => (string)($item['added_at'] ?? gmdate('c')),
        ];
    }

    shop_close_session_if_active();
    return $normalized;
}

function shop_cart_save_storage(array $cart): void
{
    shop_start_session();
    $_SESSION[SHOP_CART_SESSION_KEY] = $cart;
    shop_close_session_if_active();
}

function shop_cart_set_flash(array $payload): void
{
    shop_start_session();
    $_SESSION[SHOP_CART_FLASH_KEY] = $payload;
    shop_close_session_if_active();
}

function shop_cart_pull_flash(): ?array
{
    shop_start_session();
    if (!isset($_SESSION[SHOP_CART_FLASH_KEY]) || !is_array($_SESSION[SHOP_CART_FLASH_KEY])) {
        shop_close_session_if_active();
        return null;
    }

    $flash = $_SESSION[SHOP_CART_FLASH_KEY];
    unset($_SESSION[SHOP_CART_FLASH_KEY]);
    shop_close_session_if_active();
    return $flash;
}

function shop_cart_item_count(): int
{
    $cart = shop_cart_storage();
    $count = 0;
    foreach ($cart as $item) {
        $count += max(0, (int)($item['quantity'] ?? 0));
    }
    return $count;
}

function shop_find_product_variant(array $product, int $variantId): ?array
{
    $variants = is_array($product['variants'] ?? null) ? $product['variants'] : [];
    if (count($variants) === 0) {
        return null;
    }

    if ($variantId > 0) {
        foreach ($variants as $variant) {
            if ((int)($variant['id'] ?? 0) === $variantId) {
                return is_array($variant) ? $variant : null;
            }
        }
    }

    $first = $variants[0] ?? null;
    return is_array($first) ? $first : null;
}

function shop_fetch_cart_candidate(PDO $pdo, int $productId, int $variantId = 0): array
{
    if ($productId <= 0) {
        throw new InvalidArgumentException('Invalid product.');
    }

    $product = shop_fetch_product_detail($pdo, '', $productId);
    if (!is_array($product)) {
        throw new RuntimeException('Product is unavailable.');
    }

    $variant = shop_find_product_variant($product, $variantId);
    $selectedVariantId = $variant !== null ? (int)($variant['id'] ?? 0) : 0;
    $variantName = $variant !== null ? trim((string)($variant['variant_name'] ?? '')) : '';
    $sku = $variant !== null ? trim((string)($variant['sku'] ?? '')) : '';

    $stock = $variant !== null
        ? max(0, (int)($variant['stock_quantity'] ?? 0))
        : max(0, (int)($product['stock_quantity'] ?? 0));
    if ($stock <= 0) {
        throw new RuntimeException('This product is out of stock.');
    }

    $unitPrice = $variant !== null
        ? max(0.0, (float)($variant['price'] ?? 0))
        : max(0.0, (float)($product['price'] ?? 0));

    $images = is_array($product['images'] ?? null) ? $product['images'] : [];
    $image = '';
    if (count($images) > 0) {
        $image = trim((string)$images[0]);
    }

    return [
        'product_id' => (int)$product['id'],
        'variant_id' => $selectedVariantId,
        'product_name' => trim((string)($product['name'] ?? 'Product')),
        'variant_name' => $variantName,
        'sku' => $sku,
        'unit_price' => $unitPrice,
        'stock_quantity' => $stock,
        'image' => $image,
        'detail_url' => shop_product_detail_url($product),
    ];
}

function shop_cart_add_item(PDO $pdo, int $productId, int $quantity = 1, int $variantId = 0): array
{
    $quantity = max(1, min(99, $quantity));
    $candidate = shop_fetch_cart_candidate($pdo, $productId, $variantId);

    $lineKey = shop_cart_line_key((int)$candidate['product_id'], (int)$candidate['variant_id']);
    $cart = shop_cart_storage();
    $existingQty = isset($cart[$lineKey]) ? max(0, (int)$cart[$lineKey]['quantity']) : 0;

    $maxStock = max(1, (int)$candidate['stock_quantity']);
    $nextQty = min($maxStock, $existingQty + $quantity);
    if ($nextQty <= 0) {
        throw new RuntimeException('Unable to add this product to cart.');
    }

    $cart[$lineKey] = [
        'product_id' => (int)$candidate['product_id'],
        'variant_id' => (int)$candidate['variant_id'],
        'quantity' => $nextQty,
        'added_at' => gmdate('c'),
    ];
    shop_cart_save_storage($cart);

    return [
        'line_key' => $lineKey,
        'quantity' => $nextQty,
        'product_id' => (int)$candidate['product_id'],
        'variant_id' => (int)$candidate['variant_id'],
        'product_name' => (string)$candidate['product_name'],
        'variant_name' => (string)$candidate['variant_name'],
        'unit_price' => (float)$candidate['unit_price'],
        'image' => (string)$candidate['image'],
        'detail_url' => (string)$candidate['detail_url'],
        'cart_count' => shop_cart_item_count(),
    ];
}

function shop_cart_set_quantity(PDO $pdo, string $lineKey, int $quantity): void
{
    $cart = shop_cart_storage();
    if (!isset($cart[$lineKey]) || !is_array($cart[$lineKey])) {
        return;
    }

    $line = $cart[$lineKey];
    $productId = max(0, (int)($line['product_id'] ?? 0));
    $variantId = max(0, (int)($line['variant_id'] ?? 0));
    if ($productId <= 0) {
        unset($cart[$lineKey]);
        shop_cart_save_storage($cart);
        return;
    }

    if ($quantity <= 0) {
        unset($cart[$lineKey]);
        shop_cart_save_storage($cart);
        return;
    }

    $candidate = shop_fetch_cart_candidate($pdo, $productId, $variantId);
    $maxStock = max(1, (int)$candidate['stock_quantity']);
    $cart[$lineKey]['quantity'] = max(1, min(99, min($quantity, $maxStock)));
    shop_cart_save_storage($cart);
}

function shop_cart_remove_line(string $lineKey): void
{
    $cart = shop_cart_storage();
    if (isset($cart[$lineKey])) {
        unset($cart[$lineKey]);
        shop_cart_save_storage($cart);
    }
}

function shop_cart_clear(): void
{
    shop_start_session();
    unset($_SESSION[SHOP_CART_SESSION_KEY]);
    shop_close_session_if_active();
}

function shop_cart_details(PDO $pdo): array
{
    $cart = shop_cart_storage();
    $items = [];
    $subtotal = 0.0;
    $quantityTotal = 0;
    $changed = false;

    foreach ($cart as $lineKey => $line) {
        $productId = max(0, (int)($line['product_id'] ?? 0));
        $variantId = max(0, (int)($line['variant_id'] ?? 0));
        $quantity = max(1, (int)($line['quantity'] ?? 1));

        if ($productId <= 0) {
            unset($cart[$lineKey]);
            $changed = true;
            continue;
        }

        try {
            $candidate = shop_fetch_cart_candidate($pdo, $productId, $variantId);
        } catch (Throwable) {
            unset($cart[$lineKey]);
            $changed = true;
            continue;
        }

        $maxStock = max(1, (int)$candidate['stock_quantity']);
        $quantity = min($quantity, $maxStock);
        if ($quantity !== (int)$line['quantity']) {
            $cart[$lineKey]['quantity'] = $quantity;
            $changed = true;
        }

        $unitPrice = (float)$candidate['unit_price'];
        $lineTotal = round($unitPrice * $quantity, 2);
        $subtotal += $lineTotal;
        $quantityTotal += $quantity;

        $items[] = [
            'line_key' => $lineKey,
            'product_id' => (int)$candidate['product_id'],
            'variant_id' => (int)$candidate['variant_id'],
            'product_name' => (string)$candidate['product_name'],
            'variant_name' => (string)$candidate['variant_name'],
            'sku' => (string)$candidate['sku'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
            'stock_quantity' => $maxStock,
            'image' => (string)$candidate['image'],
            'detail_url' => (string)$candidate['detail_url'],
        ];
    }

    if ($changed) {
        shop_cart_save_storage($cart);
    }

    return [
        'items' => $items,
        'subtotal' => round($subtotal, 2),
        'item_count' => $quantityTotal,
        'line_count' => count($items),
    ];
}

function shop_shipping_methods(): array
{
    return [
        'standard' => [
            'label' => 'Standard Delivery',
            'fee' => 3000.0,
            'eta' => '3-5 days',
            'shipment_status' => 'preparing',
        ],
        'express' => [
            'label' => 'Express Delivery',
            'fee' => 7000.0,
            'eta' => 'Next day',
            'shipment_status' => 'shipped',
        ],
        'pickup' => [
            'label' => 'Store Pickup',
            'fee' => 0.0,
            'eta' => 'Same day pickup',
            'shipment_status' => 'preparing',
        ],
    ];
}

function shop_checkout_default_form(): array
{
    return [
        'full_name' => '',
        'email' => '',
        'phone' => '',
        'address_line1' => '',
        'address_line2' => '',
        'city' => '',
        'township' => '',
        'state' => '',
        'country' => 'Myanmar',
        'postal_code' => '',
        'customer_note' => '',
        'payment_method' => 'cod',
        'payment_transaction_id' => '',
        'shipping_method' => 'standard',
    ];
}

function shop_payment_methods(): array
{
    return [
        'cod' => 'Cash on Delivery',
        'kbzpay' => 'KBZPay',
        'wavepay' => 'WavePay',
        'ayapay' => 'AYA Pay',
        'cbpay' => 'CB Pay',
    ];
}

function shop_manual_payment_seed_rows(): array
{
    return [
        [
            'name' => 'KBZPay',
            'code' => 'kbzpay',
            'account_name' => 'Myint Myat Aung',
            'account_phone' => '******7400',
            'account_number' => null,
            'qr_image' => '../Assect/images/payment-qr/kbzpay.png',
            'instructions' => 'Transfer the exact order total and upload your payment slip.',
            'sort_order' => 10,
        ],
        [
            'name' => 'WavePay',
            'code' => 'wavepay',
            'account_name' => 'LuvShop Myanmar',
            'account_phone' => '09 400 000 002',
            'account_number' => null,
            'qr_image' => '../Assect/images/payment-qr/wavepay.png',
            'instructions' => 'Transfer the exact order total and upload your payment slip.',
            'sort_order' => 20,
        ],
        [
            'name' => 'AYA Pay',
            'code' => 'ayapay',
            'account_name' => 'LuvShop Myanmar',
            'account_phone' => '09 400 000 003',
            'account_number' => 'AYA-000000003',
            'qr_image' => '../Assect/images/payment-qr/ayapay.png',
            'instructions' => 'Transfer the exact order total and upload your payment slip.',
            'sort_order' => 30,
        ],
        [
            'name' => 'CB Pay',
            'code' => 'cbpay',
            'account_name' => 'LuvShop Myanmar',
            'account_phone' => '09 400 000 004',
            'account_number' => 'CB-000000004',
            'qr_image' => '../Assect/images/payment-qr/cbpay.png',
            'instructions' => 'Transfer the exact order total and upload your payment slip.',
            'sort_order' => 40,
        ],
    ];
}

function shop_ensure_checkout_tables(PDO $pdo): void
{
    if (shop_can_skip_schema_bootstrap()) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS orders (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            order_number VARCHAR(50) NOT NULL,
            user_id BIGINT UNSIGNED NULL,
            subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            shipping_fee DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            order_status ENUM('pending','confirmed','processing','shipped','delivered','cancelled','refunded') NOT NULL DEFAULT 'pending',
            payment_status ENUM('unpaid','paid','failed','refunded','partial_refund') NOT NULL DEFAULT 'unpaid',
            shipping_status ENUM('not_shipped','preparing','shipped','in_transit','delivered','returned') NOT NULL DEFAULT 'not_shipped',
            payment_method VARCHAR(50) NULL,
            customer_note TEXT NULL,
            placed_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_orders_order_number (order_number),
            KEY idx_orders_user_id (user_id),
            KEY idx_orders_status (order_status),
            KEY idx_orders_payment_status (payment_status),
            KEY idx_orders_shipping_status (shipping_status),
            KEY idx_orders_placed_at (placed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    if (!shop_table_has_column($pdo, 'orders', 'payment_method')) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) NULL AFTER shipping_status");
    }
    $pdo->exec("ALTER TABLE orders MODIFY payment_status VARCHAR(40) NOT NULL DEFAULT 'unpaid'");

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS order_items (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NULL,
            product_variant_id BIGINT UNSIGNED NULL,
            product_name VARCHAR(200) NOT NULL,
            variant_name VARCHAR(200) NULL,
            sku VARCHAR(100) NULL,
            quantity INT UNSIGNED NOT NULL DEFAULT 1,
            unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            total_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_order_items_order_id (order_id),
            KEY idx_order_items_product_id (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS shipments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL,
            courier_name VARCHAR(150) NULL,
            tracking_number VARCHAR(150) NULL,
            shipping_address JSON NOT NULL,
            shipping_fee DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            status ENUM('preparing','shipped','in_transit','delivered','returned','cancelled') NOT NULL DEFAULT 'preparing',
            shipped_at TIMESTAMP NULL DEFAULT NULL,
            delivered_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_shipments_order_id (order_id),
            KEY idx_shipments_status (status),
            KEY idx_shipments_courier_name (courier_name),
            KEY idx_shipments_tracking_number (tracking_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    shop_ensure_checkout_schema_columns($pdo);
    shop_ensure_payment_tables($pdo);
    shop_mark_schema_bootstrap_checked();
}

function shop_schema_bootstrap_cache_file(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . '.shop_schema_bootstrap.cache';
}

function shop_can_skip_schema_bootstrap(): bool
{
    $cacheFile = shop_schema_bootstrap_cache_file();
    if (!is_file($cacheFile)) {
        return false;
    }

    $lastCheckedAt = (int)@filemtime($cacheFile);
    return $lastCheckedAt > 0 && (time() - $lastCheckedAt) < 43200;
}

function shop_mark_schema_bootstrap_checked(): void
{
    @touch(shop_schema_bootstrap_cache_file());
}

function shop_ensure_checkout_schema_columns(PDO $pdo): void
{
    static $completed = false;
    if ($completed) {
        return;
    }
    $completed = true;

    $columns = [
        'orders' => [
            'order_number' => 'ALTER TABLE orders ADD COLUMN order_number VARCHAR(50) NULL',
            'user_id' => 'ALTER TABLE orders ADD COLUMN user_id BIGINT UNSIGNED NULL',
            'subtotal' => 'ALTER TABLE orders ADD COLUMN subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00',
            'discount_amount' => 'ALTER TABLE orders ADD COLUMN discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00',
            'tax_amount' => 'ALTER TABLE orders ADD COLUMN tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00',
            'shipping_fee' => 'ALTER TABLE orders ADD COLUMN shipping_fee DECIMAL(12,2) NOT NULL DEFAULT 0.00',
            'total_amount' => 'ALTER TABLE orders ADD COLUMN total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00',
            'order_status' => "ALTER TABLE orders ADD COLUMN order_status VARCHAR(40) NOT NULL DEFAULT 'pending'",
            'payment_status' => "ALTER TABLE orders ADD COLUMN payment_status VARCHAR(40) NOT NULL DEFAULT 'unpaid'",
            'shipping_status' => "ALTER TABLE orders ADD COLUMN shipping_status VARCHAR(40) NOT NULL DEFAULT 'not_shipped'",
            'payment_method' => 'ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) NULL',
            'customer_note' => 'ALTER TABLE orders ADD COLUMN customer_note TEXT NULL',
            'placed_at' => 'ALTER TABLE orders ADD COLUMN placed_at TIMESTAMP NULL DEFAULT NULL',
            'created_at' => 'ALTER TABLE orders ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'ALTER TABLE orders ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ],
        'order_items' => [
            'order_id' => 'ALTER TABLE order_items ADD COLUMN order_id BIGINT UNSIGNED NOT NULL DEFAULT 0',
            'product_id' => 'ALTER TABLE order_items ADD COLUMN product_id BIGINT UNSIGNED NULL',
            'product_variant_id' => 'ALTER TABLE order_items ADD COLUMN product_variant_id BIGINT UNSIGNED NULL',
            'product_name' => "ALTER TABLE order_items ADD COLUMN product_name VARCHAR(200) NOT NULL DEFAULT ''",
            'variant_name' => 'ALTER TABLE order_items ADD COLUMN variant_name VARCHAR(200) NULL',
            'sku' => 'ALTER TABLE order_items ADD COLUMN sku VARCHAR(100) NULL',
            'quantity' => 'ALTER TABLE order_items ADD COLUMN quantity INT UNSIGNED NOT NULL DEFAULT 1',
            'unit_price' => 'ALTER TABLE order_items ADD COLUMN unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00',
            'total_price' => 'ALTER TABLE order_items ADD COLUMN total_price DECIMAL(12,2) NOT NULL DEFAULT 0.00',
            'created_at' => 'ALTER TABLE order_items ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
        ],
        'shipments' => [
            'order_id' => 'ALTER TABLE shipments ADD COLUMN order_id BIGINT UNSIGNED NOT NULL DEFAULT 0',
            'courier_name' => 'ALTER TABLE shipments ADD COLUMN courier_name VARCHAR(150) NULL',
            'tracking_number' => 'ALTER TABLE shipments ADD COLUMN tracking_number VARCHAR(150) NULL',
            'shipping_address' => 'ALTER TABLE shipments ADD COLUMN shipping_address JSON NULL',
            'shipping_fee' => 'ALTER TABLE shipments ADD COLUMN shipping_fee DECIMAL(12,2) NOT NULL DEFAULT 0.00',
            'status' => "ALTER TABLE shipments ADD COLUMN status VARCHAR(40) NOT NULL DEFAULT 'preparing'",
            'shipped_at' => 'ALTER TABLE shipments ADD COLUMN shipped_at TIMESTAMP NULL DEFAULT NULL',
            'delivered_at' => 'ALTER TABLE shipments ADD COLUMN delivered_at TIMESTAMP NULL DEFAULT NULL',
            'created_at' => 'ALTER TABLE shipments ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'ALTER TABLE shipments ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ],
    ];

    foreach ($columns as $tableName => $tableColumns) {
        if (!shop_schema_table_exists_fresh($pdo, $tableName)) {
            continue;
        }

        foreach ($tableColumns as $columnName => $alterSql) {
            if (shop_schema_column_exists_fresh($pdo, $tableName, $columnName)) {
                continue;
            }

            try {
                $pdo->exec($alterSql);
            } catch (Throwable) {
                // Old local schemas can be inconsistent; the concrete query will
                // report any remaining non-recoverable mismatch.
            }
        }
    }
}

function shop_ensure_payment_tables(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS payment_methods (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            code VARCHAR(50) NOT NULL,
            account_name VARCHAR(150) NOT NULL,
            account_phone VARCHAR(50) NULL,
            account_number VARCHAR(100) NULL,
            qr_image VARCHAR(255) NULL,
            instructions TEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_payment_methods_code (code),
            KEY idx_payment_methods_active_sort (is_active, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS payment_slips (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            payment_method_id BIGINT UNSIGNED NOT NULL,
            slip_image VARCHAR(255) NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            sender_name VARCHAR(255) NULL,
            sender_phone VARCHAR(50) NULL,
            transaction_id VARCHAR(255) NULL,
            transferred_at DATETIME NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'pending',
            reviewed_by BIGINT UNSIGNED NULL,
            reviewed_at DATETIME NULL,
            admin_note TEXT NULL,
            reject_reason TEXT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_payment_slips_order_status (order_id, status),
            KEY idx_payment_slips_method_created (payment_method_id, created_at),
            KEY idx_payment_slips_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS payments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL,
            payment_method VARCHAR(50) NULL,
            payment_provider VARCHAR(120) NULL,
            transaction_id VARCHAR(255) NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            currency VARCHAR(10) NOT NULL DEFAULT 'MMK',
            status VARCHAR(30) NOT NULL DEFAULT 'pending',
            paid_at DATETIME NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_payments_order (order_id),
            KEY idx_payments_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    shop_ensure_payment_schema_columns($pdo);

    $insert = $pdo->prepare(
        'INSERT INTO payment_methods (name, code, account_name, account_phone, account_number, qr_image, instructions, is_active, sort_order, created_at, updated_at)
         VALUES (:name, :code, :account_name, :account_phone, :account_number, :qr_image, :instructions, 1, :sort_order, NOW(), NOW())
         ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            account_name = VALUES(account_name),
            account_phone = VALUES(account_phone),
            account_number = VALUES(account_number),
            qr_image = VALUES(qr_image),
            instructions = VALUES(instructions),
            is_active = 1,
            sort_order = VALUES(sort_order),
            updated_at = NOW()'
    );

    foreach (shop_manual_payment_seed_rows() as $row) {
        $insert->execute([
            ':name' => $row['name'],
            ':code' => $row['code'],
            ':account_name' => $row['account_name'],
            ':account_phone' => $row['account_phone'],
            ':account_number' => $row['account_number'],
            ':qr_image' => $row['qr_image'],
            ':instructions' => $row['instructions'],
            ':sort_order' => $row['sort_order'],
        ]);
    }
}

function shop_schema_column_exists_fresh(PDO $pdo, string $tableName, string $columnName): bool
{
    static $columnsByTable = [];

    $tableKey = strtolower(trim($tableName));
    $columnKey = strtolower(trim($columnName));
    if ($tableKey === '' || $columnKey === '') {
        return false;
    }

    if (!array_key_exists($tableKey, $columnsByTable)) {
        $columnsByTable[$tableKey] = [];
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
            return false;
        }

        try {
            $statement = $pdo->query('SHOW COLUMNS FROM `' . $tableName . '`');
            $rows = $statement->fetchAll();
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $field = strtolower(trim((string)($row['Field'] ?? '')));
                    if ($field !== '') {
                        $columnsByTable[$tableKey][] = $field;
                    }
                }
            }
            $columnsByTable[$tableKey] = array_values(array_unique($columnsByTable[$tableKey]));
        } catch (Throwable) {
            $columnsByTable[$tableKey] = [];
        }
    }

    return in_array($columnKey, $columnsByTable[$tableKey], true);
}

function shop_schema_table_exists_fresh(PDO $pdo, string $tableName): bool
{
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
        return false;
    }

    try {
        $statement = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($tableName));
        return $statement->fetchColumn() !== false;
    } catch (Throwable) {
        return false;
    }
}

function shop_ensure_payment_schema_columns(PDO $pdo): void
{
    static $completed = false;
    if ($completed) {
        return;
    }
    $completed = true;

    $columns = [
        'payment_methods' => [
            'created_at' => 'ALTER TABLE payment_methods ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'ALTER TABLE payment_methods ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ],
        'payment_slips' => [
            'sender_name' => 'ALTER TABLE payment_slips ADD COLUMN sender_name VARCHAR(255) NULL',
            'sender_phone' => 'ALTER TABLE payment_slips ADD COLUMN sender_phone VARCHAR(50) NULL',
            'transaction_id' => 'ALTER TABLE payment_slips ADD COLUMN transaction_id VARCHAR(255) NULL',
            'transferred_at' => 'ALTER TABLE payment_slips ADD COLUMN transferred_at DATETIME NULL',
            'status' => "ALTER TABLE payment_slips ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT 'pending'",
            'reviewed_by' => 'ALTER TABLE payment_slips ADD COLUMN reviewed_by BIGINT UNSIGNED NULL',
            'reviewed_at' => 'ALTER TABLE payment_slips ADD COLUMN reviewed_at DATETIME NULL',
            'admin_note' => 'ALTER TABLE payment_slips ADD COLUMN admin_note TEXT NULL',
            'reject_reason' => 'ALTER TABLE payment_slips ADD COLUMN reject_reason TEXT NULL',
            'created_at' => 'ALTER TABLE payment_slips ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'ALTER TABLE payment_slips ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ],
        'payments' => [
            'payment_method' => 'ALTER TABLE payments ADD COLUMN payment_method VARCHAR(50) NULL',
            'payment_provider' => 'ALTER TABLE payments ADD COLUMN payment_provider VARCHAR(120) NULL',
            'transaction_id' => 'ALTER TABLE payments ADD COLUMN transaction_id VARCHAR(255) NULL',
            'amount' => 'ALTER TABLE payments ADD COLUMN amount DECIMAL(12,2) NOT NULL DEFAULT 0.00',
            'currency' => "ALTER TABLE payments ADD COLUMN currency VARCHAR(10) NOT NULL DEFAULT 'MMK'",
            'status' => "ALTER TABLE payments ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT 'pending'",
            'paid_at' => 'ALTER TABLE payments ADD COLUMN paid_at DATETIME NULL',
            'created_at' => 'ALTER TABLE payments ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'ALTER TABLE payments ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ],
    ];

    foreach ($columns as $tableName => $tableColumns) {
        if (!shop_schema_table_exists_fresh($pdo, $tableName)) {
            continue;
        }

        foreach ($tableColumns as $columnName => $alterSql) {
            if (shop_schema_column_exists_fresh($pdo, $tableName, $columnName)) {
                continue;
            }

            try {
                $pdo->exec($alterSql);
            } catch (Throwable) {
                // Existing local databases may have partial manual edits; keep checkout usable
                // and let the next concrete query surface any non-recoverable schema issue.
            }
        }
    }
}

function shop_payment_method_records(PDO $pdo): array
{
    shop_ensure_payment_tables($pdo);

    $rows = $pdo->query(
        'SELECT id, name, code, account_name, account_phone, account_number, qr_image, instructions
         FROM payment_methods
         WHERE is_active = 1
         ORDER BY sort_order ASC, name ASC'
    )->fetchAll();

    return is_array($rows) ? $rows : [];
}

function shop_is_manual_payment_method(string $paymentMethod): bool
{
    return $paymentMethod !== 'cod';
}

function shop_checkout_validate(array $input): array
{
    $form = shop_checkout_default_form();
    foreach ($form as $key => $defaultValue) {
        if (array_key_exists($key, $input)) {
            $form[$key] = trim((string)$input[$key]);
        }
    }

    $errors = [];
    if ($form['full_name'] === '') {
        $errors[] = 'Full name is required.';
    }
    if ($form['email'] === '' || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }
    if ($form['phone'] === '') {
        $errors[] = 'Phone is required.';
    }
    if ($form['address_line1'] === '') {
        $errors[] = 'Delivery address is required.';
    }
    if ($form['city'] === '') {
        $errors[] = 'City is required.';
    }

    $paymentMethods = shop_payment_methods();
    if (!isset($paymentMethods[$form['payment_method']])) {
        $errors[] = 'Unsupported payment method.';
    }

    $shippingMethods = shop_shipping_methods();
    if (!isset($shippingMethods[$form['shipping_method']])) {
        $errors[] = 'Unsupported shipping method.';
    }

    if (count($errors) > 0) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }

    return $form;
}

function shop_generate_order_number(PDO $pdo): string
{
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $candidate = 'ORD-' . gmdate('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $statement = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE order_number = :order_number');
        $statement->execute([':order_number' => $candidate]);
        if ((int)$statement->fetchColumn() === 0) {
            return $candidate;
        }
    }

    return 'ORD-' . gmdate('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
}

function shop_encode_shipping_payload(array $payload): string
{
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return is_string($json) ? $json : '{}';
}

function shop_null_if_empty(string $value): ?string
{
    $trimmed = trim($value);
    return $trimmed === '' ? null : $trimmed;
}

function shop_resolve_checkout_user_id(PDO $pdo, array $form): ?int
{
    $currentUser = shop_current_user();
    if (is_array($currentUser)) {
        $sessionUserId = max(0, (int)($currentUser['id'] ?? 0));
        if ($sessionUserId > 0) {
            return $sessionUserId;
        }

        $firebaseUid = trim((string)($currentUser['firebase_uid'] ?? ''));
        if ($firebaseUid !== '' && shop_table_exists($pdo, 'users')) {
            $findByUid = $pdo->prepare('SELECT id FROM users WHERE firebase_uid = :firebase_uid ORDER BY id ASC LIMIT 1');
            $findByUid->execute([':firebase_uid' => $firebaseUid]);
            $row = $findByUid->fetch();
            if (is_array($row) && (int)($row['id'] ?? 0) > 0) {
                return (int)$row['id'];
            }
        }

        $sessionEmail = strtolower(trim((string)($currentUser['email'] ?? '')));
        if ($sessionEmail !== '' && filter_var($sessionEmail, FILTER_VALIDATE_EMAIL) && shop_table_exists($pdo, 'users')) {
            $findByEmail = $pdo->prepare('SELECT id FROM users WHERE email = :email ORDER BY id ASC LIMIT 1');
            $findByEmail->execute([':email' => $sessionEmail]);
            $row = $findByEmail->fetch();
            if (is_array($row) && (int)($row['id'] ?? 0) > 0) {
                return (int)$row['id'];
            }
        }

        return null;
    }

    if (!shop_table_exists($pdo, 'users')) {
        return null;
    }

    $email = strtolower(trim((string)($form['email'] ?? '')));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    $find = $pdo->prepare('SELECT id FROM users WHERE email = :email ORDER BY id ASC LIMIT 1');
    $find->execute([':email' => $email]);
    $existing = $find->fetch();
    if (is_array($existing) && (int)($existing['id'] ?? 0) > 0) {
        return (int)$existing['id'];
    }

    $firebaseUid = 'guest:' . bin2hex(random_bytes(16));
    $insert = $pdo->prepare(
        'INSERT INTO users (firebase_uid, name, email, phone, role, is_active, can_manage_products, can_manage_orders, can_manage_users, can_manage_coupons, can_manage_reports, email_verified, created_at, updated_at)
         VALUES (:firebase_uid, :name, :email, :phone, :role, 1, 0, 0, 0, 0, 0, 0, NOW(), NOW())'
    );

    try {
        $insert->execute([
            ':firebase_uid' => $firebaseUid,
            ':name' => trim((string)($form['full_name'] ?? '')),
            ':email' => $email,
            ':phone' => trim((string)($form['phone'] ?? '')),
            ':role' => 'customer',
        ]);
        return (int)$pdo->lastInsertId();
    } catch (Throwable) {
        $find->execute([':email' => $email]);
        $retry = $find->fetch();
        if (is_array($retry) && (int)($retry['id'] ?? 0) > 0) {
            return (int)$retry['id'];
        }
        return null;
    }
}

function shop_place_order(PDO $pdo, array $formInput, ?array $slipFile = null): array
{
    if (!shop_user_is_logged_in()) {
        throw new RuntimeException('Please log in before checkout.');
    }

    shop_ensure_checkout_tables($pdo);
    $form = shop_checkout_validate($formInput);
    $cart = shop_cart_details($pdo);
    if (count($cart['items']) === 0) {
        throw new RuntimeException('Your cart is empty.');
    }

    $shippingMethods = shop_shipping_methods();
    $shipping = $shippingMethods[$form['shipping_method']];
    $shippingFee = round((float)$shipping['fee'], 2);
    $subtotal = round((float)$cart['subtotal'], 2);
    $discountAmount = 0.0;
    $taxAmount = 0.0;
    $total = round($subtotal - $discountAmount + $taxAmount + $shippingFee, 2);

    $paymentMethod = $form['payment_method'];
    $isManualPayment = shop_is_manual_payment_method($paymentMethod);
    $paymentStatus = $isManualPayment ? 'payment_pending_review' : 'unpaid';
    $shippingStatus = $shippingFee > 0 ? 'preparing' : 'not_shipped';
    $shipmentStatus = (string)($shipping['shipment_status'] ?? 'preparing');
    $shipmentStatus = in_array($shipmentStatus, ['preparing', 'shipped', 'in_transit', 'delivered', 'returned', 'cancelled'], true)
        ? $shipmentStatus
        : 'preparing';
    $orderStatus = $isManualPayment ? 'pending' : 'confirmed';
    $manualPaymentMethodRecord = null;
    $slipUpload = null;
    if ($isManualPayment) {
        $transactionId = trim((string)($form['payment_transaction_id'] ?? ''));
        if ($transactionId === '') {
            throw new InvalidArgumentException('Transaction number is required for prepaid payment.');
        }

        $methodStatement = $pdo->prepare('SELECT id, name, code FROM payment_methods WHERE code = :code AND is_active = 1 LIMIT 1');
        $methodStatement->execute([':code' => $paymentMethod]);
        $methodRow = $methodStatement->fetch();
        if (!is_array($methodRow)) {
            throw new InvalidArgumentException('Selected payment method is not available.');
        }

        $manualPaymentMethodRecord = $methodRow;
        $slipUpload = shop_prepare_checkout_slip_upload($slipFile);
    }

    $userId = shop_resolve_checkout_user_id($pdo, $form);
    $orderNumber = shop_generate_order_number($pdo);
    $placedAt = gmdate('Y-m-d H:i:s');

    $shippingPayload = [
        'full_name' => $form['full_name'],
        'phone' => $form['phone'],
        'address_line1' => $form['address_line1'],
        'address_line2' => $form['address_line2'],
        'city' => $form['city'],
        'township' => $form['township'],
        'state' => $form['state'],
        'country' => $form['country'],
        'postal_code' => $form['postal_code'],
    ];

    $pdo->beginTransaction();
    $uploadedFilesystemPath = null;
    try {
        $orderStatement = $pdo->prepare(
            'INSERT INTO orders (
                order_number, user_id, subtotal, discount_amount, tax_amount, shipping_fee, total_amount,
                order_status, payment_status, shipping_status, payment_method, customer_note, placed_at, created_at, updated_at
            ) VALUES (
                :order_number, :user_id, :subtotal, :discount_amount, :tax_amount, :shipping_fee, :total_amount,
                :order_status, :payment_status, :shipping_status, :payment_method, :customer_note, :placed_at, NOW(), NOW()
            )'
        );
        $orderStatement->execute([
            ':order_number' => $orderNumber,
            ':user_id' => $userId,
            ':subtotal' => $subtotal,
            ':discount_amount' => $discountAmount,
            ':tax_amount' => $taxAmount,
            ':shipping_fee' => $shippingFee,
            ':total_amount' => $total,
            ':order_status' => $orderStatus,
            ':payment_status' => $paymentStatus,
            ':shipping_status' => $shippingStatus,
            ':payment_method' => $paymentMethod,
            ':customer_note' => shop_null_if_empty($form['customer_note']),
            ':placed_at' => $placedAt,
        ]);

        $orderId = (int)$pdo->lastInsertId();
        if ($orderId <= 0) {
            throw new RuntimeException('Failed to create order.');
        }

        if ($isManualPayment && is_array($manualPaymentMethodRecord) && is_array($slipUpload)) {
            $storedSlip = shop_store_checkout_slip_file($orderId, $slipUpload);
            $uploadedSlipPath = $storedSlip['relative_path'];
            $uploadedFilesystemPath = $storedSlip['filesystem_path'];

            $slipStatement = $pdo->prepare(
                'INSERT INTO payment_slips (
                    order_id, user_id, payment_method_id, slip_image, amount, transaction_id, status, created_at, updated_at
                ) VALUES (
                    :order_id, :user_id, :payment_method_id, :slip_image, :amount, :transaction_id, :status, NOW(), NOW()
                )'
            );
            $slipStatement->execute([
                ':order_id' => $orderId,
                ':user_id' => $userId,
                ':payment_method_id' => (int)$manualPaymentMethodRecord['id'],
                ':slip_image' => $uploadedSlipPath,
                ':amount' => $total,
                ':transaction_id' => trim((string)$form['payment_transaction_id']),
                ':status' => 'pending',
            ]);
        }

        $itemStatement = $pdo->prepare(
            'INSERT INTO order_items (
                order_id, product_id, product_variant_id, product_name, variant_name, sku, quantity, unit_price, total_price, created_at
            ) VALUES (
                :order_id, :product_id, :product_variant_id, :product_name, :variant_name, :sku, :quantity, :unit_price, :total_price, NOW()
            )'
        );

        $stockStatement = $pdo->prepare(
            'UPDATE product_variants
             SET stock_quantity = CASE WHEN stock_quantity >= :quantity_check THEN stock_quantity - :quantity_subtract ELSE 0 END,
                 updated_at = NOW()
             WHERE id = :variant_id'
        );

        foreach ($cart['items'] as $line) {
            $itemStatement->execute([
                ':order_id' => $orderId,
                ':product_id' => (int)$line['product_id'],
                ':product_variant_id' => (int)$line['variant_id'] > 0 ? (int)$line['variant_id'] : null,
                ':product_name' => (string)$line['product_name'],
                ':variant_name' => shop_null_if_empty((string)$line['variant_name']),
                ':sku' => shop_null_if_empty((string)$line['sku']),
                ':quantity' => (int)$line['quantity'],
                ':unit_price' => (float)$line['unit_price'],
                ':total_price' => (float)$line['line_total'],
            ]);

            $variantId = (int)$line['variant_id'];
            if ($variantId > 0 && shop_table_exists($pdo, 'product_variants')) {
                $stockStatement->execute([
                    ':quantity_check' => (int)$line['quantity'],
                    ':quantity_subtract' => (int)$line['quantity'],
                    ':variant_id' => $variantId,
                ]);
            }
        }

        if (shop_table_exists($pdo, 'shipments')) {
            $shippedAt = in_array($shipmentStatus, ['shipped', 'in_transit', 'delivered'], true) ? $placedAt : null;
            $deliveredAt = $shipmentStatus === 'delivered' ? $placedAt : null;
            $shipmentStatement = $pdo->prepare(
                'INSERT INTO shipments (
                    order_id, courier_name, tracking_number, shipping_address, shipping_fee, status, shipped_at, delivered_at, created_at, updated_at
                ) VALUES (
                    :order_id, :courier_name, :tracking_number, :shipping_address, :shipping_fee, :status, :shipped_at, :delivered_at, NOW(), NOW()
                )'
            );
            $shipmentStatement->execute([
                ':order_id' => $orderId,
                ':courier_name' => null,
                ':tracking_number' => null,
                ':shipping_address' => shop_encode_shipping_payload($shippingPayload),
                ':shipping_fee' => $shippingFee,
                ':status' => $shipmentStatus,
                ':shipped_at' => $shippedAt,
                ':delivered_at' => $deliveredAt,
            ]);
        }

        $pdo->commit();
        shop_cart_clear();

        return [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'total_amount' => $total,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'order_status' => $orderStatus,
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if (is_string($uploadedFilesystemPath) && $uploadedFilesystemPath !== '') {
            @unlink($uploadedFilesystemPath);
        }
        throw $exception;
    }
}

function shop_fetch_payment_order(PDO $pdo, int $orderId): array
{
    shop_ensure_payment_tables($pdo);
    $currentUser = shop_current_user();
    if (!is_array($currentUser)) {
        throw new RuntimeException('Please log in to view this order.');
    }

    $userId = max(0, (int)($currentUser['id'] ?? 0));
    if ($userId <= 0) {
        throw new RuntimeException('Your account session is not ready. Please log in again.');
    }

    $statement = $pdo->prepare(
        'SELECT o.*, COALESCE(u.name, "") AS customer_name, COALESCE(u.email, "") AS customer_email
         FROM orders o
         LEFT JOIN users u ON u.id = o.user_id
         WHERE o.id = :id AND o.user_id = :user_id
         LIMIT 1'
    );
    $statement->execute([
        ':id' => $orderId,
        ':user_id' => $userId,
    ]);
    $order = $statement->fetch();

    if (!is_array($order)) {
        throw new RuntimeException('Order was not found for your account.');
    }

    return $order;
}

function shop_prepare_checkout_slip_upload(?array $file): array
{
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Payment slip image is required for prepaid payment.');
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new InvalidArgumentException('Invalid uploaded payment slip image.');
    }

    if ((int)($file['size'] ?? 0) > 4096 * 1024) {
        throw new InvalidArgumentException('Payment slip image must be 4MB or smaller.');
    }

    $imageInfo = @getimagesize($tmpName);
    $mime = is_array($imageInfo) ? (string)($imageInfo['mime'] ?? '') : '';
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($extensions[$mime])) {
        throw new InvalidArgumentException('Payment slip image must be JPG, PNG, or WebP.');
    }

    return [
        'tmp_name' => $tmpName,
        'extension' => $extensions[$mime],
    ];
}

function shop_store_checkout_slip_file(int $orderId, array $upload): array
{
    $uploadDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'payment-slips';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Could not create payment slip upload folder.');
    }

    $filename = 'slip-' . $orderId . '-' . bin2hex(random_bytes(8)) . '.' . (string)$upload['extension'];
    $destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file((string)$upload['tmp_name'], $destination)) {
        throw new RuntimeException('Could not save uploaded payment slip.');
    }

    return [
        'relative_path' => '../uploads/payment-slips/' . $filename,
        'filesystem_path' => $destination,
    ];
}

function shop_fetch_order_payment_slips(PDO $pdo, int $orderId): array
{
    shop_ensure_payment_tables($pdo);

    $statement = $pdo->prepare(
        'SELECT ps.*, pm.name AS payment_method_name, pm.code AS payment_method_code
         FROM payment_slips ps
         INNER JOIN payment_methods pm ON pm.id = ps.payment_method_id
         WHERE ps.order_id = :order_id
         ORDER BY ps.created_at DESC, ps.id DESC'
    );
    $statement->execute([':order_id' => $orderId]);
    $rows = $statement->fetchAll();

    return is_array($rows) ? $rows : [];
}

function shop_store_payment_slip(PDO $pdo, array $order, array $input, array $file): void
{
    shop_ensure_payment_tables($pdo);

    if ((string)($order['payment_status'] ?? '') === 'paid') {
        throw new RuntimeException('This order has already been paid.');
    }

    $pending = $pdo->prepare('SELECT COUNT(*) FROM payment_slips WHERE order_id = :order_id AND status = :status');
    $pending->execute([
        ':order_id' => (int)$order['id'],
        ':status' => 'pending',
    ]);
    if ((int)$pending->fetchColumn() > 0) {
        throw new RuntimeException('A payment slip is already waiting for admin review.');
    }

    $paymentMethodId = max(0, (int)($input['payment_method_id'] ?? 0));
    $methodStatement = $pdo->prepare('SELECT id FROM payment_methods WHERE id = :id AND is_active = 1 LIMIT 1');
    $methodStatement->execute([':id' => $paymentMethodId]);
    if (!is_array($methodStatement->fetch())) {
        throw new InvalidArgumentException('Please select a valid payment method.');
    }

    $amount = round((float)($input['amount'] ?? 0), 2);
    if ($amount < 1) {
        throw new InvalidArgumentException('Amount is required.');
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Please upload a payment slip image.');
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new InvalidArgumentException('Invalid uploaded slip image.');
    }

    if ((int)($file['size'] ?? 0) > 4096 * 1024) {
        throw new InvalidArgumentException('Slip image must be 4MB or smaller.');
    }

    $imageInfo = @getimagesize($tmpName);
    $mime = is_array($imageInfo) ? (string)($imageInfo['mime'] ?? '') : '';
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($extensions[$mime])) {
        throw new InvalidArgumentException('Slip image must be JPG, PNG, or WebP.');
    }

    $uploadDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'payment-slips';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Could not create payment slip upload folder.');
    }

    $filename = 'slip-' . (int)$order['id'] . '-' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
    $destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($tmpName, $destination)) {
        throw new RuntimeException('Could not save uploaded payment slip.');
    }

    $relativePath = '../uploads/payment-slips/' . $filename;
    $transferredAt = trim((string)($input['transferred_at'] ?? ''));
    $transferredAtValue = $transferredAt !== '' ? date('Y-m-d H:i:s', strtotime($transferredAt)) : null;

    $pdo->beginTransaction();
    $uploadedSlipPath = null;
    $uploadedFilesystemPath = null;
    try {
        $insert = $pdo->prepare(
            'INSERT INTO payment_slips (
                order_id, user_id, payment_method_id, slip_image, amount, sender_name, sender_phone,
                transaction_id, transferred_at, status, created_at, updated_at
            ) VALUES (
                :order_id, :user_id, :payment_method_id, :slip_image, :amount, :sender_name, :sender_phone,
                :transaction_id, :transferred_at, :status, NOW(), NOW()
            )'
        );
        $insert->execute([
            ':order_id' => (int)$order['id'],
            ':user_id' => (int)$order['user_id'],
            ':payment_method_id' => $paymentMethodId,
            ':slip_image' => $relativePath,
            ':amount' => $amount,
            ':sender_name' => shop_null_if_empty((string)($input['sender_name'] ?? '')),
            ':sender_phone' => shop_null_if_empty((string)($input['sender_phone'] ?? '')),
            ':transaction_id' => shop_null_if_empty((string)($input['transaction_id'] ?? '')),
            ':transferred_at' => $transferredAtValue,
            ':status' => 'pending',
        ]);

        $update = $pdo->prepare(
            'UPDATE orders
             SET payment_status = :payment_status, order_status = :order_status, updated_at = NOW()
             WHERE id = :id'
        );
        $update->execute([
            ':payment_status' => 'payment_pending_review',
            ':order_status' => 'pending',
            ':id' => (int)$order['id'],
        ]);

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        @unlink($destination);
        throw $exception;
    }
}

function shop_current_user_database_id(PDO $pdo): int
{
    $currentUser = shop_current_user();
    if (!is_array($currentUser)) {
        return 0;
    }

    $sessionUserId = max(0, (int)($currentUser['id'] ?? 0));
    if ($sessionUserId > 0) {
        return $sessionUserId;
    }

    if (!shop_table_exists($pdo, 'users')) {
        return 0;
    }

    $firebaseUid = trim((string)($currentUser['firebase_uid'] ?? ''));
    if ($firebaseUid !== '') {
        $statement = $pdo->prepare('SELECT id FROM users WHERE firebase_uid = :firebase_uid ORDER BY id ASC LIMIT 1');
        $statement->execute([':firebase_uid' => $firebaseUid]);
        $row = $statement->fetch();
        if (is_array($row) && (int)($row['id'] ?? 0) > 0) {
            return (int)$row['id'];
        }
    }

    $email = strtolower(trim((string)($currentUser['email'] ?? '')));
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $statement = $pdo->prepare('SELECT id FROM users WHERE email = :email ORDER BY id ASC LIMIT 1');
        $statement->execute([':email' => $email]);
        $row = $statement->fetch();
        if (is_array($row) && (int)($row['id'] ?? 0) > 0) {
            return (int)$row['id'];
        }
    }

    return 0;
}

function shop_delivery_steps(): array
{
    return [
        'placed' => [
            'label' => 'Order Placed',
            'description' => 'We received your order.',
        ],
        'confirmed' => [
            'label' => 'Confirmed',
            'description' => 'Your order is confirmed.',
        ],
        'preparing' => [
            'label' => 'Preparing',
            'description' => 'Your items are being packed.',
        ],
        'shipped' => [
            'label' => 'Shipped',
            'description' => 'Your order has left our shop.',
        ],
        'in_transit' => [
            'label' => 'On The Way',
            'description' => 'Delivery is moving to your address.',
        ],
        'delivered' => [
            'label' => 'Delivered',
            'description' => 'Your order has been delivered.',
        ],
    ];
}

function shop_delivery_step_index(array $order): int
{
    $orderStatus = (string)($order['order_status'] ?? '');
    $shippingStatus = (string)($order['shipping_status'] ?? '');

    if ($orderStatus === 'cancelled' || $shippingStatus === 'returned') {
        return 0;
    }

    if ($shippingStatus === 'delivered' || $orderStatus === 'delivered') {
        return 5;
    }
    if ($shippingStatus === 'in_transit') {
        return 4;
    }
    if ($shippingStatus === 'shipped' || $orderStatus === 'shipped') {
        return 3;
    }
    if ($shippingStatus === 'preparing' || $orderStatus === 'processing') {
        return 2;
    }
    if (in_array($orderStatus, ['confirmed', 'processing'], true)) {
        return 1;
    }

    return 0;
}

function shop_fetch_customer_orders(PDO $pdo): array
{
    shop_ensure_checkout_tables($pdo);

    $userId = shop_current_user_database_id($pdo);
    if ($userId <= 0 || !shop_table_exists($pdo, 'orders')) {
        return [];
    }

    $shipmentJoin = shop_table_exists($pdo, 'shipments')
        ? 'LEFT JOIN shipments s ON s.order_id = o.id'
        : '';
    $shipmentColumns = shop_table_exists($pdo, 'shipments')
        ? 's.courier_name, s.tracking_number, s.status AS shipment_status, s.shipped_at, s.delivered_at,'
        : 'NULL AS courier_name, NULL AS tracking_number, NULL AS shipment_status, NULL AS shipped_at, NULL AS delivered_at,';

    $statement = $pdo->prepare(
        "SELECT
            o.id,
            o.order_number,
            o.total_amount,
            o.order_status,
            o.payment_status,
            o.shipping_status,
            o.payment_method,
            COALESCE(o.placed_at, o.created_at) AS order_datetime,
            {$shipmentColumns}
            (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
         FROM orders o
         {$shipmentJoin}
         WHERE o.user_id = :user_id
         ORDER BY COALESCE(o.placed_at, o.created_at) DESC, o.id DESC
         LIMIT 50"
    );
    $statement->execute([':user_id' => $userId]);
    $rows = $statement->fetchAll();

    return is_array($rows) ? $rows : [];
}

function shop_fetch_customer_order(PDO $pdo, int $orderId): ?array
{
    if ($orderId <= 0) {
        return null;
    }

    $orders = shop_fetch_customer_orders($pdo);
    foreach ($orders as $order) {
        if ((int)($order['id'] ?? 0) === $orderId) {
            return $order;
        }
    }

    return null;
}
