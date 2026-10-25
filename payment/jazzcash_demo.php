<?php
// ============================================================
// payment/jazzcash_demo.php — JazzCash Demo Payment UI
// Simulates real JazzCash flow for FYP demo / testing
// No real transaction — marks order as paid locally
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';

// Guard: must arrive via jazzcash.php (session vars set)
if (empty($_SESSION['jc_demo_txnref']) || empty($_SESSION['jc_demo_order'])) {
    header('Location: ' . SITE_URL . '/checkout.php');
    exit;
}

$txnRef  = $_SESSION['jc_demo_txnref'];
$order   = $_SESSION['jc_demo_order'];
$amount  = number_format((float)$_SESSION['jc_demo_amount'], 2);

// Generate a fake 6-digit OTP and store in session
if (empty($_SESSION['jc_demo_otp'])) {
    $_SESSION['jc_demo_otp'] = str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
}
$demoOtp = $_SESSION['jc_demo_otp'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>JazzCash — Secure Payment</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      background: linear-gradient(135deg, #c0392b 0%, #e74c3c 50%, #c0392b 100%);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    /* ── Header bar ── */
    .jc-header {
      width: 100%;
      max-width: 420px;
      background: #a93226;
      border-radius: 16px 16px 0 0;
      padding: 16px 20px;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .jc-logo-wrap {
      width: 40px; height: 40px;
      background: #fff;
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
    }
    .jc-logo-wrap svg { display: block; }
    .jc-header-text h1 { color: #fff; font-size: 16px; font-weight: 700; }
    .jc-header-text p  { color: rgba(255,255,255,.75); font-size: 11px; }

    /* ── Card ── */
    .jc-card {
      width: 100%;
      max-width: 420px;
      background: #fff;
      border-radius: 0 0 16px 16px;
      padding: 28px 24px 24px;
      box-shadow: 0 8px 40px rgba(0,0,0,.25);
    }

    /* ── Amount banner ── */
    .amount-banner {
      background: #fff5f5;
      border: 1px solid #fddede;
      border-radius: 10px;
      padding: 14px 16px;
      margin-bottom: 22px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .amount-banner .label { font-size: 12px; color: #888; }
    .amount-banner .value { font-size: 22px; font-weight: 700; color: #c0392b; }
    .amount-banner .ref   { font-size: 10px; color: #bbb; margin-top: 2px; }

    /* ── Steps ── */
    .steps {
      display: flex;
      gap: 0;
      margin-bottom: 24px;
    }
    .step {
      flex: 1;
      text-align: center;
      position: relative;
    }
    .step::after {
      content: '';
      position: absolute;
      top: 14px; left: 50%; right: -50%;
      height: 2px;
      background: #eee;
      z-index: 0;
    }
    .step:last-child::after { display: none; }
    .step-dot {
      width: 28px; height: 28px;
      border-radius: 50%;
      background: #eee;
      color: #aaa;
      font-size: 12px; font-weight: 700;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 4px;
      position: relative; z-index: 1;
      transition: all .3s;
    }
    .step.active .step-dot  { background: #c0392b; color: #fff; }
    .step.done .step-dot    { background: #27ae60; color: #fff; }
    .step.done::after       { background: #27ae60; }
    .step-label { font-size: 10px; color: #aaa; }
    .step.active .step-label { color: #c0392b; font-weight: 600; }
    .step.done .step-label   { color: #27ae60; }

    /* ── Form ── */
    label { display: block; font-size: 12px; color: #555; margin-bottom: 5px; font-weight: 600; }
    .input-wrap { position: relative; margin-bottom: 16px; }
    .input-wrap input {
      width: 100%;
      padding: 12px 14px 12px 44px;
      border: 1.5px solid #ddd;
      border-radius: 8px;
      font-size: 15px;
      color: #222;
      outline: none;
      transition: border-color .2s;
    }
    .input-wrap input:focus { border-color: #c0392b; }
    .input-wrap .icon {
      position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
      color: #aaa; font-size: 18px;
    }
    .input-wrap.otp-wrap input {
      letter-spacing: 6px;
      font-size: 20px;
      font-weight: 700;
      text-align: center;
      padding: 12px;
    }

    /* ── OTP info box ── */
    .otp-info {
      background: #f0faf4;
      border: 1px solid #c3e6cb;
      border-radius: 8px;
      padding: 10px 14px;
      font-size: 12px;
      color: #155724;
      margin-bottom: 16px;
      line-height: 1.6;
    }
    .otp-info strong { display: block; margin-bottom: 2px; }
    .otp-code {
      font-size: 26px;
      font-weight: 800;
      letter-spacing: 8px;
      color: #c0392b;
      display: block;
      text-align: center;
      margin: 6px 0 2px;
    }

    /* ── Buttons ── */
    .btn-pay {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, #c0392b, #e74c3c);
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      transition: opacity .2s;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-pay:hover { opacity: .9; }
    .btn-pay:disabled { opacity: .6; cursor: not-allowed; }
    .btn-cancel {
      width: 100%;
      padding: 10px;
      background: transparent;
      color: #888;
      border: 1.5px solid #ddd;
      border-radius: 8px;
      font-size: 13px;
      cursor: pointer;
      margin-top: 10px;
    }
    .btn-cancel:hover { border-color: #c0392b; color: #c0392b; }

    /* ── Processing overlay ── */
    #processingOverlay {
      display: none;
      position: fixed; inset: 0;
      background: rgba(0,0,0,.5);
      z-index: 100;
      align-items: center; justify-content: center;
    }
    #processingOverlay.show { display: flex; }
    .processing-box {
      background: #fff;
      border-radius: 16px;
      padding: 40px 32px;
      text-align: center;
      max-width: 300px;
      width: 90%;
    }
    .spinner {
      width: 52px; height: 52px;
      border: 5px solid #fdd;
      border-top-color: #c0392b;
      border-radius: 50%;
      animation: spin .8s linear infinite;
      margin: 0 auto 16px;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .processing-box h3 { color: #c0392b; font-size: 16px; margin-bottom: 6px; }
    .processing-box p  { color: #888; font-size: 13px; }

    /* ── Success overlay ── */
    #successOverlay {
      display: none;
      position: fixed; inset: 0;
      background: rgba(0,0,0,.5);
      z-index: 100;
      align-items: center; justify-content: center;
    }
    #successOverlay.show { display: flex; }
    .success-box {
      background: #fff;
      border-radius: 16px;
      padding: 40px 32px;
      text-align: center;
      max-width: 300px;
      width: 90%;
    }
    .check-circle {
      width: 64px; height: 64px;
      background: #27ae60;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 16px;
    }
    .success-box h3 { color: #27ae60; font-size: 18px; margin-bottom: 6px; }
    .success-box p  { color: #888; font-size: 13px; }

    /* ── Footer ── */
    .jc-footer {
      margin-top: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      color: rgba(255,255,255,.7);
      font-size: 11px;
    }

    /* Panel switching */
    .panel { display: none; }
    .panel.active { display: block; }
  </style>
</head>
<body>

  <!-- Header -->
  <div class="jc-header">
    <div class="jc-logo-wrap">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
        <circle cx="12" cy="12" r="11" fill="#c0392b"/>
        <text x="12" y="17" text-anchor="middle" fill="white" font-size="10" font-weight="bold" font-family="Arial">JC</text>
      </svg>
    </div>
    <div class="jc-header-text">
      <h1>JazzCash</h1>
      <p>Secure Mobile Payment Gateway</p>
    </div>
  </div>

  <!-- Card -->
  <div class="jc-card">

    <!-- Amount -->
    <div class="amount-banner">
      <div>
        <div class="label">Amount to Pay</div>
        <div class="value">PKR <?= htmlspecialchars($amount) ?></div>
        <div class="ref">Order: <?= htmlspecialchars($order) ?></div>
      </div>
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#c0392b" stroke-width="1.5"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
    </div>

    <!-- Steps -->
    <div class="steps" id="stepsBar">
      <div class="step active" id="s1">
        <div class="step-dot">1</div>
        <div class="step-label">Mobile</div>
      </div>
      <div class="step" id="s2">
        <div class="step-dot">2</div>
        <div class="step-label">OTP</div>
      </div>
      <div class="step" id="s3">
        <div class="step-dot">✓</div>
        <div class="step-label">Done</div>
      </div>
    </div>

    <!-- Panel 1: Mobile Number -->
    <div class="panel active" id="panel1">
      <label for="mobileNum">JazzCash Registered Mobile Number</label>
      <div class="input-wrap">
        <span class="icon">📱</span>
        <input type="tel" id="mobileNum" placeholder="03XX-XXXXXXX" maxlength="13" oninput="formatMobile(this)">
      </div>
      <button class="btn-pay" onclick="sendOtp()">
        <span>Send OTP</span>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </button>
      <button class="btn-cancel" onclick="cancelPayment()">Cancel</button>
    </div>

    <!-- Panel 2: OTP -->
    <div class="panel" id="panel2">
      <div class="otp-info">
        <strong>📲 OTP sent to your JazzCash number</strong>
        Your one-time password is:
        <span class="otp-code" id="otpDisplay"><?= htmlspecialchars($demoOtp) ?></span>
        <small style="color:#888;display:block;text-align:center;">Valid for 3 minutes</small>
      </div>
      <label for="otpInput">Enter OTP</label>
      <div class="input-wrap otp-wrap">
        <input type="text" id="otpInput" placeholder="— — — — — —" maxlength="6" inputmode="numeric">
      </div>
      <div id="otpError" style="color:#c0392b;font-size:12px;margin:-10px 0 12px;display:none;">
        ❌ Incorrect OTP. Please check and try again.
      </div>
      <button class="btn-pay" onclick="verifyOtp()">
        <span>Confirm Payment</span>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
      </button>
      <button class="btn-cancel" onclick="backToMobile()">← Change Number</button>
    </div>

  </div>

  <!-- Footer -->
  <div class="jc-footer">
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
    256-bit SSL Encrypted · Powered by JazzCash
  </div>

  <!-- Processing Overlay -->
  <div id="processingOverlay">
    <div class="processing-box">
      <div class="spinner"></div>
      <h3>Processing Payment…</h3>
      <p>Please do not press back or refresh.<br>This may take a few seconds.</p>
    </div>
  </div>

  <!-- Success Overlay -->
  <div id="successOverlay">
    <div class="success-box">
      <div class="check-circle">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
      </div>
      <h3>Payment Successful!</h3>
      <p>Your order has been confirmed.<br>Redirecting…</p>
    </div>
  </div>

  <!-- Hidden form to POST success to return handler -->
  <form id="successForm" method="POST" action="<?= htmlspecialchars(SITE_URL) ?>/payment/jazzcash_demo_return.php" style="display:none;">
    <input type="hidden" name="txnRef"  value="<?= htmlspecialchars($txnRef) ?>">
    <input type="hidden" name="order"   value="<?= htmlspecialchars($order) ?>">
    <input type="hidden" name="action"  value="success">
  </form>

<script>
  const CORRECT_OTP = '<?= $demoOtp ?>';

  function formatMobile(input) {
    let v = input.value.replace(/\D/g, '');
    if (v.length > 4) v = v.slice(0,4) + '-' + v.slice(4,11);
    input.value = v;
  }

  function sendOtp() {
    const num = document.getElementById('mobileNum').value.replace(/\D/g,'');
    if (num.length < 10) {
      alert('Please enter a valid mobile number (e.g. 0301-2345678)');
      return;
    }
    // Animate step transition
    document.getElementById('s1').classList.remove('active');
    document.getElementById('s1').classList.add('done');
    document.getElementById('s2').classList.add('active');
    document.getElementById('panel1').classList.remove('active');
    document.getElementById('panel2').classList.add('active');
  }

  function backToMobile() {
    document.getElementById('s1').classList.add('active');
    document.getElementById('s1').classList.remove('done');
    document.getElementById('s2').classList.remove('active');
    document.getElementById('panel1').classList.add('active');
    document.getElementById('panel2').classList.remove('active');
    document.getElementById('otpError').style.display = 'none';
    document.getElementById('otpInput').value = '';
  }

  function verifyOtp() {
    const entered = document.getElementById('otpInput').value.trim();
    const errEl   = document.getElementById('otpError');

    if (entered !== CORRECT_OTP) {
      errEl.style.display = 'block';
      document.getElementById('otpInput').value = '';
      document.getElementById('otpInput').focus();
      return;
    }
    errEl.style.display = 'none';

    // Show processing
    document.getElementById('processingOverlay').classList.add('show');

    // After 2.5s show success, then submit to complete order
    setTimeout(function() {
      document.getElementById('processingOverlay').classList.remove('show');
      document.getElementById('successOverlay').classList.add('show');
      setTimeout(function() {
        document.getElementById('successForm').submit();
      }, 1800);
    }, 2500);
  }

  function cancelPayment() {
    if (confirm('Cancel this payment and return to checkout?')) {
      window.location.href = '<?= htmlspecialchars(SITE_URL) ?>/checkout.php?payment_error=jazzcash&code=200&msg=Payment+cancelled+by+user';
    }
  }

  // Auto-focus OTP field when panel 2 shown
  document.getElementById('panel1').querySelector('.btn-pay').addEventListener('click', function() {
    setTimeout(function(){ document.getElementById('otpInput').focus(); }, 100);
  });
</script>
</body>
</html>
