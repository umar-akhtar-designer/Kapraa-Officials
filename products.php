<?php
  include 'include/head.php';
  include 'include/navbar.php';
  
  // Initialize variables
  $brandName = "";
  $categoryName = "";
  $subcategoryName = "";
  $brandImage = "";
  $categoryImage = "";
  $subcategoryImage = "";
  $searchQuery = "";
  $filterTitle = "Products"; // Default title
  
  // Initialize ONLY the required filter variables
  $availabilityFilter = '';
  $colorFilter = '';
  $sizeFilter = '';
  $minPriceFilter = '';
  $maxPriceFilter = '';
  
  // Check for search query — sanitized before use in SQL
  if (isset($_GET['search_query'])) {
      $searchQuery = trim($_GET['search_query']);
      // Convert URL-friendly format back to readable format
      $searchQuery = str_replace('-', ' ', $searchQuery);
      $searchQuery = $connection_database->real_escape_string($searchQuery);
  }
  
  // Check if form is submitted via POST
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      // Check if clear filters is requested
      if (isset($_POST['clear_filters'])) {
          unset($_SESSION['product_filters']);
          // Reset all filter variables to default
          $availabilityFilter = '';
          $colorFilter = '';
          $sizeFilter = '';
          $minPriceFilter = '';
          $maxPriceFilter = '';
      } 
      // Check if form is submitted with filters
      elseif (isset($_POST['filter'])) {
          // Store filter values in session to persist them
          $_SESSION['product_filters'] = $_POST;
          
          $availabilityFilter = isset($_POST['availability']) ? $_POST['availability'] : '';
          $colorFilter = isset($_POST['color']) ? intval($_POST['color']) : '';
          $sizeFilter = isset($_POST['size']) ? intval($_POST['size']) : '';
          $minPriceFilter = isset($_POST['min_price']) ? floatval($_POST['min_price']) : '';
          $maxPriceFilter = isset($_POST['max_price']) ? floatval($_POST['max_price']) : '';
      }
  } 
  // Load filters from session if set
  elseif (isset($_SESSION['product_filters'])) {
      $filters = $_SESSION['product_filters'];
      $availabilityFilter = isset($filters['availability']) ? $filters['availability'] : '';
      $colorFilter = isset($filters['color']) ? intval($filters['color']) : '';
      $sizeFilter = isset($filters['size']) ? intval($filters['size']) : '';
      $minPriceFilter = isset($filters['min_price']) ? floatval($filters['min_price']) : '';
      $maxPriceFilter = isset($filters['max_price']) ? floatval($filters['max_price']) : '';
  }

  // Check for brand filter (for banner display)
  if (isset($_GET['brand'])) {
      $brandSlug = $connection_database->real_escape_string(trim($_GET['brand']));
      $brandQuery = selectQuery("SELECT name, brand_banner FROM brand WHERE slug = '$brandSlug'");
      if ($brandQuery['status'] && !empty($brandQuery['data'])) {
          $brandName = $brandQuery['data'][0]['name'];
          $brandImage = $brandQuery['data'][0]['brand_banner'];
      }
  }

  // Check for category filter (for banner display)
  if (isset($_GET['category'])) {
      $categorySlug = $connection_database->real_escape_string(trim($_GET['category']));
      $categoryQuery = selectQuery("SELECT name, cat_banner FROM category WHERE slug = '$categorySlug'");
      if ($categoryQuery['status'] && !empty($categoryQuery['data'])) {
          $categoryName = $categoryQuery['data'][0]['name'];
          $categoryImage = $categoryQuery['data'][0]['cat_banner'];
      }
  }

  // Check for subcategory filter (for banner display)
  if (isset($_GET['subcategory'])) {
      $subcategorySlug = $connection_database->real_escape_string(trim($_GET['subcategory']));
      $subcategoryQuery = selectQuery("SELECT name, subcat_banner FROM subcategory WHERE slug = '$subcategorySlug'");
      if ($subcategoryQuery['status'] && !empty($subcategoryQuery['data'])) {
          $subcategoryName = $subcategoryQuery['data'][0]['name'];
          $subcategoryImage = $subcategoryQuery['data'][0]['subcat_banner'];
      }
  }
  
  // Build filter title
  $titleParts = [];
  if (!empty($searchQuery)) {
      $titleParts[] = "Search: " . htmlspecialchars($searchQuery);
  }
  if (!empty($brandName)) {
      $titleParts[] = $brandName;
  }
  if (!empty($categoryName)) {
      $titleParts[] = $categoryName;
  }
  if (!empty($subcategoryName)) {
      $titleParts[] = $subcategoryName;
  }
  
  if (!empty($titleParts)) {
      $filterTitle = implode(" - ", $titleParts);
  }
  
  // Fetch ONLY the required options for filter form
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
  
  // Function to get product details for quick view
  function getProductDetails($productId) {
      global $url_domain;
      $imagePath = "/admin/images/product/";
      
      $query = selectQuery("SELECT 
          p.*,
          c.name as category_name,
          sc.name as subcategory_name,
          b.name as brand_name
          FROM products p
          LEFT JOIN category c ON FIND_IN_SET(c.id, p.category) > 0
          LEFT JOIN subcategory sc ON FIND_IN_SET(sc.id, p.sub_category) > 0
          LEFT JOIN brand b ON FIND_IN_SET(b.id, p.brand) > 0
          WHERE p.id = '$productId'");
      
      if ($query['status'] && !empty($query['data'])) {
          $product = $query['data'][0];
          
          // Process images
          $images = [];
          if (!empty($product['image'])) {
              $imgs = explode(',', $product['image']);
              foreach ($imgs as $img) {
                  $img = trim($img);
                  if (!empty($img)) {
                      $images[] = $url_domain . $imagePath . $img;
                  }
              }
          }
          
          // If no images, add default
          if (empty($images)) {
              $images[] = $url_domain . "/images/waiting images.jpeg";
          }
          
          return [
              'id' => $product['id'],
              'name' => $product['name'],
              'slug' => $product['slug'],
              'description' => $product['description'],
              'category_name' => $product['category_name'],
              'subcategory_name' => $product['subcategory_name'],
              'brand_name' => $product['brand_name'],
              'price' => $product['price'],
              'dis_amount' => $product['dis_amount'],
              'price_after_discount' => $product['price_after_discount'],
              'sale' => $product['sale'],
              'stock' => $product['stock'],
              'sku' => $product['sku'],
              'size' => $product['size'],
              'images' => $images
          ];
      }
      
      return null;
  }
  
  // Function to get size names by IDs
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
      
      return implode(', ', $sizeNames);
  }
  
  // Function to get category names by IDs
  function getCategoryNamesByIds($categoryIds) {
      if (empty($categoryIds)) return '';
      
      $categoryArray = explode(',', $categoryIds);
      $categoryNames = [];
      
      foreach ($categoryArray as $categoryId) {
          $categoryId = trim($categoryId);
          if (!empty($categoryId)) {
              $query = selectQuery("SELECT name FROM category WHERE id = '$categoryId'");
              if ($query['status'] && !empty($query['data'])) {
                  $categoryNames[] = $query['data'][0]['name'];
              }
          }
      }
      
      return implode(', ', $categoryNames);
  }
  
  // Function to get subcategory names by IDs
  function getSubcategoryNamesByIds($subcategoryIds) {
      if (empty($subcategoryIds)) return '';
      
      $subcategoryArray = explode(',', $subcategoryIds);
      $subcategoryNames = [];
      
      foreach ($subcategoryArray as $subcategoryId) {
          $subcategoryId = trim($subcategoryId);
          if (!empty($subcategoryId)) {
              $query = selectQuery("SELECT name FROM subcategory WHERE id = '$subcategoryId'");
              if ($query['status'] && !empty($query['data'])) {
                  $subcategoryNames[] = $query['data'][0]['name'];
              }
          }
      }
      
      return implode(', ', $subcategoryNames);
  }
  
  // Function to get brand names by IDs
  function getBrandNamesByIds($brandIds) {
      if (empty($brandIds)) return '';
      
      $brandArray = explode(',', $brandIds);
      $brandNames = [];
      
      foreach ($brandArray as $brandId) {
          $brandId = trim($brandId);
          if (!empty($brandId)) {
              $query = selectQuery("SELECT name FROM brand WHERE id = '$brandId'");
              if ($query['status'] && !empty($query['data'])) {
                  $brandNames[] = $query['data'][0]['name'];
              }
          }
      }
      
      return implode(', ', $brandNames);
  }
  
  // Function to clean HTML and limit description
  function cleanDescription($description, $maxLines = 4) {
      // Decode HTML entities
      $description = html_entity_decode($description, ENT_QUOTES, 'UTF-8');
      
      // Remove HTML tags
      $description = strip_tags($description);
      
      // Split into lines
      $lines = explode("\n", $description);
      
      // Take only first $maxLines
      $limitedLines = array_slice($lines, 0, $maxLines);
      
      // Join lines back
      $limitedDescription = implode("\n", $limitedLines);
      
      // Trim and return
      return trim($limitedDescription);
  }
