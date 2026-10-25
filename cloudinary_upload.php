<?php
// ============================================================
// cloudinary_upload.php — Kapraa Cloudinary Uploader
// Accepts base64 or multipart image, uploads to Cloudinary,
// returns JSON with the Cloudinary URL.
// No SDK — uses curl + unsigned upload preset.
// ============================================================

define('CLOUDINARY_CLOUD_NAME',    'dxx12rebk');
define('CLOUDINARY_UPLOAD_PRESET', 'Kapraa'); // unsigned preset — create this in Cloudinary dashboard

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Only POST requests are accepted.']);
    exit;
}

// ── DETERMINE UPLOAD SOURCE ────────────────────────────────
$uploadData = null; // will be a base64 data-URI string

// Case 1: JSON body with image_base64
$rawBody = file_get_contents('php://input');
if ($rawBody && trim($rawBody) !== '') {
    $in = json_decode($rawBody, true);
    if (!empty($in['image_base64'])) {
        $imageType  = trim((string)($in['image_type'] ?? 'image/jpeg'));
        $uploadData = 'data:' . $imageType . ';base64,' . trim($in['image_base64']);
    }
}

// Case 2: Multipart file upload ($_FILES['image'])
if ($uploadData === null && !empty($_FILES['image']['tmp_name'])) {
    $tmpPath   = $_FILES['image']['tmp_name'];
    $mimeType  = mime_content_type($tmpPath);
    $fileBytes = file_get_contents($tmpPath);
    if ($fileBytes !== false) {
        $uploadData = 'data:' . $mimeType . ';base64,' . base64_encode($fileBytes);
    }
}

if ($uploadData === null) {
    echo json_encode(['success' => false, 'error' => 'No image provided. Send image_base64 in JSON or image via multipart.']);
    exit;
}
// ── CLOUDINARY CONFIG ───────────────────────────────
$cloudName    = 'dxx12rebk';
$uploadPreset = 'Kapraa';

$uploadUrl = "https://api.cloudinary.com/v1_1/$cloudName/image/upload";
$postFields = [
    'file'             => $uploadData,
    'upload_preset'    => $uploadPreset,
    'folder'           => 'kapraa/chat_uploads',
    'transformation'   => 'q_auto,f_auto,w_800',
];

// ── CURL TO CLOUDINARY ─────────────────────────────────────
$ch = curl_init($uploadUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS     => $postFields,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    echo json_encode(['success' => false, 'error' => 'Upload request failed: ' . $curlErr]);
    exit;
}

$result = json_decode($response, true);

if ($httpCode !== 200 || empty($result['secure_url'])) {
    $errMsg = $result['error']['message'] ?? ('Cloudinary error (HTTP ' . $httpCode . ')');
    echo json_encode(['success' => false, 'error' => $errMsg]);
    exit;
}

// ── SUCCESS ────────────────────────────────────────────────
echo json_encode([
    'success'   => true,
    'url'       => $result['secure_url'],
    'public_id' => $result['public_id'] ?? '',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit;
