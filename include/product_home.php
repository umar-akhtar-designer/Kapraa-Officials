<?php
   // Initialize variables
    $searchConditions = [];
    $filterQuery = '';

    // Get filter parameters from POST or SESSION
    $searchQuery = '';
    $categoryFilter = '';
    $subCategoryFilter = '';
    $brandFilter = '';
    $colorFilter = '';
    $sizeFilter = '';
    $minPriceFilter = '';
    $maxPriceFilter = '';
    $sortFilter = '';

    // Get## women category ID
    $womenCategoryQuery = selectQuery("SELECT id FROM category WHERE name LIKE '%women%'");
    $womenCategoryId = 1; // Default fallback
    if ($womenCategoryQuery['status'] === true && !empty($womenCategoryQuery['data'])) {
        $womenCategoryId = $womenCategoryQuery['data'][0]['id'];
    }

    // Check if form is submitted via POST
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Check if clear filters is requested
        if (isset($_POST['clear_filters'])) {
            unset($_SESSION['product_filters']);
            // Reset all filter variables to default
            $searchQuery = '';
            $categoryFilter = '';
            $subCategoryFilter = '';
            $brandFilter = '';
            $colorFilter = '';
            $sizeFilter = '';
            $minPriceFilter = '';
            $maxPriceFilter = '';
            $sortFilter = '';
        } 
        // Check if form is submitted with filters
        elseif (isset($_POST['filter'])) {
            // Store filter values in session to persist them
            $_SESSION['product_filters'] = $_POST;
            
            $searchQuery = isset($_POST['search']) ? trim($_POST['search']) : '';
            $categoryFilter = isset($_POST['category']) ? intval($_POST['category']) : '';
            $subCategoryFilter = isset($_POST['sub_category']) ? intval($_POST['sub_category']) : '';
            $brandFilter = isset($_POST['brand']) ? intval($_POST['brand']) : '';
            $colorFilter = isset($_POST['color']) ? intval($_POST['color']) : '';
            $sizeFilter = isset($_POST['size']) ? intval($_POST['size']) : '';
            $minPriceFilter = isset($_POST['min_price']) ? floatval($_POST['min_price']) : '';
            $maxPriceFilter = isset($_POST['max_price']) ? floatval($_POST['max_price']) : '';
            $sortFilter = isset($_POST['sort']) ? $_POST['sort'] : '';
        }
    } 
    // Load filters from session if set
    elseif (isset($_SESSION['product_filters'])) {
        $filters = $_SESSION['product_filters'];
        $searchQuery = isset($filters['search']) ? trim($filters['search']) : '';
        $categoryFilter = isset($filters['category']) ? intval($filters['category']) : '';
        $subCategoryFilter = isset($filters['sub_category']) ? intval($filters['sub_category']) : '';
        $brandFilter = isset($filters['brand']) ? intval($filters['brand']) : '';
        $colorFilter = isset($filters['color']) ? intval($filters['color']) : '';
        $sizeFilter = isset($filters['size']) ? intval($filters['size']) : '';
        $minPriceFilter = isset($filters['min_price']) ? floatval($filters['min_price']) : '';
        $maxPriceFilter = isset($filters['max_price']) ? floatval($filters['max_price']) : '';
        $sortFilter = isset($filters['sort']) ? $filters['sort'] : '';
    }

    // Check if clear filters is requested via GET
    if (isset($_GET['clear'])) {
        unset($_SESSION['product_filters']);
        // Redirect to same page without any parameters
        header("Location: " . basename($_SERVER['PHP_SELF']));
        exit();
    }

    // Check if we have any active filters
    $hasActiveFilters = !empty($searchQuery) || 
                    !empty($categoryFilter) || 
                    !empty($subCategoryFilter) || 
                    !empty($brandFilter) || 
                    !empty($colorFilter) || 
                    !empty($sizeFilter) || 
                    !empty($minPriceFilter) ||
                    !empty($maxPriceFilter) ||
                    !empty($sortFilter);

    // Add search conditions for each field if provided
    if (!empty($searchQuery)) {
        $searchConditions[] = "(name LIKE '%$searchQuery%' OR description LIKE '%$searchQuery%' OR sku LIKE '%$searchQuery%' OR seo_title LIKE '%$searchQuery%' OR seo_des LIKE '%$searchQuery%')";
    }

    if (!empty($categoryFilter)) {
        // Modified to check if the category ID exists in the comma-separated list
        $searchConditions[] = "FIND_IN_SET('$categoryFilter', category) > 0";
    }

    if (!empty($subCategoryFilter)) {
        // Modified to check if the subcategory ID exists in the comma-separated list
        $searchConditions[] = "FIND_IN_SET('$subCategoryFilter', sub_category) > 0";
    }

    if (!empty($brandFilter)) {
        // Modified to check if the brand ID exists in the comma-separated list
        $searchConditions[] = "FIND_IN_SET('$brandFilter', brand) > 0";
    }

    if (!empty($colorFilter)) {
        // Modified to check if the color ID exists in the comma-separated list
        $searchConditions[] = "FIND_IN_SET('$colorFilter', color) > 0";
    }

    if (!empty($sizeFilter)) {
        // Modified to check if the size ID exists in the comma-separated list
        $searchConditions[] = "FIND_IN_SET('$sizeFilter', size) > 0";
    }

    // Add price range condition if provided
    if (!empty($minPriceFilter) || !empty($maxPriceFilter)) {
        $min = !empty($minPriceFilter) ? $minPriceFilter : 0;
        $max = !empty($maxPriceFilter) ? $maxPriceFilter : 999999999;
        $searchConditions[] = "price BETWEEN '$min' AND '$max'";
    }

    // Force to include women category (check if women category ID exists in the comma-separated list)
    $searchConditions[] = "FIND_IN_SET('$womenCategoryId', category) > 0";

    // Combine search conditions with AND (if there are any)
    if (count($searchConditions) > 0) {
        $filterQuery = "AND " . implode(' AND ', $searchConditions);
    }

    // Build sorting query
    $orderBy = "ORDER BY id DESC";
    switch ($sortFilter) {
        case 'price_low_high':
            $orderBy = "ORDER BY price ASC";
            break;
        case 'price_high_low':
            $orderBy = "ORDER BY price DESC";
            break;
        case 'newest':
            $orderBy = "ORDER BY id DESC";
            break;
        case 'popularity':
            $orderBy = "ORDER BY sale DESC, id DESC";
            break;
    }

    // Fetch category options
    $categoryQuery = selectQuery("SELECT * FROM category");
    $categories = '<option value="">Select Category</option>';
    if ($categoryQuery['status'] === true) {
        foreach ($categoryQuery['data'] as $data) {
            $category_id = $data['id'];
            $category_name = $data['name'];
            $selected = ($categoryFilter == $category_id) ? 'selected' : '';
            $categories .= '<option value="' . $category_id . '" ' . $selected . '>' . $category_name . '</option>';
        }
    }

    // Fetch subcategory options
    $subcategoryQuery = selectQuery("SELECT * FROM subcategory");
    $subcategories = '<option value="">Select Subcategory</option>';
    if ($subcategoryQuery['status'] === true) {
        foreach ($subcategoryQuery['data'] as $data) {
            $subcategory_id = $data['id'];
            $subcategory_name = $data['name'];
            $selected = ($subCategoryFilter == $subcategory_id) ? 'selected' : '';
            $subcategories .= '<option value="' . $subcategory_id . '" ' . $selected . '>' . $subcategory_name . '</option>';
        }
    }

    // Fetch brand options
    $brandQuery = selectQuery("SELECT * FROM brand");
    $brand_options = '<option value="">Select Brand</option>';
    if ($brandQuery['status'] === true) {
        foreach ($brandQuery['data'] as $data) {
            $brand_id = $data['id'];
            $brand_name = $data['name'];
            $selected = ($brandFilter == $brand_id) ? 'selected' : '';
            $brand_options .= '<option value="' . $brand_id . '" ' . $selected . '>' . $brand_name . '</option>';
        }
    }

    // Fetch size options
    $sizeQuery = selectQuery("SELECT * FROM size");
    $size_options = '<option value="">Select Size</option>';
    if ($sizeQuery['status'] === true) {
        foreach ($sizeQuery['data'] as $data) {
            $size_id = $data['id'];
            $size_name = $data['name'];
            $selected = ($sizeFilter == $size_id) ? 'selected' : '';
            $size_options .= '<option value="' . $size_id . '" ' . $selected . '>' . $size_name . '</option>';
        }
    }

    // Fetch color options
    $colorQuery = selectQuery("SELECT * FROM color");
    $color_options = '<option value="">Select Colors</option>';
    if ($colorQuery['status'] === true) {
        foreach ($colorQuery['data'] as $data) {
            $color_id = $data['id'];
            $color_name = $data['name'];
            $color_code = $data['color_code'];
            $selected = ($colorFilter == $color_id) ? 'selected' : '';
            $color_options .= '<option value="' . $color_id . '" data-color="' . $color_code . '" ' . $selected . '>' . $color_name . '</option>';
        }
    }

    // Get total count of records matching filter
    $countQuery = "SELECT COUNT(*) as total FROM products WHERE is_active != 0 $filterQuery";
    $countResult = selectQuery($countQuery);
    $totalRecords = $countResult['status'] ? $countResult['data'][0]['total'] : 0;

    // Fetch products with filters
    $productQuery = selectQuery("SELECT * FROM products WHERE is_active != 0 $filterQuery $orderBy limit 12");
