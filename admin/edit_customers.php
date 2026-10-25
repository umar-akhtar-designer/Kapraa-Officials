<?php
    require 'include/head.php';
    session('WEBSITE-LOGIN');	
    
    $customersId = cleanStr($_GET['id']);
    
    // Fetch current customer data
    $customerQuery = selectQuery("SELECT * FROM customers WHERE id ='$customersId' ORDER BY id DESC");
    $customerData = $customerQuery['data'][0];
    
    $name       = $customerData['name'];
    $email      = $customerData['email'];
    $number     = $customerData['number'];
    $username   = $customerData['username'];
    $password	= $customerData['password'];
    $image      = $customerData['image'];
    
    if (isset($_POST['update'])) {
        
        $name = cleanStr($_POST['name']);
        $email = cleanStr($_POST['email']);
        $number = cleanStr($_POST['number']);
        $username = cleanStr($_POST['username']);
        $newPassword = cleanStr($_POST['password']);
        
        // Image handling - keep current image if no new image uploaded
        $currentImage = $image;
        $newImage = $currentImage;
        
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
                            $newImage = $uploadPath;
                            
                            // Delete old image if it exists and is different from new one
                            if (!empty($currentImage) && $currentImage !== $newImage && file_exists($currentImage)) {
                                unlink($currentImage);
                            }
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
        
        // Password handling - update only if new password is provided
        $finalPassword = $password; // Keep current password by default
        
        if (!empty($newPassword)) {
            $finalPassword = md5($newPassword);
        }
    
        // Check if username already exists (excluding current customer)
        $checkUsername = selectQuery("SELECT id FROM customers WHERE username = '$username' AND id != '$customersId' LIMIT 1");
        if ($checkUsername['status'] === true && count($checkUsername['data']) > 0) {
            $error[] = "Username is already taken. Please choose another one.";
        } else {
            // Update customer data
            $update = insert_and_update_data('update', 'customers', array(
                'name' => $name,
                'email' => $email,
                'number' => $number,
                'username' => $username,
                'password' => $finalPassword,
                'image' => $newImage
            ), "id = '$customersId'");
    
            if ($update == true) {
                $success[] = 'Customer updated successfully.';
                // Refresh data after update
                $customerQuery = selectQuery("SELECT * FROM customers WHERE id ='$customersId' ORDER BY id DESC");
                $customerData = $customerQuery['data'][0];
                $name = $customerData['name'];
                $email = $customerData['email'];
                $number = $customerData['number'];
                $username = $customerData['username'];
                $image = $customerData['image'];
            } else {
                $error[] = 'Failed to update Customer. Please try again.';
            }
        }
    }
?>

<div class="content-body">
    <div class="container-fluid">
        <!-- Display Messages -->
        <?php if (isset($error)): ?>
            <?php foreach ($error as $err): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo $err; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <?php foreach ($success as $msg): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Edit Customer</h4>
                </div>
                <div class="card-body">
                  
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="" class="font-weight-bold">Customers Name</label>
                                <input type="text" class="form-control" name="name" value="<?php echo $name; ?>" placeholder="Enter Name" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="" class="font-weight-bold">Customers Email</label>
                                <input type="email" class="form-control" name="email" value="<?php echo $email; ?>" placeholder="Enter Email" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="" class="font-weight-bold">Customers Number</label>
                                <input type="text" class="form-control" name="number" value="<?php echo $number; ?>" placeholder="Enter Number" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="" class="font-weight-bold">Customers Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <small class="text-muted">Leave empty to keep current image. Allowed formats: JPG, JPEG, PNG, GIF, WEBP (Max: 5MB)</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="">Username</label>
                                <input type="text" class="form-control" name="username" value="<?php echo $username; ?>" placeholder="Enter Username" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="" class="font-weight-bold">Password</label>
                                <input type="password" class="form-control" name="password" placeholder="Enter New Password">
                                <small class="text-muted">Leave empty to keep current password</small>
                            </div>
                             <!-- Display Current Image -->
                            <?php if (!empty($image) && file_exists($image)): ?>
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <label class="font-weight-bold">Current Image:</label>
                                        <div class="mt-2">
                                            <img src="<?php echo $image; ?>" alt="<?php echo $name; ?>" class="img-thumbnail" width="150" height="150">
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="col-md-12 text-end">
                                <a href="customers.php" class="btn btn-secondary mt-3">Cancel</a>
                                <button class="btn btn-primary mt-3" name="update">Update Customer</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
    include 'include/footer.php';
?>