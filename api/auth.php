<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../include/send_email.php';
$host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = (strpos($host,'localhost')!==false||strpos($host,'127.0.0.1')!==false||strpos($host,'192.168.')!==false);
if ($isLocal) {
    $conn = mysqli_connect('localhost', 'root', '', 'kapraa');
} else {
    $conn = mysqli_connect('localhost', 'u167258434_kapraa', 'Kapraa@2026', 'u167258434_kapraa');
}
if (!$conn) { echo json_encode(['success'=>false,'error'=>mysqli_connect_error()]); exit; }
mysqli_set_charset($conn, 'utf8');

$base   = $isLocal ? 'http://'.$host.'/kapraa' : 'https://'.$host;
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── LOGIN ─────────────────────────────────────────────────────
if ($action === 'login') {
    $email       = mysqli_real_escape_string($conn, trim($_POST['email']    ?? ''));
    $rawPassword = trim($_POST['password'] ?? '');

    if (!$email) { echo json_encode(['success'=>false,'message'=>'Email required']); exit; }

    // Fetch hash only — support bcrypt (new) + MD5 (legacy)
    $result = mysqli_query($conn,
        "SELECT id, name, email, number, username, image, password AS stored_hash
         FROM customers WHERE email='$email' LIMIT 1"
    );
    $user = mysqli_fetch_assoc($result);

    $passwordOk = false;
    if ($user) {
        $hash = $user['stored_hash'];
        if (str_starts_with($hash, '$2y$')) {
            $passwordOk = password_verify($rawPassword, $hash);   // bcrypt
        } else {
            $passwordOk = ($hash === md5($rawPassword));           // legacy MD5
        }
    }

    if ($passwordOk) {
        unset($user['stored_hash']); // never send hash to app
        if (!empty($user['image']))
            $user['image_url'] = $base . '/admin/' . $user['image'];
        echo json_encode(['success'=>true, 'message'=>'Login successful', 'user'=>$user]);
    } else {
        echo json_encode(['success'=>false, 'message'=>'Invalid email or password']);
    }
}

// ── REGISTER ──────────────────────────────────────────────────
elseif ($action === 'register') {
    $name     = mysqli_real_escape_string($conn, trim($_POST['name']     ?? ''));
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']    ?? ''));
    $password = password_hash(trim($_POST['password'] ?? ''), PASSWORD_BCRYPT); // bcrypt
    $number   = mysqli_real_escape_string($conn, trim($_POST['number']   ?? ''));

    if (!$name || !$email) {
        echo json_encode(['success'=>false,'message'=>'Name and email required']); exit;
    }

    // Check if email exists
    $check = mysqli_query($conn, "SELECT id FROM customers WHERE email='$email' LIMIT 1");
    if (mysqli_fetch_assoc($check)) {
        echo json_encode(['success'=>false,'message'=>'Email already registered']); exit;
    }

    $username = strtolower(str_replace(' ', '_', $name));
    $ok = mysqli_query($conn,
        "INSERT INTO customers (name, email, password, number, username)
         VALUES ('$name','$email','$password','$number','$username')"
    );

    if ($ok) {
        $id   = mysqli_insert_id($conn);
        $user = ['id'=>$id,'name'=>$name,'email'=>$email,'number'=>$number,'username'=>$username];
        echo json_encode(['success'=>true,'message'=>'Account created!','user'=>$user]);
    } else {
        echo json_encode(['success'=>false,'message'=>'Registration failed: ' . mysqli_error($conn)]);
    }
}

