<?php
// AI Size Recommender — standalone AJAX endpoint (no DB needed)
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

define('SR_GEMINI_KEY',   'AIzaSyADBOJ_gAqqvw_9jLoSea7Ssfrd6IWUN4g');
define('SR_GEMINI_MODEL', 'gemini-2.5-flash-lite');
define('SR_GEMINI_URL',   'https://generativelanguage.googleapis.com/v1beta/models/' . SR_GEMINI_MODEL . ':generateContent?key=' . SR_GEMINI_KEY);

$height   = (float)($_POST['height']   ?? 0);
$weight   = (float)($_POST['weight']   ?? 0);
$age      = (int)  ($_POST['age']      ?? 0);
$bodyType = trim(  $_POST['body_type'] ?? '');
$gender   = trim(  $_POST['gender']    ?? 'female');
$category = trim(  $_POST['category']  ?? '');
$brand    = trim(  $_POST['brand']     ?? '');
$sizes    = trim(  $_POST['available_sizes'] ?? '');

if ($height < 100 || $height > 250 || $weight < 20 || $weight > 250) {
    echo json_encode(['error' => 'Please enter valid height (cm) and weight (kg).']);
    exit;
}

$bmi = round($weight / (($height / 100) ** 2), 1);

$prompt = "You are a Pakistani fashion sizing expert for Kapraa.
A customer wants to buy a {$category} from {$brand}.

Customer details:
- Gender: {$gender}
- Height: {$height} cm
- Weight: {$weight} kg
- BMI: {$bmi}
- Age: {$age}
- Body Type: {$bodyType}
- Available sizes for this product: {$sizes}

Give a precise size recommendation for Pakistani clothing (lawn suits, kameez, shalwar sets). Be direct and helpful.

Reply in this exact JSON format only — no extra text, no markdown:
{\"recommended_size\":\"M\",\"confidence\":\"High\",\"fit_tip\":\"One short sentence about fit for this body type\",\"size_note\":\"One short brand/category specific note\",\"bmi_category\":\"Normal\"}";

$payload = [
    'contents'          => [['parts' => [['text' => $prompt]]]],
    'generationConfig'  => ['temperature' => 0.2, 'maxOutputTokens' => 300],
];

$ch = curl_init(SR_GEMINI_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$raw      = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$raw || $httpCode !== 200) {
    echo json_encode(['error' => 'AI service unavailable. Please try again.']);
    exit;
}

$resp   = json_decode($raw, true);
$text   = $resp['candidates'][0]['content']['parts'][0]['text'] ?? '';
$text   = preg_replace('/```json\s*|\s*```/', '', trim($text));
$result = json_decode($text, true);

if (!$result || !isset($result['recommended_size'])) {
    echo json_encode(['error' => 'Could not parse AI response. Please try again.']);
    exit;
}

$result['bmi'] = $bmi;
echo json_encode($result);
