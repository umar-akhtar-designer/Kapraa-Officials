<?php
    include 'include/head.php';
    include 'include/navbar.php';

    // Initialize error/success messages
    $message = '';
    $messageType = ''; // 'success' or 'error'

    if (isset($_POST['save'])) {
        $name = cleanStr($_POST['name']);
        $email = cleanStr($_POST['email']);
        $number = cleanStr($_POST['number']);
        $username = cleanStr($_POST['username']);
        $password = cleanStr($_POST['password']);
        
        // Image upload handling
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'admin/images/customers/';
            $dbImagePath = 'images/customers/';
            
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
                            // Store the path for database without 'admin/' prefix
                            $image = $dbImagePath . $newFileName;
                        } else {
                            $message = 'Failed to upload image. Please try again.';
                            $messageType = 'error';
                        }
                    } else {
                        $message = 'Image size is too large. Maximum size is 5MB.';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Error uploading image. Please try again.';
                    $messageType = 'error';
                }
            } else {
                $message = 'Invalid file type. Only JPG, JPEG, PNG, GIF, and WEBP are allowed.';
                $messageType = 'error';
            }
        }
        
        // Only proceed if no image upload errors
        if ($messageType !== 'error') {
            if (!empty($password)) {
                // bcrypt — secure, salted, industry standard (replaces MD5)
                $password = password_hash($password, PASSWORD_BCRYPT);
            }

            // Check if username already exists
            $checkUser = selectQuery("SELECT id FROM customers WHERE email = '$email' LIMIT 1");
            if ($checkUser['status'] === true && count($checkUser['data']) > 0) {
                $message = "Email is already taken. Please choose another one.";
                $messageType = 'error';
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
                    $message = 'You Account registered successfully ! You can now login.';
                    $messageType = 'success';
                    
                    // Clear form fields after successful registration
                    $_POST = array();
                } else {
                    $message = 'Failed to register. Please try again.';
                    $messageType = 'error';
                }
            }
        }
    }
?>
<!-- Page Title -->
<div class="page section-header text-center mb-0">
    <div class="page-title">
        <div class="wrapper"><h1 class="page-width">Register</h1></div>
    </div>
</div>
<!-- End Page Title -->
<!-- Breadcrumbs -->
<div class="bredcrumbWrap bredcrumbWrapPage bredcrumb-style2 text-center">
    <div class="container breadcrumbs">
        <a href="<?php echo $url_domain;?>/index" title="Back to the home page">Home</a><span aria-hidden="true">|</span><span class="title-bold">Register</span>
    </div>
</div>
<!-- End Breadcrumbs -->

<div class="container">
    <div class="row">
        <div class="col-12 col-sm-12 col-md-12 col-lg-12 box">	
            <div class="mb-4">
                <!-- Display Messages -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <h3>Personal Information</h3>
                <form method="post" class="customer-form" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <label for="Name">Name <span class="required">*</span></label>
                                <input type="text" name="name" placeholder="Name"  required />
                            </div>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <label for="CustomerEmail">Email Address <span class="required">*</span></label>
                                <input type="email" name="email" placeholder="Email Address"  required />                        	
                            </div>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <label for="Number">Number</label>
                                <input type="text" name="number" placeholder="Number" />                        	
                            </div>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-6">
                            <div class="form-group">
                                <label for="Username">Username <span class="required">*</span></label>
                                <input type="text" name="username" placeholder="Username"  required />                        	
                            </div>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-6">                                	
                            <div class="form-group">
                                <label for="Password">Password <span class="required">*</span></label>
                                <input type="password" name="password" required />
                            </div>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-6">                                	
                            <div class="form-group">
                                <label for="image">Image</label>
                                <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" />
                                <small class="form-text text-muted">Allowed formats: JPG, JPEG, PNG, GIF, WEBP (Max: 5MB)</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="text-left col-12 col-sm-12 col-md-6 col-lg-6">
                            <input type="submit" class="btn mb-3" value="Register" name="save" />
                        </div>
                        <div class="text-right col-12 col-sm-12 col-md-6 col-lg-6">
                            <a href="login"><i class="icon an an-angle-double-left me-1"></i>Back To Login</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
    include 'include/footer.php';
?>