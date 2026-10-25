<?php
session_start();
// ============================================================
// ai_chat.php  — Kapraa AI  v4.0
// Gemini-first. Real human-like replies. Live DB awareness.
// ============================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ── CONFIG ─────────────────────────────────────────────────
// DB credentials & Gemini key are loaded from central config (not hardcoded here)
require_once __DIR__ . '/admin/include/config.php';     // sets $connection_database + $url_domain
require_once __DIR__ . '/admin/include/secrets.php';    // defines GEMINI_KEY, GEMINI_MODEL, GEMINI_URL

// ── INPUT ──────────────────────────────────────────────────
$in            = json_decode(file_get_contents('php://input'), true) ?? [];
$message       = trim((string)($in['message']      ?? ''));
$history       = (array)($in['history']            ?? []);
$langMode      = strtolower(trim((string)($in['lang_mode']    ?? 'auto')));
$imageBase64   = trim((string)($in['image_base64'] ?? ''));
$imageType     = trim((string)($in['image_type']   ?? 'image/jpeg'));
$msgCount      = max(1, (int)($in['msg_count'] ?? 1));
$userLevel     = strtolower(trim((string)($in['user_level'] ?? 'normal'))); // beginner|normal|advanced
$cloudinaryUrl = trim((string)($in['cloudinary_url'] ?? ''));
$lastProducts  = (array)($in['last_products'] ?? []);  // products last shown in carousel (from frontend)

if ($message === '' && $imageBase64 === '') {
    echo json_encode(['reply' => 'Hi! How can I help?', 'products' => []]); exit;
}

// ── DB — reuse connection from config.php ───────────────────
// $connection_database is already connected via config.php
$db = $connection_database;
if (!$db || $db->connect_error) {
    echo json_encode(['reply' => 'Sorry, technical issue. Try again!', 'products' => []]); exit;
}
$db->set_charset('utf8mb4');

// $url_domain already set by config.php; derive base URL from it
$base = rtrim($url_domain, '/');

// ── IMAGE MODE ─────────────────────────────────────────────
if ($imageBase64 !== '') {
    // If user is ALREADY in an order flow step, stay in the flow —
    // don't jump to image search just because an image was attached
    $currentStep = $_SESSION['ai_cod_step'] ?? null;
    if ($currentStep !== null) {
        // Let order flow handle it below — fall through
    } elseif (detectOrderIntent($message)) {
        // Image + order intent — try URL detection first, then fall through to order flow
        // (product will be found from URL in history; image is informational)
    } else {
        // Normal image search — no order intent
        $result = handleImage($db, $imageBase64, $imageType, $message, $langMode, $base, $cloudinaryUrl, $userLevel);
        respond($result);
    }
}

// ── LIVE STORE DATA (fresh every request) ──────────────────
$store = getStoreData($db);

// ── LOAD CATEGORIES & SUBCATEGORIES INTO STORE CONTEXT ─────
$store['categories']    = getCategories($db);
$store['subcategories'] = getSubcategories($db);

// ── DETECT IF NEEDS PRODUCTS ───────────────────────────────
$needsProducts   = needsProducts($message);
$brand           = detectBrand($db, $message);
$externalBrand   = detectExternalBrand($message); // brand mentioned but NOT in our store
$isShowAllQuery  = isShowAllQuery($message);       // "all products", "sara collection", etc.
$isProjectQuery  = isProjectQuery($message);       // teacher/evaluator asking about the FYP
$isNewProductQ   = isNewProductQuery($message);    // "what did I just add?", "show latest", etc.
$products        = [];

// ── Detect specific product inquiry (URL / price check / detail question) ──
$isSpecificProductQuery = isSpecificProductQuery($message);

// ── Suppress carousel during active order flow ─────────────
$inOrderFlow = isset($_SESSION['ai_cod_step']);

// ── Recently added products query ──────────────────────────
if ($isNewProductQ && !$inOrderFlow) {
    $products = getRecentProducts($db, $base, 8);
}
// Only search if: no external brand, not a specific product detail/price query,
// not a project/FYP question, not a "new product" query (already handled above),
// and user is NOT currently in the COD order flow
elseif (($needsProducts || $brand || $isShowAllQuery) && !$externalBrand && !$isSpecificProductQuery && !$inOrderFlow && !$isProjectQuery) {
    $products = searchProducts($db, $message, $brand, $base, $isShowAllQuery);
}

