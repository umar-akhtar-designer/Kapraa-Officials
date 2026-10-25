<?php
    require 'include/head.php';
    session('WEBSITE-LOGIN');
    
    // Handle search functionality
    $searchText = isset($_GET['search']) ? cleanStr($_GET['search']) : '';
    
    if(isset($_POST['save'])){
        
        $name          = cleanStr($_POST['name']);
        $slug          = cleanStr($_POST['slug']);
        $brand_main    = cleanStr($_POST['brand_main']);
        $tag_line      = cleanStr($_POST['tag_line']);
        $discount_text = cleanStr($_POST['discount_text'] ?? '');
        
        if(empty($name))
            $error[] = "Please type Name";
      
        if(empty($error)){
            // Check if slug already exists and make it unique if needed
            $originalSlug = $slug;
            $counter = 1;
            
            while(true) {
                $checkSlug = selectQuery("SELECT id FROM brand WHERE slug = '$slug'");
                if($checkSlug['status'] == true && !empty($checkSlug['data'])) {
                    // Slug exists, append counter and check again
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                } else {
                    // Slug is unique, break the loop
                    break;
                }
            }

            $uploadPath = 'images/brand/';
            $bannerPath = 'images/brand_banner/';
            $allowedExts = array('png', 'jpg', 'jpeg','webp');
            $filename = '';
            $bannerFilename = '';
            
            // Process brand image
            if (!empty($_FILES['image']['tmp_name'])) {
                $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
                if (!in_array($fileExt, $allowedExts)) {
                    $error[] = "File type not allowed for brand image. Only PNG, JPG, webp, and JPEG are allowed.";
                } else {
                    $filename = uniqid() . '.' . $fileExt;
                    $destination_img = $uploadPath . $filename;
                    
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $destination_img)) {
                        $error[] = "Failed to upload brand image";
                    }
                }
            }
            
            // Process brand banner
            if (!empty($_FILES['brand_banner']['tmp_name'])) {
                $fileExt = strtolower(pathinfo($_FILES['brand_banner']['name'], PATHINFO_EXTENSION));
        
                if (!in_array($fileExt, $allowedExts)) {
                    $error[] = "File type not allowed for banner. Only PNG, JPG, webp, and JPEG are allowed.";
                } else {
                    $bannerFilename = uniqid() . '.' . $fileExt;
                    $destination_banner = $bannerPath . $bannerFilename;
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($bannerPath)) {
                        mkdir($bannerPath, 0777, true);
                    }
                    
                    if (!move_uploaded_file($_FILES['brand_banner']['tmp_name'], $destination_banner)) {
                        $error[] = "Failed to upload brand banner";
                    }
                }
            }
            
            // Insert data if no errors
            if(empty($error)) {
                $insert = insert_and_update_data('insert', 'brand', array(
                    'slug'          => $slug,
                    'name'          => $name,
                    'tag_line'      => $tag_line,
                    'brand_main'    => $brand_main,
                    'image'         => $filename,
                    'brand_banner'  => $bannerFilename,
                    'discount_text' => $discount_text
                ));
                
                if($insert == true){
                    $error[] = 'Brand added successfully';
                }
            }
        }
    }
