<?php
    require 'include/head.php';
    session('WEBSITE-LOGIN');	

    $categoryId = cleanStr($_GET['id']);

    // Fetch current category data
    $categoryQuery = selectQuery("SELECT * FROM category WHERE id ='$categoryId' ORDER BY id DESC");
    $categoryData = $categoryQuery['data'][0];

    $name       = $categoryData['name'];
    $priority   = $categoryData['priority'];
    $imageJson  = $categoryData['image'];
    $slug       = $categoryData['slug'];
    $catBanner  = $categoryData['cat_banner'];
    
    // Decode JSON image array or handle single image format
    $existingImages = json_decode($imageJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Handle single image format (backward compatibility)
        $existingImages = !empty($imageJson) ? [$imageJson] : [];
    }

    if(isset($_POST['update'])) {
        $name        = cleanStr($_POST['name']);
        $priority    = cleanStr($_POST['priority']);
        $slug        = cleanStr($_POST['slug']);
        
        
        $targetDir = "images/category/";
        $bannerDir = "images/cat_banner/";
        $uploadPath = $targetDir;
        $allowedExts = array('png', 'jpg', 'jpeg','webp');
        $newImages = [];
        $error = [];
        
        // Process uploaded images if any
        if (!empty($_FILES['image']['name'][0])) {
            foreach ($_FILES['image']['tmp_name'] as $key => $tmp_name) {
                $fileName = $_FILES['image']['name'][$key];
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                // Validate file type
                if (!in_array($fileExt, $allowedExts)) {
                    $error[] = "File type not allowed for $fileName. Only PNG, JPG, webp, and JPEG are allowed.";
                    continue;
                }
                
                $newName = uniqid() . '.' . $fileExt;
                $uploadFile = $uploadPath . $newName;
                
                if (move_uploaded_file($tmp_name, $uploadFile)) {
                    $newImages[] = $newName;
                } else {
                    $error[] = "Failed to upload $fileName";
                }
            }
        }
        
        // Process banner upload if provided
        $newBanner = $catBanner; // Default to existing banner
        if (!empty($_FILES['cat_banner']['name'])) {
            $bannerName = $_FILES['cat_banner']['name'];
            $bannerExt = strtolower(pathinfo($bannerName, PATHINFO_EXTENSION));
            
            // Validate file type
            if (!in_array($bannerExt, $allowedExts)) {
                $error[] = "File type not allowed for banner. Only PNG, JPG, webp, and JPEG are allowed.";
            } else {
                $newBannerName = uniqid() . '.' . $bannerExt;
                $uploadBanner = $bannerDir . $newBannerName;
                
                // Create directory if it doesn't exist
                if (!file_exists($bannerDir)) {
                    mkdir($bannerDir, 0777, true);
                }
                
                if (move_uploaded_file($_FILES['cat_banner']['tmp_name'], $uploadBanner)) {
                    // Delete old banner if exists
                    if (!empty($catBanner) && file_exists($bannerDir . $catBanner)) {
                        unlink($bannerDir . $catBanner);
                    }
                    $newBanner = $newBannerName;
                } else {
                    $error[] = "Failed to upload banner image";
                }
            }
        }
        
        // If no errors during upload
        if (empty($error)) {
            // If new images were uploaded, replace existing ones
            if (!empty($newImages)) {
                // Delete old images
                foreach ($existingImages as $oldImage) {
                    if (file_exists($targetDir . $oldImage)) {
                        unlink($targetDir . $oldImage);
                    }
                }
                $finalImages = $newImages;
            } else {
                // Keep existing images
                $finalImages = $existingImages;
            }
            
            // Update category data
            if(update_data_query('category', array(
                'name'        => $name,
                'priority'    => $priority,
                'slug'        => $slug,
                'image'       => json_encode($finalImages),
                'cat_banner'  => $newBanner
            ), "id = '$categoryId'") == true) {
                location('categories.php');
            } else {
                $error[] = "Something went wrong";
            }
        }
    }
