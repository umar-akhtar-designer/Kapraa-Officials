<?php
$host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = (
    strpos($host, 'localhost')  !== false ||
    strpos($host, '127.0.0.1') !== false ||
    strpos($host, '192.168.')   !== false
);

if ($isLocal) {
    // Local XAMPP
    $base    = 'http://' . $host . '/kapraa';
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'kapraa';
} else {
    // Hostinger live — kapraa.pk
    $base    = 'https://' . $host;
    $db_host = 'localhost';
    $db_user = 'u167258434_kapraa';
    $db_pass = 'Kapraa@2026';
    $db_name = 'u167258434_kapraa';
}