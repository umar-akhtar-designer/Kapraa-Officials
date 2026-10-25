<?php
    require 'include/head.php';
    session('WEBSITE-LOGIN');    
    $productId = cleanStr($_GET['id']);
    
    // Function to generate unique slug (excluding current product)
    function generateUniqueSlug($baseSlug, $currentProductId = null) {
        $slug = $baseSlug;
        $counter = 1;
        
        // Build the query to check for existing slugs
        $query = "SELECT id FROM products WHERE slug = '$slug'";
        if ($currentProductId) {
            $query .= " AND id != '$currentProductId'";
        }
        
        $checkSlug = selectQuery($query);
        
        // If slug exists, append a number until we find a unique one
        while ($checkSlug['status'] === true && !empty($checkSlug['data'])) {
            $slug = $baseSlug . '-' . $counter;
            $query = "SELECT id FROM products WHERE slug = '$slug'";
            if ($currentProductId) {
                $query .= " AND id != '$currentProductId'";
            }
            $checkSlug = selectQuery($query);
            $counter++;
        }
        
        return $slug;
    }
    
    // Initialize error array
    $error = array();
    
    // Handle form submission for update
    if(isset($_POST['update'])){
        $name           = cleanStr($_POST['name']);
        $slug           = cleanStr($_POST['slug']);
        $serial_no      = cleanStr($_POST['serial_no']);
        $sku            = cleanStr($_POST['sku']);
        $description    = cleanStr($_POST['description']);
        $product_type   = cleanStr($_POST['product_type']);
        $category       = isset($_POST['category']) ? $_POST['category'] : array(); // Array of categories
        $sub_category   = isset($_POST['sub_category']) ? $_POST['sub_category'] : array(); // Array of subcategories
        $brand          = isset($_POST['brand']) ? $_POST['brand'] : array(); // Array of brands
        $size           = isset($_POST['size']) ? $_POST['size'] : array();
        $color          = isset($_POST['color']) ? $_POST['color'] : array();
        $volume_ids_arr = isset($_POST['volume_ids']) ? $_POST['volume_ids'] : array();
        $unit           = cleanStr($_POST['unit']);
        $order_qty      = cleanStr($_POST['order_qty']);
        $price          = cleanStr($_POST['price']);
        $dis_amount     = cleanStr($_POST['dis_amount']);
        $price_after_discount = cleanStr($_POST['price_after_discount']);
        $shipp_cost     = cleanStr($_POST['shipp_cost']);
        $link           = cleanStr($_POST['link']);
        $seo_title      = cleanStr($_POST['seo_title']);
        $seo_des        = cleanStr($_POST['seo_des']);
        $stock          = cleanStr($_POST['stock']);
        $sale           = cleanStr($_POST['sale']);
        $styled_by      = cleanStr($_POST['styled_by']);
        $stitched_price = cleanStr($_POST['stitched_price']);
        $setsuite_price = cleanStr($_POST['setsuite_price']);
        $setsuite_quantity = cleanStr($_POST['setsuite_quantity']);
        $setsuite_discount = cleanStr($_POST['setsuite_discount']);
        $ai_score       = max(0, min(100, (int)($_POST['ai_score'] ?? 50)));
        $occasion_tag   = cleanStr($_POST['occasion_tag'] ?? '');
        $cultural_tag   = cleanStr($_POST['cultural_tag'] ?? '');
        
        // Validation
        if(empty($name))
            $error[] = "Please type product name";
        
        if(empty($description))
            $error[] = "Please type product description";
        
        if(empty($sku))
            $error[] = "Please enter product SKU";
        
        if(empty($serial_no))
            $error[] = "Please enter serial number";
      
        if(empty($price) || $price < 0)
            $error[] = "Please enter valid price";
        
        if(empty($error)){
            // Generate unique slug (excluding current product)
            $slug = generateUniqueSlug($slug, $productId);
            
            $uploadPath = 'images/product/';
            $allowedExts = array('png', 'jpg', 'jpeg','webp');
            
            // Get existing images
            $existingImages = !empty($_POST['existing_images']) ? explode(',', $_POST['existing_images']) : array();
            $newImageNames = array();
            
            // Handle new image uploads
            if (!empty($_FILES['image']['name'][0])) {
                $fileCount = count($_FILES['image']['name']);
                
                for ($i = 0; $i < $fileCount; $i++) {
                    // Check if file was uploaded without errors
                    if ($_FILES['image']['error'][$i] !== UPLOAD_ERR_OK) {
                        $error[] = "Upload error for file " . ($i + 1) . ": Error code " . $_FILES['image']['error'][$i];
                        continue;
                    }
                    
                    $fileName = $_FILES['image']['name'][$i];
                    $fileTmpName = $_FILES['image']['tmp_name'][$i];
                    $fileSize = $_FILES['image']['size'][$i];
                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    
                    // Validate file type
                    if (!in_array($fileExt, $allowedExts)) {
                        $error[] = "File type not allowed for $fileName. Only PNG, JPG, and JPEG are allowed.";
                        continue;
                    }
                    
                    // Validate file size (max 5MB)
                    if ($fileSize > 5 * 1024 * 1024) {
                        $error[] = "File $fileName is too large. Maximum size is 5MB.";
                        continue;
                    }
                    
                    // Generate unique filename and move directly (no GD processing)
                    $filename = uniqid() . '.' . $fileExt;
                    $destination_img = $uploadPath . $filename;

                    if (move_uploaded_file($fileTmpName, $destination_img)) {
                        $newImageNames[] = $filename;
                    } else {
                        $error[] = "Failed to upload image $fileName. Please check directory permissions.";
                    }
                }
            }
            
            // Combine existing and new images
            $allImages = array_merge($existingImages, $newImageNames);
            $imageStr = !empty($allImages) ? implode(',', $allImages) : '';
            
            // Convert arrays to comma-separated strings
            $categoryStr = is_array($category) && !empty($category) ? implode(',', $category) : '';
            $subcategoryStr = is_array($sub_category) && !empty($sub_category) ? implode(',', $sub_category) : '';
            $brandStr  = is_array($brand)  && !empty($brand)  ? implode(',', $brand)  : '';
            $sizeStr   = is_array($size)   && !empty($size)   ? implode(',', $size)   : '';
            $colorStr  = is_array($color)  && !empty($color)  ? implode(',', $color)  : '';
            $volumeStr = is_array($volume_ids_arr) && !empty($volume_ids_arr) ? implode(',', array_filter(array_map('intval', $volume_ids_arr))) : '';
            
            // Update product data
            $update = insert_and_update_data('update', 'products', array(
                'name' => $name,
                'slug' => $slug,
                'serial_no' => $serial_no,
                'sku' => $sku,
                'description' => $description,
                'product_type' => $product_type,
                'category' => $categoryStr,        // Comma-separated category IDs
                'sub_category' => $subcategoryStr, // Comma-separated subcategory IDs
                'brand' => $brandStr,              // Comma-separated brand IDs
                'size' => $sizeStr,                // Comma-separated size IDs
                'color' => $colorStr,              // Comma-separated color IDs
                'unit' => $unit,
                'order_qty' => $order_qty,
                'price' => $price,
                'dis_amount' => $dis_amount,
                'price_after_discount' => $price_after_discount,
                'shipp_cost' => $shipp_cost,
                'image' => $imageStr,
                'link' => $link,
                'seo_title' => $seo_title,
                'seo_des' => $seo_des,
                'stock' => $stock,
                'sale' => $sale,
                'styled_by' => $styled_by,
                'stitched_price' => $stitched_price,
                'setsuite_price' => $setsuite_price,
                'setsuite_quantity' => $setsuite_quantity,
                'setsuite_discount' => $setsuite_discount,
                'ai_score'       => $ai_score,
                'occasion_tag'   => $occasion_tag,
                'cultural_tag'   => $cultural_tag,
                'volume_ids'     => $volumeStr,
            ), "id='$productId'");
            
            if($update == true){
                $success = 'Product updated successfully';
                $error[] = $success;
                
                // Refresh product data after update
                $productQuery = selectQuery("SELECT * FROM products WHERE id ='$productId'");
                $productData  = $productQuery['data'][0];
                $image = $productData['image'];
                $existingImages = !empty($image) ? explode(',', $image) : array();
            } else {
                $error[] = 'Failed to update product. Please try again.';
                
                // Clean up newly uploaded images if update failed
                if (!empty($newImageNames)) {
                    foreach ($newImageNames as $imageName) {
                        if (file_exists($uploadPath . $imageName)) {
                            unlink($uploadPath . $imageName);
                        }
                    }
                }
            }
        }
    }
    
    // Fetch product data
    $productQuery = selectQuery("SELECT * FROM products WHERE id ='$productId'");
    $productData  = $productQuery['data'][0];
    
    $name           = $productData['name'];
    $slug           = $productData['slug'];
    $serial_no      = $productData['serial_no'];
    $sku            = $productData['sku'];
    $description    = $productData['description'];
    $product_type   = $productData['product_type'];
    $category       = $productData['category'];
    $sub_category   = $productData['sub_category'];
    $brand          = $productData['brand'];
    $size           = $productData['size'];
    $color          = $productData['color'];
    $unit           = $productData['unit'];
    $order_qty      = $productData['order_qty'];
    $price          = $productData['price'];
    $dis_amount     = $productData['dis_amount'];
    $price_after_discount   = $productData['price_after_discount'];
    $shipp_cost     = $productData['shipp_cost'];
    $image          = $productData['image'];
    $link           = $productData['link'];
    $seo_title      = $productData['seo_title'];
    $seo_des        = $productData['seo_des'];
    $created_at     = $productData['created_at'];
    $stock          = $productData['stock'];
    $sale           = $productData['sale'];
    $styled_by      = $productData['styled_by'];
    $stitched_price = $productData['stitched_price'];
    $setsuite_price = $productData['setsuite_price'];
    $setsuite_quantity = $productData['setsuite_quantity'];
    $setsuite_discount = $productData['setsuite_discount'];
    $ai_score       = isset($productData['ai_score']) ? (int)$productData['ai_score'] : 50;
    $occasion_tag   = $productData['occasion_tag'] ?? '';
    $cultural_tag   = $productData['cultural_tag'] ?? '';
    $volume_ids_str = $productData['volume_ids'] ?? '';
    $volumeArray    = !empty($volume_ids_str) ? explode(',', $volume_ids_str) : [];
    
    // Get existing images array
    $existingImages = !empty($image) ? explode(',', $image) : array();
    
    // Convert comma-separated values to arrays for pre-selecting
    $categoryArray = !empty($category) ? explode(',', $category) : array();
    $subcategoryArray = !empty($sub_category) ? explode(',', $sub_category) : array();
    $brandArray = !empty($brand) ? explode(',', $brand) : array();
    
    // Fetch categories
    $categories = '';
    $catQuery = selectQuery("SELECT * FROM category");
    if ($catQuery['status'] === true) {
        foreach ($catQuery['data'] as $data) {
            $main_categorys = $data['id'];
            $category_name = $data['name'];
            $selected = (in_array($main_categorys, $categoryArray)) ? 'selected' : '';
            $categories .= '<option value="' . $main_categorys . '" ' . $selected . '>' . $category_name . '</option>';
        }
    }
    
    // Fetch subcategories
    $subcategories = '';
    $subcatQuery = selectQuery("SELECT * FROM subcategory");
    if ($subcatQuery['status'] === true) {
        foreach ($subcatQuery['data'] as $data) {
            $main_subcategorys = $data['id'];
            $subcategory_name = $data['name'];
            $selected = (in_array($main_subcategorys, $subcategoryArray)) ? 'selected' : '';
            $subcategories .= '<option value="' . $main_subcategorys . '" ' . $selected . '>' . $subcategory_name . '</option>';
        }
    }
    
    // Fetch brands
    $brand_options = '';
    $brandQuery = selectQuery("SELECT * FROM brand");
    if ($brandQuery['status'] === true) {
        foreach ($brandQuery['data'] as $data) {
            $brand_id = $data['id'];
            $brand_name = $data['name'];
            $selected = (in_array($brand_id, $brandArray)) ? 'selected' : '';
            $brand_options .= '<option value="' . $brand_id . '" ' . $selected . '>' . $brand_name . '</option>';
        }
    }
    
    // Fetch sizes
    $size_options = '';
    $sizeQuery = selectQuery("SELECT * FROM size");
    if ($sizeQuery['status'] === true) {
        foreach ($sizeQuery['data'] as $data) {
            $size_id = $data['id'];
            $size_name = $data['name'];
            $selected = (in_array($size_id, explode(',', $size))) ? 'selected' : '';
            $size_options .= '<option value="' . $size_id . '" ' . $selected . '>' . $size_name . '</option>';
        }
    }
    
    // Fetch colors
    $color_options = '';
    $colorQuery = selectQuery("SELECT * FROM color");
    if ($colorQuery['status'] === true) {
        foreach ($colorQuery['data'] as $data) {
            $color_id = $data['id'];
            $color_name = $data['name'];
            $color_code = $data['color_code'];
            $selected = (in_array($color_id, explode(',', $color))) ? 'selected' : '';
            $color_options .= '<option value="' . $color_id . '" data-color="' . $color_code . '" ' . $selected . '>' . $color_name . '</option>';
        }
    }
    
    // Image compression + resize function (max 1200px on longest side)
    function compressImage($source, $destination, $quality) {
        $info = getimagesize($source);
        if (!$info) return false;

        if ($info['mime'] == 'image/jpeg') {
            $image = imagecreatefromjpeg($source);
        } elseif ($info['mime'] == 'image/png') {
            $image = imagecreatefrompng($source);
        } elseif ($info['mime'] == 'image/webp') {
            $image = imagecreatefromwebp($source);
        } else {
            return false;
        }
        if (!$image) return false;

        $origW = imagesx($image);
        $origH = imagesy($image);
        $maxDim = 1200;

        if ($origW > $maxDim || $origH > $maxDim) {
            $ratio  = min($maxDim / $origW, $maxDim / $origH);
            $newW   = (int)round($origW * $ratio);
            $newH   = (int)round($origH * $ratio);
            $resized = imagecreatetruecolor($newW, $newH);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            imagedestroy($image);
            $image = $resized;
        }

        $result = imagejpeg($image, $destination, $quality);
        imagedestroy($image);
        return $result;
    }
