<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ── Config ────────────────────────────────────────────────────
$GEMINI_KEY   = 'AIzaSyADBOJ_gAqqvw_9jLoSea7Ssfrd6IWUN4g';
$GEMINI_MODEL = 'gemini-2.5-flash-lite';     // free-tier quota available
$GEMINI_URL   = "https://generativelanguage.googleapis.com/v1beta/models/{$GEMINI_MODEL}:generateContent?key={$GEMINI_KEY}";

$host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = (strpos($host,'localhost')!==false||strpos($host,'127.0.0.1')!==false||strpos($host,'192.168.')!==false);
$base    = $isLocal ? 'http://'.$host.'/kapraa' : 'https://'.$host;

if ($isLocal) {
    $conn = mysqli_connect('localhost', 'root', '', 'kapraa');
} else {
    $conn = mysqli_connect('localhost', 'u167258434_kapraa', 'Kapraa@2026', 'u167258434_kapraa');
}
if (!$conn) { echo json_encode(['reply'=>'Technical issue, please try again.','products'=>[]]); exit; }
mysqli_set_charset($conn, 'utf8mb4');

// ── Input ─────────────────────────────────────────────────────
$in         = json_decode(file_get_contents('php://input'), true) ?? [];
$message    = trim((string)($in['message'] ?? ''));
$history    = (array)($in['history'] ?? []);
$orderStep  = trim((string)($in['order_step']  ?? ''));   // stateless order flow step
$orderData  = (array)($in['order_data']  ?? []);          // accumulated order data
$imageB64   = trim((string)($in['image'] ?? ''));         // base64 image for product identification

if ($message === '' && $imageB64 === '') {
    echo json_encode(['reply'=>"Salam! 👋 Main Laiba hoon — Kapraa ki fashion expert. Aaj kya dhundh rahe hain?",'products'=>[]]);
    exit;
}
// If image sent but no message, treat as empty message
if ($message === '' && $imageB64 !== '') {
    $message = '[image]';
}

// ══════════════════════════════════════════════════════════════
// COD ORDER FLOW — stateless (step + data passed in each request)
// ══════════════════════════════════════════════════════════════

// Find product from URL slug — ONLY in the current message (never history to avoid false picks)
function findProductBySlugOrId($conn, $message, $history) {
    // Match only if the user explicitly pasted a /product/slug URL in THIS message
    if (preg_match('/(?:\/product\/|[?&]slug=)([a-z0-9][a-z0-9\-]{2,80})/i', $message, $m)) {
        $slug = mysqli_real_escape_string($conn, trim($m[1]));
        $r = mysqli_query($conn, "SELECT id, name, price, price_after_discount, image, slug FROM products WHERE slug='$slug' LIMIT 1");
        if ($r && $row = mysqli_fetch_assoc($r)) return buildOrderProduct($row);
    }
    return null;
}

// Find product from text keywords
// $minScore: 1 = any match (loose), 3 = high-confidence (needs 3+ keyword hits)
function findProductByKeywords($conn, $text, $minScore = 1) {
    $text = mb_strtolower(trim($text));
    $skip = [
        // Urdu/chat filler
        'ka','ki','ke','hai','hain','mujhe','please','order','buy','want','need','lena',
        'chahiye','wala','wali','yeh','karna','karo','karna','hy','he','hai','hein',
        'this','that','the','a','an','for','see','and','with','from','some',
        // Generic clothing words (match almost every product → inflate score unfairly)
        'suit','suits','dress','dresses','kameez','shalwar','kurta','lawn','cotton',
        'khaddar','khaddi','chiffon','linen','print','prints','piece','unstitched',
        'stitched','embroidered','embroidery','collection','design','designs',
        'volume','vol','pack','shirt','dupatta','fabric','cloth','clothing','wear',
        // Generic order/command words (not product identifiers)
        'product','item','stuff','thing','need','want','lena','chahiye',
        'place','order','karna','karo','chahta','chahti',
    ];
    $words = array_filter(preg_split('/[\s\-_,\.\/]+/', $text), fn($w) => mb_strlen($w) >= 4 && !in_array($w, $skip));
    if (empty($words)) return null;
    $conds = [];
    foreach (array_slice(array_values($words), 0, 8) as $w) {
        $safe = mysqli_real_escape_string($conn, $w);
        $conds[] = "LOWER(name) LIKE '%{$safe}%'";
    }
    $where = implode(' OR ', $conds);
    $score = implode(' + ', array_map(fn($c) => "IF($c,1,0)", $conds));
    $r = mysqli_query($conn, "SELECT id, name, price, price_after_discount, image, slug, ($score) AS sc FROM products WHERE ($where) AND stock!='Out Stock' ORDER BY sc DESC, LENGTH(name) ASC LIMIT 1");
    if ($r && ($row = mysqli_fetch_assoc($r)) && (int)($row['sc'] ?? 0) >= $minScore) return buildOrderProduct($row);
    return null;
}

// Find product by analyzing an uploaded image via Gemini Vision
function findProductByImage($conn, $imageB64, $mimeType, $GEMINI_URL) {
    if (!$imageB64) return null;
    $prompt = "This is a Pakistani clothing product image. Extract only: product name keywords (fabric type, design, piece count, any visible brand/label text). Return ONLY a short comma-separated keyword list like: lawn, 3 piece, embroidered, khaddi. Max 6 keywords. No explanation.";
    $payload = json_encode([
        'contents' => [['parts' => [
            ['text' => $prompt],
            ['inline_data' => ['mime_type' => $mimeType ?: 'image/jpeg', 'data' => $imageB64]]
        ]]],
        'generationConfig' => ['temperature' => 0.1, 'maxOutputTokens' => 60]
    ]);
    $ch = curl_init($GEMINI_URL);
    curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
        CURLOPT_POSTFIELDS=>$payload, CURLOPT_TIMEOUT=>12, CURLOPT_SSL_VERIFYPEER=>false]);
    $res = curl_exec($ch); curl_close($ch);
    $d = json_decode($res, true);
    $keywords = trim((string)($d['candidates'][0]['content']['parts'][0]['text'] ?? ''));
    if (!$keywords) return null;
    return findProductByKeywords($conn, $keywords);
}

function buildOrderProduct($row) {
    $img   = trim(explode(',', (string)($row['image'] ?? ''))[0]);
    $price = (float)($row['price_after_discount'] > 0 ? $row['price_after_discount'] : $row['price']);
    return ['id' => (int)$row['id'], 'name' => (string)$row['name'], 'price' => $price, 'image' => $img, 'slug' => (string)$row['slug']];
}

// Place the actual COD order in DB
function placeOrderInDB($conn, $data, $prod) {
    $orderNum   = 'ORD-' . date('Y') . '-' . strtoupper(uniqid());
    $name       = mysqli_real_escape_string($conn, $data['name']    ?? '');
    $phone      = mysqli_real_escape_string($conn, $data['phone']   ?? '');
    $email      = mysqli_real_escape_string($conn, $data['email']   ?? '');
    $addr       = mysqli_real_escape_string($conn, $data['address'] ?? '');
    $city       = mysqli_real_escape_string($conn, $data['city']    ?? '');
    $size       = mysqli_real_escape_string($conn, $data['size']    ?? '');
    $price      = (float)($prod['price'] ?? 0);
    // Rs 250 delivery — free on orders above Rs 5000 (matches website policy)
    $shipping   = ($price >= 5000) ? 0.0 : 250.0;
    $total      = $price + $shipping;
    $notes      = mysqli_real_escape_string($conn, 'AI Chat COD — ' . ($prod['name'] ?? ''));
    $country    = 'Pakistan';

    $sql = "INSERT INTO orders (order_number,customer_name,email,phone,country,state,city,postal_code,address,notes,payment_method,payment_status,order_status,subtotal,shipping_cost,tax,total_amount,created_at)
            VALUES ('$orderNum','$name','$email','$phone','$country','','$city','','$addr','$notes','cod','pending','pending',$price,$shipping,0,$total,NOW())";
    if (!mysqli_query($conn, $sql)) return null;
    $orderId = mysqli_insert_id($conn);

    $prodId   = (int)($prod['id'] ?? 0);
    $prodName = mysqli_real_escape_string($conn, $prod['name'] ?? '');
    $prodImg  = mysqli_real_escape_string($conn, $prod['image'] ?? '');
    $sql2 = "INSERT INTO order_items (order_id,product_id,product_name,product_image,size,colors,quantity,price,stitching_price,shipping_cost,total_price)
             VALUES ($orderId,$prodId,'$prodName','$prodImg','$size','',1,$price,0,$shipping,$total)";
    if (!mysqli_query($conn, $sql2)) return null;

    return ['order_id' => $orderId, 'order_number' => $orderNum, 'total' => $total];
}

