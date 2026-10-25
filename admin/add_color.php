<?php
    require 'include/head.php';
    session('WEBSITE-LOGIN');
    
    // Handle search functionality
    $searchText = isset($_GET['search']) ? cleanStr($_GET['search']) : '';
  
    // Handle form submission
    if(isset($_POST['save'])){
        $name = cleanStr($_POST['name']);
        $color_code = cleanStr($_POST['color_code']);
        
        // Validation
        if(empty($name)) {
            $error[] = "Color name is required";
        }
        
        if(!empty($color_code) && !preg_match('/^#[0-9A-F]{6}$/i', $color_code)) {
            $error[] = "Invalid color code format. Use hex format like #FF0000";
        }
        
        // Check if color already exists
        $existingColor = selectQuery("SELECT id FROM color WHERE name = '$name' LIMIT 1");
        if($existingColor['status'] && count($existingColor['data']) > 0) {
            $error[] = "Color name already exists";
        }
        
        // If no errors, insert data
        if(empty($error)) {
            $insertData = [
                'name' => $name,
                'color_code' => $color_code
            ];
            
            $insert = insert_and_update_data('insert', 'color', $insertData);
            
            if($insert == true){
                $success = "Color added successfully!";
                // Clear form fields
                $_POST = [];
            } else {
                $error[] = "Failed to add color. Please try again.";
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
                    <h4 class="card-title mb-0">Color Management</h4>
                </div>
                <div class="col-md-6 text-end mb-3">
                    <button class="btn btn-primary me-2" data-bs-toggle="collapse" href="#collapseExample" role="button"
                            aria-expanded="false" aria-controls="collapseExample">
                        <i class="fa fa-plus me-2"></i>Add Color
                    </button>
                    <button class="btn btn-primary" data-bs-toggle="collapse" href="#searchCollapse" role="button"
                            aria-expanded="false" aria-controls="searchCollapse">
                        <i class="fa fa-search me-2"></i>Search
                    </button>
                </div>
            </div>
            
            <!-- Add Color Form -->
            <div class="collapse" id="collapseExample">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Add New Color</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="post" id="colorForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label font-weight-bold">Color Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           placeholder="Enter color name" value="<?php echo $_POST['name'] ?? ''; ?>" 
                                           required maxlength="100">
                                    <div class="form-text">Enter a unique color name</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="color_code" class="form-label font-weight-bold">Color Code</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="color_picker" 
                                               value="<?php echo $_POST['color_code'] ?? '#000000'; ?>" title="Choose color">
                                        <input type="text" class="form-control" id="color_code" name="color_code" 
                                               placeholder="#FF0000" value="<?php echo $_POST['color_code'] ?? ''; ?>" 
                                               pattern="^#[0-9A-F]{6}$" maxlength="7">
                                    </div>
                                    <div class="form-text">Optional: Hex color code (e.g., #FF0000 for red)</div>
                                </div>
                                <div class="col-md-12 text-end">
                                    <button type="reset" class="btn btn-secondary mt-3">Reset</button>
                                    <button type="submit" class="btn btn-primary mt-3" name="save">
                                        <i class="fa fa-save me-2"></i>Save Color
                                    </button>
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
                                    <input type="text" name="search" class="form-control" placeholder="Search by Color Name" value="<?= htmlspecialchars($searchText) ?>">
                                </div>
                                <div class="col-md-12 text-end">
                                    <button type="submit" class="btn btn-primary mt-2">Search</button>
                                    <?php if(!empty($searchText)): ?>
                                        <a href="add_color.php" class="btn btn-secondary mt-2">Clear</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Colors List -->
        <div class="col-lg-12 mt-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">All Colors</h4>
                </div>
                <div class="card-body">
                    <?php
                        // Modify the query to include search functionality
                        $query = "SELECT * FROM color";
                        if (!empty($searchText)) {
                            $query .= " WHERE name LIKE '%" . $searchText . "%'";
                        }
                        $query .= " ORDER BY id DESC";
                        
                        $colorQuery = selectQuery($query,

                        [
                            'dataPerPage'		=>	'20',
                            'searchVariable'	=>	'search=' . $searchText . '&',
                            'getVariable'		=>	'page',
                            'PageUrl'			=>	'add_color.php',
                        ]);

                        if(!$colorQuery['status'] || empty($colorQuery['data'])):
                    ?>
                        <div class="text-center py-4">
                            <i class="fa fa-palette fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No colors found. Add your first color above.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="60">#</th>
                                        <th>Color Name</th>
                                        <th>Color Code</th>
                                        <th>Preview</th>
                                        <th width="120">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach($colorQuery['data'] as $sr => $data){
                                        $colorId = $data['id'];
                                        $name = htmlspecialchars($data['name']);
                                        $colorCode = $data['color_code'] ?: '#CCCCCC';
                                        $status = $data['status'];
                                    ?>
                                    <tr>
                                        <td><?php echo $sr + 1; ?></td>
                                        <td>
                                            <strong><?php echo $name; ?></strong>
                                            <?php if(!$status): ?>
                                                <span class="badge bg-warning ms-2">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($data['color_code']): ?>
                                                <code><?php echo $colorCode; ?></code>
                                            <?php else: ?>
                                                <span class="text-muted">Not set</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="color-preview" 
                                                 style="background-color: <?php echo $colorCode; ?>; 
                                                        width: 30px; height: 30px; border: 1px solid #ddd; 
                                                        border-radius: 4px;" 
                                                 title="<?php echo $colorCode; ?>">
                                            </div>
                                        </td>
                                        <td class="d-flex">
                                            <a href="edit_color.php?id=<?php echo $colorId; ?>" 
                                               class="btn btn-primary btn-sm me-1" 
                                               title="Edit Color">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                            <button class="btn btn-danger btn-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal" 
                                                    data-id="<?php echo $colorId; ?>" 
                                                    data-table="color" 
                                                    data-column="id"
                                                    title="Delete Color">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    <div class="row py-3">
                        <div class="col-md-12 text-right">
                            <?php
                                echo $colorQuery['pagination'];
                            ?>
                        </div>
                    </div>
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
        
        // Sync color picker with text input
        colorPicker.addEventListener('input', function() {
            colorCodeInput.value = this.value.toUpperCase();
        });
        
        // Sync text input with color picker (with validation)
        colorCodeInput.addEventListener('input', function() {
            const color = this.value;
            if (/^#[0-9A-F]{6}$/i.test(color)) {
                colorPicker.value = color;
            }
        });
        
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