?>
<div class="content-body">
    <div class="container-fluid">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="" class="font-weight-bold">Category Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo $name?>" placeholder="Enter Name">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="" class="font-weight-bold">Slug</label>
                                <input type="text" name="slug" id="slug" class="form-control" value="<?php echo $slug ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label for="" class="font-weight-bold">Priority</label>
                                <select name="priority" class="form-control">
                                    <option value="">Please Select</option>
                                    <?php 
                                        for ($i = 1; $i <= 10; $i++) {
                                            $selected = ($priority == $i) ? "selected" : "";
                                            echo "<option value='$i' $selected>$i</option>";
                                        }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="" class="font-weight-bold">Upload New Images</label>
                                <input type="file" id="imgInp" name="image[]" class="form-control p-2 mb-3 bg-white" multiple>
                                <div id="imagePreview" class="d-flex flex-wrap"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="" class="font-weight-bold">Category Banner</label>
                                <input type="file" id="bannerInp" name="cat_banner" class="form-control bg-white">
                                <div id="bannerPreview" class="mt-3"></div>
                            </div>
                            <div class="col-md-12">
                                <label for="" class="font-weight-bold">Current Images</label>
                                <div class="d-flex flex-wrap mb-3">
                                    <?php if (!empty($existingImages)): ?>
                                        <?php foreach ($existingImages as $img): ?>
                                            <div class="position-relative me-2 mb-2">
                                                <img src="images/category/<?php echo $img; ?>" width="100" class="border rounded">
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted">No images uploaded</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label for="" class="font-weight-bold">Current Banner</label>
                                <div class="d-flex flex-wrap mb-3">
                                    <?php if (!empty($catBanner)): ?>
                                        <div class="position-relative me-2 mb-2">
                                            <img src="images/cat_banner/<?php echo $catBanner; ?>" width="200" class="border rounded">
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">No banner uploaded</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-12 text-end">
                                <button class="btn btn-primary mt-3" name="update">Save Changes</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Preview multiple images
    const imgInp = document.getElementById('imgInp');
    const previewContainer = document.getElementById('imagePreview');

    imgInp.addEventListener('change', function(event) {
        previewContainer.innerHTML = ''; // Clear previous previews
        
        // Loop through all selected files
        for (let i = 0; i < event.target.files.length; i++) {
            const file = event.target.files[i];
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const imgContainer = document.createElement('div');
                    imgContainer.className = 'position-relative me-2 mb-2';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.width = '100px';
                    img.style.height = 'auto';
                    img.className = 'border rounded';
                    
                    const badge = document.createElement('span');
                    badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success';
            
                    imgContainer.appendChild(img);
                    imgContainer.appendChild(badge);
                    previewContainer.appendChild(imgContainer);
                }
                
                reader.readAsDataURL(file);
            }
        }
    });

    // Preview banner image
    const bannerInp = document.getElementById('bannerInp');
    const bannerPreview = document.getElementById('bannerPreview');

    bannerInp.addEventListener('change', function(event) {
        bannerPreview.innerHTML = ''; // Clear previous preview
        
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const imgContainer = document.createElement('div');
                imgContainer.className = 'position-relative me-2 mb-2';
                
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.width = '200px';
                img.style.height = 'auto';
                img.className = 'border rounded';
                
                const badge = document.createElement('span');
                badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success';
                badge.textContent = 'New';
                
                imgContainer.appendChild(img);
                imgContainer.appendChild(badge);
                bannerPreview.appendChild(imgContainer);
            }
            
            reader.readAsDataURL(file);
        }
    });

    // Generate slug from name
    function generateSlug(value) {
        return value.trim().toLowerCase().replace(/ /g, '-').replace(/[^\w-]+/g, '');
    }

    document.getElementById('name').addEventListener('input', function () {
        const slugField = document.getElementById('slug');
        slugField.value = generateSlug(this.value);
    });
</script>
<?php
    include 'include/footer.php';
?>