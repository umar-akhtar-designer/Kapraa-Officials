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
$GEMINI_KEY = 'AIzaSyADBOJ_gAqqvw_9jLoSea7Ssfrd6IWUN4g';

$in           = json_decode(file_get_contents('php://input'), true) ?? [];
$text         = trim($in['text'] ?? '');
$audioBase64  = trim($in['audio_base64'] ?? '');
$audioMime    = trim($in['mime'] ?? 'audio/mp4');

// Normalize mime type — Gemini only supports audio/mp4, not audio/m4a
$mimeMap = ['audio/m4a' => 'audio/mp4', 'audio/x-m4a' => 'audio/mp4', 'audio/mpeg' => 'audio/mp3'];
$audioMime = $mimeMap[$audioMime] ?? $audioMime;

// If audio sent, first transcribe + understand with Gemini multimodal
if ($audioBase64 && !$text) {
    $audioPrompt = "This is a voice search recording from a user shopping at a Pakistani textile/clothing store. The user may be speaking in English, Urdu, or mixed language (Urdu-English). Listen carefully and extract what product they are looking for. Return ONLY valid JSON (no markdown, no extra text):
{\"query\": \"clean search text in English\", \"brand\": \"brand name or null\", \"category\": \"women|men|kids|null\", \"type\": \"kurta|suit|dress|null\", \"color\": \"color or null\", \"keywords\": [\"2-4 keywords\"]}";

    $audioPayload = [
        'contents' => [[
            'parts' => [
                ['text' => $audioPrompt],
                ['inline_data' => ['mime_type' => $audioMime, 'data' => $audioBase64]]
            ]
        ]],
        'generationConfig' => ['temperature' => 0.1, 'maxOutputTokens' => 200]
    ];
    $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$GEMINI_KEY");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true,
        CURLOPT_POSTFIELDS=>json_encode($audioPayload),
        CURLOPT_HTTPHEADER=>['Content-Type: application/json'], CURLOPT_TIMEOUT=>25]);
    $r = curl_exec($ch); curl_close($ch);
    $rd = json_decode($r, true);

    // Check for Gemini error
    if (!empty($rd['error'])) {
        echo json_encode(['success'=>false,'message'=>'Gemini error: '.$rd['error']['message']]);
        exit;
    }

    $raw = $rd['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
    $raw = preg_replace('/```json\s*|```/', '', trim($raw));
    $intent = json_decode(trim($raw), true) ?? [];
    $text = $intent['query'] ?? '';

    // If Gemini returned something but not JSON, use the raw text as query
    if (!$text && strlen(trim($raw)) > 1 && trim($raw) !== '{}') {
        $text = trim(preg_replace('/[^a-zA-Z0-9 ]/', '', $raw));
    }
}

if (!$text && empty($intent)) { echo json_encode(['success'=>false,'message'=>'No voice text']); exit; }

// Use Gemini to understand intent
$prompt = "A user is searching a Pakistani textile/clothing store by voice. Their query is: \"$text\"

Extract search intent and return ONLY valid JSON (no markdown):
{
  \"query\": \"clean search keywords\",
  \"brand\": \"brand name if mentioned or null\",
  \"category\": \"women|men|kids|null\",
  \"type\": \"kurta|suit|dress|null\",
  \"color\": \"color if mentioned or null\",
  \"keywords\": [\"2-4 database search keywords\"]
}

Examples:
- \"show me Maria B dresses\" → brand: \"Maria B\", keywords: [\"Maria B\"]
- \"red lawn suits dikhao\" → color: \"red\", type: \"suit\", keywords: [\"red\", \"lawn\", \"suit\"]
- \"show women kurta\" → category: \"women\", type: \"kurta\", keywords: [\"women\", \"kurta\"]";

$payload = [
    'contents' => [['parts' => [['text' => $prompt]]]],
    'generationConfig' => ['temperature' => 0.1]
];

$ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key=$GEMINI_KEY");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 10,
]);
$response = curl_exec($ch);
curl_close($ch);

$result  = json_decode($response, true);
$rawText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
$rawText = preg_replace('/```json|```/', '', $rawText);
$intent  = json_decode(trim($rawText), true) ?? [];

$keywords = $intent['keywords'] ?? [preg_replace('/[^a-zA-Z0-9 ]/', '', $text)];
if (!empty($intent['brand'])) $keywords[] = $intent['brand'];
if (!empty($intent['color'])) $keywords[] = $intent['color'];
$keywords = array_filter(array_unique($keywords));

$conditions = [];
foreach ($keywords as $kw) {
    $kw = mysqli_real_escape_string($conn, trim($kw));
    if (!$kw) continue;
    $conditions[] = "(p.name LIKE '%$kw%' OR p.category LIKE '%$kw%' OR p.sub_category LIKE '%$kw%' OR p.brand LIKE '%$kw%' OR b.name LIKE '%$kw%')";
}

$products = [];
if (!empty($conditions)) {
    $where = implode(' OR ', $conditions);
    $sql = "SELECT p.id, p.name, p.slug, p.image, p.price, p.price_after_discount, p.dis_amount, p.stock, b.name as brand_name
            FROM products p
            LEFT JOIN brand b ON p.brand = b.id
            WHERE ($where) AND p.image != ''
            LIMIT 20";
    $res = mysqli_query($conn, $sql);
    while ($p = mysqli_fetch_assoc($res)) {
        $imgs              = explode(',', $p['image']);
        $p['image_url']    = $base . '/admin/images/product/' . trim($imgs[0]);
        $p['sale_price']   = $p['price_after_discount'];
        $p['final_price']  = $p['price_after_discount'] ?: $p['price'];
        $p['discount_percent'] = ($p['price'] > 0 && $p['dis_amount'] > 0)
            ? round(($p['dis_amount'] / $p['price']) * 100) : 0;
        $p['stock']        = 'In Stock';
        $products[]        = $p;
    }
}

echo json_encode([
    'success'  => true,
    'intent'   => $intent,
    'query'    => $intent['query'] ?? $text,
    'products' => $products,
    'count'    => count($products),
]);
