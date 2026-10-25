<?php
    require 'include/head.php';
    session('WEBSITE-LOGIN');	
    
    // Handle search functionality
    $searchText = isset($_GET['search']) ? cleanStr($_GET['search']) : '';
    
    if(isset($_POST['save'])){

        $name		  = cleanStr($_POST['name']);
    
        $insert = insert_and_update_data('insert', 'size', array(
            'name'=> $name,
        ));
        if($insert == true){
            $error[] = '';
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
                            aria-expanded="false" aria-controls="collapseExample">Add Size</button>
                    <button class="btn btn-primary" data-bs-toggle="collapse" href="#searchCollapse" role="button"
                            aria-expanded="false" aria-controls="searchCollapse">Search</button>
                </div>
            </div>
            <div class="collapse" id="collapseExample">
                <div class="card">
                    <div class="card-body">
                        <form action="" method="post">
                            <div class="row">
                                <div class="col-md-12">
                                    <label for="" class="font-weight-bold">Size Name</label>
                                    <input type="text" class="form-control" name="name" placeholder="Enter Name">
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
                                    <input type="text" name="search" class="form-control" placeholder="Search by Size Name" value="<?= htmlspecialchars($searchText) ?>">
                                </div>
                                <div class="col-md-12 text-end">
                                    <button type="submit" class="btn btn-primary mt-2">Search</button>
                                    <?php if(!empty($searchText)): ?>
                                        <a href="add_size.php" class="btn btn-secondary mt-2">Clear</a>
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
                    <h4 class="card-title">All Size</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Size Name</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    
                                    // Modify the query to include search functionality
                                    $query = "SELECT * FROM size";
                                    if (!empty($searchText)) {
                                        $query .= " WHERE name LIKE '%" . $searchText . "%'";
                                    }
                                    $query .= " order by id desc";

                                    $sizeQuery = selectQuery($query,

                                    [
                                        'dataPerPage'		=>	'20',
                                        'searchVariable'	=>	'search=' . $searchText . '&',
                                        'getVariable'		=>	'page',
                                        'PageUrl'			=>	'add_size.php',
                                    ]);

                                    if($sizeQuery['status'] == true){
                                        foreach($sizeQuery['data'] as $sr => $data){
                                            $sizeId = $data['id'];
                                            $name = $data['name'];
                                                                            
                                        echo '<tr>
                                                <th>'.$name.'</th>
                                                <td class="d-flex">
                                                    <a href="edit_size.php?id='.$sizeId.'" class="btn btn-primary btn-sm me-1"><i class="fa fa-pencil"></i></a>
                                                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                                        data-id="'.$sizeId.'" data-table="size" data-column="id">
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
                                echo $sizeQuery['pagination'];
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