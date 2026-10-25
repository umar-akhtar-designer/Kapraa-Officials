<?php
/* Voice Ops API for kapraa.pk — mapped to the real database (2026-10-17). */

define('DB_HOST', 'localhost');
define('DB_NAME', 'u167258434_kapraa');
define('DB_USER', 'u167258434_kapraa');
define('DB_PASS', 'Kapraa@2026');
define('API_KEY', 'voiceops_a7f3b91c44e2d806f5a1b2c3d4e5f607');
define('CURRENCY_CODE', 'PKR');

function json_out($status, $body): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($body);
    exit;
}

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC],
            );
        } catch (PDOException $e) {
            json_out(500, ['error' => 'DB connection failed']);
        }
    }
    return $pdo;
}

/* Status values your website uses → the 4 the operator app understands. */
$SHIPPED   = ['shipped', 'dispatched', 'in_transit', 'out_for_delivery', 'transit'];
$DELIVERED = ['delivered', 'completed', 'complete'];
$CANCELLED = ['cancelled', 'canceled', 'returned', 'refund', 'refunded', 'rt-ship'];

function status_of($raw): string {
    global $SHIPPED, $DELIVERED, $CANCELLED;
    $s = strtolower(trim((string)$raw));
    if (in_array($s, $SHIPPED, true)) return 'shipped';
    if (in_array($s, $DELIVERED, true)) return 'delivered';
    if (in_array($s, $CANCELLED, true)) return 'cancelled';
    return 'pending';
}

function sql_status($word): array {
    global $SHIPPED, $DELIVERED, $CANCELLED;
    if ($word === 'shipped') return [$SHIPPED, true];
    if ($word === 'delivered') return [$DELIVERED, true];
    if ($word === 'cancelled') return [$CANCELLED, true];
    /* pending = everything the other three did NOT accept */
    $all = array_merge($SHIPPED, $DELIVERED, $CANCELLED);
    return [$all, false];
}

/* One orders row → app JSON. Items already fetched by row id. */
function map_order(array $r, array $items = []): array {
    $status = status_of($r['order_status']);
    return [
        'id' => (string)$r['order_number'],
        'createdAt' => (string)$r['created_at'],
        'status' => $status,
        'total' => ['amount' => round((float)$r['total_amount'], 2), 'currency' => CURRENCY_CODE],
        'customerName' => (string)($r['customer_name'] ?? ''),
        'customerPhone' => (string)($r['phone'] ?? ''),
        'customerEmail' => (string)($r['email'] ?? ''),
        'trackingNumber' => $status === 'shipped' ? (string)($r['voice_tracking'] ?? '') : null,
        'cancelledReason' => $status === 'cancelled' ? (string)($r['voice_cancel_reason'] ?? '') : null,
        'items' => $items,
    ];
}

function map_items(array $rows): array {
    $line = [];
    foreach ($rows as $li) {
        $line[] = [
            'sku' => (string)($li['product_id'] ?? ''),
            'name' => (string)($li['product_name'] ?? ''),
            'quantity' => (int)($li['quantity'] ?? 1),
            'unitPrice' => ['amount' => round((float)($li['price'] ?? 0), 2), 'currency' => CURRENCY_CODE],
        ];
    }
    return $line;
}

function map_product(array $r): array {
    return [
        'sku' => (string)($r['sku'] !== '' ? $r['sku'] : $r['id']),
        'name' => (string)$r['name'],
        'stock' => (int)$r['stock'],
        'price' => ['amount' => round((float)($r['price_after_discount'] ?: $r['price']), 2), 'currency' => CURRENCY_CODE],
    ];
}

/* Key: header x-api-key OR ?key=... in the URL (browser-friendly). */
$secret = $_SERVER['HTTP_X_API_KEY'] ?? ($_GET['key'] ?? '');
if (!hash_equals(API_KEY, $secret)) {
    json_out(401, ['error' => 'Missing or wrong x-api-key header']);
}

/* One-time light migration: two nullable columns for the voice app only. */
$has = db()->query("SHOW COLUMNS FROM orders LIKE 'voice_tracking'");
if (!$has || $has->rowCount() === 0) {
    try {
        db()->exec("ALTER TABLE orders ADD COLUMN voice_tracking VARCHAR(100) DEFAULT NULL");
        db()->exec("ALTER TABLE orders ADD COLUMN voice_cancel_reason VARCHAR(255) DEFAULT NULL");
    } catch (PDOException $e) { /* column likely already added */ }
}

