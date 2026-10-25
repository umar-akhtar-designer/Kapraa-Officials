<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
if (!$conn) { echo json_encode(['success'=>false,'error'=>mysqli_connect_error()]); exit; }
mysqli_set_charset($conn, 'utf8');

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base = $isLocal ? 'http://'.$host.'/kapraa' : 'https://'.$host;
$user_id = (int)($_POST['user_id'] ?? 0);

if (!$user_id) {
    echo json_encode(['success'=>false,'message'=>'Please login to place an order']);
    exit;
}

// ── Get cart items for this user ──────────────────────────────
$uid_key = 'app_' . $user_id;
$uid_esc = mysqli_real_escape_string($conn, $uid_key);

$cart_result = mysqli_query($conn,
    "SELECT c.id, c.product_id, c.quantity, c.sizes, c.colors, c.stitching, c.price_type,
            p.name, p.image, p.price, p.price_after_discount, p.stitched_price, p.shipp_cost
     FROM cart c
     JOIN products p ON c.product_id = p.id
     WHERE c.user_id = '$uid_esc'"
);

$cart_items = [];
while ($r = mysqli_fetch_assoc($cart_result)) $cart_items[] = $r;

if (empty($cart_items)) {
    echo json_encode(['success'=>false,'message'=>'Your cart is empty']);
    exit;
}

// ── Validate required fields ──────────────────────────────────
$name       = trim($_POST['name']        ?? '');
$email      = trim($_POST['email']       ?? '');
$phone      = trim($_POST['phone']       ?? '');
$address    = trim($_POST['address']     ?? '');
$city       = trim($_POST['city']        ?? '');
$state      = trim($_POST['state']       ?? '');
$country    = trim($_POST['country']     ?? 'Pakistan');
$postal     = trim($_POST['postal_code'] ?? '');
$notes      = trim($_POST['notes']       ?? '');
$payment    = trim($_POST['payment']     ?? 'cod');
$stripe_id  = trim($_POST['stripe_payment_intent'] ?? '');

// ── Auto-add payment_proof column if missing ──────────────────
mysqli_query($conn, "ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_proof VARCHAR(255) DEFAULT NULL");

// ── Handle payment proof image upload ────────────────────────
$proof_filename = '';
if (!empty($_FILES['payment_proof']['tmp_name'])) {
    $ext     = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];
    if (in_array($ext, $allowed) && $_FILES['payment_proof']['size'] < 5000000) {
        $proof_filename = 'proof_' . uniqid() . '.' . $ext;
        $dest = __DIR__ . '/../admin/images/payment_proofs/' . $proof_filename;
        if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
        move_uploaded_file($_FILES['payment_proof']['tmp_name'], $dest);
    }
}

if (!$name || !$phone || !$address || !$city) {
    echo json_encode(['success'=>false,'message'=>'Please fill all required fields (name, phone, address, city)']);
    exit;
}

// ── Calculate totals ──────────────────────────────────────────
$subtotal = 0;
$shipping = 0;

foreach ($cart_items as $item) {
    $price = ($item['price_after_discount'] > 0)
        ? (float)$item['price_after_discount']
        : (float)$item['price'];
    if ($item['stitching'] == 1 && !empty($item['stitched_price'])) {
        $price += (float)$item['stitched_price'];
    }
    $subtotal += $price * (int)$item['quantity'];
    $shipping += (float)($item['shipp_cost'] ?? 0);
}

// Cap shipping at one reasonable amount (don't add per-item if large)
if ($shipping > 500) $shipping = 500;
$total = $subtotal + $shipping;

// ── Generate order number ─────────────────────────────────────
$order_number = 'TH-' . strtoupper(substr(md5(uniqid()), 0, 8));