?>

<style>

  /* Sold Out Label Styling */
  .sold-out-label {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 8px 16px;
    font-weight: bold;
    text-transform: uppercase;
    z-index: 10;
    font-size: 16px;
    border-radius: 4px;
  }
  
  /* Ensure product labels are above sold out label */
  .product-labels {
    z-index: 11;
  }
  .price{
      font-size: 1.2rem;
      font-weight: bold;
  }
  
  /* Custom banner styling */
    .collection-banner {
        position: relative;
        width: 100%;
        padding-top: 25%; /* 4:1 Aspect Ratio */
        overflow: hidden;
        margin-bottom: 20px;
    }

    .collection-banner img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        /* object-fit: cover; */
    }

    /* Mobile-specific banner styling */
    @media (max-width: 768px) {
        .collection-banner {
            padding-top: 35%; /* Adjusted aspect ratio for mobile */
            margin-bottom: 5px;
        }
    }
    
    @media (max-width: 576px) {
        .collection-banner {
            padding-top: 35%; /* Further adjusted for very small screens */
            margin-bottom: 5px;
        }
    }
    
    .discount-container {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 20px;
        margin-top: 40px;
    }
    
    .discount-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 120px;
    }
    
    .discount-circle {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background-color: #000;
        color: #fff;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        font-weight: bold;
        margin-bottom: 10px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .discount-circle:hover {
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }
    
    .discount-upto {
        font-size: 14px;
        margin-bottom: 5px;
    }
    
    .discount-percent {
        font-size: 24px;
        line-height: 1;
    }
    
    .discount-off {
        font-size: 14px;
    }
    
    .discount-text {
        font-size: 14px;
        color: #333;
        font-weight: 500;
    }
    
    @media (max-width: 768px) {
        .discount-container {
            gap: 15px;
        }
        
        .discount-item {
            width: 100px;
        }
        
        .discount-circle {
            width: 80px;
            height: 80px;
        }
        
        .discount-upto {
            font-size: 12px;
        }
        
        .discount-percent {
            font-size: 20px;
        }
        
        .discount-off {
            font-size: 12px;
        }
        
        .discount-text {
            font-size: 12px;
        }
    }
    
    @media (max-width: 576px) {
        .discount-container {
            gap: 10px;
        }
        
        .discount-item {
            width: 80px;
        }
        
        .discount-circle {
            width: 70px;
            height: 70px;
        }
        
        .discount-upto {
            font-size: 10px;
        }
        
        .discount-percent {
            font-size: 18px;
        }
        
        .discount-off {
            font-size: 10px;
        }
        
        .discount-text {
            font-size: 10px;
        }

        .filter-toggle-btn {
            width: 100% !important;
        }
    }
     /* Toggle button for filters */
    .filter-toggle-btn {
        border: 1px solid black !important;
        color: black;
        padding: 8px 15px;
        border-radius: 5px;
        margin-bottom: 5px;
        cursor: pointer;
    }
    
    /* Filter form styling */
    .filter-form {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    
    /* Remove extra margin from collection header */
    .collection-header {
        margin-bottom: 0;
    }
    
    /* ── Pagination ── */
    .pagination-container {
        display: flex;
        justify-content: center;
        width: 100%;
        padding: 10px 0 30px;
    }
    .custom-pagination {
        display: flex;
        align-items: center;
        list-style: none;
        padding: 0;
        margin: 0;
        gap: 6px;
        flex-wrap: wrap;
        justify-content: center;
    }
    .custom-pagination .page-item .page-link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        border-radius: 8px;
        border: 1.5px solid #e0e0e0;
        background: #fff;
        color: #444;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    .custom-pagination .page-item .page-link:hover {
        background: #f5f5f5;
        border-color: #bbb;
        color: #111;
    }
    .custom-pagination .page-item.active .page-link {
        background: #111;
        color: #fff;
        border-color: #111;
    }
    .custom-pagination .page-item.active .page-link:hover {
        background: #111;
    }
    .custom-pagination .page-item.disabled .page-link {
        opacity: 0.35;
        pointer-events: none;
        cursor: default;
    }
    /* Arrow buttons slightly wider */
    .custom-pagination .page-item:first-child .page-link,
    .custom-pagination .page-item:last-child .page-link {
        width: 48px;
        color: #333;
    }
    /* Mobile: hide number pages, show only arrow + active */
    @media (max-width: 576px) {
        .custom-pagination .page-item { display: none; }
        .custom-pagination .page-item:first-child,
        .custom-pagination .page-item:last-child,
        .custom-pagination .page-item.active { display: flex; }
        .custom-pagination .page-item .page-link { width: 46px; height: 46px; }
    }

/*Loader*/

.product-image {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
}

@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
</style>

<!-- Page Title or Banner -->
<?php if (!empty($brandImage)): ?>
  <!-- Brand Banner -->
  <div class="collection-banner">
    <img src="<?php echo $url_domain; ?>/admin/images/brand_banner/<?php echo $brandImage; ?>" alt="<?php echo htmlspecialchars($brandName); ?>">
  </div>
<?php elseif (!empty($categoryImage)): ?>
  <!-- Category Banner -->
  <div class="collection-banner">
    <img src="<?php echo $url_domain; ?>/admin/images/cat_banner/<?php echo $categoryImage; ?>" alt="<?php echo htmlspecialchars($categoryName); ?>">
  </div>
<?php elseif (!empty($subcategoryImage)): ?>
  <!-- Subcategory Banner -->
  <div class="collection-banner">
    <img src="<?php echo $url_domain; ?>/admin/images/subcat_banner/<?php echo $subcategoryImage; ?>" alt="<?php echo htmlspecialchars($subcategoryName); ?>">
  </div>
<?php else: ?>
  <!-- Default Page Title -->
  <div class="page section-header text-center mb-0">
      <div class="page-title">
          <div class="wrapper">
              <h1 class="page-width"><?php echo htmlspecialchars($filterTitle); ?></h1>
          </div>
      </div>
  </div>
<?php endif; ?>
<!-- End Page Title or Banner -->

<!-- Collection Banner -->
<div class="collection-header">
    <div class="collection-hero">
        <div class="collection-hero__title-wrapper">
            <div class="page-width">
                <h1 class="collection-hero__title">
                    <?php echo htmlspecialchars($filterTitle); ?>
                </h1>
            </div>
        </div>
    </div>
</div>
<!-- Only show this div when category is Discount -->
<?php if ($categoryName === "Discounts"): ?>
    <div class="container mt-4 text-center">
        <div class="discount-container">
            <?php
                $discountCategoryQuery = selectQuery("SELECT id FROM category WHERE name = 'Discounts'");
                if($discountCategoryQuery['status'] == true && !empty($discountCategoryQuery['data'])){
                    $discountCategoryId = $discountCategoryQuery['data'][0]['id'];
                    $subcategoryQuery = selectQuery("SELECT * FROM subcategory WHERE main_category = '$discountCategoryId'");
                    if($subcategoryQuery['status'] == true && !empty($subcategoryQuery['data'])){
                        foreach($subcategoryQuery['data'] as $subSr => $subData){
                            $subName = $subData['name'];
                            $subSlug = $subData['slug'];
                            $percentage = "30%";
                            if (preg_match('/(\d+)\s*%/i', $subName, $matches)) {
                                $percentage = $matches[1] . "%";
                            }
                            $displayText = trim(preg_replace('/(upto|off|\d+\s*%)/i', '', $subName));
                            $displayText = trim($displayText, ' -');
                            if(empty($displayText)) {
                                $displayText = "Discount";
                            }
                ?>
                <a href="<?php echo $url_domain;?>/products/subcategory/<?php echo $subSlug; ?>" class="discount-item">
                    <div class="discount-circle">
                        <div class="discount-upto">UPTO</div>
                        <div class="discount-percent"><?php echo htmlspecialchars($percentage); ?></div>
                        <div class="discount-off">OFF</div>
                    </div>
                    <div class="discount-text"><?php echo htmlspecialchars($subName); ?></div>
                </a>
                <?php
                        }
                    } 
                } 
            ?>
        </div>
    </div>
<?php endif; ?>
<!-- End Collection Banner -->
<div class="container-fluid mt-3">
    <div class="row">
        <!-- This column will come first on mobile, last on medium+ -->
        <div class="col-md-3 order-md-last order-first">
            <form method="GET" action="">
                <select name="sort" class="form-control" onchange="this.form.submit()">
                    <option value="">Sort</option>
                    <option value="Latest" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'Latest') ? 'selected' : ''; ?>>Latest</option>
                    <option value="Price low to high" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'Price low to high') ? 'selected' : ''; ?>>Price low to high</option>
                    <option value="Price high to low" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'Price high to low') ? 'selected' : ''; ?>>Price high to low</option>
                </select>
                <!-- Preserve existing filters -->
                <?php if (isset($_GET['brand'])): ?>
                    <input type="hidden" name="brand" value="<?php echo htmlspecialchars($_GET['brand']); ?>">
                <?php endif; ?>
                <?php if (isset($_GET['category'])): ?>
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($_GET['category']); ?>">
                <?php endif; ?>
                <?php if (isset($_GET['subcategory'])): ?>
                    <input type="hidden" name="subcategory" value="<?php echo htmlspecialchars($_GET['subcategory']); ?>">
                <?php endif; ?>
                <?php if (isset($_GET['search_query'])): ?>
                    <input type="hidden" name="search_query" value="<?php echo htmlspecialchars($_GET['search_query']); ?>">
                <?php endif; ?>
            </form>
        </div>
        <!-- This column will come last on mobile, first on medium+ -->
        <div class="col-md-9 order-md-first order-last">
            <button type="button" class="filter-toggle-btn mt-md-0 mt-2" id="filterToggleBtn">
                Show Filters
            </button>
        </div>
    </div>
    <div class="col-md-12 mt-3">
        <!-- Filter Form (Hidden by default on mobile) - Using your exact form -->
        <form method="POST" action="" id="filterForm" class="filter-form bg-light p-3 rounded" style="display: none;">
            <div class="row">
                <div class="col-md-3 col-6 mb-3">
                    <label for="availability" class="filter-label">Availability :</label>
                    <select name="availability" class="form-control">
                        <option value="">All</option>
                        <option value="In Stock" <?php echo ($availabilityFilter == 'In Stock') ? 'selected' : ''; ?>>In Stock</option>
                        <option value="Out Of Stock" <?php echo ($availabilityFilter == 'Out Of Stock') ? 'selected' : ''; ?>>Out Of Stock</option>
                    </select>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <label for="color" class="filter-label">Color :</label>
                    <select name="color" id="colorSelect" class="form-control">
                        <?php echo $color_options ?>
                    </select>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <label for="size" class="filter-label">Size :</label>
                    <select name="size" class="form-control">
                        <?php echo $size_options ?>
                    </select>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <label for="min_price" class="filter-label">Low Price:</label>
                    <input type="text" class="form-control" name="min_price" id="min-price-input" placeholder="Low Price" value="<?php echo htmlspecialchars($minPriceFilter); ?>">
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <label for="max_price" class="filter-label">Max Price:</label>
                    <input type="text" class="form-control" name="max_price" id="max-price-input" placeholder="Max Price" value="<?php echo htmlspecialchars($maxPriceFilter); ?>">
                </div>
            </div>
            <div class="col-md-12 text-end mb-3">
                <div class="d-flex gap-2 w-100">
                    <button type="submit" name="filter" class="btn btn-primary rounded flex-fill">
                        <i class="fas fa-check"></i> Apply Filters
                    </button>
                    <button type="button" class="btn btn-secondary rounded flex-fill" id="clearFiltersBtn">
                        <i class="fas fa-times"></i> Clear All
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="container-fluid mt-4">
    <div class="row">
        <!-- Main Content -->
        <div class="col-12 col-sm-12 col-md-12 col-lg-12 main-col">
            <div class="productList">
                <!-- Grid Product -->
                <div class="grid-products">
                    <div class="row">
                        <?php
                            // Pagination settings
                            $dataPerPage = 50;
                            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                            $offset = ($page - 1) * $dataPerPage;
                            
                            // Build base query (keeping your original logic)
                            $sql = "SELECT * FROM products WHERE is_active != 0";
                            
                            // Add search filter if present
                            if (!empty($searchQuery)) {
                                $sql .= " AND (name LIKE '%$searchQuery%' OR description LIKE '%$searchQuery%')";
                            }
                            
                            // Add brand filter if present
                            if (isset($_GET['brand'])) {
                                $brandSlug = $_GET['brand'];
                                $brandQuery = selectQuery("SELECT id FROM brand WHERE slug = '$brandSlug'");
                                if ($brandQuery['status'] && !empty($brandQuery['data'])) {
                                    $brandId = $brandQuery['data'][0]['id'];
                                    // Modified to use FIND_IN_SET for multiple brand IDs
                                    $sql .= " AND FIND_IN_SET($brandId, brand)";
                                }
                            }
                            
                            // Add category filter if present
                            if (isset($_GET['category'])) {
                                $categorySlug = $_GET['category'];
                                $categoryQuery = selectQuery("SELECT id FROM category WHERE slug = '$categorySlug'");
                                if ($categoryQuery['status'] && !empty($categoryQuery['data'])) {
                                    $categoryId = $categoryQuery['data'][0]['id'];
                                    // Modified to use FIND_IN_SET for multiple category IDs
                                    $sql .= " AND FIND_IN_SET($categoryId, category)";
                                }
                            }
                            
                            // Add subcategory filter if present
                            if (isset($_GET['subcategory'])) {
                                $subcategorySlug = $_GET['subcategory'];
                                $subcategoryQuery = selectQuery("SELECT id FROM subcategory WHERE slug = '$subcategorySlug'");
                                if ($subcategoryQuery['status'] && !empty($subcategoryQuery['data'])) {
                                    $subcategoryId = $subcategoryQuery['data'][0]['id'];
                                    // Modified to use FIND_IN_SET for multiple subcategory IDs
                                    $sql .= " AND FIND_IN_SET($subcategoryId, sub_category)";
                                }
                            }
                            
                            // --- ADD THE NEW FILTERS HERE ---
                            
                            // Add availability filter if provided
                            if (!empty($availabilityFilter)) {
                                if ($availabilityFilter == 'In Stock') {
                                    $sql .= " AND stock = 'In Stock'";
                                } elseif ($availabilityFilter == 'Out Of Stock') {
                                    $sql .= " AND stock = 'Out Stock'";
                                }
                            }

                            if (!empty($colorFilter)) {
                                // Modified to use FIND_IN_SET for multiple color IDs
                                $sql .= " AND FIND_IN_SET($colorFilter, color)";
                            }
                            
                            if (!empty($sizeFilter)) {
                                // Use FIND_IN_SET for comma-separated values
                                $sql .= " AND FIND_IN_SET($sizeFilter, size)";
                            }
                            
                            // Add price range condition if provided
                            if (!empty($minPriceFilter) || !empty($maxPriceFilter)) {
                                $min = !empty($minPriceFilter) ? $minPriceFilter : 0;
                                $max = !empty($maxPriceFilter) ? $maxPriceFilter : 999999999;
                                $sql .= " AND price BETWEEN '$min' AND '$max'";
                            }
                            
                            // --- END OF NEW FILTERS ---
                            
                            // Add sorting
                            if (isset($_GET['sort'])) {
                                switch ($_GET['sort']) {
                                    case 'Latest':
                                        $sql .= " ORDER BY id DESC";
                                        break;
                                    case 'Price low to high':
                                        $sql .= " ORDER BY price ASC";
                                        break;
                                    case 'Price high to low':
                                        $sql .= " ORDER BY price DESC";
                                        break;
                                    default:
                                        $sql .= " ORDER BY id DESC";
                                }
                            } else {
                                $sql .= " ORDER BY id DESC";
                            }
                            
                            // Create a separate query to count total products
                            $countSql = str_replace("SELECT * FROM", "SELECT COUNT(*) as total FROM", $sql);
                            $countQuery = selectQuery($countSql);
                            $totalProducts = $countQuery['status'] && !empty($countQuery['data']) ? $countQuery['data'][0]['total'] : 0;
                            $totalPages = ceil($totalProducts / $dataPerPage);
                            
                            // Add pagination to the main query
                            $sql .= " LIMIT $offset, $dataPerPage";
                            
                            // Execute query
                            $productQuery = selectQuery($sql);
                            
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
                                    $image              = $data['image'];

                                    // Get category names (for multiple categories)
                                    $category_names = getCategoryNamesByIds($category);

                                    // Get subcategory names (for multiple subcategories)
                                    $subcategory_names = getSubcategoryNamesByIds($sub_category);

                                    // Get brand names (for multiple brands)
                                    $brand_names = getBrandNamesByIds($brand);

                                    // Get size names
                                    $sizeNames = getSizeNamesByIds($size);

                                    // =======================
                                    // Handle property images
                                    // =======================
                                    $imagePath = "/admin/images/product/";
                                    $firstImage = "";
                                    $secondImage = "";
                                    $allImages = [];

                                    if (!empty($image)) {
                                        // split by comma
                                        $imgs = explode(',', $image);
                                        foreach ($imgs as $img) {
                                            $img = trim($img);
                                            if (!empty($img)) {
                                                $allImages[] = $img;
                                            }
                                        }
                                        
                                        // Get first image
                                        $firstImage = !empty($allImages[0]) ? $allImages[0] : "";
                                        
                                        // Get second image if exists
                                        if (isset($allImages[1]) && !empty($allImages[1])) {
                                            $secondImage = $allImages[1];
                                        }
                                    }

                                    // Build full image URL with domain
                                    $firstImageUrl = !empty($firstImage) ? $url_domain . $imagePath . $firstImage : $url_domain . "/images/waiting images.jpeg";
                                    
                                    // Build second image URL if exists, otherwise use first image
                                    $secondImageUrl = !empty($secondImage) ? $url_domain . $imagePath . $secondImage : $firstImageUrl;
                                    
                                    // Create array of all image URLs for quick view
                                    $allImageUrls = [];
                                    foreach ($allImages as $img) {
                                        $allImageUrls[] = $url_domain . $imagePath . $img;
                                    }
                                    // If no images, add default
                                    if (empty($allImageUrls)) {
                                        $allImageUrls[] = $url_domain . "/images/waiting images.jpeg";
                                    }
                                    
                                    // Clean description (4 lines max)
                                    $cleanDescription = cleanDescription($description, 4);
                        ?>
                        <!-- In product loop section -->
                        <div class="col-6 col-sm-6 col-md-3 col-lg-3 item" data-pid="<?php echo $productID; ?>">
                            <!-- Product Image -->
                            <div class="product-image">
                                <!-- Product Image -->
                                <a href="<?php echo $url_domain; ?>/product/<?php echo $slug; ?>">
                                    <!-- Image -->
                                    <img class="primary blur-up lazyload" src="<?php echo $firstImageUrl; ?>" alt="<?php echo $name?>" title="product" />
                                    <!-- End Image -->
                                    <!-- Hover Image -->
                                    <img class="hover blur-up lazyload" src="<?php echo $secondImageUrl; ?>" alt="<?php echo $name?>" title="product" />
                                    <!-- End Hover Image -->
                                    <!-- Product Label -->
                                    <?php if (!empty($sale)): ?>
                                        <div class="product-labels rectangular"><span class="lbl pr-label1"><?php echo $sale?></span></div>
                                    <?php endif; ?>
                                    <!-- End Product Label -->
                                    
                                    <!-- Sold Out Label -->
                                    <?php if ($stock == 'Out Stock'): ?>
                                        <div class="sold-out-label">SOLD OUT</div>
                                    <?php endif; ?>
                                </a>
                                <!-- End Product Image -->
                                <!-- Product Button -->
                                <div class="button-set d-none d-md-block">
                                    <div class="quickview-btn" data-bs-toggle="tooltip" data-bs-placement="top">
                                        <a href="#open-quickview-popup" class="btn quick-view-popup quick-view rounded" 
                                        data-product-id="<?php echo $productID; ?>"
                                        data-product-name="<?php echo htmlspecialchars($name); ?>"
                                        data-product-price="<?php echo $price; ?>"
                                        data-product-discount-price="<?php echo $price_after_discount; ?>"
                                        data-product-sku="<?php echo $sku; ?>"
                                        data-product-stock="<?php echo $stock; ?>"
                                        data-product-description="<?php echo htmlspecialchars($cleanDescription); ?>"
                                        data-product-sizes="<?php echo htmlspecialchars($sizeNames); ?>"
                                        data-product-images='<?php echo json_encode($allImageUrls); ?>'>
                                           <span >Quick View</span>
                                        </a>
                                    </div>
                                </div>
                                <!-- End Product Button -->
                            </div>
                            <!-- End Product Image -->
                            <!-- Product Details -->
                            <div class="product-details text-center">
                                <!-- Product Name -->
                                <div class="product-name">
                                    <a href="<?php echo $url_domain; ?>/product/<?php echo $slug; ?>"><?php echo $name?></a>
                                </div>
                                <!-- End Product Name -->
                                <!-- Product Price -->
                                <div class="product-price">
                                    <?php if (!empty($price_after_discount) && $price_after_discount < $price): ?>
                                        <span class="old-price">RS. <?php echo number_format($price, 2); ?></span>
                                        <span class="price">RS. <?php echo number_format($price_after_discount, 2); ?></span>
                                    <?php else: ?>
                                        <span class="price">RS. <?php echo number_format($price, 2); ?></span>
                                    <?php endif; ?>
                                </div>
                                <!-- End Product Price -->
                            </div>
                            <!-- End Product Details -->
                        </div>
                        <?php
                                }
                            } else {
                                // No products found
                                echo '<div class="col-12 text-center py-5"><h3>No products found matching your search.</h3></div>';
                            }
                        ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="col-12 mt-4">
                        <div class="pagination-container d-flex justify-content-center">
                            <ul class="pagination custom-pagination">
                                <!-- Previous button -->
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo buildPaginationUrl($page - 1); ?>" aria-label="Previous">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                                    </a>
                                </li>
                                
                                <!-- Page numbers -->
                                <?php
                                    // Show all page numbers (for simplicity as shown in the image)
                                    for ($i = 1; $i <= $totalPages; $i++) {
                                        echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">';
                                        echo '<a class="page-link" href="' . buildPaginationUrl($i) . '">' . $i . '</a>';
                                        echo '</li>';
                                    }
                                ?>
                                
                                <!-- Next button -->
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo buildPaginationUrl($page + 1); ?>" aria-label="Next">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php
                        // Function to build pagination URL with all current filters
                        function buildPaginationUrl($pageNum) {
                            global $url_domain;
                            
                            $url = $url_domain . "/products.php?page=" . $pageNum;
                            
                            // Add search parameter if exists
                            if (!empty($_GET['search'])) {
                                $url .= "&search=" . urlencode($_GET['search']);
                            }
                            
                            // Add brand parameter if exists
                            if (!empty($_GET['brand'])) {
                                $url .= "&brand=" . urlencode($_GET['brand']);
                            }
                            
                            // Add category parameter if exists
                            if (!empty($_GET['category'])) {
                                $url .= "&category=" . urlencode($_GET['category']);
                            }
                            
                            // Add subcategory parameter if exists
                            if (!empty($_GET['subcategory'])) {
                                $url .= "&subcategory=" . urlencode($_GET['subcategory']);
                            }
                            
                            // Add sort parameter if exists
                            if (!empty($_GET['sort'])) {
                                $url .= "&sort=" . urlencode($_GET['sort']);
                            }
                            
                            // Add availability filter if exists
                            if (!empty($_GET['availability'])) {
                                $url .= "&availability=" . urlencode($_GET['availability']);
                            }
                            
                            // Add color filter if exists
                            if (!empty($_GET['color'])) {
                                $url .= "&color=" . urlencode($_GET['color']);
                            }
                            
                            // Add size filter if exists
                            if (!empty($_GET['size'])) {
                                $url .= "&size=" . urlencode($_GET['size']);
                            }
                            
                            // Add price range filters if exist
                            if (!empty($_GET['min_price'])) {
                                $url .= "&min_price=" . urlencode($_GET['min_price']);
                            }
                            
                            if (!empty($_GET['max_price'])) {
                                $url .= "&max_price=" . urlencode($_GET['max_price']);
                            }
                            
                            return $url;
                        }
                    ?>
                </div>
                <!-- End Grid Product -->
            </div>
        </div>
        <!-- End Main Content -->
    </div>