// ── SPECIFIC PRODUCT: always look up REAL price from DB ────
// When user shares a URL or asks about a specific item, fetch actual product data
// so Gemini gives the EXACT price instead of hallucinating one.
$specificProduct = null;
if ($isSpecificProductQuery && !$inOrderFlow) {
    $specificProduct = findProductFromContext($db, $message, $history);

    // Fallback: if we couldn't find the product via URL/text but the frontend
    // sent back the last carousel products, use those as the context so the AI
    // can answer price/detail follow-ups like "what's the price of this dress?"
    if (!$specificProduct && !empty($lastProducts)) {
        if (count($lastProducts) === 1) {
            // Single product shown — use it directly as the specific product
            $lp = $lastProducts[0];
            $specificProduct = [
                'id'    => (int)($lp['id'] ?? 0),
                'name'  => html_entity_decode((string)($lp['name'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'price' => (float)($lp['price_after_discount'] ?? $lp['price'] ?? 0),
                'image' => trim(explode(',', (string)($lp['image'] ?? ''))[0] ?? ''),
                'slug'  => (string)($lp['slug'] ?? ''),
            ];
        }
        // If multiple products were shown, pass $lastProducts to askGemini
        // so it can list prices for all of them (handled inside askGemini)
    }
}

// ── Brand volume info — load when brand is detected ────────
if ($brand) {
    $store['brand_volumes'] = getBrandVolumes($db, (int)$brand['id'], $brand['name']);
}

// ── COD ORDER FLOW (runs BEFORE Gemini) ────────────────────
$orderFlowResult = handleOrderFlow($db, $message, $history, $imageBase64, $imageType);
if ($orderFlowResult !== null) {
    respond($orderFlowResult);
}

// ── GEMINI REPLY ───────────────────────────────────────────
$reply = askGemini($message, $history, $langMode, $products, $brand, $store, $msgCount, $userLevel, $specificProduct, $isShowAllQuery, $lastProducts, $isProjectQuery, $isNewProductQ);

// ── CONTEXT FOR CHIPS ─────────────────────────────────────
$context = getContext($message, $brand, $products);

$mainResponse = ['reply' => $reply, 'products' => $products, 'context' => $context];
if ($cloudinaryUrl !== '') {
    $mainResponse['cloudinary_url'] = $cloudinaryUrl;
}
respond($mainResponse);

// ===========================================================
// RESPOND & EXIT
// ===========================================================
function respond($arr) {
    echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ===========================================================
// DETECT ORDER INTENT
// Returns true if the user wants to place an order
// ===========================================================
function detectOrderIntent($msg) {
    $m = mb_strtolower(trim($msg));

    // ── "All/sab products" signals → browsing, NOT ordering a single item ──
    // e.g. "place order of all khaddi suits" is really "show me all khaddi suits"
    $browsingSignals = [
        'all ', 'sab ', 'sara ', 'poora ', 'tamam ', 'saara ',
        'all suits', 'all products', 'sab suits', 'sab products',
        'total price', 'kitna total', 'how much total', 'total kitna',
        'price list', 'price of all', 'show all', 'dikhao sab',
    ];
    foreach ($browsingSignals as $bs) {
        if (mb_strpos($m, $bs) !== false) return false; // browsing intent, let Gemini handle
    }

    $keywords = [
        'order karna', 'order karo', 'order karna hai', 'order chahiye',
        'mujhe chahiye order', 'book karna', 'book kar', 'book karo',
        'lena hai', 'le lena', 'le lo', 'kharidna', 'kharidna hai',
        'manga', 'mangana',
        'place order', 'place an order', 'place the order',
        'place this order', 'place my order',
        'want to order', 'i want to order',
        'i want to buy', 'want to buy', 'need to buy', 'i need to buy',
        'i want to purchase', 'want to purchase',
        'order please', 'order krna', 'order krna hai', 'order krdena', 'order kr do',
        'order kar do', 'order kardo',
        'buy karna', 'buy karna hai', 'buy karo', 'buy kar do',
        'purchase', 'checkout', 'buy now', 'add to cart',
    ];
    foreach ($keywords as $kw) {
        if (mb_strpos($m, $kw) !== false) return true;
    }
    // "this buy [product]" — app tap-to-order format
    if (preg_match('/^(this|yeh|ye)\s+buy\b/u', $m)) return true;
    // "buy this/it/now" — "buy this Greta by SAFWA..."
    if (preg_match('/\bbuy\s+(this|it|yeh|ye|isey|ise|now)\b/u', $m)) return true;
    // "buy" at end of message
    if (preg_match('/\bbuy\s*[.!?]*$/u', $m)) return true;
    // "place ... order" — "please place order", "place this order"
    if (preg_match('/\bplace\b.{0,20}\border\b/u', $m)) return true;
    // "order this/it/karo" — "order this suit"
    if (preg_match('/\border\s+(this|it|kar|karo|karein|karna|de|yeh|ye)\b/u', $m)) return true;
    // "order" at end of message
    if (preg_match('/\border\s*[.!?]*$/u', $m)) return true;
    // Product URL + buy/order intent
    if (preg_match('/\/product\/[a-z0-9\-]+/i', $m) && preg_match('/\b(order|buy|lena|kharid)\b/i', $m)) return true;
    return false;
}

// ===========================================================
// FIND PRODUCT FROM URL  (slug / ID in any URL format)
// ===========================================================
function findProductFromUrl($db, $message, $history) {
    $allText = $message;
    foreach (array_slice($history, -10) as $h) {
        $allText .= ' ' . ($h['content'] ?? '');
    }

    // /product/some-slug  OR  product-details.php?slug=some-slug
    if (preg_match('/(?:\/product\/|[?&]slug=)([a-z0-9][a-z0-9\-]{2,80})/i', $allText, $m)) {
        $slug = $db->real_escape_string(trim($m[1]));
        $res  = $db->query("SELECT id, name, price_after_discount, image, slug FROM products WHERE slug='$slug' LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            return buildProdArr($row);
        }
    }
    // ?id=N  or  /product/123
    if (preg_match('/[?&\/](?:id|p)=?(\d{1,8})\b/i', $allText, $m)) {
        $pid = (int)$m[1];
        $res = $db->query("SELECT id, name, price_after_discount, image, slug FROM products WHERE id=$pid LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            return buildProdArr($row);
        }
    }
    return null;
}

function buildProdArr(array $row): array {
    $img = trim(explode(',', (string)($row['image'] ?? ''))[0] ?? '');
    return [
        'id'    => (int)$row['id'],
        'name'  => html_entity_decode((string)$row['name'], ENT_QUOTES, 'UTF-8'),
        'price' => (float)($row['price_after_discount'] ?? 0),
        'image' => $img,
        'slug'  => (string)$row['slug'],
    ];
}

// ===========================================================
// FIND PRODUCT FROM TEXT — fuzzy word-score ranking
// Splits user text into meaningful words and scores each product
// ===========================================================
function findProductFromText($db, $text) {
    $text = mb_strtolower(trim($text));
    // Remove stop words
    $stopWords = ['ka','ki','ke','hai','hain','mujhe','meri','mera','please','bhai',
                  'yaar','the','a','an','is','in','on','at','and','or','for','with',
                  'i','me','my','want','need','order','buy','purchase','chahiye','lena'];
    $words = array_filter(
        preg_split('/[\s\-_,\.\/\?&=]+/', $text),
        fn($w) => mb_strlen($w) >= 3 && !in_array($w, $stopWords)
    );
    if (empty($words)) return null;

    // Build LIKE conditions for each meaningful word
    $conditions = [];
    foreach ($words as $w) {
        $safe = $db->real_escape_string($w);
        $conditions[] = "(LOWER(name) LIKE '%{$safe}%' OR LOWER(COALESCE(slug,'')) LIKE '%{$safe}%')";
    }
    $whereClause = implode(' OR ', $conditions);

    $res = $db->query(
        "SELECT id, name, price_after_discount, image, slug,
                " . implode(' + ', array_map(fn($c) => "IF($c, 1, 0)", $conditions)) . " AS score
         FROM products
         WHERE ($whereClause)
         ORDER BY score DESC, LENGTH(name) ASC
         LIMIT 5"
    );
    if (!$res || $res->num_rows === 0) return null;

    $best = $res->fetch_assoc();
    if ((int)($best['score'] ?? 0) < 1) return null;
    return buildProdArr($best);
}

// ===========================================================
// USE GEMINI TO EXTRACT PRODUCT NAME FROM USER DESCRIPTION
// When user says "blue embroidered 3-piece lawn suit"
// Gemini extracts the key searchable terms → we search DB
// ===========================================================
function extractProductTermsWithGemini($userText) {
    $prompt = "The user said: \"{$userText}\"\n\nExtract the product name/type they want to buy from a clothing store. Return ONLY 2-5 keywords (no extra text, no explanation). Example output: embroidered lawn 3 piece";
    $payload = json_encode([
        'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
        'generationConfig' => ['temperature' => 0.1, 'maxOutputTokens' => 30],
    ]);
    $ch = curl_init(GEMINI_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 6,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($resp, true);
    return trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '') ?: '';
}

// ===========================================================
// USE GEMINI VISION TO IDENTIFY PRODUCT FROM IMAGE
// When user uploads a product image in the order flow
// ===========================================================
function identifyProductFromImage($db, $imageBase64, $imageType) {
    $prompt = "This is a clothing product image. Describe it in 5-10 keywords for a search (e.g. 'embroidered lawn kameez 3 piece'). Return ONLY the keywords, nothing else.";
    $payload = json_encode([
        'contents' => [[
            'role' => 'user',
            'parts' => [
                ['text' => $prompt],
                ['inline_data' => ['mime_type' => $imageType, 'data' => $imageBase64]],
            ]
        ]],
        'generationConfig' => ['temperature' => 0.1, 'maxOutputTokens' => 40],
    ]);
    $ch = curl_init(GEMINI_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    $data  = json_decode($resp, true);
    $terms = trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
    if (!$terms) return null;
    return findProductFromText($db, $terms);
}

// ===========================================================
// FIND PRODUCT FROM CONTEXT — tries all strategies
// ===========================================================
function findProductFromContext($db, $message, $history) {
    // Strategy 1: URL / slug / ID in message or history
    $p = findProductFromUrl($db, $message, $history);
    if ($p) return $p;

    // Strategy 2: Fuzzy word matching on current message
    $p = findProductFromText($db, $message);
    if ($p) return $p;

    // Strategy 3: Fuzzy word matching on recent history
    $histText = '';
    foreach (array_slice($history, -6) as $h) {
        $histText .= ' ' . ($h['content'] ?? '');
    }
    if (trim($histText)) {
        $p = findProductFromText($db, $histText);
        if ($p) return $p;
    }

    // Strategy 4: Gemini extracts product keywords → DB search
    $combined = trim($message . ' ' . $histText);
    if (mb_strlen($combined) > 6) {
        $terms = extractProductTermsWithGemini($combined);
        if ($terms) {
            $p = findProductFromText($db, $terms);
            if ($p) return $p;
        }
    }

    return null;
}

// ===========================================================
// VERIFY ADDRESS VIA OPENSTREETMAP NOMINATIM (free, no key)
// Returns true if address found in Pakistan, false otherwise
// ===========================================================
function verifyAddressPakistan($address) {
    $query    = urlencode($address . ', Pakistan');
    $url      = "https://nominatim.openstreetmap.org/search?q={$query}&format=json&countrycodes=pk&limit=1&addressdetails=0";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_HTTPHEADER     => ['User-Agent: Kapraa-AI-Chat/1.0'],
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err || !$res) return false; // Network error — skip verification, accept address
    $data = json_decode($res, true);
    return is_array($data) && count($data) > 0;
}

// ===========================================================
// HANDLE COD ORDER FLOW
// Returns a response array or null (null = not in order flow)
// ===========================================================
function handleOrderFlow($db, $message, $history, $imageBase64 = '', $imageType = 'image/jpeg') {
    $m    = mb_strtolower(trim($message));
    $step = $_SESSION['ai_cod_step'] ?? null;

    // ── Greeting or clearly new topic → always reset flow ────
    // Prevents stale session from intercepting "hello" etc.
    if ($step !== null && preg_match('/^\s*(hi|hello|hey|salam|assalam|aoa|helo|hii|helo|good morning|good evening|good afternoon|good night|asalam|assalamu)\b/i', $message)) {
        unset($_SESSION['ai_cod_step'], $_SESSION['ai_cod_data'], $_SESSION['ai_cod_product']);
        return null; // Let Gemini greet naturally
    }

    // ── Cancel at any step ────────────────────────────────
    if ($step !== null && preg_match('/\b(cancel|band karo|raho ne do|nahi|nai chahiye)\b/i', $message)) {
        unset($_SESSION['ai_cod_step'], $_SESSION['ai_cod_data'], $_SESSION['ai_cod_product']);
        return ['reply' => "Order cancel kar diya! Koi aur help chahiye? 😊", 'products' => [], 'order_placed' => false];
    }

    // ── Off-topic during order flow → reset and let Gemini handle ──
    // If user sends a product/brand question during the order flow, restart
    if ($step !== null && $step !== 'ask_product') {
        $offTopicSignals = ['show', 'find', 'collection', 'brand', 'suit', 'lawn', 'sale', 'dikhao', 'chahiye product', 'search', 'recommend', 'price', 'website'];
        foreach ($offTopicSignals as $sig) {
            if (mb_strpos($m, $sig) !== false) {
                unset($_SESSION['ai_cod_step'], $_SESSION['ai_cod_data'], $_SESSION['ai_cod_product']);
                return null; // Let Gemini handle as fresh query
            }
        }
    }

    // ── Step: not in flow — check for ordering intent ─────
    if ($step === null) {
        if (!detectOrderIntent($message)) return null; // Not ordering → let Gemini handle

        // Detect product from context (URL first, then name matching)
        $prod = findProductFromContext($db, $message, $history);
        $_SESSION['ai_cod_product'] = $prod;
        $_SESSION['ai_cod_data']    = [];

        if ($prod) {
            // Product identified — go straight to payment (all products are unstitched, no size)
            $_SESSION['ai_cod_step'] = 'ask_payment';
            $price = $prod['price'] > 0 ? 'Rs ' . number_format($prod['price'], 0) : 'Price TBD';
            return [
                'reply'       => "Product mil gaya! ✅\n\n🛍️ *{$prod['name']}*\n💰 {$price}\n\nKya yeh sahi product hai?\n✅ *haan* likhein — COD order shuru karein\n❌ *nahi* likhein — doosra product batayein",
                'products'    => [],
                'order_placed'=> false,
            ];
        } else {
            // Product not identified — ask user to share product link/name/image
            $_SESSION['ai_cod_step'] = 'ask_product';
            return [
                'reply'       => "Bilkul order kar deta/deti hoon! 😊\n\nMujhe product batayein — inme se koi bhi:\n\n1️⃣ Product page ka **link paste** karein\n2️⃣ Product ka **naam likhein** (e.g. Khaadi Lawn 3-Piece)\n3️⃣ Product ki **photo send** karein",
                'products'    => [],
                'order_placed'=> false,
            ];
        }
    }

    // ── Step: ask_product (when product wasn't detected initially) ─
    if ($step === 'ask_product') {
        $prod = null;

        // A) If user sent an image → use Gemini Vision to identify product
        if (!empty($imageBase64)) {
            $prod = identifyProductFromImage($db, $imageBase64, $imageType);
        }

        // B) Try all text strategies (URL, fuzzy words, Gemini extraction)
        if (!$prod) {
            $prod = findProductFromContext($db, $message, []);
        }

        if ($prod) {
            $_SESSION['ai_cod_product'] = $prod;
            $_SESSION['ai_cod_step']    = 'ask_payment';
            $price = $prod['price'] > 0 ? 'Rs ' . number_format($prod['price'], 0) : 'Price TBD';
            return [
                'reply'       => "Yeh product mil gaya! ✅\n\n🛍️ *{$prod['name']}*\n💰 {$price}\n\nKya yeh sahi product hai?\n✅ *haan* — COD order shuru karein\n❌ *nahi* — doosra product batayein",
                'products'    => [],
                'order_placed'=> false,
            ];
        } else {
            return [
                'reply'       => "Maafi — product samajh nahi aaya. 😕 Inme se koi bhi try karein:\n\n1️⃣ Product page ka **link paste** karein\n2️⃣ Product ka **poora naam** likhein (e.g. Khaadi Embroidered Lawn 3-Piece)\n3️⃣ Product ki **photo send** karein\n\nMain zaroor dhundh deta/deti hoon! 😊",
                'products'    => [],
                'order_placed'=> false,
            ];
        }
    }

    // ── Step: ask_payment ─────────────────────────────────
    if ($step === 'ask_payment') {
        // If user asks price/product/brand question here, reset flow
        $mLower2 = mb_strtolower(trim($message));
        $nonPaymentQuestion = (
            mb_strpos($mLower2, 'how much') !== false ||
            mb_strpos($mLower2, 'price') !== false ||
            mb_strpos($mLower2, 'show') !== false ||
            mb_strpos($mLower2, 'total') !== false ||
            mb_strpos($mLower2, 'kitna') !== false ||
            mb_strpos($mLower2, 'all ') !== false ||
            (mb_strlen(trim($message)) > 30 && !preg_match('/\b(cod|cash|delivery|online|jazzcash|card)\b/i', $message))
        );
        if ($nonPaymentQuestion) {
            unset($_SESSION['ai_cod_step'], $_SESSION['ai_cod_data'], $_SESSION['ai_cod_product']);
            return null; // Let Gemini handle
        }
        if (preg_match('/online|jazzcash|jazz\s*cash|easypaisa|easy\s*paisa|card|visa|mastercard|stripe|bank\s*transfer/i', $message)) {
            unset($_SESSION['ai_cod_step'], $_SESSION['ai_cod_data'], $_SESSION['ai_cod_product']);
            return [
                'reply'       => "Online payment ke liye please product page pe jayein aur **Buy Now** click karein — aap JazzCash, EasyPaisa ya card se pay kar sakte hain. 👍",
                'products'    => [],
                'order_placed'=> false,
            ];
        }
        if (preg_match('/\b(cod|cash|naqd|nakd|haan|ha|ji|yes|ok|okay|theek|bilkul|sahi|confirm|proceed|zaroor)\b/i', $message)
            || preg_match('/cash\s+on\s+delivery|ghar\s+pe|delivery\s+pe|cash\s+dena|cash\s+de|cash\s+rakhunga|cash\s+deta|ghar\s+wala/i', $message)
            || preg_match('/\bdelivery\b/i', $message)) {
            $_SESSION['ai_cod_step'] = 'ask_name';
            return [
                'reply'       => "Cash on Delivery — bilkul! 😊\n\nAapka *pura naam* batayein please (jaise: Ali Hassan):",
                'products'    => [],
                'order_placed'=> false,
            ];
        }
        // Unclear answer
        return [
            'reply'       => "Please batayein:\n💵 *COD* — ghar pe deliver hone ke baad pay karein\n💳 *Online* — JazzCash / EasyPaisa / Card\n\nKaunsa chahiye?",
            'products'    => [],
            'order_placed'=> false,
        ];
    }

    // ── Step: ask_name ────────────────────────────────────
    if ($step === 'ask_name') {
        $name = trim($message);
        if (mb_strlen($name) < 2) {
            return ['reply' => "Naam thoda sa aur detail mein batayein please 😊", 'products' => [], 'order_placed' => false];
        }
        $_SESSION['ai_cod_data']['name'] = $name;
        $_SESSION['ai_cod_step']         = 'ask_phone';
        return [
            'reply'       => "Shukriya, {$name}! 😊\n\nAb apna *WhatsApp/Mobile number* batayein (e.g. 03001234567):",
            'products'    => [],
            'order_placed'=> false,
        ];
    }

    // ── Step: ask_phone ───────────────────────────────────
    if ($step === 'ask_phone') {
        // Strip spaces, dashes, dots, parentheses
        $phone = preg_replace('/[\s\-\.\(\)]+/', '', trim($message));

        // Normalize: +923001234567 → 03001234567, 00923001234567 → 03001234567
        if (preg_match('/^\+92(3[0-9]{9})$/', $phone, $m))   $phone = '0' . $m[1];
        if (preg_match('/^0092(3[0-9]{9})$/', $phone, $m))   $phone = '0' . $m[1];

        // Must be 03XXXXXXXXX — exactly 11 digits starting with 03
        if (!preg_match('/^03[0-9]{9}$/', $phone)) {
            return [
                'reply'       => "⚠️ Number sahi nahi laga.\n\nPlease Pakistani mobile number dein:\n📱 *03001234567* (11 digits)\n📱 *+923001234567* (with country code)\n\nDobara likhein:",
                'products'    => [],
                'order_placed'=> false,
            ];
        }
        $_SESSION['ai_cod_data']['phone'] = $phone;
        $_SESSION['ai_cod_step']          = 'ask_email';
        return [
            'reply'       => "Got it! 📱\n\nAb *email address* batayein:\n_(Order confirmation ke liye — **skip** likhein agar nahi dena)_",
            'products'    => [],
            'order_placed'=> false,
        ];
    }

    // ── Step: ask_email ───────────────────────────────────
    if ($step === 'ask_email') {
        $em = mb_strtolower(trim($message));
        if (in_array($em, ['skip','skip.','no','nahi','na','nope','nai'])) {
            // User explicitly skipped — store empty, do NOT auto-fill from session
            $_SESSION['ai_cod_data']['email']        = '';
            $_SESSION['ai_cod_data']['email_skipped'] = true;
        } elseif (filter_var(trim($message), FILTER_VALIDATE_EMAIL)) {
            $_SESSION['ai_cod_data']['email']        = trim($message);
            $_SESSION['ai_cod_data']['email_skipped'] = false;
        } else {
            return [
                'reply'       => "Valid email chahiye (jaise: ali@gmail.com)\nYa **skip** likhein agar email nahi dena:",
                'products'    => [],
                'order_placed'=> false,
            ];
        }
        $_SESSION['ai_cod_step'] = 'ask_address';
        return [
            'reply'       => "Got it! 😊\n\nAb *poora delivery address* batayein:\n_(Ghar number, gali, mohalla, area — detail mein)_",
            'products'    => [],
            'order_placed'=> false,
        ];
    }

    // ── Step: ask_address ─────────────────────────────────
    if ($step === 'ask_address') {
        $addr = trim($message);

        // Basic length check
        if (mb_strlen($addr) < 8) {
            return [
                'reply'       => "Address thoda detail mein batayein:\n_(Ghar number, gali, mohalla — jaise: House 12, Street 4, Gulshan)_",
                'products'    => [],
                'order_placed'=> false,
            ];
        }

        // If user already confirmed address previously (after map warning), accept it
        if (!empty($_SESSION['ai_cod_data']['address_confirmed'])) {
            $_SESSION['ai_cod_data']['address'] = $addr;
            unset($_SESSION['ai_cod_data']['address_confirmed']);
            $_SESSION['ai_cod_step'] = 'ask_city';
            return ['reply' => "Address note kar liya! 📦\n\nAb *shehar (city)* batayein:", 'products' => [], 'order_placed' => false];
        }

        // Verify address via OpenStreetMap Nominatim (free, no API key)
        $verified = verifyAddressPakistan($addr);

        if ($verified === true) {
            // Address found on map ✅
            $_SESSION['ai_cod_data']['address'] = $addr;
            $_SESSION['ai_cod_step']            = 'ask_city';
            return [
                'reply'       => "✅ Address verify ho gaya!\n\nAb *shehar (city)* batayein:",
                'products'    => [],
                'order_placed'=> false,
            ];
        } else {
            // Address not found on map — ask user to confirm or re-enter
            $_SESSION['ai_cod_data']['address_pending']   = $addr;
            $_SESSION['ai_cod_data']['address_confirmed'] = false;
            $_SESSION['ai_cod_step']                      = 'confirm_address';
            return [
                'reply'       => "⚠️ *Address map pe verify nahi hua:*\n_{$addr}_\n\nKya yeh address sahi hai?\n✅ *haan* — is address pe deliver karein\n✏️ *nahi* — dobara likhein",
                'products'    => [],
                'order_placed'=> false,
            ];
        }
    }

    // ── Step: confirm_address (after map could not verify) ──
    if ($step === 'confirm_address') {
        $m2 = mb_strtolower(trim($message));
        $isYes = preg_match('/\b(haan|ha|ji|yes|ok|okay|theek|bilkul|sahi|correct|right|confirm)\b/u', $m2);
        $isNo  = preg_match('/\b(no|nahi|nai|galat|wrong|change|badlo|na)\b/u', $m2);

        if ($isYes) {
            // Accept the unverified address
            $_SESSION['ai_cod_data']['address'] = $_SESSION['ai_cod_data']['address_pending'] ?? '';
            unset($_SESSION['ai_cod_data']['address_pending']);
            $_SESSION['ai_cod_step'] = 'ask_city';
            return [
                'reply'       => "Theek hai, address note kar liya! 📦\n\nAb *shehar (city)* batayein:",
                'products'    => [],
                'order_placed'=> false,
            ];
        } elseif ($isNo) {
            // Ask to re-enter address
            $_SESSION['ai_cod_step'] = 'ask_address';
            return [
                'reply'       => "Theek hai! Dobara *poora address* likhein:\n_(Ghar number, gali, mohalla, area)_",
                'products'    => [],
                'order_placed'=> false,
            ];
        } else {
            // Treat any new long text as the new address
            $newAddr = trim($message);
            if (mb_strlen($newAddr) >= 8) {
                $_SESSION['ai_cod_data']['address'] = $newAddr;
                $_SESSION['ai_cod_step'] = 'ask_city';
                return [
                    'reply'       => "Address update kar liya! 📦\n\nAb *shehar (city)* batayein:",
                    'products'    => [],
                    'order_placed'=> false,
                ];
            }
            return [
                'reply'       => "Please **haan** ya **nahi** likhein — ya naya address likhein:",
                'products'    => [],
                'order_placed'=> false,
            ];
        }
    }

    // ── Step: ask_city ────────────────────────────────────
    if ($step === 'ask_city') {
        $city = trim($message);
        if (mb_strlen($city) < 2) {
            return ['reply' => "City ka naam batayein please:", 'products' => [], 'order_placed' => false];
        }
        $_SESSION['ai_cod_data']['city'] = $city;
        $_SESSION['ai_cod_step']         = 'confirm';

        // Build summary
        $d    = $_SESSION['ai_cod_data'];
        $prod = $_SESSION['ai_cod_product'];
        $prodPrice  = ($prod && $prod['price'] > 0) ? (float)$prod['price'] : 0.0;
        $shipAmt    = ($prodPrice >= 5000) ? 0.0 : 250.0;
        $totalAmt   = $prodPrice + $shipAmt;
        $priceStr   = $prodPrice > 0 ? 'Rs ' . number_format($prodPrice, 0) : 'TBD';
        $shipStr    = $shipAmt == 0 ? 'FREE 🎉 (Rs 5000+)' : 'Rs 250';
        $totalStr   = $prodPrice > 0 ? 'Rs ' . number_format($totalAmt, 0) : 'TBD';
        $prodLine   = $prod
            ? "🛍️ *Product:* {$prod['name']}\n💰 *Price:* {$priceStr}\n🚚 *Delivery:* {$shipStr}\n💳 *Total:* {$totalStr}"
            : "🛍️ *Product:* (Admin will confirm)";
        $emailLine  = !empty($d['email']) ? "\n📧 *Email:* {$d['email']}"  : '';
        $summary = "📋 *Order Summary — confirm karein:*\n\n{$prodLine}\n\n👤 *Naam:* {$d['name']}\n📱 *Phone:* {$d['phone']}{$emailLine}\n🏠 *Address:* {$d['address']}\n🏙️ *City:* {$city}\n💵 *Payment:* Cash on Delivery\n\nSab theek hai? Type **confirm** ya **cancel**.";
        return [
            'reply'       => $summary,
            'products'    => [],
            'order_placed'=> false,
        ];
    }

    // ── Step: confirm ─────────────────────────────────────
    if ($step === 'confirm') {
        if (!preg_match('/\bconfirm\b/i', $message)) {
            if (preg_match('/\bcancel\b/i', $message)) {
                unset($_SESSION['ai_cod_step'], $_SESSION['ai_cod_data'], $_SESSION['ai_cod_product']);
                return ['reply' => "Order cancel kar diya. Koi aur madad chahiye? 😊", 'products' => [], 'order_placed' => false];
            }
            // User is asking a question — re-show full order summary
            $d    = $_SESSION['ai_cod_data'] ?? [];
            $prod = $_SESSION['ai_cod_product'] ?? null;
            $rp2       = ($prod && $prod['price'] > 0) ? (float)$prod['price'] : 0.0;
            $ship2     = ($rp2 >= 5000) ? 0.0 : 250.0;
            $tot2      = $rp2 + $ship2;
            $prStr2    = $rp2 > 0 ? 'Rs ' . number_format($rp2, 0) : 'TBD';
            $shStr2    = $ship2 == 0 ? 'FREE 🎉' : 'Rs 250';
            $totStr2   = $rp2 > 0 ? 'Rs ' . number_format($tot2, 0) : 'TBD';
            $emailLine = !empty($d['email']) ? "\n📧 *Email:* {$d['email']}" : '';
            $prodLine  = $prod ? "🛍️ *Product:* {$prod['name']}\n💰 *Price:* {$prStr2}\n🚚 *Delivery:* {$shStr2}\n💳 *Total:* {$totStr2}" : "🛍️ *Product:* (No product detected)";
            $summary = "📋 *Aapka order summary:*\n\n{$prodLine}\n\n👤 *Naam:* " . ($d['name'] ?? '—') . "\n📱 *Phone:* " . ($d['phone'] ?? '—') . "{$emailLine}\n🏠 *Address:* " . ($d['address'] ?? '—') . "\n🏙️ *City:* " . ($d['city'] ?? '—') . "\n💵 *Payment:* Cash on Delivery\n\nType **confirm** ya **cancel**.";
            return ['reply' => $summary, 'products' => [], 'order_placed' => false];
        }

        // Place the order
        $d    = $_SESSION['ai_cod_data'];
        $prod = $_SESSION['ai_cod_product'];

        $orderNumber  = 'ORD-' . date('Y') . '-' . strtoupper(uniqid());
        $customerName = $db->real_escape_string($d['name'] ?? '');

        // Use provided email only — do NOT auto-fill from session if user skipped
        // (user consciously chose to skip → respect that choice)
        $email = $db->real_escape_string($d['email'] ?? '');
        $phone        = $db->real_escape_string($d['phone'] ?? '');
        $address      = $db->real_escape_string($d['address'] ?? '');
        $city         = $db->real_escape_string($d['city'] ?? '');
        $notes        = 'AI Chat COD Order' . ($prod ? ' — Product: ' . $prod['name'] : '');
        $notesEsc     = $db->real_escape_string($notes);
        $price        = $prod ? (float)$prod['price'] : 0.0;
        $shippingCost = ($price >= 5000) ? 0.0 : 250.0;  // Free delivery on Rs 5000+
        $tax          = 0.0;
        $total        = $price > 0 ? ($price + $shippingCost) : 0.0;

        $db->begin_transaction();
        try {
            $stmt = $db->prepare(
                "INSERT INTO orders (order_number, customer_name, email, phone, country, state, city, postal_code, address, notes, payment_method, subtotal, shipping_cost, tax, total_amount)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            if (!$stmt) throw new Exception('Prepare orders failed: ' . $db->error);
            $country   = 'Pakistan';
            $state     = '';
            $postal    = '';
            $pm        = 'cod';
            $stmt->bind_param('sssssssssssdddd',
                $orderNumber, $customerName, $email, $phone,
                $country, $state, $city, $postal,
                $address, $notesEsc, $pm,
                $price, $shippingCost, $tax, $total
            );
            if (!$stmt->execute()) throw new Exception('Execute orders failed: ' . $db->error);
            $orderId = $db->insert_id;

            // Insert order item
            $prodId    = $prod ? (int)$prod['id']    : 0;
            $prodName  = $prod ? $db->real_escape_string($prod['name'])  : 'AI Chat Order - Product TBD';
            $prodImage = $prod ? $db->real_escape_string($prod['image']) : '';
            $qty       = 1;
            $stitching = 0.0;
            $itemTotal = $price + $stitching + $shippingCost;
            $size      = $db->real_escape_string($d['size'] ?? '');
            $colors    = '';

            $stmt2 = $db->prepare(
                "INSERT INTO order_items (order_id, product_id, product_name, product_image, size, colors, quantity, price, stitching_price, shipping_cost, total_price)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            if (!$stmt2) throw new Exception('Prepare order_items failed: ' . $db->error);
            $stmt2->bind_param('iissssidddd',
                $orderId, $prodId, $prodName, $prodImage,
                $size, $colors, $qty,
                $price, $stitching, $shippingCost, $itemTotal
            );
            if (!$stmt2->execute()) throw new Exception('Execute order_items failed: ' . $db->error);

            $db->commit();

            // Clear session
            unset($_SESSION['ai_cod_step'], $_SESSION['ai_cod_data'], $_SESSION['ai_cod_product']);

            $shipLine  = $shippingCost == 0 ? 'FREE (Rs 5000+)' : 'Rs 250';
            $totalFmt  = 'RS. ' . number_format($total, 2);
            $emailConf = !empty($d['email']) ? $d['email'] : '—';
            $fullAddr  = $d['address'] . ', ' . $d['city'] . ', Pakistan';
            $reply  = "🎉 *Thank you for your order!*\n";
            $reply .= "Your order has been successfully placed.\n\n";
            $reply .= "━━━━━━━━━━━━━━━━━━━\n";
            $reply .= "📦 *Order Number:* {$orderNumber}\n";
            $reply .= "━━━━━━━━━━━━━━━━━━━\n\n";
            $reply .= "🛍️ *Order Details*\n\n";
            $reply .= "📋 *Product:* {$prod['name']}\n";
            $reply .= "👤 *Customer Name:* {$d['name']}\n";
            $reply .= "📧 *Email:* {$emailConf}\n";
            $reply .= "📱 *Phone:* {$d['phone']}\n";
            $reply .= "🏠 *Shipping Address:* {$fullAddr}\n";
            $reply .= "💵 *Payment Method:* Cash on Delivery\n";
            $reply .= "📋 *Order Status:* Pending\n";
            $reply .= "🚚 *Delivery Charge:* {$shipLine}\n";
            $reply .= "💳 *Total Amount:* {$totalFmt}\n\n";
            $reply .= "✅ Hamara team aapko jald contact karega.\n";
            $reply .= "🚚 *Delivery: 3–5 working days*";
            return [
                'reply'        => $reply,
                'products'     => [],
                'order_placed' => true,
                'order_number' => $orderNumber,
            ];
        } catch (Exception $e) {
            $db->rollback();
            unset($_SESSION['ai_cod_step'], $_SESSION['ai_cod_data'], $_SESSION['ai_cod_product']);
            return [
                'reply'        => "Maafi chahta/chahti hoon — order place nahi ho saka. Please WhatsApp karein: +92 320 9815213",
                'products'     => [],
                'order_placed' => false,
            ];
        }
    }

    return null;
}

// ===========================================================
// LIVE STORE DATA — reads DB every request, always fresh
// ===========================================================
function getStoreData($db) {
    $d = [
        'total_products' => 0,
        'total_brands'   => 0,
        'sale_count'     => 0,
        'brands'         => [],
        'sale_brands'    => [],
        'new_count'      => 0,
        'price_min'      => 0,
        'price_max'      => 0,
        'phone'          => '+92 320 9815213',
        'whatsapp'       => '+92 320 9815213',
        'email'          => '',
        'address'        => '',
        'delivery_days'  => '3-5',
        'delivery_charge'=> '250',
    ];

    $r = $db->query("SELECT COUNT(*) c FROM products WHERE LOWER(COALESCE(stock,'')) NOT IN ('out of stock','0')");
    if ($r) $d['total_products'] = (int)$r->fetch_assoc()['c'];

    $r = $db->query("SELECT COUNT(*) c FROM products WHERE sale='In Sale' AND LOWER(COALESCE(stock,'')) NOT IN ('out of stock','0')");
    if ($r) $d['sale_count'] = (int)$r->fetch_assoc()['c'];

    $r = $db->query("SELECT DISTINCT TRIM(name) n FROM brand WHERE name IS NOT NULL AND TRIM(name) != '' ORDER BY name ASC");
    if ($r) {
        while ($row = $r->fetch_assoc()) { if ($row['n']) $d['brands'][] = $row['n']; }
        $d['total_brands'] = count($d['brands']);
    }

    $r = $db->query("SELECT DISTINCT b.name FROM products p JOIN brand b ON FIND_IN_SET(b.id, p.brand) WHERE p.sale='In Sale' AND LOWER(COALESCE(p.stock,'')) NOT IN ('out of stock','0') ORDER BY b.name LIMIT 10");
    if ($r) while ($row = $r->fetch_assoc()) $d['sale_brands'][] = $row['name'];

    $r = $db->query("SELECT MIN(price_after_discount) mn, MAX(price_after_discount) mx FROM products WHERE LOWER(COALESCE(stock,'')) NOT IN ('out of stock','0')");
    if ($r) { $row = $r->fetch_assoc(); $d['price_min'] = (int)$row['mn']; $d['price_max'] = (int)$row['mx']; }

    $r = $db->query("SELECT COUNT(*) c FROM products WHERE id > (SELECT MAX(id)-30 FROM products) AND LOWER(COALESCE(stock,'')) NOT IN ('out of stock','0')");
    if ($r) $d['new_count'] = (int)$r->fetch_assoc()['c'];

    // Top 5 featured / AI-scored products
    $d['featured_products'] = [];
    $r = $db->query("SELECT p.name, p.price_after_discount, b.name as brand_name FROM products p LEFT JOIN brand b ON FIND_IN_SET(b.id, p.brand) WHERE LOWER(COALESCE(p.stock,'')) NOT IN ('out of stock','0') ORDER BY COALESCE(p.ai_score, 50) DESC, p.id DESC LIMIT 5");
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $pName  = html_entity_decode((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8');
            $pPrice = (int)($row['price_after_discount'] ?? 0);
            $pBrand = trim((string)($row['brand_name'] ?? ''));
            $d['featured_products'][] = ($pBrand ? "{$pBrand} — " : '') . "{$pName} (Rs {$pPrice})";
        }
    }

    // Set suit products — exact details for AI (quantity, price, discount)
    $d['set_suits'] = [];
    $r = $db->query(
        "SELECT p.name, p.slug, p.price_after_discount, p.setsuite_price, p.setsuite_quantity, p.setsuite_discount, b.name AS brand_name
         FROM products p
         LEFT JOIN brand b ON FIND_IN_SET(b.id, p.brand)
         WHERE p.setsuite_price IS NOT NULL AND p.setsuite_price != '' AND p.setsuite_price != '0'
           AND LOWER(COALESCE(p.stock,'')) NOT IN ('out of stock','0','out stock')
         ORDER BY p.id DESC LIMIT 20"
    );
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $n  = html_entity_decode((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8');
            $b  = trim((string)($row['brand_name'] ?? ''));
            $sp = (int)($row['setsuite_price'] ?? $row['price_after_discount'] ?? 0);
            $sq = (int)($row['setsuite_quantity'] ?? 0);
            $sd = (int)($row['setsuite_discount'] ?? 0);
            $entry = ($b ? "{$b} — " : '') . "{$n}";
            if ($sp > 0) $entry .= " | Set price: Rs {$sp}";
            if ($sq > 0) $entry .= " | {$sq} pieces/set";
            if ($sd > 0) $entry .= " | {$sd}% set discount";
            $d['set_suits'][] = $entry;
        }
    }

    // ── Complete brand stats: product count + volumes per brand ──
    // This gives AI accurate answers to "do you have X?", "how many brands?", "what volumes?"
    $d['brand_urls']  = [];
    $d['brand_stats'] = [];   // brand name => { products, volumes: [...] }

    $isLocal = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
    $baseUrl = $isLocal ? 'http://localhost/kapraa' : 'https://kapraa.pk';

    $r = $db->query(
        "SELECT b.id, b.name, b.slug,
                COUNT(p.id) AS product_count
         FROM brand b
         LEFT JOIN products p ON FIND_IN_SET(b.id, p.brand)
                              AND LOWER(COALESCE(p.stock,'')) NOT IN ('out of stock','0','out stock')
         WHERE b.name IS NOT NULL AND TRIM(b.name) != ''
         GROUP BY b.id, b.name, b.slug
         ORDER BY b.name ASC"
    );
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $bName  = trim($row['name']);
            $bSlug  = $row['slug'] ?? '';
            $bCount = (int)$row['product_count'];
            $bId    = (int)$row['id'];

            if ($bSlug) {
                $d['brand_urls'][$bName] = $baseUrl . '/brand/' . $bSlug;
            }

            // Fetch volumes for this brand (VOL-01, VOL-02 etc.)
            $vols = [];
            $vr   = $db->query(
                "SELECT name FROM products
                 WHERE FIND_IN_SET($bId, brand)
                   AND LOWER(name) LIKE '%vol%'
                   AND LOWER(COALESCE(stock,'')) NOT IN ('out of stock','0','out stock')"
            );
            if ($vr) {
                $volCounts = [];
                while ($vrow = $vr->fetch_assoc()) {
                    if (preg_match('/\bv(?:ol(?:ume)?)?[-.\s]*0*(\d+)\b/i', $vrow['name'], $vm)) {
                        $vNum = (int)$vm[1];
                        $vKey = 'Vol-' . str_pad($vNum, 2, '0', STR_PAD_LEFT);
                        $volCounts[$vKey] = ($volCounts[$vKey] ?? 0) + 1;
                    }
                }
                ksort($volCounts);
                foreach ($volCounts as $vLabel => $vCnt) {
                    $vols[] = $vLabel . ' (' . $vCnt . ' products)';
                }
            }

            $d['brand_stats'][$bName] = [
                'count'   => $bCount,
                'volumes' => $vols,
            ];
        }
    }

    // ── Occasion tag inventory (admin-tagged products) ──
    $d['occasion_tags'] = [];
    $r = $db->query(
        "SELECT occasion_tag, COUNT(*) cnt FROM products
         WHERE occasion_tag IS NOT NULL AND TRIM(occasion_tag) != ''
           AND LOWER(COALESCE(stock,'')) NOT IN ('out of stock','0')
         GROUP BY occasion_tag ORDER BY cnt DESC"
    );
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $d['occasion_tags'][] = $row['occasion_tag'] . ' (' . (int)$row['cnt'] . ' products)';
        }
    }

    // ── Cultural tag inventory ──
    $d['cultural_tags'] = [];
    $r = $db->query(
        "SELECT cultural_tag, COUNT(*) cnt FROM products
         WHERE cultural_tag IS NOT NULL AND TRIM(cultural_tag) != ''
           AND LOWER(COALESCE(stock,'')) NOT IN ('out of stock','0')
         GROUP BY cultural_tag ORDER BY cnt DESC"
    );
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $d['cultural_tags'][] = $row['cultural_tag'] . ' (' . (int)$row['cnt'] . ' products)';
        }
    }

    // Site contact info
    foreach (['contact', 'web_settings'] as $tbl) {
        $chk = $db->query("SHOW TABLES LIKE '{$tbl}'");
        if (!$chk || $chk->num_rows === 0) continue;
        $res = $db->query("SELECT * FROM `{$tbl}` ORDER BY id DESC LIMIT 1");
        if (!$res || $res->num_rows === 0) continue;
        $row = $res->fetch_assoc();
        foreach ($row as $k => $v) {
            $k = strtolower($k);
            $v = trim(strip_tags(html_entity_decode((string)$v, ENT_QUOTES, 'UTF-8')));
            if (!$v) continue;
            if (!$d['phone']    && preg_match('/^(phone|mobile|cell|tel)/i', $k)) $d['phone'] = $v;
            if (!$d['whatsapp'] && preg_match('/whatsapp|wa/i', $k))              $d['whatsapp'] = $v;
            if (!$d['email']    && preg_match('/email|mail/i', $k))               $d['email'] = $v;
            if (!$d['address']  && preg_match('/address|location/i', $k))         $d['address'] = $v;
        }
    }

    return $d;
}

