<?php
// ============================================================
// dashboard_stats.php — Sales chart data endpoint
// Returns JSON: { labels[], orders_count[], revenue[] }
// ============================================================
require 'include/config.php';
if (empty($_SESSION['WEBSITE-LOGIN'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

global $connection_database;

$period   = $_GET['period'] ?? '7d';
$labels   = [];
$orders   = [];
$revenue  = [];

if ($period === '7d') {
    // Last 7 days (today inclusive)
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $labels[] = date('D d', strtotime($date)); // e.g. "Mon 14"

        $row = mysqli_fetch_assoc(mysqli_query($connection_database,
            "SELECT COUNT(*) AS cnt, COALESCE(SUM(total_amount),0) AS rev
             FROM orders
             WHERE DATE(created_at) = '{$date}'"
        ));
        $orders[]  = (int)($row['cnt'] ?? 0);
        $revenue[] = round((float)($row['rev'] ?? 0), 2);
    }

} elseif ($period === '30d') {
    // Last 30 days grouped by day
    for ($i = 29; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $labels[] = date('d M', strtotime($date)); // e.g. "14 Apr"

        $row = mysqli_fetch_assoc(mysqli_query($connection_database,
            "SELECT COUNT(*) AS cnt, COALESCE(SUM(total_amount),0) AS rev
             FROM orders
             WHERE DATE(created_at) = '{$date}'"
        ));
        $orders[]  = (int)($row['cnt'] ?? 0);
        $revenue[] = round((float)($row['rev'] ?? 0), 2);
    }

} else {
    // 12m — last 12 months grouped by month
    for ($i = 11; $i >= 0; $i--) {
        $year  = date('Y', strtotime("-{$i} months"));
        $month = date('m', strtotime("-{$i} months"));
        $labels[] = date('M Y', strtotime("-{$i} months")); // e.g. "Apr 2025"

        $row = mysqli_fetch_assoc(mysqli_query($connection_database,
            "SELECT COUNT(*) AS cnt, COALESCE(SUM(total_amount),0) AS rev
             FROM orders
             WHERE YEAR(created_at) = '{$year}' AND MONTH(created_at) = '{$month}'"
        ));
        $orders[]  = (int)($row['cnt'] ?? 0);
        $revenue[] = round((float)($row['rev'] ?? 0), 2);
    }
}

echo json_encode([
    'labels'       => $labels,
    'orders_count' => $orders,
    'revenue'      => $revenue,
], JSON_UNESCAPED_UNICODE);