</div>
<!-- Quick View popup -->
<div id="open-quickview-popup" class="quickview-popup magnific-popup mfp-hide">
    <div id="ProductSection-product-template" class="product-template__container prstyle1">
        <div class="product-singles">
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 col-12">
                    <div class="quickview-details mb-3 mb-md-0">
                        <!-- Thumbnails Single (Main Image) -->
                        <div class="quickview-details-img quickview-thumbnails-single">
                            <div class="item">
                                <img class="rounded" id="quickview-main-image" src="<?php echo $url_domain;?>/images/waiting images.jpeg" alt="Product Image" />
                            </div>
                        </div>
                        <!-- End Thumbnails Single -->
                        <!-- Thumbnail Lists (Will be populated dynamically) -->
                        <div class="product-thumb-lists quickview-thumbnail-items" id="quickview-thumbnail-container">
                            <!-- Thumbnail images will be dynamically loaded here -->
                        </div>
                        <!-- End Thumbnail Lists -->
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 col-12">
                    <div class="product-single__meta">
                        <h2 class="product-single__title" id="quickview-product-name">Product Quick View</h2>
                        <div class="prInfoRow">
                            <div class="product-stock"> 
                                <span class="instock" id="quickview-stock-status">In Stock</span> 
                                <span class="outstock hide" id="quickview-outstock">Unavailable</span> 
                            </div>
                            <div class="product-sku">SKU : <span class="variant-sku" id="quickview-sku">N/A</span></div>
                        </div>
                        <p class="product-single__price product-single__price-product-template">
                            <span class="visually-hidden">Regular price</span>
                            <s id="ComparePrice-product-template"><span class="money" id="quickview-old-price">RS. 0.00</span></s>
                            <span class="product-price__price product-price__price-product-template product-price__sale product-price__sale--single">
                                <span id="ProductPrice-product-template"><span class="money" id="quickview-price">RS. 0.00</span></span>
                            </span>
                        </p>
                        
                        <!-- Product Sizes Section -->
                        <div class="product-sizes mb-3" id="quickview-sizes-container">
                            <label class="form-label">Available Sizes:</label>
                            <div class="size-options" id="quickview-size-options">
                                <!-- Sizes will be dynamically loaded here -->
                            </div>
                        </div>
                        <!-- End Product Sizes Section -->
                        
                        <div class="product-single__description rte" id="quickview-description">
                            Product description will be loaded here...
                        </div>
                        <form method="post" action="#" id="product_form_1" accept-charset="UTF-8" class="product-form product-form-product-template hidedropdown" enctype="multipart/form-data">
                            <!-- Hidden input for product ID -->
                            <input type="hidden" id="form-product-id" name="product_id" value="">
                            <!-- Product Action -->
                            <div class="product-action clearfix d-none">
                                <div class="product-form__item--quantity">
                                    <div class="wrapQtyBtn">
                                        <div class="qtyField">
                                            <input type="hidden" id="Quantity" name="quantity" value="1" class="product-form__input qty" />
                                        </div>
                                    </div>
                                </div>
                                <div class="product-form__item--submit">
                                    <!-- <button type="button" name="add" class="btn product-form__cart-submit">
                                        <span>Add to cart</span>
                                    </button> -->
                                </div>
                            </div>
                            <!-- End Product Action -->
                        </form>
                    </div>
                </div>
            </div>
            <!-- End-product-single -->
        </div>
    </div>
