<?php
// ============================================================
// payment/jazzcash.php — JazzCash Hosted Checkout v1.1
// Page redirect to official JazzCash sandbox/live URL
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
date_default_timezone_set('Asia/Karachi');

// ── Validate session ─────────────────────────────────────────
if (empty($_SESSION['pending_order_id']) || empty($_SESSION['pending_order_number'])) {
    header('Location: ' . SITE_URL . '/checkout.php');
    exit;
}

$orderId      = (int)$_SESSION['pending_order_id'];
$orderNumber  = $_SESSION['pending_order_number'];
$amountPKR    = (float)$_SESSION['pending_order_amount'];
$amountPaisas = (string)(int)round($amountPKR * 100);

$txnDateTime    = date('YmdHis');
$expiryDateTime = date('YmdHis', strtotime('+60 minutes'));
$txnRefNo       = 'T' . date('YmdHis') . str_pad(mt_rand(10000, 99999), 5, '0', STR_PAD_LEFT);
$billRef        = 'TH' . $orderId;
$returnURL      = SITE_URL . '/payment/jazzcash_return.php';

// ── Save txnRef in DB ─────────────────────────────────────────
$_db = paymentDB();
if (!$_db->connect_errno) {
    $_db->set_charset('utf8mb4');
    $safeRef   = $_db->real_escape_string($txnRefNo);
    $safeOrder = $_db->real_escape_string($orderNumber);
    $_db->query("UPDATE orders SET jc_txn_ref='$safeRef' WHERE order_number='$safeOrder'");
    $_db->close();
}
$_SESSION['jc_txnref_map'][$txnRefNo] = $orderNumber;

// ── Demo mode (JazzCash sandbox services not activated on MC536933) ──
if (defined('JAZZCASH_DEMO') && JAZZCASH_DEMO) {
    $_SESSION['jc_demo_txnref'] = $txnRefNo;
    $_SESSION['jc_demo_order']  = $orderNumber;
    $_SESSION['jc_demo_amount'] = $amountPKR;
    header('Location: ' . SITE_URL . '/payment/jazzcash_demo.php');
    exit;
}

// ── Build params ──────────────────────────────────────────────
$params = [
    'pp_Version'           => '1.1',
    'pp_TxnType'           => '',            // empty = JazzCash shows card + wallet tabs
    'pp_Language'          => 'EN',
    'pp_MerchantID'        => JAZZCASH_MERCHANT_ID,
    'pp_SubMerchantID'     => '',
    'pp_Password'          => JAZZCASH_PASSWORD,
    'pp_BillReference'     => $billRef,
    'pp_Amount'            => $amountPaisas,
    'pp_TxnCurrency'       => 'PKR',
    'pp_TxnDateTime'       => $txnDateTime,
    'pp_TxnExpiryDateTime' => $expiryDateTime,
    'pp_TxnRefNo'          => $txnRefNo,
    'pp_Description'       => 'Kapraa Order',
    'pp_ReturnURL'         => $returnURL,
    'pp_SecureHash'        => '',
    'ppmpf_1'              => '1',
    'ppmpf_2'              => '2',
    'ppmpf_3'              => '3',
    'ppmpf_4'              => '4',
    'ppmpf_5'              => '5',
];

// ── HMAC-SHA256 Hash ──────────────────────────────────────────
function jazzcashHash(array $params, string $hashKey): string {
    ksort($params);
    $str = $hashKey;
    foreach ($params as $k => $v) {
        if ($k !== 'pp_SecureHash' && $v !== '') {
            $str .= '&' . $v;
        }
    }
    return strtoupper(hash_hmac('sha256', $str, $hashKey));
}

$params['pp_SecureHash'] = jazzcashHash($params, JAZZCASH_HASH_KEY);
$jcUrl = JAZZCASH_SANDBOX ? JAZZCASH_URL_SANDBOX : JAZZCASH_URL_LIVE;

error_log("[JazzCash SEND] order=$orderNumber | txnRef=$txnRefNo | amount=$amountPaisas | url=$jcUrl");
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Redirecting to JazzCash…</title>
  <style>
    *{margin:0;padding:0;box-sizing:border-box;}
    body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f5f5f5;}
    .box{text-align:center;background:#fff;padding:40px 32px;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.1);max-width:400px;width:90%;}
    .jc-logo{width:56px;height:56px;background:#cc0000;border-radius:50%;margin:0 auto 12px;display:flex;align-items:center;justify-content:center;}
    h3{color:#cc0000;margin:0 0 6px;font-size:1.1rem;}
    p{color:#666;font-size:13px;margin-bottom:4px;}
    .spinner{width:44px;height:44px;border:4px solid #f0f0f0;border-top-color:#cc0000;border-radius:50%;animation:spin 0.8s linear infinite;margin:16px auto;}
    @keyframes spin{to{transform:rotate(360deg);}}
    .info{margin-top:16px;background:#e8f5e9;border:1px solid #c8e6c9;border-radius:8px;padding:12px 14px;font-size:12px;color:#1b5e20;text-align:left;line-height:1.8;}
    .info strong{display:block;margin-bottom:4px;font-size:13px;}
    .info code{background:#c8e6c9;padding:1px 5px;border-radius:3px;font-family:monospace;font-size:12px;}
    form{display:none;}
  </style>
</head>
<body>
  <div class="box">
    <div class="jc-logo">
      <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
    </div>
    <h3>Redirecting to JazzCash…</h3>
    <div class="spinner"></div>
    <p>You will be taken to the official JazzCash payment page.</p>

    <div class="info">
      <strong>🧪 Sandbox — IMPORTANT — follow exactly:</strong>
      1. On the JazzCash page, look for <strong>"Card"</strong> or <strong>"Debit/Credit Card"</strong> tab — click it<br>
      2. Do <strong>NOT</strong> enter a mobile number<br>
      3. Enter the test card below:<br><br>
      Card No: <code>5123450000000008</code><br>
      Expiry: <code>01/39</code> &nbsp;&nbsp; CVV: <code>100</code><br>
      Name: <code>Test User</code>
    </div>

    <form id="jcForm" method="POST" action="<?= htmlspecialchars($jcUrl) ?>">
      <?php foreach ($params as $key => $val): ?>
        <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($val) ?>">
      <?php endforeach; ?>
    </form>
  </div>
  <script>
    setTimeout(function(){ document.getElementById('jcForm').submit(); }, 1800);
  </script>
</body>
</html>
