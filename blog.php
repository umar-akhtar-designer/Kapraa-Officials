<?php
  include 'include/head.php';
  include 'include/navbar.php';
?>
 <!-- Page Title -->
<div class="page section-header text-center mb-0">
    <div class="page-title">
        <div class="wrapper">
            <h1 class="page-width">Our Blogs</h1>
        </div>
    </div>
</div>
<!-- End Page Title -->
<!-- Breadcrumbs -->
<div class="bredcrumbWrap bredcrumb-style2">
    <div class="container breadcrumbs">
        <a href="<?php echo $url_domain;?>/index" title="Back to the home page">Home</a><span aria-hidden="true">|</span><span
            class="title-bold">Our Blogs</span>
    </div>
</div>
<!-- End Breadcrumbs -->
<div class="container">
    <div class="row flex-column-reverse flex-md-row">
        <!-- Main Content -->
        <div class="col-12 col-sm-12 col-md-8 col-lg-9 main-col ">
            <!-- Grid Article Posts -->
            <div class="blog--list-view">
                <div class="row">
                    <?php include 'include/blog_card.php';?>
                </div>
            </div>
            <!-- End Grid Article Posts -->
        </div>
        <!-- End Main Content -->

        <!-- Sidebar -->
        <div class="col-12 col-sm-12 col-md-4 col-lg-3 blog-sidebar sidebar">
            <div class="sidebar_tags">
                <!-- Recent Posts -->
                <div class="sidebar_widget">
                    <div class="widget-title">
                        <h2>Recent Posts</h2>
                    </div>
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
                                            <a class="grid-view-item__link" href="blog/<?php echo $slug?>">
                                                <img class="grid-view-item__image blur-up lazyload" src="admin/images/blogs/<?php echo $image?>" alt="<?php echo $title?>" height="50px" />
                                            </a>
                                        </div>
                                        <div class="ms-3 details"> 
                                            <a class="grid-view-item__title" href="blog/<?php echo $slug?>"><?php echo $title?></a>
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