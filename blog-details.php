<?php
  include 'include/head.php';
  include 'include/navbar.php';
  $slug = cleanStr($_GET['slug']);

  // Fetch current blog data
  $blogQuery = selectQuery("SELECT * FROM blog WHERE slug ='$slug' ORDER BY id DESC");
  
  // Check if blog post exists
  if ($blogQuery['status'] == false || empty($blogQuery['data'])) {
      // Redirect to blog page if not found
      header("Location: /blog");
      exit();
  }
  
  $blogData = $blogQuery['data'][0];

  $title       = $blogData['title'];
  $description = $blogData['description'];
  $image       = $blogData['image'];
  $slug        = $blogData['slug'];
  $seotitle    = $blogData['seotitle'];
  $seodes      = $blogData['seodes'];
  $tags        = $blogData['tags'];
  $date        = $blogData['date'];

?>
<!-- Page Title -->
<div class="page section-header text-center mb-0">
    <div class="page-title">
        <div class="wrapper"><h1 class="page-width"><?php echo $title?></h1></div>
    </div>
</div>
<!-- End Page Title -->

<!-- Breadcrumbs -->
<div class="bredcrumbWrap bredcrumb-style2">
    <div class="container breadcrumbs">
        <a href="index" title="Back to the home page">Home</a><span aria-hidden="true">|</span><a href="blog">Blog</a><span aria-hidden="true">|</span><span class="title-bold"><?php echo $title?></span>
    </div>
</div>
<!-- End Breadcrumbs -->
<div class="container">
    <div class="row flex-column-reverse flex-md-row">
        <!-- Main Content -->
        <div class="col-12 col-sm-12 col-md-8 col-lg-9 main-col">
            <!-- Article Posts -->
            <div class="blog--list-view">
                <div class="article">
                    <!-- Article Image -->
                    <div class="article_featured-image">
                        <img class="blur-up lazyload" src="<?php echo $url_domain;?>/admin/images/blogs/<?php echo $image?>" alt="<?php echo $title?>" />
                    </div>
                    <!-- End Article Image -->
                    <!-- Article Info -->
                    <h1 class="h2 text-uppercase"><?php echo $title?></h1>
                    <ul class="publish-detail rte">                      
                        <li><i class="icon an an-user me-1" aria-hidden="true"></i>Admin</li>
                        <li><i class="icon an an-clock me-1"></i> <?php echo $date?></li>
                    </ul>
                    <div class="rte">
                        <?php echo html_entity_decode($description) ?>
                    </div>
                    <!-- End Article Info -->
                    <hr/>
                </div>
            </div>
            <!-- End Article Posts -->

            <!-- Return to Posts -->
            <div class="text-center return-link-wrapper my-5">
                <a href="<?php echo $url_domain;?>/blog" class="btn btn--has-icon-before return-link"><i class="icon an an-angle-left an-lg align-middle"></i><span class="align-middle">Back to Blogs</span></a>
            </div>
            <!-- End Return to Posts -->
        </div>
        <!-- End Main Content -->

        <!-- Sidebar -->
        <div class="col-12 col-sm-12 col-md-4 col-lg-3 blog-sidebar sidebar">
            <div class="sidebar_tags">
                <!-- Recent Posts -->
                <div class="sidebar_widget">
                    <div class="widget-title"><h2>Recent Posts</h2></div>
                    <div class="widget-content d-md-block">
                        <div class="list list-sidebar-products">
                            <div class="grid">
                                <?php
                                 $blogsQuery = selectQuery("SELECT * FROM blog  ORDER BY id DESC LIMIT 4");
                                    if ($blogsQuery['status'] == true) {
                                        foreach ($blogsQuery['data'] as $sr => $data) {
                                        $postId      = $data['id'];
                                        $title       = $data['title'];
                                        $description = $data['description'];
                                        $image       = $data['image'];
                                        $date        = $data['date'];
                                        $slug        = $data['slug'];
                                        $seotitle    = $data['seotitle'];
                                        $seodes      = $data['seodes'];
                                        $tags        = $data['tags'];
                                ?>
                                <div class="grid__item">
                                    <div class="mini-list-item d-flex">
                                        <div class="mini-view_image">
                                            <a class="grid-view-item__link" href="<?php echo $slug?>">
                                                <img class="grid-view-item__image blur-up lazyload" src="<?php echo $url_domain;?>/admin/images/blogs/<?php echo $image?>" alt="<?php echo $title?>" height="50px" />
                                            </a>
                                        </div>
                                        <div class="ms-3 details"> 
                                            <a class="grid-view-item__title" href="<?php echo $slug?>"><?php echo $title?></a>
                                            <div class="grid-view-item__meta">
                                                <span class="article__date"> 
                                                   <?php echo $date?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                    }
                                  }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Recent Posts -->
            </div>
        </div>
        <!-- End Sidebar -->
    </div>
</div>
<?php
  include 'include/footer.php';
?>