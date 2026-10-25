<?php
    require 'include/head.php';
    session('WEBSITE-LOGIN');

    // Handle search functionality
    $searchText = isset($_GET['search']) ? cleanStr($_GET['search']) : '';

    if(isset($_POST['save'])){
        $name          = cleanStr($_POST['name']);
        $priority      = cleanStr($_POST['priority']);
        $slug          = cleanStr($_POST['slug']);
    
        if(empty($name))
            $error[] = "Please type Name";
    
        if(empty($error)){
            // Handle category images
            $uploadPath = 'images/category/';
            $allowedExts = array('png', 'jpg', 'jpeg','webp');
            $imageFilenames = []; // Array to store all uploaded image filenames
            
            // Check if any files were uploaded
            if (!empty($_FILES['image']['name'][0])) {
                // Loop through each uploaded file
                foreach ($_FILES['image']['tmp_name'] as $key => $tmp_name) {
                    $fileName = $_FILES['image']['name'][$key];
                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    
                    // Validate file type
                    if (!in_array($fileExt, $allowedExts)) {
                        $error[] = "File type not allowed for $fileName. Only PNG, JPG, webp, and JPEG are allowed.";
                    } else {
                        $filename = uniqid() . '.' . $fileExt;
                        $destination_img = $uploadPath . $filename;
                        
                        // Move uploaded file
                        if (move_uploaded_file($tmp_name, $destination_img)) {
                            $imageFilenames[] = $filename; // Add to filenames array
                        } else {
                            $error[] = "Failed to upload $fileName";
                        }
                    }
                }
            }
            
            // Handle category banner
            $bannerPath = 'images/cat_banner/';
            $bannerFilename = '';
            
            if (!empty($_FILES['cat_banner']['name'])) {
                $bannerName = $_FILES['cat_banner']['name'];
                $bannerExt = strtolower(pathinfo($bannerName, PATHINFO_EXTENSION));
                
                // Validate file type
                if (!in_array($bannerExt, $allowedExts)) {
                    $error[] = "File type not allowed for banner. Only PNG, JPG, webp, and JPEG are allowed.";
                } else {
                    $bannerFilename = uniqid() . '.' . $bannerExt;
                    $destination_banner = $bannerPath . $bannerFilename;
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($bannerPath)) {
                        mkdir($bannerPath, 0777, true);
                    }
                    
                    // Move uploaded file
                    if (!move_uploaded_file($_FILES['cat_banner']['tmp_name'], $destination_banner)) {
                        $error[] = "Failed to upload banner image";
                        $bannerFilename = '';
                    }
                }
            }
            
            // If no errors occurred during upload
            if(empty($error)){
                $insert = insert_and_update_data('insert', 'category', array(
                    'slug'     => $slug,
                    'name'     => $name,
                    'priority' => $priority, 
                    'image'    => json_encode($imageFilenames), // Store as JSON array
                    'cat_banner' => $bannerFilename // Store banner filename
                ));
                
                if($insert == true){
                    $error[] = 'Category added successfully';
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
                            aria-expanded="false" aria-controls="collapseExample">Add Category</button>
                    <button class="btn btn-primary" data-bs-toggle="collapse" href="#searchCollapse" role="button"
                            aria-expanded="false" aria-controls="searchCollapse">Search</button>
                </div>
            </div>
            
            <!-- Add Category Collapse -->
            <div class="collapse" id="collapseExample">
                <div class="card">
                    <div class="card-body">
                        <form action="" method="post" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="" class="font-weight-bold">Category Name</label>
                                    <input type="text" class="form-control" name="name" id="name" placeholder="Enter Name">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="" class="font-weight-bold">Slug</label>
                                    <input type="text" name="slug" id="slug" class="form-control" readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="" class="font-weight-bold">Priority</label>
                                    <select name="priority" id="" class="form-control">
                                        <option value="">Please Select</option>
                                        <?php for($i=1; $i<=10; $i++): ?>
                                            <option value="<?= $i ?>"><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                               
                                <div class="col-md-4 mb-3">
                                    <label for="" class="font-weight-bold">Category Images</label>
                                    <input type="file" id="imgInp" name="image[]" class="form-control bg-white" multiple>
                                    <div id="imagePreview" class="mt-3 d-flex flex-wrap"></div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="" class="font-weight-bold">Category Banner</label>
                                    <input type="file" id="bannerInp" name="cat_banner" class="form-control bg-white">
                                    <div id="bannerPreview" class="mt-3"></div>
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
                                    <input type="text" name="search" class="form-control" placeholder="Search by Category Name" value="<?= htmlspecialchars($searchText) ?>">
                                </div>
                                <div class="col-md-12 text-end">
                                    <button type="submit" class="btn btn-primary mt-2">Search</button>
                                    <?php if(!empty($searchText)): ?>
                                        <a href="categories.php" class="btn btn-secondary mt-2">Clear</a>
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
                    <h4 class="card-title">Category</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Category Name</th>
                                    <th>Category Images</th>
                                    <th>Category Banner</th>
                                    <th>Priority</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php

                                    // Modify the query to include search functionality
                                    $query = "SELECT * FROM category";
                                    if (!empty($searchText)) {
                                        $query .= " WHERE name LIKE '%" . $searchText . "%'";
                                    }
                                    $query .= " order by id desc";

                                    $categoryQuery = selectQuery($query,
                                    [
                                        'dataPerPage'		=>	'20',
                                        'searchVariable'	=>	'search=' . $searchText . '&',
                                        'getVariable'		=>	'page',
                                        'PageUrl'			=>	'categories.php',
                                    ]);

                                    if($categoryQuery['status'] == true){
                                        foreach($categoryQuery['data'] as $sr => $data){
                                            $categoryId = $data['id'];
                                            $name = $data['name'];
                                            $slug = $data['slug'];
                                            
                                            $imageJson = $data['image'];
                                            $priority = $data['priority'];
                                            $catBanner = $data['cat_banner'] ?? '';
                                            
                                            // Decode JSON image array
                                            $images = json_decode($imageJson, true) ?: [];
                                            $imageHtml = '';
                                            
                                            // Generate HTML for all images
                                            foreach($images as $img) {
                                                $imageHtml .= '<img src="images/category/'.$img.'" height="40px" class="rounded me-1">';
                                            }
                                            
                                            // Generate HTML for banner image
                                            $bannerHtml = '';
                                            if (!empty($catBanner)) {
                                                $bannerHtml = '<img src="images/cat_banner/'.$catBanner.'" height="40px" class="rounded">';
                                            }
                                            
                                            echo '<tr>
                                                    <th>'.$name.'</th>
                                                    <td>'.$imageHtml.'</td>
                                                    <td>'.$bannerHtml.'</td>
                                                    <td>'.$priority.'</td>
                                                    <td class="d-flex">
                                                        <a href="edit_categories.php?id='.$categoryId.'" class="btn btn-primary btn-sm me-1"><i class="fa fa-pencil"></i></a>
                                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                                            data-id="'.$categoryId.'" data-table="category" data-column="id">
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
                                echo $categoryQuery['pagination'];
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Preview multiple category images
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
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.width = '100px';
                    img.style.height = 'auto';
                    img.style.marginRight = '10px';
                    img.style.marginBottom = '10px';
                    img.className = 'rounded';
                    previewContainer.appendChild(img);
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
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.width = '200px';
                img.style.height = 'auto';
                img.className = 'rounded';
                bannerPreview.appendChild(img);
            }
            
            reader.readAsDataURL(file);
        }
    });

    // Generate slug from name
    function generateSlug(value) {
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