</div>
<!-- End Quick View Popup -->
<?php
  include 'include/quick_view.php';
?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Restore scroll position if available
        const scrollPosition = sessionStorage.getItem('productScrollPosition');
        if (scrollPosition) {
            window.scrollTo(0, parseInt(scrollPosition));
            sessionStorage.removeItem('productScrollPosition');
        }
        
        // Filter toggle functionality
        const filterToggleBtn = document.getElementById('filterToggleBtn');
        const filterForm = document.getElementById('filterForm');
        
        if (filterToggleBtn && filterForm) {
            filterToggleBtn.addEventListener('click', function() {
                if (filterForm.style.display === 'none') {
                    filterForm.style.display = 'block';
                    this.innerHTML = '<i class="fas fa-times"></i> Hide Filters';
                } else {
                    filterForm.style.display = 'none';
                    this.innerHTML = '<i class="fas fa-filter"></i> Show Filters';
                }
            });
        }
        
        // Price input validation
        const minPriceInput = document.getElementById('min-price-input');
        const maxPriceInput = document.getElementById('max-price-input');
        
        function validatePriceInputs() {
            const minVal = parseFloat(minPriceInput.value) || 0;
            const maxVal = parseFloat(maxPriceInput.value) || 999999999;
            
            // Ensure min <= max
            if (minVal > maxVal) {
                minPriceInput.value = maxVal;
            }
        }
        
        if (minPriceInput) {
            minPriceInput.addEventListener('input', validatePriceInputs);
        }
        
        if (maxPriceInput) {
            maxPriceInput.addEventListener('input', validatePriceInputs);
        }
        
        // Color dropdown styling
        const colorSelect = document.getElementById('colorSelect');
        if (colorSelect) {
            // Apply color preview to options
            const options = colorSelect.querySelectorAll('option');
            options.forEach(option => {
                const colorCode = option.getAttribute('data-color');
                if (colorCode) {
                    option.style.backgroundColor = colorCode;
                    option.style.color = getContrastColor(colorCode);
                    option.style.padding = '5px';
                }
            });
            
            function getContrastColor(hexcolor) {
                // Remove # if present
                hexcolor = hexcolor.replace('#', '');
                
                if (hexcolor.length === 3) {
                    hexcolor = hexcolor.split('').map(char => char + char).join('');
                }
                
                // Convert to RGB
                const r = parseInt(hexcolor.substr(0, 2), 16);
                const g = parseInt(hexcolor.substr(2, 2), 16);
                const b = parseInt(hexcolor.substr(4, 2), 16);
                
                // Calculate luminance
                const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
                
                return luminance > 0.5 ? '#000000' : '#ffffff';
            }
        }
        
        // Clear filters functionality
        const clearFiltersBtn = document.getElementById('clearFiltersBtn');
        
        function clearFilters() {
            // Save scroll position before submitting
            sessionStorage.setItem('productScrollPosition', window.pageYOffset);
            
            // Reset form values
            const form = document.getElementById('filterForm');
            if (form) {
                // Reset all form fields
                form.reset();
                
                // Explicitly clear price inputs
                if (minPriceInput) minPriceInput.value = '';
                if (maxPriceInput) maxPriceInput.value = '';
                
                // Add a hidden input to indicate clearing filters
                const clearInput = document.createElement('input');
                clearInput.type = 'hidden';
                clearInput.name = 'clear_filters';
                clearInput.value = '1';
                form.appendChild(clearInput);
                
                // Submit form
                form.submit();
            }
        }
        
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', clearFilters);
        }
        
        // Save scroll position when form is submitted
        const filterFormElement = document.getElementById('filterForm');
        if (filterFormElement) {
            filterFormElement.addEventListener('submit', function() {
                sessionStorage.setItem('productScrollPosition', window.pageYOffset);
            });
        }
    });
</script>
<?php
  include 'include/footer.php';
?>