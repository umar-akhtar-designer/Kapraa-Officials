<?php
    $blogQuery = selectQuery("SELECT * FROM blog order by id desc");

    if ($blogQuery['status'] == true) {
        foreach ($blogQuery['data'] as $sr => $data) {
            $postId      = $data['id'];
            $title       = $data['title'];
            $description = $data['description'];
            $image       = $data['image'];
            $date        = $data['date'];
            $slug        = $data['slug'];
            $seotitle    = $data['seotitle'];
            $seodes      = $data['seodes'];
            $tags        = $data['tags'];

            // Format the date from database
            $day   = date('d', strtotime($date));   // Get day like "02"
            $month = date('M', strtotime($date));   // Get month like "Jul"

            echo '
                    <!-- Article Posts -->
                        <div class="col-12 col-sm-12 col-md-6 col-lg-6 article">
                            <!-- Article Image -->
                            <a href="blog/'.$slug.'" class="article_featured-image">
                                <img class="blur-up lazyload" src="admin/images/blogs/'.$image.'" alt="'.$title.'">
                            </a>
                            <!-- End Article Image -->
                            <!-- Blog Content -->
                            <h2 class="article__title h3 text-uppercase mb-2">
                               <a href="blog/'.$slug.'">'.$title.'</a>
                            </h2>
                            <ul class="publish-detail rte mb-2">
                                <li>
                                    <i class="icon an an-user me-1" aria-hidden="true"></i> Admin
                                </li>
                                <li>
                                    <i class="icon an an-clock me-1"></i> 
                                    '.$date.'
                                </li>
                            </ul>
                            <div class="rte mb-2 pb-1">
                                <p class="line-2">'.strip_tags(html_entity_decode($description)).'</p>
                            </div>
                            <div class="blog-action">
                                <a href="blog/'.$slug.'" class="btn btn-secondary btn--small">Read more</a>
                            </div>
                            <!-- End Blog Content -->
                        </div>
                    <!-- End Article Posts -->
                ';
        }
    }
?>