// ── FORGOT PASSWORD — generate OTP ───────────────────────────
elseif ($action === 'forgot_password') {
    $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    if (!$email) { echo json_encode(['success'=>false,'message'=>'Email required']); exit; }

    // Auto-add OTP columns if not present
    mysqli_query($conn, "ALTER TABLE customers ADD COLUMN IF NOT EXISTS reset_otp VARCHAR(6) DEFAULT NULL");
    mysqli_query($conn, "ALTER TABLE customers ADD COLUMN IF NOT EXISTS reset_otp_expiry DATETIME DEFAULT NULL");

    $check = mysqli_query($conn, "SELECT id FROM customers WHERE email='$email' LIMIT 1");
    if (!mysqli_fetch_assoc($check)) {
        echo json_encode(['success'=>false,'message'=>'This email is not registered.','registered'=>false]);
        exit;
    }

    $otp    = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    mysqli_query($conn, "UPDATE customers SET reset_otp='$otp', reset_otp_expiry='$expiry' WHERE email='$email'");

    // Send OTP via email
    $emailBody = "
    <div style='font-family:Arial,sans-serif;max-width:480px;margin:0 auto;padding:30px;'>
      <div style='text-align:center;margin-bottom:24px;'>
        <h2 style='color:#1a1a2e;margin:0;'>Kapraa</h2>
        <p style='color:#888;font-size:13px;margin:4px 0 0;'>Password Reset Request</p>
      </div>
      <div style='background:#f9f9f9;border-radius:10px;padding:28px;text-align:center;'>
        <p style='color:#333;font-size:15px;margin:0 0 16px;'>Your password reset code is:</p>
        <div style='background:#1a1a2e;color:#c8a96e;font-size:36px;font-weight:900;letter-spacing:10px;padding:20px;border-radius:8px;display:inline-block;'>$otp</div>
        <p style='color:#888;font-size:13px;margin:16px 0 0;'>This code expires in <strong>15 minutes</strong>.</p>
        <p style='color:#888;font-size:12px;margin:8px 0 0;'>If you did not request this, please ignore this email.</p>
      </div>
      <p style='color:#bbb;font-size:11px;text-align:center;margin-top:20px;'>Kapraa &mdash; Your Fashion Destination</p>
    </div>";

    $emailSent = sendEmail($email, 'Your Kapraa Password Reset Code', $emailBody);
    echo json_encode(['success'=>true,'registered'=>true,'message'=>'Reset code sent to your email.','otp'=>$emailSent ? null : $otp,'email_sent'=>$emailSent]);
}

// ── RESET PASSWORD — verify OTP + set new password ───────────
elseif ($action === 'reset_password') {
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']    ?? ''));
    $otp      = mysqli_real_escape_string($conn, trim($_POST['otp']      ?? ''));
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$otp || !$password) {
        echo json_encode(['success'=>false,'message'=>'All fields required']); exit;
    }
    if (strlen($password) < 6) {
        echo json_encode(['success'=>false,'message'=>'Password must be at least 6 characters']); exit;
    }

    $now    = date('Y-m-d H:i:s');
    $result = mysqli_query($conn,
        "SELECT id FROM customers
         WHERE email='$email' AND reset_otp='$otp' AND reset_otp_expiry > '$now' LIMIT 1"
    );
    if (!mysqli_fetch_assoc($result)) {
        echo json_encode(['success'=>false,'message'=>'Invalid or expired reset code']); exit;
    }

    $hashed = password_hash($password, PASSWORD_BCRYPT); // bcrypt for reset too
    $safeHash = mysqli_real_escape_string($conn, $hashed);
    mysqli_query($conn,
        "UPDATE customers SET password='$safeHash', reset_otp=NULL, reset_otp_expiry=NULL WHERE email='$email'"
    );
    echo json_encode(['success'=>true,'message'=>'Password reset successful. Please login.']);
}

// ── PROFILE ───────────────────────────────────────────────────
elseif ($action === 'profile') {
    $id     = (int)($_GET['user_id'] ?? 0);
    $result = mysqli_query($conn,
        "SELECT id, name, email, number, username, image FROM customers WHERE id=$id LIMIT 1"
    );
    $user = mysqli_fetch_assoc($result);
    if ($user) {
        if (!empty($user['image']))
            $user['image_url'] = $base . '/admin/' . $user['image'];
        echo json_encode(['success'=>true,'user'=>$user]);
    } else {
        echo json_encode(['success'=>false,'message'=>'User not found']);
    }
} else {
    echo json_encode(['success'=>true,'status'=>'auth api ready','db'=>$conn ? 'connected' : 'failed']);
}
