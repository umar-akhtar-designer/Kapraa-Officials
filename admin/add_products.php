<?php
    require 'include/head.php';
    session('WEBSITE-LOGIN');    
    
    // Fetch category options
    $categoryQuery = selectQuery("SELECT * FROM category");
    $categories = '';
    if ($categoryQuery['status'] === true) {
        foreach ($categoryQuery['data'] as $data) {
            $category_id = $data['id'];
            $category_name = $data['name'];
            $categories .= '<option value="' . $category_id . '">' . $category_name . '</option>';
        }
    }
    
    // Fetch subcategory options
    $subcategoryQuery = selectQuery("SELECT * FROM subcategory");
    $subcategories = '';
    if ($subcategoryQuery['status'] === true) {
        foreach ($subcategoryQuery['data'] as $data) {
            $subcategory_id = $data['id'];
            $subcategory_name = $data['name'];
            $subcategories .= '<option value="' . $subcategory_id . '">' . $subcategory_name . '</option>';
        }
    }
    
    // Fetch brand options
    $brandQuery = selectQuery("SELECT * FROM brand");
    $brand_options = '';
    if ($brandQuery['status'] === true) {
        foreach ($brandQuery['data'] as $data) {
            $brand_id = $data['id'];
            $brand_name = $data['name'];
            $brand_options .= '<option value="' . $brand_id . '">' . $brand_name . '</option>';
        }
    }
    
    // Fetch size options
    $sizeQuery = selectQuery("SELECT * FROM size");
    $size_options = '';
    if ($sizeQuery['status'] === true) {
        foreach ($sizeQuery['data'] as $data) {
            $size_id = $data['id'];
            $size_name = $data['name'];
            $size_options .= '<option value="' . $size_id . '">' . $size_name . '</option>';
        }
    }
    
    // Fetch color options
    $colorQuery = selectQuery("SELECT * FROM color");
    $color_options = '';
    if ($colorQuery['status'] === true) {
        foreach ($colorQuery['data'] as $data) {
            $color_id = $data['id'];
            $color_name = $data['name'];
            $color_code = $data['color_code'];
            $color_options .= '<option value="' . $color_id . '" data-color="' . $color_code . '">' . $color_name . '</option>';
        }
    }
    
    // Generate 5-digit serial number
    function generateSerialNumber() {
        // Get the last product serial number from database
        $lastProduct = selectQuery("SELECT serial_no FROM products ORDER BY id DESC LIMIT 1");
        $lastSerial = '00000';
        
        if ($lastProduct['status'] === true && !empty($lastProduct['data'])) {
            $lastSerial = $lastProduct['data'][0]['serial_no'];
        }
        
        // Increment the serial number
        $newSerial = str_pad((int)$lastSerial + 1, 5, '0', STR_PAD_LEFT);
        
        return $newSerial;
    }
    
    // Function to generate unique slug
    function generateUniqueSlug($baseSlug) {
        $slug = $baseSlug;
        $counter = 1;
        
        // Check if slug exists
        $checkSlug = selectQuery("SELECT id FROM products WHERE slug = '$slug'");
        
        // If slug exists, append a number until we find a unique one
        while ($checkSlug['status'] === true && !empty($checkSlug['data'])) {
            $slug = $baseSlug . '-' . $counter;
            $checkSlug = selectQuery("SELECT id FROM products WHERE slug = '$slug'");
            $counter++;
        }
        
        return $slug;
    }
    
    // Initialize error array
    $error = array();
    
    if(isset($_POST['save'])){
        
        $name           = cleanStr($_POST['name']);
        $slug           = cleanStr($_POST['slug']);
        $serial_no      = cleanStr($_POST['serial_no']);
        $sku            = cleanStr($_POST['sku']);
        $description    = cleanStr($_POST['description']);
        $product_type   = cleanStr($_POST['product_type']);
        $category       = isset($_POST['category']) ? $_POST['category'] : array(); // Array of categories
        $sub_category   = isset($_POST['sub_category']) ? $_POST['sub_category'] : array(); // Array of subcategories
        $brand          = isset($_POST['brand']) ? $_POST['brand'] : array(); // Array of brands
        $size           = isset($_POST['size']) ? $_POST['size'] : array(); // Array of sizes
        $color          = isset($_POST['color']) ? $_POST['color'] : array(); // Array of colors
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
        $occasion_tag   = cleanStr($_POST['occasion_tag'] ?? '');
        $cultural_tag   = cleanStr($_POST['cultural_tag'] ?? '');
        $stitched_price = cleanStr($_POST['stitched_price']);
        $setsuite_price = cleanStr($_POST['setsuite_price']);
        $setsuite_quantity = cleanStr($_POST['setsuite_quantity']);
        $setsuite_discount = cleanStr($_POST['setsuite_discount']);
       
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
            // Generate unique slug
            $slug = generateUniqueSlug($slug);
            
            $uploadPath = 'images/product/';
            $allowedExts = array('png', 'jpg', 'jpeg','webp');
            $imageNames = array(); // Store all image names
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            
            // Handle multiple image uploads
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
                        $imageNames[] = $filename;
                    } else {
                        $error[] = "Failed to upload image $fileName. Please check directory permissions.";
                    }
                }
            }
            
            // If there are upload errors but we have some valid images, continue with valid ones
            if (!empty($error) && !empty($imageNames)) {
                // We have both errors and successful uploads - you can decide how to handle this
                // For now, we'll continue with the successful uploads but show the errors
                $uploadErrors = $error;
                $error = array(); // Reset errors to allow insertion
            }
            
            // Proceed with database insertion if no critical errors
            if (empty($error)) {
                // Convert arrays to comma-separated strings
                $categoryStr    = is_array($category)       && !empty($category)       ? implode(',', $category)       : '';
                $subcategoryStr = is_array($sub_category)  && !empty($sub_category)  ? implode(',', $sub_category)  : '';
                $brandStr       = is_array($brand)          && !empty($brand)          ? implode(',', $brand)          : '';
                $sizeStr        = is_array($size)           && !empty($size)           ? implode(',', $size)           : '';
                $colorStr       = is_array($color)          && !empty($color)          ? implode(',', $color)          : '';
                $volumeStr      = is_array($volume_ids_arr) && !empty($volume_ids_arr) ? implode(',', array_filter(array_map('intval', $volume_ids_arr))) : '';
                $imageStr       = !empty($imageNames) ? implode(',', $imageNames) : '';
                
                $insert = insert_and_update_data('insert', 'products', array(
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
                    'image' => $imageStr,              // Comma-separated image filenames
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
                    'volume_ids' => $volumeStr,
                    'occasion_tag'  => $occasion_tag,
                    'cultural_tag'  => $cultural_tag,
                ));
                
                if($insert == true){
                    $success = 'Product added successfully';
                    if (!empty($uploadErrors)) {
                        $success .= ' with some upload warnings: ' . implode(', ', $uploadErrors);
                    }
                    $error[] = $success;
                    
                    // Clear form or redirect
                    // header("Location: products.php");
                    // exit();
                } else {
                    $error[] = 'Failed to add product. Please try again.';
                    
                    // Clean up uploaded images if database insertion failed
                    if (!empty($imageNames)) {
                        foreach ($imageNames as $imageName) {
                            if (file_exists($uploadPath . $imageName)) {
                                unlink($uploadPath . $imageName);
                            }
                        }
                    }
                }
            }
        }
    }
    
    // Generate serial number for the form
    $generatedSerial = generateSerialNumber();
    
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
                        <h4>Add New Product</h4>
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
                            <div class="section-title">Basic Information</div>
                            <div class="row form-row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Product Name</label>
                                    <input type="text" class="form-control" name="name" id="name" placeholder="Enter product name" required>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Slug</label>
                                    <input type="text" name="slug" id="slug" class="form-control" readonly>
                                    <small class="form-text">If the slug already exists, it will be automatically adjusted to be unique.</small>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Serial Number</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="serial_no" id="serialInput" value="<?php echo  $generatedSerial; ?>" required readonly>
                                        <button class="btn btn-outline-secondary" type="button" id="generateSerial">
                                            <i class="fas fa-sync-alt me-1"></i> Generate
                                        </button>
                                    </div>
                                    <small class="form-text">5-digit auto-generated serial number</small>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Product SKU</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="sku" id="skuInput" placeholder="Enter SKU" required>
                                        <button class="btn btn-outline-secondary" type="button" id="generateSku">
                                            <i class="fas fa-sync-alt me-1"></i> Generate
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12 mb-4">
                                    <label class="form-label">Product Description</label>
                                    <textarea name="description" id="editor" placeholder="Enter detailed product description" class="form-control p-3" rows="5" required></textarea>
                                </div>
                            </div>
                            
                            <div class="section-title">Product Classification</div>
                            <div class="row form-row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Product Type</label>
                                    <select name="product_type" class="form-control">
                                        <option value="">Select Type</option>
                                        <option value="Physical">Physical Product</option>
                                        <option value="Digital">Digital Product</option>
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

                                <!-- Volume selector — loads after brand is picked -->
                                <div class="col-md-3 mb-3" id="volumeFieldWrap">
                                    <label class="form-label">
                                        Brand Volume / Collection
                                        <span class="badge bg-secondary ms-1" style="font-size:10px;">optional</span>
                                    </label>
                                    <div id="volumeCheckboxes"
                                         style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:8px;padding:10px;min-height:52px;max-height:180px;overflow-y:auto;">
                                        <span class="text-muted small">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                            Select a brand first
                                        </span>
                                    </div>
                                    <small class="form-text">Tick the volume(s) this product belongs to. Leave blank = visible in all products of that brand.</small>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Product Stock</label>
                                    <select name="stock" class="form-control">
                                        <option value="">Select Type</option>
                                        <option value="In Stock">In Stock</option>
                                        <option value="Out Stock">Out Stock</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Product Sale</label>
                                    <select name="sale" class="form-control">
                                        <option value="">Select Type</option>
                                        <option value="In Sale">In Sale</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">STYLED BY You</label>
                                    <select name="styled_by" class="form-control">
                                        <option value="">Select Type</option>
                                        <option value="Kapraa">Kapraa</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Occasion Tag <small class="text-muted">(for AI Recommender)</small></label>
                                    <select name="occasion_tag" class="form-control">
                                        <option value="">— None —</option>
                                        <option value="wedding">💍 Wedding / Shadi</option>
                                        <option value="eid">🌙 Eid / Festive</option>
                                        <option value="casual">☕ Casual / Daily</option>
                                        <option value="office">💼 Office / Formal</option>
                                        <option value="party">🎊 Party</option>
                                        <option value="winter">❄️ Winter</option>
                                        <option value="summer">☀️ Summer / Lawn</option>
                                        <option value="sports">🏃 Sports / Active</option>
                                        <option value="bridal">👰 Bridal</option>
                                        <option value="mehndi">🪷 Mehndi</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Cultural Tag <small class="text-muted">(for AI Recommender)</small></label>
                                    <select name="cultural_tag" class="form-control">
                                        <option value="">— None —</option>
                                        <option value="punjabi">🌾 Punjabi</option>
                                        <option value="sindhi">🏺 Sindhi</option>
                                        <option value="balochi">🪡 Balochi</option>
                                        <option value="pashtun">🏔️ Pashtun</option>
                                        <option value="kashmiri">🌸 Kashmiri</option>
                                        <option value="pakistani">🇵🇰 General Pakistani</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Stitched Price</label>
                                    <input type="number" class="form-control" name="stitched_price">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Set Suite Price</label>
                                    <input type="number" class="form-control" name="setsuite_price">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Set Suite Quantity</label>
                                    <input type="number" class="form-control" name="setsuite_quantity">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Set Suite Discount</label>
                                    <input type="number" class="form-control" name="setsuite_discount">
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
                                    <select class="form-control" name="unit" required>
                                        <option value="">Select Unit</option>
                                        <option value="kg">Kilogram (kg)</option>
                                        <option value="pc">Piece (pc)</option>
                                        <option value="gms">Grams (gms)</option>
                                        <option value="ltrs">Liters (ltrs)</option>
                                        <option value="pair">Pair</option>
                                        <option value="oz">Ounce (oz)</option>
                                        <option value="lb">Pound (lb)</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <label class="form-label">Stock Remaining Qty</label>
                                    <input type="number" class="form-control" name="order_qty" placeholder="Rem. quantity">
                                </div>
                            </div>
                            
                            <div class="section-title">Pricing & Shipping</div>
                            <div class="row form-row">
                                <div class="col-md-3 mb-4">
                                    <label class="form-label">Unit Price (RS)</label>
                                    <input type="number" class="form-control price-input" name="price" id="priceInput" placeholder="0.00" step="0.01" required>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <label class="form-label">Discount Amount (RS)</label>
                                    <input type="number" class="form-control price-input" name="dis_amount" id="discountInput" placeholder="0.00" step="0.01">
                                </div>
                                <div class="col-md-3 mb-4">
                                    <label class="form-label">Price After Discount (RS)</label>
                                    <input type="number" class="form-control" name="price_after_discount" id="priceAfterDiscount" placeholder="0.00" step="0.01" readonly>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <label class="form-label">Shipping Cost (RS)</label>
                                    <input type="number" class="form-control" name="shipp_cost" placeholder="0.00" step="0.01">
                                </div>
                            </div>
                            
                            <!-- Price Calculation Display -->
                            <div class="row form-row">
                                <div class="col-12">
                                    <div class="price-calculation">
                                        <h6 class="mb-3">Price Calculation</h6>
                                        <div class="calculation-row">
                                            <span>Original Price:</span>
                                            <span id="displayOriginalPrice">RS 0.00</span>
                                        </div>
                                        <div class="calculation-row">
                                            <span>Discount Amount:</span>
                                            <span id="displayDiscount">RS 0.00</span>
                                        </div>
                                        <div class="calculation-row final-price">
                                            <span>Final Price:</span>
                                            <span id="displayFinalPrice">RS 0.00</span>
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
                                        <div id="imagePreview" class="image-preview-container mt-3"></div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Youtube Video Link</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fab fa-youtube"></i></span>
                                        <input type="text" class="form-control" name="link" placeholder="https://youtube.com/...">
                                    </div>
                                    
                                    <label class="form-label mt-3">SEO Title</label>
                                    <input type="text" class="form-control" name="seo_title" placeholder="SEO optimized title">
                                    
                                    <label class="form-label mt-3">SEO Description</label>
                                    <textarea name="seo_des" class="form-control" rows="3" placeholder="SEO meta description"></textarea>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                                <button type="reset" class="btn btn-outline-secondary me-2 px-4" id="resetForm">
                                    <i class="fas fa-redo me-2"></i> Reset
                                </button>
                                <button type="submit" class="btn btn-primary px-4" name="save">
                                    <i class="fas fa-save me-2"></i> Save Product
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

        // Handle color select
        $('#colorSelect').change(function() {
            $('#selectedColors').empty();

            $(this).find('option:selected').each(function() {
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
            // Reset serial number to generated value
            $('#serialInput').val('<?php echo $generatedSerial; ?>');
            // Reset price calculation
            setTimeout(function() {
                calculatePriceAfterDiscount();
            }, 100);
        });

        // IMAGE HANDLING
        var imageFiles = [];
        var imagePreview = document.getElementById('imagePreview');
        
        // Initialize Sortable for image reordering
        var sortable = new Sortable(imagePreview, {
            animation: 150,
            ghostClass: 'dragging',
            onEnd: function(evt) {
                // Reorder the imageFiles array based on the new order
                var item = imageFiles[evt.oldIndex];
                imageFiles.splice(evt.oldIndex, 1);
                imageFiles.splice(evt.newIndex, 0, item);
                
                updateFileInput();
                updateImageOrderNumbers();
            }
        });
        
        // Update image order numbers
        function updateImageOrderNumbers() {
            $('.image-order-badge').each(function(index) {
                $(this).text(index + 1);
            });
        }
        
        // Clear all images
        $('#clearAllImages').click(function() {
            imageFiles = [];
            $('#imagePreview').empty();
            updateFileInput();
        });

        $('#imageUpload').change(function() {
            var files = this.files;
            
            // Add new files to imageFiles array
            for (var i = 0; i < files.length; i++) {
                // Check if file already exists to avoid duplicates
                var isDuplicate = false;
                for (var j = 0; j < imageFiles.length; j++) {
                    if (files[i].name === imageFiles[j].name && files[i].size === imageFiles[j].size) {
                        isDuplicate = true;
                        break;
                    }
                }
                
                if (!isDuplicate) {
                    imageFiles.push(files[i]);
                }
            }

            // Rebuild preview with all files
            rebuildImagePreview();
        });

        // Rebuild the entire image preview
        function rebuildImagePreview() {
            $('#imagePreview').empty();
            
            for (let i = 0; i < imageFiles.length; i++) {
                let file = imageFiles[i];
                let reader = new FileReader();

                reader.onload = function(e) {
                    var container = $('<div class="image-container">')
                        .css({
                            width: '150px',
                            height: '150px',
                            position: 'relative'
                        })
                        .attr('data-index', i);

                    var orderBadge = $('<div class="image-order-badge">' + (i + 1) + '</div>');

                    var img = $('<img>')
                        .attr('src', e.target.result)
                        .css({
                            width: '100%',
                            height: '100%',
                            objectFit: 'cover'
                        });

                    var removeBtn = $('<div class="remove-image">&times;</div>')
                        .click(function() {
                            var indexToRemove = $(this).parent().data('index');
                            imageFiles.splice(indexToRemove, 1);
                            rebuildImagePreview();
                        });

                    container.append(orderBadge).append(img).append(removeBtn);
                    $('#imagePreview').append(container);
                };

                reader.readAsDataURL(file);
            }
            
            updateFileInput();
        }

        // Update the actual file input
        function updateFileInput() {
            var dt = new DataTransfer();
            for (var i = 0; i < imageFiles.length; i++) {
                dt.items.add(imageFiles[i]);
            }
            document.getElementById('imageUpload').files = dt.files;
        }

        // FORM SUBMIT FIX
        $('#productForm').on('submit', function() {
            // Update CKEditor content
            for (instance in CKEDITOR.instances) {
                CKEDITOR.instances[instance].updateElement();
            }
            
            // Ensure file input is updated with current files
            updateFileInput();
            
            // Ensure price calculation is up to date
            calculatePriceAfterDiscount();
        });
    });
    
    // ── Brand → Volume dynamic loader ────────────────────────
    const brandSel = document.getElementById('brandSelect');
    const volBox   = document.getElementById('volumeCheckboxes');

    function loadVolumes(brandId) {
        if (!brandId) {
            volBox.innerHTML = '<span class="text-muted small"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> Select a brand first</span>';
            return;
        }

        volBox.innerHTML = '<span class="text-muted small">Loading volumes…</span>';

        fetch(`include/brand_volume_ajax.php?action=for_brand&brand_id=${brandId}`)
            .then(r => r.json())
            .then(data => {
                if (!data.volumes || data.volumes.length === 0) {
                    volBox.innerHTML = `<span class="text-muted small">No volumes for this brand yet. <a href="edit_brand.php?id=${brandId}" target="_blank" style="color:#D4537E;">Add volumes here</a></span>`;
                    return;
                }
                let html = '';
                data.volumes.forEach(v => {
                    html += `
                    <label style="display:flex;align-items:center;gap:8px;margin-bottom:6px;cursor:pointer;padding:4px 6px;border-radius:6px;transition:background .15s;" onmouseover="this.style.background='#e9ecef'" onmouseout="this.style.background='transparent'">
                        <input type="checkbox" name="volume_ids[]" value="${v.id}" style="width:16px;height:16px;accent-color:#D4537E;cursor:pointer;">
                        <span style="font-size:13px;line-height:1.3;">
                            <strong style="color:#333;">Vol ${v.volume_number}</strong>
                            ${v.volume_name ? `<span style="color:#666;"> — ${v.volume_name}</span>` : ''}
                        </span>
                    </label>`;
                });
                volBox.innerHTML = html;
            })
            .catch(() => {
                volBox.innerHTML = '<span class="text-danger small">Error loading volumes</span>';
            });
    }

    if (brandSel) {
        brandSel.addEventListener('change', function () {
            // Use the FIRST selected brand for volumes
            const selected = Array.from(this.selectedOptions).map(o => o.value).filter(Boolean);
            loadVolumes(selected[0] || '');
        });
    }

    function generateSlug(value) {
        // Replace spaces with hyphens and convert to lowercase
        return value.trim().toLowerCase().replace(/ /g, '-').replace(/[^\w-]+/g, '');
    }
    
    document.getElementById('name').addEventListener('input', function() {
        const slugField = document.getElementById('slug');
        slugField.value = generateSlug(this.value);
    });
</script>

<?php include 'include/footer.php'; ?>
<script src="vendor/bootstrap-select/dist/js/bootstrap-select.min.js"></script>