$script_name = basename($_SERVER['SCRIPT_NAME']);
$uri = strtok($_SERVER['REQUEST_URI'] ?? '', '?') ?: '';
$pos = stripos($uri, $script_name);
if ($pos !== false) { $uri = substr($uri, $pos + strlen($script_name)); }
$path = trim($uri, '/');
$parts = $path === '' ? [] : explode('/', $path);
$roots = ['orders', 'products', 'sales', 'tables'];
foreach ($parts as $i => $seg) {
    if (in_array($seg, $roots, true)) { $parts = array_slice($parts, $i); break; }
}
$method = $_SERVER['REQUEST_METHOD'];
$body = $method === 'POST' ? (json_decode((string)file_get_contents('php://input'), true) ?? []) : [];

function fetch_items($rowId): array {
    $q = db()->prepare('SELECT product_id, product_name, quantity, price FROM order_items WHERE order_id = ?');
    $q->execute([$rowId]);
    return $q->fetchAll();
}

try {
    /* GET /tables — columns diagnostic. */
    if ($parts === ['tables']) {
        $all = db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $rows = ['__all_tables' => $all];
        foreach (['orders', 'order_items', 'products'] as $t) {
            $q = db()->prepare('SHOW COLUMNS FROM `' . str_replace('`', '', $t) . '`');
            $q->execute();
            $rows[$t] = array_column($q->fetchAll(), 'Field');
        }
        $rows['orders']['__columns_voice'] = ['voice_tracking', 'voice_cancel_reason'];
        json_out(200, $rows);
    }

    /* GET /orders?status=pending&limit=20 */
    if ($parts === ['orders'] && $method === 'GET') {
        $limit = min(max((int)($_GET['limit'] ?? 20), 1), 100);
        $word = $_GET['status'] ?? '';
        $out = [];
        $params = [];
        $sql = 'SELECT id, order_number, created_at, order_status, total_amount, customer_name, phone, email, voice_tracking, voice_cancel_reason FROM orders';
        if ($word !== '') {
            [$values, $isIn] = sql_status($word);
            $instr = implode(',', array_fill(0, count($values), '?'));
            $sql .= $isIn ? " WHERE order_status IN ($instr)" : " WHERE order_status NOT IN ($instr)";
            $params = $values;
        }
        $sql .= " ORDER BY created_at DESC LIMIT $limit";
        $q = db()->prepare($sql);
        $q->execute($params);
        foreach ($q->fetchAll() as $row) {
            $out[] = map_order($row, fetch_items($row['id']));
        }
        json_out(200, ['items' => $out]);
    }

    /* GET /orders/{number} — order_number is what the shop shows people. */
    if (count($parts) === 2 && $parts[0] === 'orders' && $method === 'GET') {
        $q = db()->prepare('SELECT id, order_number, created_at, order_status, total_amount, customer_name, phone, email, voice_tracking, voice_cancel_reason FROM orders WHERE order_number = ? LIMIT 1');
        $q->execute([$parts[1]]);
        $row = $q->fetch();
        if (!$row) json_out(404, ['error' => "Order {$parts[1]} not found"]);
        json_out(200, map_order($row, fetch_items($row['id'])));
    }

    /* POST /orders/{number}/fulfill */
    if (count($parts) === 3 && $parts[0] === 'orders' && $parts[2] === 'fulfill' && $method === 'POST') {
        $q = db()->prepare('SELECT * FROM orders WHERE order_number = ? LIMIT 1');
        $q->execute([$parts[1]]);
        $order = $q->fetch();
        if (!$order) json_out(404, ['error' => "Order {$parts[1]} not found"]);
        if (status_of($order['order_status']) === 'cancelled') {
            json_out(409, ['error' => 'Cannot fulfil a cancelled order']);
        }
        $tracking = (string)($body['tracking'] ?? 'MANUAL-' . time());
        $q = db()->prepare("UPDATE orders SET order_status = 'shipped', voice_tracking = ?, updated_at = NOW() WHERE id = ?");
        $q->execute([$tracking, $order['id']]);
        $q = db()->prepare('SELECT id, order_number, created_at, order_status, total_amount, customer_name, phone, email, voice_tracking, voice_cancel_reason FROM orders WHERE id = ? LIMIT 1');
        $q->execute([$order['id']]);
        json_out(200, map_order($q->fetch(), fetch_items($order['id'])));
    }

    /* POST /orders/{number}/cancel */
    if (count($parts) === 3 && $parts[0] === 'orders' && $parts[2] === 'cancel' && $method === 'POST') {
        $q = db()->prepare('SELECT * FROM orders WHERE order_number = ? LIMIT 1');
        $q->execute([$parts[1]]);
        $order = $q->fetch();
        if (!$order) json_out(404, ['error' => "Order {$parts[1]} not found"]);
        if (status_of($order['order_status']) === 'shipped') {
            json_out(409, ['error' => 'Shipped order cannot be cancelled']);
        }
        $reason = (string)($body['reason'] ?? 'No reason given');
        $q = db()->prepare("UPDATE orders SET order_status = 'cancelled', voice_cancel_reason = ?, updated_at = NOW() WHERE id = ?");
        $q->execute([$reason, $order['id']]);
        $q = db()->prepare('SELECT id, order_number, created_at, order_status, total_amount, customer_name, phone, email, voice_tracking, voice_cancel_reason FROM orders WHERE id = ? LIMIT 1');
        $q->execute([$order['id']]);
        json_out(200, map_order($q->fetch(), fetch_items($order['id'])));
    }

    /* GET /products?lowStockBelow=10 */
    if ($parts === ['products'] && $method === 'GET') {
        $where = '';
        $params = [];
        if (isset($_GET['lowStockBelow'])) {
            $where = 'WHERE stock <= ?';
            $params[] = (int)$_GET['lowStockBelow'];
        }
        $q = db()->prepare("SELECT id, name, sku, stock, price, price_after_discount FROM products $where ORDER BY stock ASC LIMIT 250");
        $q->execute($params);
        json_out(200, ['items' => array_map('map_product', $q->fetchAll())]);
    }

    /* POST /products/{sku}/restock — finds by sku, falls back to product id. */
    if (count($parts) === 3 && $parts[0] === 'products' && $parts[2] === 'restock' && $method === 'POST') {
        $q = db()->prepare('SELECT id, name, sku, stock, price, price_after_discount FROM products WHERE sku = ? LIMIT 1');
        $q->execute([$parts[1]]);
        $product = $q->fetch();
        if (!$product) {
            $q = db()->prepare('SELECT id, name, sku, stock, price, price_after_discount FROM products WHERE id = ? LIMIT 1');
            $q->execute([(int)$parts[1]]);
            $product = $q->fetch();
        }
        if (!$product) json_out(404, ['error' => "No product with SKU {$parts[1]}"]);
        $quantity = $body['quantity'] ?? null;
        if (!is_numeric($quantity) || $quantity < 0) {
            json_out(400, ['error' => 'quantity must be a non-negative number']);
        }
        $newStock = (($body['mode'] ?? 'set') === 'add')
            ? (int)$product['stock'] + (int)$quantity
            : (int)$quantity;
        $q = db()->prepare('UPDATE products SET stock = ? WHERE id = ?');
        $q->execute([$newStock, $product['id']]);
        $product['stock'] = $newStock;
        json_out(200, map_product($product));
    }

    /* GET /sales/today */
    if ($parts === ['sales', 'today'] && $method === 'GET') {
        $today = date('Y-m-d');
        $q = db()->prepare(
            "SELECT COALESCE(SUM(total_amount),0) AS revenue, COUNT(*) AS orders
             FROM orders WHERE DATE(created_at) = ?"
        );
        $q->execute([$today]);
        $row = $q->fetch() ?: ['revenue' => 0, 'orders' => 0];
        json_out(200, [
            'date' => $today,
            'revenue' => ['amount' => round((float)$row['revenue'], 2), 'currency' => CURRENCY_CODE],
            'orders' => (int)$row['orders'],
            'topSellers' => [],
        ]);
    }

    json_out(404, ['error' => 'Unknown endpoint']);
} catch (PDOException $e) {
    json_out(500, ['error' => 'Database error']);
}