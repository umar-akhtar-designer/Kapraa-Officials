<?php
  	$blogQuery = selectQuery("SELECT count(id) as today FROM blog");
    $Totalblog = number_format($blogQuery['data'][0]['today']);
    
    $teamQuery = selectQuery("SELECT count(id) as today FROM team");
    $Totalteam = number_format($teamQuery['data'][0]['today']);
    
    $contactQuery = selectQuery("SELECT count(id) as today FROM contact");
    $Totalcontact = number_format($contactQuery['data'][0]['today']);
    
    $brandQuery = selectQuery("SELECT count(id) as today FROM brand");
    $Totalbrand = number_format($brandQuery['data'][0]['today']);
    
    $customerQuery = selectQuery("SELECT count(id) as today FROM customers");
    $Totalcustomer = number_format($customerQuery['data'][0]['today']);
    
    $productQuery = selectQuery("SELECT count(id) as today FROM products");
    $Totalproduct = number_format($productQuery['data'][0]['today']);

    // Add this function to your functions.php or a separate helper file
    function getOrderStatusCounts() {
        global $connection_database;
        
        $query = "SELECT order_status, COUNT(*) as count FROM orders GROUP BY order_status";
        $result = mysqli_query($connection_database, $query);
        
        // Initialize counts
        $counts = [
            'all' => 0,
            'pending' => 0,
            'confirmed' => 0,
            'packaging' => 0,
            'out of delivery' => 0,
            'return' => 0,
            'failed deliver' => 0,
            'cancel' => 0
        ];
        
        // Process results
        while ($row = mysqli_fetch_assoc($result)) {
            $status = $row['order_status'];
            $count = $row['count'];
            
            // Add to total
            $counts['all'] += $count;
            
            // Update specific status count
            if (isset($counts[$status])) {
                $counts[$status] = $count;
            }
        }
        
        return $counts;
    }

?>


<!--**********************************
    Nav header start
***********************************-->
<div class="nav-header">
    <a href="dashboard.php" class="brand-logo d-flex align-items-center">
		<img src="images/Kapraa-logo.png" alt="Kapraa" class="logo-abbr" width="90px">
	</a>
    <div class="nav-control">
        <div class="hamburger">
            <span class="line"></span><span class="line"></span><span class="line"></span>
        </div>
    </div>
</div>
<!--**********************************
    Nav header end
***********************************-->
<!--**********************************
    Header start
***********************************-->
<div class="header">
    <div class="header-content">
        <nav class="navbar navbar-expand">
            <div class="collapse navbar-collapse justify-content-between">
                <div class="header-left"></div>
                <ul class="navbar-nav header-right">
                    <li class="nav-item dropdown header-profile">
                        <a class="nav-link" href="javascript:void(0);" role="button" data-bs-toggle="dropdown">
                            <img src="images/Kapraa-logo.png" width="20" alt="Kapraa">
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="profile.php" class="dropdown-item ai-icon">
                                <svg id="icon-user2" xmlns="http://www.w3.org/2000/svg" class="text-primary"
                                    width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <span class="ms-2">Profile </span>
                            </a>
                            <a href="logout.php" class="dropdown-item ai-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="text-danger" width="18"
                                    height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <polyline points="16 17 21 12 16 7"></polyline>
                                    <line x1="21" y1="12" x2="9" y2="12"></line>
                                </svg>
                                <span class="ms-2">Logout </span>
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</div>
<!--**********************************
    Header end ti-comment-alt
***********************************-->

<!--**********************************
    Sidebar start
