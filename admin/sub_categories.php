<?php
    require 'include/head.php';
    session('WEBSITE-LOGIN');
    
    // Handle search functionality
    $searchText = isset($_GET['search']) ? cleanStr($_GET['search']) : '';
    
    if(isset($_POST['save'])){
    
        $name          = cleanStr($_POST['name']);
        $priority      = cleanStr($_POST['priority']);
        $slug          = cleanStr($_POST['slug']);
        $main_category = cleanStr($_POST['main_category']);
        $sale          = cleanStr($_POST['sale']);
        
        if(empty($name))
            $error[] = "Please type Name";
      
        if(empty($error)){
    
            $uploadPath = 'images/subcategory/';
            $bannerPath = 'images/subcat_banner/';
            $allowedExts = array('png', 'jpg', 'jpeg','webp');
            $filename = '';
            $bannerFilename = '';
            
            // Process subcategory image
            if (!empty($_FILES['image']['tmp_name'])) {
                $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
                if (!in_array($fileExt, $allowedExts)) {
                    $error[] = "File type not allowed for image. Only PNG, JPG, webp, and JPEG are allowed.";
                } else {
                    $filename = uniqid() . '.' . $fileExt;
                    $destination_img = $uploadPath . $filename;
                    
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $destination_img)) {
                        $error[] = "Failed to upload subcategory image";
                    }
                }
            }
            
            // Process subcategory banner
            if (!empty($_FILES['subcat_banner']['tmp_name'])) {
                $fileExt = strtolower(pathinfo($_FILES['subcat_banner']['name'], PATHINFO_EXTENSION));
        
                if (!in_array($fileExt, $allowedExts)) {
                    $error[] = "File type not allowed for banner. Only PNG, JPG, webp, and JPEG are allowed.";
                } else {
                    $bannerFilename = uniqid() . '.' . $fileExt;
                    $destination_banner = $bannerPath . $bannerFilename;
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($bannerPath)) {
                        mkdir($bannerPath, 0777, true);
                    }
                    
                    if (!move_uploaded_file($_FILES['subcat_banner']['tmp_name'], $destination_banner)) {
                        $error[] = "Failed to upload subcategory banner";
                    }
                }
            }
            
            // Insert data if no errors
            if(empty($error)) {
                $insert = insert_and_update_data('insert', 'subcategory', array(
                    'slug' => $slug,
                    'name' => $name,
                    'priority' => $priority,
                    'main_category' => $main_category,
                    'image' => $filename,
                    'sale'     => $sale,
                    'subcat_banner' => $bannerFilename
                ));
                
                if($insert == true){
                    $error[] = 'Sub Category added successfully';
                }
            }
        }
    }
    
    // Fetch designation options
    $categoryQuery = selectQuery("SELECT * FROM category");
    $categories = '';
    if ($categoryQuery['status'] === true) {
        foreach ($categoryQuery['data'] as $data) {
            $category_id = $data['id'];
            $category_name = $data['name'];
            $categories .= '<option value="' . $category_id . '">' . $category_name . '</option>';
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
                            aria-expanded="false" aria-controls="collapseExample">Add Sub Category</button>
                    <button class="btn btn-primary" data-bs-toggle="collapse" href="#searchCollapse" role="button"
                            aria-expanded="false" aria-controls="searchCollapse">Search</button>
                </div>
            </div>
            
            <!-- Add Sub Category Collapse -->
            <div class="collapse" id="collapseExample">
                <div class="card">
                    <div class="card-body">
                        <form action="" method="post" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="" class="font-weight-bold">Sub Category Name</label>
                                    <input type="text" class="form-control" name="name" id="name" placeholder="Enter Name">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="" class="font-weight-bold">Slug</label>
                                    <input type="text" name="slug" id="slug" class="form-control" readonly>
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
                                    <select name="priority" id="" class="form-control">
                                        <option value="">Please Select</option>
                                        <?php for($i=1; $i<=10; $i++): ?>
                                            <option value="<?= $i ?>"><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="" class="font-weight-bold">Sub Category Images</label>
                                    <input type="file" id="imgInp" name="image" class="form-control bg-white">
                                    <img id="imagePreview" width="100px" class="mt-3 rounded">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="" class="font-weight-bold">Sub Category Banner</label>
                                    <input type="file" id="bannerInp" name="subcat_banner" class="form-control bg-white">
                                    <img id="bannerPreview" width="100px" class="mt-3 rounded">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="" class="font-weight-bold">Sale</label>
                                    <input type="text" name="sale"  class="form-control">
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
                                    <input type="text" name="search" class="form-control" placeholder="Search by Subcategory Name" value="<?= htmlspecialchars($searchText) ?>">
                                </div>
                                <div class="col-md-12 text-end">
                                    <button type="submit" class="btn btn-primary mt-2">Search</button>
                                    <?php if(!empty($searchText)): ?>
                                        <a href="sub_categories.php" class="btn btn-secondary mt-2">Clear</a>
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
                    <h4 class="card-title">Sub Category</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Sub Category Name</th>
                                    <th>Main Category</th>
                                    <th>Category Images</th>
                                    <th>Category Banner</th>
                                    <th>Sale</th>
                                    <th>Priority</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php

                                    // Modify the query to include search functionality
                                    $query = "SELECT * FROM subcategory";
                                    if (!empty($searchText)) {
                                        $query .= " WHERE name LIKE '%" . $searchText . "%'";
                                    }
                                    $query .= " order by id desc";

                                    $subcategoryQuery = selectQuery($query,
                                    [
                                        'dataPerPage'		=>	'50',
                                        'searchVariable'	=>	'search=' . $searchText . '&',
                                        'getVariable'		=>	'page',
                                        'PageUrl'			=>	'sub_categories.php',
                                    ]);

                                    if($subcategoryQuery['status'] == true){
                                        foreach($subcategoryQuery['data'] as $sr => $data){
                                            $subcategoryId = $data['id'];
                                            $name = $data['name'];
                                            $slug = $data['slug'];
                                            $sale = $data['sale'];
                                            $image = $data['image'];
                                            $priority = $data['priority'];
                                            $main_category = $data['main_category'];
                                            $subcat_banner = $data['subcat_banner'] ?? '';
                                            
                                            $catQuery = selectQuery("SELECT * FROM category WHERE id = '$main_category'");
                                            $cat_name = $catQuery['data'][0]['name'];
                                            
                                            // Generate HTML for images
                                            $imageHtml = '';
                                            if (!empty($image)) {
                                                $imageHtml = '<img src="images/subcategory/'.$image.'" height="40px" class="rounded">';
                                            }
                                            
                                            // Generate HTML for banner
                                            $bannerHtml = '';
                                            if (!empty($subcat_banner)) {
                                                $bannerHtml = '<img src="images/subcat_banner/'.$subcat_banner.'" height="40px" class="rounded">';
                                            }
                                                                            
                                        echo '<tr>
                                                <th>'.$name.'</th>
                                                <th>'.$cat_name.'</th>
                                                <td>'.$imageHtml.'</td>
                                                <td>'.$bannerHtml.'</td>
                                                <td>'.$sale.'</td>
                                                <td>'.$priority.'</td>
                                                <td class="d-flex">
                                                    <a href="edit_sub_categories.php?id='.$subcategoryId.'" class="btn btn-primary btn-sm me-1"><i class="fa fa-pencil"></i></a>
                                                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                                        data-id="'.$subcategoryId.'" data-table="subcategory" data-column="id">
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
                                echo $subcategoryQuery['pagination'];
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Preview subcategory image
    const imgInp = document.getElementById('imgInp');
    const imagePreview = document.getElementById('imagePreview');

    imgInp.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            imagePreview.src = URL.createObjectURL(file);
        }
    });
    
    // Preview subcategory banner
    const bannerInp = document.getElementById('bannerInp');
    const bannerPreview = document.getElementById('bannerPreview');

    bannerInp.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            bannerPreview.src = URL.createObjectURL(file);
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