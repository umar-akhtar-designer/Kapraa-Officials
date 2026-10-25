<?php
// Mini JSON endpoint for loyalty popup — returns top 5 players
require_once 'admin/include/config.php';
header('Content-Type: application/json; charset=utf-8');
global $connection_database;

$demoUsers = [
    ['name'=>'Fatima Malik','score'=>87],
    ['name'=>'Zara Ahmed','score'=>72],
    ['name'=>'Sara Khan','score'=>65],
    ['name'=>'Nadia Hussain','score'=>45],
    ['name'=>'Ayesha Tariq','score'=>30],
];

$realRes = mysqli_query($connection_database,
    "SELECT ls.score, c.name FROM loyalty_scores ls
     LEFT JOIN customers c ON c.id = ls.customer_id
     ORDER BY ls.score DESC LIMIT 5");
$real = [];
if ($realRes) while ($r = mysqli_fetch_assoc($realRes)) $real[] = ['name'=>$r['name'],'score'=>(int)$r['score']];

$players = $real;
if (count($players) < 5) {
    $needed = 5 - count($players);
    foreach (array_slice($demoUsers, 0, $needed) as $d) $players[] = $d;
}

echo json_encode(['players' => $players], JSON_UNESCAPED_UNICODE);