// Helper: check if message is a positive confirmation
function isYes($msg) {
    $m = mb_strtolower(trim($msg));
    return preg_match('/\b(yes|confirm|haan|ha|ji|ok|okay|theek|bilkul|sahi|correct|right|done|proceed|ha ji|ji haan|haan ji|yep|yup|sure|zaroor)\b/u', $m);
}
// Helper: check if message is a negative / correction
function isNo($msg) {
    $m = mb_strtolower(trim($msg));
    return preg_match('/\b(no|nahi|nai|nahin|nope|galat|wrong|ghalat|different|change|badlo|aur|doosra|other|na)\b/u', $m);
}

// Main order flow handler — stateless
function handleOrderFlowStateless($conn, $message, $history, $step, $data, $base, $imageB64='', $mimeType='', $GEMINI_URL='') {

    // ── Cancel at any step (except confirm_product — there "no" just goes back) ──
    if ($step && $step !== 'confirm_product' && preg_match('/\b(cancel|band karo|nai chahiye|choro|choddo|order nahi)\b/i', $message)) {
        return ['reply' => "Order cancel kar diya! Koi aur cheez chahiye? 😊", 'products' => [], 'order_step' => '', 'order_data' => []];
    }

    // ── No active step: detect order intent ───────────────────
    if (!$step) {
        if (!detectOrderIntent($message)) return null;

        // 1) Exact URL slug in current message → 100% confident, go straight to payment
        $prod = findProductBySlugOrId($conn, $message, $history);
        if ($prod) {
            $price = 'Rs ' . number_format($prod['price'], 0);
            return [
                'reply'      => "Product mil gaya! ✅\n\n🛍️ *{$prod['name']}*\n💰 {$price}\n\n*Cash on Delivery (COD)* chahiye ya *Online Payment* (JazzCash/EasyPaisa)?",
                'products'   => [], 'order_step' => 'ask_payment',
                'order_data' => ['product_id'=>$prod['id'],'product_name'=>$prod['name'],'product_price'=>$prod['price'],'product_image'=>$prod['image'],'product_slug'=>$prod['slug']],
            ];
        }

        // 2) Specific product name (score ≥ 2) — show for confirmation before proceeding
        //    confirm_product step acts as safety net so false matches are caught by user
        $prod = findProductByKeywords($conn, $message, 2);

        // 3) Image uploaded alongside order intent
        if (!$prod && $imageB64 && $GEMINI_URL) {
            $prod = findProductByImage($conn, $imageB64, $mimeType, $GEMINI_URL);
        }

        if ($prod) {
            $price = 'Rs ' . number_format($prod['price'], 0);
            return [
                'reply'      => "Yeh product mila hai — kya yeh sahi hai? 🤔\n\n🛍️ *{$prod['name']}*\n💰 *{$price}*\n\n✅ Sahi hai? — **haan** likhein\n❌ Galat? — *poora naam ya link* bhejein",
                'products'   => [], 'order_step' => 'confirm_product',
                'order_data' => ['product_id'=>$prod['id'],'product_name'=>$prod['name'],'product_price'=>$prod['price'],'product_image'=>$prod['image'],'product_slug'=>$prod['slug']],
            ];
        }

        // 4) Vague — ask to specify
        return [
            'reply'      => "Bilkul! Order karte hain 😊\n\nKaunsa product chahiye? 3 tareeqe hain:\n\n🔗 *Product ka link paste karein*\n✍️ *Product ka poora naam likhein*\n🖼️ *Product ki image upload karein*\n\nMisaal: \"Rahat by Janan Lawn 3pc D-04\"",
            'products'   => [], 'order_step' => 'ask_product', 'order_data' => [],
        ];
    }

    // ── confirm_product: user confirms or rejects the found product ──
    if ($step === 'confirm_product') {
        if (isYes($message)) {
            $price = 'Rs ' . number_format((float)($data['product_price'] ?? 0), 0);
            return [
                'reply'      => "✅ Theek hai!\n\n🛍️ *{$data['product_name']}*\n💰 {$price}\n\n*Cash on Delivery (COD)* chahiye ya *Online Payment* (JazzCash/EasyPaisa)?",
                'products'   => [], 'order_step' => 'ask_payment', 'order_data' => $data,
            ];
        }
        // User said no / sent different product
        $newProd = findProductBySlugOrId($conn, $message, $history);
        if (!$newProd && $imageB64 && $GEMINI_URL) $newProd = findProductByImage($conn, $imageB64, $mimeType, $GEMINI_URL);
        if (!$newProd && $message !== '[image]' && !isNo($message)) $newProd = findProductByKeywords($conn, $message, 2);
        if ($newProd) {
            $price = 'Rs ' . number_format($newProd['price'], 0);
            return [
                'reply'      => "Yeh wala? 🤔\n\n🛍️ *{$newProd['name']}*\n💰 *{$price}*\n\n**haan** ya **galat** batayein:",
                'products'   => [], 'order_step' => 'confirm_product',
                'order_data' => ['product_id'=>$newProd['id'],'product_name'=>$newProd['name'],'product_price'=>$newProd['price'],'product_image'=>$newProd['image'],'product_slug'=>$newProd['slug']],
            ];
        }
        return [
            'reply'      => "Koi baat nahi! Sahi product batayein:\n\n🔗 Product page ka *link*\n✍️ Product ka *poora naam*\n🖼️ Product ki *image*",
            'products'   => [], 'order_step' => 'ask_product', 'order_data' => [],
        ];
    }

    // ── ask_product: user gives product name/URL/image ───────────
    if ($step === 'ask_product') {
        $prod = findProductBySlugOrId($conn, $message, $history);
        if (!$prod && $imageB64 && $GEMINI_URL) $prod = findProductByImage($conn, $imageB64, $mimeType, $GEMINI_URL);
        if (!$prod && $message !== '[image]') $prod = findProductByKeywords($conn, $message, 2);
        if ($prod) {
            $price = 'Rs ' . number_format($prod['price'], 0);
            return [
                'reply'      => "Yeh product mila — kya yeh sahi hai? 🤔\n\n🛍️ *{$prod['name']}*\n💰 *{$price}*\n\n**haan** = sahi | **galat** = doosra batayein",
                'products'   => [], 'order_step' => 'confirm_product',
                'order_data' => ['product_id'=>$prod['id'],'product_name'=>$prod['name'],'product_price'=>$prod['price'],'product_image'=>$prod['image'],'product_slug'=>$prod['slug']],
            ];
        }
        $hint = $imageB64 ? "Image se identify nahi hua. " : "";
        return [
            'reply'      => "{$hint}Product nahi mila 😕\n\n🔗 Product ka *link paste* karein\n✍️ Product ka *poora naam* likhein\n🖼️ *Clear product image* upload karein",
            'products'   => [], 'order_step' => 'ask_product', 'order_data' => $data,
        ];
    }

    // ── ask_payment ───────────────────────────────────────────
    if ($step === 'ask_payment') {
        if (preg_match('/\b(online|jazzcash|easypaisa|card|stripe|bank|transfer)\b/i', $message)) {
            return [
                'reply'    => "Online payment ke liye:\n1️⃣ Product page pe jayein\n2️⃣ *Buy Now* tap karein\n3️⃣ JazzCash / EasyPaisa / Card se pay karein 💳\n\nYa WhatsApp: +92 320 9815213",
                'products' => [], 'order_step' => '', 'order_data' => [],
            ];
        }
        if (preg_match('/\b(cod|cash|naqd|delivery|ghar|baad|milne)\b/i', $message)
            || preg_match('/cash on delivery|on delivery|ghar pe|pay on|COD/i', $message)
            || isYes($message)) {
            return [
                'reply'      => "Cash on Delivery — bilkul! 😊\n\nAapka *pura naam* batayein (as on ID):",
                'products'   => [], 'order_step' => 'ask_name', 'order_data' => $data,
            ];
        }
        return [
            'reply'      => "Please batayein:\n💵 *COD* — ghar pe deliver hone ke baad pay karein\n💳 *Online* — JazzCash / EasyPaisa / Card",
            'products'   => [], 'order_step' => 'ask_payment', 'order_data' => $data,
        ];
    }

    // ── ask_name ──────────────────────────────────────────────
    if ($step === 'ask_name') {
        $name = trim($message);
        if (mb_strlen($name) < 3 || preg_match('/^\d+$/', $name)) {
            return ['reply' => "Apna *pura naam* batayein (jaise: Ali Khan):", 'products' => [], 'order_step' => 'ask_name', 'order_data' => $data];
        }
        $data['name'] = $name;
        return [
            'reply'      => "Shukriya *{$data['name']}*! 😊\n\nAb *email address* batayein:\n_(Order confirmation email bhejne ke liye — skip likhein agar nahi hai)_",
            'products'   => [], 'order_step' => 'ask_email', 'order_data' => $data,
        ];
    }

    // ── ask_email ─────────────────────────────────────────────
    if ($step === 'ask_email') {
        $em = mb_strtolower(trim($message));
        if ($em === 'skip' || $em === 'no' || $em === 'nahi' || $em === 'na') {
            $data['email'] = '';
        } elseif (filter_var($message, FILTER_VALIDATE_EMAIL)) {
            $data['email'] = trim($message);
        } else {
            return ['reply' => "Valid email chahiye (jaise: ali@gmail.com)\nYa *skip* likhein:", 'products' => [], 'order_step' => 'ask_email', 'order_data' => $data];
        }
        return [
            'reply'      => "Got it! 📧\n\nAb *mobile number* batayein:\n_(e.g. 03001234567)_",
            'products'   => [], 'order_step' => 'ask_phone', 'order_data' => $data,
        ];
    }

    // ── ask_phone ─────────────────────────────────────────────
    if ($step === 'ask_phone') {
        $phone = preg_replace('/[\s\-\(\)\.]+/', '', trim($message));
        if (!preg_match('/^(\+92|0092|0)[0-9]{9,11}$/', $phone)) {
            return ['reply' => "Valid Pakistani number chahiye — jaise *03001234567*\nDobara likhein:", 'products' => [], 'order_step' => 'ask_phone', 'order_data' => $data];
        }
        $data['phone'] = $phone;
        return [
            'reply'      => "Got it! 📱\n\nAb poora *delivery address* batayein:\n_(ghar number, gali, mohalla / area)_",
            'products'   => [], 'order_step' => 'ask_address', 'order_data' => $data,
        ];
    }

    // ── ask_address ───────────────────────────────────────────
    if ($step === 'ask_address') {
        $addr = trim($message);
        if (mb_strlen($addr) < 8) {
            return ['reply' => "Thoda detail mein address batayein:\n_(ghar number, gali, mohalla, area)_", 'products' => [], 'order_step' => 'ask_address', 'order_data' => $data];
        }
        $data['address'] = $addr;
        return [
            'reply'      => "Address note ho gaya! 📦\n\nAb *shehar (city)* batayein:",
            'products'   => [], 'order_step' => 'ask_city', 'order_data' => $data,
        ];
    }

    // ── ask_city ──────────────────────────────────────────────
    if ($step === 'ask_city') {
        $city = trim($message);
        if (mb_strlen($city) < 2 || preg_match('/^\d+$/', $city)) {
            return ['reply' => "Shehar ka naam batayein (jaise Karachi, Lahore, Islamabad):", 'products' => [], 'order_step' => 'ask_city', 'order_data' => $data];
        }
        $data['city'] = $city;
        $prodPrice  = (float)($data['product_price'] ?? 0);
        $shipping   = ($prodPrice >= 5000) ? 0.0 : 250.0;
        $total      = $prodPrice + $shipping;
        $priceStr   = 'Rs ' . number_format($prodPrice, 0);
        $shipStr    = $shipping == 0 ? 'FREE 🎉 (above Rs 5000)' : 'Rs 250';
        $totalStr   = 'Rs ' . number_format($total, 0);
        $emailLine  = !empty($data['email']) ? "\n📧 *Email:* {$data['email']}" : '';
        $summary    = "📋 *Order Summary — please confirm:*\n\n"
                    . "🛍️ *Product:* {$data['product_name']}\n"
                    . "💰 *Price:* {$priceStr}\n"
                    . "🚚 *Delivery:* {$shipStr}\n"
                    . "💳 *Total:* {$totalStr}\n\n"
                    . "👤 *Name:* {$data['name']}{$emailLine}\n"
                    . "📱 *Phone:* {$data['phone']}\n"
                    . "🏠 *Address:* {$data['address']}, {$data['city']}\n"
                    . "💵 *Payment:* Cash on Delivery\n\n"
                    . "Sab sahi? Type **confirm** ya **cancel**";
        return ['reply' => $summary, 'products' => [], 'order_step' => 'confirm', 'order_data' => $data];
    }

    // ── confirm: final check then place order ─────────────────
    if ($step === 'confirm') {
        if (isNo($message) || preg_match('/\bcancel\b/i', $message)) {
            return ['reply' => "Order cancel kar diya. Koi aur madad? 😊", 'products' => [], 'order_step' => '', 'order_data' => []];
        }
        if (!isYes($message) && !preg_match('/\bconfirm\b/i', $message)) {
            $prod  = ['name'=>$data['product_name']??'','price'=>(float)($data['product_price']??0)];
            $total = 'Rs ' . number_format($prod['price'] + 200, 0);
            $size  = !empty($data['size']) ? " ({$data['size']})" : '';
            return [
                'reply'      => "📋 {$prod['name']}{$size}\n👤 {$data['name']} | 📱 {$data['phone']}\n🏠 {$data['address']}, {$data['city']}\n💳 *Total: {$total}* (COD)\n\n**confirm** likhein ya **cancel**:",
                'products'   => [], 'order_step' => 'confirm', 'order_data' => $data,
            ];
        }

        // Place order
        $prod   = ['id'=>(int)($data['product_id']??0),'name'=>$data['product_name']??'','price'=>(float)($data['product_price']??0),'image'=>$data['product_image']??'','slug'=>$data['product_slug']??''];
        $result = placeOrderInDB($conn, $data, $prod);
        if ($result) {
            $shipping  = ($prod['price'] >= 5000) ? 0.0 : 250.0;
            $shipStr   = $shipping == 0 ? 'FREE' : 'Rs 250';
            $totalStr  = 'Rs ' . number_format($result['total'], 0);
            $emailLine = !empty($data['email']) ? "\n📧 *Email:* {$data['email']}" : '';
            $reply = "🎉 *Thank you for your order!*\n\n"
                   . "━━━━━━━━━━━━━━━━━━━\n"
                   . "📦 *Order Number:* {$result['order_number']}\n"
                   . "━━━━━━━━━━━━━━━━━━━\n\n"
                   . "*Order Details:*\n"
                   . "🛍️ *Product:* {$prod['name']}\n"
                   . "💰 *Price:* Rs " . number_format($prod['price'], 0) . "\n"
                   . "🚚 *Delivery:* {$shipStr}\n"
                   . "💳 *Total Amount:* {$totalStr}\n"
                   . "💵 *Payment:* Cash on Delivery\n"
                   . "📋 *Status:* Pending\n\n"
                   . "*Customer Details:*\n"
                   . "👤 *Name:* {$data['name']}{$emailLine}\n"
                   . "📱 *Phone:* {$data['phone']}\n"
                   . "🏠 *Address:* {$data['address']}, {$data['city']}, Pakistan\n\n"
                   . "✅ Hamara team aapko jald contact karega.\n"
                   . "🚚 *Delivery: 3–5 working days*\n\n"
                   . "Shukriya Kapraa choose karne ka! 😊";
            return [
                'reply'        => $reply,
                'products'     => [], 'order_step' => '', 'order_data' => [],
                'order_placed' => true, 'order_number' => $result['order_number'],
            ];
        }
        return [
            'reply'    => "⚠️ Technical masla — order save nahi ho saka.\nWhatsApp pe order karein: *+92 320 9815213* 📞",
            'products' => [], 'order_step' => '', 'order_data' => [],
        ];
    }

    return null;
}

