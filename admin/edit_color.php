<?php
    require 'include/head.php';
    session('WEBSITE-LOGIN');	
    
    $colorId = cleanStr($_GET['id']);
    
    // Fetch color data
    $colorQuery = selectQuery("SELECT * FROM color WHERE id ='$colorId'");
    if(!$colorQuery['status'] || empty($colorQuery['data'])) {
        header("Location: color_management.php");
        exit();
    }
    
    $colorData = $colorQuery['data'][0];
    $name = $colorData['name'];
    $color_code = $colorData['color_code'];
    $status = $colorData['status'];
    
    if(isset($_POST['update'])){
        $name = cleanStr($_POST['name']);
        $color_code = cleanStr($_POST['color_code']);
        $status = isset($_POST['status']) ? 1 : 0;
        
        // Validation
        if(empty($name)) {
            $error[] = "Color name is required";
        }
        
        if(!empty($color_code) && !preg_match('/^#[0-9A-F]{6}$/i', $color_code)) {
            $error[] = "Invalid color code format. Use hex format like #FF0000";
        }
        
        // Check if color name already exists (excluding current color)
        $existingColor = selectQuery("SELECT id FROM color WHERE name = '$name' AND id != '$colorId' LIMIT 1");
        if($existingColor['status'] && count($existingColor['data']) > 0) {
            $error[] = "Color name already exists";
        }
        
        // If no errors, update data
        if(empty($error)) {
            $updateData = [
                'name' => $name,
                'color_code' => $color_code,
                'status' => $status
            ];
            
            if(update_data_query('color', $updateData, "id = '$colorId'") == true) {
                $success = "Color updated successfully!";
                // Refresh data
                $colorQuery = selectQuery("SELECT * FROM color WHERE id ='$colorId'");
                $colorData = $colorQuery['data'][0];
                $name = $colorData['name'];
                $color_code = $colorData['color_code'];
                $status = $colorData['status'];
            } else {
                $error[] = "Failed to update color. Please try again.";
            }
        }
    }
?>
<div class="content-body">
    <div class="container-fluid">
        <!-- Success/Error Messages -->
        <?php if(!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0">
                    <?php foreach($error as $err): ?>
                        <li><?php echo $err; ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="col-md-12">
            <div class="row">
                <div class="col-md-6">
                    <h4 class="card-title mb-0">Edit Color</h4>
                </div>
                <div class="col-md-6 text-end mb-3">
                    <a href="add_color.php" class="btn btn-secondary">
                        <i class="fa fa-arrow-left me-2"></i>Back to Colors
                    </a>
                </div>
            </div>
            
            <!-- Edit Color Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Edit Color: <?php echo htmlspecialchars($name); ?></h5>
                </div>
                <div class="card-body">
                    <form action="" method="post" id="colorForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label font-weight-bold">Color Name *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       placeholder="Enter color name" value="<?php echo $name; ?>" 
                                       required maxlength="100">
                                <div class="form-text">Enter a unique color name</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="color_code" class="form-label font-weight-bold">Color Code</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" id="color_picker" 
                                           value="<?php echo $color_code ?: '#000000'; ?>" title="Choose color">
                                    <input type="text" class="form-control" id="color_code" name="color_code" 
                                           placeholder="#FF0000" value="<?php echo $color_code; ?>" 
                                           pattern="^#[0-9A-F]{6}$" maxlength="7">
                                </div>
                                <div class="form-text">Optional: Hex color code (e.g., #FF0000 for red)</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="status" name="status" 
                                           value="1" <?php echo $status ? 'checked' : ''; ?>>
                                    <label class="form-check-label font-weight-bold" for="status">
                                        Active Color
                                    </label>
                                </div>
                                <div class="form-text">Toggle to activate or deactivate this color</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Color Preview</h6>
                                        <div class="color-preview mx-auto" 
                                             style="background-color: <?php echo $color_code ?: '#CCCCCC'; ?>; 
                                                    width: 60px; height: 60px; border: 1px solid #ddd; 
                                                    border-radius: 8px;" 
                                             title="<?php echo $color_code ?: 'No color set'; ?>">
                                        </div>
                                        <p class="mt-2 mb-0 small">
                                            <?php echo $color_code ?: 'No color code set'; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12 text-end">
                                <a href="color_management.php" class="btn btn-secondary mt-3">Cancel</a>
                                <button type="submit" class="btn btn-primary mt-3" name="update">
                                    <i class="fa fa-save me-2"></i>Update Color
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Color Picker -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const colorPicker = document.getElementById('color_picker');
        const colorCodeInput = document.getElementById('color_code');
        const colorPreview = document.querySelector('.color-preview');
        
        // Sync color picker with text input
        colorPicker.addEventListener('input', function() {
            colorCodeInput.value = this.value.toUpperCase();
            updateColorPreview();
        });
        
        // Sync text input with color picker (with validation)
        colorCodeInput.addEventListener('input', function() {
            const color = this.value;
            if (/^#[0-9A-F]{6}$/i.test(color)) {
                colorPicker.value = color;
                updateColorPreview();
            }
        });
        
        // Update color preview
        function updateColorPreview() {
            const color = colorCodeInput.value;
            if (/^#[0-9A-F]{6}$/i.test(color)) {
                colorPreview.style.backgroundColor = color;
                colorPreview.title = color;
                // Update the text below preview
                const previewText = colorPreview.parentElement.querySelector('p');
                if (previewText) {
                    previewText.textContent = color;
                }
            }
        }
        
        // Form validation
        document.getElementById('colorForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const colorCode = document.getElementById('color_code').value.trim();
            
            if (!name) {
                e.preventDefault();
                alert('Color name is required');
                return;
            }
            
            if (colorCode && !/^#[0-9A-F]{6}$/i.test(colorCode)) {
                e.preventDefault();
                alert('Please enter a valid hex color code (e.g., #FF0000)');
                return;
            }
        });
    });
</script>

<?php
    include 'include/footer.php';
?>