<?php
// ============================================================
// payment/jazzcash_wallet.php — JazzCash MWALLET Payment Form
// Collects mobile number + CNIC, POSTs to jazzcash_wallet_process.php
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';

if (empty($_SESSION['jc_params'])) {
    header('Location: ' . SITE_URL . '/checkout.php');
    exit;
}

$p       = $_SESSION['jc_params'];
$amount  = number_format((float)$p['amountPKR'], 2);
$order   = $p['orderNumber'];

// Pass any error from previous attempt
$error = $_GET['err'] ?? '';
$errMsg = '';
if ($error === 'invalid_mobile') $errMsg = 'Please enter a valid Pakistani mobile number (e.g. 03123456789).';
elseif ($error === 'invalid_cnic') $errMsg = 'Please enter the last 6 digits of your CNIC (numbers only).';
elseif ($error === 'jc_fail') $errMsg = htmlspecialchars(urldecode($_GET['msg'] ?? 'Payment failed. Please try again.'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>JazzCash — Mobile Wallet Payment</title>
  <style>
    *{margin:0;padding:0;box-sizing:border-box;}
    body{
      font-family:'Segoe UI',Arial,sans-serif;
      background:linear-gradient(135deg,#b71c1c 0%,#e53935 60%,#b71c1c 100%);
      min-height:100vh;display:flex;flex-direction:column;
      align-items:center;justify-content:center;padding:20px;
    }
    /* Header */
    .jc-header{
      width:100%;max-width:420px;
      background:rgba(0,0,0,.18);
      border-radius:16px 16px 0 0;
      padding:14px 20px;
      display:flex;align-items:center;gap:12px;
    }
    .jc-logo{width:38px;height:38px;background:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;}
    .jc-logo img{width:28px;}
    .jc-header h1{color:#fff;font-size:15px;font-weight:700;}
    .jc-header p{color:rgba(255,255,255,.7);font-size:11px;}
    /* Card */
    .jc-card{width:100%;max-width:420px;background:#fff;border-radius:0 0 16px 16px;padding:24px;box-shadow:0 10px 40px rgba(0,0,0,.3);}
    /* Amount */
    .amount-row{background:#fff5f5;border:1px solid #fdd;border-radius:10px;padding:12px 16px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;}
    .amount-row .lbl{font-size:11px;color:#999;margin-bottom:2px;}
    .amount-row .val{font-size:22px;font-weight:800;color:#c62828;}
    .amount-row .ref{font-size:10px;color:#ccc;}
    /* Steps */
    .steps{display:flex;margin-bottom:20px;gap:0;}
    .step{flex:1;text-align:center;position:relative;}
    .step::after{content:'';position:absolute;top:13px;left:50%;right:-50%;height:2px;background:#eee;z-index:0;}
    .step:last-child::after{display:none;}
    .dot{width:26px;height:26px;border-radius:50%;background:#eee;color:#bbb;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;margin:0 auto 3px;position:relative;z-index:1;transition:all .3s;}
    .step.active .dot{background:#e53935;color:#fff;}
    .step.done .dot{background:#43a047;color:#fff;}
    .step.done::after{background:#43a047;}
    .step-lbl{font-size:10px;color:#bbb;}
    .step.active .step-lbl{color:#e53935;font-weight:600;}
    .step.done  .step-lbl{color:#43a047;}
    /* Form */
    .field{margin-bottom:16px;}
    .field label{display:block;font-size:12px;font-weight:600;color:#555;margin-bottom:5px;}
    .field .hint{font-size:11px;color:#aaa;margin-bottom:5px;}
    .input-wrap{position:relative;}
    .input-wrap input{width:100%;padding:12px 14px 12px 42px;border:1.5px solid #ddd;border-radius:8px;font-size:15px;color:#222;outline:none;transition:border-color .2s;}
    .input-wrap input:focus{border-color:#e53935;}
    .input-wrap .ic{position:absolute;left:13px;top:50%;transform:translateY(-50%);font-size:17px;}
    /* Error */
    .err-box{background:#fff0f0;border:1px solid #ffcdd2;border-radius:8px;padding:10px 14px;font-size:12px;color:#c62828;margin-bottom:14px;display:flex;gap:8px;align-items:flex-start;}
    /* Sandbox notice */
    .sandbox-note{background:#e8f5e9;border:1px solid #c8e6c9;border-radius:8px;padding:10px 14px;font-size:12px;color:#2e7d32;margin-bottom:16px;line-height:1.7;}
    .sandbox-note strong{display:block;margin-bottom:3px;}
    .sandbox-note code{background:#c8e6c9;padding:1px 5px;border-radius:3px;font-size:12px;font-family:monospace;}
    /* Buttons */
    .btn-pay{width:100%;padding:13px;background:linear-gradient(135deg,#c62828,#e53935);color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:opacity .2s;}
    .btn-pay:hover{opacity:.9;}
    .btn-pay:disabled{opacity:.6;cursor:not-allowed;}
    .btn-cancel{width:100%;padding:10px;background:transparent;color:#999;border:1.5px solid #eee;border-radius:8px;font-size:13px;cursor:pointer;margin-top:9px;transition:all .2s;}
    .btn-cancel:hover{border-color:#e53935;color:#e53935;}
    /* Processing overlay */
    #overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:99;align-items:center;justify-content:center;}
    #overlay.show{display:flex;}
    .ov-box{background:#fff;border-radius:16px;padding:36px 28px;text-align:center;max-width:280px;width:90%;}
    .spinner{width:48px;height:48px;border:5px solid #fdd;border-top-color:#e53935;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 14px;}
    @keyframes spin{to{transform:rotate(360deg);}}
    .ov-box h3{color:#c62828;font-size:15px;margin-bottom:6px;}
    .ov-box p{color:#888;font-size:12px;}
    /* Footer */
    .footer{margin-top:14px;color:rgba(255,255,255,.65);font-size:11px;display:flex;align-items:center;gap:5px;}
  </style>
</head>
<body>

<div class="jc-header">
  <div class="jc-logo">
    <!-- JazzCash "JC" text logo in brand red -->
    <svg width="28" height="28" viewBox="0 0 40 40">
      <rect width="40" height="40" rx="8" fill="#e53935"/>
      <text x="20" y="28" text-anchor="middle" fill="white" font-size="16" font-weight="bold" font-family="Arial">JC</text>
    </svg>
  </div>
  <div>
    <h1>JazzCash</h1>
    <p>Mobile Wallet · Secure Payment</p>
  </div>
</div>

<div class="jc-card">

  <!-- Amount -->
  <div class="amount-row">
    <div>
      <div class="lbl">Amount Due</div>
      <div class="val">PKR <?= htmlspecialchars($amount) ?></div>
      <div class="ref">Ref: <?= htmlspecialchars($order) ?></div>
    </div>
    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#e53935" stroke-width="1.5"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
  </div>

  <!-- Steps -->
  <div class="steps">
    <div class="step active" id="s1"><div class="dot">1</div><div class="step-lbl">Details</div></div>
    <div class="step" id="s2"><div class="dot">2</div><div class="step-lbl">Confirm</div></div>
    <div class="step" id="s3"><div class="dot">✓</div><div class="step-lbl">Done</div></div>
  </div>

  <!-- Error message -->
  <?php if ($errMsg): ?>
  <div class="err-box">
    <span>❌</span>
    <span><?= $errMsg ?></span>
  </div>
  <?php endif; ?>

  <!-- Sandbox test hint -->
  <div class="sandbox-note">
    <strong>🧪 Sandbox Test Credentials</strong>
    Mobile: <code>03123456789</code> &nbsp;|&nbsp; CNIC last 6: <code>345678</code>
  </div>

  <!-- Payment form -->
  <form method="POST" action="<?= htmlspecialchars(SITE_URL) ?>/payment/jazzcash_wallet_process.php" id="payForm" onsubmit="showOverlay()">

    <div class="field">
      <label>JazzCash Mobile Number</label>
      <div class="hint">Enter the number linked to your JazzCash wallet</div>
      <div class="input-wrap">
        <span class="ic">📱</span>
        <input type="tel" name="mobile" id="mobileInput" placeholder="03XXXXXXXXX" maxlength="11"
               value="<?= isset($_GET['err']) ? htmlspecialchars($_POST['mobile'] ?? '') : '' ?>"
               required>
      </div>
    </div>

    <div class="field">
      <label>CNIC — Last 6 Digits</label>
      <div class="hint">Last 6 digits of your National Identity Card</div>
      <div class="input-wrap">
        <span class="ic">🪪</span>
        <input type="text" name="cnic" id="cnicInput" placeholder="e.g. 345678" maxlength="6" inputmode="numeric"
               value="<?= isset($_GET['err']) ? htmlspecialchars($_POST['cnic'] ?? '') : '' ?>"
               required>
      </div>
    </div>

    <button type="submit" class="btn-pay" id="payBtn">
      <span>Pay PKR <?= htmlspecialchars($amount) ?></span>
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
    </button>
    <button type="button" class="btn-cancel" onclick="cancelPay()">Cancel Payment</button>
  </form>

</div>

<div class="footer">
  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
  256-bit SSL Encrypted · Powered by JazzCash
</div>

<!-- Processing overlay -->
<div id="overlay">
  <div class="ov-box">
    <div class="spinner"></div>
    <h3>Processing Payment…</h3>
    <p>Please wait. Do not press back or close this tab.</p>
  </div>
</div>

<script>
function showOverlay() {
  // Basic validation before showing overlay
  const mobile = document.getElementById('mobileInput').value.replace(/\D/g,'');
  const cnic   = document.getElementById('cnicInput').value.replace(/\D/g,'');
  if (mobile.length !== 11 || !mobile.startsWith('03')) {
    alert('Please enter a valid mobile number starting with 03 (11 digits total).');
    return false;
  }
  if (cnic.length !== 6) {
    alert('Please enter exactly 6 digits for your CNIC.');
    return false;
  }
  document.getElementById('overlay').classList.add('show');
  document.getElementById('payBtn').disabled = true;
  // Mark step 2 active
  document.getElementById('s1').className = 'step done';
  document.getElementById('s2').className = 'step active';
}

function cancelPay() {
  if (confirm('Cancel payment and return to checkout?')) {
    window.location.href = '<?= htmlspecialchars(SITE_URL) ?>/checkout.php?payment_error=jazzcash&code=200&msg=Payment+cancelled';
  }
}
</script>

</body>
</html>
