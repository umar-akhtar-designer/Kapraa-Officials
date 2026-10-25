<?php
// ============================================================
// payment/config.example.php
// COPY THIS FILE TO config.php and fill in your credentials
// DO NOT commit config.php — it is in .gitignore
// ============================================================

// ── SITE ────────────────────────────────────────────────────
$is_local = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
          || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false);

// ── NGROK OVERRIDE (for local JazzCash/Stripe testing) ──────
// Run: ngrok http 80 → paste URL here e.g. https://abc123.ngrok-free.app/kapraa
define('NGROK_URL', '');

define('SITE_URL', ($is_local && NGROK_URL !== '')
    ? rtrim(NGROK_URL, '/')
    : ($is_local
        ? 'http://localhost/kapraa'
        : 'https://yourdomain.com'));

// ── STRIPE ──────────────────────────────────────────────────
define('STRIPE_PUBLIC_KEY', 'pk_test_YOUR_PUBLIC_KEY_HERE');
define('STRIPE_SECRET_KEY', 'sk_test_YOUR_SECRET_KEY_HERE');
define('STRIPE_SANDBOX',    true);

// ── JAZZCASH ────────────────────────────────────────────────
define('JAZZCASH_MERCHANT_ID', 'YOUR_MERCHANT_ID');
define('JAZZCASH_PASSWORD',    'YOUR_PASSWORD');
define('JAZZCASH_HASH_KEY',    'YOUR_HASH_KEY');
define('JAZZCASH_SANDBOX',     true);
define('JAZZCASH_URL_SANDBOX', 'https://sandbox.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/');
define('JAZZCASH_URL_LIVE',    'https://payments.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/');

// ── EASYPAISA ─────────────────────────────────────────────
define('EASYPAISA_ENABLED',    false);
define('EASYPAISA_STORE_ID',   '');
define('EASYPAISA_HASH_KEY',   '');
define('EASYPAISA_SANDBOX',    true);
define('EASYPAISA_URL_SANDBOX','https://easypay1.easypaisa.com.pk/easypay/');
define('EASYPAISA_URL_LIVE',   'https://easypay.easypaisa.com.pk/easypay/');
