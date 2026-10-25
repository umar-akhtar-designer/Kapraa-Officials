<style>
    /* Price styling */
    .price-container {
        position: relative;
        padding: 10px 0;
    }
    
    .original-price {
        color: #6c757d; /* Gray color */
        text-decoration: line-through;
        font-size: 1rem;
        margin: 0;
        font-weight: normal;
    }
    
    .discounted-price {
        color: #dc3545; /* Red color */
        font-weight: bold;
        margin: 5px 0;
        font-size: 1.2rem;
    }
    
    .regular-price {
        color: #212529;
        font-weight: bold;
        margin: 5px 0;
        font-size: 1.2rem;
    }

    /* Badge Styles */
    .image-container {
        position: relative;
    }
    
    .sale-badge {
        position: absolute;
        top: 10px;
        left: 10px;
        background-color: #74B549;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 12px;
        z-index: 1;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .out-of-stock-badge {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 8px 15px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 14px;
        z-index: 1;
        text-align: center;
        width: 80%;
    }
    
    .product-image {
        width: 100%;
        height: auto;
        display: block;
        border-radius:4px;
    }
</style>
<div class="col-md-12 mt-4">
    <header class="section-header">
        <h2 class="section-header__title text-center h2"><span>Products You've Viewed</span></h2>
        <p class="sub-heading">Based on your browsing history</p>
    </header>
    <div class="row">
        <div class="row">
            <?php
                // SOLUTION: Use sessions to preserve visitor_id during same visit
                if (!isset($_SESSION['visitor_id'])) {
                    if (isset($_COOKIE['visitor_id'])) {
                        // Use existing cookie
                        $_SESSION['visitor_id'] = $_COOKIE['visitor_id'];
                    } else {
                        // Create new visitor ID
                        $_SESSION['visitor_id'] = uniqid('visitor_', true);
                        setcookie('visitor_id', $_SESSION['visitor_id'], time() + (365 * 24 * 60 * 60), "/");
                    }
                }

                $visitor_id = $_SESSION['visitor_id'];

                // Debug info (remove in production)
                echo "<!-- Debug: Session Visitor ID: " . $visitor_id . " -->";
                echo "<!-- Debug: Cookie Visitor ID: " . ($_COOKIE['visitor_id'] ?? 'NOT SET') . " -->";

                // Get products this visitor viewed
                $query = "
                    SELECT p.*, v.view_time
                    FROM products p
                    INNER JOIN product_views v ON p.id = v.productID
                    WHERE v.visitor_id = ? AND p.is_active != 0
                    ORDER BY id DESC
                    LIMIT 4
                ";

                $stmt = $connection_database->prepare($query);
                $stmt->bind_param("s", $visitor_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($data = $result->fetch_assoc()) {
                        
                        $productID          = $data['id'];
                        $serial_no          = $data['serial_no'];
                        $sku                = $data['sku'];
                        $name               = $data['name'];
                        $category           = $data['category'];
                        $sub_category       = $data['sub_category'];
                        $product_type       = $data['product_type'];
                        $slug               = $data['slug'];
                        $description        = $data['description'];
                        $brand              = $data['brand'];
                        $size               = $data['size'];
                        $color              = $data['color'];
                        $unit               = $data['unit'];
                        $order_qty          = $data['order_qty'];
                        $price              = $data['price'];
                        $dis_amount         = $data['dis_amount'];
                        $price_after_discount = $data['price_after_discount'];
                        $shipp_cost         = $data['shipp_cost'];
                        $link               = $data['link'];
                        $seo_title          = $data['seo_title'];
                        $seo_des            = $data['seo_des'];
                        $stock              = $data['stock'];
                        $sale               = $data['sale'];
                        $imageString        = $data['image'];
                        $view_time          = $data['view_time'];

                        // Convert images into array
                        $images = explode(",", $imageString);
                        // Get the first image
                        $firstImage = !empty($images) ? trim($images[0]) : '';
                        
                        // Calculate discount percentage if discount exists
                        $hasDiscount = ($dis_amount > 0 && $price_after_discount < $price);
                        $discountPercentage = 0;
                        if ($hasDiscount) {
                            $discountPercentage = round(($dis_amount / $price) * 100);
                        }
                        
                        // Format view time
                        $formattedViewTime = date("M d, Y", strtotime($view_time));
                        
                        // Get category names for multiple categories
                        $categoryNames = '';
                        if (!empty($category)) {
                            $categoryIds = explode(',', $category);
                            $categoryNamesArray = [];
                            foreach ($categoryIds as $categoryId) {
                                $categoryId = trim($categoryId);
                                if (!empty($categoryId)) {
                                    $catQuery = selectQuery("SELECT name FROM category WHERE id = '$categoryId'");
                                    if ($catQuery['status'] && !empty($catQuery['data'])) {
                                        $categoryNamesArray[] = $catQuery['data'][0]['name'];
                                    }
                                }
                            }
                            $categoryNames = implode(', ', $categoryNamesArray);
                        }
                        
                        // Get subcategory names for multiple subcategories
                        $subcategoryNames = '';
                        if (!empty($sub_category)) {
                            $subcategoryIds = explode(',', $sub_category);
                            $subcategoryNamesArray = [];
                            foreach ($subcategoryIds as $subcategoryId) {
                                $subcategoryId = trim($subcategoryId);
                                if (!empty($subcategoryId)) {
                                    $subcatQuery = selectQuery("SELECT name FROM subcategory WHERE id = '$subcategoryId'");
                                    if ($subcatQuery['status'] && !empty($subcatQuery['data'])) {
                                        $subcategoryNamesArray[] = $subcatQuery['data'][0]['name'];
                                    }
                                }
                            }
                            $subcategoryNames = implode(', ', $subcategoryNamesArray);
                        }
                        
                        // Get brand names for multiple brands
                        $brandNames = '';
                        if (!empty($brand)) {
                            $brandIds = explode(',', $brand);
                            $brandNamesArray = [];
                            foreach ($brandIds as $brandId) {
                                $brandId = trim($brandId);
                                if (!empty($brandId)) {
                                    $brandQuery = selectQuery("SELECT name FROM brand WHERE id = '$brandId'");
                                    if ($brandQuery['status'] && !empty($brandQuery['data'])) {
                                        $brandNamesArray[] = $brandQuery['data'][0]['name'];
                                    }
                                }
                            }
                            $brandNames = implode(', ', $brandNamesArray);
                        }
                        
                        echo '
                            <a href="' . $url_domain . '/product/' . $slug . '" class="col-md-3 col-6 mb-3">
                                <div class="card border-0 border-radius text-center">
                                    <div class="image-container position-relative">
                                        <img src="'.$url_domain.'/admin/images/product/'.$firstImage.'" alt="'.$name.'" class="product-image">
                                        
                                        <!-- Sale Badge -->
                                        '.($sale == "In Sale" ? '<div class="sale-badge">In Sale</div>' : '').'
                                        
                                        <!-- Out of Stock Badge -->
                                        '.($stock == "Out Stock" ? '<div class="out-of-stock-badge">SOLD OUT</div>' : '').'
                                    </div>
                                    <p class="mt-2 mb-0">'.$name.'</p>
                                    <div class="price-container">';
                                    
                                    if ($hasDiscount) {
                                        echo '
                                            <h5 class="original-price">Rs. '.number_format($price, 2).'</h5>
                                            <h5 class="discounted-price">Rs. '.number_format($price_after_discount, 2).'</h5>
                                        ';
                                    } else {
                                        echo '<h5 class="regular-price">Rs. '.number_format($price, 2).'</h5>';
                                    }
                                    
                                echo '
                                    </div>
                                </div>
                            </a>
                        ';
                    }
                } else {
                    echo '<div class="col-12 text-center my-5">';
                    echo '<h5>No products viewed yet</h5>';
                    echo '<p>Browse products on our website and they will appear here.</p>';
                    echo '</div>';
                }
            ?>
        </div>
    </div>
</div>