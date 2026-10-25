
<?php
// Kapraa Virtual Try-On — Configuration

// YouCam API key
define('YOUCAM_API_KEY', 'sk-lQELVjfOEdWlHfBug3YfpasdgiTfbLiZ9BI3RsIUiu9MhH-AzHy7I5witJ93UpvT');

define('TRYON_MAX_UPLOAD_BYTES', 10 * 1024 * 1024);
define('TRYON_POLL_INTERVAL_SEC', 3);
define('TRYON_POLL_MAX_TRIES', 14);

// Garment category detection
function detectGarmentCategory(string $productName): string {
    $n = mb_strtolower($productName);
    if (preg_match('/trouser|bottom|pant|pajama|shalwar/', $n)) return 'lower_body';
    if (preg_match('/full|abaya|coat|jacket|shawl/', $n)) return 'full_body';
    return 'upper_body';
}
