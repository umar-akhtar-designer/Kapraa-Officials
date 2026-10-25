<?php
  include 'include/head.php';
  include 'include/navbar.php';
  
  // Get product slug from URL - handle both formats
  if (isset($_GET['slug'])) {
      $slug = $_GET['slug'];
  } else {
      // Try to get slug from PATH_INFO (for URL rewriting)
      $request_uri = $_SERVER['REQUEST_URI'];
      if (strpos($request_uri, 'slug=') !== false) {
          $parts = explode('slug=', $request_uri);
          if (isset($parts[1])) {
              $slug = $parts[1];
          }
      } else {
          // Redirect to products page if no slug provided
          header("Location: " . $url_domain . "/products");
          exit();
      }
  }
  
  // Clean the slug (remove any query parameters)
  $slug = preg_replace('/\?.*/', '', $slug);
  $slug = trim($slug);
  // Sanitize slug against SQL injection before using in query
  $slug = $connection_database->real_escape_string($slug);

  // Fetch product details based on slug
  $productQuery = selectQuery("SELECT
      p.*,
      c.name as category_name,
      sc.name as subcategory_name,
      b.name as brand_name,
      c.slug as category_slug,
      sc.slug as subcategory_slug,
      b.slug as brand_slug
      FROM products p
      LEFT JOIN category c ON p.category = c.id
      LEFT JOIN subcategory sc ON p.sub_category = sc.id
      LEFT JOIN brand b ON p.brand = b.id
      WHERE p.slug = '$slug'");
  
  if (!$productQuery['status'] || empty($productQuery['data'])) {
      // Product not found, redirect to products page
      header("Location: products");
      exit();
  }
  
  $product = $productQuery['data'][0];
  
  // Extract product data
  $productID = $product['id'];
  $name = $product['name'];
  $description = $product['description'];
  $category_name = $product['category_name'];
  $subcategory_name = $product['subcategory_name'];
  $brand_name = $product['brand_name'];
  $category_slug = $product['category_slug'];
  $subcategory_slug = $product['subcategory_slug'];
  $brand_slug = $product['brand_slug'];
  $price = $product['price'];
  $dis_amount = $product['dis_amount'];
  $price_after_discount = $product['price_after_discount'];
  $sale = $product['sale'];
  $stock = $product['stock'];
  $sku = $product['sku'];
  $size = $product['size'];
  $color = $product['color']; // Added color field
  $link = $product['link'];
  $order_qty = $product['order_qty'];
  $shipp_cost = $product['shipp_cost'];
  $setsuite_price = $product['setsuite_price'];
  $setsuite_discount = isset($product['setsuite_discount']) ? $product['setsuite_discount'] : 0;
  $setsuite_quantity = isset($product['setsuite_quantity']) ? $product['setsuite_quantity'] : 0;
  $image = $product['image'];
  $stitched_price = $product['stitched_price'];
  $category_id = $product['category']; // Added for related products
  $subcategory_id = $product['sub_category']; // Added for related products
  $brand_id = $product['brand']; // Added for related products
  $seo_title = !empty($product['seo_title']) ? $product['seo_title'] : $name;
  $seo_des = !empty($product['seo_des']) ? $product['seo_des'] : substr(strip_tags($description), 0, 160);
  
  // Calculate set suite discounted price
  $setsuite_price_after_discount = $setsuite_price;
  if (!empty($setsuite_discount) && $setsuite_discount > 0) {
      $setsuite_price_after_discount = $setsuite_price - $setsuite_discount;
  }
  
  // Process images
  $imagePath = "/admin/images/product/";
  $mainImage = "";
  $allImages = [];
  $imageUrls = [];
  
  if (!empty($image)) {
      $imgs = explode(',', $image);
      foreach ($imgs as $img) {
          $img = trim($img);
          if (!empty($img)) {
              $allImages[] = $img;
              $imageUrls[] = $url_domain . $imagePath . $img;
          }
      }
      
      // Set main image
      $mainImage = !empty($allImages[0]) ? $allImages[0] : "";
  }
  
  // If no images, add default
  if (empty($allImages)) {
      $mainImage = "waiting images.jpeg";
      $allImages[] = "waiting images.jpeg";
      $imageUrls[] = $url_domain . "/images/waiting images.jpeg";
  }
  
  // Get size names by IDs
  function getSizeNamesByIds($sizeIds) {
      if (empty($sizeIds)) return '';
      
      $sizeArray = explode(',', $sizeIds);
      $sizeNames = [];
      
      foreach ($sizeArray as $sizeId) {
          $sizeId = trim($sizeId);
          if (!empty($sizeId)) {
              $query = selectQuery("SELECT name FROM size WHERE id = '$sizeId'");
              if ($query['status'] && !empty($query['data'])) {
                  $sizeNames[] = $query['data'][0]['name'];
              }
          }
      }
      
      return $sizeNames;
  }
  
  // Get color details by IDs
  function getColorDetailsByIds($colorIds) {
      if (empty($colorIds)) return [];
      
      $colorArray = explode(',', $colorIds);
      $colorDetails = [];
      
      foreach ($colorArray as $colorId) {
          $colorId = trim($colorId);
          if (!empty($colorId)) {
              $query = selectQuery("SELECT name, color_code FROM color WHERE id = '$colorId' AND status = 1");
              if ($query['status'] && !empty($query['data'])) {
                  $colorDetails[] = $query['data'][0];
              }
          }
      }
      
      return $colorDetails;
  }
  
  $sizeNamesArray = getSizeNamesByIds($size);
  $sizeNames = !empty($sizeNamesArray) ? implode(', ', $sizeNamesArray) : '';
  
  // Get color details
  $colorDetailsArray = getColorDetailsByIds($color);
  
  // Calculate discount percentage
  $discount_percent = 0;
  if (!empty($price_after_discount) && $price_after_discount < $price && $price > 0) {
      $discount_percent = round((($price - $price_after_discount) / $price) * 100);
  }
  
  // Calculate set suite discount percentage
  $set_suite_discount_percent = 0;
  if (!empty($setsuite_discount) && $setsuite_discount > 0 && $setsuite_price > 0) {
      $set_suite_discount_percent = round(($setsuite_discount / $setsuite_price) * 100);
  }
  
  // Function to get first 3 lines of description
  function getFirstLines($text, $numLines = 3) {
      $lines = explode("\n", $text);
      $firstLines = array_slice($lines, 0, $numLines);
      return implode("\n", $firstLines);
  }
  
  // Clean description - remove HTML tags and decode HTML entities
  function cleanDescription($text) {
      // Remove all HTML tags
      $text = strip_tags($text);
      // Decode HTML entities
      $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
      // Trim whitespace
      $text = trim($text);
      return $text;
  }
  
  $cleanDescription = cleanDescription($description);
  $shortDescription = getFirstLines($cleanDescription, 3);
  $fullDescription = $cleanDescription;
  
  // Process video link
  $video_url = '';
  $video_id = '';
  if (!empty($link)) {
      $video_url = trim($link);
      // Extract YouTube video ID
      if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $video_url, $matches)) {
          $video_id = $matches[1];
      } elseif (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $video_url, $matches)) {
          $video_id = $matches[1];
      }
  }
  
  // Set page title and meta description
  echo '<title>' . htmlspecialchars($seo_title) . ' - ' . $site_title . '</title>';
  echo '<meta name="description" content="' . htmlspecialchars($seo_des) . '">';

  // Use session to maintain consistent visitor_id
   if (!isset($_SESSION['visitor_id'])) {
        if (isset($_COOKIE['visitor_id'])) {
            $_SESSION['visitor_id'] = $_COOKIE['visitor_id'];
        } else {
            $_SESSION['visitor_id'] = uniqid('visitor_', true);
            setcookie('visitor_id', $_SESSION['visitor_id'], time() + (365 * 24 * 60 * 60), "/");
        }
    }

    $visitor_id = $_SESSION['visitor_id'];

    // Record or update property view
    $check_view = $connection_database->prepare("SELECT id FROM product_views WHERE visitor_id = ? AND productID = ?");
    $check_view->bind_param("si", $visitor_id, $productID);
    $check_view->execute();
    $existing_view = $check_view->get_result();

    if ($existing_view->num_rows == 0) {
        // Insert new view
        $stmt = $connection_database->prepare("INSERT INTO product_views (visitor_id, productID) VALUES (?, ?)");
        $stmt->bind_param("si", $visitor_id, $productID);
        $stmt->execute();
    } else {
        // Update view time
        $update_stmt = $connection_database->prepare("UPDATE product_views SET view_time = NOW() WHERE visitor_id = ? AND productID = ?");
        $update_stmt->bind_param("si", $visitor_id, $productID);
        $update_stmt->execute();
    }
?>
<style>
    .size-tag {
        border: 1px solid #DFDCE7;
        color: black;
        padding: 5px 10px;
        border-radius:5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        display: inline-block;
        margin: 0 5px 5px 0;
    }
    
    .size-tag.selected {
        border: 2px solid black !important;
    }
    
    /* Color tag styles */
    .color-tag {
        border: 1px solid #DFDCE7;
        padding: 5px 10px;
        border-radius:5px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        margin: 0 5px 5px 0;
        position: relative;
    }
    
    .color-tag.selected {
        border: 2px solid black !important;
        box-shadow: 0 0 0 2px rgba(0,0,0,0.1);
    }
    
    .color-swatch {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        margin-right: 8px;
        border: 1px solid #ddd;
    }
    
    .color-name {
        font-size: 14px;
    }
    
    /* Selection mode toggle */
    .size-selection-mode {
        margin-bottom: 10px;
        font-size: 14px;
    }
    
    .size-selection-mode label {
        margin-right: 15px;
        cursor: pointer;
    }
    
    .size-selection-mode input[type="radio"] {
        margin-right: 5px;
    }
    
    /* Add to cart button animation */
    .add-to-cart-btn {
        transition: all 0.3s ease;
        background: black;
        border: none;
        border-radius:5px;
    }

    /* Buy Now button */
    .buy-now-btn {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        color: #fff;
        border: none;
        border-radius: 5px;
        font-weight: 700;
        letter-spacing: 0.3px;
        transition: all 0.3s ease;
    }
    .buy-now-btn:hover {
        background: linear-gradient(135deg, #c0392b, #a93226);
        color: #fff;
        transform: translateY(-1px);
    }
    /* Social sharing buttons */
    .social-sharing a {
        transition: all 0.3s ease;
    }

    .social-sharing a:hover {
        transform: translateY(-3px);
    }

    /* Add a nice header for the product details */
    .tab-container::before {
        content: "";
        display: block;
        width: 60px;
        height: 4px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        margin-bottom: 20px;
        border-radius: 2px;
    }
    
    /* Stitching total price styling */
    .stitching-total-price {
        font-weight: bold;
        color: #28a745;
        margin-top: 5px;
        display: none;
    }

    /* Responsive adjustments */
    @media (max-width: 767px) {
        .product-features {
            padding: 20px;
        }
        .tab-container::before {
            display: none;
        }
        
        /* Add line after mobile heading */
        .acor-ttl.active::after {
            content: "";
            display: block;
            width: 60px;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 10px 0 20px;
            border-radius: 2px;
        }
        
        /* Adjust spacing for mobile content */
        .tab-content {
            padding-top: 0;
        }
    }

    /* Enhanced price styling */
    .product-single__price {
        /* margin-top:10px; */
        /* margin-bottom: 15px; */
    }
    
    .discount-badge {
        font-size: 0.9rem;
        color: #28a745;
        font-weight: 600;
    }
    .related-product-price {
        font-size: 18px;
        font-weight: 700;
        color: #333;
        margin-bottom: 15px;
    }
    .related-product-price .old-price {
        text-decoration: line-through;
        color: #999;
        font-size: 14px;
        font-weight: 400;
        margin-right: 8px;
    }
    .related-product-price .discount {
        color: #e74c3c;
        font-size: 14px;
        font-weight: 500;
    }

    /* Notification styles */
    .notification-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
    }
    
    .notification {
        padding: 15px 20px;
        margin-bottom: 10px;
        border-radius: 4px;
        color: white;
        font-weight: bold;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        animation: slideIn 0.3s ease-out;
        display: flex;
        align-items: center;
    }
    
    .notification-success {
        background-color: #28a745;
    }
    
    .notification-error {
        background-color: #dc3545;
    }
    
    .notification i {
        margin-right: 10px;
        font-size: 20px;
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    .accordion-button:focus{
        border-color: white !important;
        box-shadow : none !important
    }
    .aaa {
        position: fixed;
        width: 120px;
        height: 200px;
        bottom: 150px;
        right: 15px;
        color: #FFF;
        border-radius: 10px;
        box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.4);
        z-index: 1000;
        display: flex;
    }
    .container iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 200px;
        border-radius: 10px;
    }
    /* Close button styles */
    .video-close-btn {
        position: absolute;
        top: 5px;
        right: 5px;
        width: 25px;
        height: 25px;
        background-color: rgba(0, 0, 0, 0.7);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        cursor: pointer;
        z-index: 1001;
        border: none;
        transition: all 0.3s ease;
    }
    .video-close-btn:hover {
        background-color: rgba(0, 0, 0, 0.9);
        transform: scale(1.1);
    }
    
    /* Set Suits button styles */
    .set-suits-btn {
        transition: all 0.3s ease;
    }
    
    .set-suits-btn.active {
        background-color: black !important;
    }

    .set-suits-btn:focus {
        outline: none !important; /* Remove focus outline */
        box-shadow: none !important; /* Remove focus box-shadow */
    }
    
    /* Price transition effect */
    .price-transition {
        transition: all 0.3s ease;
    }
    
    /* Hide elements transition */
    .hide-element {
        transition: opacity 0.3s ease, max-height 0.3s ease;
        opacity: 1;
        max-height: 500px;
        overflow: hidden;
    }
    
    .hide-element.hidden {
        opacity: 0;
        max-height: 0;
        margin: 0;
        padding: 0;
    }
    
    /* Set suite quantity message styling */
    .set-suite-message {
        padding: 2px 7px;
        background-color: #f8f9fa;
        border-left: 4px solid #1DAA61;
        margin-bottom: 0px !important;
        border-radius: 4px;
        font-weight: 500;
        color: #333;
        transition: all 0.3s ease;
        opacity: 0;
        max-height: 0;
        overflow: hidden;
        margin: 0;
        
    }
    
    .set-suite-message.show {
        opacity: 1;
        max-height: 100px;
        margin-bottom: 15px;
    }
    
    /* Button container to keep button at bottom */
    .button-container {
        margin-top: 4px;
        margin-bottom: 0px !important;
    }
    
    /* Stock Availability Indicator Styles */
    .stock-indicator {
        display: flex;
        align-items: center;
        margin-top: 8px;
        
    }
    
    .stock-icon {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 8px;
    }