// ── Escape all strings ────────────────────────────────────────
$name_e    = mysqli_real_escape_string($conn, $name);
$email_e   = mysqli_real_escape_string($conn, $email);
$phone_e   = mysqli_real_escape_string($conn, $phone);
$address_e = mysqli_real_escape_string($conn, $address);
$city_e    = mysqli_real_escape_string($conn, $city);
$state_e   = mysqli_real_escape_string($conn, $state);
$country_e = mysqli_real_escape_string($conn, $country);
$postal_e  = mysqli_real_escape_string($conn, $postal);
$notes_e   = mysqli_real_escape_string($conn, $notes);
$pay_e     = mysqli_real_escape_string($conn, in_array($payment, ['cod','manual']) ? $payment : 'cod'); // stripe disabled
$proof_e   = mysqli_real_escape_string($conn, $proof_filename);
$stripe_e  = mysqli_real_escape_string($conn, $stripe_id);

// ── Insert order ──────────────────────────────────────────────
$ok = mysqli_query($conn,
    "INSERT INTO orders
        (order_number, customer_name, email, phone, address, city, state, country, postal_code,
         notes, payment_method, payment_status, order_status,
         subtotal, shipping_cost, tax, total_amount, payment_proof)
     VALUES
        ('$order_number','$name_e','$email_e','$phone_e','$address_e','$city_e','$state_e','$country_e','$postal_e',
         '$notes_e','$pay_e','" . ($payment==='stripe' ? 'paid' : 'pending') . "','pending',
         $subtotal, $shipping, 0, $total, '$proof_e')"
);

if (!$ok) {
    echo json_encode(['success'=>false,'message'=>'Failed to create order: ' . mysqli_error($conn)]);
    exit;
}

$order_id = mysqli_insert_id($conn);

// ── Insert order items ────────────────────────────────────────
foreach ($cart_items as $item) {
    $price = ($item['price_after_discount'] > 0)
        ? (float)$item['price_after_discount']
        : (float)$item['price'];
    $stitch_price = ($item['stitching'] == 1 && !empty($item['stitched_price']))
        ? (float)$item['stitched_price'] : 0;
    $item_total = ($price + $stitch_price) * (int)$item['quantity'];

    $pname  = mysqli_real_escape_string($conn, $item['name']);
    $pimage = mysqli_real_escape_string($conn, $item['image']);
    $psize  = mysqli_real_escape_string($conn, $item['sizes']  ?? '');
    $pcolor = mysqli_real_escape_string($conn, $item['colors'] ?? '');
    $pid    = (int)$item['product_id'];
    $qty    = (int)$item['quantity'];

    mysqli_query($conn,
        "INSERT INTO order_items
            (order_id, product_id, product_name, product_image, size, colors,
             quantity, price, stitching_price, shipping_cost, total_price)
         VALUES
            ($order_id, $pid, '$pname', '$pimage', '$psize', '$pcolor',
             $qty, $price, $stitch_price, 0, $item_total)"
    );
}

// ── Clear this user's cart ────────────────────────────────────
mysqli_query($conn, "DELETE FROM cart WHERE user_id = '$uid_esc'");

