<?php
/**
 * Kapraa — Central email sender
 * Used by: forgot password, order confirmation, and any future email
 *
 * Usage: sendEmail($to, $subject, $htmlBody, $toName = '')
 */

define('MAIL_FROM',    'kapraaofficial@gmail.com');
define('MAIL_NAME',    'Kapraa');
define('SMTP_HOST_TH', 'smtp.gmail.com');
define('SMTP_PORT_TH', 587);
define('SMTP_PASS_TH', 'agyifycxbftomjio');

function sendEmail($toEmail, $subject, $htmlBody, $toName = '') {
    $cleanName = $toName ? str_replace(["\r","\n"], '', $toName) : $toEmail;

    // ── Try PHP mail() first — server's internal MTA, no socket needed ──
    $headers  = "From: " . MAIL_NAME . " <" . MAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . MAIL_FROM . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "X-Mailer: Kapraa/1.0";
    if (@mail($toEmail, $subject, $htmlBody, $headers)) return true;

    // ── Fallback: Gmail SMTP port 587 STARTTLS ──────────────────────────────
    $sock = @fsockopen('tcp://' . SMTP_HOST_TH, SMTP_PORT_TH, $errno, $errstr, 15);
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
    $cmd('EHLO ' . SMTP_HOST_TH);
    $cmd('STARTTLS');
    stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    $cmd('EHLO ' . SMTP_HOST_TH);
    $cmd('AUTH LOGIN');
    $cmd(base64_encode(MAIL_FROM));
    $authResp = $cmd(base64_encode(SMTP_PASS_TH));

    if (strpos($authResp, '235') === false) { fclose($sock); return false; }

    $cmd('MAIL FROM:<' . MAIL_FROM . '>');
    $cmd('RCPT TO:<' . $toEmail . '>');
    $cmd('DATA');

    $msg  = "Date: " . date('r') . "\r\n";
    $msg .= "From: " . MAIL_NAME . " <" . MAIL_FROM . ">\r\n";
    $msg .= "To: {$cleanName} <{$toEmail}>\r\n";
    $msg .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $msg .= "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nX-Mailer: Kapraa/1.0\r\n\r\n";
    $msg .= $htmlBody . "\r\n.";

    $sendResp = $cmd($msg);
    $cmd('QUIT');
    fclose($sock);
    return strpos($sendResp, '250') !== false;
}