?>
<div class="content-body">
    <div class="container-fluid">
        <div class="col-md-12">
            <div class="row">
                <div class="col-md-6"></div>
                <div class="col-md-6 text-end mb-3">
                    <button class="btn btn-primary me-2" data-bs-toggle="collapse" href="#collapseExample" role="button"
                            aria-expanded="false" aria-controls="collapseExample">Add Brand</button>
                    <button class="btn btn-primary" data-bs-toggle="collapse" href="#searchCollapse" role="button"
                            aria-expanded="false" aria-controls="searchCollapse">Search</button>
                </div>
            </div>
            
            <!-- Add Brand Collapse -->
            <div class="collapse" id="collapseExample">
                <div class="card">
                    <div class="card-body">
                        <form action="" method="post" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="" class="font-weight-bold">Brand Name</label>
                                    <input type="text" class="form-control" name="name" id="name" placeholder="Enter Name">
                                </div>
                                <div class="col-md-4">
                                    <label for="" class="font-weight-bold">Slug</label>
                                    <input type="text" name="slug" id="slug" class="form-control" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label for="" class="font-weight-bold">Brand Images</label>
                                    <input type="file" id="imgInp" name="image" class="form-control bg-white">
                                    <img id="blah" width="100px" class="mt-3 rounded">
                                </div>
                                <div class="col-md-4">
                                    <label for="" class="font-weight-bold">Brand Main Page</label>
                                    <select name="brand_main" class="form-control" id="">
                                        <option value="">-- Please Select --</option>
                                        <option value="New Arrivals">New Arrivals</option>
                                        <!-- <option value="Best Sellers">Best Sellers</option> -->
                                        <option value="Festive Wear">Festive Wear</option>
                                        <option value="Unstitched Dresses">Unstitched Dresses</option>
                                        <option value="Mens Wear">Mens Wear</option>
                                        <option value="Kids Wear">Kids Wear</option>
                                        <!-- <option value="Accessories and Footwear">Accessories and Footwear</option> -->
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="" class="font-weight-bold">Brand Tag Line</label>
                                    <input type="text" class="form-control" name="tag_line" placeholder="Tag Line">
                                </div>
                                <div class="col-md-4">
                                    <label for="" class="font-weight-bold">Discount Text <small class="text-muted">(e.g. UPTO 40% OFF)</small></label>
                                    <input type="text" class="form-control" name="discount_text" placeholder="e.g. UPTO 40% OFF or FLAT 20% OFF">
                                    <small class="text-muted">Shows on circle flip animation. Leave blank to disable.</small>
                                </div>
                                <div class="col-md-4">
                                    <label for="" class="font-weight-bold">Brand Banner</label>
                                    <input type="file" id="bannerInp" name="brand_banner" class="form-control bg-white">
                                    <img id="bannerPreview" width="100px" class="mt-3 rounded">
                                </div>
                                <div class="col-md-12 text-end">
                                    <button class="btn btn-primary mt-3" name="save">Save</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Search Collapse -->
            <div class="collapse mb-3" id="searchCollapse">
                <div class="card">
                    <div class="card-body">
                        <form action="" method="get">
                            <div class="row">
                                <div class="col-md-12">
                                    <input type="text" name="search" class="form-control" placeholder="Search by Brand Name" value="<?= htmlspecialchars($searchText) ?>">
                                </div>
                                <div class="col-md-12 text-end">
                                    <button type="submit" class="btn btn-primary mt-2">Search</button>
                                    <?php if(!empty($searchText)): ?>
                                        <a href="add_brand.php" class="btn btn-secondary mt-2">Clear</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Brand</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Brand Name</th>
                                    <th>Brand Images</th>
                                    <th>Brand Banner</th>
                                    <th>Brand Main</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php

                                    // Modify the query to include search functionality
                                    $query = "SELECT * FROM brand";
                                    if (!empty($searchText)) {
                                        $query .= " WHERE name LIKE '%" . $searchText . "%'";
                                    }
                                    $query .= " order by id desc";

                                    $brandQuery = selectQuery($query,
                                    
                                    [
                                        'dataPerPage'		=>	'30',
                                        'searchVariable'	=>	'search=' . $searchText . '&',
                                        'getVariable'		=>	'page',
                                        'PageUrl'			=>	'add_brand.php',
                                    ]);

                                    if($brandQuery['status'] == true){
                                        foreach($brandQuery['data'] as $sr => $data){
                                            $brandId = $data['id'];
                                            $name = $data['name'];
                                            $brand_main = $data['brand_main'];
                                            $slug = $data['slug'];
                                            $image = $data['image'];
                                            $brand_banner = $data['brand_banner'] ?? '';
                                            
                                            // Generate HTML for brand image
                                            $imageHtml = '';
                                            if (!empty($image)) {
                                                $imageHtml = '<img src="images/brand/'.$image.'" height="40px" class="rounded">';
                                            }
                                            
                                            // Generate HTML for banner image
                                            $bannerHtml = '';
                                            if (!empty($brand_banner)) {
                                                $bannerHtml = '<img src="images/brand_banner/'.$brand_banner.'" height="40px" class="rounded">';
                                            }
                                            
                                            echo '<tr>
                                                    <th>'.$name.'</th>
                                                    <td>'.$imageHtml.'</td>
                                                    <td>'.$bannerHtml.'</td>
                                                    <th>'.$brand_main.'</th>
                                                    <td class="d-flex">
                                                        <a href="edit_brand.php?id='.$brandId.'" class="btn btn-primary btn-sm me-1"><i class="fa fa-pencil"></i></a>
                                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                                            data-id="'.$brandId.'" data-table="brand" data-column="id">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>';
                                        }                
                                    }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="row py-3">
                        <div class="col-md-12 text-right">
                            <?php
                                echo $brandQuery['pagination'];
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Preview brand image
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
    const bannerPreview = document.getElementById('bannerPreview');

    bannerInp.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            bannerPreview.src = URL.createObjectURL(file);
            bannerPreview.style.width = '100px';
            bannerPreview.style.height = 'auto';
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
<?php
  include 'include/footer.php';
?>