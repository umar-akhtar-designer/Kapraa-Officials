
<?php
// Kapraa Virtual Try-On — YouCam AI Clothes API
set_time_limit(150);
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST required.']);
    exit;
}

require_once __DIR__ . '/tryon_config.php';

$apiKey = defined('YOUCAM_API_KEY') ? trim(YOUCAM_API_KEY) : '';
if ($apiKey === '') {
    fail('YouCam API key is not configured.', 500);
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$personB64 = trim((string)($input['person_b64'] ?? ''));
$personType = strtolower(trim((string)($input['person_type'] ?? 'image/jpeg')));
$garmentPath = ltrim(trim((string)($input['garment_path'] ?? '')), '/');
$productName = trim((string)($input['product_name'] ?? ''));

if ($personB64 === '' || $garmentPath === '') {
    fail('Please provide your photo and select a garment.');
}

// Only allow Kapraa product-image paths.
if (strpos($garmentPath, '..') !== false ||
    strpos($garmentPath, 'admin/images/product/') !== 0) {
    fail('Invalid garment image path.');
}

$garmentFile = __DIR__ . '/' . $garmentPath;
if (!is_file($garmentFile) || !is_readable($garmentFile)) {
    fail('The selected product image could not be found.');
}

// YouCam's documented flow supports JPG and PNG images.
$mimeMap = [
    'image/jpeg' => ['ext' => 'jpg', 'api' => 'image/jpg'],
    'image/jpg'  => ['ext' => 'jpg', 'api' => 'image/jpg'],
    'image/png'  => ['ext' => 'png', 'api' => 'image/png'],
];

if (!isset($mimeMap[$personType])) {
    fail('Please upload a JPG or PNG photo. WebP is not supported by this endpoint.');
}

$personBytes = base64_decode($personB64, true);
if ($personBytes === false || strlen($personBytes) < 1024) {
    fail('The uploaded photo is invalid. Please try another image.');
}
if (strlen($personBytes) > 10 * 1024 * 1024) {
    fail('Your photo must be smaller than 10 MB.');
}

$garmentSize = filesize($garmentFile);
if ($garmentSize === false || $garmentSize > 10 * 1024 * 1024) {
    fail('The product image must be smaller than 10 MB.');
}

// Public URL that YouCam can access.
$garmentUrl = 'https://kapraa.pk/' . str_replace('%2F', '/', rawurlencode($garmentPath));
$garmentUrl = str_replace('%3A', ':', $garmentUrl);

// 1. Upload the shopper image to YouCam's File API.
$personFile = youcamUpload(
    $apiKey,
    $personBytes,
    $mimeMap[$personType]['api'],
    'kapraa-shopper-' . bin2hex(random_bytes(5)) . '.' . $mimeMap[$personType]['ext']
);

if (empty($personFile['file_id'])) {
    error_log('[Kapraa YouCam] Upload failed: ' . json_encode($personFile));
    fail('Could not upload your photo to the AI service. Please try again.');
}

// 2. Create the AI clothes try-on task.
// Use a single, front-facing garment product image where possible.
$task = youcamRequest(
    'POST',
    'https://yce-api-01.makeupar.com/s2s/v2.0/task/cloth-v4',
    $apiKey,
    [
        'src_file_id' => $personFile['file_id'],
        'ref_file_url' => $garmentUrl,
        'garment_category' => 'upper_body'
    ]
);

$taskId = $task['data']['task_id'] ?? '';
if (($task['status'] ?? 0) != 200 || $taskId === '') {
    error_log('[Kapraa YouCam] Task creation failed: ' . json_encode($task));
    fail('YouCam could not start the try-on. Check the image and API access, then retry.');
}

// 3. Poll task status for the actual generated result.
$final = null;
for ($i = 0; $i < 12; $i++) {
    sleep(3);

    $status = youcamRequest(
        'GET',
        'https://yce-api-01.makeupar.com/s2s/v2.0/task/cloth-v4/' . rawurlencode($taskId),
        $apiKey
    );

    $taskStatus = strtolower((string)($status['data']['task_status'] ?? ''));

    if ($taskStatus === 'success') {
        $final = $status['data']['results']['url'] ?? '';
        break;
    }

    if ($taskStatus === 'error') {
        error_log('[Kapraa YouCam] Task error: ' . json_encode($status));
        fail('AI try-on failed for this photo. Please try a clearer photo or another garment.');
    }
}

if (!is_string($final) || !filter_var($final, FILTER_VALIDATE_URL) ||
    !in_array(strtolower((string)parse_url($final, PHP_URL_SCHEME)), ['https'], true)) {
    fail('The AI result is not ready yet. Please try again in a moment.', 504);
}

echo json_encode([
    'success' => true,
    'mode' => 'ai',
    'result_url' => $final,
    'product_name' => $productName
]);
exit;


// ---------------- Helper functions ----------------

function fail(string $message, int $httpCode = 400): void {
    http_response_code($httpCode);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function youcamRequest(
    string $method,
    string $url,
    string $apiKey,
    ?array $body = null
): array {
    $ch = curl_init($url);

    $headers = [
        'Authorization: Bearer ' . $apiKey,
        'Accept: application/json',
        'Content-Type: application/json'
    ];

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CUSTOMREQUEST => $method
    ];

    if ($body !== null) {
        $options[CURLOPT_POSTFIELDS] = json_encode($body);
    }

    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {
        error_log('[Kapraa YouCam] cURL error: ' . curl_error($ch));
        curl_close($ch);
        return ['status' => 0, 'error' => 'Network request failed'];
    }

    curl_close($ch);
    $decoded = json_decode($response, true);

    if (!is_array($decoded)) {
        error_log('[Kapraa YouCam] Invalid JSON; HTTP ' . $httpCode);
        return ['status' => $httpCode, 'error' => 'Invalid API response'];
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        error_log('[Kapraa YouCam] HTTP ' . $httpCode . ': ' . substr($response, 0, 500));
        $decoded['_http_code'] = $httpCode;
    }

    return $decoded;
}

function youcamUpload(
    string $apiKey,
    string $bytes,
    string $mime,
    string $filename
): array {
    // Request a temporary upload URL.
    $fileInfo = youcamRequest(
        'POST',
        'https://yce-api-01.makeupar.com/s2s/v2.0/file',
        $apiKey,
        [
            'files' => [[
                'content_type' => $mime,
                'file_name' => $filename,
                'file_size' => strlen($bytes)
            ]]
        ]
    );

    $file = $fileInfo['data']['files'][0] ?? [];
    $fileId = $file['file_id'] ?? '';
    $uploadRequest = $file['requests'][0] ?? [];
    $uploadUrl = $uploadRequest['url'] ?? '';

    if ($fileId === '' || $uploadUrl === '') {
        return ['error' => 'YouCam did not provide an upload URL'];
    }

    // Upload binary image bytes to the temporary signed URL.
    $uploadHeaders = [];
    foreach (($uploadRequest['headers'] ?? []) as $name => $value) {
        $uploadHeaders[] = $name . ': ' . $value;
    }
    if (!$uploadHeaders) {
        $uploadHeaders[] = 'Content-Type: ' . $mime;
    }

    $ch = curl_init($uploadUrl);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_HTTPHEADER => $uploadHeaders,
        CURLOPT_POSTFIELDS => $bytes,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30
    ]);

    $uploadResponse = curl_exec($ch);
    $uploadCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($uploadResponse === false) {
        error_log('[Kapraa YouCam] Image upload cURL error: ' . curl_error($ch));
    }
    curl_close($ch);

    if (!in_array($uploadCode, [200, 201, 204], true)) {
        error_log('[Kapraa YouCam] Signed upload failed; HTTP ' . $uploadCode);
        return ['error' => 'Image upload failed'];
    }

    return ['file_id' => $fileId];
}
