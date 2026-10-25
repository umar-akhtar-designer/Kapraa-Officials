<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['WEBSITE-LOGIN'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Not authenticated'
    ]);
    exit;
}

/* Generate NEW 6-digit OTP EVERY TIME */
$otp = str_pad(
    (string) random_int(0, 999999),
    6,
    '0',
    STR_PAD_LEFT
);

/* Save NEW OTP */
$_SESSION['otp_code'] = $otp;
$_SESSION['otp_expires'] = time() + 600;
$_SESSION['otp_method'] = 'email';

/* Try email */
$fromEmail = 'contact@kapraa.pk';
$fromName  = 'Kapraa Admin';
$toEmail   = 'kapraaofficial@gmail.com';

$subject = 'Kapraa Admin Verification Code';

$message = "Your Kapraa Admin verification code is: {$otp}\n\n";
$message .= "This code expires in 10 minutes.";

$headers  = "From: {$fromName} <{$fromEmail}>\r\n";
$headers .= "Reply-To: {$fromEmail}\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

@mail(
    $toEmail,
    $subject,
    $message,
    $headers,
    '-f' . $fromEmail
);

/*
 * Always return the NEW OTP so it can be shown
 * on the verification form.
 */
echo json_encode([
    'success' => true,
    'fallback_code' => $otp
]);

exit;
?>