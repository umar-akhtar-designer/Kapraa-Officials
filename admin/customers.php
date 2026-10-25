<?php
    require 'include/head.php';
    session('WEBSITE-LOGIN');
    
    // Handle search functionality
    $searchText = isset($_GET['search']) ? cleanStr($_GET['search']) : '';

    if (isset($_POST['save'])) {
        $name = cleanStr($_POST['name']);
        $email = cleanStr($_POST['email']);
        $number = cleanStr($_POST['number']);
        $username = cleanStr($_POST['username']);
        $password = cleanStr($_POST['password']);
        
        // Image upload handling
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'images/customers/';
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = $_FILES['image']['name'];
            $fileTmp = $_FILES['image']['tmp_name'];
            $fileSize = $_FILES['image']['size'];
            $fileError = $_FILES['image']['error'];
            
            // Get file extension
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Allowed file types
            $allowedExt = array('jpg', 'jpeg', 'png', 'gif', 'webp');
            
            // Check if file type is allowed
            if (in_array($fileExt, $allowedExt)) {
                // Check for upload errors
                if ($fileError === 0) {
                    // Check file size (max 5MB)
                    if ($fileSize <= 5000000) {
                        // Generate unique filename
                        $newFileName = uniqid('customer_', true) . '.' . $fileExt;
                        $uploadPath = $uploadDir . $newFileName;
                        
                        // Move uploaded file
                        if (move_uploaded_file($fileTmp, $uploadPath)) {
                            $image = $uploadPath;
                        } else {
                            $error[] = 'Failed to upload image. Please try again.';
                        }
                    } else {
                        $error[] = 'Image size is too large. Maximum size is 5MB.';
                    }
                } else {
                    $error[] = 'Error uploading image. Please try again.';
                }
            } else {
                $error[] = 'Invalid file type. Only JPG, JPEG, PNG, GIF, and WEBP are allowed.';
            }
        }
        
        if (!empty($password)) {
            $password = md5($password);
        }

        // Check if username already exists
        $checkUsername = selectQuery("SELECT id FROM customers WHERE username = '$username' LIMIT 1");
        if ($checkUsername['status'] === true && count($checkUsername['data']) > 0) {
            $error[] = "Username is already taken. Please choose another one.";
        } else {
            // Insert if username does not exist
            $insert = insert_and_update_data('insert', 'customers', array(
                'name' => $name,
                'email' => $email,
                'number' => $number,
                'username' => $username,
                'password' => $password,
                'image' => $image
            ));

            if ($insert == true) {
                $error[] = 'Customer added successfully.';
            } else {
                $error[] = 'Failed to add Customer. Please try again.';
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
                            aria-expanded="false" aria-controls="collapseExample">Add Customers</button>
                    <button class="btn btn-primary" data-bs-toggle="collapse" href="#searchCollapse" role="button"
                            aria-expanded="false" aria-controls="searchCollapse">Search</button>
                </div>
            </div>
            <div class="collapse" id="collapseExample">
                <div class="card">
                    <div class="card-body">
                        <form action="" method="post" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="" class="font-weight-bold">Customers Name</label>
                                    <input type="text" class="form-control" name="name" placeholder="Enter Name" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="" class="font-weight-bold">Customers Email</label>
                                    <input type="email" class="form-control" name="email" placeholder="Enter Email" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="" class="font-weight-bold">Customers Number</label>
                                    <input type="text" class="form-control" name="number" placeholder="Enter Number" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="" class="font-weight-bold">Customers Image</label>
                                    <input type="file" class="form-control" name="image" accept="image/*">
                                    <small class="text-muted">Allowed formats: JPG, JPEG, PNG, GIF, WEBP (Max: 5MB)</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="">Username</label>
                                    <input type="text" class="form-control" name="username" placeholder="Enter Username" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="" class="font-weight-bold">Password</label>
                                    <input type="password" class="form-control" name="password" placeholder="Enter Password" required>
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
                                    <input type="text" name="search" class="form-control" placeholder="Search by Customer Name" value="<?= htmlspecialchars($searchText) ?>">
                                </div>
                                <div class="col-md-12 text-end">
                                    <button type="submit" class="btn btn-primary mt-2">Search</button>
                                    <?php if(!empty($searchText)): ?>
                                        <a href="customers.php" class="btn btn-secondary mt-2">Clear</a>
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
                    <h4 class="card-title">All Customers</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Customer Name</th>
                                    <th>Contact info</th>
                                    <th>Total Order</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php

                                    // Modify the query to include search functionality
                                    $query = "SELECT * FROM customers";
                                    if (!empty($searchText)) {
                                        $query .= " WHERE name LIKE '%" . $searchText . "%'";
                                    }
                                    $query .= " order by id desc";

                                    $customersQuery = selectQuery($query,

                                    [
                                        'dataPerPage'		=>	'20',
                                        'searchVariable'	=>	'search=' . $searchText . '&',
                                        'getVariable'		=>	'page',
                                        'PageUrl'			=>	'customers.php',
                                    ]);

                                    if($customersQuery['status'] == true){
                                        foreach($customersQuery['data'] as $sr => $data){
                                            $customersId = $data['id'];
                                            $name = $data['name'];
                                            $email = $data['email'];
                                            $number = $data['number'];
                                            $username = $data['username'];
                                            $image = $data['image'];
                                            
                                            // Display image or placeholder
                                            $displayImage = '';
                                            if (!empty($image) && file_exists($image)) {
                                                $displayImage = '<img src="'.$image.'" alt="'.$name.'" class="rounded-circle" width="50" height="50">';
                                            } else {
                                                $displayImage = '<img src="images/placeholder.jpg" alt="No Image" class="rounded-circle" width="50" height="50">';
                                            }
                                            
                                            echo '<tr>
                                                    <td class="text-center">'.$displayImage.'</td>
                                                    <td>'.$name.'</td>
                                                    <td>
                                                       <p class="mb-0">'.$email.'</p>
                                                       <p class="mt-0">'.$number.'</p>
                                                    </td>
                                                    <td></td>
                                                    <td class="d-flex">
                                                        <a href="edit_customers.php?id='.$customersId.'" class="btn btn-primary btn-sm me-1"><i class="fa fa-pencil"></i></a>
                                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                                            data-id="'.$customersId.'" data-table="customers" data-column="id">
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
                                echo $customersQuery['pagination'];
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
    include 'include/footer.php';
?>