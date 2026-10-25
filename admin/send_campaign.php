<?php
// Campaign email sender — AJAX endpoint
// Direct DB connect (avoids session/output conflicts from config.php)
header('Content-Type: application/json; charset=utf-8');

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = (strpos($host,'localhost') !== false || strpos($host,'127.0.0.1') !== false || strpos($host,'ngrok') !== false);

if ($isLocal) {
    $db = @mysqli_connect('localhost','root','','kapraa');
} else {
    $db = @mysqli_connect('localhost','u167258434_kapraa','Kapraa@2026','u167258434_kapraa');
}

if (!$db) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}
mysqli_set_charset($db, 'utf8');
date_default_timezone_set('Asia/Karachi');

// $db is ready

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['campaign_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$cid = (int)$_POST['campaign_id'];

// Fetch campaign
$cRes = mysqli_query($db, "SELECT * FROM email_campaigns WHERE id=$cid LIMIT 1");
if (!$cRes || mysqli_num_rows($cRes) === 0) {
    echo json_encode(['success' => false, 'error' => 'Campaign not found']);
    exit;
}
$campaign = mysqli_fetch_assoc($cRes);

// Use selected emails if provided, otherwise fall back to all customers
$customers = [];

if (!empty($_POST['emails']) && is_array($_POST['emails'])) {
    // Sanitise and deduplicate the submitted email list
    $selectedEmails = [];
    foreach ($_POST['emails'] as $e) {
        $e = trim($e);
        if ($e !== '' && strpos($e, '@') !== false) {
            $selectedEmails[] = mysqli_real_escape_string($db, $e);
        }
    }

    if (empty($selectedEmails)) {
        echo json_encode(['success' => false, 'error' => 'No valid emails in selection']);
        exit;
    }

    // Build IN clause and fetch matching customer names
    $inClause = "'" . implode("','", $selectedEmails) . "'";
    $emailRes  = mysqli_query($db,
        "SELECT email, customer_name FROM orders
         WHERE email IN ($inClause)
         GROUP BY email
         ORDER BY MAX(id) DESC");

    if (!$emailRes) {
        echo json_encode(['success' => false, 'error' => 'Could not fetch selected emails']);
        exit;
    }
    while ($row = mysqli_fetch_assoc($emailRes)) {
        $customers[$row['email']] = $row['customer_name'] ?: 'Valued Customer';
    }

    // Ensure every submitted email is represented even if not in orders
    foreach ($selectedEmails as $e) {
        if (!isset($customers[$e])) {
            $customers[$e] = 'Valued Customer';
        }
    }

} else {
    // Fallback: send to ALL unique customer emails from orders
    $emailRes = mysqli_query($db,
        "SELECT DISTINCT email, customer_name FROM orders
         WHERE email IS NOT NULL AND email != '' AND email LIKE '%@%'
         ORDER BY id DESC");
    if (!$emailRes) {
        echo json_encode(['success' => false, 'error' => 'Could not fetch emails']);
        exit;
    }
    while ($row = mysqli_fetch_assoc($emailRes)) {
        $customers[$row['email']] = $row['customer_name'] ?: 'Valued Customer';
    }
}

if (empty($customers)) {
    echo json_encode(['success' => false, 'error' => 'No customer emails found']);
    exit;
}

// Build site URL
$siteUrl = $isLocal ? "http://localhost/kapraa" : "https://kapraa.pk";
$logoUrl = $siteUrl . "/assets/images/512.png";

// Build HTML template
function buildEmailHtml($campaign, $customerName, $siteUrl, $logoUrl) {
    $badge    = htmlspecialchars($campaign['badge_text']);
    $headline = htmlspecialchars($campaign['headline']);
    $subhl    = htmlspecialchars($campaign['sub_headline']);
    $body     = nl2br(htmlspecialchars($campaign['body_text']));
    $btnTxt   = htmlspecialchars($campaign['button_text']);
    $btnUrl   = htmlspecialchars($campaign['button_url']) ?: $siteUrl;
    $footer   = htmlspecialchars($campaign['footer_note']);
    $name     = htmlspecialchars($customerName);
    $subject  = htmlspecialchars($campaign['subject']);

    // Pre-build optional blocks (ternary not allowed inside heredoc)
    $badgeRow  = $badge  ? '<tr><td style="padding:16px 32px 0;text-align:center;"><span style="display:inline-block;background:#fff3cd;color:#856404;border-radius:20px;padding:6px 18px;font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;">'.$badge.'</span></td></tr>' : '';
    $subhlRow  = $subhl  ? '<tr><td style="padding:10px 32px 0;text-align:center;"><p style="margin:0;font-size:18px;color:#555;font-weight:600;">'.$subhl.'</p></td></tr>' : '';
    $bodyRow   = $body   ? '<tr><td style="padding:20px 40px 0;text-align:center;"><p style="margin:0;font-size:14px;color:#666;line-height:1.75;">'.$body.'</p></td></tr>' : '';
    $footerRow = $footer ? '<p style="margin:0 0 6px;font-size:11px;color:#aaa;">'.$footer.'</p>' : '';

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>{$subject}</title>
</head>
<body style="margin:0;padding:0;background:#f0f0f0;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f0f0f0;padding:30px 0;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;width:100%;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.1);">

  <!-- HEADER -->
  <tr>
    <td style="background:linear-gradient(135deg,#1a1a2e 0%,#0f3460 100%);padding:28px 32px;text-align:center;">
      <img src="{$logoUrl}" alt="Kapraa" height="50" style="max-width:180px;height:auto;display:inline-block;">
    </td>
  </tr>

  <!-- COLOR BAR -->
  <tr><td style="background:linear-gradient(90deg,#f9ca24,#f0932b);height:6px;font-size:1px;line-height:1px;">&nbsp;</td></tr>

  <!-- GREETING -->
  <tr>
    <td style="padding:24px 32px 0;text-align:center;">
      <p style="margin:0;font-size:15px;color:#666;">Hi <strong style="color:#1a1a2e;">{$name}</strong>,</p>
    </td>
  </tr>

  {$badgeRow}

  <!-- HEADLINE -->
  <tr>
    <td style="padding:16px 32px 0;text-align:center;">
      <h1 style="margin:0;font-size:52px;font-weight:900;color:#1a1a2e;letter-spacing:-2px;line-height:1;">{$headline}</h1>
    </td>
  </tr>

  {$subhlRow}

  <!-- DIVIDER -->
  <tr>
    <td style="padding:20px 32px 0;text-align:center;">
      <div style="width:50px;height:4px;background:linear-gradient(90deg,#f9ca24,#f0932b);margin:0 auto;border-radius:2px;"></div>
    </td>
  </tr>

  {$bodyRow}

  <!-- CTA BUTTON -->
  <tr>
    <td style="padding:28px 32px 32px;text-align:center;">
      <a href="{$btnUrl}" style="display:inline-block;background:linear-gradient(135deg,#1a1a2e,#0f3460);color:#ffffff;text-decoration:none;padding:15px 44px;border-radius:30px;font-size:16px;font-weight:700;letter-spacing:.5px;">
        {$btnTxt} &rarr;
      </a>
    </td>
  </tr>

  <!-- COLOR BAR -->
  <tr><td style="background:linear-gradient(90deg,#f9ca24,#f0932b);height:4px;font-size:1px;line-height:1px;">&nbsp;</td></tr>

  <!-- FOOTER -->
  <tr>
    <td style="background:#f8f8f8;padding:22px 32px;text-align:center;">
      <p style="margin:0 0 6px;font-size:12px;color:#888;">You're receiving this because you shopped with <strong>Kapraa</strong>.</p>
      {$footerRow}
      <p style="margin:0;font-size:12px;color:#aaa;">&copy; 2025 Kapraa &middot; <a href="{$siteUrl}" style="color:#f0932b;text-decoration:none;">Visit Store</a></p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
HTML;
}

// ── Hostinger SMTP credentials ───────────────────────────────
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'kapraaofficial@gmail.com');
define('SMTP_PASS', 'agyifycxbftomjio');
define('SMTP_FROM', 'kapraaofficial@gmail.com');
define('SMTP_NAME', 'Kapraa');
// ─────────────────────────────────────────────────────────────

/**
 * Send one HTML email — PHP mail() first, SMTP socket fallback.
 */
function smtpSendGmail($toEmail, $toName, $subject, $htmlBody) {
    $cleanName = str_replace(["\r","\n"], '', $toName);

    // ── Try PHP mail() first (server internal MTA — no socket needed) ──
    $headers  = "From: " . SMTP_NAME . " <" . SMTP_FROM . ">\r\n";
    $headers .= "Reply-To: " . SMTP_FROM . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "X-Mailer: Kapraa/1.0";
    if (@mail($toEmail, $subject, $htmlBody, $headers)) return true;

    // ── Fallback: SMTP socket (port 587 STARTTLS) ──────────────────────────
    $sock = @fsockopen('tcp://' . SMTP_HOST, SMTP_PORT, $errno, $errstr, 15);
    if (!$sock) return false;

    $read = function() use ($sock) {
        $r = '';
        while ($line = fgets($sock, 512)) {
            $r .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $r;
    };
    $cmd = function($c) use ($sock, $read) {
        fwrite($sock, $c . "\r\n");
        return $read();
    };

    $read(); // greeting
    $cmd('EHLO ' . SMTP_HOST);
    $cmd('STARTTLS');
    stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    $cmd('EHLO ' . SMTP_HOST);
    $cmd('AUTH LOGIN');
    $cmd(base64_encode(SMTP_USER));
    $authResp = $cmd(base64_encode(SMTP_PASS));

    if (strpos($authResp, '235') === false) { fclose($sock); return false; }

    $cmd('MAIL FROM:<' . SMTP_FROM . '>');
    $cmd('RCPT TO:<' . $toEmail . '>');
    $cmd('DATA');

    $msg  = "Date: " . date('r') . "\r\n";
    $msg .= "From: " . SMTP_NAME . " <" . SMTP_FROM . ">\r\n";
    $msg .= "To: {$cleanName} <{$toEmail}>\r\n";
    $msg .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $msg .= "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nX-Mailer: Kapraa/1.0\r\n\r\n";
    $msg .= $htmlBody . "\r\n.";

    $sendResp = $cmd($msg);
    $cmd('QUIT');
    fclose($sock);
    return strpos($sendResp, '250') !== false;
}

// Send emails
$sent = 0;
$failed = 0;

set_time_limit(300); // allow up to 5 minutes

foreach ($customers as $email => $name) {
    $html    = buildEmailHtml($campaign, $name, $siteUrl, $logoUrl);
    $subject = $campaign['subject'];

    $result = smtpSendGmail($email, $name, $subject, $html);

    // Log result
    $status   = $result ? 'sent' : 'failed';
    $emailEsc = mysqli_real_escape_string($db, $email);
    mysqli_query($db, "INSERT INTO email_campaign_log (campaign_id,email,status) VALUES ($cid,'$emailEsc','$status')");

    if ($result) $sent++;
    else $failed++;

    usleep(300000); // 0.3s between emails — stay within Gmail rate limit
}

// Update campaign record
$now = date('Y-m-d H:i:s');
mysqli_query($db, "UPDATE email_campaigns SET sent_count=sent_count+$sent, status='sent', sent_at='$now' WHERE id=$cid");

echo json_encode([
    'success' => true,
    'sent'    => $sent,
    'failed'  => $failed,
    'total'   => count($customers),
]);
