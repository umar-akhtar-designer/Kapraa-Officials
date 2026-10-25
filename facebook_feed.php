<?php
/**
 * Facebook / Meta Product Catalog Feed — Kapraa
 * ---------------------------------------------------------
 * Outputs an XML product feed (RSS 2.0 + Facebook product namespace)
 * that Facebook Commerce Manager fetches on a schedule.
 *
 * INSTALL:
 * 1. Upload this file to your site root, next to index.php
 *    (so the URL is: https://kapraa.pk/facebook_feed.php)
 * 2. Paste that URL into Commerce Manager's "Data feed URL" field.
 * 3. Set the fetch schedule to Daily.
 *
 * This file re-uses your existing DB connection setup — it does NOT
 * duplicate your header/footer, since Facebook only wants raw XML.
 */

// Reuse your existing DB config — adjust the path if needed to match
// however admin/include/config.php is included elsewhere on your site.
include 'admin/include/config.php';

// Serve as XML, not HTML
header('Content-Type: application/xml; charset=utf-8');

// Path where product images live (same as used in product-details.php)
$imagePath = "/admin/images/product/";

// Pull all active products with their category/brand names
$feedQuery = "SELECT
        p.*, 
        c.name  AS category_name,
        b.name  AS brand_name
    FROM products p
    LEFT JOIN category c ON p.category = c.id
    LEFT JOIN brand    b ON p.brand    = b.id
    WHERE p.is_active != 0";

$result = $connection_database->query($feedQuery);

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
<channel>
    <title>Kapraa Product Feed</title>
    <link><?php echo htmlspecialchars($url_domain); ?></link>
    <description>Kapraa — Pakistan's Premium Clothing Store</description>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($product = $result->fetch_assoc()): ?>
            <?php
                // ── Basic fields ──────────────────────────────
                $id          = $product['id'];
                $name        = trim($product['name']);
                $description = trim(strip_tags(html_entity_decode($product['description'] ?? '')));
                if (empty($description)) {
                    $description = $name; // Facebook requires a non-empty description
                }
                $slug = $product['slug'];
                $link = rtrim($url_domain, '/') . '/product-details.php?slug=' . urlencode($slug);

                // ── Price logic (matches your product-details.php logic) ──
                $price              = (float) $product['price'];
                $price_after_disc   = (float) ($product['price_after_discount'] ?? 0);
                $hasDiscount        = !empty($price_after_disc) && $price_after_disc < $price && $price > 0;
                $displayPrice       = $price; // Facebook wants the ORIGINAL price in <g:price>
                $salePrice          = $hasDiscount ? $price_after_disc : null;

                // ── Availability / Stock ────────────────────────
                $stock = $product['stock'];
                $availability = ($stock == 'Out Stock') ? 'out of stock' : 'in stock';

                // ── Images ──────────────────────────────────────
                $images = !empty($product['image']) ? explode(',', $product['image']) : [];
                $images = array_map('trim', $images);
                $images = array_filter($images);
                $mainImage = !empty($images[0]) ? $images[0] : '';
                $mainImageUrl = $mainImage ? rtrim($url_domain, '/') . $imagePath . $mainImage : '';

                // Skip products with no image — Facebook rejects these
                if (empty($mainImageUrl)) continue;

                $additionalImages = array_slice($images, 1, 9); // up to 10 total images allowed

                // ── Brand / Category ─────────────────────────────
                $brand    = !empty($product['brand_name']) ? $product['brand_name'] : 'Kapraa';
                $category = !empty($product['category_name']) ? $product['category_name'] : 'Clothing';
            ?>
    <item>
        <g:id><?php echo htmlspecialchars($id); ?></g:id>
        <g:title><?php echo htmlspecialchars($name); ?></g:title>
        <g:description><?php echo htmlspecialchars(mb_substr($description, 0, 5000)); ?></g:description>
        <g:link><?php echo htmlspecialchars($link); ?></g:link>
        <g:image_link><?php echo htmlspecialchars($mainImageUrl); ?></g:image_link>
        <?php foreach ($additionalImages as $img): ?>
        <g:additional_image_link><?php echo htmlspecialchars(rtrim($url_domain, '/') . $imagePath . $img); ?></g:additional_image_link>
        <?php endforeach; ?>
        <g:availability><?php echo $availability; ?></g:availability>
        <g:condition>new</g:condition>
        <g:price><?php echo number_format($displayPrice, 2, '.', ''); ?> PKR</g:price>
        <?php if ($hasDiscount): ?>
        <g:sale_price><?php echo number_format($salePrice, 2, '.', ''); ?> PKR</g:sale_price>
        <?php endif; ?>
        <g:brand><?php echo htmlspecialchars($brand); ?></g:brand>
        <g:product_type><?php echo htmlspecialchars($category); ?></g:product_type>
        <g:google_product_category>1604</g:google_product_category>
        <?php if (!empty($product['sku'])): ?>
        <g:mpn><?php echo htmlspecialchars($product['sku']); ?></g:mpn>
        <?php endif; ?>
    </item>
        <?php endwhile; ?>
    <?php endif; ?>
</channel>
</rss>