</style>

<!-- Notification Container -->
<div id="notification-container" class="notification-container"></div>
<?php if (!empty($link)): ?>
    <div class="container video-container border aaa" id="productVideoContainer">
        <button class="video-close-btn" id="closeVideoBtn" title="Close Video">
            <i class="icon an an-times"></i>
        </button>
        <div class="">
            <?php if (!empty($video_id)): ?>
                <iframe id="productVideo" 
                    src="https://www.youtube.com/embed/<?php echo $video_id; ?>?rel=0&autoplay=1&loop=1&playlist=<?php echo $video_id; ?>" 
                    title="Product Video" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen></iframe>
            <?php elseif (!empty($video_url)): ?>
                <?php 
                // Initialize embed URL
                $embed_url = $video_url;
                $video_id_for_playlist = '';
                
                // Handle YouTube Shorts URLs
                if (strpos($video_url, 'youtube.com/shorts/') !== false) {
                    $video_id = substr($video_url, strpos($video_url, 'shorts/') + 7);
                    $video_id = strtok($video_id, '?');
                    $video_id_for_playlist = $video_id;
                    $embed_url = 'https://www.youtube.com/embed/' . $video_id;
                }
                // Handle regular YouTube watch URLs
                elseif (strpos($video_url, 'youtube.com/watch?v=') !== false) {
                    parse_str(parse_url($video_url, PHP_URL_QUERY), $params);
                    if (isset($params['v'])) {
                        $video_id_for_playlist = $params['v'];
                        $embed_url = 'https://www.youtube.com/embed/' . $params['v'];
                    }
                }
                
                // Check if URL already has query parameters
                $separator = (strpos($embed_url, '?') === false) ? '?' : '&';
                $embed_url .= $separator . 'autoplay=1&loop=1&rel=0';
                
                // Add playlist parameter if we have a video ID (required for loop to work)
                if (!empty($video_id_for_playlist)) {
                    $embed_url .= '&playlist=' . $video_id_for_playlist;
                }
                ?>
                <iframe id="productVideo" 
                    src="<?php echo $embed_url; ?>" 
                    title="Product Video" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen></iframe>
            <?php else: ?>
                <div class="alert alert-info text-center py-5">
                    <i class="icon an an-video-slash fs-1"></i>
                    <p class="mt-3">No video available for this product.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
<!-- Bredcrumbs -->
<div class="bredcrumbWrap bredcrumb-style2">
    <div class="container breadcrumbs">
        <a href="<?php echo $url_domain; ?>/index" title="Back to the home page">Home</a>
        <span aria-hidden="true">|</span>
        <?php if (!empty($category_name)): ?>
            <a href="<?php echo $url_domain; ?>/products/category/<?php echo $category_slug; ?>"><?php echo $category_name; ?></a>
            <span aria-hidden="true">|</span>
        <?php endif; ?>
        <?php if (!empty($subcategory_name)): ?>
            <a href="<?php echo $url_domain; ?>/products/subcategory/<?php echo $subcategory_slug; ?>"><?php echo $subcategory_name; ?></a>
            <span aria-hidden="true">|</span>
        <?php endif; ?>
        <span class="title-bold"><?php echo $name; ?></span>
    </div>
</div>
<!-- End Bredcrumbs -->

<div class="container">
    <!-- Main Content -->
    <div id="ProductSection-product-template" class="product-template__container prstyle2">
        <!-- #ProductSection product template -->
        <div class="product-single product-single-1">
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 col-12">
                    <div class="product-details-img product-single__photos bottom">
                        <!-- Product Images -->
                        <div class="zoompro-wrap product-zoom-right pl-20">
                            <div class="zoompro-span">
                                <img class="blur-up lazyload zoompro" 
                                     data-zoom-image="<?php echo $url_domain . $imagePath . $mainImage; ?>" 
                                     alt="<?php echo htmlspecialchars($name); ?>" 
                                     src="<?php echo $url_domain . $imagePath . $mainImage; ?>" />
                            </div>
                            <!-- Product Label - Only show if sale value exists -->
                            <?php if (!empty($sale)): ?>
                                <div class="product-labels"><span class="lbl on-sale"><?php echo $sale; ?></span></div>
                            <?php endif; ?>
                            <div class="product-buttons">
                                <a href="<?php echo $url_domain . $imagePath . $mainImage; ?>" class="btn prlightbox" data-bs-toggle="tooltip" data-bs-placement="left" title="Zoom Image">
                                    <i class="icon an an-expand-arrows-alt" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                        
                        <?php if (count($allImages) > 1): ?>
                        <div class="product-thumb product-thumb-1">
                            <div id="gallery" class="product-dec-slider-1 product-tab-left">
                                <?php foreach ($allImages as $index => $img): ?>
                                <a data-image="<?php echo $url_domain . $imagePath . $img; ?>" 
                                   data-zoom-image="<?php echo $url_domain . $imagePath . $img; ?>" 
                                   class="slick-slide <?php echo $index === 0 ? 'active' : ''; ?>" 
                                   tabindex="<?php echo $index; ?>">
                                    <img class="blur-up lazyload" 
                                         src="<?php echo $url_domain . $imagePath . $img; ?>" 
                                         alt="<?php echo htmlspecialchars($name) . ' - Image ' . ($index + 1); ?>" />
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="lightboximages">
                            <?php foreach ($imageUrls as $imgUrl): ?>
                            <a href="<?php echo $imgUrl; ?>" data-size="1462x2048"></a>
                            <?php endforeach; ?>
                        </div>
                        <!-- End Product Images -->
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 col-12">
                    <!-- Product Info -->
                    <div class="product-single__meta">
                        <h2 class="product-single__title"><?php echo htmlspecialchars($name); ?></h2>
                        <?php if (!empty($brand_name)): ?>
                            <p class="product-type mb-0 mt-3"><b> Brand : </b>
                                <a href="<?php echo $url_domain; ?>/products/brand/<?php echo $brand_slug; ?>" class="text-decoration-underline"><?php echo $brand_name; ?></a>
                            </p>
                        <?php endif; ?>
                        <!-- Product Price -->
                        <div class="product-single__price product-single__price-product-template" id="price-container">
                            <?php if (!empty($price_after_discount) && $price_after_discount < $price): ?>
                                <span class="visually-hidden">Regular price</span>
                                <s id="ComparePrice-product-template"><span class="money">RS. <?php echo number_format($price, 2); ?></span></s>
                                <span class="product-price__price product-price__price-product-template product-price__sale product-price__sale--single">
                                    <span id="ProductPrice-product-template" data-base-price="<?php echo $price_after_discount; ?>"><span class="money">RS. <?php echo number_format($price_after_discount, 2); ?></span></span>
                                </span>
                                <?php if ($discount_percent > 0): ?>
                                <span class="discount-badge">
                                    <span class="devider">|</span>&nbsp;
                                    <span>You Save</span>
                                    <span id="SaveAmount-product-template" class="product-single__save-amount">
                                        <span class="money">RS. <?php echo number_format($price - $price_after_discount, 2); ?></span></span>
                                    <span class="off">(<span><?php echo $discount_percent; ?></span>%)</span>
                                </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="product-price__price product-price__price-product-template">
                                    <span id="ProductPrice-product-template" data-base-price="<?php echo $price; ?>"><span class="money">RS. <?php echo number_format($price, 2); ?></span></span>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Set Suite Message -->
                        <div id="set-suite-message" class="set-suite-message">
                            <?php echo $setsuite_quantity; ?> piece is in set of suits
                        </div>
                        
                        <!-- Button Container -->
                        <div class="button-container">
                            <?php if (!empty($setsuite_price)): ?>
                                <button type="button" id="set-suits-btn" class="btn btn-primary mb-3 rounded set-suits-btn" 
                                    data-set-price="<?php echo $setsuite_price_after_discount; ?>" 
                                    data-original-set-price="<?php echo $setsuite_price; ?>"
                                    data-set-discount="<?php echo $setsuite_discount; ?>"
                                    data-regular-price="<?php echo !empty($price_after_discount) && $price_after_discount < $price ? $price_after_discount : $price; ?>" 
                                    data-quantity="<?php echo $setsuite_quantity; ?>">
                                    Set Suits
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <!-- End Product Price -->
                        <div id="sizes-section" class="hide-element">
                            <?php if (!empty($sizeNamesArray)): ?>
                                <p class="product-sizes mb-2 "><b> Available Sizes : </b>
                                    <a href="javascript:void(0)" onclick="document.getElementById('sizeGuideModal').style.display='flex'"
                                       style="font-size:12px;color:#7c3aed;text-decoration:underline;font-weight:600;cursor:pointer;margin-left:8px;">
                                       <i class="fas fa-ruler me-1"></i>Size Guide
                                    </a>
                                    <a href="javascript:void(0)" onclick="openSizeAI()"
                                       style="font-size:12px;color:#e67e22;text-decoration:underline;font-weight:600;cursor:pointer;margin-left:12px;">
                                       <i class="fa fa-magic me-1"></i>AI Find My Size
                                    </a>
                                </p>
                                    <div class="size-selection-mode">
                                        <label>
                                            <input type="radio" name="size-selection-mode" value="single" checked> Single Selection
                                        </label>
                                        <label>
                                            <input type="radio" name="size-selection-mode" value="multiple"> Multiple Selection
                                        </label>
                                    </div>
                                    <div class="size-tags" data-selection-mode="single">
                                        <?php foreach ($sizeNamesArray as $sizeName): ?>
                                            <span class="size-tag" data-size="<?php echo htmlspecialchars($sizeName); ?>"><?php echo htmlspecialchars($sizeName); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                
                            <?php endif; ?>
                        </div>
                        
                        <!-- Colors Section -->
                        <div id="colors-section" class="hide-element">
                            <?php if (!empty($colorDetailsArray)): ?>
                                <p class="product-colors mb-2 "><b> Available Colors : </b></p>
                                    <div class="color-tags">
                                        <?php foreach ($colorDetailsArray as $colorDetail): ?>
                                            <span class="color-tag" data-color="<?php echo htmlspecialchars($colorDetail['name']); ?>">
                                                <span class="color-swatch" style="background-color: <?php echo htmlspecialchars($colorDetail['color_code']); ?>;"></span>
                                                <span class="color-name"><?php echo htmlspecialchars($colorDetail['name']); ?></span>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                
                            <?php endif; ?>
                        </div>
                        
                        <!-- Product Intro -->
                        <div class="product-info">
                            <p class="product-stock mt-2">Availability : 
                                <?php if ($stock == 'Out Stock'): ?>
                                    <span class="badge bg-danger">Out of Stock</span>
                                <?php else: ?>
                                    <span class="badge bg-success">In Stock</span>
                                <?php endif; ?>
                            </p>
                            
                            <!-- Stock Availability Indicator with Color Coding -->
                            <?php if ($order_qty > 0): ?>
                                <?php 
                                $stockStatus = '';
                                $stockClass = '';
                                $iconColor = '';
                                
                                if ($order_qty > 5) {
                                    $stockStatus = 'In Stock';
                                    $stockClass = 'stock-good';
                                    $iconColor = '#28a745'; // Green
                                } else {
                                    $stockStatus = 'Low Stock';
                                    $stockClass = 'stock-low';
                                    $iconColor = '#ffc107'; // Yellow/Amber
                                }
                                ?>
                                <div class="stock-indicator <?php echo $stockClass; ?>">
                                    <span class="stock-icon" style="background-color: <?php echo $iconColor; ?>"></span>
                                    <span><?php echo $stockStatus; ?> - <?php echo $order_qty; ?> Items Left</span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Only show stitching option if stitched_price exists and is greater than 0 -->
                            <div id="stitching-section" class="hide-element mb-0">
                                <?php if (!empty($stitched_price) && $stitched_price > 0): ?>
                                <div class="col-md-12 bg-light py-1 mt-2">
                                    <div class="d-flex align-items-center">
                                        <input type="checkbox" class="ms-3" id="stitching-checkbox" value="<?php echo $stitched_price?>">
                                        <p class="ms-2 mb-0">Get Your Clothes Stitched (+RS <?php echo $stitched_price?>)</p>
                                    </div>
                                    <p id="stitching-total-price" class="stitching-total-price ms-3 mb-0"></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <!-- End Product Intro -->
                        <!-- Form -->
                        <form method="post" action="#" id="product_form_<?php echo $productID; ?>" accept-charset="UTF-8" class=" mt-0 product-form product-form-product-template hidedropdown" enctype="multipart/form-data">
                            <!-- Hidden product ID -->
                            <input type="hidden" name="product_id" value="<?php echo $productID; ?>">
                            <input type="hidden" name="quantity" value="1" id="Quantity">
                            <input type="hidden" name="selected_sizes" id="selected-sizes" value="">
                            <input type="hidden" name="selected_colors" id="selected-colors" value="">
                            <input type="hidden" name="stitching_option" id="stitching-option" value="0">
                            <input type="hidden" name="price_type" id="price-type" value="regular">
                            <!-- Product Action -->
                            <div class="product-action clearfix mt-4">
                                <div class="product-form__item--submit w-100">
                                    <?php if ($stock == 'Out Stock'): ?>
                                        <button type="button" class="btn w-100 rounded bg-danger" disabled>
                                            <i class="icon an an-times-circle me-2"></i>
                                            <span>Out of Stock</span>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" name="add" class="btn add-to-cart-btn w-100" data-product-id="<?php echo $productID; ?>">
                                            <i class="icon an an-shopping-cart me-2"></i>
                                            <span>Add to cart</span>
                                        </button>
                                        <button type="button" class="btn buy-now-btn w-100 mt-2" data-product-id="<?php echo $productID; ?>">
                                            <i class="icon an an-flash me-2"></i>
                                            <span>Buy Now</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- End Product Action -->
                        </form>
                        <!-- End Form -->
                        <div class="col-md-12 mt-3">
                            <h4 class="mb-2">Description :</h4>
                            <!--<?php echo html_entity_decode($description) ?>-->
                            <?php echo nl2br(html_entity_decode($description)) ?>
                        </div>
                    </div>
                    <!-- End Product Info -->
                    <div class="col-md-12 border rounded">
                        <div class="accordion" id="accordionExample">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button text-dark bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">Shipping information</button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse " aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                         <p class=" mt-0">Estimate delivery times: 2-4 days (Local), 7-9 days (International)
                                            <br><br>
                                            Please note, when you place an order, we will estimate shipping and delivery dates for you based on the availability of your items and the shipping options you choose. The delivery time for your order will be mentioned at the checkout page and order invoice. 
                                            <br><br>
                                            Depending on the shipping provider you choose, shipping date estimates may appear on the shipping quotes page.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>                        
                    </div>
                    <!-- Wishlist - Share -->
                    <div class="display-table shareRow pt-3 pb-0 d-table">
                        <div class="display-table-cell text-center align-top">
                            <div class="social-sharing">
                                <a target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($url_domain . '/product-details.php?slug=' . $slug); ?>" class="btn btn--small btn--secondary btn--share share-facebook" data-bs-toggle="tooltip" data-bs-placement="top" title="Share on Facebook">
                                    <i class="icon an an-facebook" aria-hidden="true"></i><span class="share-title" aria-hidden="true">Facebook</span>
                                </a>
                                <a target="_blank" href="https://wa.me/?text=<?php echo urlencode($name . ' - ' . $url_domain . '/product-details.php?slug=' . $slug); ?>" class="btn btn--small btn--secondary btn--share share-pinterest" data-bs-toggle="tooltip" data-bs-placement="top" title="Share on WhatsApp">
                                    <i class="icon an an-whatsapp" aria-hidden="true"></i><span class="share-title" aria-hidden="true">WhatsApp</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- End Wishlist - Share -->
                </div>
            </div>

            <!-- Product Tabs -->
            <div class="tabs-listing tab-details mt-4 mt-md-5 mb-4">
                <!-- End Tabs Container -->
                <div class="col-md-12 mt-3">
                    <header class="section-header">
                        <h2 class="section-header__title text-center h2"><span>Related Products</span></h2>
                        <p class="sub-heading">You can manage this section from store admin as describe in above section</p>
                    </header>
                    <div class="row">
                        <?php
                            // Build WHERE conditions for related products
                            $whereConditions = [];
                            
                            // Handle brand IDs (single or multiple)
                            if (!empty($brand_id)) {
                                $brandIds = explode(',', $brand_id);
                                $brandConditions = [];
                                foreach ($brandIds as $bId) {
                                    $bId = trim($bId);
                                    if (!empty($bId)) {
                                        $brandConditions[] = "FIND_IN_SET('$bId', brand) > 0";
                                    }
                                }
                                if (!empty($brandConditions)) {
                                    $whereConditions[] = "(" . implode(" OR ", $brandConditions) . ")";
                                }
                            }
                            
                            // Build the final WHERE clause
                            $whereClause = "";
                            if (!empty($whereConditions)) {
                                $whereClause = "(" . implode(" OR ", $whereConditions) . ") AND id != '$productID'";
                            } else {
                                // Fallback if no brand is found
                                $whereClause = "id != '$productID'";
                            }
                            
                            // Get 4 related products based on the built conditions
                            $relatedProductsQuery = selectQuery("SELECT * FROM products WHERE ($whereClause) AND is_active != 0 ORDER BY id DESC LIMIT 4");
                            
                            if ($relatedProductsQuery['status'] && !empty($relatedProductsQuery['data'])) {
                                foreach ($relatedProductsQuery['data'] as $relatedProduct) {
                                    $related_id = $relatedProduct['id'];
                                    $related_name = $relatedProduct['name'];
                                    $related_slug = $relatedProduct['slug'];
                                    $related_price = $relatedProduct['price'];
                                    $related_price_after_discount = $relatedProduct['price_after_discount'];
                                    $related_image = $relatedProduct['image'];
                                    $related_stock = $relatedProduct['stock'];
                                    
                                    // Process image
                                    $related_images = explode(',', $related_image);
                                    $related_mainImage = trim($related_images[0]);
                                    if (empty($related_mainImage)) {
                                        $related_mainImage = "waiting images.jpeg";
                                    }
                                    
                                    // Calculate discount percentage if applicable
                                    $related_discount_percent = 0;
                                    if (!empty($related_price_after_discount) && $related_price_after_discount < $related_price && $related_price > 0) {
                                        $related_discount_percent = round((($related_price - $related_price_after_discount) / $related_price) * 100);
                                    }
                        ?>
                        <a href="<?php echo $url_domain; ?>/product-details.php?slug=<?php echo $related_slug; ?>" class="col-md-3 col-6 mb-3">
                            <div class="card border-0 border-radius text-center">
                                <div class="image-container position-relative">
                                    <img src="<?php echo $url_domain . $imagePath . $related_mainImage; ?>" alt="<?php echo htmlspecialchars($related_name); ?>" class="product-image">
                                </div>
                                <p class="mt-3 mb-0"><?php echo $related_name ?></p>
                                <div class="related-product-price mt-2">
                                    <?php if (!empty($related_price_after_discount) && $related_price_after_discount < $related_price): ?>
                                        <span class="old-price">RS. <?php echo number_format($related_price, 2); ?></span>
                                        RS. <?php echo number_format($related_price_after_discount, 2); ?>
                                    
                                    <?php else: ?>
                                        RS. <?php echo number_format($related_price, 2); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                        <?php
                            }
                            }
                        ?>
                    </div>
                </div>
            </div>
            <?php include 'include/recently_product.php'; ?>
            </div>
        </div>
        <!-- #ProductSection product template -->
    </div>
    <!-- End Main Content -->
