<?php
// ============================================================
// payment/jazzcash_wallet_process.php — JazzCash REST API Call
// Server-side MWALLET transaction using DoMWalletTransaction API
// Works with sandbox test credentials:
//   Mobile: 03123456789  |  CNIC: 345678  → Successful
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
date_default_timezone_set('Asia/Karachi');

// ── Guard: must have session params ──────────────────────────
if (empty($_SESSION['jc_params'])) {
    header('Location: ' . SITE_URL . '/checkout.php');
    exit;
}

$p    = $_SESSION['jc_params'];
$base = SITE_URL . '/payment/jazzcash_wallet.php';

// ── Collect + validate inputs ─────────────────────────────────
$mobile = preg_replace('/\D/', '', $_POST['mobile'] ?? '');
$cnic   = preg_replace('/\D/', '', $_POST['cnic']   ?? '');

if (strlen($mobile) !== 11 || !str_starts_with($mobile, '03')) {
    header('Location: ' . $base . '?err=invalid_mobile');
    exit;
}
if (strlen($cnic) !== 6) {
    header('Location: ' . $base . '?err=invalid_cnic');
    exit;
}

// ── Build REST API params ─────────────────────────────────────
$params = [
    'pp_Version'           => '1.1',
    'pp_TxnType'           => 'MWALLET',
    'pp_Language'          => 'EN',
    'pp_MerchantID'        => JAZZCASH_MERCHANT_ID,
    'pp_SubMerchantID'     => '',
    'pp_Password'          => JAZZCASH_PASSWORD,
    'pp_BillReference'     => $p['billRef'],
    'pp_Amount'            => $p['amountPaisas'],
    'pp_TxnCurrency'       => 'PKR',
    'pp_TxnDateTime'       => $p['txnDateTime'],
    'pp_TxnExpiryDateTime' => $p['expiryDateTime'],
    'pp_TxnRefNo'          => $p['txnRefNo'],
    'pp_Description'       => 'Kapraa Order',
    'pp_MobileNumber'      => $mobile,
    'pp_CNIC'              => $cnic,
    'pp_SecureHash'        => '',
];

// ── HMAC-SHA256 Hash ──────────────────────────────────────────
function jcHash(array $params, string $key): string {
    ksort($params);
    $str = $key;
    foreach ($params as $k => $v) {
        if ($k !== 'pp_SecureHash' && $v !== '') {
            $str .= '&' . $v;
        }
    }
    return strtoupper(hash_hmac('sha256', $str, $key));
}

$params['pp_SecureHash'] = jcHash($params, JAZZCASH_HASH_KEY);

// ── REST API endpoint ─────────────────────────────────────────
$apiUrl = JAZZCASH_SANDBOX
    ? 'https://sandbox.jazzcash.com.pk/ApplicationAPI/API/2.0/Purchase/DoMWalletTransaction'
    : 'https://payments.jazzcash.com.pk/ApplicationAPI/API/2.0/Purchase/DoMWalletTransaction';

error_log("[JazzCash REST] Calling API: $apiUrl");
error_log("[JazzCash REST] Params: " . json_encode($params));

// ── cURL POST (JSON body) ─────────────────────────────────────
$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($params),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => false,   // sandbox SSL may use self-signed cert
]);

$raw      = curl_exec($ch);
$curlErr  = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

error_log("[JazzCash REST] HTTP=$httpCode | Response: $raw | curlErr=$curlErr");

if ($curlErr || !$raw) {
    error_log("[JazzCash REST] cURL failed: $curlErr");
    header('Location: ' . $base . '?err=jc_fail&msg=' . urlencode('Could not reach JazzCash. Please try again.'));
    exit;
}

$resp = json_decode($raw, true);
if (!$resp) {
    error_log("[JazzCash REST] JSON decode failed. Raw: $raw");
    header('Location: ' . $base . '?err=jc_fail&msg=' . urlencode('Invalid response from JazzCash.'));
    exit;
}

$responseCode = trim($resp['pp_ResponseCode'] ?? '');
$responseMsg  = trim($resp['pp_ResponseMessage'] ?? 'Transaction failed.');
$txnRef       = trim($resp['pp_TxnRefNo'] ?? $p['txnRefNo']);

error_log("[JazzCash REST] Code=$responseCode | Msg=$responseMsg | TxnRef=$txnRef");

// ── SUCCESS ───────────────────────────────────────────────────
if ($responseCode === '000') {
    // Mark order paid in DB
    $_db = paymentDB();
    if (!$_db->connect_errno) {
        $_db->set_charset('utf8mb4');
        $safeOrder = $_db->real_escape_string($p['orderNumber']);
        $safeRef   = $_db->real_escape_string($txnRef);
        $_db->query("UPDATE orders SET payment_status='paid', payment_gateway_ref='$safeRef' WHERE order_number='$safeOrder'");

        // Clear cart
        $userId = $_SESSION['WEBSITE-LOGINSS'] ?? $_SESSION['user_id'] ?? '';
        if ($userId) {
            $safeUser = $_db->real_escape_string($userId);
            $_db->query("DELETE FROM cart WHERE user_id='$safeUser'");
        }
        $_db->close();
    }

    // Clear session
    $_SESSION['cart'] = [];
    unset(
        $_SESSION['pending_order_id'],
        $_SESSION['pending_order_number'],
        $_SESSION['pending_order_amount'],
        $_SESSION['pending_order_email'],
        $_SESSION['jc_txnref_map'],
        $_SESSION['jc_params']
    );

    header('Location: ' . SITE_URL . '/order-confirmation.php?order=' . urlencode($p['orderNumber']) . '&paid=1');
    exit;
}

// ── FAILED ────────────────────────────────────────────────────
// Update order as failed
$_db = paymentDB();
if (!$_db->connect_errno) {
    $_db->set_charset('utf8mb4');
    $safeOrder = $_db->real_escape_string($p['orderNumber']);
    $_db->query("UPDATE orders SET payment_status='failed', payment_gateway_ref='FAIL_$responseCode' WHERE order_number='$safeOrder'");
    $_db->close();
}

header('Location: ' . $base . '?err=jc_fail&msg=' . urlencode($responseMsg));
exit;
