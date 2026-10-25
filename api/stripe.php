<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ── Replace with your Stripe TEST secret key from stripe.com/dashboard ──
$STRIPE_SECRET = 'sk_test_51RRWc7BHXymeMjlSZM7OfL3HZR0sbmnTJfuOiw4ydY63o4FRntpxFhZ5Eb3pXgzO7WPnPhrrExtgF4qn3X8MOY1W00VAQMsws8';

$in     = json_decode(file_get_contents('php://input'), true) ?? [];
$amtPKR = (int)($in['amount_pkr'] ?? 0);
if ($amtPKR < 1) { echo json_encode(['success'=>false,'error'=>'Invalid amount']); exit; }

// Convert PKR → USD cents (approx 1 USD = 280 PKR) for Stripe test
$amtUSD = max(50, (int)round(($amtPKR / 280) * 100)); // minimum 50 cents

$ch = curl_init('https://api.stripe.com/v1/payment_intents');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ["Authorization: Bearer $STRIPE_SECRET"],
    CURLOPT_POSTFIELDS     => http_build_query([
        'amount'   => $amtUSD,
        'currency' => 'usd',
        'automatic_payment_methods[enabled]' => 'true',
    ]),
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT        => 15,
]);
$res  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($res, true);
if ($code === 200 && isset($data['client_secret'])) {
    echo json_encode(['success'=>true, 'client_secret'=>$data['client_secret'], 'amount_usd'=>$amtUSD]);
} else {
    echo json_encode(['success'=>false, 'error'=>$data['error']['message'] ?? 'Stripe error']);
}
