<?php
    require 'include/head.php';
    session('WEBSITE-LOGIN');
    
    // Handle search functionality
    $searchText = isset($_GET['search']) ? cleanStr($_GET['search']) : '';
    
    if(isset($_POST['save'])){
        $banner_type       = cleanStr($_POST['banner_type']);
        $banner_for        = cleanStr($_POST['banner_for']);
        $category          = cleanStr($_POST['category']);
        
        // Check for restricted banner types
        $restricted_banners = ['Home Banner 2', 'Home Banner 3', 'Home Banner 4'];
        if (in_array($banner_type, $restricted_banners)) {
            $existing = selectQuery("SELECT id FROM banner WHERE banner_type = '$banner_type' AND banner_for = '$banner_for'");
            if ($existing['status'] === true && count($existing['data']) > 0) {
                $error[] = "This banner type and platform combination already exists. Please update or delete the existing one.";
            }
        }
        
        if(empty($error)){
            $uploadPath = 'images/banner/';
            $allowedExts = array('png', 'jpg', 'jpeg','webp');
        
            if (!empty($_FILES['image']['tmp_name'])) {
                $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
                if (!in_array($fileExt, $allowedExts)) {
                    $error[] = "File type not allowed. Only PNG, JPG, webp, and JPEG are allowed.";
                } else {
                    $filename = uniqid() . '.' . $fileExt;
                    $destination_img = $uploadPath . $filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $destination_img)) {
                        $insert = insert_and_update_data('insert', 'banner', array(
                            'banner_type'=>$banner_type,
                            'banner_for'=> $banner_for,
                            'category'=> $category,
                            'image'=> $filename
                        ));
                        if($insert == true){
                            $error[] = 'Banner added successfully';
                        }
                    }
                }
            }
        }
    }
    
    // Fetch category options
    $brandQuery = selectQuery("SELECT * FROM brand");
    $brand = '';
    if ($brandQuery['status'] === true) {
        foreach ($brandQuery['data'] as $data) {
            $brand_id = $data['id'];
            $brand_name = $data['name'];
            $brand .= '<option value="' . $brand_id . '">' . $brand_name . '</option>';
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
                            aria-expanded="false" aria-controls="collapseExample">Add Banner</button>
                    <button class="btn btn-primary" data-bs-toggle="collapse" href="#searchCollapse" role="button"
                            aria-expanded="false" aria-controls="searchCollapse">Search</button>
                </div>
            </div>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($error as $err): ?>
                        <p><?php echo $err; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Add Banner Collapse -->
            <div class="collapse" id="collapseExample">
                <div class="card">
                    <div class="card-body">
                        <form action="" method="post" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="" class="font-weight-bold">Banner type</label>
                                    <select name="banner_type" id="" class="form-control">
                                        <option value="">Please Select</option>
                                        <option value="Home Banner 1">Home Banner 1</option>
                                        <option value="Home Banner 2">Home Banner 2</option>
                                        <option value="Home Banner 3">Home Banner 3</option>
                                        <option value="Home Banner 4">Home Banner 4</option>
                                        <option value="App Banner">📱 App Banner (1080×1080px)</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="" class="font-weight-bold">Banner For</label>
                                    <select name="banner_for" id="" class="form-control">
                                        <option value="">Please Select</option>
                                        <option value="For Desktop">For Desktop</option>
                                        <option value="For Mobile">For Mobile</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="" class="font-weight-bold">Brand</label>
                                    <select name="category" id="" class="form-control">
                                        <option value="">Please Select</option>
                                        <?php echo $brand?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="" class="font-weight-bold">Banner Images</label>
                                    <input type="file" id="imgInp" name="image" class="form-control bg-white">
                                    <img id="blah" width="10%" class="mt-3">
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
                                    <input type="text" name="search" class="form-control" placeholder="Search by Banner Type" value="<?= htmlspecialchars($searchText) ?>">
                                </div>
                                <div class="col-md-12 text-end">
                                    <button type="submit" class="btn btn-primary mt-2">Search</button>
                                    <?php if(!empty($searchText)): ?>
                                        <a href="banner.php" class="btn btn-secondary mt-2">Clear</a>
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
                    <h4 class="card-title">All Banner</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Banner type</th>
                                    <th>Banner For</th>
                                    <th>Brand</th>
                                    <th>Banner Images</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php

                                    // Modify the query to include search functionality
                                    $query = "SELECT * FROM banner";
                                    if (!empty($searchText)) {
                                        $query .= " WHERE banner_type LIKE '%" . $searchText . "%'";
                                    }
                                    $query .= " order by id desc";

                                    $bannerQuery = selectQuery($query,

                                    [
                                        'dataPerPage'		=>	'20',
                                        'searchVariable'	=>	'search=' . $searchText . '&',
                                        'getVariable'		=>	'page',
                                        'PageUrl'			=>	'banner.php',
                                    ]);

                                    if($bannerQuery['status'] == true){
                                        foreach($bannerQuery['data'] as $sr => $data){
                                            $bannerId = $data['id'];
                                            $banner_type = $data['banner_type'];
                                            $banner_for = $data['banner_for'];
                                            $image = $data['image'];
                                            $category = $data['category'];
                                            
                                            $brandQuery = selectQuery("SELECT * FROM brand WHERE id = '$category'");
                                            $brand_name = $brandQuery['data'][0]['name'];
                                                                            
                                        echo '<tr>
                                                <th>'.$banner_type.'</th>
                                                <th>'.$banner_for.'</th>
                                                <th>'.$brand_name.'</th>
                                                <td>
                                                    <img src="images/banner/'.$image.'" height="40px" class="rounded">
                                                </td>
                                                <td class="d-flex">
                                                    <a href="edit_banner.php?id='.$bannerId.'" class="btn btn-primary btn-sm me-1"><i class="fa fa-pencil"></i></a>
                                                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                                        data-id="'.$bannerId.'" data-table="banner" data-column="id">
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
                                echo $bannerQuery['pagination'];
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Preview single image (for main image)
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
</script>
<?php
  include 'include/footer.php';
?>