// ===========================================================
// FETCH CATEGORIES
// ===========================================================
function getCategories($db) {
    $cats = [];
    $r = $db->query("SELECT name FROM category WHERE name IS NOT NULL AND TRIM(name)!='' ORDER BY name ASC LIMIT 20");
    if ($r) while ($row = $r->fetch_assoc()) $cats[] = $row['name'];
    return $cats;
}

function getSubcategories($db) {
    $subs = [];
    // main_category column stores category id as text
    $r = $db->query("SELECT sc.name, c.name AS cat FROM subcategory sc LEFT JOIN category c ON c.id = CAST(sc.main_category AS UNSIGNED) WHERE sc.name IS NOT NULL AND TRIM(sc.name)!='' ORDER BY sc.name ASC LIMIT 40");
    if ($r) while ($row = $r->fetch_assoc()) $subs[] = $row['name'] . ($row['cat'] ? ' (' . $row['cat'] . ')' : '');
    return $subs;
}

// ===========================================================
// SHOULD WE SEARCH FOR PRODUCTS?
// Only search when user clearly wants to browse/buy something
// ===========================================================
// ===========================================================
// SPECIFIC PRODUCT QUERY DETECTION
// Returns true when user is asking about a particular product
// (price, details, availability) — carousel NOT needed in this case
// ===========================================================
function isSpecificProductQuery($msg) {
    // 1. Contains a product URL (localhost or live site)
    if (preg_match('/(?:localhost|kapraaonline\.com\.pk|ngrok)[^\s]*\/product\/[a-z0-9\-]{5,}/i', $msg)) {
        return true;
    }
    // 2. "?slug=" or "/product/" anywhere in message
    if (preg_match('/[?&]slug=|\/product\//i', $msg)) {
        return true;
    }
    $m = mb_strtolower($msg);
    // 3. Price/detail inquiry keywords paired with a specific item reference
    $priceWords  = ['how much', 'price', 'kitna', 'rate', 'cost', 'keemat', 'kya rate', 'is ka price', 'is ki price'];
    $specificRef = ['this', 'yeh', 'ye', 'is', 'wala', 'wali', 'iska', 'iski', 'iska'];
    $hasPriceWord   = false;
    $hasSpecificRef = false;
    foreach ($priceWords  as $p) { if (mb_strpos($m, $p) !== false) { $hasPriceWord = true; break; } }
    foreach ($specificRef as $r) { if (mb_strpos($m, $r) !== false) { $hasSpecificRef = true; break; } }
    if ($hasPriceWord && $hasSpecificRef) return true;

    // 4. "Do you have [specific product name]?" — only for specific product/slug references
    // NOTE: General category queries like "do you have punjabi dresses" should NOT match here
    // They should go through normal product search (searchProducts). Only match when:
    // a product slug/name identifier follows (at least 15+ chars in the message + specific indicator)
    if (mb_strlen($m) > 30 &&
        preg_match('/\b(this product|ye product|yeh product|is product)\b/i', $m)) {
        return true;
    }
    if (preg_match('/\b(stock mein hai|in stock|available hai)\b.*\b(ye|yeh|is|iska)\b/i', $m)) {
        return true;
    }

    return false;
}

// ===========================================================
// DETECT FINAL YEAR PROJECT QUESTION
// Teachers/evaluators asking about the project itself
// ===========================================================
function isProjectQuery($msg) {
    $m = mb_strtolower(trim($msg));
    $keywords = [
        // Direct project references
        'final year project', 'fyp', 'your project', 'this project', 'is project',
        'project ka', 'project ki', 'project mein', 'project me',
        'apka project', 'tumhara project', 'aapka project',
        // Technology questions
        'technology', 'tech stack', 'technologies used', 'programming language',
        'what language', 'which language', 'konsi language', 'konsa language',
        'database', 'mysql', 'php', 'javascript', 'bootstrap',
        'gemini', 'google ai', 'ai model', 'api', 'cloudinary',
        // System/architecture questions
        'how does this work', 'how does it work', 'kaise kaam karta',
        'system work', 'architecture', 'how was this built', 'kaise banaya',
        'how did you build', 'how you made', 'kaise banaya',
        'admin panel', 'admin side', 'backend', 'frontend',
        // Feature questions
        'features', 'feature kya', 'what are the features', 'functionality',
        'what can this', 'what does this', 'system ki features', 'modules',
        'live chat kaise', 'ai chat kaise', 'order system',
        // Database questions
        'tables', 'database structure', 'schema', 'erd', 'entity',
        'how many tables', 'kitne tables', 'database mein',
        // Purpose/objective
        'purpose', 'objective', 'motive', 'goal', 'scope', 'what is this website',
        'is website ka', 'system ka maqsad', 'project scope',
        // Contribution / FYP evaluation
        'contribution', 'what did you do', 'your role', 'team', 'teammates',
        'supervisor', 'methodology', 'sdlc', 'agile', 'waterfall',
        'testing', 'deployment', 'future work', 'future enhancements',
        'limitations', 'challenges', 'problems faced',
        'explain this system', 'explain your', 'tell me about this project',
        'describe this', 'overview', 'introduction',
    ];
    foreach ($keywords as $kw) {
        if (mb_strpos($m, $kw) !== false) return true;
    }
    // Pattern: "what is [this/your/the] system/website/platform"
    if (preg_match('/\b(what is|explain|describe|tell me about)\b.{0,30}\b(system|website|platform|project|application|app)\b/i', $msg)) return true;
    return false;
}

// ===========================================================
// DETECT "WHAT DID I JUST ADD?" — new/recently added products
// ===========================================================
function isNewProductQuery($msg) {
    $m = mb_strtolower(trim($msg));
    $signals = [
        'just add', 'just added', 'newly added', 'new add', 'naya add',
        'what did i add', 'what i added', 'what have i added', 'what did you add',
        'recently added', 'latest added', 'last added', 'latest product',
        'last product', 'new product', 'naya product', 'abhi add kiya',
        'abhi add', 'new entry', 'latest entry', 'jo add kiya', 'jo maine add',
        'show new', 'which product i add', 'which product did i add',
        'konsa product add', 'show me new', 'show me what', 'what is new',
        'whats new', "what's new", 'naya kya', 'kya naya', 'nayi product',
    ];
    foreach ($signals as $s) {
        if (mb_strpos($m, $s) !== false) return true;
    }
    return false;
}

// ===========================================================
// FETCH RECENTLY ADDED PRODUCTS FROM DB
// ===========================================================
function getRecentProducts($db, $base, $limit = 8) {
    $res = $db->query(
        "SELECT p.id, p.name, p.slug, p.price, p.price_after_discount, p.image,
                p.sale, p.occasion_tag, p.cultural_tag,
                b.name brand_name,
                ROUND(((p.price-p.price_after_discount)/NULLIF(p.price,0))*100) discount_pct,
                DATE_FORMAT(p.created_at, '%d %b %Y %h:%i %p') AS added_at
         FROM products p
         LEFT JOIN brand b ON FIND_IN_SET(b.id, p.brand)
         WHERE LOWER(COALESCE(p.stock,'')) NOT IN ('out of stock','0')
         ORDER BY p.id DESC
         LIMIT $limit"
    );
    $products = [];
    if (!$res) return $products;
    while ($row = $res->fetch_assoc()) {
        $imgRaw = trim(explode(',', (string)($row['image'] ?? ''))[0] ?? '');
        $products[] = [
            'id'                  => (int)$row['id'],
            'name'                => html_entity_decode((string)$row['name'], ENT_QUOTES, 'UTF-8'),
            'slug'                => (string)$row['slug'],
            'brand'               => (string)($row['brand_name'] ?: 'Kapraa'),
            'price'               => number_format((float)$row['price'], 0),
            'sale_price'          => number_format((float)$row['price_after_discount'], 0),
            'discount'            => ((int)$row['discount_pct'] > 0 ? (int)$row['discount_pct'] . '% OFF' : ''),
            'image_url'           => $imgRaw,
            'product_url'         => $base . '/product/' . $row['slug'],
            'is_on_sale'          => ((string)$row['sale'] === 'In Sale'),
            'ai_reason'           => 'New',
            'added_at'            => (string)$row['added_at'],
        ];
    }
    return $products;
}

// ===========================================================
// DETECT "SHOW ALL" INTENT
// User wants entire brand/category collection, not just one item
// ===========================================================
function isShowAllQuery($msg) {
    $m = mb_strtolower($msg);
    $allSignals = [
        'all products', 'all suits', 'all items', 'all collection', 'all designs',
        'sab products', 'sab suits', 'sab items', 'sara collection', 'sab dikhao',
        'sab show', 'show all', 'show me all', 'all available', 'full collection',
        'pure collection', 'poora collection', 'tamam', 'sab kuch', 'list karo',
        'list karden', 'all ka list', 'all designs dikhao', 'complete collection',
        'every product', 'all the', 'all suits', 'all dresses', 'all kurta',
    ];
    foreach ($allSignals as $s) {
        if (mb_strpos($m, $s) !== false) return true;
    }
    return false;
}