***********************************-->
<div class="dlabnav">
    <div class="dlabnav-scroll">
        <ul class="metismenu" id="menu">
            <li>
                <a class="" href="dashboard.php" aria-expanded="false">
                    <i class="flaticon-025-dashboard"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span class="nav-text">Orders</span>
                </a>
                <ul aria-expanded="false">
                    <?php
                        $orderCounts = getOrderStatusCounts();
                    ?>
                    <li><a href="all_order.php">All (<?php echo $orderCounts['all']; ?>)</a></li>
                    <li><a href="pending_order.php">Pending (<?php echo $orderCounts['pending']; ?>)</a></li>
                    <li><a href="confirmed_order.php">Confirmed (<?php echo $orderCounts['confirmed']; ?>)</a></li>
                    <li><a href="packaging_order.php">Packaging (<?php echo $orderCounts['packaging']; ?>)</a></li>
                    <li><a href="dlivery_order.php">Out of Dlivery (<?php echo $orderCounts['out of delivery']; ?>)</a></li>
                    <li><a href="return_order.php">Return (<?php echo $orderCounts['return']; ?>)</a></li>
                    <li><a href="failed_dlivery_order.php">Failed Dlliver (<?php echo $orderCounts['failed deliver']; ?>)</a></li>
                    <li><a href="cancel_order.php">Cancel (<?php echo $orderCounts['cancel']; ?>)</a></li>
                </ul>
            </li>
            <li>
                <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                    <i class="fa fa-list-alt" aria-hidden="true"></i>
                    <span class="nav-text">Category Setup</span>
                </a>
                <ul aria-expanded="false">
                    <li><a href="categories.php">Categories</a></li>
                    <li><a href="sub_categories.php">Sub Categories</a></li>
                </ul>
            </li>
            <li>
                <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                    <i class="fa-brands fa-react"></i>
                    <span class="nav-text">Brand ( <?php echo $Totalbrand ?> )</span>
                </a>
                <ul aria-expanded="false">
                    <li><a href="add_brand.php">Add Brand</a></li>
                </ul>
            </li>
            <li>
                <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                    <i class="fa-brands fa-product-hunt"></i>
                    <span class="nav-text">Products</span>
                </a>
                <ul aria-expanded="false">
                    <li><a href="add_products.php">Add Products</a></li>
                    <li><a href="product.php">Products List ( <?php echo $Totalproduct ?> )</a></li>
                    <li><a href="add_size.php">Add Size</a></li>
                    <li><a href="add_color.php">Add Color</a></li>
                </ul>
            </li>
            <li>
                <a class="" href="banner.php" aria-expanded="false">
                    <i class="fa-solid fa-image"></i>
                    <span class="nav-text">Banner Setup</span>
                </a>
            </li>
            <li>
                <?php
                    $couponQuery = @$connection_database->query("SELECT count(id) as c FROM coupons WHERE enabled=1 AND (expires_at IS NULL OR expires_at >= CURDATE())");
                    $Totalcoupon = $couponQuery ? number_format($couponQuery->fetch_assoc()['c']) : 0;
                ?>
                <a class="" href="coupons.php" aria-expanded="false">
                    <i class="fa fa-ticket"></i>
                    <span class="nav-text">Coupons ( <?php echo $Totalcoupon ?> )</span>
                </a>
            </li>
            <li>
                <?php
                    $loyWinQ = @$connection_database->query("SELECT COUNT(*) c FROM loyalty_scores WHERE is_winner=1 AND winner_notified=0");
                    $loyWinCount = $loyWinQ ? (int)$loyWinQ->fetch_assoc()['c'] : 0;
                ?>
                <a class="" href="loyalty.php" aria-expanded="false">
                    <i class="fa fa-trophy"></i>
                    <span class="nav-text">Loyalty Scores<?php if($loyWinCount>0): ?> <span class="badge bg-danger ms-1"><?= $loyWinCount ?></span><?php endif; ?></span>
                </a>
            </li>
            <li>
                <a class="" href="email_campaigns.php" aria-expanded="false">
                    <i class="fa fa-envelope"></i>
                    <span class="nav-text">Email Campaigns</span>
                </a>
            </li>
            <li>
                <a class="" href="social_proof.php" aria-expanded="false">
                    <i class="fa fa-bell"></i>
                    <span class="nav-text">Social Proof</span>
                </a>
            </li>
            <li>
                <a class="" href="announcement.php" aria-expanded="false">
                    <i class="fa-solid fa-bullhorn"></i>
                    <span class="nav-text">Announcement Bar</span>
                </a>
            </li>
            <li>
                <a class="" href="popup.php" aria-expanded="false">
                    <i class="fa-solid fa-rectangle-ad"></i>
                    <span class="nav-text">Promo Popup</span>
                </a>
            </li>
            <li>
                <?php
                    $lcWaitQ = @$connection_database->query("SELECT COUNT(*) c FROM live_chats WHERE status='waiting'");
                    $lcWait  = ($lcWaitQ) ? (int)$lcWaitQ->fetch_assoc()['c'] : 0;
                ?>
                <a class="" href="live_chat.php" aria-expanded="false">
                    <i class="fa fa-comments"></i>
                    <span class="nav-text">Live Chat
                        <?php if($lcWait > 0): ?>
                            <span style="background:#e74c3c;color:#fff;font-size:9px;font-weight:800;padding:1px 6px;border-radius:10px;margin-left:4px;"><?php echo $lcWait ?></span>
                        <?php endif; ?>
                    </span>
                </a>
            </li>
            <li>
                <a class="" href="customers.php" aria-expanded="false">
                    <i class="fa fa-users" aria-hidden="true"></i>
                    <span class="nav-text">Customers ( <?php echo $Totalcustomer ?> )</span>
                </a>
            </li>
            <li>
                <a class="" href="blog.php" aria-expanded="false">
                    <i class='fas fa-blog'></i>
                    <span class="nav-text">Blog ( <?php echo $Totalblog ?> )</span>
                </a>
            </li>
            <li>
                <a class="" href="team.php" aria-expanded="false">
                    <i class="fa fa-users"></i>
                    <span class="nav-text">Team ( <?php echo $Totalteam ?> )</span>
                </a>
            </li>
            <li>
                <a class="" href="contact.php" aria-expanded="false">
                    <i class="fa fa-address-book"></i>
                    <span class="nav-text">Contact ( <?php echo $Totalcontact ?> )</span>
                </a>
            </li>
            <li>
                <?php
                    // Safely count reviews (table may not exist yet)
                    @$connection_database->query("CREATE TABLE IF NOT EXISTS product_reviews (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL, user_id INT DEFAULT NULL, reviewer_name VARCHAR(80) NOT NULL, reviewer_photo VARCHAR(255) DEFAULT NULL, rating TINYINT NOT NULL DEFAULT 5, review_text TEXT NOT NULL, status ENUM('visible','hidden') DEFAULT 'visible', created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX idx_product (product_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    $revCountQ = $connection_database->query("SELECT COUNT(*) c FROM product_reviews WHERE status='visible'");
                    $revCount  = ($revCountQ) ? (int)$revCountQ->fetch_assoc()['c'] : 0;
                ?>
                <a class="" href="reviews.php" aria-expanded="false">
                    <i class="fa fa-star"></i>
                    <span class="nav-text">Reviews ( <?php echo $revCount; ?> )</span>
                </a>
            </li>
        </ul>
    </div>
</div>
<!--**********************************
    Sidebar end
***********************************-->