?>
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
    
    .discount-badge {
        background-color: #ED2590;
        color: white;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: bold;
        position: absolute;
        top: 0;
        right: 0;
    }
    
    /* Badge Styles */
    .image-container {
        position: relative;
    }
    
    .sale-badge {
        position: absolute;
        top: 10px;
        left: 10px;
        background-color: #74B549; /* Changed to green */
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
        border-radius: 4px;
    }
    
    /* Results info */
    .results-info {
        background-color: #f8f9fa;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 20px;
        text-align: center;
    }
    
    /* Active filters display */
    .active-filters {
        background-color: #e9ecef;
        padding: 10px 15px;
        border-radius: 5px;
        margin-bottom: 15px;
    }
    
    /* No results */
    .no-results {
        text-align: center;
        padding: 40px;
        background-color: #f8f9fa;
        border-radius: 10px;
        margin: 20px 0;
    }
    
    .no-results i {
        font-size: 48px;
        color: #6c757d;
        margin-bottom: 15px;
    }
    
    /* Filter form styling */
    .filter-form {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    /* Toggle button for filters */
    .filter-toggle-btn {
        background-color: #ED2590;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 5px;
        margin-bottom: 15px;
        cursor: pointer;
    }
    
    .filter-toggle-btn:hover {
        background-color: #ED2590;
    }
    
    .filter-toggle-btn i {
        margin-right: 5px;
    }
    
    /* Header section with View All button */
    .page-header-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding: 10px 0;
        /* border-bottom: 2px solid #ED2590; */
    }
    
    .page-title {
        font-size: 24px;
        font-weight: bold;
        color: #333;
        margin: 0;
    }
    
    .view-all-btn {
        background-color: #ED2590;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: bold;
        display: flex;
        align-items: center;
        transition: background-color 0.3s;
    }
    
    .view-all-btn:hover {
        background-color: #d41e7f;
        color: white;
        text-decoration: none;
    }
    
    .view-all-btn i {
        margin-left: 8px;
    }
    
    /* Mobile adjustments for discount badge */
    @media (max-width: 768px) {
        .price-container {
            padding-top: 25px; /* Make room for the badge */
        }
        
        .discount-badge {
            font-size: 0.7rem;
            padding: 2px 5px;
            top: 3px;
            right: -5px;
        }
        
        .page-header-section {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .view-all-btn {
            margin-top: 10px;
            width: 100%;
            text-align: center;
            justify-content: center;
        }
    }
</style>
<section class="mt-4" id="product-section">
    <div class="container-fluid">
        <!-- Page Header with Title and View All Button -->
        <div class="page-header-section">
            <h1 class="page-title">Women Collections</h1>
           
        </div>
    </div>
    
    <div class="container-fluid mt-md-3 mt-3">
        <!-- Products Grid -->
        <div class="row">
            <?php
            if ($productQuery['status'] == true && !empty($productQuery['data'])) {
                foreach ($productQuery['data'] as $sr => $data) {
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
                    
                    echo '
                        <a href="' . $url_domain . '/product/' . $slug . '" class="col-md-3 col-6 mb-3">
                            <div class="card border-0 border-radius text-center">
                                <div class="image-container position-relative">
                                    <img src="admin/images/product/'.$firstImage.'" alt="'.$name.'" class="product-image">
                                    
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
                                        <span class="discount-badge">'.$discountPercentage.'% OFF</span>
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
            echo'<div class="col-md-12 text-center">
                    <a href="'.$url_domain.'/products/category/women" class="btn btn-primary rounded">
                        View All Products
                    </a>
                </div>';
            } else {
                // No products found
                echo '
                <div class="col-12">
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h4>No products found</h4>
                        <p>';
                        
                if ($hasActiveFilters) {
                    if (!empty($searchQuery)) {
                        echo 'No products match your search for "<strong>' . htmlspecialchars($searchQuery) . '</strong>"';
                    } else {
                        echo 'No products available with selected filters';
                    }
                } else {
                    echo 'No products available at the moment';
                }
                
                echo '</p>
                        <button type="button" class="btn btn-primary mt-3" id="clearFiltersBtn2">View All Products</button>
                    </div>
                </div>
                ';
                
            }
            ?>
        </div>
    </div>
</section>