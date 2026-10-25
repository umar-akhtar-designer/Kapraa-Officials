<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = (strpos($host,'localhost')!==false||strpos($host,'127.0.0.1')!==false||strpos($host,'192.168.')!==false);
$base    = $isLocal ? 'http://'.$host.'/kapraa' : 'https://'.$host;
if ($isLocal) {
    $conn = mysqli_connect('localhost', 'root', '', 'kapraa');
} else {
    $conn = mysqli_connect('localhost', 'u167258434_kapraa', 'Kapraa@2026', 'u167258434_kapraa');
}
if (!$conn) { echo json_encode(['success'=>false,'error'=>mysqli_connect_error()]); exit; }
mysqli_set_charset($conn, 'utf8');

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base = $isLocal ? 'http://'.$host.'/kapraa' : 'https://'.$host;

// ── Try App Banner first (1080×1080 square banners for mobile app)
$result = mysqli_query($conn,
    "SELECT b.image, br.slug, br.name AS brand_name
     FROM banner b
     LEFT JOIN brand br ON b.category = br.id
     WHERE b.banner_type = 'App Banner'
     ORDER BY b.id ASC"
);

$appBanners = [];
while ($r = mysqli_fetch_assoc($result)) {
    $appBanners[] = [
        'image_url'  => $base . '/admin/images/banner/' . $r['image'],
        'brand_slug' => $r['slug'] ?? '',
        'brand_name' => $r['brand_name'] ?? '',
    ];
}

// ── If no App Banners uploaded yet, fall back to Home Banner 1 Mobile
if (empty($appBanners)) {
    $result = mysqli_query($conn,
        "SELECT b.image, br.slug, br.name AS brand_name
         FROM banner b
         LEFT JOIN brand br ON b.category = br.id
         WHERE b.banner_type = 'Home Banner 1' AND b.banner_for = 'For Mobile'
         ORDER BY b.id ASC"
    );
    while ($r = mysqli_fetch_assoc($result)) {
        $appBanners[] = [
            'image_url'  => $base . '/admin/images/banner/' . $r['image'],
            'brand_slug' => $r['slug'] ?? '',
            'brand_name' => $r['brand_name'] ?? '',
        ];
    }
}

// ── Also return desktop banners for website use
$result2  = mysqli_query($conn,
    "SELECT b.image, b.banner_for, br.slug, br.name AS brand_name
     FROM banner b
     LEFT JOIN brand br ON b.category = br.id
     WHERE b.banner_type = 'Home Banner 1'
     ORDER BY b.id ASC"
);
$desktop = []; $mobile = [];
while ($r = mysqli_fetch_assoc($result2)) {
    $item = [
        'image_url'  => $base . '/admin/images/banner/' . $r['image'],
        'brand_slug' => $r['slug'] ?? '',
        'brand_name' => $r['brand_name'] ?? '',
    ];
    if ($r['banner_for'] === 'For Desktop') $desktop[] = $item;
    else $mobile[] = $item;
}

echo json_encode([
    'success'     => true,
    'app'         => $appBanners,   // ← App uses this
    'desktop'     => $desktop,
    'mobile'      => $mobile,
]);