function needsProducts($msg) {
    $m = mb_strtolower($msg);
    // Clear product-search signals
    $signals = [
        'show', 'find', 'search', 'looking for', 'dhundh', 'chahiye', 'need',
        'buy', 'kharidna', 'recommend', 'suggest', 'kya hai', 'kya available',
        'lawn', 'linen', 'khaddar', 'suit', 'dress', 'kurta', 'dupatta',
        'collection', 'eid', 'wedding', 'shadi', 'festive', 'bridal', 'mehndi',
        'party', 'summer', 'winter', 'office', 'casual', 'unstitched', 'stitched',
        '3pc', '2pc', 'embroidered', 'printed', 'sale', 'discount', 'offer',
        'under rs', 'budget', 'cheap', 'sasta', 'new arrivals', 'naya',
        'kids', 'children', 'bacha', 'men', 'gents', 'ladies', 'women',
        'chiffon', 'silk', 'cotton', 'fabric',
        't-shirt', 'tshirt', 'shirt', 'trouser', 'bottom', 'jeans', 'pant',
        'shoes', 'footwear', 'chappal', 'sandal', 'accessories', 'jewelry',
        'scarf', 'shawl', 'dupatta', 'shalwar', 'kameez', 'salwar', 'lehenga',
        'gharara', 'sharara', 'formal', 'eastern', 'western', 'designer',
        // Cultural dress keywords
        'punjabi', 'sindhi', 'balochi', 'pashtun', 'pashto', 'kashmiri',
        'phulkari', 'ajrak', 'pheran', 'khet partug', 'perahan', 'cultural',
        'traditional', 'regional', 'provincial', 'desi',
        // Sports / athletic / new
        'sport', 'sports', 'athletic', 'gym', 'active', 'workout',
        'new', 'latest', 'fresh', 'arrival', 'just in', 'recently added',
        // Urdu shopping signals
        'dikhao', 'koi suit', 'koi dress', 'kuch dikhao', 'options',
    ];
    foreach ($signals as $s) {
        if (mb_strpos($m, $s) !== false) return true;
    }
    return false;
}

// ===========================================================
// BRAND DETECTION — brands IN our store
// ===========================================================
function detectBrand($db, $msg) {
    $m    = mb_strtolower($msg);
    $res  = $db->query("SELECT id, name FROM brand ORDER BY LENGTH(name) DESC");
    if (!$res) return null;
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;

    // Step 1: Exact substring match
    foreach ($rows as $row) {
        $b = mb_strtolower(trim($row['name']));
        if ($b && mb_strlen($b) > 2 && mb_strpos($m, $b) !== false) return $row;
    }

    // Step 1.5: Normalized match — strips dots/spaces so "MARIA.B." matches "maria b"
    // e.g. "MARIA.B." → "mariab", user types "maria b" → "mariab" ✓
    $mNorm = preg_replace('/[.\s\-_]+/', '', $m);
    foreach ($rows as $row) {
        $bNorm = preg_replace('/[.\s\-_]+/', '', mb_strtolower(trim($row['name'])));
        if (mb_strlen($bNorm) >= 3 && mb_strpos($mNorm, $bNorm) !== false) return $row;
    }

    // Step 2: Word-by-word match (handles "Khaadi" vs "khaadi lawn")
    $msgWords = preg_split('/[\s\-_]+/', $m);
    foreach ($rows as $row) {
        $bWords = preg_split('/[\s\-_]+/', mb_strtolower(trim($row['name'])));
        $matchCount = 0;
        foreach ($bWords as $bw) {
            if (mb_strlen($bw) >= 4) {
                foreach ($msgWords as $mw) {
                    if (mb_strlen($mw) >= 4 && mb_strpos($mw, $bw) !== false) { $matchCount++; break; }
                }
            }
        }
        $needed = count(array_filter($bWords, fn($w) => mb_strlen($w) >= 4));
        if ($needed > 0 && $matchCount >= $needed) return $row;
    }

    // Step 3: Levenshtein — catch 1-2 char typos (e.g. "Khaidi" → "Khaadi")
    // Only apply to longer words to avoid false positives (e.g. "sale" ≠ "lala")
    $commonWords = ['sale', 'suit', 'show', 'find', 'need', 'want', 'have', 'new', 'best', 'nice', 'good', 'shop'];
    foreach ($msgWords as $mw) {
        if (mb_strlen($mw) < 5) continue; // skip short words — too many false positives
        if (in_array($mw, $commonWords)) continue;
        foreach ($rows as $row) {
            $bWords = preg_split('/[\s\-_]+/', mb_strtolower(trim($row['name'])));
            foreach ($bWords as $bw) {
                if (mb_strlen($bw) >= 5 && levenshtein($mw, $bw) <= 2) return $row;
            }
        }
    }
    return null;
}

// ===========================================================
// EXTERNAL BRAND DETECTION — popular brands NOT in our store
// If customer asks for these, show no products (AI will explain)
// ===========================================================
function detectExternalBrand($msg) {
    $m = mb_strtolower($msg);
    $external = [
        'gull ahmed','gul ahmed','gulahmed',
        'sana safinaz','sana-safinaz',
        'sapphire','sapphir',
        'alkaram','al-karam','al karam',
        'bonanza','bonanza satrangi',
        'zellbury',
        'junaid jamshed','j. junaid','j.junaid',
        'ethnic by outfitters',
        'limelight',
        'ego',
        'bareeze',
        'charcoal',
        'generation',
        'rang ja','rang-ja',
        'beechtree','beech tree',
        'outfitters',
        'libas',
        'qalamkar',
        'asim jofa',
        'anarkali',
        'nida azwer',
        'hussain rehar',
        'republic womenswear',
        'zara','h&m','mango','uniqlo',  // international brands
    ];
    foreach ($external as $b) {
        if (mb_strpos($m, $b) !== false) return $b;
    }
    return null;
}

// ===========================================================
// BRAND VOLUMES — how many volumes a brand has, with counts
// Extracts VOL-01, VOL-02 etc. from product names in DB
// ===========================================================
function getBrandVolumes($db, $brandId, $brandName) {
    $volumes = [];
    $id = (int)$brandId;
    // Fetch all product names for this brand that have stock
    $r = $db->query(
        "SELECT name, price_after_discount, stock FROM products
         WHERE FIND_IN_SET($id, brand)
           AND LOWER(COALESCE(stock,'')) NOT IN ('out of stock','0','out stock')
         ORDER BY name ASC"
    );
    if (!$r) return [];

    $volCounts = []; // vol label => product count
    $volPrices = []; // vol label => [prices]
    $totalProducts = 0;

    while ($row = $r->fetch_assoc()) {
        $totalProducts++;
        $name = (string)$row['name'];
        $price = (float)($row['price_after_discount'] ?? 0);

        // Match VOL-01, VOL 01, Vol-1, Volume 1, Vol.1, V-01, etc.
        if (preg_match('/\bv(?:ol(?:ume)?)?[-.\s]*0*(\d+)\b/i', $name, $m)) {
            $volNum = (int)$m[1];
            $key = 'Vol-' . str_pad($volNum, 2, '0', STR_PAD_LEFT);
            $volCounts[$key] = ($volCounts[$key] ?? 0) + 1;
            if ($price > 0) $volPrices[$key][] = $price;
        }
    }

    ksort($volCounts);

    $result = [
        'total_products' => $totalProducts,
        'total_volumes'  => count($volCounts),
        'volumes'        => [],
    ];

    foreach ($volCounts as $vol => $count) {
        $entry = ['label' => $vol, 'count' => $count];
        if (!empty($volPrices[$vol])) {
            $entry['price_min'] = (int)min($volPrices[$vol]);
            $entry['price_max'] = (int)max($volPrices[$vol]);
        }
        $result['volumes'][] = $entry;
    }

    return $result;
}

// ===========================================================
// PRODUCT SEARCH
// ===========================================================
function searchProducts($db, $msg, $brand, $base, $showAll = false) {
    $m      = mb_strtolower($msg);
    $where  = ["LOWER(COALESCE(p.stock,'')) NOT IN ('out of stock','0')"];
    $params = [];
    $types  = '';
    $limit  = $brand ? 50 : ($showAll ? 30 : 8);

    if ($brand) {
        // Try FIND_IN_SET first; also add plain = fallback via OR
        $where[]  = "(FIND_IN_SET(?, p.brand) OR p.brand = ?)";
        $params[] = (string)$brand['id'];
        $params[] = (string)$brand['id'];
        $types   .= 'ss';
    }

    // When user wants ALL products of a brand, skip all keyword/occasion/category filters
    if ($brand && $showAll) {
        goto run_query;
    }

    // Category
    if (mb_strpos($m, 'kids') !== false || mb_strpos($m, 'bacha') !== false || mb_strpos($m, 'baccha') !== false || mb_strpos($m, 'children') !== false) {
        $where[] = "p.category = 5";
    } elseif (
        mb_strpos($m, 'women') === false &&
        (
            preg_match('/\bmen\b|\bmens\b|\bmen\'s\b|\bgents\b|\bgent\b|\bmardana\b|\bmard\b|\bhusband\b|\bbhai\b/u', $m) ||
            preg_match('/\bmale\b/u', $m)
        )
    ) {
        $where[] = "p.category = 3";
    }

    // Sale
    if (mb_strpos($m, 'sale') !== false || mb_strpos($m, 'discount') !== false || mb_strpos($m, 'sasta') !== false) {
        $where[] = "p.sale = 'In Sale'";
    }

    // New
    if (mb_strpos($m, 'new') !== false || mb_strpos($m, 'latest') !== false || mb_strpos($m, 'arrival') !== false) {
        $where[] = "p.id > (SELECT MAX(id)-50 FROM products)";
    }

    // Budget
    preg_match('/(\d{3,6})/', $msg, $bm);
    if (!empty($bm[1]) && preg_match('/under|below|budget|less|tak|se kam/i', $msg)) {
        $where[]  = "p.price_after_discount <= ?";
        $params[] = (int)$bm[1];
        $types   .= 'i';
    }

    // Occasions (occasion_tag column) — also checks name and description
    // NOTE: Cultural keywords (punjabi/sindhi etc.) are handled separately in cultMap below
    $occMap2 = [
        'eid'     => 'eid',
        'wedding' => 'wedding',
        'shadi'   => 'wedding',
        'festive' => 'festive',
        'office'  => 'office',
        'casual'  => 'casual',
        'summer'  => 'summer',
        'winter'  => 'winter',
        'bridal'  => 'bridal',
        'mehndi'  => 'mehndi',
        'party'   => 'party',
        'sports'  => 'sports',
        'sport'   => 'sports',
        'athletic'=> 'sports',
        'gym'     => 'sports',
    ];
    // Only run occasion filter if no cultural keyword is present (to avoid double AND)
    $hasCulturalKw = preg_match('/\b(punjabi|sindhi|balochi|pashtun|pashto|kashmiri|phulkari|ajrak|pheran|khet|perahan)\b/i', $m);
    $matchedOccTag = null;
    if (!$hasCulturalKw) {
        foreach ($occMap2 as $keyword => $tag) {
            if (mb_strpos($m, $keyword) !== false) {
                $where[]  = "(COALESCE(p.occasion_tag,'') LIKE ? OR COALESCE(p.description,'') LIKE ? OR p.name LIKE ?)";
                $params[] = "%{$tag}%";
                $params[] = "%{$tag}%";
                $params[] = "%{$keyword}%";
                $types   .= 'sss';
                $matchedOccTag = $db->real_escape_string($tag);
                break;
            }
        }
    }

    // "Show ALL cultural dresses" — no specific culture mentioned
    // Triggered by: "cultural", "traditional", "regional", "saqafati"
    if (!$matchedCultTag &&
        (mb_strpos($m, 'cultural') !== false || mb_strpos($m, 'traditional') !== false
         || mb_strpos($m, 'regional') !== false || mb_strpos($m, 'saqafati') !== false)) {
        $where[]       = "(COALESCE(p.cultural_tag,'') != '' AND TRIM(p.cultural_tag) != '')";
        $matchedCultTag = '__all__';   // sentinel: prevents fallback & enables hasOccasion flag
        if ($limit === 8) $limit = 12;
    }

    // Cultural dresses — specific tag (cultural_tag column)
    $cultMap = [
        'punjabi'  => 'punjabi',
        'phulkari' => 'punjabi',
        'sindhi'   => 'sindhi',
        'ajrak'    => 'sindhi',
        'balochi'  => 'balochi',
        'pashtun'  => 'pashtun',
        'pashto'   => 'pashtun',
        'khet'     => 'pashtun',
        'perahan'  => 'pashtun',
        'kashmiri' => 'kashmiri',
        'pheran'   => 'kashmiri',
        'kashmir'  => 'kashmiri',
    ];
    $matchedCultTag     = null;  // mapped tag  (e.g. "punjabi")
    $matchedCultKeyword = null;  // user's word (e.g. "phulkari") — may differ from tag
    foreach ($cultMap as $keyword => $tag) {
        if (mb_strpos($m, $keyword) !== false) {
            // Search cultural_tag FIRST (most specific), then name, then occasion_tag
            $where[]  = "(COALESCE(p.cultural_tag,'') LIKE ? OR p.name LIKE ? OR COALESCE(p.occasion_tag,'') LIKE ?)";
            $params[] = "%{$tag}%";
            $params[] = "%{$tag}%";
            $params[] = "%{$tag}%";
            $types   .= 'sss';
            $matchedCultTag     = $db->real_escape_string($tag);
            $matchedCultKeyword = $db->real_escape_string($keyword); // actual word user typed
            if ($limit === 8) $limit = 12;
            break;
        }
    }

    // Keywords — expanded to include all clothing types
    $kwParts = [];
    $allKeywords = [
        'lawn', 'linen', 'khaddar', 'embroidered', 'printed', 'unstitched', 'stitched',
        'chiffon', 'cotton', 'silk', '3pc', '2pc', 'kurta', 'shalwar', 'kameez',
        'dupatta', 'shirt', 'trouser', 'pant', 'lehenga', 'gharara', 'sharara',
        't-shirt', 'tshirt', 'formal', 'eastern', 'western', 'designer', 'digital',
        'shoes', 'footwear', 'chappal', 'sandal', 'scarf', 'shawl', 'accessories',
        'sport', 'sports', 'athletic', 'gym', 'active',
    ];
    foreach ($allKeywords as $kw) {
        if (mb_strpos($m, $kw) !== false) {
            $kwParts[] = "(p.name LIKE ? OR COALESCE(p.description,'') LIKE ?)";
            $params[]  = "%{$kw}%";
            $params[]  = "%{$kw}%";
            $types    .= 'ss';
        }
    }
    if ($kwParts) $where[] = '(' . implode(' OR ', $kwParts) . ')';


    run_query:
    // Cultural-specific ORDER BY:
    // Rank 0 — cultural_tag is EXACTLY this tag (e.g. "balochi" only, not "balochi,pakistani,festive")
    //           → admin deliberately tagged this product ONLY as balochi → highest relevance
    // Rank 1 — name explicitly contains the cultural keyword → very specific product
    // Rank 2 — cultural_tag contains the tag among others (multi-tagged products)
    // Rank 3 — only occasion_tag / description matched (weakest signal)
    // Within same rank → ai_score DESC then id DESC
    if ($matchedCultTag && $matchedCultTag !== '__all__') {
        // Cultural-specific: exact tag > name match > multi-tag > weak match
        $orderBy = "CASE
            WHEN LOWER(COALESCE(p.cultural_tag,'')) = '{$matchedCultTag}'                               THEN 0
            WHEN LOWER(p.name) LIKE '%{$matchedCultKeyword}%'                                           THEN 1"
            . ($matchedCultKeyword !== $matchedCultTag ? "
            WHEN LOWER(p.name) LIKE '%{$matchedCultTag}%'                                               THEN 1" : "") . "
            WHEN LOWER(COALESCE(p.cultural_tag,'')) LIKE '%{$matchedCultTag}%'                          THEN 2
            ELSE 3
        END ASC, COALESCE(p.ai_score, 50) DESC, p.id DESC";
    } elseif ($matchedOccTag) {
        // Occasion-specific: exact occasion_tag > multi-tag occasion > description/name only
        $orderBy = "CASE
            WHEN LOWER(COALESCE(p.occasion_tag,'')) = '{$matchedOccTag}'                                THEN 0
            WHEN LOWER(COALESCE(p.occasion_tag,'')) LIKE '%{$matchedOccTag}%'                           THEN 1
            ELSE 2
        END ASC, COALESCE(p.ai_score, 50) DESC, p.id DESC";
    } else {
        $orderBy = "COALESCE(p.ai_score, 50) DESC, p.id DESC";
    }

    $sql = "SELECT p.id, p.name, p.slug, p.price, p.price_after_discount, p.image, p.sale,
                   p.occasion_tag,
                   b.name brand_name,
                   ROUND(((p.price - p.price_after_discount) / NULLIF(p.price,0)) * 100) discount_pct
            FROM products p
            LEFT JOIN brand b ON FIND_IN_SET(b.id, p.brand)
            WHERE " . implode(' AND ', $where) . "
            ORDER BY {$orderBy}
            LIMIT {$limit}";

    if ($params) {
        $st = $db->prepare($sql);
        if (!$st) return [];
        $st->bind_param($types, ...$params);
        $st->execute();
        $res = $st->get_result();
    } else {
        $res = $db->query($sql);
    }

    $rows = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = formatProduct($row, $m, $base);
        }
    }

    // Only show fallback products if user did NOT ask for a specific brand
    // and did NOT search by keyword — i.e. a general "show me something" request
    $hasKeywordSearch = !empty($kwParts);
    $hasBudgetSearch  = !empty($bm[1]);
    $hasOccasion      = !empty($matchedCultTag); // cultural search counts as occasion
    if (!$hasOccasion) {
        foreach (['eid','wedding','shadi','festive','office','casual','summer','winter','bridal','mehndi','party','punjabi'] as $occ) {
            if (mb_strpos($m, $occ) !== false) { $hasOccasion = true; break; }
        }
    }

    // ── Name search: try to match individual words from message against product names ──
    // This catches "Britanney Hoover" style specific product names that have no clothing keywords
    // Skip this entire fallback if a cultural search was done — no result = we genuinely don't have it
    if (empty($rows) && !$matchedCultTag) {
        $nameWords = array_filter(
            preg_split('/[\s\-_,\.\/\?&=]+/', $m),
            fn($w) => mb_strlen($w) >= 4 && !in_array($w, [
                'need','want','have','this','that','does','what','show','find','tell','give',
                'please','order','buy','karna','mujhe','chahiye','lena','karo','hain','kiya',
                // Generic words that produce false matches
                'dress','dresses','suit','suits','wear','cloth','clothes','clothing',
                // Cultural keywords already handled above
                'punjabi','sindhi','balochi','pashtun','pashto','kashmiri','phulkari',
                'ajrak','pheran','khet','perahan','cultural','traditional','pakistani',
            ])
        );
        if (!empty($nameWords)) {
            $nameParts = [];
            foreach ($nameWords as $w) {
                $safe = $db->real_escape_string($w);
                $nameParts[] = "LOWER(p.name) LIKE '%{$safe}%'";
            }
            $nameWhere = implode(' OR ', $nameParts);
            $fb = $db->query("SELECT p.id, p.name, p.slug, p.price, p.price_after_discount, p.image, p.sale, p.occasion_tag, b.name brand_name, ROUND(((p.price-p.price_after_discount)/NULLIF(p.price,0))*100) discount_pct FROM products p LEFT JOIN brand b ON FIND_IN_SET(b.id,p.brand) WHERE ({$nameWhere}) AND LOWER(COALESCE(p.stock,'')) NOT IN ('out of stock','0') ORDER BY COALESCE(p.ai_score,50) DESC LIMIT 6");
            if ($fb) while ($row = $fb->fetch_assoc()) $rows[] = formatProduct($row, $m, $base);
        }
    }

    // ── Single-keyword fallback: if keyword search returned nothing, try one keyword alone ──
    if (empty($rows) && $hasKeywordSearch && !$brand) {
        // Pick the most specific keyword (longest) from the message and search alone
        $singleKw = '';
        foreach ($allKeywords as $kw) {
            if (mb_strpos($m, $kw) !== false && mb_strlen($kw) > mb_strlen($singleKw)) {
                $singleKw = $kw;
            }
        }
        if ($singleKw) {
            $safe = $db->real_escape_string($singleKw);
            $fb   = $db->query("SELECT p.id, p.name, p.slug, p.price, p.price_after_discount, p.image, p.sale, p.occasion_tag, b.name brand_name, ROUND(((p.price-p.price_after_discount)/NULLIF(p.price,0))*100) discount_pct FROM products p LEFT JOIN brand b ON FIND_IN_SET(b.id,p.brand) WHERE (p.name LIKE '%{$safe}%' OR COALESCE(p.description,'') LIKE '%{$safe}%') AND LOWER(COALESCE(p.stock,'')) NOT IN ('out of stock','0') ORDER BY COALESCE(p.ai_score,50) DESC LIMIT 8");
            if ($fb) while ($row = $fb->fetch_assoc()) $rows[] = formatProduct($row, $m, $base);
        }
    }

    if (empty($rows) && !$brand && !$hasKeywordSearch && !$hasBudgetSearch && !$hasOccasion) {
        // General browse — show latest top-scored products
        $fb = $db->query("SELECT p.id, p.name, p.slug, p.price, p.price_after_discount, p.image, p.sale, p.occasion_tag, b.name brand_name, ROUND(((p.price-p.price_after_discount)/NULLIF(p.price,0))*100) discount_pct FROM products p LEFT JOIN brand b ON FIND_IN_SET(b.id,p.brand) WHERE LOWER(COALESCE(p.stock,'')) NOT IN ('out of stock','0') ORDER BY COALESCE(p.ai_score,50) DESC, RAND() LIMIT 8");
        if ($fb) while ($row = $fb->fetch_assoc()) $rows[] = formatProduct($row, '', $base);
    }

    // ── RELAX FILTERS: category+occasion combo returned nothing ──────────────
    // e.g. "mens wear for mehndi" — no men's products with mehndi tag exist
    // → try category alone first, then occasion alone
    if (empty($rows) && !$brand && $matchedOccTag !== null) {
        // Step 1: find which category was applied (men/women/kids)
        $catId = null;
        foreach ($where as $w) {
            if (preg_match('/p\.category\s*=\s*(\d+)/', $w, $cm)) {
                $catId = (int)$cm[1]; break;
            }
        }
        if ($catId) {
            // Try category without occasion filter
            $fb = $db->query("SELECT p.id, p.name, p.slug, p.price, p.price_after_discount, p.image, p.sale, p.occasion_tag, b.name brand_name, ROUND(((p.price-p.price_after_discount)/NULLIF(p.price,0))*100) discount_pct FROM products p LEFT JOIN brand b ON FIND_IN_SET(b.id,p.brand) WHERE p.category = {$catId} AND LOWER(COALESCE(p.stock,'')) NOT IN ('out of stock','0') ORDER BY COALESCE(p.ai_score,50) DESC, RAND() LIMIT 8");
            if ($fb) while ($row = $fb->fetch_assoc()) $rows[] = formatProduct($row, $m, $base);
        }
        // Step 2: still empty → try occasion from any category
        if (empty($rows)) {
            $safe = $db->real_escape_string($matchedOccTag);
            $fb = $db->query("SELECT p.id, p.name, p.slug, p.price, p.price_after_discount, p.image, p.sale, p.occasion_tag, b.name brand_name, ROUND(((p.price-p.price_after_discount)/NULLIF(p.price,0))*100) discount_pct FROM products p LEFT JOIN brand b ON FIND_IN_SET(b.id,p.brand) WHERE (COALESCE(p.occasion_tag,'') LIKE '%{$safe}%' OR p.name LIKE '%{$safe}%') AND LOWER(COALESCE(p.stock,'')) NOT IN ('out of stock','0') ORDER BY COALESCE(p.ai_score,50) DESC LIMIT 8");
            if ($fb) while ($row = $fb->fetch_assoc()) $rows[] = formatProduct($row, $m, $base);
        }
        // Step 3: still empty → show general top products
        if (empty($rows)) {
            $fb = $db->query("SELECT p.id, p.name, p.slug, p.price, p.price_after_discount, p.image, p.sale, p.occasion_tag, b.name brand_name, ROUND(((p.price-p.price_after_discount)/NULLIF(p.price,0))*100) discount_pct FROM products p LEFT JOIN brand b ON FIND_IN_SET(b.id,p.brand) WHERE LOWER(COALESCE(p.stock,'')) NOT IN ('out of stock','0') ORDER BY COALESCE(p.ai_score,50) DESC, RAND() LIMIT 8");
            if ($fb) while ($row = $fb->fetch_assoc()) $rows[] = formatProduct($row, '', $base);
        }
    }
    // If brand searched but not found → return empty (AI explains honestly)

    return $rows;
}

