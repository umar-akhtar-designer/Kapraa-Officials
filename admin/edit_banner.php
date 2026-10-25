<?php
    require 'include/head.php';
    session('WEBSITE-LOGIN');	

    $bannerId = cleanStr($_GET['id']);
    
    // Fetch current banner data
    $bannerQuery = selectQuery("SELECT * FROM banner WHERE id ='$bannerId' ORDER BY id DESC");
    $bannerData = $bannerQuery['data'][0];

    $banner_type    = $bannerData['banner_type'];
    $banner_for     = $bannerData['banner_for'];
    $image          = $bannerData['image'];
    $category_id    = $bannerData['category'];

    // Fetch category list
    $brand = '';
    $brandQuery = selectQuery("SELECT * FROM brand");
    if ($brandQuery['status'] === true) {
        foreach ($brandQuery['data'] as $data) {
            $main_brand = $data['id'];
            $brand_name = $data['name'];
            $selected = ($category_id == $main_brand) ? 'selected' : '';
            $brand .= '<option value="' . $main_brand . '" ' . $selected . '>' . $brand_name . '</option>';
        }
    }
    
    if(isset($_POST['update'])) {
        $banner_type    = cleanStr($_POST['banner_type']);
        $banner_for     = cleanStr($_POST['banner_for']);
        $category       = cleanStr($_POST['category']);
        
        // Check for restricted banner types
        $restricted_banners = ['Home Banner 2', 'Home Banner 3', 'Home Banner 4'];
        if (in_array($banner_type, $restricted_banners)) {
            $existing = selectQuery("SELECT id FROM banner WHERE banner_type = '$banner_type' AND banner_for = '$banner_for' AND id != '$bannerId'");
            if ($existing['status'] === true && count($existing['data']) > 0) {
                $error[] = "This banner type and platform combination already exists. Please update the existing one or choose different settings.";
            }
        }
        
        if(empty($error)) {
            // Handle image upload
            $imageName = $image; // default to existing image
            if(!empty($_FILES['image']['name'])) {
                $imgName   = $_FILES['image']['name'];
                $imgTmp    = $_FILES['image']['tmp_name'];
                $imgExt    = strtolower(pathinfo($imgName, PATHINFO_EXTENSION));
                $allowedExts = array('png', 'jpg', 'jpeg','webp');
                
                if (!in_array($imgExt, $allowedExts)) {
                    $error[] = "File type not allowed. Only PNG, JPG, webp, and JPEG are allowed.";
                } else {
                    $newName   = uniqid() . '.' . $imgExt;
                    $targetDir = "images/banner/";
                    $uploadPath = $targetDir . $newName;

                    // Move the file
                    if(move_uploaded_file($imgTmp, $uploadPath)) {
                        $imageName = $newName;
                        // Delete old image
                        if(file_exists($targetDir . $image)) {
                            unlink($targetDir . $image);
                        }
                    } else {
                        $error[] = "Image upload failed";
                    }
                }
            }

            // Update query
            if(empty($error)) {
                if(update_data_query('banner', array(
                    'banner_type' => $banner_type,
                    'banner_for'  => $banner_for,
                    'category'    => $category,
                    'image'       => $imageName
                ), "id = '$bannerId'") == true) {
                    $success[] = "Banner updated successfully";
                } else {
                    $error[] = "Something went wrong";
                }
            }
        }
    }
?>

<div class="content-body">
    <div class="container-fluid">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($error as $err): ?>
                                <p><?php echo $err; ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <?php foreach ($success as $msg): ?>
                                <p><?php echo $msg; ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="row">
                            <!-- Banner Type -->
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Banner type</label>
                                <select name="banner_type" class="form-control">
                                    <option value="">Please Select</option>
                                    <option value="Home Banner 1" <?= ($banner_type == "Home Banner 1") ? 'selected' : '' ?>>Home Banner 1</option>
                                    <option value="Home Banner 2" <?= ($banner_type == "Home Banner 2") ? 'selected' : '' ?>>Home Banner 2</option>
                                    <option value="Home Banner 3" <?= ($banner_type == "Home Banner 3") ? 'selected' : '' ?>>Home Banner 3</option>
                                    <option value="Home Banner 4" <?= ($banner_type == "Home Banner 4") ? 'selected' : '' ?>>Home Banner 4</option>
                                </select>
                            </div>

                            <!-- Banner For -->
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Banner For</label>
                                <select name="banner_for" class="form-control">
                                    <option value="">Please Select</option>
                                    <option value="For Desktop" <?= ($banner_for == "For Desktop") ? 'selected' : '' ?>>For Desktop</option>
                                    <option value="For Mobile" <?= ($banner_for == "For Mobile") ? 'selected' : '' ?>>For Mobile</option>
                                </select>
                            </div>

                            <!-- Category -->
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Brand</label>
                                <select name="category" class="form-control">
                                    <option value="">Please Select</option>
                                    <?= $brand ?>
                                </select>
                            </div>

                            <!-- Uploaded Banner Image -->
                            <div class="col-md-4">
                                <label class="font-weight-bold">Banner Images</label>
                                <input type="file" id="imgInp" name="image" class="form-control p-2 mb-3 bg-white">
                                <img id="blah" src="images/banner/<?= $image ?>" width="100" class="mt-2 border rounded">
                            </div>

                            <div class="col-md-12 text-end">
                                <button class="btn btn-primary mt-3" name="update">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function generateSlug(value) {
        return value.trim().toLowerCase().replace(/ /g, '-').replace(/[^\w-]+/g, '');
    }

    if (document.getElementById('name')) {
        document.getElementById('name').addEventListener('input', function () {
            const slugField = document.getElementById('slug');
            slugField.value = generateSlug(this.value);
        });
    }
    
    // Preview image when selected
    const imgInp = document.getElementById('imgInp');
    const blah = document.getElementById('blah');

    imgInp.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            blah.src = URL.createObjectURL(file);
        }
    });
</script>

<?php include 'include/footer.php'; ?>