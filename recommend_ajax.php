<?php
// ============================================================
// recommend_ajax.php — AI Outfit + Cultural Recommendation Engine
// Uses Gemini AI + product DB for smart recommendations
// ============================================================
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'admin/include/config.php';
ob_clean();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('ngrok-skip-browser-warning: true');

define('GEMINI_KEY',   'AIzaSyADBOJ_gAqqvw_9jLoSea7Ssfrd6IWUN4g');
define('GEMINI_MODEL', 'gemini-2.5-flash-lite');
define('GEMINI_URL',   'https://generativelanguage.googleapis.com/v1beta/models/'.GEMINI_MODEL.':generateContent?key='.GEMINI_KEY);

$action = trim($_POST['action'] ?? '');

// ── Helper: fetch products ─────────────────────────────────────
function getProducts(string $whereClause, int $limit = 12): array {
    global $connection_database;
    $sql = "SELECT id, name, slug, price, dis_amount, price_after_discount, image, stock, sale
            FROM products
            WHERE stock = 'In Stock' AND is_active != 0 AND $whereClause
            ORDER BY ai_score DESC, RAND()
            LIMIT $limit";
    $res = $connection_database->query($sql);
    $rows = [];
    if ($res) while ($r = $res->fetch_assoc()) {
        // image field can be comma-separated — take first image only
        $imgs   = array_filter(array_map('trim', explode(',', $r['image'] ?? '')));
        $first  = !empty($imgs) ? array_values($imgs)[0] : '';
        $second = (count($imgs) > 1) ? array_values($imgs)[1] : $first;

        $price = (float)$r['price'];
        $final = ($r['price_after_discount'] && (float)$r['price_after_discount'] < $price)
                   ? (float)$r['price_after_discount'] : $price;

        $rows[] = [
            'id'         => (int)$r['id'],
            'name'       => htmlspecialchars($r['name']),
            'slug'       => $r['slug'],
            'price'      => $price,
            'final'      => $final,
            'hasDiscount'=> ($final < $price),
            'img1'       => $first,
            'img2'       => $second,
            'sale'       => htmlspecialchars($r['sale'] ?? ''),
        ];
    }
    return $rows;
}

// ── Helper: call Gemini ───────────────────────────────────────
function askGemini(string $prompt): string {
    $payload = ['contents' => [['parts' => [['text' => $prompt]]]]];
    $ch = curl_init(GEMINI_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($raw, true);
    return trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
}

// ── OCCASION RECOMMENDATION ──────────────────────────────────
if ($action === 'occasion') {
    $occasion = strtolower(trim($_POST['occasion'] ?? ''));
    if (!$occasion) { echo json_encode(['success'=>false,'message'=>'No occasion selected.']); exit; }

    $occ = $connection_database->real_escape_string($occasion);
    $where = "(occasion_tag = '$occ' OR FIND_IN_SET('$occ', occasion_tag))";

    $products = getProducts($where, 12);

    // Gemini AI style advice
    $names = implode(', ', array_slice(array_map(fn($p) => $p['name'], $products), 0, 5));
    $advice = askGemini(
        "You are a Pakistani fashion stylist. Give 3-4 sentences of outfit styling advice for a {$occasion} occasion in Pakistan. ".
        "Mention what fabrics, colors, and styles work best. Be specific about Pakistani fashion like shalwar kameez, lawn suits, etc. ".
        "Some available products include: $names. Keep it friendly and under 80 words."
    );

    echo json_encode([
        'success'   => true,
        'products'  => $products,
        'advice'    => $advice,
        'occasion'  => $occasion,
        'count'     => count($products),
    ]);
    exit;
}

// ── CULTURAL RECOMMENDATION ───────────────────────────────────
if ($action === 'cultural') {
    $culture = strtolower(trim($_POST['culture'] ?? ''));
    if (!$culture) { echo json_encode(['success'=>false,'message'=>'No culture selected.']); exit; }

    $cult = $connection_database->real_escape_string($culture);
    // Exact match only — avoids showing unrelated products tagged 'balochi,pakistani' etc.
    $where = "cultural_tag = '$cult'";
    $products = getProducts($where, 12);

    $cultureInfo = [
        'punjabi'  => ['name'=>'Punjabi','desc'=>'Punjab province of Pakistan','attire'=>'Shalwar Kameez, Phulkari dupatta, Khussa shoes, vibrant colors'],
        'sindhi'   => ['name'=>'Sindhi','desc'=>'Sindh province of Pakistan','attire'=>'Ajrak prints, Sindhi topi, bright embroidery, mirror work'],
        'balochi'  => ['name'=>'Balochi','desc'=>'Balochistan province of Pakistan','attire'=>'Balochi dress with intricate embroidery, mirror work, rich colors'],
        'pashtun'  => ['name'=>'Pashtun','desc'=>'KPK province of Pakistan','attire'=>'Khet partug, Peshawari chappal, Perahan tunban'],
        'kashmiri' => ['name'=>'Kashmiri','desc'=>'Azad Kashmir Pakistan','attire'=>'Pheran, Kashmiri shawl, embroidered kurta'],
    ];

    $info   = $cultureInfo[$culture] ?? ['name'=>ucfirst($culture),'desc'=>'Pakistani culture','attire'=>'Traditional Pakistani attire'];
    $names  = implode(', ', array_slice(array_map(fn($p) => $p['name'], $products), 0, 5));

    $advice = askGemini(
        "You are a Pakistani cultural fashion expert. Give 3-4 sentences describing traditional {$info['name']} clothing from {$info['desc']}. ".
        "Mention typical {$info['attire']}. Then suggest how these modern products fit the cultural style: $names. ".
        "Be enthusiastic and culturally respectful. Under 90 words."
    );

    echo json_encode([
        'success'   => true,
        'products'  => $products,
        'advice'    => $advice,
        'culture'   => $culture,
        'info'      => $info,
        'count'     => count($products),
    ]);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action.']);
