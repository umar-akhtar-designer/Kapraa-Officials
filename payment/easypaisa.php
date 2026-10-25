<?php
// ============================================================
// payment/easypaisa.php
// Builds signed EasyPaisa hosted payment form and auto-submits
// ============================================================
session_start();
require_once __DIR__ . '/config.php';

// ── Check if EasyPaisa is enabled ──────────────────────────
if (!EASYPAISA_ENABLED) {
    header('Location: ' . SITE_URL . '/checkout.php?payment_error=easypaisa_coming_soon');
    exit;
}

if (empty($_SESSION['pending_order_id']) || empty($_SESSION['pending_order_number'])) {
    header('Location: ' . SITE_URL . '/checkout.php');
    exit;
}

$orderId     = (int)$_SESSION['pending_order_id'];
$orderNumber = $_SESSION['pending_order_number'];
$amountPKR   = number_format((float)$_SESSION['pending_order_amount'], 2, '.', '');
$returnURL   = SITE_URL . '/payment/easypaisa_return.php';
$expiryDate  = date('Ymd', strtotime('+1 day')) . '000000';

// ── Build EasyPaisa params ──────────────────────────────────
$params = [
    'storeId'        => EASYPAISA_STORE_ID,
    'orderId'        => $orderNumber,
    'transactionAmount' => $amountPKR,
    'mobileAccountNo'=> '',          // empty for hosted (user enters on EasyPaisa page)
    'emailAddress'   => $_SESSION['pending_order_email'] ?? '',
    'transactionType'=> 'MA',        // MA = Mobile Account
    'tokenExpiry'    => $expiryDate,
    'bankIdentificationNumber' => '',
    'encryptedHashRequest' => '',    // filled below
    'postBackURL'    => $returnURL,
    'paymentMethod'  => 'InitialRequest',
];

// ── EasyPaisa SHA256 hash ────────────────────────────────────
function easypaisaHash(array $params, string $hashKey): string {
    // EasyPaisa hash: sort fields alphabetically, concatenate values, then SHA256
    $fields = [
        'amount'    => $params['transactionAmount'],
        'orderRefNum' => $params['orderId'],
        'paymentMethod' => $params['paymentMethod'],
        'postBackURL' => $params['postBackURL'],
        'storeId'   => $params['storeId'],
        'timeStamp' => $params['tokenExpiry'],
    ];
    ksort($fields);
    $hashStr = implode('&', array_values($fields));
    return strtoupper(hash_hmac('sha256', $hashStr, $hashKey));
}
$params['encryptedHashRequest'] = easypaisaHash($params, EASYPAISA_HASH_KEY);

$epUrl = EASYPAISA_SANDBOX ? EASYPAISA_URL_SANDBOX : EASYPAISA_URL_LIVE;
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Redirecting to EasyPaisa…</title>
  <style>
    body { font-family: sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; background:#f5f5f5; margin:0; }
    .box { text-align:center; background:#fff; padding:40px; border-radius:16px; box-shadow:0 4px 24px rgba(0,0,0,.1); }
    p { color:#666; }
    .spinner { width:40px; height:40px; border:4px solid #f0f0f0; border-top-color:#57a227; border-radius:50%; animation:spin 0.8s linear infinite; margin:16px auto; }
    @keyframes spin { to { transform:rotate(360deg); } }
  </style>
</head>
<body>
  <div class="box">
    <div style="width:48px;height:48px;border-radius:50%;background:#4caf50;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;"><svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>
    <h3>Redirecting to EasyPaisa…</h3>
    <div class="spinner"></div>
    <p>Please wait. Do not press back.</p>
    <form id="epForm" method="POST" action="<?php echo htmlspecialchars($epUrl); ?>">
      <input type="hidden" name="storeId"       value="<?php echo htmlspecialchars($params['storeId']); ?>">
      <input type="hidden" name="orderId"        value="<?php echo htmlspecialchars($params['orderId']); ?>">
      <input type="hidden" name="transactionAmount" value="<?php echo htmlspecialchars($params['transactionAmount']); ?>">
      <input type="hidden" name="mobileAccountNo"   value="">
      <input type="hidden" name="emailAddress"   value="<?php echo htmlspecialchars($params['emailAddress']); ?>">
      <input type="hidden" name="transactionType"   value="MA">
      <input type="hidden" name="tokenExpiry"    value="<?php echo htmlspecialchars($params['tokenExpiry']); ?>">
      <input type="hidden" name="bankIdentificationNumber" value="">
      <input type="hidden" name="encryptedHashRequest" value="<?php echo htmlspecialchars($params['encryptedHashRequest']); ?>">
      <input type="hidden" name="postBackURL"    value="<?php echo htmlspecialchars($params['postBackURL']); ?>">
      <input type="hidden" name="paymentMethod"  value="InitialRequest">
    </form>
  </div>
  <script>document.getElementById('epForm').submit();</script>
</body>
</html>
