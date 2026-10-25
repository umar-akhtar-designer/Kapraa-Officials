<?php
    require 'include/head.php';
    session('WEBSITE-LOGIN');	

    $subcategoryId = cleanStr($_GET['id']);
    
    // Fetch current subcategory data
    $subcatQuery = selectQuery("SELECT * FROM subcategory WHERE id ='$subcategoryId' ORDER BY id DESC");
    $subcatData = $subcatQuery['data'][0];

    $name     = $subcatData['name'];
    $slug     = $subcatData['slug'];
    $priority = $subcatData['priority'];
    $image    = $subcatData['image'];
    $sale     = $subcatData['sale'];
    $main_category = $subcatData['main_category'];
    $subcat_banner = $subcatData['subcat_banner'];

    // Fetch category list
    $categories = '';
    $catQuery = selectQuery("SELECT * FROM category");
    if ($catQuery['status'] === true) {
        foreach ($catQuery['data'] as $data) {
            $main_categorys = $data['id'];
            $category_name = $data['name'];
            $selected = ($main_category == $main_categorys) ? 'selected' : '';
            $categories .= '<option value="' . $main_categorys . '" ' . $selected . '>' . $category_name . '</option>';
        }
    }
    
    if(isset($_POST['update'])) {
        
        $name        = cleanStr($_POST['name']);
        $priority    = cleanStr($_POST['priority']);
        $slug        = cleanStr($_POST['slug']);
        $main_category  = cleanStr($_POST['main_category']);
        $sale        = cleanStr($_POST['sale']);
        $allowedExts = array('png', 'jpg', 'jpeg','webp');
        $error = [];

        // Handle image upload
        $imageName = $image; // default to existing image
        if(!empty($_FILES['image']['name'])) {
            $imgName   = $_FILES['image']['name'];
            $imgTmp    = $_FILES['image']['tmp_name'];
            $imgExt    = strtolower(pathinfo($imgName, PATHINFO_EXTENSION));
            
            // Validate file type
            if (!in_array($imgExt, $allowedExts)) {
                $error[] = "File type not allowed for image. Only PNG, JPG, webp, and JPEG are allowed.";
            } else {
                $newName   = uniqid() . '.' . $imgExt;
                $targetDir = "images/subcategory/";
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

        // Handle banner upload
        $bannerName = $subcat_banner; // default to existing banner
        if(!empty($_FILES['subcat_banner']['name'])) {
            $bannerImgName   = $_FILES['subcat_banner']['name'];
            $bannerTmp    = $_FILES['subcat_banner']['tmp_name'];
            $bannerExt    = strtolower(pathinfo($bannerImgName, PATHINFO_EXTENSION));
            
            // Validate file type
            if (!in_array($bannerExt, $allowedExts)) {
                $error[] = "File type not allowed for banner. Only PNG, JPG, webp, and JPEG are allowed.";
            } else {
                $newBannerName   = uniqid() . '.' . $bannerExt;
                $bannerDir = "images/subcat_banner/";
                $bannerUploadPath = $bannerDir . $newBannerName;
                
                // Create directory if it doesn't exist
                if (!file_exists($bannerDir)) {
                    mkdir($bannerDir, 0777, true);
                }

                // Move the file
                if(move_uploaded_file($bannerTmp, $bannerUploadPath)) {
                    $bannerName = $newBannerName;
                    // Delete old banner
                    if(!empty($subcat_banner) && file_exists($bannerDir . $subcat_banner)) {
                        unlink($bannerDir . $subcat_banner);
                    }
                } else {
                    $error[] = "Banner upload failed";
                }
            }
        }

        // Update query if no errors
        if(empty($error)) {
            if(update_data_query('subcategory', array(
                'name'        => $name,
                'priority'    => $priority,
                'slug'        => $slug,
                'main_category'  => $main_category,
                'image'       => $imageName,
                'sale'        => $sale,
                'subcat_banner' => $bannerName
            ), "id = '$subcategoryId'") == true) {
                $success[] = "Subcategory updated successfully";
                location('sub_categories.php');
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
                            <div class="col-md-4 mb-3">
                                <label for="" class="font-weight-bold">Sub Category Name</label>
                                <input type="text" class="form-control" name="name" id="name" value="<?php echo $name?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="" class="font-weight-bold">Slug</label>
                                <input type="text" name="slug" id="slug" class="form-control" readonly value="<?php echo $slug?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="" class="font-weight-bold">Main Category</label>
                                <select name="main_category" id="" class="form-control">
                                    <option value="">Please Select</option>
                                    <?php echo $categories?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
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
                            <div class="col-md-4 mb-3">
                                <label for="" class="font-weight-bold">Sub Category Image</label>
                                <input type="file" id="imgInp" name="image" class="form-control p-2 mb-3 bg-white">
                                <div class="mt-2">
                                    <label class="small text-muted">Current Image:</label>
                                    <img id="blah" src="images/subcategory/<?php echo $image; ?>" width="100" class="border rounded">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="" class="font-weight-bold">Sub Category Banner</label>
                                <input type="file" id="bannerInp" name="subcat_banner" class="form-control bg-white">
                                <div class="mt-2">
                                    <label class="small text-muted">Current Banner:</label>
                                    <?php if (!empty($subcat_banner)): ?>
                                        <img id="currentBanner" src="images/subcat_banner/<?php echo $subcat_banner; ?>" width="100" class="border rounded">
                                    <?php else: ?>
                                        <p class="text-muted">No banner uploaded</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="" class="font-weight-bold">Sale</label>
                                <input type="text" name="sale" class="form-control" value="<?php echo $sale?>">
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
    // Preview subcategory image
    const imgInp = document.getElementById('imgInp');
    const blah = document.getElementById('blah');

    imgInp.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            blah.src = URL.createObjectURL(file);
            blah.style.width = '100px';
            blah.style.height = 'auto';
        }
    });

    // Preview banner image
    const bannerInp = document.getElementById('bannerInp');
    const bannerPreview = document.getElementById('currentBanner');

    bannerInp.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            // Create a new image element for preview if it doesn't exist
            if (!document.getElementById('newBannerPreview')) {
                const newImg = document.createElement('img');
                newImg.id = 'newBannerPreview';
                newImg.style.width = '100px';
                newImg.style.height = 'auto';
                newImg.className = 'border rounded ms-2';
                bannerPreview.parentNode.appendChild(newImg);
            }
            
            // Set the preview image
            document.getElementById('newBannerPreview').src = URL.createObjectURL(file);
        }
    });

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