// ── Normalize text helper ─────────────────────────────────────
function normalizeName($s) {
    $s = mb_strtolower($s);
    $s = preg_replace('/[.\-_]+/', ' ', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    return trim($s);
}

// ── Detect order intent (placing an order, NOT browsing) ──────
function detectOrderIntent($msg) {
    $m = mb_strtolower(trim($msg));
    // "all/sab" signals → browsing, NOT single-item ordering
    if (preg_match('/\ball\b|\bsab\b|\bsara\b|\btamam\b|\bpoora\b/u', $m)) return false;
    $kws = [
        // English order phrases
        'order karna','order karo','order karna hai','order chahiye',
        'place order','place an order','place the order',
        'place this order','place my order','place the order for',
        'want to order','i want to order',
        'want to buy','i want to buy','need to buy','i need to buy',
        'i want to purchase','want to purchase',
        // Buy + karna variations (Urdu-English mix)
        'buy karna','buy karna hai','buy karna hy','buy karna he','buy karna h',
        'buy karo','buy kar do','buy kr do',
        // Lena variations
        'lena hai','lena hy','lena he','lena h','le lena','le lo',
        'lena chahta','lena chahti','lena chahiye',
        // Kharidna
        'kharidna hai','kharidna hy','kharidna chahta','kharidna chahti',
        // Book / checkout
        'book karna','book karo',
        'purchase','checkout','buy now','add to cart',
        // Order kardo variations
        'order kar do','order kardo','order krna','order krdena','order kr do',
    ];
    foreach ($kws as $k) if (mb_strpos($m,$k)!==false) return true;
    // "this buy [product]" — app tap-to-order format
    if (preg_match('/^(this|yeh|ye)\s+buy\b/u', $m)) return true;
    // "buy this/it/now/yeh" — "buy this Crimson Floral..."
    if (preg_match('/\bbuy\s+(this|it|yeh|ye|isey|ise|now)\b/u', $m)) return true;
    // "buy" at end of message
    if (preg_match('/\bbuy\s*[.!?]*$/u', $m)) return true;
    // "place ... order" — catches "place this order", "place the order", "please place order"
    if (preg_match('/\bplace\b.{0,20}\border\b/u', $m)) return true;
    // "order this/it/product" — "order this Crimson Floral..."
    if (preg_match('/\border\s+(this|it|kar|karo|karein|karna|dena|de|yeh|ye)\b/u', $m)) return true;
    // "order" at end of message — "Crimson Floral ka order"
    if (preg_match('/\border\s*[.!?]*$/u', $m)) return true;
    // Product URL in message alongside buy/order/lena word
    if (preg_match('/\/product\/[a-z0-9\-]+/i', $m) && preg_match('/\b(order|buy|lena|kharid)\b/i', $m)) return true;
    return false;
}

// ── External (non-store) brand detection ─────────────────────
// Only list INTERNATIONAL brands definitely not in a Pakistani store
function isExternalBrand($msg) {
    $m = mb_strtolower($msg);
    $external = [
        'zara','gucci','prada','versace','armani','chanel','louis vuitton','hermes',
        'burberry','ralph lauren','h&m','uniqlo','shein','forever21','primark',
        'nike','adidas','puma','reebok','under armour','levis','wrangler','gap',
        'mango','massimo dutti','marks spencer','next','asos',
    ];
    foreach ($external as $b) {
        if (mb_strpos($m, $b) !== false) return $b;
    }
    return null;
}

// ── Order tracking by order ID or phone number ───────────────
function getOrderStatus($conn, $message) {
    $m = $message;
    $row = null;
    // Look for numeric order ID (3-8 digits)
    if (preg_match('/\b(\d{3,8})\b/', $m, $matches)) {
        $oid = (int)$matches[1];
        $r = @mysqli_query($conn, "SELECT id, order_status, created_at FROM orders WHERE id = $oid LIMIT 1");
        if ($r) $row = mysqli_fetch_assoc($r);
    }
    // Look for phone number if no ID match
    if (!$row && preg_match('/\b(03\d{2}[\s\-]?\d{7}|\+923\d{9})\b/', $m, $matches)) {
        $phone = mysqli_real_escape_string($conn, preg_replace('/[\s\-]/', '', $matches[1]));
        $r = @mysqli_query($conn, "SELECT id, order_status, created_at FROM orders WHERE phone LIKE '%$phone%' ORDER BY id DESC LIMIT 1");
        if ($r) $row = mysqli_fetch_assoc($r);
    }
    return $row; // null if nothing found
}

// ── Detect order tracking intent ─────────────────────────────
function isOrderTrackIntent($msg) {
    $m = mb_strtolower($msg);
    $signals = ['track order','order track','order status','check order','mera order','order kahan',
                'order check','where is my order','order kitne din','delivered','pending','confirm',
                'order number','order id','order no'];
    foreach ($signals as $s) if (mb_strpos($m, $s) !== false) return true;
    // also: has a 3+ digit number AND has order-related word
    if (preg_match('/\b\d{3,8}\b/', $m) && preg_match('/\border|parcel|deliver\b/i', $m)) return true;
    return false;
}

// ── Detect "buy all [brand]" — bulk order intent ─────────────
function isBuyAllIntent($msg) {
    $m = mb_strtolower($msg);
    return preg_match('/\b(buy all|all products|sab products|sare products|all suits|sab suits|poora collection|sara collection|tamam|buy everything)\b/u', $m);
}

// ── Live store data ───────────────────────────────────────────
function getStoreData($conn) {
    $d = [];
    $r = mysqli_query($conn,"SELECT COUNT(*) c FROM products WHERE stock!='Out Stock'");
    $d['total_products'] = (int)mysqli_fetch_assoc($r)['c'];
    $r = mysqli_query($conn,"SELECT COUNT(*) c FROM brand");
    $d['total_brands'] = (int)mysqli_fetch_assoc($r)['c'];
    $r = mysqli_query($conn,"SELECT COUNT(*) c FROM products WHERE price_after_discount>0");
    $d['sale_count'] = (int)mysqli_fetch_assoc($r)['c'];
    $r = mysqli_query($conn,"SELECT MIN(price) mn, MAX(price) mx FROM products WHERE stock!='Out Stock' AND price > 100");
    $row = mysqli_fetch_assoc($r);
    $d['price_min'] = (int)($row['mn']??500);
    $d['price_max'] = (int)($row['mx']??15000);
    $r = mysqli_query($conn,"SELECT name FROM brand ORDER BY name ASC");
    $brands = [];
    while($row=mysqli_fetch_assoc($r)) $brands[] = $row['name'];
    $d['brands'] = implode(', ', $brands);
    $r = mysqli_query($conn,"SELECT name FROM category ORDER BY priority ASC");
    $cats=[];
    while($row=mysqli_fetch_assoc($r)) $cats[]=$row['name'];
    $d['categories'] = implode(', ', $cats);
    return $d;
}

// ── Fetch products for a brand ID ─────────────────────────────
function fetchBrandProducts($conn, $brandId, $base, $limit=10) {
    $result = mysqli_query($conn,
        "SELECT p.id, p.name, p.slug, p.image, p.price, p.price_after_discount, p.dis_amount, p.stock,
                b.name AS brand_name
         FROM products p LEFT JOIN brand b ON p.brand = b.id
         WHERE p.stock != 'Out Stock' AND p.brand = $brandId AND p.image != ''
         ORDER BY p.id DESC LIMIT $limit"
    );
    $products = [];
    while ($r = mysqli_fetch_assoc($result)) {
        $imgs = explode(',', $r['image']);
        $r['image_url'] = $base . '/admin/images/product/' . trim($imgs[0]);
        $r['sale_price'] = $r['price_after_discount'];
        $r['discount_percent'] = ($r['price'] > 0 && $r['price_after_discount'] > 0 && $r['price_after_discount'] < $r['price'])
            ? round((($r['price'] - $r['price_after_discount']) / $r['price']) * 100) : 0;
        $products[] = $r;
    }
    return $products;
}

// ── Search products ───────────────────────────────────────────
function searchProducts($conn, $message, $base) {
    $normMsg = normalizeName($message);

    // Load all brands (longest first to avoid short names matching inside longer ones)
    $brandResult = mysqli_query($conn, "SELECT id, name FROM brand ORDER BY LENGTH(name) DESC");
    $allBrands = [];
    while ($b = mysqli_fetch_assoc($brandResult)) $allBrands[] = $b;

    // ── Pass 1: Exact normalized brand match ─────────────────
    $exactBrandId = null; $exactBrandName = null;
    foreach ($allBrands as $b) {
        $normBrand = normalizeName($b['name']);
        if (mb_strlen($normBrand) >= 2 && mb_stripos($normMsg, $normBrand) !== false) {
            $exactBrandId   = (int)$b['id'];
            $exactBrandName = $b['name'];
            break;
        }
    }
    if ($exactBrandId) {
        $products = fetchBrandProducts($conn, $exactBrandId, $base, 12);
        return ['products'=>$products,'brand_found'=>true,'brand_name'=>$exactBrandName,'fuzzy_words'=>null,'has_products'=>count($products)>0];
    }

    // ── Pass 2: Partial/fuzzy word match ─────────────────────
    // Exclude very common English/Urdu words from fuzzy brand matching
    // (prevents "men" matching "Safwa Men", "new" matching "Feroza new", etc.)
    $brandStopWords = ['men','new','the','for','and','top','all','one','two','our','big',
                       'old','hot','red','raw','raw','set','max','pro','plus','buy','get',
                       'kha','use','her','his','its','any','not','but'];
    $fuzzyBrandName = null; $fuzzyMatchWords = null;
    $msgWords = array_unique(preg_split('/\s+/', $normMsg));
    foreach ($allBrands as $b) {
        $normBrand  = normalizeName($b['name']);
        $brandWords = array_filter(explode(' ', $normBrand), fn($w) => mb_strlen($w) >= 4 && !in_array($w, $brandStopWords));
        foreach ($brandWords as $bw) {
            foreach ($msgWords as $mw) {
                if (mb_strlen($mw) >= 4 && !in_array($mw, $brandStopWords) && $mw === $bw) {
                    $fuzzyBrandName  = $b['name'];
                    $fuzzyMatchWords = $mw;
                    break 3;
                }
            }
        }
    }
    if ($fuzzyBrandName) {
        return ['products'=>[],'brand_found'=>'fuzzy','brand_name'=>$fuzzyBrandName,'fuzzy_words'=>$fuzzyMatchWords,'has_products'=>false];
    }

    // ── Pass 3: No brand — keyword + category search ──────────
    // Extended stop words — query words that don't help find products
    $stopWords = ['do','you','have','the','a','an','is','are','show','me','some','any',
                  'what','i','want','need','looking','for','please','kya','hai','mujhe',
                  'chahiye','dikhao','brand','products','collection','available','stock',
                  'order','place','sab','sara','all','design','designs','wala','ki',
                  'karo','karna','kar','dena','dedo','lao','chahte','chahti','batao',
                  'suggest','recommend','tell','like','love','good','nice','best','top',
                  'acha','accha','sundar','khubsurat','wear','wearing','worn','get',
                  'buy','buying','purchase','find','search','dhundo','chahiye','mil',
                  'something','anything','kuch','koi','apna','apni','mera','meri',
                  'wali','wala','wale','liye','liye','ke','ka','ki','ko','se','mein'];
    $words    = preg_split('/\s+/', mb_strtolower($message));
    $keywords = array_filter($words, fn($w) => strlen($w) >= 4 && !in_array($w, $stopWords));

    // Detect gender/category AND subcategory from message
    $gender = null; $subgender = null;
    if (preg_match('/\b(kids?|children|child|baby|babies|bachon|bacha|bachi|baccha)\b/i', $message)) {
        $gender = 'kids';
        if      (preg_match('/\b(boys?)\b/i', $message))   $subgender = 'boys';
        elseif  (preg_match('/\b(girls?)\b/i', $message))  $subgender = 'womens';
    } elseif (preg_match('/\b(mens?|man|male|gents?|mard)\b/i', $message)) {
        $gender = 'men';
    } elseif (preg_match('/\b(womens?|woman|female|ladies|lady|aurat|larki)\b/i', $message)) {
        $gender = 'women';
    } elseif (preg_match('/\b(boys?)\b/i', $message)) {
        $gender = 'kids'; $subgender = 'boys';
    } elseif (preg_match('/\b(girls?)\b/i', $message)) {
        $gender = 'kids'; $subgender = 'womens';
    }

    // Detect occasions
    $occasionWhere = '';
    $occMap = [
        'eid'     => 'eid', 'wedding' => 'wedding', 'shadi' => 'wedding',
        'bridal'  => 'bridal', 'mehndi' => 'mehndi', 'party' => 'party',
        'office'  => 'office', 'casual'  => 'casual', 'summer' => 'summer',
        'winter'  => 'winter', 'festive' => 'festive', 'formal' => 'formal',
        'occasion'=> 'festive',
    ];
    foreach ($occMap as $kw => $tag) {
        if (mb_stripos($message, $kw) !== false) {
            $safe = mysqli_real_escape_string($conn, $tag);
            $occasionWhere = "AND (COALESCE(p.occasion_tag,'') LIKE '%$safe%' OR p.name LIKE '%$safe%' OR COALESCE(p.description,'') LIKE '%$safe%')";
            break;
        }
    }

    // ── Gender filter using p.category directly (proven to work) ──
    $genderWhere  = '';  // for p.category direct filter
    $subgenderWhere = ''; // for subcategory (boys/girls via join)
    if ($gender === 'kids') {
        if ($subgender === 'boys')      $subgenderWhere = "AND sc.id = 16";
        elseif ($subgender === 'womens') $subgenderWhere = "AND sc.id = 17";
        else                            $genderWhere    = "AND p.category = 5";
    } elseif ($gender === 'women')      $genderWhere    = "AND p.category = 2";
    elseif ($gender === 'men')          $genderWhere    = "AND p.category = 3";

    // Sale filter
    $saleWhere = '';
    if (preg_match('/\b(sale|discount|offer|sasta|cheap)\b/i', $message)) {
        $saleWhere = "AND (p.price_after_discount > 0 AND p.price_after_discount < p.price)";
    }

    // Build keyword conditions — skip gender/occasion words already handled
    $skipKws = ['kids','mens','women','boys','girls','child','baby','eid','wedding',
                'bridal','summer','winter','party','office','fashion','suits','suit',
                'dress','dresses','khaddar','chiffon','cotton','lawn','linen'];
    $kwConds = [];
    foreach (array_slice(array_values($keywords), 0, 5) as $kw) {
        if (in_array(strtolower($kw), $skipKws)) continue;
        $safe = mysqli_real_escape_string($conn, $kw);
        $kwConds[] = "(p.name LIKE '%$safe%' OR COALESCE(p.description,'') LIKE '%$safe%')";
    }
    $kwWhere = !empty($kwConds) ? 'AND (' . implode(' OR ', $kwConds) . ')' : '';

    // Limit — bigger for broad/occasion queries
    $limit = ($occasionWhere || !$kwWhere) ? 12 : 10;

    // Need subcategory JOIN only when filtering by subcategory (boys/girls)
    $joinSc = ($subgenderWhere) ? "LEFT JOIN subcategory sc ON p.sub_category = sc.id" : '';
    $allGenderWhere = $genderWhere . ' ' . $subgenderWhere;

    $result = mysqli_query($conn,
        "SELECT p.id, p.name, p.slug, p.image, p.price, p.price_after_discount, p.dis_amount, p.stock,
                b.name AS brand_name
         FROM products p
         LEFT JOIN brand b ON p.brand = b.id
         $joinSc
         WHERE p.stock != 'Out Stock' AND p.image != ''
         $allGenderWhere $occasionWhere $saleWhere $kwWhere
         ORDER BY p.id DESC LIMIT $limit"
    );
    $products = [];
    while ($r = mysqli_fetch_assoc($result)) {
        $imgs = explode(',', $r['image']);
        $r['image_url'] = $base . '/admin/images/product/' . trim($imgs[0]);
        $r['sale_price'] = $r['price_after_discount'];
        $r['discount_percent'] = ($r['price'] > 0 && $r['price_after_discount'] > 0 && $r['price_after_discount'] < $r['price'])
            ? round((($r['price'] - $r['price_after_discount']) / $r['price']) * 100) : 0;
        $products[] = $r;
    }

    // ── Subcategory fallback: if boys/girls returns nothing → show all kids ──
    if (empty($products) && $subgenderWhere) {
        $result_kids = mysqli_query($conn,
            "SELECT p.id, p.name, p.slug, p.image, p.price, p.price_after_discount, p.dis_amount, p.stock,
                    b.name AS brand_name
             FROM products p LEFT JOIN brand b ON p.brand = b.id
             WHERE p.stock != 'Out Stock' AND p.image != '' AND p.category = 5
             ORDER BY p.id DESC LIMIT 10"
        );
        while ($r = mysqli_fetch_assoc($result_kids)) {
            $imgs = explode(',', $r['image']);
            $r['image_url'] = $base . '/admin/images/product/' . trim($imgs[0]);
            $r['sale_price'] = $r['price_after_discount'];
            $r['discount_percent'] = ($r['price'] > 0 && $r['price_after_discount'] > 0 && $r['price_after_discount'] < $r['price'])
                ? round((($r['price'] - $r['price_after_discount']) / $r['price']) * 100) : 0;
            $products[] = $r;
        }
    }

    // ── Fallback 1: keyword search returned nothing → try without keywords (gender/occasion only) ──
    if (empty($products) && $kwWhere && ($allGenderWhere || $occasionWhere)) {
        $result2 = mysqli_query($conn,
            "SELECT p.id, p.name, p.slug, p.image, p.price, p.price_after_discount, p.dis_amount, p.stock,
                    b.name AS brand_name
             FROM products p
             LEFT JOIN brand b ON p.brand = b.id
             $joinSc
             WHERE p.stock != 'Out Stock' AND p.image != ''
             $allGenderWhere $occasionWhere $saleWhere
             ORDER BY p.id DESC LIMIT 10"
        );
        while ($r = mysqli_fetch_assoc($result2)) {
            $imgs = explode(',', $r['image']);
            $r['image_url'] = $base . '/admin/images/product/' . trim($imgs[0]);
            $r['sale_price'] = $r['price_after_discount'];
            $r['discount_percent'] = ($r['price'] > 0 && $r['price_after_discount'] > 0 && $r['price_after_discount'] < $r['price'])
                ? round((($r['price'] - $r['price_after_discount']) / $r['price']) * 100) : 0;
            $products[] = $r;
        }
    }

    // ── Fallback 2: very few products from occasion search → broaden to recent ──
    if (count($products) <= 2 && $occasionWhere && !$allGenderWhere) {
        $result3 = mysqli_query($conn,
            "SELECT p.id, p.name, p.slug, p.image, p.price, p.price_after_discount, p.dis_amount, p.stock,
                    b.name AS brand_name
             FROM products p LEFT JOIN brand b ON p.brand = b.id
             WHERE p.stock != 'Out Stock' AND p.image != ''
             ORDER BY p.id DESC LIMIT 10"
        );
        while ($r = mysqli_fetch_assoc($result3)) {
            $imgs = explode(',', $r['image']);
            $r['image_url'] = $base . '/admin/images/product/' . trim($imgs[0]);
            $r['sale_price'] = $r['price_after_discount'];
            $r['discount_percent'] = ($r['price'] > 0 && $r['price_after_discount'] > 0 && $r['price_after_discount'] < $r['price'])
                ? round((($r['price'] - $r['price_after_discount']) / $r['price']) * 100) : 0;
            $products[] = $r;
        }
    }

    return ['products'=>$products,'brand_found'=>null,'brand_name'=>null,'fuzzy_words'=>null,'has_products'=>count($products)>0];
}

// ── Decide if we need to search products ─────────────────────
function needsProducts($msg) {
    $m = mb_strtolower($msg);
    if (detectOrderIntent($msg)) return false;
    // Use word-boundary regex for short words that appear as substrings (e.g. "men" in "payment")
    $wordBoundary = ['men','new','buy','need','show','find','suit','suits','sale','kids','silk'];
    foreach ($wordBoundary as $s) {
        if (preg_match('/\b' . preg_quote($s, '/') . '\b/u', $m)) return true;
    }
    // Substring signals (longer, less likely to false-match)
    $signals = [
        'dikhao','dhundo','kuch dikhao','chahiye','kharidna','recommend','suggest',
        'kya available','hai kya','options','lawn','linen','khaddar','dupatta',
        'collection','eid','wedding','shadi','bridal','mehndi','party','summer',
        'winter','office','casual','formal','festive','occasion','3pc','2pc',
        'embroidered','printed','discount','offer','sasta','budget','latest',
        'arrival','naya','children','bacha','gents','ladies','women','chiffon',
        'cotton','fabric','shirt','shalwar','kameez','lehenga','dress','kurta',
        'products','brand','textile','studio','clothing','garments','fashion',
        'cultural','traditional','punjabi','sindhi','kashmiri','balochi','pashtun',
        'ajrak','phulkari','khet partug','pheran','regional','heritage',
    ];
    foreach ($signals as $s) if (mb_strpos($m,$s)!==false) return true;
    return false;
}

// ── Smart local fallback — covers everything when Gemini is down ─
function localFallback($message, $products, $store, $brandFound, $brandName) {
    $m  = mb_strtolower(trim($message));
    $p  = count($products);
    $tp = $store['total_products'] ?? '300+';
    $tb = $store['total_brands']   ?? '24';
    $pn = $store['price_min']      ?? 1500;
    $px = $store['price_max']      ?? 8999;

    // ── FYP / Project questions (detect first — high priority) ────
    if (preg_match('/\b(project|fyp|final year|technology|tech stack|how does|how do|explain|features?|database|mobile apps?|react native|php|mysql|gemini|apis?|backend|frontend|architecture|visual search|algorithm|system)\b/i', $m))
        return fypAnswer($m, $tp, $tb);

    // ── Greetings ─────────────────────────────────────────────────
    if (preg_match('/\b(hi|hello|hey|salam|assalam|aoa|slamoalaikum|morning|afternoon|evening|night)\b/i', $m))
        return "Salam! 👋 Main Laiba hoon — Kapraa ki AI fashion consultant. Kya dhundh rahe hain aaj?";

    // ── Who are you / name ────────────────────────────────────────
    if (preg_match('/\b(who are you|your name|tumhara naam|kaun ho|laiba)\b/i', $m))
        return "Main Laiba hoon — Kapraa ki AI fashion assistant! Suit, dress, brand — kuch bhi poochein, main help karungi. 😊";

    // ── Thanks / Acknowledgements ─────────────────────────────────
    if (preg_match('/\b(thanks|thank you|shukriya|jazakallah|shukria|mehrbani)\b/i', $m))
        return "Khushi hui madad karke! Kuch aur chahiye toh zaroor batayein. 😊";
    if (preg_match('/\b(theek|theek hai|okay|ok|acha|accha|alright|got it|samajh|noted)\b/i', $m))
        return "Ji bilkul! Koi bhi sawaal ho ya kuch dhundna ho — main yahaan hoon. 😊";

    // ── Delivery ──────────────────────────────────────────────────
    if (preg_match('/\b(delivery|shipping|kitne din|dispatch|deliver)\b/i', $m))
        return "Delivery 3–5 working days mein hoti hai. Pakistan-wide charges sirf Rs 200 hain! 📦";

    // ── Return / Exchange ─────────────────────────────────────────
    if (preg_match('/\b(return|exchange|wapas|refund|change)\b/i', $m))
        return "7 din mein return ya exchange ho sakta hai delivery ke baad. WhatsApp: +92 320 9815213 📱";

    // ── Payment methods ───────────────────────────────────────────
    if (preg_match('/\b(payment|jazzcash|easypaisa|cod|cash on delivery|pay kaise|how to pay)\b/i', $m))
        return "Payment options: COD (Cash on Delivery), JazzCash, ya EasyPaisa! Jo bhi easy lage. 💳";

    // ── Contact / WhatsApp ────────────────────────────────────────
    if (preg_match('/\b(whatsapp|contact|phone number|helpline|support)\b/i', $m))
        return "WhatsApp: +92 320 9815213 | Website: kapraa.pk. Hum yahan hain! 📞";

    // ── Price range ───────────────────────────────────────────────
    if (preg_match('/\b(price range|price list|kitna price|cost|rate|how much|budget)\b/i', $m))
        return "Hamare products Rs {$pn} se Rs {$px} tak hain! Budget batayein — main best option dhundh deti hoon. 💰";

    // ── How many products/brands ──────────────────────────────────
    if (preg_match('/\b(how many|kitne products|total|kitni variety|products available)\b/i', $m))
        return "Abhi {$tp} products available hain — {$tb} brands se! Bahut variety hai. 🛍️";

    // ── What brands do you have ───────────────────────────────────
    if (preg_match('/\b(brands|brand list|konse brand|which brands|brand available)\b/i', $m))
        return "Hamare brands: {$store['brands']}. Koi specific brand dhundh rahe hain? 😊";

    // ── Order tracking ────────────────────────────────────────────
    if (preg_match('/\b(track|order status|mera order|order kahan|check order|delivered|pending|confirm|order id|order no)\b/i', $m))
        return "Order track karne ke liye: Order ID ya phone number WhatsApp pe bhejein: +92 320 9815213. Ya account.php > My Orders mein dekh saktay hain! 📦";

    // ── Off-topic deflection ──────────────────────────────────────
    if (preg_match('/\b(cricket|football|food|recipe|biryani|politics|movie|song|weather|math|2\+2|laptop|phone|mobile)\b/i', $m))
        return "Haha! Main sirf fashion ki expert hoon. 😄 Koi suit, dress ya brand dhundna ho toh zaroor batao!";

    // ── Buy ALL brand products ────────────────────────────────────
    if (isBuyAllIntent($message) && $p > 0 && $brandFound === true)
        return "Yahan {$brandName} ke saare {$p} products hain! 👇\n\nEk product order karne ke liye naam ya link share karein.\nBulk order ke liye: 📞 WhatsApp +92 320 9815213";
    if (isBuyAllIntent($message) && $p > 0)
        return "Yahan saare {$p} products hain! 👇\nBulk ke liye WhatsApp: +92 320 9815213 📦";

    // ── Order intent (single product) ────────────────────────────
    if (detectOrderIntent($message))
        return "Bilkul order karte hain! 😊\n\nKaunsa product chahiye?\n🔗 Link paste karein\n✍️ Poora naam likhein\n🖼️ Image upload karein";

    // ── External brand ────────────────────────────────────────────
    $extBrand = isExternalBrand($message);
    if ($extBrand && $p === 0) {
        $extName = ucwords($extBrand);
        $ourSample = implode(', ', array_slice(explode(', ', $store['brands']), 0, 4));
        return "Ye store Pakistani brands ka hai — {$extName} hamare paas available nahi. Lekin hamare paas {$ourSample} aur aur bahut brands hain! Kuch show karoon?";
    }

    // ── Products found ────────────────────────────────────────────
    if ($p > 0 && $brandFound === true)
        return "Ji! {$brandName} ke {$p} products mil gaye. Neeche dekho — tap karke detail pe jao ya Add to Cart karo! 🛍️";
    if ($p > 0)
        return "Ji! {$p} products mil gaye aapke liye. Neeche dekho aur apna pasandida chunein! 👇";

    // ── Brand in store but no stock ───────────────────────────────
    if ($brandFound === true && $p === 0)
        return "{$brandName} hamare store mein hai lekin abhi stock nahi. Jald aayega — doosre brands check karein!";

    // ── Fuzzy brand match ─────────────────────────────────────────
    if ($brandFound === 'fuzzy')
        return "Kya aap {$brandName} ke baare mein pooch rahe hain? Confirm karein toh main products dikhata hoon!";

    // ── Generic helpful response ──────────────────────────────────
    return "Ji! {$tp} products available hain {$tb} Pakistani brands se. Brand, occasion, ya budget batayein — main best option dhundh deti hoon! 😊";
}

// ── Targeted FYP answers based on what was asked ─────────────
function fypAnswer($m, $tp, $tb) {
    if (mb_strpos($m,'database')!==false || mb_strpos($m,'mysql')!==false || mb_strpos($m,'sql')!==false)
        return "Database: MySQL with tables for products, brands, categories, orders, users, cart, wishlist, and chat messages. MySQLi prepared statements are used for security (prevents SQL injection). The DB has {$tp} real products from {$tb} Pakistani brands. 🗄️";
    if (mb_strpos($m,'visual search')!==false || mb_strpos($m,'image search')!==false)
        return "Visual Search: User uploads any clothing photo → Gemini Vision API analyzes it → extracts keywords (fabric, color, style) → searches similar products in DB. Also uses pHash (perceptual hashing) for exact image matching — if you upload the exact same product image, it finds it instantly! 🔍";
    if (mb_strpos($m,'mobile')!==false || mb_strpos($m,'react native')!==false || mb_strpos($m,'app')!==false)
        return "Mobile App: Built with React Native (Expo) — cross-platform for Android & iOS. It connects to the same PHP API backend. Features: AI chat, product browse, cart, checkout, visual search. APK is available for Android! 📱";
    if (mb_strpos($m,'how does')!==false || mb_strpos($m,'how do')!==false || mb_strpos($m,'ai work')!==false || mb_strpos($m,'chatbot')!==false)
        return "How AI works: User sends message → PHP detects intent (brand? category? occasion?) → queries MySQL for matching products → sends products + system prompt to Gemini API → Gemini returns natural language reply → shown in chat with product carousel. Fully dynamic — live DB data every request! 🤖";
    if (mb_strpos($m,'technology')!==false || mb_strpos($m,'tech stack')!==false || mb_strpos($m,'built')!==false)
        return "Tech Stack: PHP 8 backend + MySQL database + HTML/CSS/Bootstrap frontend. Mobile app: React Native (Expo). AI: Google Gemini API. Hosting: cPanel shared hosting. Visual Search: Gemini Vision + pHash. Emails: PHP mail(). No heavy framework — lightweight and fast! ⚙️";
    if (mb_strpos($m,'feature')!==false)
        return "Key Features: (1) AI Chatbot in Urdu/English, (2) Visual Search by photo, (3) Brand/category/occasion filtering, (4) Admin panel, (5) Order management, (6) Email notifications, (7) COD + JazzCash + EasyPaisa payments, (8) React Native mobile app. Total: {$tp} products, {$tb} brands! ✨";
    if (mb_strpos($m,'backend')!==false || mb_strpos($m,'php')!==false)
        return "Backend: Pure PHP 8 (no framework — for performance on shared hosting). REST APIs return JSON. MySQLi for database. PHP mail() for emails. Gemini API called via cURL. Admin panel is also PHP. Deployed on cPanel hosting at kapraa.pk. 🖥️";
    if (mb_strpos($m,'frontend')!==false)
        return "Frontend: HTML5, CSS3, Bootstrap 5, JavaScript (jQuery). Responsive design — works on mobile, tablet, and desktop. AI chat widget is a floating bubble (th_widget.js) loaded on every page. Product carousels are dynamic JS. 🎨";
    // Generic FYP answer
    return "Kapraa is an FYP (Final Year Project) — a full-stack AI e-commerce platform for Pakistani clothing! Tech: PHP + MySQL + React Native app + Google Gemini AI. It has {$tp} real products, {$tb} brands, AI chatbot, visual search, admin panel, and order management. Live at kapraa.pk 🎓";
}

// ── Gemini call with retry ────────────────────────────────────
function callGemini($GEMINI_URL, $payload, $attempt=1) {
    $ch = curl_init($GEMINI_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200 && $res) {
        $d     = json_decode($res, true);
        $text  = trim((string)($d['candidates'][0]['content']['parts'][0]['text'] ?? ''));
        if ($text) return $text;
    }
    // Rate limit — don't retry
    if ($code === 429) return null;
    // Retry once on failure
    if ($attempt < 2) return callGemini($GEMINI_URL, $payload, $attempt+1);
    return null;
}

// ══════════════════════════════════════════════════════════════
// MAIN LOGIC
// ══════════════════════════════════════════════════════════════
$store        = getStoreData($conn);
$products     = [];
$brandFound   = null;
$brandName    = null;
$hasProducts  = false;
$isOrderFlow  = detectOrderIntent($message);
$isTrackOrder = isOrderTrackIntent($message);
$isBuyAll     = isBuyAllIntent($message);
$externalBrand = isExternalBrand($message); // e.g. "Zara", "Gucci", "Nike"

// ── Order tracking: check DB before anything else ────────────
if ($isTrackOrder) {
    $orderRow = getOrderStatus($conn, $message);
    if ($orderRow) {
        $statusEmoji = [
            'pending'   => '🕐',
            'confirmed' => '✅',
            'processing'=> '📦',
            'shipped'   => '🚚',
            'delivered' => '🎉',
            'cancelled' => '❌',
        ];
        $s = strtolower($orderRow['order_status'] ?? 'pending');
        $emoji = $statusEmoji[$s] ?? '📋';
        $date = date('d M Y', strtotime($orderRow['created_at']));
        $reply = "{$emoji} Order #{$orderRow['id']} — Status: " . ucfirst($s) . " | Placed: {$date}. ";
        if ($s === 'pending') $reply .= "Hum jald confirm karenge! Sawal ho toh WhatsApp: +92 320 9815213";
        elseif ($s === 'confirmed') $reply .= "Aapka order confirm ho gaya! 3–5 working days mein deliver hoga. 📦";
        elseif ($s === 'shipped') $reply .= "Aapka parcel raste mein hai! Jald pahunchega. 🚚";
        elseif ($s === 'delivered') $reply .= "Delivered ho gaya! Umeed hai pasand aaya hoga. 😊";
        elseif ($s === 'cancelled') $reply .= "Order cancel tha. Koi masla ho toh WhatsApp: +92 320 9815213";
        echo json_encode(['reply'=>$reply,'products'=>[]], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;
    }
    // No order found - will fall through to Gemini with a tracking-context note
}

// ── COD Order Flow: handle multi-step stateless order placement ──────────────
$imageB64  = $imageB64  ?? '';   // already parsed from input above
$mimeType  = trim((string)($in['mime_type'] ?? 'image/jpeg'));
$orderFlowResult = handleOrderFlowStateless($conn, $message, $history, $orderStep, $orderData, $base, $imageB64, $mimeType, $GEMINI_URL);
if ($orderFlowResult !== null) {
    echo json_encode($orderFlowResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ── Product search: run unless it's a single-item order (not "buy all") ──────
// "Buy all KHADDI" = browse/show all → search products
// "I want to buy [specific product]" = order intent → handleOrderFlowStateless already handled it
if (!$externalBrand && (!$isOrderFlow || $isBuyAll) && needsProducts($message)) {
    $searchResult = searchProducts($conn, $message, $base);
    $products     = $searchResult['products'];
    $brandFound   = $searchResult['brand_found'];
    $brandName    = $searchResult['brand_name'];
    $hasProducts  = $searchResult['has_products'];
}

// ── Buy-all: exit immediately with WhatsApp redirect — never let Gemini fake a bulk order ──
if ($isBuyAll && $hasProducts) {
    $bn    = $brandName ?: 'in';
    $cnt   = count($products);
    $reply = "Yahan {$bn} ke saare {$cnt} products hain! 👇\n\nKisi ek product ka order karne ke liye:\n✍️ Product ka naam ya link share karein — main step-by-step order place karunga!\n\nBulk/wholesale order ke liye:\n📞 WhatsApp: *+92 320 9815213*";
    echo json_encode(['reply'=>$reply,'products'=>$products], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}
if ($isBuyAll && !$hasProducts) {
    $reply = "Abhi is brand ka stock available nahi. Jald aa raha hai!\n📞 Bulk order ke liye WhatsApp: *+92 320 9815213*";
    echo json_encode(['reply'=>$reply,'products'=>[]], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

// ── Build product context for Gemini ─────────────────────────
$brandList   = $store['brands'];
$totalProds  = $store['total_products'];
$totalBrands = $store['total_brands'];
$saleCount   = $store['sale_count'];
$priceMin    = $store['price_min'];
$priceMax    = $store['price_max'];
$categories  = $store['categories'];

$productContext = '';
if ($isTrackOrder) {
    $productContext = "\n\nORDER TRACKING REQUEST: Customer wants to track their order. Tell them to: share their Order ID or phone number via WhatsApp +92 320 9815213 OR check 'My Orders' in their account. If they already provided an order number/phone in their message, the system will look it up automatically.";
} elseif ($isBuyAll && $hasProducts) {
    $productContext = "\n\nBUY ALL REQUEST — " . count($products) . " products shown:\n";
    foreach ($products as $p) {
        $price = ($p['price_after_discount']>0) ? $p['price_after_discount'] : $p['price'];
        $disc  = $p['discount_percent'] > 0 ? " ({$p['discount_percent']}% off)" : '';
        $bname = $p['brand_name'] ? " | {$p['brand_name']}" : '';
        $productContext .= "• {$p['name']}{$bname} — Rs {$price}{$disc}\n";
    }
    $productContext .= "\nCustomer wants to buy all/many of these. Tell them: to order individual items, tap the product card → Add to Cart → Checkout. For bulk orders, WhatsApp: +92 320 9815213. Be enthusiastic but concise!";
} elseif ($isOrderFlow) {
    $productContext = "\n\nORDER INTENT DETECTED: Customer wants to place an order via AI chat. Tell them to share the exact product name, link, or image so you can place the order step by step. Do NOT say 'tap Add to Cart'. Keep reply short.";
} elseif ($brandFound === 'fuzzy') {
    $productContext = "\n\nBRAND CLARIFICATION NEEDED: Customer partially mentioned '{$brandName}'. Politely ask: 'Kya aap {$brandName} ke baare mein pooch rahe hain?' Do NOT show products yet.";
} elseif ($brandFound === true && $hasProducts) {
    $productContext = "\n\nPRODUCTS FOUND FOR {$brandName} ({$totalProds} total in store):\n";
    foreach ($products as $p) {
        $price = ($p['price_after_discount']>0) ? $p['price_after_discount'] : $p['price'];
        $disc  = $p['discount_percent'] > 0 ? " ({$p['discount_percent']}% off)" : '';
        $productContext .= "• {$p['name']} — Rs {$price}{$disc}\n";
    }
    $productContext .= "\nTell the customer about these {$brandName} products naturally. Suggest tapping on them to view details.";
} elseif ($brandFound === true && !$hasProducts) {
    $productContext = "\n\nBRAND '{$brandName}' IS IN OUR STORE but currently out of stock. Tell the customer honestly and suggest similar brands.";
} elseif ($hasProducts) {
    $productContext = "\n\nPRODUCTS FOUND (".count($products)." items):\n";
    foreach ($products as $p) {
        $price = ($p['price_after_discount']>0) ? $p['price_after_discount'] : $p['price'];
        $disc  = $p['discount_percent'] > 0 ? " ({$p['discount_percent']}% off)" : '';
        $bname = $p['brand_name'] ? " | {$p['brand_name']}" : '';
        $productContext .= "• {$p['name']}{$bname} — Rs {$price}{$disc}\n";
    }
    $productContext .= "\nTell the customer about these products naturally and suggest tapping on them.";
} elseif (needsProducts($message) && !$hasProducts && !$isOrderFlow) {
    $productContext = "\n\nNO PRODUCTS FOUND for this query in current stock. Apologize briefly and suggest they try a different search or browse by category. Suggest similar available brands: {$brandList}";
}

// ── System prompt — highly trained for FYP examiners ─────────
$system = <<<PROMPT
You are LAIBA — a friendly, smart AI fashion consultant at Kapraa (Pakistan's online clothing store). Reply in conversational English with light Urdu words (like "ji", "bilkul", "yaar") mixed in. Keep replies to 2-4 sentences max — short and helpful.

=== STORE INFO (LIVE DATA) ===
Total products: {$totalProds} | Total brands: {$totalBrands} | Price range: Rs {$priceMin}–{$priceMax}
Products on sale: {$saleCount}
OUR BRANDS: {$brandList}
CATEGORIES: {$categories}
FABRICS: Lawn=summer lightweight | Khaddar=winter warm | Chiffon=formal/bridal | Linen=all-season | Silk=luxury | Cotton=casual everyday

=== POLICIES ===
Delivery: Rs 200 flat | 3–5 working days | Nationwide Pakistan
Returns: 7-day return/exchange policy
Payment: COD, JazzCash, EasyPaisa
WhatsApp: +92 320 9815213
Website: kapraa.pk

=== ABOUT THIS PROJECT (FYP INFO — answer confidently to examiners) ===
Kapraa is a Final Year Project (FYP) — a full-featured e-commerce web + mobile app for Pakistani clothing.
TECHNOLOGY STACK:
• Frontend Web: HTML, CSS, Bootstrap, JavaScript (jQuery)
• Mobile App: React Native (Expo) — cross-platform Android/iOS
• Backend: PHP (REST APIs), MySQL database
• AI Features: Google Gemini 1.5 Flash API for natural language chat
• Visual Search: Gemini Vision + pHash (perceptual hashing) for image-based product search
• Payment: COD, JazzCash, EasyPaisa integration
• Email: PHP mail() with SMTP fallback for order confirmations, OTPs, campaigns

KEY FEATURES:
1. AI Chatbot (LAIBA) — natural language shopping assistant in Urdu/English
2. Visual Search — upload a photo of any clothing, AI finds similar products
3. Order Management — full cart, checkout, COD/digital payment
4. Admin Panel — product/order/brand/category management with analytics
5. Email Notifications — order confirmations, forgot password, email campaigns
6. User Accounts — register, login, OTP verification, profile management
7. Real-time Inventory — live stock management
8. Mobile App — React Native Expo app connecting to same PHP API backend
9. Occasion-based Search — find products by Eid, wedding, party, casual etc.
10. Cultural Dress Search — Punjabi, Sindhi, Balochi, Kashmiri, Pashtun dresses

DATABASE: MySQL with tables for products, brands, categories, subcategories, orders, order_items, users, cart, wishlist, color, occasion_tags, cultural_tags
WHY THIS PROJECT: Solves the problem of online clothing shopping in Pakistan with local brands, local payment methods, and AI assistance in Urdu/English.

=== STRICT RULES ===
1. NEVER show Brand A products when asked about Brand B
2. NEVER mix categories — kids query → kids products ONLY; men → men ONLY; women → women ONLY
3. If brand NOT in OUR BRANDS list → say we don't carry it, suggest 2 similar brands we do have
4. Off-topic questions (food, cricket, politics, tech gadgets) → redirect politely to fashion
5. Only mention prices from data given — NEVER make up prices or totals
6. If asked about the AI/project/technology → answer confidently using the FYP INFO section above
7. NEVER invent product counts for regional/cultural categories — only use actual products shown below
8. Order tracking: direct customer to WhatsApp +92 320 9815213 or their account's My Orders page
9. "Buy all brand" → show products + say "WhatsApp +92 320 9815213 for bulk orders"

=== ORDER RULES — READ CAREFULLY ===
10. NEVER confirm, simulate, place, or pretend to place any order — you CANNOT place orders
11. NEVER ask for customer name, phone, address, or payment in chat — the order system handles this
12. NEVER create or invent order numbers, confirmation codes, or order IDs
13. NEVER calculate or invent a total price for multiple products
14. If customer wants to ORDER → say: "Exact product ka naam, link, ya image share karein — main order process karunga!"
15. If customer wants to buy ALL products of a brand → say: "Ek ek product order hoga. Koi specific product chunein ya bulk ke liye WhatsApp: +92 320 9815213"

=== PRODUCT RULES ===
16. NEVER ask about SIZE — our products are unstitched fabric, sizes don't apply
17. NEVER ask stitched or unstitched — almost ALL products are UNSTITCHED by default. Only a few Janan Volume suits are stitched and they are clearly labeled
18. NEVER ask about colors — customer chooses from product page
19. Keep all replies under 3-4 sentences. Do NOT write long paragraphs

{$productContext}
PROMPT;

// ── Build conversation history ────────────────────────────────
$contents = [];
// Include last 6 turns for better context
foreach (array_slice($history, -6) as $h) {
    $role = ($h['role'] === 'assistant') ? 'model' : 'user';
    $text = trim((string)($h['content'] ?? ''));
    if ($text === '') continue;
    $contents[] = ['role'=>$role,'parts'=>[['text'=>$text]]];
}
$contents[] = ['role'=>'user','parts'=>[['text'=>$system."\n\n---\nCustomer: ".$message]]];

$payload = json_encode([
    'contents'         => $contents,
    'generationConfig' => ['temperature'=>0.7,'maxOutputTokens'=>280,'topP'=>0.90],
], JSON_UNESCAPED_UNICODE);

// ── Call Gemini ───────────────────────────────────────────────
$reply = callGemini($GEMINI_URL, $payload);

// ── Fallback if Gemini fails ──────────────────────────────────
if (!$reply) {
    // Try smart local fallback first
    $fb = localFallback($message, $products, $store, $brandFound, $brandName);
    if ($fb) {
        $reply = $fb;
    } elseif ($hasProducts) {
        $reply = "Ji! Maine " . count($products) . " products dhundhe hain aapke liye. Neeche dekho! 🛍️";
    } elseif ($brandFound === 'fuzzy') {
        $reply = "Kya aap {$brandName} ke baare mein pooch rahe hain? Confirm karein!";
    } else {
        // Give a helpful response based on message content, not an error
        $m = mb_strtolower($message);
        if (mb_strpos($m,'delivery')!==false || mb_strpos($m,'shipping')!==false) {
            $reply = "Delivery 3–5 working days, charges Rs 200 — Pakistan-wide! 📦";
        } elseif (mb_strpos($m,'return')!==false || mb_strpos($m,'exchange')!==false) {
            $reply = "7 din mein return/exchange kar sakte hain. WhatsApp: +92 320 9815213";
        } elseif (mb_strpos($m,'payment')!==false || mb_strpos($m,'pay')!==false) {
            $reply = "COD, JazzCash, ya EasyPaisa — hamare payment options hain!";
        } elseif (mb_strpos($m,'eid')!==false) {
            $reply = "Eid ke liye hamare paas {$totalProds} products available hain! Neeche diye gaye options dekho ya search karein! 🌙";
        } elseif (detectOrderIntent($message)) {
            $reply = "Bilkul order karte hain! 😊\n\nKaunsa product chahiye? 3 tareeqe:\n\n🔗 Product ka *link paste* karein\n✍️ Product ka *poora naam* likhein\n🖼️ Product ki *image upload* karein";
        } else {
            $reply = "Ji! Koi bhi suit, dress ya brand dhundna ho toh batayein — main help karungi! Abhi {$totalProds} products available hain. 😊";
        }
    }
}

echo json_encode(['reply'=>$reply,'products'=>$products], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
