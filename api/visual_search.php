<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = (strpos($host,'localhost')!==false||strpos($host,'127.0.0.1')!==false||strpos($host,'192.168.')!==false);
$base    = $isLocal ? 'http://'.$host.'/kapraa' : 'https://'.$host;
if ($isLocal) {
    $conn = mysqli_connect('localhost', 'root', '', 'kapraa');
} else {
    $conn = mysqli_connect('localhost', 'u167258434_kapraa', 'Kapraa@2026', 'u167258434_kapraa');
}
if (!$conn) { echo json_encode(['success'=>false,'message'=>'DB error']); exit; }
$imgDir  = __DIR__ . '/../admin/images/product/';
$GEMINI_KEY = 'AIzaSyADBOJ_gAqqvw_9jLoSea7Ssfrd6IWUN4g';

if (empty($_FILES['image']['tmp_name'])) {
    echo json_encode(['success'=>false,'message'=>'No image uploaded']); exit;
}

$uploadedBytes = file_get_contents($_FILES['image']['tmp_name']);
$uploadedMD5   = md5($uploadedBytes);
$uploadedSize  = strlen($uploadedBytes);

// ── STEP 1: Exact match — compare MD5 + perceptual hash against every product image ──
function pHash($imgPath) {
    // Resize to 8x8 grayscale, return 64-bit string of light/dark pixels
    $info = @getimagesize($imgPath);
    if (!$info) return null;
    $ext = strtolower(pathinfo($imgPath, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'jpg': case 'jpeg': $src = @imagecreatefromjpeg($imgPath); break;
        case 'png':              $src = @imagecreatefrompng($imgPath);  break;
        case 'webp':             $src = @imagecreatefromwebp($imgPath); break;
        case 'gif':              $src = @imagecreatefromgif($imgPath);  break;
        default: return null;
    }
    if (!$src) return null;
    $small = imagecreatetruecolor(8, 8);
    imagecopyresampled($small, $src, 0,0,0,0, 8,8, imagesx($src), imagesy($src));
    imagedestroy($src);
    $bits = '';
    $sum  = 0;
    $pixels = [];
    for ($y = 0; $y < 8; $y++) for ($x = 0; $x < 8; $x++) {
        $c = imagecolorat($small, $x, $y);
        $gray = (($c >> 16 & 0xFF) * 299 + ($c >> 8 & 0xFF) * 587 + ($c & 0xFF) * 114) / 1000;
        $pixels[] = $gray; $sum += $gray;
    }
    imagedestroy($small);
    $avg = $sum / 64;
    foreach ($pixels as $p) $bits .= ($p >= $avg ? '1' : '0');
    return $bits;
}

function hammingDistance($a, $b) {
    $diff = 0;
    for ($i = 0; $i < strlen($a); $i++) if ($a[$i] !== $b[$i]) $diff++;
    return $diff;
}

// Build perceptual hash of uploaded image (save to temp file first)
$tmpExt  = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION)) ?: 'jpg';
$tmpPath = sys_get_temp_dir() . '/vs_upload_' . uniqid() . '.' . $tmpExt;
file_put_contents($tmpPath, $uploadedBytes);
$uploadedPHash = pHash($tmpPath);

$exactProduct = null;
$bestDistance = 999;

// Fetch all products with images
$allProds = mysqli_query($conn,
    "SELECT id, name, slug, image, price, price_after_discount, dis_amount
     FROM products WHERE stock != 'Out Stock' AND image != ''"
);

while ($p = mysqli_fetch_assoc($allProds)) {
    $imgs = explode(',', $p['image']);
    foreach ($imgs as $img) {
        $img  = trim($img);
        $path = $imgDir . $img;
        if (!file_exists($path)) continue;

        // Fast: MD5 exact match
        if (md5_file($path) === $uploadedMD5) {
            $exactProduct = $p;
            $exactProduct['_match'] = 'exact';
            break 2;
        }

        // Perceptual hash — handles resized/re-saved versions
        if ($uploadedPHash) {
            $ph = pHash($path);
            if ($ph) {
                $dist = hammingDistance($uploadedPHash, $ph);
                if ($dist < $bestDistance) {
                    $bestDistance = $dist;
                    if ($dist <= 12) { // ≤12 bit difference = very similar image (handles screenshots/resized)
                        $exactProduct = $p;
                        $exactProduct['_match'] = 'perceptual';
                    }
                }
            }
        }
    }
}

