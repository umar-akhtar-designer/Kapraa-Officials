<?php
    // Query for Home Banner 2 with category slug
    $bannersQuery = "SELECT b.image, b.banner_for, c.slug 
                     FROM banner b 
                     LEFT JOIN brand c ON b.category = c.id 
                     WHERE b.banner_type = 'Home Banner 2'";
    $bannersResult = mysqli_query($connection_database, $bannersQuery);

    if(mysqli_num_rows($bannersResult) > 0) {
        $desktopBanners = [];
        $mobileBanners = [];

        // Separate banners by type
        while($banner = mysqli_fetch_assoc($bannersResult)) {
            if($banner['banner_for'] == 'For Desktop') {
                $desktopBanners[] = $banner;
            } elseif($banner['banner_for'] == 'For Mobile') {
                $mobileBanners[] = $banner;
            }
        }
?>

<section class="collection-box tle-bold section">
    <div class="container-fluid">
        <?php
            // Display desktop banner
            if(!empty($desktopBanners)) {
                foreach($desktopBanners as $banner) {
                    // Generate category link using slug
                    $categorySlug = !empty($banner['slug']) ? $banner['slug'] : '';
                    $categoryLink = !empty($categorySlug) ? $url_domain . '/products/brand/' . htmlspecialchars($categorySlug) : '#';
                    
                    echo '<a href="'.$categoryLink.'">';
                    echo '    <img src="admin/images/banner/'.htmlspecialchars($banner['image']).'" alt="" class="img-fluid d-none d-md-block w-100 h-100">';
                    echo '</a>';
                }
            }
            
            // Display mobile banner
            if(!empty($mobileBanners)) {
                foreach($mobileBanners as $banner) {
                    // Generate category link using slug
                    $categorySlug = !empty($banner['slug']) ? $banner['slug'] : '';
                    $categoryLink = !empty($categorySlug) ? $url_domain . '/products/brand/' . htmlspecialchars($categorySlug) : '#';
                    
                    echo '<a href="'.$categoryLink.'">';
                    echo '    <img src="admin/images/banner/'.htmlspecialchars($banner['image']).'" alt="" class="img-fluid d-block d-md-none w-100 h-100">';
                    echo '</a>';
                }
            }
        ?>
    </div>
</section>
<?php
    }
?>