// ── Send order confirmation email to customer ─────────────────
if ($email) {
    // Build items rows
    $itemsHtml = '';
    foreach ($cart_items as $item) {
        $iPrice = ($item['price_after_discount'] > 0) ? (float)$item['price_after_discount'] : (float)$item['price'];
        $iStitch = ($item['stitching'] == 1 && !empty($item['stitched_price'])) ? (float)$item['stitched_price'] : 0;
        $iTotal  = ($iPrice + $iStitch) * (int)$item['quantity'];
        $iName   = htmlspecialchars($item['name']);
        $iQty    = (int)$item['quantity'];
        $iSize   = $item['sizes']  ? ' | Size: ' . htmlspecialchars($item['sizes'])  : '';
        $iColor  = $item['colors'] ? ' | Color: ' . htmlspecialchars($item['colors']) : '';
        $iStitchLabel = $item['stitching'] == 1 ? ' | Stitched' : '';
        $itemsHtml .= '
        <tr>
          <td style="padding:10px 12px;border-bottom:1px solid #f0f0f0;font-size:13px;color:#333;">'
            . $iName . '<br><small style="color:#999;">' . $iSize . $iColor . $iStitchLabel . '</small></td>
          <td style="padding:10px 12px;border-bottom:1px solid #f0f0f0;text-align:center;font-size:13px;color:#555;">' . $iQty . '</td>
          <td style="padding:10px 12px;border-bottom:1px solid #f0f0f0;text-align:right;font-size:13px;color:#1a1a2e;font-weight:700;">Rs ' . number_format($iTotal) . '</td>
        </tr>';
    }

    $payLabel = $payment === 'stripe' ? 'Card Payment (Paid)' : ($payment === 'manual' ? 'Bank Transfer' : 'Cash on Delivery');
    $siteUrl  = $isLocal ? 'http://localhost/kapraa' : 'https://kapraa.pk';
    $logoUrl  = $siteUrl . '/assets/images/512.png';
    $custName = htmlspecialchars($name);
    $custAddr = htmlspecialchars("$address, $city" . ($state ? ", $state" : '') . ", $country");

    $orderHtml = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Order Confirmation</title>
</head>
<body style="margin:0;padding:0;background:#f0f0f0;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f0f0f0;padding:30px 0;">
<tr><td align="center">
<table width="580" cellpadding="0" cellspacing="0" border="0" style="max-width:580px;width:100%;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.1);">

  <!-- HEADER -->
  <tr>
    <td style="background:linear-gradient(135deg,#1a1a2e 0%,#0f3460 100%);padding:28px 32px;text-align:center;">
      <img src="{$logoUrl}" alt="Kapraa" height="50" style="max-width:180px;height:auto;display:inline-block;"><br>
      <p style="color:#f9ca24;margin:10px 0 0;font-size:13px;letter-spacing:1px;text-transform:uppercase;">Order Confirmation</p>
    </td>
  </tr>
  <tr><td style="background:linear-gradient(90deg,#f9ca24,#f0932b);height:5px;font-size:1px;">&nbsp;</td></tr>

  <!-- GREETING -->
  <tr>
    <td style="padding:28px 32px 0;">
      <p style="margin:0;font-size:16px;color:#333;">Hi <strong style="color:#1a1a2e;">{$custName}</strong>,</p>
      <p style="margin:10px 0 0;font-size:14px;color:#555;line-height:1.6;">
        Thank you for your order! We've received it and will process it shortly.<br>
        Here's a summary of what you ordered:
      </p>
    </td>
  </tr>

  <!-- ORDER NUMBER BOX -->
  <tr>
    <td style="padding:20px 32px;">
      <div style="background:#f8f9fa;border-left:4px solid #f0932b;border-radius:8px;padding:14px 18px;display:flex;justify-content:space-between;">
        <span style="font-size:13px;color:#888;">Order Number</span>
        <strong style="font-size:15px;color:#1a1a2e;letter-spacing:1px;">{$order_number}</strong>
      </div>
    </td>
  </tr>

  <!-- ITEMS TABLE -->
  <tr>
    <td style="padding:0 32px;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-radius:10px;overflow:hidden;border:1px solid #f0f0f0;">
        <tr style="background:#1a1a2e;">
          <th style="padding:10px 12px;text-align:left;font-size:12px;color:#f9ca24;font-weight:700;text-transform:uppercase;">Product</th>
          <th style="padding:10px 12px;text-align:center;font-size:12px;color:#f9ca24;font-weight:700;text-transform:uppercase;">Qty</th>
          <th style="padding:10px 12px;text-align:right;font-size:12px;color:#f9ca24;font-weight:700;text-transform:uppercase;">Price</th>
        </tr>
        {$itemsHtml}
        <tr>
          <td colspan="2" style="padding:10px 12px;text-align:right;font-size:13px;color:#888;">Shipping</td>
          <td style="padding:10px 12px;text-align:right;font-size:13px;color:#555;">Rs {$shipping}</td>
        </tr>
        <tr style="background:#f8f9fa;">
          <td colspan="2" style="padding:12px;text-align:right;font-size:15px;font-weight:800;color:#1a1a2e;">Total</td>
          <td style="padding:12px;text-align:right;font-size:16px;font-weight:900;color:#f0932b;">Rs {$total}</td>
        </tr>
      </table>
    </td>
  </tr>

  <!-- DELIVERY & PAYMENT -->
  <tr>
    <td style="padding:20px 32px 0;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td width="50%" style="vertical-align:top;padding-right:8px;">
            <div style="background:#f8f9fa;border-radius:10px;padding:14px;">
              <p style="margin:0 0 6px;font-size:11px;font-weight:700;color:#999;text-transform:uppercase;letter-spacing:.5px;">&#128205; Delivery Address</p>
              <p style="margin:0;font-size:13px;color:#333;line-height:1.5;">{$custAddr}</p>
            </div>
          </td>
          <td width="50%" style="vertical-align:top;padding-left:8px;">
            <div style="background:#f8f9fa;border-radius:10px;padding:14px;">
              <p style="margin:0 0 6px;font-size:11px;font-weight:700;color:#999;text-transform:uppercase;letter-spacing:.5px;">&#128179; Payment</p>
              <p style="margin:0;font-size:13px;color:#333;">{$payLabel}</p>
            </div>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <!-- CTA -->
  <tr>
    <td style="padding:24px 32px;text-align:center;">
      <a href="{$siteUrl}" style="display:inline-block;background:linear-gradient(135deg,#1a1a2e,#0f3460);color:#fff;text-decoration:none;padding:13px 36px;border-radius:30px;font-size:14px;font-weight:700;">
        Continue Shopping &rarr;
      </a>
    </td>
  </tr>

  <tr><td style="background:linear-gradient(90deg,#f9ca24,#f0932b);height:4px;font-size:1px;">&nbsp;</td></tr>

  <!-- FOOTER -->
  <tr>
    <td style="background:#f8f8f8;padding:18px 32px;text-align:center;">
      <p style="margin:0;font-size:12px;color:#aaa;">&copy; 2025 Kapraa &middot; Questions? Contact us at contact@kapraa.pk</p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
HTML;

    // Send via PHP mail() — server internal MTA (same method that works for OTP)
    $mh  = "From: Kapraa <contact@kapraa.pk>\r\n";
    $mh .= "Reply-To: contact@kapraa.pk\r\n";
    $mh .= "MIME-Version: 1.0\r\n";
    $mh .= "Content-Type: text/html; charset=UTF-8\r\n";
    $mh .= "X-Mailer: Kapraa/1.0";
    $mailSent = @mail($email, 'Order Confirmed: ' . $order_number . ' - Kapraa', $orderHtml, $mh);

    // Fallback: SMTP via Hostinger mail server
    if (!$mailSent) {
        $smtpHost = 'mail.kapraa.pk';
        $smtpPort = 587;
        $smtpUser = 'contact@kapraa.pk';
        $smtpPass = 'Kapraa@2026';
        $sock = @fsockopen('tcp://' . $smtpHost, $smtpPort, $en, $es, 15);
        if ($sock) {
            $rd = function() use ($sock) {
                $r = '';
                while ($l = fgets($sock, 512)) { $r .= $l; if (isset($l[3]) && $l[3] === ' ') break; }
                return $r;
            };
            $cm = function($c) use ($sock, $rd) { fwrite($sock, $c . "\r\n"); return $rd(); };
            $rd();
            $cm('EHLO ' . $smtpHost);
            $cm('STARTTLS');
            stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $cm('EHLO ' . $smtpHost);
            $cm('AUTH LOGIN');
            $cm(base64_encode($smtpUser));
            $ar = $cm(base64_encode($smtpPass));
            if (strpos($ar, '235') !== false) {
                $cm('MAIL FROM:<' . $smtpUser . '>');
                $cm('RCPT TO:<' . $email . '>');
                $cm('DATA');
                $msg  = "Date: " . date('r') . "\r\n";
                $msg .= "From: Kapraa <{$smtpUser}>\r\n";
                $msg .= "To: {$name} <{$email}>\r\n";
                $msg .= "Subject: =?UTF-8?B?" . base64_encode('Order Confirmed: ' . $order_number . ' — Kapraa') . "?=\r\n";
                $msg .= "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n";
                $msg .= $orderHtml . "\r\n.";
                $cm($msg);
            }
            $cm('QUIT');
            fclose($sock);
        }
    }
}
// ─────────────────────────────────────────────────────────────

echo json_encode([
    'success'      => true,
    'message'      => 'Order placed successfully!',
    'order_id'     => $order_id,
    'order_number' => $order_number,
    'total'        => $total,
]);
