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
                <div class="col-md-6"></div>
                <div class="col-md-6 text-end">
                    <a href="add_blog.php" class="btn btn-primary me-2">ADD BLOG</a>
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
                                    <input type="text" name="search" class="form-control" placeholder="Search by Blog Title" value="<?= htmlspecialchars($searchText) ?>">
                                </div>
                                <div class="col-md-12 text-end">
                                    <button type="submit" class="btn btn-primary mt-2">Search</button>
                                    <?php if(!empty($searchText)): ?>
                                        <a href="blog.php" class="btn btn-secondary mt-2">Clear</a>
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
                    <h4 class="card-title">Our Blogs</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Slug</th>
                                    <th>Images</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                              <?php
                                    // Modify the query to include search functionality
                                    $query = "SELECT * FROM blog";
                                    if (!empty($searchText)) {
                                        $query .= " WHERE title LIKE '%" . $searchText . "%'";
                                    }
                                    $query .= " order by id desc";
                                    
                                    $blogQuery = selectQuery($query,

                                    [
                                        'dataPerPage'		=>	'20',
                                        'searchVariable'	=>	'search=' . $searchText . '&',
                                        'getVariable'		=>	'page',
                                        'PageUrl'			=>	'blog.php',
                                    ]);

                                    if($blogQuery['status'] == true){
                                        foreach($blogQuery['data'] as $sr => $data){
                                            $blogId = $data['id'];
                                            $title = $data['title'];
                                            $slug = $data['slug'];
                                            $image = $data['image'];
                                            $date = $data['date'];
                                                                            
                                        echo '<tr>
                                                <th>'.$title.'</th>
                                                <td>'.$slug.'</td>
                                                <td>
                                                    <img src="images/blogs/'.$image.'" height="40px" class="rounded">
                                                </td>
                                                <td>'.$date.'</td>
                                                <td class="d-flex">
                                                    <a href="edit_blog.php?id='.$blogId.'" class="btn btn-primary btn-sm me-1"><i class="fa fa-pencil"></i></a>
                                                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                                        data-id="'.$blogId.'" data-table="blog" data-column="id">
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
                                echo $blogQuery['pagination'];
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