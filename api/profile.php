<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = (strpos($host,'localhost')!==false||strpos($host,'127.0.0.1')!==false||strpos($host,'192.168.')!==false);
$base    = $isLocal ? 'http://'.$host.'/kapraa' : 'https://'.$host;
if ($isLocal) {
    $conn = mysqli_connect('localhost', 'root', '', 'kapraa');
} else {
    $conn = mysqli_connect('localhost', 'u167258434_kapraa', 'Kapraa@2026', 'u167258434_kapraa');
}
if (!$conn) { echo json_encode(['success'=>false,'error'=>mysqli_connect_error()]); exit; }
mysqli_set_charset($conn, 'utf8mb4');

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base = $isLocal ? 'http://'.$host.'/kapraa' : 'https://'.$host;
$action  = $_POST['action'] ?? '';
$user_id = (int)($_POST['user_id'] ?? 0);

if (!$user_id) { echo json_encode(['success'=>false,'message'=>'Not logged in']); exit; }

// ── GET PROFILE ───────────────────────────────────────────────
if ($action === 'get') {
    $r   = mysqli_query($conn, "SELECT id,name,email,number,image FROM customers WHERE id=$user_id LIMIT 1");
    $row = mysqli_fetch_assoc($r);
    if (!$row) { echo json_encode(['success'=>false,'message'=>'User not found']); exit; }
    if ($row['image']) $row['image_url'] = $base . '/admin/images/customers/' . $row['image'];
    echo json_encode(['success'=>true,'data'=>$row]);
}

// ── UPDATE PROFILE ────────────────────────────────────────────
elseif ($action === 'update') {
    $name   = trim($_POST['name']   ?? '');
    $number = trim($_POST['number'] ?? '');

    if (!$name) { echo json_encode(['success'=>false,'message'=>'Name is required']); exit; }

    // Handle profile image upload
    $img_update = '';
    if (!empty($_FILES['image']['tmp_name'])) {
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (in_array($ext, $allowed) && $_FILES['image']['size'] < 3000000) {
            $filename = 'customer_' . $user_id . '_' . time() . '.' . $ext;
            $dest     = __DIR__ . '/../admin/images/customers/' . $filename;
            if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $img_update = ", image='" . mysqli_real_escape_string($conn, $filename) . "'";
            }
        }
    }

    $name_e   = mysqli_real_escape_string($conn, $name);
    $number_e = mysqli_real_escape_string($conn, $number);

    $ok = mysqli_query($conn,
        "UPDATE customers SET name='$name_e', number='$number_e' $img_update WHERE id=$user_id"
    );

    if (!$ok) { echo json_encode(['success'=>false,'message'=>'Update failed']); exit; }

    $r   = mysqli_query($conn, "SELECT id,name,email,number,image FROM customers WHERE id=$user_id LIMIT 1");
    $row = mysqli_fetch_assoc($r);
    if ($row['image']) $row['image_url'] = $base . '/admin/images/customers/' . $row['image'];
    echo json_encode(['success'=>true,'message'=>'Profile updated!','data'=>$row]);
}

// ── CHANGE PASSWORD ───────────────────────────────────────────
elseif ($action === 'change_password') {
    $current  = trim($_POST['current_password'] ?? '');
    $new_pass = trim($_POST['new_password']     ?? '');

    if (!$current || !$new_pass) { echo json_encode(['success'=>false,'message'=>'All fields required']); exit; }
    if (strlen($new_pass) < 6)   { echo json_encode(['success'=>false,'message'=>'Password must be at least 6 characters']); exit; }

    $r   = mysqli_query($conn, "SELECT password FROM customers WHERE id=$user_id LIMIT 1");
    $row = mysqli_fetch_assoc($r);

    if (!$row || $row['password'] !== md5($current)) {
        echo json_encode(['success'=>false,'message'=>'Current password is incorrect']);
        exit;
    }

    $new_hash = md5($new_pass);
    mysqli_query($conn, "UPDATE customers SET password='$new_hash' WHERE id=$user_id");
    echo json_encode(['success'=>true,'message'=>'Password changed successfully!']);
}

else {
    echo json_encode(['success'=>false,'message'=>'Unknown action']);
}
