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

$result = mysqli_query($conn,
    "SELECT id, name, slug, image FROM category ORDER BY priority ASC, id ASC"
);
$cats = [];
while ($r = mysqli_fetch_assoc($result)) {
    $r['image_url'] = $r['image'] ? $base . '/admin/images/category/' . $r['image'] : null;
    $cats[] = $r;
}
echo json_encode(['success'=>true,'data'=>$cats]);