</div>

<!-- ══════════════════════════════════════════════════════════
     CUSTOMERS ALSO BOUGHT
══════════════════════════════════════════════════════════ -->
<?php
// Collaborative Filtering — find products co-purchased with this one
$cabProducts = [];
$cabRes = mysqli_query($connection_database,
    "SELECT oi2.product_id, p.name, p.slug, p.image, p.price, p.dis_amount,
            COUNT(*) AS co_count
     FROM order_items oi1
     JOIN order_items oi2 ON oi2.order_id = oi1.order_id AND oi2.product_id != oi1.product_id
     JOIN products p ON p.id = oi2.product_id
     WHERE oi1.product_id = $productID
       AND p.stock != 'Out Stock'
     GROUP BY oi2.product_id
     ORDER BY co_count DESC
     LIMIT 8"
);
if ($cabRes) while ($r = mysqli_fetch_assoc($cabRes)) $cabProducts[] = $r;
?>
<?php if (!empty($cabProducts)): ?>
<section style="background:#fff;padding:48px 0 56px;border-top:1px solid #ebebeb;">
<style>
.cab-ai-tag { font-size:.68rem; color:#7c3aed; font-weight:600; margin-top:3px; }
</style>
<div class="container">
    <h5 style="font-size:1.25rem;font-weight:900;color:#111;margin-bottom:4px;">
        <i class="fa fa-users me-2" style="color:#f0932b;"></i>Customers Also Bought
    </h5>
    <p style="font-size:.83rem;color:#999;margin-bottom:28px;">
        <i class="fa fa-magic me-1" style="color:#7c3aed;"></i>AI-powered — based on real purchase patterns
    </p>
    <div class="row">
    <?php foreach ($cabProducts as $cab):
        $cabImgs  = explode(',', $cab['image'] ?? '');
        $cabImg1  = trim($cabImgs[0] ?? '');
        $cabSrc   = $cabImg1 ? $url_domain . $imagePath . $cabImg1 : $url_domain . '/images/waiting images.jpeg';
        $cabPrice = (float)$cab['price'];
        $cabDis   = (float)$cab['dis_amount'];
        $cabFinal = ($cabDis > 0 && $cabDis < $cabPrice) ? $cabDis : $cabPrice;
        $cabDisPct = ($cabDis > 0 && $cabDis < $cabPrice) ? round((($cabPrice - $cabDis) / $cabPrice) * 100) : 0;
    ?>
    <a href="<?= $url_domain ?>/product/<?= htmlspecialchars($cab['slug']) ?>" class="col-md-3 col-6 mb-3">
        <div class="card border-0 border-radius text-center">
            <div class="image-container position-relative">
                <img src="<?= $cabSrc ?>" alt="<?= htmlspecialchars($cab['name']) ?>" class="product-image">
                <?php if ($cabDisPct > 0): ?>
                    <div class="product-labels rectangular"><span class="lbl pr-label1"><?= $cabDisPct ?>% OFF</span></div>
                <?php endif; ?>
            </div>
            <p class="mt-3 mb-0"><?= htmlspecialchars($cab['name']) ?></p>
            <div class="related-product-price mt-2">
                <?php if ($cabDisPct > 0): ?>
                    <span class="old-price">RS. <?= number_format($cabPrice, 2) ?></span>
                    RS. <?= number_format($cabFinal, 2) ?>
                <?php else: ?>
                    RS. <?= number_format($cabFinal, 2) ?>
                <?php endif; ?>
            </div>
            <div class="cab-ai-tag mb-2"><i class="fa fa-users"></i> <?= (int)$cab['co_count'] ?> customers bought both</div>
        </div>
    </a>
    <?php endforeach; ?>
    </div>
</div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════
     REVIEWS & RATINGS SECTION
══════════════════════════════════════════════════════════ -->
<style>
/* ── Reviews Layout ───────────────────────────────────── */
.rev-section { background:#fff; border-top:1px solid #ebebeb; padding:48px 0 60px; }
.rev-section h2 { font-size:1.5rem; font-weight:800; margin-bottom:28px; text-align:center; letter-spacing:-.3px; }
/* Summary bar */
.rev-summary { display:flex; align-items:center; gap:24px; flex-wrap:wrap; background:#f8f8f8; border-radius:14px; padding:24px 28px; margin-bottom:32px; }
.rev-avg-left { text-align:center; flex-shrink:0; min-width:100px; }
.rev-avg-num { font-size:2.4rem; font-weight:900; color:#222; line-height:1; }
.rev-avg-stars { display:flex; gap:2px; justify-content:center; margin:4px 0 2px; }
.rev-avg-stars span { font-size:1.2rem; color:#f5a623; }
.rev-avg-stars .re { color:#ddd; }
.rev-avg-label { font-size:.78rem; color:#888; }
.rev-bars { flex:1; min-width:160px; }
.rev-bar-row { display:flex; align-items:center; gap:8px; margin-bottom:5px; }
.rev-bar-label { font-size:.78rem; color:#555; width:16px; text-align:right; flex-shrink:0; }
.rev-bar-track { flex:1; height:8px; background:#e8e8e8; border-radius:4px; overflow:hidden; }
.rev-bar-fill  { height:100%; background:#2d7a5f; border-radius:4px; transition:width .4s; }
.rev-bar-count { font-size:.78rem; color:#888; width:20px; flex-shrink:0; }
.rev-write-btn { flex-shrink:0; }
.rev-write-btn button { background:#2d7a5f; color:#fff; border:none; border-radius:6px; padding:13px 28px; font-size:.95rem; font-weight:700; cursor:pointer; transition:background .2s; }
.rev-write-btn button:hover { background:#245f4b; }
/* Review list */
.rev-list-header { display:flex; align-items:center; gap:10px; margin-bottom:16px; font-size:.85rem; color:#555; }
.rev-card { border-bottom:1px solid #f0f0f0; padding:20px 0; }
.rev-card:last-child { border-bottom:none; }
.rev-card-top { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:6px; }
.rev-card-stars span { font-size:1rem; color:#f5a623; }
.rev-card-stars .re { color:#ddd; }
.rev-card-date { font-size:.78rem; color:#bbb; }
.rev-card-meta { display:flex; align-items:center; gap:10px; margin-bottom:8px; }
.rev-user-icon { width:34px; height:34px; border-radius:50%; background:#e8e8e8; display:flex; align-items:center; justify-content:center; flex-shrink:0; overflow:hidden; }
.rev-user-icon svg { width:20px; height:20px; color:#999; }
.rev-user-icon img { width:100%; height:100%; object-fit:cover; }
.rev-user-name { font-weight:700; font-size:.9rem; color:#222; }
.rev-card-text { font-size:.9rem; color:#555; line-height:1.65; }
.rev-card-actions { display:flex; align-items:center; gap:10px; margin-top:12px; }
.rev-like-btn, .rev-dislike-btn { display:inline-flex; align-items:center; gap:5px; background:none; border:1px solid #e8e8e8; border-radius:20px; padding:4px 12px; font-size:.78rem; color:#888; cursor:pointer; transition:all .2s; }
.rev-like-btn:hover { border-color:#27ae60; color:#27ae60; background:#f0fff4; }
.rev-dislike-btn:hover { border-color:#e74c3c; color:#e74c3c; background:#fff5f5; }
.rev-like-btn.rev-voted { border-color:#27ae60; color:#27ae60; background:#f0fff4; }
.rev-dislike-btn.rev-voted { border-color:#e74c3c; color:#e74c3c; background:#fff5f5; }
.rev-helpful-label { font-size:.75rem; color:#bbb; }
.no-reviews-msg { text-align:center; padding:40px 0; color:#bbb; font-size:.95rem; }
/* Try-On strip */
.tryon-strip { background:linear-gradient(135deg,#f3e8ff,#fce4f0); border-radius:14px; padding:24px 28px; margin-top:28px; display:flex; align-items:center; gap:20px; flex-wrap:wrap; }
.tryon-strip-text { flex:1; min-width:160px; }
.tryon-strip-text h5 { font-weight:800; margin:0 0 4px; font-size:1.05rem; }
.tryon-strip-text p  { margin:0; color:#666; font-size:.85rem; }
.tryon-strip-btn { background:linear-gradient(135deg,#7c3aed,#ED2590); color:#fff; border:none; border-radius:30px; padding:11px 26px; font-weight:700; font-size:.9rem; cursor:pointer; display:inline-flex; align-items:center; gap:8px; transition:opacity .2s; flex-shrink:0; }
.tryon-strip-btn:hover { opacity:.88; color:#fff; }

/* ── Mobile responsive — Reviews & Try-On ─────────────── */
@media (max-width: 575px) {
    .rev-section { padding: 28px 0 36px; }
    .rev-section h2 { font-size: 1.25rem; margin-bottom: 20px; }
    .rev-summary { flex-direction: column; align-items: stretch; gap: 16px; padding: 18px 16px; margin-bottom: 20px; }
    .rev-avg-left { display: flex; align-items: center; gap: 12px; min-width: unset; text-align: left; }
    .rev-avg-num { font-size: 2rem; }
    .rev-avg-stars span { font-size: 1rem; }
    .rev-bars { min-width: unset; }
    .rev-write-btn { text-align: center; }
    .rev-write-btn button { width: 100%; padding: 12px 20px; font-size: .9rem; }
    .tryon-strip { padding: 18px 16px; gap: 14px; justify-content: center; text-align: center; }
    .tryon-strip-text { min-width: unset; }
    .tryon-strip-btn { width: 100%; justify-content: center; padding: 12px 20px; }
    .rev-card { padding: 14px 0; }
    .rev-card-text { font-size: .85rem; }
    /* Review modal — make it full-width on mobile */
    #writeReviewModal .modal-dialog { margin: 12px; max-width: calc(100% - 24px) !important; }
    #writeReviewModal .modal-content { border-radius: 14px !important; }
}
@media (max-width: 767px) {
    .rev-summary { flex-wrap: wrap; }
    .rev-write-btn { width: 100%; text-align: center; }
    .rev-write-btn button { min-width: 200px; }
}

 
    /* Fix modal stacking */
    .modal-backdrop { z-index: 100000 !important; }
    .modal          { z-index: 100001 !important; }
</style>

<div class="rev-section">
    <div class="container">
        <h2>Customer Reviews</h2>

        <!-- ── Summary ──────────────────────────────────── -->
        <div class="rev-summary">
            <!-- Average score -->
            <div class="rev-avg-left">
                <div class="rev-avg-num" id="avgScore">—</div>
                <div class="rev-avg-stars" id="avgStars"></div>
                <div class="rev-avg-label" id="avgLabel">No reviews yet</div>
            </div>
            <!-- Rating bars -->
            <div class="rev-bars" id="revBars">
                <?php for ($s = 5; $s >= 1; $s--): ?>
                <div class="rev-bar-row">
                    <div class="rev-bar-label"><?= $s ?></div>
                    <div class="rev-bar-track"><div class="rev-bar-fill" id="bar<?= $s ?>" style="width:0%"></div></div>
                    <div class="rev-bar-count" id="bc<?= $s ?>">0</div>
                </div>
                <?php endfor; ?>
            </div>
            <!-- Write review button -->
            <div class="rev-write-btn">
                <button onclick="openWriteReview()">Write a Review</button>
            </div>
        </div>

        <!-- ── Reviews List ──────────────────────────────── -->
        <div class="rev-list-header">
            <span id="revCountLabel"></span>
        </div>
        <div id="reviewsList">
            <div class="no-reviews-msg">Loading reviews…</div>
        </div>

        <!-- ── Virtual Try-On strip ──────────────────────── -->
        <div class="tryon-strip">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2l-4 4H5l-2 5h3v11h12V11h3L19 6h-3z"/>
                    <path d="M9 6c0 1.7 1.3 3 3 3s3-1.3 3-3"/>
                </svg>
            </div>
            <div class="tryon-strip-text">
                <h5>Virtual Try-On</h5>
                <p>Upload your photo — AI shows you wearing this outfit!</p>
            </div>
            <button class="tryon-strip-btn" onclick="openTryOn()">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                Try On This Dress
            </button>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     WRITE REVIEW — Multi-step Modal
══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="writeReviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content" style="border-radius:18px;overflow:hidden;border:none;">
            <!-- Step 1: Rate the product -->
            <div id="wrStep1">
                <div class="modal-body p-0">
                    <div style="padding:28px 28px 0;text-align:center;">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" style="float:right;margin:-4px -4px 0 0;"></button>
                        <h5 style="font-weight:800;font-size:1.15rem;margin-bottom:6px;">How would you rate this product?</h5>
                        <p style="color:#888;font-size:.88rem;margin-bottom:20px;">We would love it if you would share a bit about your experience.</p>
                        <!-- Product image + name -->
                        <img src="<?= $url_domain . $imagePath . htmlspecialchars($allImages[0] ?? '') ?>"
                             style="width:200px;height:240px;object-fit:contain;border-radius:12px;margin-bottom:14px;background:#f7f7f7;">
                        <div style="font-weight:700;font-size:1rem;margin-bottom:20px;"><?= htmlspecialchars($name) ?></div>
                        <!-- Stars -->
                        <div id="wrStars" style="display:flex;gap:12px;justify-content:center;font-size:2.8rem;margin-bottom:8px;">
                            <span class="wrs" data-v="1" style="cursor:pointer;color:#ddd;line-height:1;">★</span>
                            <span class="wrs" data-v="2" style="cursor:pointer;color:#ddd;line-height:1;">★</span>
                            <span class="wrs" data-v="3" style="cursor:pointer;color:#ddd;line-height:1;">★</span>
                            <span class="wrs" data-v="4" style="cursor:pointer;color:#ddd;line-height:1;">★</span>
                            <span class="wrs" data-v="5" style="cursor:pointer;color:#ddd;line-height:1;">★</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;padding:0 10px;font-size:.78rem;color:#888;margin-bottom:24px;">
                            <span>Poor</span><span>Great</span>
                        </div>
                    </div>
                    <div style="padding:0 28px 28px;display:flex;justify-content:flex-end;">
                        <button id="wrNextBtn1" onclick="wrGoTo(2)" disabled
                            style="background:#2d7a5f;color:#fff;border:none;border-radius:6px;padding:12px 32px;font-weight:700;font-size:.95rem;cursor:pointer;opacity:.5;">
                            Next →
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 2: Review text -->
            <div id="wrStep2" style="display:none;">
                <div class="modal-body p-0">
                    <div style="padding:28px 28px 0;">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" style="float:right;margin:-4px -4px 0 0;"></button>
                        <div style="text-align:center;font-size:1.6rem;margin-bottom:4px;" id="wrStep2Stars"></div>
                        <h5 style="font-weight:800;font-size:1.05rem;margin-bottom:18px;">Review content <span style="color:#888;font-weight:400;font-size:.85rem;">(Required)</span></h5>
                        <textarea id="wrText" rows="6" placeholder="Start writing here..."
                            style="width:100%;border:1px solid #ddd;border-radius:8px;padding:14px;font-size:.93rem;resize:none;outline:none;line-height:1.6;"
                            maxlength="1000"></textarea>
                        <div style="font-size:.75rem;color:#aaa;margin-top:4px;text-align:right;"><span id="wrCharCount">0</span>/1000</div>
                        <p style="font-size:.75rem;color:#aaa;margin-top:14px;line-height:1.5;">We'll only contact you about your review if necessary. By submitting your review, you agree to our <a href="#" style="color:inherit;">terms and conditions</a> and <a href="#" style="color:inherit;">privacy policy</a>.</p>
                    </div>
                    <div style="padding:0 28px 28px;display:flex;justify-content:space-between;">
                        <button onclick="wrGoTo(1)" style="background:none;border:none;color:#555;font-weight:600;cursor:pointer;font-size:.9rem;">← Back</button>
                        <button id="wrNextBtn2" onclick="wrGoTo(3)"
                            style="background:#2d7a5f;color:#fff;border:none;border-radius:6px;padding:12px 32px;font-weight:700;font-size:.95rem;cursor:pointer;">
                            Next →
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 3: Name + Photo (optional) -->
            <div id="wrStep3" style="display:none;">
                <div class="modal-body p-0">
                    <div style="padding:28px 28px 0;">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" style="float:right;margin:-4px -4px 0 0;"></button>
                        <h5 style="font-weight:800;font-size:1.05rem;margin-bottom:6px;">Almost done!</h5>
                        <p style="color:#888;font-size:.88rem;margin-bottom:20px;">Name and photo are optional — leave blank to post as Anonymous.</p>

                        <!-- Photo upload + Name -->
                        <div style="display:flex;gap:14px;align-items:flex-start;margin-bottom:18px;">
                            <div style="text-align:center;flex-shrink:0;">
                                <div id="wrAvatarCircle"
                                     onclick="document.getElementById('wrPhotoInput').click()"
                                     style="width:60px;height:60px;border-radius:50%;background:#e8e8e8;border:2px dashed #ccc;
                                            display:flex;align-items:center;justify-content:center;cursor:pointer;overflow:hidden;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#bbb" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                                </div>
                                <div style="font-size:.68rem;color:#bbb;margin-top:4px;width:60px;line-height:1.2;">Add photo<br>(optional)</div>
                                <input type="file" id="wrPhotoInput" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="wrPreviewPhoto(this)">
                            </div>
                            <div style="flex:1;">
                                <label style="display:block;font-weight:700;font-size:.82rem;color:#555;margin-bottom:6px;">Your Name <span style="color:#888;font-weight:400;">(optional)</span></label>
                                <input type="text" id="wrName" class="form-control"
                                       placeholder="Leave blank for Anonymous"
                                       maxlength="80">
                            </div>
                        </div>
                        <div id="wrSubmitMsg" style="min-height:20px;font-size:.85rem;margin-bottom:8px;"></div>
                    </div>
                    <div style="padding:0 28px 28px;display:flex;justify-content:space-between;">
                        <button onclick="wrGoTo(2)" style="background:none;border:none;color:#555;font-weight:600;cursor:pointer;font-size:.9rem;">← Back</button>
                        <button id="wrSubmitBtn" onclick="wrSubmit()"
                            style="background:#2d7a5f;color:#fff;border:none;border-radius:6px;padding:12px 32px;font-weight:700;font-size:.95rem;cursor:pointer;">
                            Submit Review
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 4: Success -->
            <div id="wrStepDone" style="display:none;">
                <div class="modal-body" style="text-align:center;padding:48px 28px;">
                    <div style="margin-bottom:16px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#2d7a5f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="9 12 11 14 15 10"/>
                        </svg>
                    </div>
                    <h5 style="font-weight:800;">Thank you for your review!</h5>
                    <p style="color:#888;font-size:.9rem;">Your review has been posted successfully.</p>
                    <button data-bs-dismiss="modal"
                        style="background:#2d7a5f;color:#fff;border:none;border-radius:6px;padding:11px 28px;font-weight:700;margin-top:12px;cursor:pointer;">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Virtual Try-On Modal ──────────────────────────────── -->
<div class="modal fade" id="tryOnModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg,#7c3aed,#ED2590);color:#fff;border:none;display:flex;align-items:center;justify-content:space-between;padding:16px 20px;">
                <h5 class="modal-title" style="font-weight:800;margin:0;display:flex;align-items:center;gap:10px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2l-4 4H5l-2 5h3v11h12V11h3L19 6h-3z"/>
                        <path d="M9 6c0 1.7 1.3 3 3 3s3-1.3 3-3"/>
                    </svg>
                    Virtual Try-On
                </h5>
                <button type="button" data-bs-dismiss="modal"
                    style="background:rgba(255,255,255,.25);border:none;border-radius:50%;width:30px;height:30px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#fff;font-size:1rem;line-height:1;flex-shrink:0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="modal-body p-4">
                <div id="tryonStep1">
                    <p style="color:#555;margin-bottom:16px;">Upload a clear front-facing photo of yourself. The outfit will be visualized on your photo.</p>
                    <div style="border:2px dashed #ddd;border-radius:12px;padding:30px;text-align:center;margin-bottom:16px;cursor:pointer;transition:border-color .2s;" onclick="document.getElementById('tryonPhotoInput').click()" onmouseover="this.style.borderColor='#7c3aed'" onmouseout="this.style.borderColor='#ddd'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#bbb" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:8px;"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        <p style="margin:0;color:#888;font-size:.9rem;font-weight:600;">Click to upload your photo</p>
                        <p style="margin:4px 0 0;color:#bbb;font-size:.78rem;">JPG, PNG — clear front-facing photo works best</p>
                    </div>
                    <input type="file" id="tryonPhotoInput" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="previewTryonPhoto(this)">
                    <?php if (count($allImages) > 1): ?>
                    <!-- Garment image selector -->
                    <div style="margin-bottom:14px;">
                        <p style="font-size:.8rem;color:#7c3aed;font-weight:700;margin-bottom:8px;">📸 Select Outfit Photo for Try-On:</p>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <?php foreach ($allImages as $idx => $img): ?>
                            <div onclick="selectGarmentImage(<?= $idx ?>, '<?= $imagePath . htmlspecialchars($img) ?>')"
                                 id="garmentThumb_<?= $idx ?>"
                                 style="cursor:pointer;border-radius:8px;overflow:hidden;border:3px solid <?= $idx === 0 ? '#7c3aed' : '#ddd' ?>;transition:border-color .2s;flex-shrink:0;">
                                <img src="<?= $url_domain . $imagePath . htmlspecialchars($img) ?>"
                                     style="width:64px;height:64px;object-fit:cover;display:block;">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <p style="font-size:.72rem;color:#999;margin-top:6px;margin-bottom:0;">Tip: Select the clearest garment photo for best AI result ✨</p>
                    </div>
                    <?php endif; ?>
                    <div id="tryonPreviewWrap" style="display:none;text-align:center;margin-bottom:16px;">
                        <div style="display:flex;align-items:center;justify-content:center;gap:16px;flex-wrap:wrap;">
                            <div>
                                <p style="font-size:.75rem;color:#999;margin-bottom:4px;">Your Photo</p>
                                <img id="tryonPreviewImg" src="" style="max-height:160px;border-radius:10px;border:2px solid #eee;">
                            </div>
                            <div style="color:#bbb;font-size:1.4rem;">+</div>
                            <div>
                                <p style="font-size:.75rem;color:#999;margin-bottom:4px;">Selected Outfit</p>
                                <img id="tryonOutfitPreview" src="<?= $url_domain . $imagePath . htmlspecialchars($allImages[0] ?? '') ?>" style="max-height:160px;border-radius:8px;border:2px solid #eee;">
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-dark w-100 mt-2" id="tryonStartBtn" onclick="startTryOn()" style="display:none;background:linear-gradient(135deg,#7c3aed,#ED2590);border:none;padding:12px;font-size:1rem;font-weight:700;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        Generate Try-On Preview
                    </button>
                </div>
                <!-- Processing state -->
                <div id="tryonProcessing" style="display:none;text-align:center;padding:30px 16px;">
                    <div style="width:64px;height:64px;margin:0 auto 16px;background:linear-gradient(135deg,#7c3aed,#ED2590);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                    </div>
                    <p style="font-weight:700;color:#222;font-size:1rem;margin-bottom:6px;" id="tryonStatusMsg">AI is processing your try-on…</p>
                    <p style="color:#888;font-size:.82rem;margin:0;" id="tryonStatusSub">This takes 20–40 seconds. Please wait ✨</p>
                    <div style="margin-top:14px;height:4px;background:#f0e6ff;border-radius:4px;overflow:hidden;max-width:220px;margin-left:auto;margin-right:auto;">
                        <div id="tryonProgressBar" style="height:100%;width:0%;background:linear-gradient(90deg,#7c3aed,#ED2590);border-radius:4px;transition:width 3s linear;"></div>
                    </div>
                </div>

                <!-- Result state — AI image or canvas -->
                <div id="tryonResult" style="display:none;text-align:center;">
                    <p id="tryonResultLabel" style="font-weight:700;color:#222;margin-bottom:12px;display:flex;align-items:center;justify-content:center;gap:8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#27ae60" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        Here's how it looks on you!
                    </p>
                    <!-- AI result: real image from Fashn.ai -->
                    <img id="tryonAiImg" src="" alt="Virtual Try-On Result"
                         style="display:none;max-width:100%;border-radius:12px;border:3px solid #ED2590;box-shadow:0 8px 32px rgba(237,37,144,.15);">
                    <!-- Canvas fallback -->
                    <canvas id="tryonCanvas" style="max-width:100%;border-radius:12px;border:3px solid #ED2590;box-shadow:0 8px 32px rgba(237,37,144,.15);"></canvas>
                    <div class="mt-3 d-flex gap-2 justify-content-center flex-wrap">
                        <button class="btn btn-dark" onclick="downloadTryOn()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Save Photo
                        </button>
                        <button class="btn btn-outline-secondary" onclick="resetTryOn()">Try Another Photo</button>
                    </div>
                </div>

                <div id="tryonError" style="display:none;text-align:center;padding:20px;">
                    <p style="color:#dc3545;" id="tryonErrorMsg">Something went wrong. Please try again.</p>
                    <button class="btn btn-outline-dark btn-sm" onclick="resetTryOn()">Try Again</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ══════════════════════════════════════════════════════════
// REVIEWS SYSTEM
// ══════════════════════════════════════════════════════════
const PRODUCT_ID        = <?= (int)$productID ?>;
const REVIEW_URL        = '<?= $url_domain ?>/review_ajax.php';
const REVIEW_PHOTO_BASE = '<?= $url_domain ?>/uploads/review_photos/';

function starsHtml(rating, sz) {
    let h = '';
    for (let i = 1; i <= 5; i++)
        h += `<span class="${i <= Math.round(rating) ? '' : 're'}" style="font-size:${sz||'1rem'};color:${i<=Math.round(rating)?'#f5a623':'#ddd'}">★</span>`;
    return h;
}

function loadReviews() {
    // Use POST so the browser NEVER caches the response
    const fd = new FormData();
    fd.append('action',     'list');
    fd.append('product_id', PRODUCT_ID);
    console.log('[Reviews] Fetching for product_id =', PRODUCT_ID, '| URL =', REVIEW_URL);
    fetch(REVIEW_URL, { method: 'POST', body: fd, cache: 'no-store' })
        .then(r => r.text()).then(raw => {
            console.log('[Reviews] Raw response:', raw);
            let data;
            try { data = JSON.parse(raw); } catch(e) {
                console.error('[Reviews] JSON parse failed:', e);
                document.getElementById('reviewsList').innerHTML = '<div class="no-reviews-msg">Could not load reviews.</div>';
                return;
            }
            // Average
            const avgEl  = document.getElementById('avgScore');
            const stEl   = document.getElementById('avgStars');
            const lbEl   = document.getElementById('avgLabel');
            const ctEl   = document.getElementById('revCountLabel');
            if (data.count > 0) {
                if (avgEl) avgEl.textContent = data.average;
                if (stEl)  stEl.innerHTML    = starsHtml(data.average, '1.1rem');
                if (lbEl)  lbEl.textContent  = `out of 5 · Based on ${data.count} review${data.count!==1?'s':''}`;
                if (ctEl)  ctEl.textContent  = `${data.count} review${data.count!==1?'s':''}`;
            } else {
                if (avgEl) avgEl.textContent = '—';
                if (lbEl)  lbEl.textContent  = 'No reviews yet';
                if (ctEl)  ctEl.textContent  = '';
            }
            // Breakdown bars
            const breakdown = {5:0,4:0,3:0,2:0,1:0};
            if (data.breakdown) {
                Object.assign(breakdown, data.breakdown);
            } else if (data.reviews) {
                data.reviews.forEach(r => { const v = Math.round(r.rating); if (breakdown[v]!==undefined) breakdown[v]++; });
            }
            const total = data.count || 1;
            for (let s = 1; s <= 5; s++) {
                const fill = document.getElementById('bar' + s);
                const cnt  = document.getElementById('bc' + s);
                if (fill) fill.style.width = Math.round((breakdown[s]/total)*100) + '%';
                if (cnt)  cnt.textContent  = breakdown[s];
            }
            // Review cards
            const el = document.getElementById('reviewsList');
            if (!el) { console.error('[Reviews] #reviewsList element not found in DOM'); return; }
            if (!data.reviews || data.reviews.length === 0) {
                el.innerHTML = '<div class="no-reviews-msg">No reviews yet. Be the first to share your experience!</div>';
                return;
            }
            el.innerHTML = data.reviews.map(r => {
                const date = new Date(r.created_at).toLocaleDateString('en-GB', {day:'2-digit',month:'2-digit',year:'numeric'});
                const displayName = r.reviewer_name && r.reviewer_name.trim() ? r.reviewer_name : 'Anonymous';
                const avatarHtml  = r.reviewer_photo
                    ? `<div class="rev-user-icon"><img src="${REVIEW_PHOTO_BASE}${r.reviewer_photo}" alt=""></div>`
                    : `<div class="rev-user-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg></div>`;
                const likes    = parseInt(r.likes    || 0);
                const dislikes = parseInt(r.dislikes || 0);
                return `<div class="rev-card" data-rev-id="${r.id}">
                    <div class="rev-card-top">
                        <div class="rev-card-stars">${starsHtml(r.rating)}</div>
                        <div class="rev-card-date">${date}</div>
                    </div>
                    <div class="rev-card-meta">
                        ${avatarHtml}
                        <span class="rev-user-name">${displayName}</span>
                    </div>
                    <div class="rev-card-text">${r.review_text.replace(/\n/g,'<br>')}</div>
                    <div class="rev-card-actions">
                        <button class="rev-like-btn" onclick="reviewVote(${r.id},'like',this)" title="Helpful">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3H14z"/><path d="M7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
                            <span class="rev-like-count">${likes}</span>
                        </button>
                        <button class="rev-dislike-btn" onclick="reviewVote(${r.id},'dislike',this)" title="Not helpful">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3H10z"/><path d="M17 2h2.67A2.31 2.31 0 0 1 22 4v7a2.31 2.31 0 0 1-2.33 2H17"/></svg>
                            <span class="rev-dislike-count">${dislikes}</span>
                        </button>
                        <span class="rev-helpful-label">Helpful?</span>
                    </div>
                </div>`;
            }).join('');
        }).catch(err => {
            console.error('[Reviews] Fetch error:', err);
            document.getElementById('reviewsList').innerHTML = '<div class="no-reviews-msg">Could not load reviews.</div>';
        });
}

document.addEventListener('DOMContentLoaded', loadReviews);

// Reload reviews when user switches back to this tab
document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'visible') loadReviews();
});

// Also auto-refresh every 30 seconds while the tab is open
setInterval(function () {
    if (document.visibilityState === 'visible') loadReviews();
}, 30000);

// ── Like / Dislike ─────────────────────────────────────────
function reviewVote(id, type, btn) {
    if (btn.disabled) return;
    btn.disabled = true;
    btn.style.opacity = '0.5';
    const fd = new FormData();
    fd.append('action', type);
    fd.append('id', id);
    fetch(REVIEW_URL, { method:'POST', body:fd })
        .then(r => r.json()).then(data => {
            if (data.success) {
                const card = btn.closest('.rev-card');
                card.querySelector('.rev-like-count').textContent    = data.likes;
                card.querySelector('.rev-dislike-count').textContent = data.dislikes;
                btn.style.opacity = '1';
                // Highlight voted button
                btn.classList.add('rev-voted');
            } else {
                btn.disabled = false;
                btn.style.opacity = '1';
            }
        }).catch(() => { btn.disabled = false; btn.style.opacity = '1'; });
}

// ══════════════════════════════════════════════════════════
// WRITE REVIEW — multi-step modal
// ══════════════════════════════════════════════════════════
let wrRating = 0;
let wrPhotoFile = null;
function showModalOnBody(id) {
    const el = document.getElementById(id);
    if (el.parentElement !== document.body) document.body.appendChild(el);
    bootstrap.Modal.getOrCreateInstance(el).show();
}
function openWriteReview() {
    wrRating = 0; wrPhotoFile = null;
    // Reset all steps
    document.querySelectorAll('.wrs').forEach(s => s.style.color = '#ddd');
    document.getElementById('wrNextBtn1').disabled = true;
    document.getElementById('wrNextBtn1').style.opacity = '.5';
    document.getElementById('wrText').value = '';
    document.getElementById('wrCharCount').textContent = '0';
    document.getElementById('wrName').value = '';
    document.getElementById('wrSubmitMsg').innerHTML = '';
    document.getElementById('wrPhotoInput').value = '';
    document.getElementById('wrAvatarCircle').innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#bbb" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>`;
    wrGoTo(1);
   showModalOnBody('writeReviewModal');
}

function wrGoTo(step) {
    [1,2,3,'Done'].forEach((s,i) => {
        const el = document.getElementById('wrStep' + (i===3?'Done':s));
        if (el) el.style.display = 'none';
    });
    const target = document.getElementById(step === 'Done' ? 'wrStepDone' : 'wrStep' + step);
    if (target) target.style.display = 'block';
    if (step === 2) {
        // Show rating at top of step 2
        document.getElementById('wrStep2Stars').innerHTML = starsHtml(wrRating, '1.5rem');
    }
}

// Star picker (step 1)
(function() {
    const stars = document.querySelectorAll('.wrs');
    stars.forEach(s => {
        s.addEventListener('mouseenter', () => stars.forEach(x => x.style.color = +x.dataset.v <= +s.dataset.v ? '#f5a623' : '#ddd'));
        s.addEventListener('mouseleave', () => stars.forEach(x => x.style.color = +x.dataset.v <= wrRating ? '#f5a623' : '#ddd'));
        s.addEventListener('click', () => {
            wrRating = +s.dataset.v;
            stars.forEach(x => x.style.color = +x.dataset.v <= wrRating ? '#f5a623' : '#ddd');
            const btn = document.getElementById('wrNextBtn1');
            btn.disabled = false; btn.style.opacity = '1';
        });
    });
})();

// Text counter (step 2)
document.getElementById('wrText').addEventListener('input', function() {
    document.getElementById('wrCharCount').textContent = this.value.length;
});

// Photo preview (step 3)
function wrPreviewPhoto(input) {
    const file = input.files[0];
    if (!file) return;
    wrPhotoFile = file;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('wrAvatarCircle').innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
    };
    reader.readAsDataURL(file);
}

// Submit
function wrSubmit() {
    const text   = document.getElementById('wrText').value.trim();
    const name   = document.getElementById('wrName').value.trim() || 'Anonymous';
    const msg    = document.getElementById('wrSubmitMsg');
    const btn    = document.getElementById('wrSubmitBtn');

    btn.disabled = true; btn.textContent = 'Submitting…';
    const fd = new FormData();
    fd.append('action',     'submit');
    fd.append('product_id', PRODUCT_ID);
    fd.append('name',       name);
    fd.append('review',     text);
    fd.append('rating',     wrRating);
    if (wrPhotoFile) fd.append('photo', wrPhotoFile);

    fetch(REVIEW_URL, { method:'POST', body:fd })
        .then(r => r.json()).then(data => {
            if (data.success) {
                wrGoTo('Done');
                loadReviews();
            } else {
                msg.innerHTML = `<span style="color:#dc3545">⚠️ ${data.message}</span>`;
                btn.disabled = false; btn.textContent = 'Submit Review';
            }
        }).catch(() => {
            msg.innerHTML = '<span style="color:#dc3545">Network error. Please try again.</span>';
            btn.disabled = false; btn.textContent = 'Submit Review';
        });
}

// ══════════════════════════════════════════════════════════
// VIRTUAL TRY-ON
// ══════════════════════════════════════════════════════════
let GARMENT_PATH   = <?= json_encode($imagePath . ($allImages[0] ?? '')) ?>;
let tryonPhotoB64  = null;
let tryonPhotoType = 'image/jpeg';

// All product images available for garment selection
const ALL_GARMENT_IMAGES = <?= json_encode(array_map(fn($img) => $imagePath . $img, $allImages)) ?>;

function selectGarmentImage(idx, path) {
    // Update active garment path
    GARMENT_PATH = path;
    // Update border highlights on thumbnails
    ALL_GARMENT_IMAGES.forEach(function(_, i) {
        var thumb = document.getElementById('garmentThumb_' + i);
        if (thumb) thumb.style.borderColor = (i === idx) ? '#7c3aed' : '#ddd';
    });
    // Update outfit preview image if visible
    var outfitPreview = document.getElementById('tryonOutfitPreview');
    if (outfitPreview) {
        outfitPreview.src = '<?= $url_domain ?>' + path;
    }
}

function openTryOn() {
    resetTryOn();
    showModalOnBody('tryOnModal');
}

function previewTryonPhoto(input) {
    const file = input.files[0];
    if (!file) return;
    const allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!allowed.includes(file.type)) {
        showTryonError('Please upload JPG, PNG, or WEBP image.');
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        showTryonError('Photo is too large. Please upload under 5MB.');
        return;
    }
    tryonPhotoType = file.type || 'image/jpeg';
    const reader = new FileReader();
    reader.onload = e => {
        tryonPhotoB64 = e.target.result.split(',')[1];
        document.getElementById('tryonPreviewImg').src = e.target.result;
        document.getElementById('tryonPreviewWrap').style.display = 'block';
        document.getElementById('tryonStartBtn').style.display    = 'block';
    };
    reader.readAsDataURL(file);
}

function startTryOn() {
    if (!tryonPhotoB64) return;
    if (!GARMENT_PATH) {
        showTryonError('Outfit image is missing for this product.');
        return;
    }
    const startBtn = document.getElementById('tryonStartBtn');
    if (startBtn) {
        startBtn.disabled = true;
        startBtn.style.opacity = '0.7';
    }
    document.getElementById('tryonStep1').style.display      = 'none';
    document.getElementById('tryonProcessing').style.display = 'block';
    document.getElementById('tryonResult').style.display     = 'none';
    document.getElementById('tryonError').style.display      = 'none';

    // Animate progress bar (fake but reassuring)
    var bar = document.getElementById('tryonProgressBar');
    var pct = 0;
    var msgs = [
        [0,  'Uploading your photo…'],
        [12, 'Connecting to AI server…'],
        [25, 'AI is analysing your body shape…'],
        [42, 'Fitting the garment…'],
        [60, 'Blending textures…'],
        [75, 'Applying lighting & shadows…'],
        [88, 'Almost ready…'],
    ];
    bar.style.width = '0%';
    var progTimer = setInterval(function() {
        pct = Math.min(pct + (Math.random() * 2.5 + 0.5), 93);
        bar.style.transition = 'width 2s linear';
        bar.style.width = pct + '%';
        msgs.forEach(function(m){ if (Math.round(pct) === m[0]) document.getElementById('tryonStatusMsg').textContent = m[1]; });
    }, 2000);

    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 70000);

    fetch('<?= $url_domain ?>/tryon_ajax.php', {
        method:  'POST',
        headers: {'Content-Type':'application/json'},
        signal: controller.signal,
        body:    JSON.stringify({
            person_b64:    tryonPhotoB64,
            person_type:   tryonPhotoType,
            garment_path:  GARMENT_PATH,
            product_name:  <?= json_encode($name) ?>
        })
    })
    .then(async r => {
        const txt = await r.text();
        let data = null;
        try { data = JSON.parse(txt); } catch (_) {}
        if (!r.ok && !data) {
            throw new Error('Try-on server unavailable');
        }
        return data || { success:false, message:'Try-on response invalid.' };
    })
    .then(data => {
        clearTimeout(timeoutId);
        clearInterval(progTimer);
        bar.style.transition = 'width .4s';
        bar.style.width = '100%';
        setTimeout(function() {
            document.getElementById('tryonProcessing').style.display = 'none';
            if (!data.success) {
                // If server fails, still show local comparison preview so user experience never breaks.
                var label = document.getElementById('tryonResultLabel');
                if (label) label.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Preview Mode — Your photo vs this outfit';
                document.getElementById('tryonAiImg').style.display  = 'none';
                document.getElementById('tryonCanvas').style.display = 'block';
                compositeCanvas({
                    garment_url: '<?= $url_domain ?>/' + GARMENT_PATH.replace(/^\/+/, ''),
                    reason: data.message || 'AI service unavailable, showing instant preview.'
                });
                const statusSub = document.getElementById('tryonStatusSub');
                if (statusSub) statusSub.textContent = data.message || 'AI service unavailable, showing instant preview.';
                if (startBtn) {
                    startBtn.disabled = false;
                    startBtn.style.opacity = '';
                }
                return;
            }
            if (data.mode === 'ai') {
                // Real AI result — show image directly
                var aiImg  = document.getElementById('tryonAiImg');
                var canvas = document.getElementById('tryonCanvas');
                var label  = document.getElementById('tryonResultLabel');
                aiImg.src = data.result_url;
                aiImg.style.display  = 'block';
                canvas.style.display = 'none';
                if (label) label.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#27ae60" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg> AI Try-On — Here\'s how it looks on you! ✨';
                document.getElementById('tryonResult').style.display = 'block';
            } else {
                // Canvas comparison fallback
                var label = document.getElementById('tryonResultLabel');
                if (label) label.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Preview Mode — Your photo vs this outfit';
                document.getElementById('tryonAiImg').style.display  = 'none';
                document.getElementById('tryonCanvas').style.display = 'block';
                compositeCanvas(data);
                if (data.reason) {
                    const statusSub = document.getElementById('tryonStatusSub');
                    if (statusSub) statusSub.textContent = data.reason;
                }
            }
            if (startBtn) {
                startBtn.disabled = false;
                startBtn.style.opacity = '';
            }
        }, 400);
    }).catch(function() {
        clearTimeout(timeoutId);
        clearInterval(progTimer);
        document.getElementById('tryonProcessing').style.display = 'none';
        // Final guard: even on network error, render local preview comparison.
        var label = document.getElementById('tryonResultLabel');
        if (label) label.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Preview Mode — Your photo vs this outfit';
        document.getElementById('tryonAiImg').style.display  = 'none';
        document.getElementById('tryonCanvas').style.display = 'block';
        compositeCanvas({
            garment_url: '<?= $url_domain ?>/' + GARMENT_PATH.replace(/^\/+/, ''),
            reason: 'Network issue with AI service, showing instant preview.'
        });
        const statusSub = document.getElementById('tryonStatusSub');
        if (statusSub) statusSub.textContent = 'Network issue with AI service, showing instant preview.';
        if (startBtn) {
            startBtn.disabled = false;
            startBtn.style.opacity = '';
        }
    });
}

/* ── Canvas: side-by-side comparison ────────────────────── */
// The product photos already have a model wearing the clothes,
// so overlaying doesn't make sense — show a clean comparison instead.
function compositeCanvas(data) {
    const canvas = document.getElementById('tryonCanvas');
    const ctx    = canvas.getContext('2d');

    const personImg  = new Image();
    const garmentImg = new Image();
    garmentImg.crossOrigin = 'anonymous';

    let personLoaded  = false;
    let garmentLoaded = false;

    function tryDraw() {
        if (!personLoaded || !garmentLoaded) return;

        // Target height — normalise both images to same height
        const TARGET_H = 520;
        const GAP      = 16;   // gap between the two panels
        const LABEL_H  = 32;   // space for labels at top
        const PAD      = 14;   // padding around each image
        const RADIUS   = 14;

        // Scale each image to TARGET_H
        const pAspect = (personImg.naturalWidth  || 400) / (personImg.naturalHeight || 500);
        const gAspect = (garmentImg.naturalWidth || 400) / (garmentImg.naturalHeight || 500);
        const pW = Math.round(TARGET_H * pAspect);
        const gW = Math.round(TARGET_H * gAspect);

        const TOTAL_W = PAD + pW + PAD + GAP + PAD + gW + PAD;
        const TOTAL_H = LABEL_H + PAD + TARGET_H + PAD + 38; // 38px bottom info bar

        canvas.width  = TOTAL_W;
        canvas.height = TOTAL_H;

        // ── Background ──────────────────────────────────────
        // Soft gradient bg
        const bgGrad = ctx.createLinearGradient(0, 0, 0, TOTAL_H);
        bgGrad.addColorStop(0, '#f5eeff');
        bgGrad.addColorStop(1, '#fff0f6');
        ctx.fillStyle = bgGrad;
        ctx.fillRect(0, 0, TOTAL_W, TOTAL_H);

        // ── Divider line in the middle ───────────────────────
        const divX = PAD + pW + PAD + GAP / 2;
        ctx.strokeStyle = 'rgba(124,58,237,0.18)';
        ctx.lineWidth   = 1.5;
        ctx.setLineDash([6, 4]);
        ctx.beginPath();
        ctx.moveTo(divX, LABEL_H + 6);
        ctx.lineTo(divX, TOTAL_H - 38);
        ctx.stroke();
        ctx.setLineDash([]);

        // ── VS circle in the middle ──────────────────────────
        const vsY = LABEL_H + PAD + TARGET_H / 2;
        ctx.fillStyle = 'linear-gradient(135deg,#7c3aed,#ED2590)';
        ctx.beginPath();
        ctx.arc(divX, vsY, 18, 0, Math.PI * 2);
        const vsGrad = ctx.createRadialGradient(divX, vsY, 0, divX, vsY, 18);
        vsGrad.addColorStop(0, '#9f6ef5');
        vsGrad.addColorStop(1, '#7c3aed');
        ctx.fillStyle = vsGrad;
        ctx.fill();
        ctx.fillStyle = '#fff';
        ctx.font      = 'bold 11px Arial';
        ctx.textAlign    = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('VS', divX, vsY);

        // ── Helper: draw rounded image panel ────────────────
        function drawPanel(img, x, y, w, h, label) {
            // Card shadow
            ctx.save();
            ctx.shadowColor   = 'rgba(0,0,0,0.12)';
            ctx.shadowBlur    = 12;
            ctx.shadowOffsetY = 4;
            ctx.fillStyle     = '#fff';
            roundedRect(ctx, x - PAD, y - PAD, w + PAD * 2, h + PAD * 2 + LABEL_H, RADIUS);
            ctx.fill();
            ctx.restore();

            // Clip image to rounded rect
            ctx.save();
            roundedRect(ctx, x, y, w, h, RADIUS * 0.6);
            ctx.clip();
            ctx.drawImage(img, x, y, w, h);
            ctx.restore();

            // Label bar at top of card
            ctx.fillStyle = label === 'Your Photo' ? 'rgba(124,58,237,0.82)' : 'rgba(237,37,144,0.82)';
            roundedRectTop(ctx, x - PAD, y - PAD, w + PAD * 2, LABEL_H, RADIUS);
            ctx.fill();
            ctx.fillStyle    = '#fff';
            ctx.font         = `bold 13px Arial`;
            ctx.textAlign    = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(label, x - PAD + (w + PAD * 2) / 2, y - PAD + LABEL_H / 2);
        }

        // Person photo — left panel
        const pX = PAD;
        const pY = LABEL_H + PAD;
        drawPanel(personImg, pX, pY, pW, TARGET_H, 'Your Photo');

        // Garment photo — right panel
        const gX = PAD + pW + PAD + GAP + PAD;
        const gY = LABEL_H + PAD;
        drawPanel(garmentImg, gX, gY, gW, TARGET_H, 'This Outfit');

        // ── Bottom info bar ──────────────────────────────────
        const barY = TOTAL_H - 38;
        ctx.fillStyle = 'rgba(124,58,237,0.07)';
        ctx.fillRect(0, barY, TOTAL_W, 38);

        ctx.fillStyle    = '#7c3aed';
        ctx.font         = 'bold 11px Arial';
        ctx.textAlign    = 'left';
        ctx.textBaseline = 'middle';
        ctx.fillText('✦ Preview Mode', 14, barY + 19);

        ctx.fillStyle = 'rgba(0,0,0,0.3)';
        ctx.font      = '10px Arial';
        ctx.textAlign = 'right';
        ctx.fillText('Kapraa', TOTAL_W - 12, barY + 19);

        document.getElementById('tryonResult').style.display = 'block';
    }

    function roundedRect(ctx, x, y, w, h, r) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + w - r, y);
        ctx.quadraticCurveTo(x + w, y, x + w, y + r);
        ctx.lineTo(x + w, y + h - r);
        ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
        ctx.lineTo(x + r, y + h);
        ctx.quadraticCurveTo(x, y + h, x, y + h - r);
        ctx.lineTo(x, y + r);
        ctx.quadraticCurveTo(x, y, x + r, y);
        ctx.closePath();
    }

    function roundedRectTop(ctx, x, y, w, h, r) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + w - r, y);
        ctx.quadraticCurveTo(x + w, y, x + w, y + r);
        ctx.lineTo(x + w, y + h);
        ctx.lineTo(x, y + h);
        ctx.lineTo(x, y + r);
        ctx.quadraticCurveTo(x, y, x + r, y);
        ctx.closePath();
    }

    personImg.onload  = () => { personLoaded  = true; tryDraw(); };
    personImg.onerror = () => showTryonError('Could not load your photo.');
    personImg.src     = 'data:' + tryonPhotoType + ';base64,' + tryonPhotoB64;

    garmentImg.onload  = () => { garmentLoaded  = true; tryDraw(); };
    garmentImg.onerror = () => showTryonError('Could not load outfit image.');
    garmentImg.src     = data.garment_url;
}

function showTryonError(msg) {
    document.getElementById('tryonProcessing').style.display = 'none';
    document.getElementById('tryonErrorMsg').textContent = msg || 'Try-on failed.';
    document.getElementById('tryonError').style.display  = 'block';
}

function downloadTryOn() {
    // AI mode: download the result image directly
    var aiImg = document.getElementById('tryonAiImg');
    if (aiImg && aiImg.style.display !== 'none' && aiImg.src) {
        var link = document.createElement('a');
        link.download = 'kapraa-tryon.jpg';
        link.href = aiImg.src;
        link.target = '_blank';
        link.click();
        return;
    }
    // Canvas fallback
    var canvas = document.getElementById('tryonCanvas');
    if (!canvas || canvas.width === 0) return;
    var link = document.createElement('a');
    link.download = 'kapraa-tryon.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
}

function resetTryOn() {
    tryonPhotoB64 = null;
    // Reset garment path to first image
    if (ALL_GARMENT_IMAGES.length > 0) {
        GARMENT_PATH = ALL_GARMENT_IMAGES[0];
        ALL_GARMENT_IMAGES.forEach(function(_, i) {
            var thumb = document.getElementById('garmentThumb_' + i);
            if (thumb) thumb.style.borderColor = (i === 0) ? '#7c3aed' : '#ddd';
        });
    }
    document.getElementById('tryonStep1').style.display      = 'block';
    document.getElementById('tryonProcessing').style.display = 'none';
    document.getElementById('tryonResult').style.display     = 'none';
    document.getElementById('tryonError').style.display      = 'none';
    document.getElementById('tryonPreviewWrap').style.display = 'none';
    document.getElementById('tryonStartBtn').style.display   = 'none';
    document.getElementById('tryonStartBtn').disabled        = false;
    document.getElementById('tryonStartBtn').style.opacity   = '';
    document.getElementById('tryonPhotoInput').value         = '';
}
</script>

<!-- ══ SIZE GUIDE MODAL ══ -->
<div id="sizeGuideModal" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.6);align-items:center;justify-content:center;padding:16px;">
  <div style="background:#fff;border-radius:16px;max-width:640px;width:100%;max-height:90vh;overflow-y:auto;position:relative;box-shadow:0 20px 60px rgba(0,0,0,.3);">
    <!-- Header -->
    <div style="padding:20px 24px 16px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#fff;z-index:2;">
      <div>
        <h5 style="margin:0;font-weight:800;font-size:1.1rem;">Size Guide</h5>
        <p style="margin:4px 0 0;font-size:12px;color:#888;">Measurements are in inches. CM values in brackets.</p>
      </div>
      <button onclick="document.getElementById('sizeGuideModal').style.display='none'"
              style="border:none;background:rgba(0,0,0,.08);border-radius:50%;width:32px;height:32px;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;">✕</button>
    </div>
    <!-- Body -->
    <div style="padding:20px 24px;">
      <!-- Women's Suits/Kameez Size Chart -->
      <h6 style="font-weight:800;margin:0 0 12px;color:#1f2937;">Women's Suits / Kameez</h6>
      <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;min-width:480px;">
          <thead>
            <tr style="background:#7c3aed;color:#fff;">
              <th style="padding:10px 14px;text-align:left;border-radius:8px 0 0 0;">Size</th>
              <th style="padding:10px 14px;text-align:center;">Chest</th>
              <th style="padding:10px 14px;text-align:center;">Waist</th>
              <th style="padding:10px 14px;text-align:center;">Hip</th>
              <th style="padding:10px 14px;text-align:center;border-radius:0 8px 0 0;">Kameez Length</th>
            </tr>
          </thead>
          <tbody>
            <tr style="background:#fafafa;">
              <td style="padding:10px 14px;font-weight:700;border-bottom:1px solid #f0f0f0;">XS</td>
              <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #f0f0f0;">34" (86cm)</td>
              <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #f0f0f0;">26" (66cm)</td>
              <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #f0f0f0;">36" (91cm)</td>
              <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #f0f0f0;">40" (102cm)</td>
            </tr>
            <tr>
              <td style="padding:10px 14px;font-weight:700;border-bottom:1px solid #f0f0f0;">S</td>
              <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #f0f0f0;">36" (91cm)</td>
              <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #f0f0f0;">28" (71cm)</td>
              <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #f0f0f0;">38" (97cm)</td>
              <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #f0f0f0;">42" (107cm)</td>
            </tr>
            <tr style="background:#fafafa;">
              <td style="padding:10px 14px;font-weight:700;border-bottom:1px solid #f0f0f0;">M</td>
              <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #f0f0f0;">38" (97cm)</td>
              <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #f0f0f0;">30" (76cm)</td>
              <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #f0f0f0;">40" (102cm)</td>
              <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #f0f0f0;">42" (107cm)</td>
            </tr>
            <tr>
              <td style="padding:10px 14px;font-weight:700;border-bottom:1px solid #f0f0f0;">L</td>
              <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #f0f0f0;">40" (102cm)</td>
              <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #f0f0f0;">32" (81cm)</td>
              <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #f0f0f0;">42" (107cm)</td>
              <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #f0f0f0;">43" (109cm)</td>
            </tr>
            <tr style="background:#fafafa;">
              <td style="padding:10px 14px;font-weight:700;border-bottom:1px solid #f0f0f0;">XL</td>
              <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #f0f0f0;">42" (107cm)</td>
              <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #f0f0f0;">34" (86cm)</td>
              <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #f0f0f0;">44" (112cm)</td>
              <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #f0f0f0;">43" (109cm)</td>
            </tr>
            <tr>
              <td style="padding:10px 14px;font-weight:700;border-bottom:1px solid #f0f0f0;">XXL</td>
              <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #f0f0f0;">44" (112cm)</td>
              <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #f0f0f0;">36" (91cm)</td>
              <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #f0f0f0;">46" (117cm)</td>
              <td style="padding:10px 14px;text-align:center;border-bottom:1px solid #f0f0f0;">44" (112cm)</td>
            </tr>
            <tr style="background:#fafafa;">
              <td style="padding:10px 14px;font-weight:700;">3XL</td>
              <td style="padding:10px 14px;text-align:center;">46" (117cm)</td>
              <td style="padding:10px 14px;text-align:center;">38" (97cm)</td>
              <td style="padding:10px 14px;text-align:center;">48" (122cm)</td>
              <td style="padding:10px 14px;text-align:center;">44" (112cm)</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Dupatta / Trouser note -->
      <div style="margin-top:16px;padding:14px;background:#f8f4ff;border-radius:10px;border-left:3px solid #7c3aed;">
        <p style="margin:0;font-size:12px;color:#555;line-height:1.7;">
          <strong style="color:#7c3aed;">Dupatta:</strong> Standard 2.5m length (all sizes)<br>
          <strong style="color:#7c3aed;">Trouser (Shalwar):</strong> Add 2" to hip measurement for comfortable fit<br>
          <strong style="color:#7c3aed;">Tip:</strong> If between sizes, choose the larger size for a comfortable fit
        </p>
      </div>

      <!-- How to measure -->
      <h6 style="font-weight:800;margin:20px 0 12px;color:#1f2937;">How to Measure</h6>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div style="padding:12px;background:#f9f9f9;border-radius:8px;font-size:12px;">
          <strong>Chest:</strong> Measure around the fullest part of your chest, keeping the tape horizontal.
        </div>
        <div style="padding:12px;background:#f9f9f9;border-radius:8px;font-size:12px;">
          <strong>Waist:</strong> Measure around the narrowest part of your natural waistline.
        </div>
        <div style="padding:12px;background:#f9f9f9;border-radius:8px;font-size:12px;">
          <strong>Hip:</strong> Measure around the fullest part of your hips, keeping the tape parallel to the floor.
        </div>
        <div style="padding:12px;background:#f9f9f9;border-radius:8px;font-size:12px;">
          <strong>Length:</strong> Measure from the top of the shoulder down to the desired garment length.
        </div>
      </div>
    </div>
  </div>
</div>
<script>
// Close size guide modal on backdrop click
document.getElementById('sizeGuideModal').addEventListener('click', function(e){
  if (e.target === this) this.style.display = 'none';
});
document.addEventListener('keydown', function(e){
  if (e.key === 'Escape') {
    var m = document.getElementById('sizeGuideModal');
    if (m) m.style.display = 'none';
  }
});
</script>

<!-- ══════════ AI SIZE RECOMMENDER POPUP ══════════ -->
<div id="sizeAiOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:99999;align-items:center;justify-content:center;">
<div style="background:#fff;border-radius:20px;width:100%;max-width:460px;margin:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.3);position:relative;">

  <!-- Header -->
  <div style="background:linear-gradient(135deg,#1a1a2e,#0f3460);padding:22px 24px;color:#fff;position:relative;">
    <div style="font-size:1.2rem;font-weight:800;"><i class="fa fa-magic me-2" style="color:#f9ca24;"></i>AI Size Recommender</div>
    <div style="font-size:.8rem;color:rgba(255,255,255,.6);margin-top:3px;">Powered by Google Gemini AI</div>
    <button onclick="closeSizeAI()" style="position:absolute;top:14px;right:16px;background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:50%;width:30px;height:30px;cursor:pointer;font-size:1rem;line-height:30px;text-align:center;">&times;</button>
  </div>

  <!-- Form -->
  <div style="padding:24px;" id="sizeAiForm">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
      <div>
        <label style="font-size:.78rem;font-weight:700;color:#555;display:block;margin-bottom:4px;">Gender</label>
        <select id="ai_gender" style="width:100%;border:1.5px solid #e0e0e0;border-radius:10px;padding:9px 12px;font-size:.85rem;outline:none;">
          <option value="female">Female</option>
          <option value="male">Male</option>
        </select>
      </div>
      <div>
        <label style="font-size:.78rem;font-weight:700;color:#555;display:block;margin-bottom:4px;">Age</label>
        <input type="number" id="ai_age" placeholder="e.g. 25" min="10" max="80"
               style="width:100%;border:1.5px solid #e0e0e0;border-radius:10px;padding:9px 12px;font-size:.85rem;outline:none;">
      </div>
      <div>
        <label style="font-size:.78rem;font-weight:700;color:#555;display:block;margin-bottom:4px;">Height (cm)</label>
        <input type="number" id="ai_height" placeholder="e.g. 162" min="100" max="220"
               style="width:100%;border:1.5px solid #e0e0e0;border-radius:10px;padding:9px 12px;font-size:.85rem;outline:none;">
      </div>
      <div>
        <label style="font-size:.78rem;font-weight:700;color:#555;display:block;margin-bottom:4px;">Weight (kg)</label>
        <input type="number" id="ai_weight" placeholder="e.g. 58" min="20" max="200"
               style="width:100%;border:1.5px solid #e0e0e0;border-radius:10px;padding:9px 12px;font-size:.85rem;outline:none;">
      </div>
    </div>
    <div style="margin-top:14px;">
      <label style="font-size:.78rem;font-weight:700;color:#555;display:block;margin-bottom:4px;">Body Type</label>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <?php foreach(['Slim','Regular','Athletic','Plus Size','Petite'] as $bt): ?>
        <label style="cursor:pointer;">
          <input type="radio" name="ai_body" value="<?= $bt ?>" style="display:none;" <?= $bt==='Regular'?'checked':'' ?>>
          <span class="ai-body-btn" data-val="<?= $bt ?>" style="display:inline-block;padding:6px 14px;border:1.5px solid #e0e0e0;border-radius:20px;font-size:.78rem;font-weight:600;color:#555;transition:all .2s;<?= $bt==='Regular'?'background:#1a1a2e;color:#fff;border-color:#1a1a2e;':'' ?>"><?= $bt ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    </div>
    <div style="margin-top:16px;padding:10px 14px;background:#fff9db;border-radius:10px;font-size:.76rem;color:#856404;border:1px solid #fde68a;">
      <i class="fa fa-lock me-1"></i>Your measurements are not stored — used only for this recommendation.
    </div>
    <button onclick="runSizeAI()" id="sizeAiBtn"
            style="margin-top:16px;width:100%;background:linear-gradient(135deg,#f9ca24,#f0932b);color:#1a1a1a;border:none;border-radius:12px;padding:13px;font-size:.95rem;font-weight:800;cursor:pointer;transition:opacity .2s;">
      <i class="fa fa-magic me-2"></i>Get My Size Recommendation
    </button>
  </div>

  <!-- Result -->
  <div id="sizeAiResult" style="display:none;padding:24px;">
    <div id="sizeAiLoading" style="text-align:center;padding:30px 0;">
      <div style="width:48px;height:48px;border:4px solid #f0f0f0;border-top-color:#f0932b;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 14px;"></div>
      <div style="font-size:.88rem;color:#888;">AI is analysing your measurements…</div>
    </div>
    <div id="sizeAiAnswer" style="display:none;">
      <div style="text-align:center;margin-bottom:20px;">
        <div style="font-size:.8rem;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">Recommended Size</div>
        <div id="aiSizeBadge" style="display:inline-block;font-size:2.8rem;font-weight:900;background:linear-gradient(135deg,#1a1a2e,#0f3460);color:#f9ca24;border-radius:16px;padding:10px 36px;"></div>
        <div id="aiConfBadge" style="margin-top:10px;display:inline-block;padding:4px 14px;border-radius:20px;font-size:.75rem;font-weight:700;"></div>
      </div>
      <div id="aiFitTip"   style="background:#f0f9ff;border-radius:10px;padding:12px 16px;font-size:.83rem;color:#1e40af;margin-bottom:10px;"><i class="fa fa-lightbulb-o me-2"></i></div>
      <div id="aiSizeNote" style="background:#f0fdf4;border-radius:10px;padding:12px 16px;font-size:.83rem;color:#15803d;margin-bottom:16px;"><i class="fa fa-info-circle me-2"></i></div>
      <div style="display:flex;gap:10px;">
        <button onclick="applySizeAI()" id="applyAiBtn"
                style="flex:1;background:#111;color:#fff;border:none;border-radius:10px;padding:11px;font-size:.88rem;font-weight:700;cursor:pointer;">
          <i class="fa fa-check me-1"></i>Select This Size
        </button>
        <button onclick="resetSizeAI()"
                style="background:#f5f5f5;color:#555;border:none;border-radius:10px;padding:11px 16px;font-size:.88rem;cursor:pointer;">
          <i class="fa fa-refresh"></i>
        </button>
      </div>
    </div>
    <div id="sizeAiError" style="display:none;text-align:center;padding:20px;color:#dc2626;font-size:.88rem;">
      <i class="fa fa-exclamation-circle" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
      <span></span>
    </div>
  </div>

</div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
#sizeAiOverlay { display: none; }
#sizeAiOverlay.active { display: flex !important; }
</style>

<script>
(function(){
  var _aiSize = null;

  window.openSizeAI = function() {
    document.getElementById('sizeAiOverlay').classList.add('active');
  };
  window.closeSizeAI = function() {
    document.getElementById('sizeAiOverlay').classList.remove('active');
  };
  window.resetSizeAI = function() {
    document.getElementById('sizeAiResult').style.display = 'none';
    document.getElementById('sizeAiForm').style.display = 'block';
    document.getElementById('sizeAiAnswer').style.display = 'none';
    document.getElementById('sizeAiLoading').style.display = 'block';
    _aiSize = null;
  };

  // Body type pill toggle
  document.querySelectorAll('.ai-body-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.ai-body-btn').forEach(function(b) {
        b.style.background = ''; b.style.color = '#555'; b.style.borderColor = '#e0e0e0';
      });
      btn.style.background = '#1a1a2e'; btn.style.color = '#fff'; btn.style.borderColor = '#1a1a2e';
      document.querySelector('input[name="ai_body"][value="'+btn.dataset.val+'"]').checked = true;
    });
  });

  // Close on overlay click
  document.getElementById('sizeAiOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeSizeAI();
  });

  window.runSizeAI = function() {
    var height   = document.getElementById('ai_height').value;
    var weight   = document.getElementById('ai_weight').value;
    var age      = document.getElementById('ai_age').value;
    var gender   = document.getElementById('ai_gender').value;
    var bodyType = (document.querySelector('input[name="ai_body"]:checked') || {}).value || 'Regular';

    if (!height || !weight) {
      alert('Please enter your height and weight.');
      return;
    }

    document.getElementById('sizeAiForm').style.display = 'none';
    document.getElementById('sizeAiResult').style.display = 'block';
    document.getElementById('sizeAiLoading').style.display = 'block';
    document.getElementById('sizeAiAnswer').style.display = 'none';
    document.getElementById('sizeAiError').style.display = 'none';

    var fd = new FormData();
    fd.append('height', height);
    fd.append('weight', weight);
    fd.append('age', age);
    fd.append('gender', gender);
    fd.append('body_type', bodyType);
    fd.append('category', <?= json_encode($category_name ?? '') ?>);
    fd.append('brand', <?= json_encode($brand_name ?? '') ?>);
    fd.append('available_sizes', <?= json_encode($sizeNames ?? '') ?>);

    fetch('<?= $url_domain ?>/size_recommender.php', { method: 'POST', body: fd })
      .then(function(r){ return r.json(); })
      .then(function(data) {
        document.getElementById('sizeAiLoading').style.display = 'none';
        if (data.error) {
          document.getElementById('sizeAiError').style.display = 'block';
          document.getElementById('sizeAiError').querySelector('span').textContent = data.error;
          return;
        }
        _aiSize = data.recommended_size;
        document.getElementById('aiSizeBadge').textContent = data.recommended_size;

        var conf = document.getElementById('aiConfBadge');
        var confColors = { 'High': '#dcfce7|#15803d', 'Medium': '#fff9db|#856404', 'Low': '#fee2e2|#dc2626' };
        var cc = (confColors[data.confidence] || '#f3f4f6|#6b7280').split('|');
        conf.style.background = cc[0]; conf.style.color = cc[1];
        conf.textContent = data.confidence + ' Confidence';

        document.getElementById('aiFitTip').innerHTML   = '<i class="fa fa-lightbulb-o me-2"></i>' + (data.fit_tip || '');
        document.getElementById('aiSizeNote').innerHTML = '<i class="fa fa-info-circle me-2"></i>' + (data.size_note || '');

        // Show/hide apply button if size exists in product
        var availSizes = <?= json_encode($sizeNamesArray ?? []) ?>;
        var applyBtn = document.getElementById('applyAiBtn');
        if (availSizes.length > 0) {
          applyBtn.style.display = '';
        } else {
          applyBtn.style.display = 'none';
        }

        document.getElementById('sizeAiAnswer').style.display = 'block';
      })
      .catch(function() {
        document.getElementById('sizeAiLoading').style.display = 'none';
        document.getElementById('sizeAiError').style.display = 'block';
        document.getElementById('sizeAiError').querySelector('span').textContent = 'Connection error. Please try again.';
      });
  };

  window.applySizeAI = function() {
    if (!_aiSize) return;
    var tags = document.querySelectorAll('.size-tag');
    var matched = false;
    tags.forEach(function(tag) {
      if (tag.dataset.size.toUpperCase() === _aiSize.toUpperCase()) {
        tag.click();
        matched = true;
      }
    });
    closeSizeAI();
    if (matched) {
      // Smooth scroll to size section
      var sec = document.getElementById('sizes-section');
      if (sec) sec.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  };
})();
</script>
<!-- Meta Pixel ViewContent Event -->
<script>
fbq('track', 'ViewContent', {
    content_ids: [<?php echo json_encode((string)$productID); ?>],
    content_name: <?php echo json_encode($name); ?>,
    content_type: 'product',
    value: <?php echo json_encode((float)(!empty($price_after_discount) && $price_after_discount < $price ? $price_after_discount : $price)); ?>,
    currency: 'PKR'
});
</script>
<!-- Meta Pixel AddToCart Event -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    var pixelProductId    = <?php echo json_encode((string)$productID); ?>;
    var pixelProductName  = <?php echo json_encode($name); ?>;
    var pixelProductPrice = <?php echo json_encode((float)(!empty($price_after_discount) && $price_after_discount < $price ? $price_after_discount : $price)); ?>;

    function fireAddToCart() {
        if (typeof fbq === 'function') {
            fbq('track', 'AddToCart', {
                content_ids: [pixelProductId],
                content_name: pixelProductName,
                content_type: 'product',
                value: pixelProductPrice,
                currency: 'PKR'
            });
        }
    }

    var addBtn = document.querySelector('.add-to-cart-btn');
    if (addBtn) addBtn.addEventListener('click', fireAddToCart);

    var buyBtn = document.querySelector('.buy-now-btn');
    if (buyBtn) buyBtn.addEventListener('click', fireAddToCart);
});
</script>


<!-- Meta Pixel InitiateCheckout Event -->


<?php
  include 'include/footer.php';
?>
