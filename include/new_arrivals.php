<?php
    $brandQuery = selectQuery("SELECT * FROM brand WHERE brand_main = 'New Arrivals' order by id desc limit 20");
    $brands = [];
    if ($brandQuery['status'] == true) {
        foreach ($brandQuery['data'] as $b) {
            if (!empty($b['image']) && file_exists(__DIR__ . '/../admin/images/brand/' . $b['image'])) {
                $brands[] = $b;
                if (count($brands) >= 5) break;
            }
        }
    }
    if (count($brands) > 0):
        $hero   = $brands[0];
        $grid   = array_slice($brands, 1, 4);
?>
<style>
/* ── New Arrivals — 3:4 portrait layout ───────────────── */
.na-section { width: 100%; }

/* Hero card */
.na-hero-wrap {
    position: relative;
    overflow: hidden;
    border-radius: 12px;
    display: block;
    text-decoration: none;
    aspect-ratio: 3 / 4;
    width: 100%;
}
.na-hero-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.45s ease;
}
.na-hero-wrap:hover img { transform: scale(1.05); }
.na-hero-overlay {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.75) 0%, rgba(0,0,0,0) 55%);
    padding: 48px 20px 22px;
    border-radius: 0 0 12px 12px;
}
.na-hero-badge {
    display: inline-block;
    background: #fff;
    color: #111;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.2px;
    padding: 4px 10px;
    border-radius: 4px;
    margin-bottom: 8px;
    text-transform: uppercase;
}
.na-hero-name {
    color: #fff;
    font-size: 22px;
    font-weight: 700;
    margin: 0 0 4px;
    line-height: 1.2;
}
.na-hero-tagline {
    color: rgba(255,255,255,0.82);
    font-size: 13px;
    margin: 0;
}

/* Grid cards — same 3:4 ratio, so 2 rows = hero height */
.na-grid-wrap {
    position: relative;
    overflow: hidden;
    border-radius: 10px;
    display: block;
    text-decoration: none;
    aspect-ratio: 3 / 4;
    width: 100%;
}
.na-grid-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.4s ease;
}
.na-grid-wrap:hover img { transform: scale(1.07); }
.na-grid-overlay {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.70) 0%, rgba(0,0,0,0) 65%);
    padding: 30px 10px 12px;
    border-radius: 0 0 10px 10px;
}
.na-grid-name {
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    margin: 0 0 5px;
    line-height: 1.2;
}
.na-ship-badge {
    display: inline-block;
    background: #fff;
    color: #111;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1px;
    padding: 3px 8px;
    border-radius: 3px;
    text-transform: uppercase;
}

@media (max-width: 767px) {
    .na-hero-wrap  { border-radius: 10px; }
    .na-hero-name  { font-size: 18px; }
}
</style>

<div class="container-fluid na-section">
    <div class="row g-3 align-items-start">

        <!-- Hero card — left half -->
        <div class="col-12 col-md-6">
            <a href="<?php echo $url_domain; ?>/brand/<?php echo htmlspecialchars($hero['slug']); ?>"
               class="na-hero-wrap">
                <?php $hpos = in_array($hero['image_position'] ?? '', ['top','center','bottom']) ? $hero['image_position'] : 'center'; ?>
                <img src="admin/images/brand/<?php echo htmlspecialchars($hero['image']); ?>"
                     alt="<?php echo htmlspecialchars($hero['name']); ?>"
                     style="object-position:<?php echo $hpos; ?> center;">
                <div class="na-hero-overlay">
                    <span class="na-hero-badge">Express Shipping</span>
                    <h3 class="na-hero-name"><?php echo htmlspecialchars($hero['name']); ?></h3>
                    <?php if(!empty($hero['tag_line'])): ?>
                    <p class="na-hero-tagline"><?php echo htmlspecialchars($hero['tag_line']); ?></p>
                    <?php endif; ?>
                </div>
            </a>
        </div>

        <!-- 2×2 grid — right half -->
        <div class="col-12 col-md-6">
            <div class="row g-3">
                <?php foreach($grid as $item): ?>
                <div class="col-6">
                    <a href="<?php echo $url_domain; ?>/brand/<?php echo htmlspecialchars($item['slug']); ?>"
                       class="na-grid-wrap">
                        <?php $gpos = in_array($item['image_position'] ?? '', ['top','center','bottom']) ? $item['image_position'] : 'center'; ?>
                        <img src="admin/images/brand/<?php echo htmlspecialchars($item['image']); ?>"
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             style="object-position:<?php echo $gpos; ?> center;">
                        <div class="na-grid-overlay">
                            <p class="na-grid-name"><?php echo htmlspecialchars($item['name']); ?></p>
                            <span class="na-ship-badge">Express Shipping</span>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<?php endif; ?>
