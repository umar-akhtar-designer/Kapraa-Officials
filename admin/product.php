<?php
    require 'include/head.php';
    session('WEBSITE-LOGIN');	
    
    // Handle search functionality
    $searchText = isset($_GET['search']) ? cleanStr($_GET['search']) : '';
?>
<div class="content-body">
    <div class="container-fluid">
        <div class="col-md-12">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h4 class="card-title mb-0">All Products</h4>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-primary" data-bs-toggle="collapse" href="#searchCollapse" role="button"
                            aria-expanded="false" aria-controls="searchCollapse">
                        <i class="fa fa-search me-2"></i>Search
                    </button>
                </div>
            </div>
            
            <!-- Search Collapse -->
            <div class="collapse mb-3" id="searchCollapse">
                <div class="card">
                    <div class="card-body">
                        <form action="" method="get">
                            <div class="row">
                                <div class="col-md-12">
                                    <input type="text" name="search" class="form-control" placeholder="Search by Product Name" value="<?= htmlspecialchars($searchText) ?>">
                                </div>
                                <div class="col-md-12 text-end">
                                    <button type="submit" class="btn btn-primary mt-2">Search</button>
                                    <?php if(!empty($searchText)): ?>
                                        <a href="product.php" class="btn btn-secondary mt-2">Clear</a>
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
                    <h4 class="card-title">All Products</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Product Type</th>
                                    <th>Unit Price</th>
                                    <th>Discount Amount</th>
                                    <th>Price After Discount</th>
                                    <th>Product Images</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                             <tbody>
                                 <?php
                                    // Modify the query to include search functionality
                                    $query = "SELECT * FROM products";
                                    if (!empty($searchText)) {
                                        $query .= " WHERE name LIKE '%" . $searchText . "%'";
                                    }
                                    $query .= " ORDER BY id DESC";

                                    $productQuery = selectQuery($query,

                                    [
                                        'dataPerPage'		=>	'50',
                                        'searchVariable'	=>	'search=' . $searchText . '&',
                                        'getVariable'		=>	'page',
                                        'PageUrl'			=>	'product.php',
                                    ]);

                                    if ($productQuery['status'] == true) {
                                        foreach ($productQuery['data'] as $sr => $data) {
                                    
                                            $productId     = $data['id'];
                                            $name          = $data['name'];
                                            $product_type  = $data['product_type'];
                                            $price         = $data['price'];
                                            $dis_amount    = $data['dis_amount'];
                                            $price_after_discount  = $data['price_after_discount'];
                                            $imageString   = $data['image']; // multiple images comma-separated
                                            
                                            // Convert images into array
                                            $images = explode(",", $imageString);
                                    
                                            echo '<tr>
                                                    <th>' . $name . '</th>
                                                    <th>' . $product_type . '</th>
                                                    <th>' . $price . '</th>
                                                    <th>' . $dis_amount . '</th>
                                                    <th>' . $price_after_discount . '</th>
                                                    <td>';
                                    
                                                    // Show all images
                                                    foreach ($images as $img) {
                                                        echo '<img src="images/product/' . trim($img) . '" height="40px" class="rounded me-1">';
                                                    }
                                    
                                            echo    '</td>
                                                    <td class="d-flex">
                                                        <a href="edit_products.php?id=' . $productId . '" class="btn btn-primary btn-sm me-1">
                                                            <i class="fa fa-pencil"></i>
                                                        </a>
                                    
                                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" 
                                                            data-bs-target="#deleteModal" 
                                                            data-id="' . $productId . '" 
                                                            data-table="products" 
                                                            data-column="id">
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
                                echo $productQuery['pagination'];
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