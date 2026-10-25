<?php
// Social Proof Notifications — JSON endpoint
require_once 'admin/include/config.php';
header('Content-Type: application/json; charset=utf-8');
global $connection_database;

// Ensure tables exist
mysqli_query($connection_database, "CREATE TABLE IF NOT EXISTS social_proof_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    custom_city VARCHAR(100) DEFAULT '',
    custom_time_label VARCHAR(60) DEFAULT '',
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
mysqli_query($connection_database, "CREATE TABLE IF NOT EXISTS social_proof_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(60) UNIQUE,
    setting_value VARCHAR(255)
)");

// Load settings
$settings = [];
$sRes = mysqli_query($connection_database, "SELECT setting_key, setting_value FROM social_proof_settings");
if ($sRes) while ($s = mysqli_fetch_assoc($sRes)) $settings[$s['setting_key']] = $s['setting_value'];

$enabled  = (int)($settings['enabled']          ?? 1);
$delay    = (int)($settings['delay_seconds']    ?? 5);
$duration = (int)($settings['duration_seconds'] ?? 4);

if (!$enabled) {
    echo json_encode(['enabled' => false, 'notifications' => [], 'delay' => $delay, 'duration' => $duration]);
    exit;
}

$cities = ['Karachi','Lahore','Islamabad','Rawalpindi','Faisalabad','Multan','Peshawar','Quetta','Sialkot','Gujranwala'];
$timeOptions = ['just now','1 min ago','2 min ago','3 min ago','5 min ago','7 min ago','10 min ago','15 min ago','20 min ago'];

$res = mysqli_query($connection_database,
    "SELECT sp.id, sp.custom_city, sp.custom_time_label,
            p.name AS product_name, p.image AS product_image,
            p.price, p.dis_amount, p.slug,
            b.name AS brand_name
     FROM social_proof_notifications sp
     LEFT JOIN products p ON p.id = sp.product_id
     LEFT JOIN brand b ON b.id = p.brand
     WHERE sp.is_active = 1
     ORDER BY sp.sort_order ASC, sp.id DESC"
);

$notifications = [];
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
        $img = $r['product_image'] ?? '';
        $imgSrc = '';
        if ($img) {
            $imgSrc = str_starts_with($img, 'http') ? $img : $url_domain . '/admin/images/product/' . $img;
        }
        $notifications[] = [
            'product_name'  => $r['product_name']  ?? 'Product',
            'brand_name'    => $r['brand_name']     ?? '',
            'product_image' => $imgSrc,
            'slug'          => $r['slug'] ?? '',
            'city'          => $r['custom_city']       ?: $cities[array_rand($cities)],
            'time_label'    => $r['custom_time_label'] ?: $timeOptions[array_rand($timeOptions)],
        ];
    }
}

echo json_encode([
    'enabled'       => true,
    'notifications' => $notifications,
    'delay'         => $delay,
    'duration'      => $duration,
]);
