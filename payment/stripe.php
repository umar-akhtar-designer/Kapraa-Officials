<?php
// ============================================================
// payment/stripe.php
// Creates a Stripe Checkout Session via curl, redirects user
// No SDK needed — pure curl API call
// ============================================================
session_start();
require_once __DIR__ . '/config.php';

if (empty($_SESSION['pending_order_id']) || empty($_SESSION['pending_order_number'])) {
    header('Location: ' . SITE_URL . '/checkout.php');
    exit;
}

$orderNumber = $_SESSION['pending_order_number'];
$amountPKR   = (float)$_SESSION['pending_order_amount'];

// PKR is a DECIMAL currency (100 paisa = ₨1) — Stripe needs amount in paisa.
// Stripe minimum: $0.50 USD ≈ ₨150 PKR → 15000 paisa minimum.
$amountStripe = (int)round($amountPKR * 100); // convert PKR → paisa for Stripe

if ($amountPKR < 150) {
    header('Location: ' . SITE_URL . '/checkout.php?payment_error=' . urlencode('Minimum order for card payment is Rs 150. Please add more items or use Cash on Delivery.'));
    exit;
}

$successURL  = SITE_URL . '/payment/stripe_return.php?session_id={CHECKOUT_SESSION_ID}&order=' . urlencode($orderNumber);
$cancelURL   = SITE_URL . '/checkout.php?payment_error=stripe_cancelled';

// ── Create Stripe Checkout Session via curl ─────────────────
$postData = http_build_query([
    'payment_method_types[0]'          => 'card',
    'line_items[0][price_data][currency]' => 'pkr',
    'line_items[0][price_data][unit_amount]' => $amountStripe,
    'line_items[0][price_data][product_data][name]' => 'Kapraa Order ' . $orderNumber,
    'line_items[0][price_data][product_data][description]' => 'Pakistani clothing order from Kapraa',
    'line_items[0][quantity]'          => 1,
    'mode'                             => 'payment',
    'success_url'                      => $successURL,
    'cancel_url'                       => $cancelURL,
    'metadata[order_number]'           => $orderNumber,
    'metadata[order_id]'               => $_SESSION['pending_order_id'],
]);

$isLocalEnv = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
            || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false
            || strpos($_SERVER['HTTP_HOST'] ?? '', 'ngrok') !== false);

$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postData,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD        => STRIPE_SECRET_KEY . ':',
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_SSL_VERIFYPEER => !$isLocalEnv,
    CURLOPT_SSL_VERIFYHOST => $isLocalEnv ? 0 : 2,
    CURLOPT_TIMEOUT        => 30,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    $err = json_decode($response, true);
    $msg = $err['error']['message'] ?? 'Stripe error. Try another payment method.';
    header('Location: ' . SITE_URL . '/checkout.php?payment_error=' . urlencode($msg));
    exit;
}

$session = json_decode($response, true);
$_SESSION['stripe_session_id'] = $session['id'];

// Redirect user to Stripe Checkout page
header('Location: ' . $session['url']);
exit;
