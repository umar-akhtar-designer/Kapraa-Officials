<?php
header('Content-Type: application/xml; charset=utf-8');
require_once __DIR__ . '/admin/include/config.php';

$base = 'https://kapraa.pk';

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

function url($loc, $priority = '0.7', $changefreq = 'weekly') {
    echo '  <url><loc>' . htmlspecialchars($loc) . '</loc><changefreq>' . $changefreq . '</changefreq><priority>' . $priority . '</priority></url>' . "\n";
}

// ── Homepage ─────────────────────────────────────────────────
url($base . '/', '1.0', 'daily');

// ── Static pages ─────────────────────────────────────────────
foreach (['about', 'contact', 'blog', 'faqs', 'privacy-policy', 'term-condition'] as $page) {
    url($base . '/' . $page, '0.5', 'monthly');
}

// ── Category pages ───────────────────────────────────────────
$cats = $connection_database->query("SELECT slug FROM category WHERE slug IS NOT NULL AND slug != ''");
if ($cats) while ($c = $cats->fetch_assoc()) {
    url($base . '/products/category/' . $c['slug'], '0.9', 'daily');
}

// ── Subcategory pages ────────────────────────────────────────
$subcats = $connection_database->query("SELECT slug FROM subcategory WHERE slug IS NOT NULL AND slug != ''");
if ($subcats) while ($s = $subcats->fetch_assoc()) {
    url($base . '/products/subcategory/' . $s['slug'], '0.8', 'daily');
}

// ── Brand pages (only real, current brands) ──────────────────
$brands = $connection_database->query("SELECT slug FROM brand WHERE slug IS NOT NULL AND slug != ''");
if ($brands) while ($b = $brands->fetch_assoc()) {
    url($base . '/brand/' . $b['slug'], '0.8', 'weekly');
}

// ── Product pages (only active, currently-listed products) ───
$products = $connection_database->query("SELECT slug FROM products WHERE is_active != 0 AND slug IS NOT NULL AND slug != ''");
if ($products) while ($p = $products->fetch_assoc()) {
    url($base . '/product/' . $p['slug'], '0.7', 'weekly');
}

echo '</urlset>';