function formatProduct($row, $msg, $base) {
    // Use admin-set occasion_tag if available
    $occMap = [
        'eid'     => 'Eid',
        'wedding' => 'Wedding',
        'office'  => 'Office',
        'summer'  => 'Summer',
        'winter'  => 'Winter',
        'kids'    => 'Kids',
        'bridal'  => 'Bridal',
        'festive' => 'Festive',
        'party'   => 'Party',
        'mehndi'  => 'Mehndi',
        'casual'  => 'Casual',
    ];
    $adminTag = trim((string)($row['occasion_tag'] ?? ''));
    if ($adminTag && isset($occMap[$adminTag])) {
        $ai = $occMap[$adminTag];
    } else {
        $ai = 'AI Pick';
        if (mb_strpos($msg, 'eid') !== false)    $ai = 'Eid';
        elseif (mb_strpos($msg, 'wedding') !== false || mb_strpos($msg, 'shadi') !== false) $ai = 'Wedding';
        elseif (mb_strpos($msg, 'kids') !== false)   $ai = 'Kids';
        elseif (mb_strpos($msg, 'office') !== false)  $ai = 'Office';
        elseif (mb_strpos($msg, 'summer') !== false)  $ai = 'Summer';
        elseif (mb_strpos($msg, 'winter') !== false)  $ai = 'Winter';
        elseif (mb_strpos($msg, 'punjabi') !== false || mb_strpos($msg, 'phulkari') !== false) $ai = 'Punjabi';
        elseif (mb_strpos($msg, 'sindhi') !== false || mb_strpos($msg, 'ajrak') !== false)     $ai = 'Sindhi';
        elseif (mb_strpos($msg, 'balochi') !== false) $ai = 'Balochi';
        elseif (mb_strpos($msg, 'pashtun') !== false || mb_strpos($msg, 'pashto') !== false)   $ai = 'Pashtun';
        elseif (mb_strpos($msg, 'kashmiri') !== false || mb_strpos($msg, 'kashmir') !== false) $ai = 'Kashmiri';
        elseif (($row['sale'] ?? '') === 'In Sale')    $ai = 'On Sale';
        elseif (mb_strpos($msg, 'new') !== false || mb_strpos($msg, 'latest') !== false) $ai = 'New';
    }
    return [
        'id'          => (int)($row['id'] ?? 0),
        'name'        => html_entity_decode((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'slug'        => (string)($row['slug'] ?? ''),
        'brand'       => (string)($row['brand_name'] ?: 'Kapraa'),
        'price'       => number_format((float)($row['price'] ?? 0), 0),
        'sale_price'  => number_format((float)($row['price_after_discount'] ?? 0), 0),
        'discount'    => ((int)($row['discount_pct'] ?? 0) > 0 ? (int)$row['discount_pct'] . '% OFF' : ''),
        'image_url'   => trim(explode(',', (string)($row['image'] ?? ''))[0] ?? ''),
        'product_url' => $base . '/product/' . (string)($row['slug'] ?? ''),
        'is_on_sale'  => (($row['sale'] ?? '') === 'In Sale'),
        'ai_reason'   => $ai,
    ];
}

// ===========================================================
// CONTEXT FOR CHIPS
// ===========================================================
function getContext($msg, $brand, $products) {
    $m = mb_strtolower($msg);
    if (mb_strpos($m, 'sale') !== false || mb_strpos($m, 'discount') !== false) return 'sale';
    if (mb_strpos($m, 'kids') !== false || mb_strpos($m, 'bacha') !== false || mb_strpos($m, 'children') !== false) return 'kids';
    if (mb_strpos($m, 'women') === false &&
        (preg_match('/\bmen\b|\bmens\b|\bmen\'s\b|\bgents\b|\bmardana\b/u', $m) || preg_match('/\bmale\b/u', $m))
    ) return 'men';
    if ($brand || mb_strpos($m, 'women') !== false || mb_strpos($m, 'lawn') !== false || mb_strpos($m, 'suit') !== false) return 'women';
    return 'default';
}

// ===========================================================
// ASK GEMINI — THE BRAIN
// This is where all the magic happens.
// We give Gemini full store context + conversation history
// and let it reply naturally like a human.
// ===========================================================
function askGemini($message, $history, $langMode, $products, $brand, $store, $msgCount, $userLevel = 'normal', $specificProduct = null, $isShowAll = false, $lastProducts = [], $isProjectQuery = false, $isNewProductQ = false) {
    if (!GEMINI_KEY) return simpleFallback($message, $products, $brand, $store);

    // Base URL (needed throughout this function)
    $isLocalFn = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
              || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;
    $baseFn = $isLocalFn ? 'http://localhost/kapraa' : 'https://kapraa.pk';

    $isUrdu  = isUrdu($langMode, $message);
    $pCount  = count($products);
    $bName   = $brand['name'] ?? 'none';
    $allBrands  = implode(', ', array_slice($store['brands'], 0, 20));
    $saleBrands = implode(', ', $store['sale_brands']);

    // Language instruction based on user level + lang mode
    $lang = match ($userLevel) {
        'beginner'  => 'LANGUAGE LEVEL: IELTS Band 2–3 English.
Use VERY simple, everyday words only — the kind a 10-year-old understands.
Short sentences. Maximum 8 words per sentence.
It is OKAY to mix in Urdu words written in English letters (Roman Urdu) to help the user understand — like "ji", "bilkul", "yeh suit", "bahut acha", "shukriya", "zaroor", "kya chahiye", "bahut sasta", "bahut sundar".
Example style: "Ji! Yeh suit bahut sundar hai. Color green hai. Price Rs 2,500 hai. Aap ko chahiye?"
DO NOT use: long words, complex grammar, fashion jargon, difficult vocabulary.
NEVER use actual Urdu/Arabic script characters — only Roman Urdu (Urdu in English letters).',

        'advanced'  => 'LANGUAGE LEVEL: IELTS Band 7–9 English — academic, professional, eloquent.
Write in PURE ENGLISH ONLY. Zero Urdu, zero Roman Urdu, zero "ji/bilkul/yaar/mashallah".
Use rich, sophisticated vocabulary: silhouette, drape, motif, jacquard, couture, bespoke, artisanal, palette, composition, intricate, impeccable, curated, aesthetic, embellishment, resplendent, exquisite, lustrous, diaphanous.
Use complex sentence structures: relative clauses, passive voice where appropriate, varied sentence lengths.
Tone: confident, knowledgeable, like a Vogue editor or high-end boutique consultant.
Example: "This embroidered three-piece lawn ensemble features an intricately crafted floral motif, rendered in a resplendent palette of emerald and gold — an exceptional choice for formal occasions."
Grammar must be FLAWLESS. No colloquialisms. No casual tone.',

        default     => match ($langMode) {
            'urdu'    => 'LANGUAGE LEVEL: IELTS Band 5 Roman Urdu. Reply mostly in Roman Urdu (Urdu words written in English letters). You may use simple English words mixed in.',
            'english' => 'LANGUAGE LEVEL: IELTS Band 5 English. Reply in clear, simple English. Short sentences. Easy vocabulary. No Urdu words.',
            default   => 'LANGUAGE LEVEL: IELTS Band 5 English — clear, conversational, easy to understand.
Use everyday vocabulary — not too simple, not too complex.
Short to medium sentences. Friendly casual tone.
You may use 1–2 light Urdu words per reply (like "ji" or "bilkul") to feel warm, but the main text must be in plain English.
Example: "Ji! We have some really nice lawn suits in this collection. The prices start from Rs 2,500. Want me to show you more options?"',
        },
    };

    $allCategories  = implode(', ', $store['categories'] ?? []);
    $allSubs        = implode(', ', array_slice($store['subcategories'] ?? [], 0, 25));
    $occasionTagStr = !empty($store['occasion_tags']) ? implode(', ', $store['occasion_tags']) : 'none tagged yet';
    $culturalTagStr = !empty($store['cultural_tags']) ? implode(', ', $store['cultural_tags']) : 'none tagged yet';

    // ── Current time awareness ──────────────────────────────
    $pkTime    = new DateTime('now', new DateTimeZone('Asia/Karachi'));
    $timeStr   = $pkTime->format('h:i A');
    $hour      = (int)$pkTime->format('G');
    $timeOfDay = match(true) {
        $hour >= 5  && $hour < 12 => 'morning',
        $hour >= 12 && $hour < 15 => 'afternoon (Zuhr time)',
        $hour >= 15 && $hour < 18 => 'afternoon',
        $hour >= 18 && $hour < 20 => 'evening',
        $hour >= 20 && $hour < 23 => 'night',
        default                   => 'late night',
    };
    $timeNote = "CURRENT TIME (Pakistan): {$timeStr} — it is {$timeOfDay}. Use this naturally if greeted (e.g. 'Good morning!' / 'Good evening!') but DO NOT mention the time unless it's relevant or the user asks.";

    // ── PROJECT QUERY OVERRIDE ──────────────────────────────────
    if ($isProjectQuery) {
        $productNote = "PROJECT QUERY: A teacher or evaluator is asking about the Kapraa Final Year Project. Answer in detail using the FYP KNOWLEDGE SECTION below. NO product carousel needed. Respond professionally and confidently.";
    }

    // ── NEW / RECENTLY ADDED PRODUCTS OVERRIDE ─────────────────
    if ($isNewProductQ && $pCount > 0) {
        // Build summary of the newest products for the AI
        $newSummary = '';
        foreach (array_slice($products, 0, 5) as $np) {
            $npName  = $np['name'] ?? '';
            $npPrice = isset($np['price_after_discount']) ? 'Rs ' . number_format((int)$np['price_after_discount']) : '';
            $npBrand = $np['brand_name'] ?? '';
            $npDate  = $np['added_at'] ?? '';
            $newSummary .= "• {$npName}" . ($npBrand ? " by {$npBrand}" : '') . ($npPrice ? " — {$npPrice}" : '') . ($npDate ? " (added: {$npDate})" : '') . "\n";
        }
        $productNote = "RECENTLY ADDED PRODUCTS — fetched live from DB right now (most recent first):\n{$newSummary}"
                     . "The user is asking what was recently/just added. Tell them the newest product(s) shown in the carousel below. "
                     . "Point to the carousel: 'Here are the latest products added to the store 👇'. "
                     . "Mention the most recently added one by name. Do NOT guess — use only the data above.";
    } elseif ($isNewProductQ && $pCount === 0) {
        $productNote = "NEW PRODUCT QUERY but no products found in DB. Tell the user the store appears to have no products yet, or all are out of stock.";
    }

    $extBrand = detectExternalBrand($message);
    $isSpecific = isSpecificProductQuery($message);
    if ($extBrand) {
        $productNote = "IMPORTANT: Customer asked for '{$extBrand}' — this brand is NOT in our store. NO products are being shown. Tell the customer clearly in English that we don't carry {$extBrand}, then suggest 2-3 similar brands from OUR brand list: {$allBrands}. Be friendly and helpful.";
    } elseif ($isSpecific) {
        if ($specificProduct) {
            $spName  = $specificProduct['name'];
            $spPrice = number_format((float)$specificProduct['price'], 0);
            $spSlug  = $specificProduct['slug'] ?? '';
            $productNote = "SPECIFIC PRODUCT — REAL DATA FROM DATABASE (use EXACTLY, do NOT guess or change the price):\n"
                         . "• Name: {$spName}\n"
                         . "• Price: Rs {$spPrice}\n"
                         . ($spSlug ? "• URL: {$baseFn}/product/{$spSlug}\n" : '')
                         . "NO product carousel is shown. State the EXACT price above. Never invent a different number.";
        } elseif (!empty($lastProducts)) {
            // We couldn't identify the specific product by name/URL,
            // but we know what was shown in the last carousel — use that as context.
            $lpCount = count($lastProducts);
            if ($lpCount === 1) {
                // Only one product was shown — "this dress" clearly means that one
                $lp      = $lastProducts[0];
                $lpName  = html_entity_decode((string)($lp['name'] ?? ''), ENT_QUOTES, 'UTF-8');
                $lpPrice = number_format((float)($lp['price_after_discount'] ?? $lp['price'] ?? 0), 0);
                $lpSlug  = (string)($lp['slug'] ?? '');
                $productNote = "CONTEXT: The customer is asking about the product that was just shown in the previous carousel. REAL DATA:\n"
                             . "• Name: {$lpName}\n"
                             . "• Price: Rs {$lpPrice}\n"
                             . ($lpSlug ? "• URL: {$baseFn}/product/{$lpSlug}\n" : '')
                             . "Answer using EXACTLY this data. State the exact price. Do NOT say you can't find the product.";
            } else {
                // Multiple products were shown — list all their prices so the AI can answer
                $lpLines = '';
                foreach (array_slice($lastProducts, 0, 6) as $lp) {
                    $lpName  = html_entity_decode((string)($lp['name'] ?? ''), ENT_QUOTES, 'UTF-8');
                    $lpPrice = number_format((float)($lp['price_after_discount'] ?? $lp['price'] ?? 0), 0);
                    $lpSlug  = (string)($lp['slug'] ?? '');
                    $lpLines .= "• {$lpName} — Rs {$lpPrice}" . ($lpSlug ? " ({$baseFn}/product/{$lpSlug})" : '') . "\n";
                }
                $productNote = "CONTEXT: The customer is asking about price/details of the products shown in the last carousel. "
                             . "Here are ALL {$lpCount} products with their EXACT prices (use these, do NOT invent prices):\n"
                             . $lpLines
                             . "If the customer says 'this dress' and only one product matches their description, give its exact price. "
                             . "If unclear which one they mean, list all prices above and ask which one they're referring to.";
            }
        } else {
            $productNote = "SPECIFIC PRODUCT INQUIRY: Customer asked about a particular product but we couldn't find it in our database. Tell them you couldn't find that exact product and ask them to double-check the link or name. Do NOT invent a price.";
        }
    } elseif ($isShowAll && $pCount > 0) {
        $productNote = "SHOW-ALL BRAND REQUEST: The customer wants to see the full {$bName} collection. "
                     . "ALL {$pCount} products are displayed below in the carousel automatically. "
                     . "DO NOT ask about size. DO NOT start an order flow. DO NOT ask anything. "
                     . "Just say: 'Here's the complete {$bName} collection — all {$pCount} designs are below! 👇' "
                     . "Then add one exciting sentence about this brand. Nothing else.";
    } elseif ($isShowAll && $pCount === 0) {
        $productNote = "SHOW-ALL REQUEST but no products found. Say we don't currently have stock, then suggest similar brands.";
    } elseif ($brand && $pCount > 0) {
        $brandReplyEx = ($userLevel === 'advanced')
            ? "Say something like 'We have {$pCount} {$bName} designs in stock — browse below 👇' and add a brief style note."
            : "Say something like 'Ji! Hamare paas {$bName} ke {$pCount} designs hain — neeche check karein 👇' and add a brief style note.";
        $productNote = "BRAND QUERY: Customer asked about {$bName}. ALL {$pCount} matching products are shown below in the carousel. "
                     . "DO NOT ask about size or payment. DO NOT start an order. "
                     . $brandReplyEx
                     . " If customer wants to ORDER a specific product AFTER seeing the carousel, THEN ask about size.";
    } elseif ($pCount > 0) {
        $checkBelow = ($userLevel === 'advanced') ? 'browse these below 👇' : 'check these out below 👇';
        // Build a compact product summary so AI can answer follow-up price/detail questions
        $prodSummary = '';
        foreach (array_slice($products, 0, 5) as $pr) {
            $pn = $pr['name'] ?? '';
            $pp = isset($pr['price_after_discount']) ? 'Rs ' . number_format((int)$pr['price_after_discount']) : '';
            $pb = $pr['brand_name'] ?? '';
            $prodSummary .= "• {$pn}" . ($pb ? " by {$pb}" : '') . ($pp ? " — {$pp}" : '') . "\n";
        }
        $productNote = "⚠️ PRODUCTS ARE SHOWN IN CAROUSEL BELOW ({$pCount} items). "
                     . "MANDATORY: Your reply MUST include a phrase like '{$checkBelow}' to direct the user to the carousel. "
                     . "NEVER give a plain 'yes/no' answer without pointing to what's shown. "
                     . "DO NOT list product names in text. Add ONE short style comment after directing to carousel.\n"
                     . "PRODUCT DETAILS (for price/detail follow-up questions):\n{$prodSummary}";
    } else {
        $productNote = "NO PRODUCTS SHOWN: User asked a general question. Give a helpful response and ask what they're looking for (brand, occasion, or budget).";
    }

    // THE SYSTEM PROMPT — clothing-focused, human, live DB aware
    $featuredList = !empty($store['featured_products'])
        ? implode("\n", array_map(fn($p) => "• {$p}", $store['featured_products']))
        : '(none listed)';

    $setSuitList = !empty($store['set_suits'])
        ? implode("\n", array_map(fn($s) => "• {$s}", $store['set_suits']))
        : '(none available)';

    // Build complete brand directory for the system prompt
    // Format: "Brand Name — 24 products | Volumes: Vol-01 (10), Vol-02 (8), Vol-03 (6)"
    $brandDirectory = '';
    if (!empty($store['brand_stats'])) {
        foreach ($store['brand_stats'] as $bn => $bs) {
            $line = "• {$bn} — {$bs['count']} products in stock";
            if (!empty($bs['volumes'])) {
                $line .= " | Volumes: " . implode(', ', $bs['volumes']);
            }
            if (!empty($store['brand_urls'][$bn])) {
                $line .= " | " . $store['brand_urls'][$bn];
            }
            $brandDirectory .= $line . "\n";
        }
    }
    $totalBrandsReal = count($store['brand_stats']);

    // Build brand volume note for currently-detected brand
    $brandVolumeNote = '(No specific brand detected in this message)';
    if (!empty($store['brand_volumes'])) {
        $bv = $store['brand_volumes'];
        $brandVolumeNote = "• {$bName} has {$bv['total_products']} products in stock.";
        if ($bv['total_volumes'] > 0) {
            $vParts = [];
            foreach ($bv['volumes'] as $v) {
                $part = $v['label'] . " ({$v['count']} products";
                if (!empty($v['price_min'])) {
                    $part .= ", Rs " . number_format((int)$v['price_min'], 0);
                    if ($v['price_max'] !== $v['price_min']) $part .= "–" . number_format((int)$v['price_max'], 0);
                }
                $part .= ")";
                $vParts[] = $part;
            }
            $brandVolumeNote .= "\n• Volumes: " . implode(', ', $vParts);
        } else {
            $brandVolumeNote .= "\n• No volume-based collections for this brand.";
        }
    }

    // Pre-compute value that can't be used as expression inside heredoc
    $bNameDisplay = $bName ?: 'current collection';

    // Language-specific snippets for injection into system prompt
    $whoYouAreStyle = ($userLevel === 'advanced')
        ? '• Sophisticated, professional tone — like a Vogue editor or luxury boutique consultant
• PURE ENGLISH ONLY — zero Urdu, zero Roman Urdu, zero "ji/bilkul/yaar/mashallah"
• You have refined opinions: "Personally, this embroidered chiffon ensemble is impeccable for formal occasions."'
        : '• Warm, friendly, like texting a very knowledgeable friend
• Mix English + Roman Urdu naturally: "ji", "bilkul", "yaar", "mashallah", "haan"
• You have real opinions: "personally, embroidered lawn is the best for Eid!"';

    $greetingExample = ($userLevel === 'advanced')
        ? 'Example: "Hello! 👋 I\'m Laiba, your fashion consultant at Kapraa. What are you looking for today?"'
        : 'Example: "Salam! 👋 Kya haal hai? Main Laiba hoon — Kapraa ki fashion expert. Kya dhundh rahe hain aaj?"';

    $followUpExamples = ($userLevel === 'advanced')
        ? 'Ask ONE relevant follow-up: "What is your budget?", "What occasion is this for?", "Do you prefer stitched or unstitched?", "What size do you need?"'
        : 'Ask ONE relevant follow-up: "Kya budget hai?", "Kis occasion ke liye?", "Stitched chahiye ya unstitched?", "Konsa size?"';

    $rule8BrandEx = ($userLevel === 'advanced')
        ? '"We don\'t carry [brand] at Kapraa. However, we carry [2-3 real brands from directory] — excellent alternatives!"'
        : '"Nahi yaar, hum [brand] carry nahi karte. But we have [2-3 real brands from directory]!"';

    $rule9ImageYes = ($userLevel === 'advanced')
        ? '"Yes, we have similar styles — browse below 👇"'
        : '"Ji yes! We have similar styles, check below 👇"';
    $rule9ImageNo = ($userLevel === 'advanced')
        ? '"We don\'t carry this exact style currently — tell me your occasion and I\'ll find something close!"'
        : '"Ye exact style hamare paas nahi hai filhaal, but tell me your occasion and I\'ll find something close!"';

    $system = <<<SYSTEM
You are LAIBA — a fashion & clothing expert at Kapraa, a Pakistani clothing store. You are a master of ALL clothing, textile, and fashion knowledge — not just our store.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
WHO YOU ARE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
• 24-year-old Pakistani woman — fashion expert with deep knowledge
• You work at Kapraa but you know ALL about clothing, fabrics, fashion globally
$whoYouAreStyle
• You remember what was said earlier in this chat

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
KAPRAA — LIVE STORE DATA
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Website: kapraa.pk
Total products in stock: {$store['total_products']}
Total brands: {$store['total_brands']}
Products on sale: {$store['sale_count']}
Price range: Rs {$store['price_min']} to Rs {$store['price_max']}
New arrivals: {$store['new_count']} items

CATEGORIES: {$allCategories}
SUBCATEGORIES: {$allSubs}
BRANDS CURRENTLY ON SALE: {$saleBrands}

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
COMPLETE BRAND DIRECTORY — LIVE FROM DATABASE
Total brands we carry: {$totalBrandsReal}
IMPORTANT: This list is 100% accurate. A brand is IN our store ONLY if it appears below.
If customer asks "do you have [brand]?" — check this list. If not here, we DON'T carry it.
If customer asks "how many brands?" — answer EXACTLY {$totalBrandsReal}.
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
{$brandDirectory}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

TOP FEATURED PRODUCTS:
{$featuredList}

SET SUIT DETAILS (use EXACTLY — never guess):
{$setSuitList}

CONTACT & POLICIES:
• WhatsApp/Phone: {$store['phone']}
• Email: {$store['email']}
• Address: {$store['address']}
• Delivery: {$store['delivery_days']} working days, Rs {$store['delivery_charge']} anywhere in Pakistan
• Returns: Within 7 days of receiving
• Wholesale/Bulk: kapraa.pk/wholesale (5–20% discount)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
WHAT WE SELL
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Lawn suits, linen, khaddar, chiffon, cotton, silk, georgette
Unstitched & stitched suits (2-piece, 3-piece)
Embroidered, printed, digital print, block print designs
Shalwar kameez, kurtas, dupattas, lehenga, sharara, gharara
Bridal wear, festive, Eid, wedding collections
Women's wear, men's wear, kids wear
Seasonal: summer lawn/chiffon, winter khaddar/linen/wool
Traditional & Cultural dresses: Punjabi, Sindhi, Balochi, Pashtun, Kashmiri

OCCASION CATEGORIES — products tagged in database:
{$occasionTagStr}
(These are REAL occasion categories with products. When customer asks for these, products ARE available.)
• Wedding/Shadi: bridal, mehndi, walima outfits
• Eid: festive, Eid-ul-Fitr, Eid-ul-Adha collections
• Casual/Daily: everyday wear, relaxed outfits
• Office/Formal: professional, smart casual
• Sports/Athletic: gym wear, active wear, sportswear
• Party: party wear, event dressing
• Winter: khaddar, linen, warm fabrics
• Summer/Lawn: lawn suits, chiffon, breathable fabrics

CULTURAL DRESS CATEGORIES — products tagged in database:
{$culturalTagStr}
(These are REAL cultural categories with products. When customer asks, show what we have.)
• Punjabi: Phulkari embroidery, vibrant colors, shalwar kameez with mirror work, khussa-style
• Sindhi: Ajrak prints, bright embroidery, mirror work, Sindhi topi style
• Balochi: Balochi embroidery, intricate mirror work, rich earth tones
• Pashtun: Khet partug style, Perahan tunban, Peshawari chappal-friendly, earth tones
• Kashmiri: Pheran style, Kashmiri embroidery (tilla/sozni), pashmina shawls, floral motifs

Currently detected brand: {$bName}
CURRENT BRAND DETAIL:
{$brandVolumeNote}

{$productNote}

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
YOUR EXPERT CLOTHING KNOWLEDGE (answer these questions perfectly)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

FABRIC GUIDE:
• Lawn — lightweight cotton, breathable, best for Pakistani summers (March–Sept). Comes printed/embroidered.
• Khaddar — thick handwoven cotton, warm and comfortable, perfect for winter (Oct–Feb).
• Linen — natural, breathable, all-season but especially good spring/autumn. Wrinkles easily.
• Chiffon — sheer, flowy, elegant. Great for dupattas, formal/bridal wear. Delicate, dry-clean preferred.
• Cotton — everyday wear, comfortable, machine washable, absorbs sweat well.
• Silk — luxurious, smooth, formal occasions. Dry-clean only, avoid water stains.
• Georgette — semi-sheer, flowy, good for formal outfits and parties.
• Karandi / Khaddi — thick winter fabric, warm, good for casual winter wear.
• Organza — stiff, sheer, used for bridal dupattas and formal wear.
• Velvet — rich texture, very formal, popular for winter bridal/party wear.

FABRIC CARE & WASHING:
• Lawn: machine wash cold, gentle cycle, air dry flat, low-iron on reverse
• Khaddar: hand wash or gentle machine cold, air dry, steam iron
• Chiffon/Silk: dry clean only OR very gentle hand wash cold, do NOT wring, lay flat to dry
• Linen: machine wash cool, remove promptly to prevent creasing, medium iron while damp
• Cotton: machine wash warm/cold, tumble dry low, medium-hot iron
• Embroidered suits: ALWAYS hand wash cold / dry clean — machine washing destroys embroidery
• Digital print: cold wash inside-out to preserve print colors
• DO NOT bleach any Pakistani clothing
• Iron temperatures: silk=cool (110°C), chiffon=cool (110°C), cotton=medium (150°C), linen=hot (200°C), khaddar=medium

COLOR MATCHING FOR PAKISTANI OUTFITS:
• Classic combos: navy+gold, maroon+silver, green+pink, mustard+brown, teal+orange
• Eid: bright colors — pink, mint, lemon yellow, sky blue, coral
• Wedding guest: jewel tones — emerald, royal blue, deep purple, burgundy
• Mehndi/Dholki: yellow, green, orange, multi-color, festive bright
• Baraat/Walima: pastels or rich jewel tones depending on role (bride=lighter, guests=deeper)
• Office: navy, grey, olive, soft pastels, neutrals — avoid loud prints
• Casual: any color, comfortable fits
• Dupatta contrast rule: If suit is solid, dupatta can be printed. If suit is printed, dupatta solid.
• Gold/silver accessories look best with jewel tones. Colorful accessories with pastels.

PAKISTANI OCCASION DRESS GUIDE:
• Eid ul Fitr: new bright embroidered suits, lawn or chiffon, festive colors
• Eid ul Adha: slightly more subtle, comfortable fabric for visiting/hosting
• Mehndi (haldi): yellow/green/orange, bright, festive, lehenga or 3-piece suits
• Dholki: colorful, fun, any traditional Pakistani outfit
• Baraat (groom side): usually matching family color scheme, formal suits
• Walima: guest outfit should be elegant but not outshine bride — pastels or jewel tones
• Wedding guest: floor-length formal suit, chiffon or silk, embroidered
• Office/Work: stitched shalwar kameez, solid colors, modest, comfortable
• Casual daily wear: printed lawn, comfortable cotton, simple designs
• Namaz/Religious: modest, full coverage, light colors preferred, no tight fits
• Winter casual: khaddar or linen suit, warm colors (maroon, brown, bottle green)
• Summer casual: light lawn, pastel colors, breathable fabric

STITCHING KNOWLEDGE:
• Standard sizes: XS(34"), S(36"), M(38"), L(40"), XL(42"), XXL(44"), XXXL(46")
• Kameez length: Knee-length=casual, Calf-length=semi-formal, Floor-length=formal/bridal
• Necklines: Round neck (everyday), V-neck (slimming), Square neck (festive), Collar (formal/men's)
• Sleeves: Full sleeve=formal/modest, 3/4 sleeve=semi-formal, Short sleeve=casual
• Shalwar types: Straight shalwar (classic), Cigarette pant (modern), Palazzo (trendy), Bell-bottom (retro)
• Dupatta styles: Pinned (formal), Draped one shoulder (casual), Tied around waist (modern), Not used (casual)
• Embroidery placement: Neckline only=simple, Front panel=semi-formal, All-over=bridal/heavy formal
• Stitching cost tip: simple suit Rs 1,500-3,000; embroidered suit Rs 3,000-8,000; bridal Rs 10,000+

SIZING HELP:
• If between sizes → size up for unstitched (fabric can be adjusted)
• Measure chest (fullest point), waist, hips, and kameez length from shoulder
• Pakistani women's sizing runs slightly small — when in doubt go one size bigger
• For kids: measure height and chest, not age

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
KAPRAA — FINAL YEAR PROJECT (FYP) KNOWLEDGE
Answer these confidently when teachers, evaluators, or developers ask
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

PROJECT OVERVIEW:
• Name: Kapraa Online
• Type: Final Year Project (FYP) — Full-stack AI-powered E-commerce platform
• Domain: Pakistani Textile & Clothing Industry
• Live Website: kapraa.pk
• Local: Runs on XAMPP (Apache + MySQL) at localhost/kapraa
• Purpose: Digitize the Pakistani textile shopping experience by combining a modern e-commerce platform with an intelligent AI assistant that understands cultural dresses, occasions, brands, and fashion — making it easy for Pakistani customers to browse, search, and order clothing online

TECH STACK:
• Frontend: HTML5, CSS3, JavaScript (Vanilla), Bootstrap 5
• Backend: PHP 8+ (procedural + functions, no heavy framework for performance)
• Database: MySQL (via MySQLi — prepared statements for security)
• AI Engine: Google Gemini API — model: gemini-2.5-flash-lite (fast, free-tier, complete responses)
• Image Storage/CDN: Cloudinary (stores product images, chat images — fast global CDN)
• Web Server: Apache (via XAMPP for local, cPanel hosting for live)
• Voice: MediaRecorder API (browser) → WebM/Opus audio → uploaded to server
• Real-time Chat: HTTP Long Polling (no WebSockets needed — lightweight, works on shared hosting)

KEY FEATURES:
1. AI CHATBOT (Laiba) — Powered by Google Gemini. Understands natural language in English + Roman Urdu. Searches products from DB live. Shows product carousels. Handles price queries. Remembers conversation history. Can identify clothing from uploaded images (vision). Knows all brands, occasions, cultural dresses in the store.
2. LIVE CHAT SUPPORT — Real-time admin-customer messaging via polling. Supports text, image upload, and voice messages (WhatsApp-style custom player with waveforms). Multi-session support. Admin sees all chats with unread badges. User can delete chats (admin sees deletion notification).
3. COD ORDER FLOW — Customers can place Cash on Delivery orders directly through the AI chat. AI collects: product, size, name, phone, address → saves to database → sends to admin.
4. PRODUCT CATALOG — Products have: name, price, discounted price, images (multi), brand, category, subcategory, occasion_tag, cultural_tag, ai_score (for ranking), stock status, sale status, description, slug.
5. IMAGE SEARCH — Customer uploads clothing image → Gemini Vision identifies the style → system searches similar products in DB → shows carousel.
6. CULTURAL DRESS SEARCH — Products tagged with cultural origins: Punjabi, Sindhi, Balochi, Pashtun, Kashmiri. AI ranks exact cultural matches above multi-tagged products.
7. OCCASION SEARCH — Products tagged by occasion: eid, wedding, office, casual, sports, party, festive. Intelligent ranking: exact matches rank above partial.
8. ADMIN PANEL — Full management: add/edit/delete products, manage brands, categories, orders, live chats, quick replies, store settings. Admin live chat shows WhatsApp-style voice player + avatar with first letter of visitor name.
9. VOICE MESSAGES — Both AI chat (voice-to-text queries) and Live Chat (record + send voice, WhatsApp-style waveform player). Uses MediaRecorder → WebM → server upload.
10. BRAND DIRECTORY — Complete catalog of brands with product counts, volumes, URLs. AI checks this live from DB.
11. NEW PRODUCT AWARENESS — When admin adds a product, AI immediately knows (DB queried fresh every request). Admin can ask "what did I just add?" and AI shows it.
12. MULTI-LANGUAGE — Auto-detects English vs Roman Urdu and responds in matching style. Advanced/beginner user level detection.
13. CLOUDINARY CDN — Product images served via Cloudinary for fast global loading. Chat images also uploaded to Cloudinary.
14. RESPONSIVE DESIGN — Mobile-first design, works on all screen sizes.
15. SECURITY — MySQLi prepared statements prevent SQL injection. Session-based auth for admin. MIME-type validation for file uploads.

DATABASE TABLES:
• products — main product catalog (id, name, slug, price, price_after_discount, image, brand, category, subcategory, stock, sale, occasion_tag, cultural_tag, ai_score, description, created_at)
• brand — clothing brands (id, name, slug, logo, description, url)
• category — product categories: Women, Men, Kids (id, name)
• subcategory — sub-categories like Lawn, Linen, Khaddar (id, name, main_category)
• orders — COD orders placed through AI chat (id, product_id, customer_name, phone, address, size, status, created_at)
• live_chats — live chat sessions (id, session_id, visitor_name, status, user_deleted, created_at, updated_at)
• live_chat_messages — individual messages (id, chat_id, sender, message, message_type, file_url, created_at)
• store_settings — store info: phone, email, address, delivery info (key-value pairs)
• quick_replies — admin saved quick-reply templates (id, text, created_at)

HOW THE AI WORKS (technical):
1. User sends message → ai_chat.php receives it
2. PHP detects intent: needs products? specific product? brand? cultural? occasion? new product? project question?
3. If products needed → MySQL query with filters (brand, category, occasion_tag, cultural_tag, budget)
4. Product data + conversation history + store context → assembled into Gemini system prompt
5. Gemini API called (HTTP POST via cURL) → returns natural language reply
6. Response sent back: {reply, products[], context}
7. Widget renders: text reply + product carousel cards (image, name, price, add-to-cart style buttons)
8. Conversation history maintained on frontend → sent back each request for multi-turn memory

HOW LIVE CHAT WORKS:
• User starts chat → POST to live_chat.php → creates session in DB → gets chat_id + session_id
• Every 3 seconds → poll action → fetches new messages from DB → renders in chat
• Admin panel polls every 3 seconds too → sees new chats + messages in real time
• Voice: MediaRecorder records → Blob uploaded as multipart → saved to /uploads/chat/ → URL stored in DB
• User deletes chat → API marks user_deleted=1 → admin sees "DELETED" badge, cannot reply

METHODOLOGY / SDLC:
• Followed Agile-like iterative development — features added in sprints
• Planning → UI Design → Backend Development → AI Integration → Testing → Deployment
• Local development on XAMPP → Live deployment on shared hosting (cPanel, InfinityFree/paid host)

MOBILE APP (ALREADY BUILT & LIVE):
• React Native (Expo) cross-platform app — Android APK available
• Connects to same PHP REST API backend (api/chat.php, api/visual_search.php, etc.)
• Features: AI chat, product browse, cart, checkout, order tracking, visual search
• Built with: React Native, Expo Router, React Native Reanimated, Axios
• App APK shared via direct download link / Google Drive / Expo Go

PAYMENT (ALREADY IMPLEMENTED):
• COD (Cash on Delivery) — primary payment at checkout
• JazzCash — integrated at checkout
• EasyPaisa — integrated at checkout
• Order confirmation emails sent automatically after payment

FUTURE ENHANCEMENTS:
• WhatsApp API for automated order status notifications
• Product reviews and ratings system
• Push notifications for order updates
• More AI personalization (style preferences memory)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
STRICT RULES — MUST FOLLOW EVERY TIME
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

RULE 1 — TOPICS YOU HANDLE:
You are an expert on: clothing, fabrics, fashion, styling, color matching, Pakistani culture & dress codes, occasions, stitching, washing/care, sizes, accessories, shoes that match outfits, Kapraa store info, AND the Kapraa Final Year Project (FYP) system details.
For NON-clothing, NON-project topics (cricket, food, politics, health, movies, math, etc.) — politely redirect in one short line.
Use a tone like: "I specialize in clothing, fashion, and this platform — ask me anything about those!"
Exception: If someone mentions a celebrity's OUTFIT or fashion style (e.g. "I like Hania Amir's style"), you CAN discuss the fashion/style aspect only.
FYP EXCEPTION: If a teacher, professor, or evaluator asks about this system/project/technology → answer fully and professionally using the FYP KNOWLEDGE SECTION above. This is a priority topic.

RULE 1B — PRODUCT PRICING (IMPORTANT):
• Give the SINGLE PRODUCT PRICE directly when asked. Example: "Rs 2,650"
• Do NOT automatically ask "single or set?" — just give the price.
• ONLY mention bulk/set pricing IF the customer specifically asks about bulk/set.
• Use ONLY exact prices from the database. NEVER guess or invent a price.
• If price not in your data, say "Can you share the product link? I'll check the exact price for you."

RULE 2 — GREETINGS (VERY IMPORTANT):
When user says "hi", "hello", "salam", "hey", "aoa", "good morning", "good evening" or any greeting:
• Just greet back warmly and naturally — ONE or TWO lines MAX.
• {$greetingExample}
• DO NOT ask about payment, size, order, or anything else on a greeting. Just say hello!
• NEVER start an order flow from a greeting.
• IMPORTANT: If the message is about products/brands/discount/sale — DO NOT treat it as a greeting. Reply about the products.

RULE 2B — NO ROBOT WORDS:
NEVER say: "Certainly!", "Of course!", "Great question!", "As an AI", "I'd be happy to help", "Sure!", "Absolutely!"
Talk naturally like a real friendly person.

RULE 3 — BE HONEST & ACCURATE:
• If we don't have a product, say so kindly. NEVER make up products.
• If a brand is NOT in our directory → say honestly we don't carry it, suggest 2-3 REAL brands from our list.
• If products ARE shown below chat → they ARE available in our store. Say "yes we have similar ones, check below 👇". NEVER say "we don't have this" when products are shown.
• If NO products found → say we don't have this exact item but ask what occasion/budget/style they want so you can find something.
• "Do you have this?" with an image → products shown below = YES we have similar. No products = we don't have this exact item, describe what we do carry.
• NEVER say "we don't carry this" AND show products at the same time — that's contradictory.

RULE 4 — REPLY LENGTH:
• Simple questions (greetings, delivery, thanks): 1-2 lines
• Product/brand questions: 2-4 lines
• Fashion advice / fabric knowledge questions: 3-6 lines (be genuinely helpful)
• Never write walls of text. Be concise but complete.

RULE 5 — FASHION EXPERTISE IN ACTION:
When giving advice, be specific and practical. Don't just say "lawn is good for summer" — say "lawn is perfect right now because it's lightweight and breathable, and our {$bNameDisplay} has beautiful prints!"

RULE 6 — LANGUAGE (MUST FOLLOW EXACTLY):
{$lang}
This overrides everything. If language says English only — zero Urdu words.

RULE 7 — FOLLOW UP SMARTLY:
{$followUpExamples}
Never ask multiple questions at once.

RULE 8 — BRAND ACCURACY (CRITICAL):
The COMPLETE BRAND DIRECTORY above is 100% live database data.
• "Do you have [brand]?" → Check directory. Not in list → {$rule8BrandEx}
• "How many brands?" → Answer EXACTLY {$totalBrandsReal}.
• "How many products of [brand]?" → Use directory count.
• "What volumes of [brand]?" → Use directory volumes.
• NEVER claim we have a brand NOT in directory. NEVER guess.

RULE 9 — IMAGE HANDLING:
If a customer sends a photo of clothing: describe what you see (fabric, style, color, occasion suitability), then point to the similar items shown below.
If customer sends a photo of themselves and asks style advice: compliment kindly, then give specific outfit recommendations for their look/coloring.
If customer asks "do you have this?" with a photo: check if products are shown below. If yes → {$rule9ImageYes}. If no → {$rule9ImageNo}

RULE 10 — GENDER MATCHING (CRITICAL):
The system already filters products by gender from the image. Your job is to MATCH your reply to the correct gender:
• Men's image → ONLY talk about men's kurta, shalwar, gents suits. NEVER recommend women's suits.
• Kids' image → ONLY talk about kids' clothing. NEVER recommend adult clothing.
• Women's image → women's suits, lawn, embroidered, 3-piece etc.
Example: if someone shares a men's blue kurta and asks "do you have this?" → reply like "Ji yes! Ye ek mardana light blue kurta shalwar hai — hamare paas similar men's styles hain neeche 👇"
NEVER say "febiha by feroza" or any women's brand for a men's clothing query.

{$timeNote}
Message #{$msgCount} in this conversation.
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SYSTEM;

    // Build conversation history for Gemini (multi-turn)
    $contents = [];

    // Add past conversation as multi-turn messages
    foreach (array_slice($history, -10) as $h) {
        $role       = ($h['role'] === 'assistant') ? 'model' : 'user';
        $content    = trim((string)($h['content'] ?? ''));
        if ($content === '') continue;
        $contents[] = ['role' => $role, 'parts' => [['text' => $content]]];
    }

    // Current message with system prompt embedded
    $contents[] = [
        'role'  => 'user',
        'parts' => [['text' => $system . "\n\n---\nCustomer says: " . $message]]
    ];

    $payload = json_encode([
        'contents'         => $contents,
        'generationConfig' => [
            'temperature'     => 0.7,
            'maxOutputTokens' => 280,
            'topP'            => 0.90,
        ],
    ]);

    $text = geminiCall($payload);

    if (!$text || trim($text) === '') {
        // Check if simpleFallback can give a good answer; otherwise use friendly error
        $fb = simpleFallback($message, $products, $brand, $store, $userLevel);
        if ($fb) return $fb;
        $errMsg = ($userLevel === 'advanced')
            ? "Apologies — a brief technical issue occurred. Please try again, or contact us via WhatsApp: +92 320 9815213"
            : "Maafi yaar — thori technical masla aa gayi! Ek baar dobara try karein, ya seedha WhatsApp karein: +92 320 9815213 😊";
        return $errMsg;
    }

    return $text;
}

// ===========================================================
// GEMINI HTTP CALL
// ===========================================================
function geminiCall($payload, $attempt = 1) {
    $ch = curl_init(GEMINI_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 20,           // 20s — enough for Gemini on live server
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,        // avoids SSL errors on some servers
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code === 200 && $res) {
        $d    = json_decode($res, true);
        $text = trim((string)($d['candidates'][0]['content']['parts'][0]['text'] ?? ''));
        if ($text) return $text;
    }
    // 429 = rate limit: don't retry — fall through to fallback immediately
    if ($code === 429) return null;
    // Other errors: retry once immediately (no sleep — live server needs fast response)
    if ($attempt < 2) return geminiCall($payload, $attempt + 1);
    return null;
}

// ===========================================================
// SIMPLE FALLBACK (only if Gemini completely fails)
// Handles common queries locally — no Gemini needed
// ===========================================================
function simpleFallback($message, $products, $brand, $store, $userLevel = 'normal') {
    $m   = mb_strtolower($message);
    $c   = count($products);
    $b   = $brand['name'] ?? '';
    $adv = ($userLevel === 'advanced');  // pure English mode

    $totalP  = $store['total_products']   ?? '100+';
    $totalBr = $store['total_brands']     ?? '10+';
    $pMin    = $store['price_min']        ?? 1500;
    $pMax    = $store['price_max']        ?? 15000;
    $delDays = $store['delivery_days']    ?? '3–5';
    $delFee  = $store['delivery_charge']  ?? 200;
    $phone   = $store['phone']            ?? '+92 320 9815213';
    $email   = $store['email']            ?? 'contact@kapraa.pk';

    // ── FYP / Project Questions ───────────────────────────────
    $fypSignals = ['project','fyp','final year','technology','tech stack','database','how does','how do',
                   'ai work','visual search','gemini','api','react native','php','mysql','feature','architecture',
                   'what is kapraa','explain','functionality','backend','frontend','mobile app'];
    foreach ($fypSignals as $sig) {
        if (mb_strpos($m, $sig) !== false) {
            return "Kapraa is an FYP — a full-stack Pakistani e-commerce platform! "
                 . "Tech: PHP backend + MySQL + React Native mobile app. "
                 . "Key features: AI chatbot (Gemini API), visual search (image → find products), "
                 . "order management, email notifications, admin panel, and {$totalP} real products from {$totalBr} Pakistani brands. 🎓";
        }
    }

    // ── Off-topic deflection ──────────────────────────────────
    $offTopics = ['food','cricket','politics','laptop','weather','corona','hospital','salary','bitcoin','game','football'];
    foreach ($offTopics as $ot) {
        if (mb_strpos($m, $ot) !== false)
            return $adv
                ? "I specialise in fashion and clothing — share your outfit or fabric question and I'll help!"
                : "Haha, yaar main sirf fashion expert hoon! Koi suit ya dress dhundna ho toh batao! 😄";
    }

    // ── Greetings ─────────────────────────────────────────────
    if (preg_match('/\b(hi|hello|hey|salam|assalam|aoa|slamoalaikum)\b/i', $m))
        return $adv
            ? "Hello! 👋 I'm Laiba, Kapraa's AI fashion consultant. What can I help you find today?"
            : "Assalam o Alaikum! 👋 Main Laiba hoon — Kapraa ki fashion expert. Kya dhundh rahe hain aaj?";

    // ── Name / who are you ────────────────────────────────────
    if (mb_strpos($m,'name')!==false || mb_strpos($m,'who are')!==false || mb_strpos($m,'kaun')!==false || mb_strpos($m,'laiba')!==false)
        return $adv
            ? "I'm Laiba — Kapraa's AI fashion assistant! I help you find clothing, compare brands, and guide you through ordering."
            : "Main Laiba hoon — Kapraa ki AI fashion consultant! Suit, dress, brand — kuch bhi dhundna ho batayein!";

    // ── Thanks ────────────────────────────────────────────────
    if (preg_match('/\b(thanks|shukriya|jazakallah|thank|shukria)\b/i', $m))
        return $adv
            ? "My pleasure! Feel free to ask if you need any more fashion guidance. 😊"
            : "Ji bilkul, khushi hui! Kuch aur outfit help chahiye toh zaroor poochein! 😊";

    // ── Delivery / shipping ───────────────────────────────────
    if (mb_strpos($m,'delivery')!==false || mb_strpos($m,'shipping')!==false || mb_strpos($m,'kitne din')!==false)
        return $adv
            ? "Delivery takes {$delDays} working days. Nationwide shipping charge is just Rs {$delFee}."
            : "Delivery {$delDays} working days mein hoti hai — Pakistan-wide charges sirf Rs {$delFee} hain! 📦";

    // ── Return / exchange ─────────────────────────────────────
    if (mb_strpos($m,'return')!==false || mb_strpos($m,'exchange')!==false || mb_strpos($m,'wapas')!==false || mb_strpos($m,'refund')!==false)
        return $adv
            ? "Returns and exchanges are accepted within 7 days of delivery. Contact us via WhatsApp: {$phone}"
            : "Ji, delivery ke 7 din ke andar return ya exchange ho sakta hai. WhatsApp karein: {$phone} 📱";

    // ── Payment methods ───────────────────────────────────────
    if (mb_strpos($m,'payment')!==false || mb_strpos($m,'jazzcash')!==false || mb_strpos($m,'easypaisa')!==false || mb_strpos($m,'cod')!==false || mb_strpos($m,'cash')!==false)
        return $adv
            ? "We accept COD (Cash on Delivery), JazzCash, and EasyPaisa — secure and convenient!"
            : "Payment options: COD (Cash on Delivery), JazzCash, ya EasyPaisa! Jo bhi convenient lage. 💳";

    // ── Contact / WhatsApp ────────────────────────────────────
    if (mb_strpos($m,'contact')!==false || mb_strpos($m,'whatsapp')!==false || mb_strpos($m,'number')!==false || mb_strpos($m,'phone')!==false)
        return "WhatsApp: {$phone} | Email: {$email} — hum yahan hain aapki help ke liye! 😊";

    // ── Price / budget ────────────────────────────────────────
    if (mb_strpos($m,'price')!==false || mb_strpos($m,'cost')!==false || mb_strpos($m,'budget')!==false || mb_strpos($m,'kitna')!==false || mb_strpos($m,'rate')!==false)
        return $adv
            ? "Our collection ranges from Rs {$pMin} to Rs {$pMax}. What is your budget? I'll find the best options."
            : "Hamare products Rs {$pMin} se Rs {$pMax} tak hain. Budget batayein — main best suit dhundh deti hoon! 💰";

    // ── How many products / brands ────────────────────────────
    if (mb_strpos($m,'how many')!==false || mb_strpos($m,'kitne')!==false || mb_strpos($m,'total')!==false)
        return $adv
            ? "We currently have {$totalP} products from {$totalBr} brands — all Pakistani clothing!"
            : "Abhi hamare paas {$totalP} products hain {$totalBr} brands se! Kaafi variety hai! 🛍️";

    // ── Order intent ──────────────────────────────────────────
    if (preg_match('/\b(order|buy|purchase|checkout|cart)\b/i', $m))
        return $adv
            ? "To order: tap the product card → click 'Add to Cart' or 'Buy Now' → complete checkout with your address."
            : "Order karne ke liye product card pe tap karein → 'Add to Cart' ya 'Buy Now' → address dal ke checkout complete karein! 🛒";

    // ── Brands with products ──────────────────────────────────
    if ($b && $c > 0)
        return $adv
            ? "Here are {$c} {$b} products — browse below! 👇"
            : "{$b} ke {$c} options mil gaye — neeche dekhen! 👇";
    if ($b) {
        $alts = !empty($store['brands']) ? implode(', ', array_slice($store['brands'], 0, 4)) : 'our available brands';
        return $adv
            ? "We carry {$b} but they're currently out of stock. You might like: {$alts}."
            : "{$b} abhi stock mein nahi. Yeh brands try karein: {$alts}!";
    }
    if ($c > 0)
        return $adv
            ? "Here are {$c} great options — browse below! 👇"
            : "{$c} options mil gaye — neeche check karein! Koi pasand aaye toh batayein! 👇";

    // ── Generic helpful response ──────────────────────────────
    return $adv
        ? "What are you looking for? Tell me your preferred brand, occasion, or budget — I'll find the perfect outfit."
        : "Batayein, kya dhundh rahe hain? Brand, occasion, ya budget batayein — main best suit dhundh dungi! 😊";
}

// ===========================================================
// LANGUAGE DETECTION
// ===========================================================
function isUrdu($langMode, $msg) {
    if ($langMode === 'urdu')    return true;
    if ($langMode === 'english') return false;
    return preg_match('/[اآبپتٹثجچحخدڈذرڑزژسشصضطظعغفقکگلمنوہیئے]/u', $msg)
        || preg_match('/\b(mujhe|chahiye|kya|hai|aap|kahan|ji|bhi|kar|ka|ki|ke|ko|aur|nahi|hain|hoon|tha|thi|mein|se|pe|par)\b/i', $msg);
}

// ===========================================================
// EXACT IMAGE MATCH — pHash + MD5 (same as visual_search.php)
// Returns formatted product array or null if no exact match
// ===========================================================
function pHashBits($imgPath) {
    $ext = strtolower(pathinfo($imgPath, PATHINFO_EXTENSION));
    $src = match($ext) {
        'jpg','jpeg' => @imagecreatefromjpeg($imgPath),
        'png'        => @imagecreatefrompng($imgPath),
        'webp'       => @imagecreatefromwebp($imgPath),
        'gif'        => @imagecreatefromgif($imgPath),
        default      => false,
    };
    if (!$src) return null;
    $small = imagecreatetruecolor(8, 8);
    imagecopyresampled($small, $src, 0,0,0,0, 8,8, imagesx($src), imagesy($src));
    imagedestroy($src);
    $pixels = []; $sum = 0;
    for ($y = 0; $y < 8; $y++) for ($x = 0; $x < 8; $x++) {
        $c    = imagecolorat($small, $x, $y);
        $gray = (($c >> 16 & 0xFF)*299 + ($c >> 8 & 0xFF)*587 + ($c & 0xFF)*114) / 1000;
        $pixels[] = $gray; $sum += $gray;
    }
    imagedestroy($small);
    $avg = $sum / 64; $bits = '';
    foreach ($pixels as $p) $bits .= ($p >= $avg ? '1' : '0');
    return $bits;
}

function hammingDist($a, $b) {
    $d = 0;
    for ($i = 0, $l = strlen($a); $i < $l; $i++) if ($a[$i] !== $b[$i]) $d++;
    return $d;
}

function exactImageMatch($db, $imageBytes, $base) {
    // ── Fast MD5-only check (no GD loading — avoids memory exhaustion on 800+ images) ──
    // pHash (full scan) is left to visual_search.php which is purpose-built for it.
    $uploadedMD5 = md5($imageBytes);
    $imgDir      = __DIR__ . '/admin/images/product/';

    $res = $db->query(
        "SELECT p.id, p.name, p.slug, p.image, p.price, p.price_after_discount,
                p.dis_amount, b.name AS brand_name
         FROM products p LEFT JOIN brand b ON p.brand = b.id
         WHERE p.stock != 'Out Stock' AND p.image != ''
         ORDER BY p.id DESC"
    );
    if (!$res) return null;

    while ($p = $res->fetch_assoc()) {
        $imgs = explode(',', $p['image']);
        foreach ($imgs as $img) {
            $img  = trim($img);
            $path = $imgDir . $img;
            if (!file_exists($path)) continue;
            if (md5_file($path) === $uploadedMD5) {
                return formatExactProduct($p, $imgs, $base, 'md5');
            }
        }
    }
    return null;
}

function formatExactProduct($p, $imgs, $base, $matchType) {
    $finalPrice = ($p['price_after_discount'] > 0) ? $p['price_after_discount'] : $p['price'];
    $discPct    = ($p['price'] > 0 && $finalPrice < $p['price'])
                  ? round((($p['price'] - $finalPrice) / $p['price']) * 100) : 0;
    return [
        'id'               => (int)$p['id'],
        'name'             => $p['name'],
        'slug'             => $p['slug'],
        'brand'            => $p['brand_name'] ?? 'Kapraa',
        'image'            => $imgs[0],
        'image_url'        => $base . '/admin/images/product/' . trim($imgs[0]),
        'product_url'      => $base . '/product/' . $p['slug'],
        'price'            => number_format((float)$p['price'], 0),
        'sale_price'       => number_format((float)$p['price_after_discount'], 0),
        'discount'         => ($discPct > 0 ? $discPct . '% OFF' : ''),
        'discount_pct'     => $discPct,
        'discount_percent' => $discPct,
        'dis_amount'       => (float)($p['dis_amount'] ?? 0),
        'brand_name'       => $p['brand_name'] ?? '',
        '_match'           => $matchType,
    ];
}

// ===========================================================
// IMAGE SEARCH
// ===========================================================
function handleImage($db, $b64, $type, $msg, $langMode, $base, $cloudinaryUrl = '', $userLevel = 'normal') {
    if ($userLevel === 'advanced') {
        $isUr    = false;
        $imgLang = 'IELTS Band 7-9 ENGLISH ONLY. Sophisticated fashion vocabulary. Zero Urdu. Eloquent, professional.';
    } elseif ($userLevel === 'beginner') {
        $isUr    = true;
        $imgLang = 'IELTS Band 2-3. Very simple English + Roman Urdu words. Short sentences. Max 8 words each.';
    } else {
        $isUr    = isUrdu($langMode, $msg);
        $imgLang = $isUr
            ? 'Roman Urdu (Urdu in English letters). Friendly, casual.'
            : 'IELTS Band 5 clear English. Friendly, short sentences. 1-2 Urdu words ok.';
    }

    // ── STEP 0: Try exact match (MD5 + pHash) BEFORE calling Gemini ──────────
    // This catches when user uploads the EXACT same product image from the website
    $imageBytes   = base64_decode($b64);
    $exactProduct = exactImageMatch($db, $imageBytes, $base);
    if ($exactProduct) {
        $pName  = $exactProduct['name'];
        $pPrice = number_format((float)(($exactProduct['price_after_discount'] > 0) ? $exactProduct['price_after_discount'] : $exactProduct['price']), 0);
        $pBrand = $exactProduct['brand_name'] ? " by {$exactProduct['brand_name']}" : '';
        $discTxt = $exactProduct['discount_percent'] > 0 ? " ({$exactProduct['discount_percent']}% off!)" : '';
        $reply = $isUr
            ? "Ye product hamare store mein hai! 🎉 **{$pName}**{$pBrand} — Rs {$pPrice}{$discTxt}. Neeche product card se order kar saktay hain!"
            : "Great news — we found this exact product! 🎉 **{$pName}**{$pBrand} — Rs {$pPrice}{$discTxt}. Tap the card below to order!";
        $ret = ['reply' => $reply, 'products' => [$exactProduct], 'context' => 'default', 'exact_match' => true];
        if ($cloudinaryUrl !== '') $ret['cloudinary_url'] = $cloudinaryUrl;
        return $ret;
    }

    // ── STEP 1: Gemini vision analysis (no exact match found) ──────────────
    // Include Cloudinary URL in the prompt context if available
    $promptExtra = $msg;
    if ($cloudinaryUrl !== '') {
        $promptExtra .= "\nImage URL: {$cloudinaryUrl}";
    }

    $analysis = analyzeImage($b64, $type, $promptExtra, $cloudinaryUrl);

    if (!$analysis) {
        $ret = [
            'reply'    => $isUr ? "Uff, image analyze nahi ho saki. Text mein batayein kya chahiye?" : "Hmm, couldn't analyze the image. Can you describe what you're looking for?",
            'products' => [],
            'context'  => 'default',
        ];
        if ($cloudinaryUrl !== '') $ret['cloudinary_url'] = $cloudinaryUrl;
        return $ret;
    }

    // Non-clothing images should get a polite, appreciative redirect.
    if (!empty($analysis['non_clothing']) && (int)$analysis['non_clothing'] === 1) {
        $reply = "Thanks for sharing the image — appreciate it! I am best at clothing and fashion help. Send a dress/suit photo or tell me your occasion, and I will guide you perfectly.";
        if ($isUr) {
            $reply = "Image share karne ka bohat shukriya — appreciate it! Main clothing aur fashion mein best help karti hoon. Koi suit/dress ki photo bhej dein ya occasion batayein, main perfect guide karungi.";
        }
        $ret = ['reply' => $reply, 'products' => [], 'context' => 'default'];
        if ($cloudinaryUrl !== '') $ret['cloudinary_url'] = $cloudinaryUrl;
        return $ret;
    }

    $imgBrand  = strtolower(trim($analysis['known_brand'] ?? ''));
    $fabric    = strtolower(trim($analysis['fabric']      ?? ''));
    $style     = strtolower(trim($analysis['style']       ?? ''));
    $descEn    = $analysis['description_en'] ?? 'Pakistani outfit';
    $descUr    = $analysis['description_ur'] ?? 'Pakistani outfit';
    $gender    = strtolower(trim($analysis['gender']      ?? 'women'));
    // Safety net: only accept known genders
    if (!in_array($gender, ['men', 'kids', 'women'])) $gender = 'women';

    // Human-friendly gender labels for replies
    $genderLabel   = match($gender) { 'men' => "men's", 'kids' => "kids'", default => "women's" };
    $genderLabelUr = match($gender) { 'men' => 'mardana', 'kids' => 'bacchon ke', default => "ladies'" };

    // Context chip for JS carousel
    $context = match($gender) { 'men' => 'men', 'kids' => 'kids', default => 'women' };

    // ── Check if detected brand is one we carry ───────────────────────────
    $ourBrand = null;
    if ($imgBrand) {
        $safe = $db->real_escape_string($imgBrand);
        $br = $db->query("SELECT id, name, slug FROM brand WHERE LOWER(TRIM(name)) LIKE '%{$safe}%' LIMIT 1");
        if ($br && $br->num_rows > 0) $ourBrand = $br->fetch_assoc();
    }

    // ── External brand check ──────────────────────────────────────────────
    if ($imgBrand && detectExternalBrand($imgBrand) && !$ourBrand) {
        // Brand detected but we don't carry it — show gender-matched similar products
        $products = imageSearch($db, $analysis['keywords'], $base, '', $fabric, $style, $gender);
        $c = count($products);
        $brandCap = ucwords($imgBrand);
        $reply = "This looks like a {$brandCap} {$genderLabel} outfit — we don't carry {$brandCap}, but here are similar {$genderLabel} styles we have! 👇";
        if ($isUr) $reply = "Ye {$brandCap} ka {$genderLabelUr} suit lag raha hai — hamare paas {$brandCap} nahi hai, lekin similar styles neeche hain!";
        $ret = ['reply' => $reply, 'products' => $products, 'context' => $context];
        if ($cloudinaryUrl !== '') $ret['cloudinary_url'] = $cloudinaryUrl;
        return $ret;
    }

    // ── Search products (gender-filtered, with brand hint if we carry it) ─
    $products = imageSearch($db, $analysis['keywords'], $base, $imgBrand, $fabric, $style, $gender);
    $c = count($products);

    // ── Build smart reply based on what we found ──────────────────────────
    $isLocal2 = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
             || strpos($_SERVER['HTTP_HOST'] ?? '', 'ngrok') !== false;
    $baseUrl2 = $isLocal2 ? 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/kapraa' : 'https://kapraa.pk';

    if ($ourBrand && $c > 0) {
        // We carry this brand AND found products
        $brandName = $ourBrand['name'];
        $brandUrl  = $baseUrl2 . '/brand/' . $ourBrand['slug'];
        $reply = "Yes! We carry {$brandName}! Here are similar {$genderLabel} styles we have in stock 👇 You can also browse the full {$brandName} collection: {$brandUrl}";
        if ($isUr) $reply = "Bilkul! Hamare paas {$brandName} hai! Ye similar {$genderLabelUr} suits hain neeche 👇 Poora {$brandName} collection: {$brandUrl}";
    } elseif ($ourBrand && $c === 0) {
        // We carry this brand but no products found in gender category
        $brandName = $ourBrand['name'];
        $brandUrl  = $baseUrl2 . '/brand/' . $ourBrand['slug'];
        $reply = "We carry {$brandName}! Couldn't find this exact piece, but here are {$genderLabel} options we have 👇 Full collection: {$brandUrl}";
        if ($isUr) $reply = "Hamare paas {$brandName} hai! Ye exact piece nahi mila, lekin yahan {$genderLabelUr} options hain 👇 Poora collection: {$brandUrl}";
    } elseif ($c > 0) {
        // Found keyword-matching products — use descEn always (no Urdu script)
        $reply = "I can see this is {$descEn} — we have {$c} similar {$genderLabel} styles in our store! Check them out below 👇";
        if ($isUr) $reply = "Ye {$descEn} lag raha hai — hamare paas {$c} similar {$genderLabelUr} options hain! Neeche dekhen 👇";
    } else {
        // Nothing found — give honest, helpful response (no random unrelated products)
        $genderCatLabel = match($gender) { 'men' => "men's", 'kids' => "kids'", default => "women's" };
        $reply = "I can see this is {$descEn}. We couldn't find an exact match in our {$genderCatLabel} collection right now. Could you describe what you're looking for — occasion, fabric, or budget? I'll find you the closest match! 😊";
        if ($isUr) $reply = "Ye {$descEn} hai. Hamare {$genderLabelUr} collection mein abhi ye exact style nahi mila. Occasion, fabric ya budget batao — main aapke liye best option dhundh deti hoon! 😊";
    }

    $ret = ['reply' => $reply, 'products' => $products, 'context' => $context];
    if ($cloudinaryUrl !== '') $ret['cloudinary_url'] = $cloudinaryUrl;
    return $ret;
}

function analyzeImage($b64, $type, $extra, $cloudinaryUrl = '') {
    if (!GEMINI_KEY) return null;
    $p = 'You are a Pakistani clothing store expert. Analyze ONLY the clothing/garment in this image — IGNORE the face, head, accessories, or background entirely. Return ONLY valid JSON with no extra text:
{"description_en":"detailed English description of the CLOTHING ONLY e.g. light blue mens turtleneck sweater with dark jeans","description_ur":"ROMAN URDU description (Urdu words written in English letters ONLY — NO Arabic script at all) e.g. halki neeli rang ki turtle neck sweater ke sath dark jeans","keywords":["sweater","turtleneck","grey","men","casual","winter"],"known_brand":"","colors":["grey","blue"],"fabric":"wool","style":"casual","gender":"men","non_clothing":0}.
Rules:
- Focus ONLY on the clothing garment — ignore any cartoon heads, masks, mannequins, or faces.
- keywords: 6-8 specific clothing-related words that would appear in a product name (e.g. "sweater", "kurta", "lawn", "embroidered", "turtleneck"). Include fabric, color, style, cut. Think about what a Pakistani store would name this product.
- description_ur: MUST be Roman Urdu ONLY (Urdu words written in English letters). ABSOLUTELY NO Arabic/Urdu script characters. Example: "grey rango ki turtle neck sweater, dark jeans ke sath, mardana wear"
- If the image is NOT clothing/apparel (e.g. face only, random object, scenery, food), set non_clothing=1 and keep keywords minimal.
- gender: CRITICAL — identify who the clothing is FOR based on the garment style/cut:
  * "men"  — mens kurta, shalwar kameez for men, sherwani, gents shirt, mens suit, mens sweater, mens t-shirt, boys kurta, male Western wear (jeans+shirt, jeans+sweater, polo shirt, hoodie for men)
  * "kids" — childrens clothing, baby clothes, toddler wear
  * "women" — ladies suits, womens shalwar kameez, womens dresses (DEFAULT if unclear)
  Look at the CUT, STYLE, and CONTEXT: mens kurtas have band collar, straight cut, no heavy embellishment on dupatta. Womens suits have dupatta, heavy embroidery, feminine cuts.
- known_brand: if image shows a specific brand name/logo (like LALA, Maria.B, Sapphire, Gull Ahmed, Khaadi, Sana Safinaz, Alkaram, Nishat, etc.) write it in lowercase, otherwise empty string.
- keywords: include fabric (lawn/linen/khaddar/chiffon/cotton/silk/georgette), style (embroidered/printed/digital/block print), piece count (2pc/3pc), stitching (stitched/unstitched), cut (straight/flared/anarkali), and main colors. For mens: include "kurta", "mens", "gents". For kids: include "kids", "children".
- colors: list all visible colors.
- fabric: main fabric type only.
- style: main style descriptor only (e.g. "embroidered", "digital print", "block print").
Extract ALL visible details — this is used to find matching products in a store.';
    if ($cloudinaryUrl !== '') {
        $p .= "\nImage URL: {$cloudinaryUrl}";
    }
    $payload = json_encode(['contents' => [['parts' => [['inline_data' => ['mime_type' => $type, 'data' => $b64]], ['text' => $p . ($extra ? "\nUser note: $extra" : '')]]]], 'generationConfig' => ['maxOutputTokens' => 350, 'temperature' => 0.1]]);
    $text = geminiCall($payload);
    if (!$text) return null;
    $text = trim(preg_replace(['/^```json\s*/i', '/\s*```$/'], '', $text));
    $parsed = json_decode($text, true);
    return $parsed ?: ['description_en' => 'Pakistani suit', 'description_ur' => 'Pakistani suit', 'keywords' => ['suit', 'lawn', 'unstitched'], 'known_brand' => '', 'colors' => [], 'fabric' => '', 'style' => '', 'gender' => 'women', 'non_clothing' => 0];
}

function imageSearch($db, $keywords, $base, $knownBrand = '', $fabric = '', $style = '', $gender = 'women') {
    if (empty($keywords) && !$knownBrand && !$fabric) return [];

    $stockCond = "LOWER(COALESCE(p.stock,'')) NOT IN ('out of stock','0','out stock')";

    // ── Gender → category filter (category 3 = men, category 4 = kids) ──────
    // Women's products have no special category restriction (they're the default)
    $genderFilter = '';
    if ($gender === 'men')  $genderFilter = 'AND p.category = 3';
    if ($gender === 'kids') $genderFilter = 'AND p.category = 5';

    // ── Step 1: Try to find exact brand in our DB ─────────────────────────────
    $brandRow = null;
    if ($knownBrand) {
        $safe = $db->real_escape_string(strtolower($knownBrand));
        $br = $db->query("SELECT id, name, slug FROM brand WHERE LOWER(TRIM(name)) LIKE '%{$safe}%' LIMIT 1");
        if ($br && $br->num_rows > 0) $brandRow = $br->fetch_assoc();
    }

    // ── Step 2: Keyword search (OR match on name/description) ────────────────
    $parts = []; $params = []; $types = '';
    $allKw = array_unique(array_filter(array_merge(
        $keywords,
        $fabric  ? [$fabric]  : [],
        $style   ? [$style]   : []
    )));
    foreach ($allKw as $kw) {
        $kw = trim($kw); if (strlen($kw) < 3) continue;
        $parts[]  = "(p.name LIKE ? OR COALESCE(p.description,'') LIKE ?)";
        $params[] = "%{$kw}%"; $params[] = "%{$kw}%"; $types .= 'ss';
    }

    $rows = [];

    // Search with keywords + optional brand filter + gender filter
    if (!empty($parts)) {
        $brandFilter = $brandRow ? "AND FIND_IN_SET({$brandRow['id']}, p.brand)" : '';
        $sql = "SELECT p.id, p.name, p.slug, p.price, p.price_after_discount, p.image, p.sale, p.occasion_tag,
                       b.name brand_name, ROUND(((p.price-p.price_after_discount)/NULLIF(p.price,0))*100) discount_pct
                FROM products p LEFT JOIN brand b ON FIND_IN_SET(b.id,p.brand)
                WHERE {$stockCond} {$genderFilter} {$brandFilter} AND (" . implode(' OR ', $parts) . ")
                ORDER BY COALESCE(p.ai_score,50) DESC, p.id DESC LIMIT 6";
        $st = $db->prepare($sql);
        if ($st) {
            $st->bind_param($types, ...$params);
            $st->execute();
            $res = $st->get_result();
            while ($row = $res->fetch_assoc()) $rows[] = formatProduct($row, 'image', $base);
        }
    }

    // ── Step 3: If still empty but brand found → show that brand's products (gender-filtered) ──
    if (empty($rows) && $brandRow) {
        $bid = (int)$brandRow['id'];
        $fb = $db->query("SELECT p.id, p.name, p.slug, p.price, p.price_after_discount, p.image, p.sale, p.occasion_tag,
                                 b.name brand_name, ROUND(((p.price-p.price_after_discount)/NULLIF(p.price,0))*100) discount_pct
                          FROM products p LEFT JOIN brand b ON FIND_IN_SET(b.id,p.brand)
                          WHERE {$stockCond} {$genderFilter} AND FIND_IN_SET({$bid}, p.brand)
                          ORDER BY COALESCE(p.ai_score,50) DESC, p.id DESC LIMIT 6");
        if ($fb) while ($row = $fb->fetch_assoc()) $rows[] = formatProduct($row, 'image', $base);
    }

    // Step 4: REMOVED — never show random products as fallback when image keywords don't match.
    // If keyword search and brand search both fail, return empty array.
    // handleImage() will show a helpful "we don't have this exact style" message.
    // This prevents showing completely unrelated products that confuse the user.

    return $rows;
}