@unlink($tmpPath);

if ($exactProduct) {
    $imgs = explode(',', $exactProduct['image']);
    $exactProduct['image_url']   = $base . '/admin/images/product/' . trim($imgs[0]);
    $exactProduct['final_price'] = ($exactProduct['price_after_discount'] > 0)
                                    ? $exactProduct['price_after_discount'] : $exactProduct['price'];
    $exactProduct['discount_pct']= ($exactProduct['price'] > 0 && $exactProduct['final_price'] < $exactProduct['price'])
                                    ? round((($exactProduct['price'] - $exactProduct['final_price']) / $exactProduct['price']) * 100) : 0;
    echo json_encode([
        'success'       => true,
        'exact_match'   => true,
        'analysis'      => ['type' => 'Exact product found', 'colors' => [], 'keywords' => []],
        'products'      => [$exactProduct],
        'count'         => 1,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ── STEP 2: No exact match — use Gemini AI for similar products ──
$imageData = base64_encode($uploadedBytes);
$mimeType  = $_FILES['image']['type'] ?: 'image/jpeg';

$prompt = "Analyze this image. First check: is this a Pakistani clothing/textile/fashion product image? Return ONLY a valid JSON object (no markdown, no explanation):
{
  \"is_clothing\": true or false,
  \"type\": \"suit|kurta|dress|shalwar kameez|frock|lehenga|dupatta|fabric|other\",
  \"fabric\": \"lawn|linen|khaddar|chiffon|cotton|silk|georgette|karandi|unknown\",
  \"colors\": [\"main color in English, e.g. black, red, white, blue, green, pink, maroon, purple\"],
  \"style\": \"casual|formal|bridal|semi-formal|party\",
  \"pattern\": \"embroidered|printed|plain|digital print|block print\",
  \"gender\": \"women|men|kids\",
  \"keywords\": [\"2-4 short single words to find similar products\"]
}
If the image is NOT clothing (e.g. food, animals, objects, logos, scenery), set is_clothing=false and type=other.";

$payload = [
    'contents' => [[
        'parts' => [
            ['text' => $prompt],
            ['inline_data' => ['mime_type' => $mimeType, 'data' => $imageData]]
        ]
    ]],
    'generationConfig' => ['temperature' => 0.1, 'maxOutputTokens' => 200]
];

$ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key=$GEMINI_KEY");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 15,
]);
$response = curl_exec($ch);
curl_close($ch);

$result   = json_decode($response, true);
$text     = $result['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
$text     = preg_replace('/```json|```/', '', $text);
$analysis = json_decode(trim($text), true) ?? [];

// If not clothing → return no products with clear message
if (isset($analysis['is_clothing']) && $analysis['is_clothing'] === false) {
    echo json_encode([
        'success'     => true,
        'exact_match' => false,
        'not_clothing'=> true,
        'message'     => 'This image does not appear to be a clothing or textile product. Please upload a photo of a dress, suit, kurta, or any clothing item.',
        'analysis'    => $analysis,
        'products'    => [],
        'count'       => 0,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Resolve colors to IDs
$colorIds = [];
foreach (($analysis['colors'] ?? []) as $colorName) {
    $safe = mysqli_real_escape_string($conn, trim($colorName));
    $r    = mysqli_query($conn, "SELECT id FROM color WHERE name LIKE '%$safe%' LIMIT 3");
    while ($row = mysqli_fetch_assoc($r)) $colorIds[] = (int)$row['id'];
}

$conditions = [];
foreach ($colorIds as $cid) {
    $conditions[] = "FIND_IN_SET('$cid', p.color)";
}
if (strpos(strtolower($analysis['pattern'] ?? ''), 'embroid') !== false) {
    $conditions[] = "p.has_embroidery = 1";
}
$fabric = strtolower($analysis['fabric'] ?? '');
if ($fabric && $fabric !== 'unknown') {
    $safe = mysqli_real_escape_string($conn, $fabric);
    $conditions[] = "p.name LIKE '%$safe%'";
}
$stopWords = ['suit','suits','dress','wear','style','design','collection','women','men','kids'];
foreach (($analysis['keywords'] ?? []) as $phrase) {
    foreach (explode(' ', strtolower($phrase)) as $word) {
        $word = trim($word);
        if (strlen($word) >= 4 && !in_array($word, $stopWords)) {
            $safe = mysqli_real_escape_string($conn, $word);
            $conditions[] = "p.name LIKE '%$safe%'";
        }
    }
}

$products = [];
if (!empty($conditions)) {
    $where = implode(' OR ', $conditions);
    $sql   = "SELECT p.id, p.name, p.slug, p.image, p.price, p.price_after_discount, p.dis_amount
              FROM products p
              WHERE p.stock != 'Out Stock' AND p.image != '' AND ($where)
              ORDER BY p.id DESC LIMIT 12";
    $res = mysqli_query($conn, $sql);
    while ($p = mysqli_fetch_assoc($res)) {
        $imgs = explode(',', $p['image']);
        $p['image_url']   = $base . '/admin/images/product/' . trim($imgs[0]);
        $p['final_price'] = ($p['price_after_discount'] > 0) ? $p['price_after_discount'] : $p['price'];
        $p['discount_pct']= ($p['price'] > 0 && $p['final_price'] < $p['price'])
                            ? round((($p['price'] - $p['final_price']) / $p['price']) * 100) : 0;
        $products[] = $p;
    }
}

// If no matching products found — suggest similar by gender/type
if (empty($products)) {
    $gender  = strtolower($analysis['gender'] ?? 'women');
    $type    = strtolower($analysis['type']   ?? '');
    $gSafe   = mysqli_real_escape_string($conn, $gender);
    $tSafe   = mysqli_real_escape_string($conn, $type);

    $fallbackWhere = "p.stock != 'Out Stock' AND p.image != ''";
    $genderJoin    = '';
    if ($gSafe && $gSafe !== 'unknown') {
        $genderJoin    = "LEFT JOIN category cat ON p.category = cat.id LEFT JOIN subcategory sc ON p.sub_category = sc.id";
        $fallbackWhere .= " AND (cat.name LIKE '%$gSafe%' OR sc.name LIKE '%$gSafe%')";
    }
    if ($tSafe && !in_array($tSafe, ['other','unknown','fabric'])) {
        $fallbackWhere .= " AND p.name LIKE '%$tSafe%'";
    }

    $sql = "SELECT p.id, p.name, p.slug, p.image, p.price, p.price_after_discount, p.dis_amount
            FROM products p $genderJoin WHERE $fallbackWhere ORDER BY p.id DESC LIMIT 8";
    $res = mysqli_query($conn, $sql);
    while ($p = mysqli_fetch_assoc($res)) {
        $imgs = explode(',', $p['image']);
        $p['image_url']   = $base . '/admin/images/product/' . trim($imgs[0]);
        $p['final_price'] = ($p['price_after_discount'] > 0) ? $p['price_after_discount'] : $p['price'];
        $p['discount_pct']= ($p['price'] > 0 && $p['final_price'] < $p['price'])
                            ? round((($p['price'] - $p['final_price']) / $p['price']) * 100) : 0;
        $products[] = $p;
    }
    $analysis['_fallback'] = true;
    $analysis['_fallback_reason'] = 'No exact match found. Showing similar ' . $gender . ' products.';
}

echo json_encode([
    'success'     => true,
    'exact_match' => false,
    'analysis'    => $analysis,
    'products'    => $products,
    'count'       => count($products),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