?>

<style>
    .card {
        border: none;
        border-radius: 0.35rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 0.25rem 2rem 0 rgba(58, 59, 69, 0.2);
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .form-control, .form-select {
        border-radius: 0.35rem;
        border: 1px solid #d1d3e2;
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #bac8f3;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    }
    
    .form-label {
        font-weight: 700;
        color: #5a5c69;
        margin-bottom: 0.5rem;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .btn-primary {
        background-color: var(--primary);
        border-color: var(--primary);
        border-radius: 0.35rem;
        padding: 0.75rem 1.5rem;
        font-weight: 700;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        background-color: #2e59d9;
        border-color: #2653d4;
        transform: translateY(-2px);
        box-shadow: 0 0.15rem 1rem rgba(78, 115, 223, 0.4);
    }
    
    .btn-outline-secondary {
        border-radius: 0.35rem;
        border: 1px solid #d1d3e2;
        color: #5a5c69;
        transition: all 0.3s ease;
    }
    
    .btn-outline-secondary:hover {
        background-color: #f8f9fc;
        border-color: #bac8f3;
        color: var(--primary);
    }
    
    .input-group {
        border-radius: 0.35rem;
        overflow: hidden;
    }
    
    .image-container {
        position: relative;
        border-radius: 0.35rem;
        overflow: hidden;
        box-shadow: 0 0.15rem 0.5rem rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        cursor: move;
    }
    
    .image-container:hover {
        transform: scale(1.03);
        box-shadow: 0 0.25rem 1rem rgba(0, 0, 0, 0.15);
    }
    
    .image-container.dragging {
        opacity: 0.5;
        transform: scale(0.95);
    }
    
    .remove-image {
        position: absolute;
        top: 8px;
        right: 8px;
        background: rgba(231, 74, 59, 0.9);
        color: white;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-weight: bold;
        font-size: 16px;
        transition: all 0.3s ease;
        z-index: 10;
    }
    
    .remove-image:hover {
        background: var(--danger);
        transform: scale(1.1);
    }
    
    .color-box {
        border-radius: 0.35rem;
        box-shadow: 0 0.15rem 0.5rem rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .color-box:hover {
        transform: scale(1.1);
        box-shadow: 0 0.25rem 1rem rgba(0, 0, 0, 0.15);
    }
    
    .form-text {
        font-size: 0.75rem;
        color: #858796;
        font-style: italic;
    }
    
    .card-header {
        background-color: #f8f9fc;
        border-bottom: 1px solid #e3e6f0;
        padding: 1rem 1.5rem;
        border-radius: calc(0.35rem - 1px) calc(0.35rem - 1px) 0 0 !important;
    }
    
    .card-header h4 {
        color: var(--primary);
        font-weight: 700;
        margin: 0;
        font-size: 1.1rem;
    }
    
    .section-title {
        position: relative;
        padding-bottom: 15px;
        margin-bottom: 20px;
    }
    
    .section-title:after {
        content: "";
        position: absolute;
        left: 0;
        bottom: 0;
        width: 50px;
        height: 3px;
        background: var(--primary);
        border-radius: 3px;
    }
    
    .form-row {
        margin-bottom: 20px;
    }
    
    .price-calculation {
        background-color: #f8f9fc;
        border-radius: 0.35rem;
        padding: 15px;
        margin-top: 10px;
        border-left: 4px solid var(--primary);
    }
    
    .calculation-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        font-size: 0.9rem;
    }
    
    .final-price {
        font-weight: bold;
        color: var(--primary);
        font-size: 1.1rem;
        border-top: 1px solid #dee2e6;
        padding-top: 8px;
        margin-top: 8px;
    }
    
    .image-preview-container {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 15px;
    }
    
    .image-order-badge {
        position: absolute;
        top: 8px;
        left: 8px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
        z-index: 5;
    }
    
    .existing-image {
        border: 2px solid var(--primary);
    }
    
    @media (max-width: 768px) {
        .card-body {
            padding: 1rem;
        }
        
        .form-control, .form-select {
            padding: 0.5rem 0.75rem;
        }
        
        .btn-primary {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        .image-container {
            width: 120px !important;
            height: 120px !important;
        }
    }
</style>

<div class="content-body">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Edit Product</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        // Display errors/success messages
                        if(!empty($error)){
                            foreach($error as $err){
                                echo '<div class="alert alert-info">'.$err.'</div>';
                            }
                        }
                        ?>
                        
                        <form action="" method="post" enctype="multipart/form-data" id="productForm">
                            <!-- Hidden field to store existing images -->
                            <input type="hidden" name="existing_images" id="existingImagesInput" value="<?php echo $image; ?>">
                            
                            <div class="section-title">Basic Information</div>
                            <div class="row form-row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Product Name</label>
                                    <input type="text" class="form-control" name="name" id="name" placeholder="Enter product name" value="<?php echo $name ?>" required>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Slug</label>
                                    <input type="text" name="slug" id="slug" class="form-control" value="<?php echo $slug ?>" readonly>
                                    <small class="form-text">If the slug already exists, it will be automatically adjusted to be unique.</small>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Serial Number</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="serial_no" id="serialInput" value="<?php echo $serial_no ?>" required readonly>
                                        <button class="btn btn-outline-secondary" type="button" id="generateSerial">
                                            <i class="fas fa-sync-alt me-1"></i> Generate
                                        </button>
                                    </div>
                                    <small class="form-text">5-digit auto-generated serial number</small>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Product SKU</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="sku" id="skuInput" placeholder="Enter SKU" value="<?php echo $sku ?>" required>
                                        <button class="btn btn-outline-secondary" type="button" id="generateSku">
                                            <i class="fas fa-sync-alt me-1"></i> Generate
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12 mb-4">
                                    <label class="form-label">Product Description</label>
                                    <textarea name="description" id="editor" placeholder="Enter detailed product description" class="form-control p-3" rows="5" required><?php echo $description ?></textarea>
                                </div>
                            </div>
                            
                            <div class="section-title">Product Classification</div>
                            <div class="row form-row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Product Type</label>
                                    <select name="product_type" class="form-control">
                                        <option value="">Select Type</option>
                                        <option value="Physical" <?php echo ($product_type == 'Physical') ? 'selected' : ''; ?>>Physical Product</option>
                                        <option value="Digital" <?php echo ($product_type == 'Digital') ? 'selected' : ''; ?>>Digital Product</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Category</label>
                                    <select name="category[]" class="default-select form-control wide" multiple>
                                        <option value="">Select Categories</option>
                                        <?php echo $categories?>
                                    </select>
                                    <small class="form-text">Hold Ctrl/Cmd to select multiple</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Sub Category</label>
                                    <select name="sub_category[]" class="default-select form-control wide" multiple>
                                        <option value="">Select Subcategories</option>
                                        <?php echo $subcategories?>
                                    </select>
                                    <small class="form-text">Hold Ctrl/Cmd to select multiple</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Brand</label>
                                    <select name="brand[]" class="default-select form-control wide" multiple id="brandSelect">
                                        <option value="">Select Brands</option>
                                        <?php echo $brand_options?>
                                    </select>
                                    <small class="form-text">Hold Ctrl/Cmd to select multiple</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">
                                        Brand Volume / Collection
                                        <span class="text-muted small ms-1">(optional)</span>
                                    </label>
                                    <div id="volumeCheckboxes" style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:8px;padding:10px;min-height:50px;">
                                        <span class="text-muted small" id="volHint">Select a brand first to see volumes</span>
                                    </div>
                                    <small class="form-text">Assign to one or more volumes. Leave blank = shows in all products.</small>
                                </div>
                                 <div class="col-md-3 mb-3">
                                    <label class="form-label">Product Stock</label>
                                    <select name="stock" class="form-control">
                                        <option value="">Select Type</option>
                                        <option value="In Stock" <?php echo ($stock == 'In Stock') ? 'selected' : ''; ?>>In Stock</option>
                                        <option value="Out Stock" <?php echo ($stock == 'Out Stock') ? 'selected' : ''; ?>>Out Stock</option>
                                    </select>
                                </div>
                                 <div class="col-md-3 mb-3">
                                    <label class="form-label">Product Sale</label>
                                    <select name="sale" class="form-control">
                                        <option value="">Select Type</option>
                                        <option value="In Sale" <?php echo ($sale == 'In Sale') ? 'selected' : ''; ?>>In Sale</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">STYLED BY You</label>
                                    <select name="styled_by" class="form-control">
                                        <option value="">Select Type</option>
                                        <option value="Kapraa" <?php echo ($styled_by == 'Kapraa') ? 'selected' : ''; ?>>Kapraa</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Stitched Price</label>
                                    <input type="number" class="form-control" name="stitched_price" value="<?php echo $stitched_price?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Set Suite Price</label>
                                    <input type="number" class="form-control" name="setsuite_price" value="<?php echo $setsuite_price?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Set Suite Quantity</label>
                                    <input type="number" class="form-control" name="setsuite_quantity" value="<?php echo $setsuite_quantity?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Set Suite Discount</label>
                                    <input type="number" class="form-control" name="setsuite_discount" value="<?php echo $setsuite_discount?>">
                                </div>
                            </div>
                            <div class="section-title"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M9 11V7a3 3 0 0 1 6 0v4"/><circle cx="9" cy="16" r="1" fill="currentColor"/><circle cx="15" cy="16" r="1" fill="currentColor"/><path d="M12 3v2"/></svg> AI Settings</div>
                            <div class="row form-row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">AI Boost Score <span class="badge bg-info text-dark"><?php echo $ai_score; ?>/100</span></label>
                                    <input type="range" class="form-range" name="ai_score" min="0" max="100" value="<?php echo $ai_score; ?>" oninput="this.previousElementSibling.querySelector('.badge').textContent=this.value+'/100'">
                                    <small class="form-text text-muted">Higher score = appears first in AI recommendations. Default: 50. Set 80-100 to promote, 0-20 to suppress.</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Occasion Tag</label>
                                    <select class="form-control" name="occasion_tag">
                                        <option value="">-- None --</option>
                                        <?php
                                        $occasions = ['eid','wedding','office','casual','summer','winter','kids','bridal','festive','party','mehndi','sports'];
                                        foreach ($occasions as $occ) {
                                            $sel = ($occasion_tag === $occ) ? 'selected' : '';
                                            echo "<option value=\"$occ\" $sel>" . ucfirst($occ) . "</option>";
                                        }
                                        ?>
                                    </select>
                                    <small class="form-text text-muted">Shows in AI Occasion Recommender.</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">🇵🇰 Cultural Tag</label>
                                    <select class="form-control" name="cultural_tag">
                                        <option value="">-- None --</option>
                                        <?php
                                        $cultures = [
                                            'punjabi'  => '🌾 Punjabi (Punjab)',
                                            'sindhi'   => '🏺 Sindhi (Sindh)',
                                            'balochi'  => '🪡 Balochi (Balochistan)',
                                            'pashtun'  => '🏔️ Pashtun (KPK)',
                                            'kashmiri' => '🌸 Kashmiri (AJK)',
                                        ];
                                        foreach ($cultures as $val => $label) {
                                            $sel = ($cultural_tag === $val) ? 'selected' : '';
                                            echo "<option value=\"$val\" $sel>$label</option>";
                                        }
                                        ?>
                                    </select>
                                    <small class="form-text text-muted">Shows in AI Cultural Dress Recommender.</small>
                                </div>
                            </div>
                            <div class="section-title">Product Attributes</div>
                            <div class="row form-row">
                                <div class="col-md-3 mb-4">
                                    <label class="form-label">Size</label>
                                    <select name="size[]" id="sizeSelect" multiple class="default-select form-control wide">
                                        <option value="">Select Sizes</option>
                                        <?php echo $size_options?>
                                    </select>
                                    <small class="form-text">Hold Ctrl/Cmd to select multiple</small>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <label class="form-label">Color</label>
                                    <select name="color[]" id="colorSelect" multiple class="default-select form-control wide">
                                        <option value="">Select Colors</option>
                                        <?php echo $color_options?>
                                    </select>
                                    <small class="form-text">Hold Ctrl/Cmd to select multiple</small>
                                    <div id="selectedColors" class="mt-3 d-flex flex-wrap gap-2"></div>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <label class="form-label">Unit</label>
                                    <select class="form-control" name="unit">
                                        <option value="">Select Unit</option>
                                        <option value="kg" <?php echo ($unit == 'kg') ? 'selected' : ''; ?>>Kilogram (kg)</option>
                                        <option value="pc" <?php echo ($unit == 'pc') ? 'selected' : ''; ?>>Piece (pc)</option>
                                        <option value="gms" <?php echo ($unit == 'gms') ? 'selected' : ''; ?>>Grams (gms)</option>
                                        <option value="ltrs" <?php echo ($unit == 'ltrs') ? 'selected' : ''; ?>>Liters (ltrs)</option>
                                        <option value="pair" <?php echo ($unit == 'pair') ? 'selected' : ''; ?>>Pair</option>
                                        <option value="oz" <?php echo ($unit == 'oz') ? 'selected' : ''; ?>>Ounce (oz)</option>
                                        <option value="lb" <?php echo ($unit == 'lb') ? 'selected' : ''; ?>>Pound (lb)</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <label class="form-label">Stock Remaining Qty</label>
                                    <input type="number" class="form-control" name="order_qty" placeholder="Rem. quantity" value="<?php echo $order_qty ?>">
                                </div>
                            </div>
                            
                            <div class="section-title">Pricing & Shipping</div>
                            <div class="row form-row">
                                <div class="col-md-3 mb-4">
                                    <label class="form-label">Unit Price (RS)</label>
                                    <input type="number" class="form-control price-input" name="price" id="priceInput" placeholder="0.00" step="0.01" value="<?php echo $price ?>" required>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <label class="form-label">Discount Amount (RS)</label>
                                    <input type="number" class="form-control price-input" name="dis_amount" id="discountInput" placeholder="0.00" step="0.01" value="<?php echo $dis_amount ?>">
                                </div>
                                <div class="col-md-3 mb-4">
                                    <label class="form-label">Price After Discount (RS)</label>
                                    <input type="number" class="form-control" name="price_after_discount" id="priceAfterDiscount" placeholder="0.00" step="0.01" value="<?php echo $price_after_discount ?>" readonly>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <label class="form-label">Shipping Cost (RS)</label>
                                    <input type="number" class="form-control" name="shipp_cost" placeholder="0.00" step="0.01" value="<?php echo $shipp_cost ?>">
                                </div>
                            </div>
                            
                            <!-- Price Calculation Display -->
                            <div class="row form-row">
                                <div class="col-12">
                                    <div class="price-calculation">
                                        <h6 class="mb-3">Price Calculation</h6>
                                        <div class="calculation-row">
                                            <span>Original Price:</span>
                                            <span id="displayOriginalPrice">RS <?php echo number_format($price, 2) ?></span>
                                        </div>
                                        <div class="calculation-row">
                                            <span>Discount Amount:</span>
                                            <span id="displayDiscount">RS <?php echo number_format($dis_amount, 2) ?></span>
                                        </div>
                                        <div class="calculation-row final-price">
                                            <span>Final Price:</span>
                                            <span id="displayFinalPrice">RS <?php echo number_format($price_after_discount, 2) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="section-title">Media & SEO</div>
                            <div class="row form-row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Product Images</label>
                                    <div class="border rounded p-3 bg-light">
                                        <input type="file" class="form-control mb-3" id="imageUpload" name="image[]" multiple accept="image/*">
                                        <small class="form-text">Maximum 5MB per image. Allowed formats: PNG, JPG, JPEG, WebP</small>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <small class="form-text">Drag images to reorder</small>
                                            <button type="button" class="btn btn-sm btn-outline-danger" id="clearAllImages">
                                                <i class="fas fa-trash me-1"></i> Clear All
                                            </button>
                                        </div>
                                        <div id="imagePreview" class="image-preview-container mt-3">
                                            <!-- Existing images will be loaded here -->
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Youtube Video Link</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fab fa-youtube"></i></span>
                                        <input type="text" class="form-control" name="link" placeholder="https://youtube.com/..." value="<?php echo $link ?>">
                                    </div>
                                    
                                    <label class="form-label mt-3">SEO Title</label>
                                    <input type="text" class="form-control" name="seo_title" placeholder="SEO optimized title" value="<?php echo $seo_title ?>">
                                    
                                    <label class="form-label mt-3">SEO Description</label>
                                    <textarea name="seo_des" class="form-control" rows="3" placeholder="SEO meta description"><?php echo $seo_des ?></textarea>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                                <button type="reset" class="btn btn-outline-secondary me-2 px-4" id="resetForm">
                                    <i class="fas fa-redo me-2"></i> Reset
                                </button>
                                <button type="submit" class="btn btn-primary px-4" name="update">
                                    <i class="fas fa-save me-2"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php define('GLOBAL_JS_LOADED', true); ?>
<script src="vendor/global/global.min.js"></script>
<script src="vendor/ckeditor/ckeditor.js"></script>
<script src="js/Sortable.min.js"></script>
<script>
    try { CKEDITOR.replace('editor'); } catch(e) {}

    $(document).ready(function() {
        // Store existing and new images
        var existingImages = <?php echo !empty($existingImages) ? json_encode($existingImages) : '[]'; ?>;
        var newImageFiles = [];
        
        // Initialize image preview with existing images
        loadExistingImages();
        
        // Generate SKU
        $('#generateSku').click(function() {
            const randomPart = Math.random().toString(36).substring(2, 10).toUpperCase();
            const sku = 'PRD-' + randomPart;
            $('#skuInput').val(sku);
        });

        // Generate Serial Number
        $('#generateSerial').click(function() {
            // Generate a new 5-digit serial number (incremental)
            let currentSerial = $('#serialInput').val();
            let newSerial = '00001';
            
            if (currentSerial && !isNaN(currentSerial)) {
                newSerial = String(parseInt(currentSerial) + 1).padStart(5, '0');
            }
            
            $('#serialInput').val(newSerial);
        });

        // Handle color select - show selected colors
        function updateSelectedColors() {
            $('#selectedColors').empty();
            $('#colorSelect option:selected').each(function() {
                var colorName = $(this).text();
                var colorCode = $(this).data('color');

                var colorBox = $('<div class="color-box d-inline-block me-2 mb-2 text-center">')
                    .css({
                        'width': '40px',
                        'height': '40px',
                        'background-color': colorCode,
                        'border': '1px solid #ccc',
                        'border-radius': '4px'
                    })
                    .attr('title', colorName);

                $('#selectedColors').append(colorBox);
            });
        }
        
        // Initialize selected colors on page load
        updateSelectedColors();
        
        $('#colorSelect').change(function() {
            updateSelectedColors();
        });

        // Price Calculation Function
        function calculatePriceAfterDiscount() {
            let price = parseFloat($('#priceInput').val()) || 0;
            let discount = parseFloat($('#discountInput').val()) || 0;
            
            // Ensure discount doesn't exceed price
            if (discount > price) {
                discount = price;
                $('#discountInput').val(discount.toFixed(2));
            }
            
            let finalPrice = price - discount;
            
            // Update the price after discount field
            $('#priceAfterDiscount').val(finalPrice.toFixed(2));
            
            // Update display
            $('#displayOriginalPrice').text('RS ' + price.toFixed(2));
            $('#displayDiscount').text('RS ' + discount.toFixed(2));
            $('#displayFinalPrice').text('RS ' + finalPrice.toFixed(2));
        }

        // Bind price calculation to input events
        $('.price-input').on('input', function() {
            calculatePriceAfterDiscount();
        });

        // Calculate initial price on page load
        calculatePriceAfterDiscount();

        // Reset form handler
        $('#resetForm').click(function() {
            // Reload existing images after reset
            setTimeout(function() {
                loadExistingImages();
                calculatePriceAfterDiscount();
                updateSelectedColors();
            }, 100);
        });

        // IMAGE HANDLING
        var imagePreview = document.getElementById('imagePreview');
        
        // Initialize Sortable for image reordering
        var sortable = new Sortable(imagePreview, {
            animation: 150,
            ghostClass: 'dragging',
            onEnd: function(evt) {
                updateImageArraysAfterReorder();
                updateFileInput();
                updateHiddenInput();
                updateImageOrderNumbers();
            }
        });
        
        // Update image arrays after reordering
        function updateImageArraysAfterReorder() {
            var updatedExistingImages = [];
            var updatedNewImageFiles = [];
            
            // Get all image containers in current order
            $('.image-container').each(function(index) {
                if ($(this).hasClass('existing-image')) {
                    var imageName = $(this).find('img').attr('src').split('/').pop();
                    updatedExistingImages.push(imageName);
                } else {
                    var fileIndex = $(this).data('file-index');
                    if (fileIndex !== undefined) {
                        updatedNewImageFiles.push(newImageFiles[fileIndex]);
                    }
                }
            });
            
            existingImages = updatedExistingImages;
            newImageFiles = updatedNewImageFiles;
        }
        
        // Load existing images from database
        function loadExistingImages() {
            $('#imagePreview').empty();
            existingImages = <?php echo !empty($existingImages) ? json_encode($existingImages) : '[]'; ?>;
            newImageFiles = [];
            
            // Display existing images
            for (let i = 0; i < existingImages.length; i++) {
                let imageName = existingImages[i];
                let imageUrl = 'images/product/' + imageName;
                
                var container = $('<div class="image-container existing-image">')
                    .css({
                        width: '150px',
                        height: '150px',
                        position: 'relative'
                    })
                    .attr('data-index', i)
                    .attr('data-type', 'existing');

                var orderBadge = $('<div class="image-order-badge">' + (i + 1) + '</div>');

                var img = $('<img>')
                    .attr('src', imageUrl)
                    .attr('alt', 'Product Image')
                    .css({
                        width: '100%',
                        height: '100%',
                        objectFit: 'cover'
                    });

                var removeBtn = $('<div class="remove-image">&times;</div>')
                    .click(function() {
                        var container = $(this).parent();
                        var indexToRemove = container.data('index');
                        var imageType = container.data('type');
                        
                        if (imageType === 'existing') {
                            existingImages.splice(indexToRemove, 1);
                        } else {
                            var fileIndex = container.data('file-index');
                            newImageFiles.splice(fileIndex, 1);
                        }
                        
                        rebuildImagePreview();
                    });

                container.append(orderBadge).append(img).append(removeBtn);
                $('#imagePreview').append(container);
            }
            
            updateHiddenInput();
            updateFileInput();
        }
        
        // Update image order numbers
        function updateImageOrderNumbers() {
            $('.image-order-badge').each(function(index) {
                $(this).text(index + 1);
            });
        }
        
        // Clear all images
        $('#clearAllImages').click(function() {
            if(confirm('Are you sure you want to remove all images?')) {
                existingImages = [];
                newImageFiles = [];
                $('#imagePreview').empty();
                updateHiddenInput();
                updateFileInput();
            }
        });

        // Handle new image uploads
        $('#imageUpload').change(function() {
            var files = this.files;
            
            // Add new files to newImageFiles array
            for (var i = 0; i < files.length; i++) {
                // Check if file already exists to avoid duplicates
                var isDuplicate = false;
                for (var j = 0; j < newImageFiles.length; j++) {
                    if (files[i].name === newImageFiles[j].name && files[i].size === newImageFiles[j].size) {
                        isDuplicate = true;
                        break;
                    }
                }
                
                if (!isDuplicate) {
                    newImageFiles.push(files[i]);
                }
            }

            // Rebuild preview with all files
            rebuildImagePreview();
        });

        // Rebuild the entire image preview
        function rebuildImagePreview() {
            $('#imagePreview').empty();
            
            // Display existing images
            for (let i = 0; i < existingImages.length; i++) {
                let imageName = existingImages[i];
                let imageUrl = 'images/product/' + imageName;
                
                var container = $('<div class="image-container existing-image">')
                    .css({
                        width: '150px',
                        height: '150px',
                        position: 'relative'
                    })
                    .attr('data-index', i)
                    .attr('data-type', 'existing');

                var orderBadge = $('<div class="image-order-badge">' + (i + 1) + '</div>');

                var img = $('<img>')
                    .attr('src', imageUrl)
                    .attr('alt', 'Product Image')
                    .css({
                        width: '100%',
                        height: '100%',
                        objectFit: 'cover'
                    });

                var removeBtn = $('<div class="remove-image">&times;</div>')
                    .click(function() {
                        var container = $(this).parent();
                        var indexToRemove = container.data('index');
                        existingImages.splice(indexToRemove, 1);
                        rebuildImagePreview();
                    });

                container.append(orderBadge).append(img).append(removeBtn);
                $('#imagePreview').append(container);
            }
            
            // Display new images
            for (let i = 0; i < newImageFiles.length; i++) {
                let file = newImageFiles[i];
                let reader = new FileReader();

                reader.onload = function(e) {
                    var container = $('<div class="image-container">')
                        .css({
                            width: '150px',
                            height: '150px',
                            position: 'relative'
                        })
                        .attr('data-file-index', i)
                        .attr('data-type', 'new');

                    var orderBadge = $('<div class="image-order-badge">' + (i + existingImages.length + 1) + '</div>');

                    var img = $('<img>')
                        .attr('src', e.target.result)
                        .css({
                            width: '100%',
                            height: '100%',
                            objectFit: 'cover'
                        });

                    var removeBtn = $('<div class="remove-image">&times;</div>')
                        .click(function() {
                            var container = $(this).parent();
                            var fileIndex = container.data('file-index');
                            newImageFiles.splice(fileIndex, 1);
                            rebuildImagePreview();
                        });

                    container.append(orderBadge).append(img).append(removeBtn);
                    $('#imagePreview').append(container);
                };

                reader.readAsDataURL(file);
            }
            
            updateFileInput();
            updateHiddenInput();
        }

        // Update the actual file input
        function updateFileInput() {
            var dt = new DataTransfer();
            for (var i = 0; i < newImageFiles.length; i++) {
                dt.items.add(newImageFiles[i]);
            }
            document.getElementById('imageUpload').files = dt.files;
        }
        
        // Update hidden input with existing images
        function updateHiddenInput() {
            $('#existingImagesInput').val(existingImages.join(','));
        }

        // FORM SUBMIT FIX
        $('#productForm').on('submit', function() {
            // Update CKEditor content
            for (instance in CKEDITOR.instances) {
                CKEDITOR.instances[instance].updateElement();
            }
            
            // Ensure file input is updated with current files
            updateFileInput();
            updateHiddenInput();
            
            // Ensure price calculation is up to date
            calculatePriceAfterDiscount();
        });
    });
    
    function generateSlug(value) {
        // Replace spaces with hyphens and convert to lowercase
        return value.trim().toLowerCase().replace(/ /g, '-').replace(/[^\w-]+/g, '');
    }

    document.getElementById('name').addEventListener('input', function () {
        const slugField = document.getElementById('slug');
        slugField.value = generateSlug(this.value);
    });

    // ── Volume selector (loads from brand selection) ─────────
    const CURRENT_VOLUME_IDS = <?= json_encode($volumeArray) ?>;

    function loadVolumesForBrand(brandId) {
        if (!brandId) {
            document.getElementById('volumeCheckboxes').innerHTML = '<span class="text-muted small" id="volHint">Select a brand first to see volumes</span>';
            return;
        }
        fetch(`include/brand_volume_ajax.php?action=for_brand&brand_id=${brandId}`)
            .then(r => r.json()).then(data => {
                const box = document.getElementById('volumeCheckboxes');
                if (!data.volumes || data.volumes.length === 0) {
                    box.innerHTML = '<span class="text-muted small">No volumes set for this brand yet. <a href="edit_brand.php?id=' + brandId + '" target="_blank">Add volumes</a></span>';
                    return;
                }
                let html = '';
                data.volumes.forEach(v => {
                    const checked = CURRENT_VOLUME_IDS.includes(String(v.id)) ? 'checked' : '';
                    html += `<label style="display:flex;align-items:center;gap:8px;margin-bottom:6px;cursor:pointer;">
                        <input type="checkbox" name="volume_ids[]" value="${v.id}" ${checked} style="width:16px;height:16px;">
                        <span style="font-size:13px;"><strong>Vol ${v.volume_number}</strong>${v.volume_name ? ' — ' + v.volume_name : ''}</span>
                    </label>`;
                });
                box.innerHTML = html;
            });
    }

    // When brand selection changes, reload volumes
    const brandSel = document.getElementById('brandSelect');
    if (brandSel) {
        brandSel.addEventListener('change', function() {
            const selected = Array.from(this.selectedOptions).map(o => o.value).filter(v => v);
            // Use first selected brand for volumes (most common use case)
            loadVolumesForBrand(selected[0] || '');
        });
        // On page load, load volumes for the already-selected brand
        const alreadySel = Array.from(brandSel.selectedOptions).map(o => o.value).filter(v => v);
        if (alreadySel.length > 0) loadVolumesForBrand(alreadySel[0]);
    }
</script>

<?php include 'include/footer.php'; ?>
<script src="vendor/bootstrap-select/dist/js/bootstrap-select.min.js"></script>