<?php
    // Process login BEFORE any HTML output (include/head.php below starts
    // printing <!DOCTYPE html> immediately) — otherwise header("Location: ...")
    // silently fails with "headers already sent" and the redirect never happens.
    include 'admin/include/config.php';
    include 'cart_functions.php';

    if(isset($_POST['LOGIN'])){
        $email       = trim($_POST['email'] ?? '');
        $rawPassword = trim($_POST['password'] ?? '');

        // Fetch stored hash by email only (support both MD5 legacy + bcrypt new)
        $stmt = $connection_database->prepare("SELECT id, password FROM customers WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->bind_result($loginId, $storedHash);
        $loginFound = $stmt->fetch();
        $stmt->close();

        // Verify: bcrypt (new) OR md5 (legacy old users)
        $passwordOk = false;
        if ($loginFound && $storedHash) {
            if (str_starts_with($storedHash, '$2y$')) {
                // New bcrypt hash
                $passwordOk = password_verify($rawPassword, $storedHash);
            } else {
                // Legacy MD5 hash — still works for old accounts
                $passwordOk = ($storedHash === md5($rawPassword));
            }
        }

        if ($passwordOk && $loginId) {
            $_SESSION['WEBSITE-LOGINSS'] = $loginId;

            // Check if cart has items
            $cartItems = getCartItems();

            // Redirect based on cart contents
            if (!empty($cartItems)) {
                // If cart has items, redirect to checkout
                header("Location: checkout");
                exit;
            } else {
                // If cart is empty, redirect to user dashboard
                header("Location: user");
                exit;
            }
        } else {
            $error[] = "Your username and password are not correct";
        }
    }

    include 'include/head.php';
?>
<style>
    /* Sidebar Styles */
    .sidebarsss {
        position: fixed;
        top: 0;
        left: -300px;
        width: 300px;
        height: 100%;
        background-color: white;
        color: black;
        transition: left 0.3s ease;
        z-index: 1000;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }

    .sidebarsss.active {
        left: 0;
    }

    .sidebarsss-header {
        padding: 15px;
        background-color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .sidebarsss-title {
        font-size: 20px;
        font-weight: 600;
        color: black;
    }

    .close-btn {
        color: #000;
        font-size: 14px;
        cursor: pointer;
    }
    .sidebarsss-menu {
        flex: 1;
        overflow-y: auto;
    }
    .sidebarsss-menu ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebarsss-menu li {
        padding: 0;
        position: relative;
    }

    .sidebarsss-menu a {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 20px;
        color: black;
        text-decoration: none;
        transition: background-color 0.3s;
        font-size: 16px;
        border-bottom: 1px solid #eee;
    }

    .sidebarsss-menu a:hover {
        background-color: black;
        color: white
    }

    .sidebarsss-menu .submenu {
        display: none;
        background-color: #f9f9f9;
        color: black;
    }

    .sidebarsss-menu .submenu.active {
        display: block;
    }

    .sidebarsss-menu .submenu a {
        padding-left: 30px;
        font-size: 12px;
    }

    .sidebarsss-menu .submenu .submenu a {
        padding-left: 50px;
    }

    .sidebarsss-menu .has-submenu > a .icon {
        transition: transform 0.3s ease;
        font-size: 15px;
        display: inline-block;
        width: 16px;
        height: 16px;
        position: relative;
    }

    .sidebarsss-menu .has-submenu > a .icon:before {
        content: '⌵';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-weight: bold;
        transition: transform 0.3s ease;
    }

    .sidebarsss-menu .has-submenu.active > a .icon:before {
        content: '⌵';
        transform: translate(-50%, -50%) rotate(180deg);
    }

    /* Search Bar Styles */
    .search-container {
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
    }

    .search-input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        outline: none;
        transition: border-color 0.3s;
    }

    .search-input:focus {
        border-color: #000;
    }

    .no-results {
        padding: 10px 20px;
        color: #666;
        font-style: italic;
        font-size: 14px;
    }

    /* Live Search Results */
    .live-search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-top: none;
        max-height: 300px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
    }

    .live-search-result-item {
        padding: 10px;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
    }

    .live-search-result-item:last-child {
        border-bottom: none;
    }

    .live-search-result-item a {
        display: flex;
        align-items: center;
        text-decoration: none;
        color: #333;
        width: 100%;
    }

    .live-search-result-item img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        margin-right: 15px;
        border-radius: 4px;
    }

    .live-search-result-item .product-name {
        font-weight: 500;
    }

    /* Overlay */
    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 999;
        display: none;
    }

    .overlay.active {
        display: block;
    }

    /* Main Content */
    .main-content {
        flex: 1;
        padding: 30px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .content-card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 30px;
        margin-bottom: 30px;
    }

    /* Menu Button */
    .menu-button {
        background: none;
        border: none;
        cursor: pointer;
        padding: 10px;
    }

    /* Social Media in Sidebar */
    .sidebar-social {
        padding: 20px;
        border-top: 1px solid #eee;
        background-color: white;
    }

    .sidebar-social .social-icons {
        display: flex;
        justify-content: center;
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar-social .social-icons li {
        margin: 0 15px;
    }

    .sidebar-social .social-icons a {
        color: black;
        font-size: 18px;
        transition: color 0.3s;
    }

    .sidebar-social .social-icons a:hover {
        color: #4267B2; /* Facebook blue */
    }

    .sidebar-social .social-icons li:nth-child(2) a:hover {
        color: #E1306C; /* Instagram pink */
    }

    .sidebar-social .social-icons li:nth-child(3) a:hover {
        color: #FF0000; /* YouTube red */
    }

    /* Responsive */
    @media (max-width: 768px) {
        .sidebarsss {
            width: 250px;
        }
        
        .main-content {
            padding: 20px;
        }
    }
    .site-header__cart-count1{
        font-size: 11px;
        font-weight: 500;
        display: inline-block;
        position: absolute;
        top: -5px;
        right: -13px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        text-align: center;
        line-height: 16px;
    }
    .ttt{
        font-size: 11px !important;
    }
</style>
<?php
    // Fetch categories with their subcategories
    $categories = [];
    $categoryQuery = "SELECT * FROM category ORDER BY priority ASC";
    $categoryResult = $connection_database->query($categoryQuery);

    if ($categoryResult && $categoryResult->num_rows > 0) {
        while($category = $categoryResult->fetch_assoc()) {
            $categoryId = $category['id'];
            $category['subcategories'] = [];
            
            // Fetch subcategories for this category
            $subcategoryQuery = "SELECT * FROM subcategory WHERE main_category = $categoryId ORDER BY priority ASC";
            $subcategoryResult = $connection_database->query($subcategoryQuery);
            
            if ($subcategoryResult && $subcategoryResult->num_rows > 0) {
                while($subcategory = $subcategoryResult->fetch_assoc()) {
                    $category['subcategories'][] = $subcategory;
                }
            }
            
            $categories[] = $category;
        }
    }

    // Fetch brands
    $brands = [];
    $brandQuery = "SELECT * FROM brand ORDER BY name ASC";
    $brandResult = $connection_database->query($brandQuery);

    if ($brandResult && $brandResult->num_rows > 0) {
        while($brand = $brandResult->fetch_assoc()) {
            $brands[] = $brand;
        }
    }
?>

<!-- Search Form Drawer -->
<div class="search">
    <div class="search__form">
        <!-- Change this form action -->
        <form class="search-bar__form" action="<?php echo $url_domain; ?>/product/" method="get">
            <button class="go-btn search__button" type="submit"><i class="icon an an-search"></i></button>
            <input class="search__input" type="search" name="search_query" value="" placeholder="Search Entire Store..." aria-label="Search" autocomplete="off" />
        </form>
        <div class="live-search-results"></div>
        <button type="button" class="search-trigger close-btn" data-bs-toggle="tooltip" data-bs-placement="left" title="Close"><i class="icon an an-times"></i></button>
    </div>
</div>
<!-- End Search Form Drawer -->

<!-- Main Header -->
<div class="header-section clearfix animated hdr-sticky">
    <!-- Desktop Header -->
    <div class="header-6 classic-style">
        <!-- Top Header -->
        <div class="top-header">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-7 col-sm-8 col-md-7 col-lg-4">
                        <p class="phone-no float-start"><i class="icon an an-phone me-1"></i><a href="tel:+923209815213">+92 320 9815213</a></p>
                    </div>
                    <div class="col-sm-4 col-md-4 col-lg-4 d-none d-md-none d-lg-block">
                        <div class="text-center">
                            <p class="top-header_middle-text transform-none">SECURE PAYMENT GATEWAYS</p>
                        </div>
                    </div>
                    <div class="col-5 col-sm-4 col-md-5 col-lg-4 text-end">
                        <div class="header-social">
                            <ul class="justify-content-end list--inline social-icons">
                                <li><a class="social-icons__link" href="https://www.facebook.com/kapraaofficial" target="_blank" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Facebook"><i class="icon an an-facebook"></i> <span class="icon__fallback-text">Facebook</span></a></li>
                                <li><a class="social-icons__link" href="https://www.instagram.com/kapraaofficial" target="_blank" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Instagram"><i class="icon an an-instagram"></i> <span class="icon__fallback-text">Instagram</span></a></li>
                                <li>
                                    <a href="https://www.tiktok.com/@kapraaofficial" target="_blank" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Tik Tok">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                                        </svg> 
                                    </a>                               
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        <!-- End Top Header -->

        <!-- Header -->
        <div class="header-wrap d-flex border-bottom">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-4 col-sm-4 col-md-5 col-lg-5 d-block d-lg-none">
                        <button type="button" class="btn--link site-header__menu js-mobile-nav-toggle mobile-nav--open" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Menu">
                            <!--<i class="icon an an-times"></i>-->
                            <i class="icon an an-bars"></i>
                        </button>
                    </div>
                    <div class="col-4 col-sm-4 col-md-5 col-lg-5 d-none d-sm-block">
                        <button type="button" id="menu-button" class="border-0 rounded py-1">
                            <i class="icon an an-bars text-dark" style="font-size:30px !important"></i>
                        </button>
                    </div>
                    <!-- Desktop Logo -->
                    <div class="logo col-4 col-sm-4 col-md-2 col-lg-2 text-center">
                        <a href="<?php echo $url_domain;?>/index">
                            <img src="<?php echo $url_domain;?>/assets/images/logo2.png" alt="" height="55px">
                        </a>
                    </div>
                    <!-- End Desktop Logo -->

                    <!-- Right Side -->
                    <div class="col-4 col-sm-4 col-md-5 col-lg-5">
                        <div class="right-action text-action d-flex-align-center justify-content-end">
                            <!-- Mobile Search -->
                            <div class="item site-user-menu">
                                <button type="button" class="search-trigger border-0" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Search">
                                    <i class="icon an an-search"></i>
                                </button>
                            </div>
                            <div class="item site-user-menu d-none d-md-none d-lg-block">
                                <a href="<?php echo $url_domain;?>/login" class="icon-login text-capitalize text-nowrap"><i class="icon an an-user-alt"></i></a>
                            </div>
                            <!-- End User Links -->
                           
                            <!-- Minicart -->
                            <div class="item site-cart">
                                <a href="#" class="icon-cart site-header-cart btn-minicart th-cart-wrap" data-bs-toggle="modal" data-bs-target="#minicart-drawer">
                                    <i class="icon an an-shopping-bag"></i>
                                    <span id="CartCount" class="site-header__cart-count1"><?php echo getCartItemCount(); ?></span>
                                </a>
                            </div>
                            <!-- End Minicart -->
                        </div>
                    </div>
                    <!-- End Right Side -->
                </div>
            </div>
        </div>
        <!-- End Header -->
    </div>
    <!-- End Desktop Header -->
</div>
<!-- End Main Header -->
<!-- Sidebar Desktop -->
<div class="sidebarsss" id="sidebarsss">
    <div class="sidebarsss-header">
        <div class="sidebarsss-title">Kapraa</div>
        <div class="close-btn" id="close-sidebar">
            <i class="icon an an-times-circle closemenu m-1 text-dark fa-2x"></i>
        </div>
    </div>
    <div class="sidebarsss-menu">
        <ul id="MobileNav" class="mobile-nav">
             <li class="lvl1 parent megamenu">
                <a href="<?php echo $url_domain;?>/index" class="site-nav">Home</a>
            </li>
            <!-- Categories Section -->
            <?php foreach($categories as $category): ?>
            <li class="lvl1 parent megamenu has-submenu">
                <a href="<?php echo $url_domain;?>/products/category/<?php echo $category['slug']; ?>" class="site-nav">
                    <?php echo $category['name']; ?>
                    <?php if(!empty($category['subcategories'])): ?>
                    <span class="icon"></span>
                    <?php endif; ?>
                </a>
                <?php if(!empty($category['subcategories'])): ?>
                <ul class="submenu">
                    <!-- Search bar for subcategories -->
                    <li class="search-container">
                        <input type="text" class="search-input" placeholder="Search..." data-search-target="subcategories-<?php echo $category['id']; ?>">
                    </li>
                    <div class="submenu-items" id="subcategories-<?php echo $category['id']; ?>">
                        <?php foreach($category['subcategories'] as $subcategory): ?>
                        <li><a href="<?php echo $url_domain;?>/products/subcategory/<?php echo $subcategory['slug']; ?>" class="site-nav"><?php echo $subcategory['name']; ?></a></li>
                        <?php endforeach; ?>
                    </div>
                </ul>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>

            <!-- Brands Section -->
            <li class="lvl1 parent megamenu has-submenu">
                <a href="#" class="site-nav">Brands <span class="icon"></span></a>
                <ul class="submenu">
                    <!-- Search bar for brands -->
                    <li class="search-container">
                        <input type="text" class="search-input" placeholder="Search brands..." data-search-target="brands">
                    </li>
                    <div class="submenu-items" id="brands">
                        <?php foreach($brands as $brand): ?>
                        <li><a href="<?php echo $url_domain;?>/products/brand/<?php echo $brand['slug']; ?>" class="site-nav"><?php echo $brand['name']; ?></a></li>
                        <?php endforeach; ?>
                    </div>
                </ul>
            </li>
            
            <!-- Static Pages (Keep your existing pages) -->
            <li class="lvl1 parent megamenu">
                <a href="<?php echo $url_domain;?>/contact" class="site-nav">Contact</a>
            </li>
            <li class="lvl1 parent megamenu">
                <a href="<?php echo $url_domain;?>/login" class="site-nav">Login</a>
            </li>
        </ul>
    </div>
    
    <!-- Social Media Icons at Bottom -->
    <div class="sidebar-social">
        <ul class="social-icons">
            <li><a href="https://www.facebook.com/kapraaofficial" target="_blank" data-bs-toggle="tooltip" data-bs-placement="top" title="Facebook"><i class="icon an an-facebook"></i></a></li>
            <li><a href="https://www.instagram.com/kapraaofficial" target="_blank" data-bs-toggle="tooltip" data-bs-placement="top" title="Instagram"><i class="icon an an-instagram"></i></a></li>
            <li>
                <a href="https://www.tiktok.com/@kapraaofficial" target="_blank" data-bs-toggle="tooltip" data-bs-placement="top" title="Tik Tok">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                    </svg> 
                </a>
            </li>
        </ul>
    </div>
</div>
<div class="overlay" id="overlay"></div>
<!-- Mobile Menu -->
<div class="mobile-nav-wrapper dark-wrapper" role="navigation">
    <div class="sidebarsss-header closemobileMenu">
        <div class="sidebarsss-title">Kapraa</div>
        <div class="close-btn" id="close-mobile-sidebar">
            <i class="icon an an-times-circle closemenu m-1 text-dark fa-2x"></i>
        </div>
    </div>
    <ul id="MobileNav" class="mobile-nav">
        <li class="lvl1 parent megamenu">
            <a href="<?php echo $url_domain;?>/index" class="site-nav">Home</a>
        </li>
        <!-- Categories Section - Modified for mobile as well -->
        <?php foreach($categories as $category): ?>
        <li class="lvl1 parent megamenu has-submenu">
            <a href="<?php echo $url_domain;?>/products/category/<?php echo $category['slug']; ?>" class="site-nav">
                <?php echo $category['name']; ?>
                <?php if(!empty($category['subcategories'])): ?>
                <i class="an an-plus icon mobile-icon"></i>
                <?php endif; ?>
            </a>
            <?php if(!empty($category['subcategories'])): ?>
            <ul class="submenu mobile-submenu">
                <li class="search-container">
                    <input type="text" class="search-input" placeholder="Search..." data-search-target="mobile-subcategories-<?php echo $category['id']; ?>">
                </li>
                <div class="submenu-items" id="mobile-subcategories-<?php echo $category['id']; ?>">
                    <?php foreach($category['subcategories'] as $subcategory): ?>
                    <li><a href="<?php echo $url_domain;?>/products/subcategory/<?php echo $subcategory['slug']; ?>" class="site-nav ttt"><?php echo $subcategory['name']; ?></a></li>
                    <?php endforeach; ?>
                </div>
            </ul>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>

        <!-- Brands Section -->
        <li class="lvl1 parent megamenu has-submenu">
            <a href="#" class="site-nav">Brands <i class="an an-plus icon mobile-icon"></i></a>
            <ul class="submenu mobile-submenu">
                <li class="search-container">
                    <input type="text" class="search-input" placeholder="Search brands..." data-search-target="mobile-brands">
                </li>
                <div class="submenu-items" id="mobile-brands">
                    <?php foreach($brands as $brand): ?>
                    <li><a href="<?php echo $url_domain;?>/products/brand/<?php echo $brand['slug']; ?>" class="site-nav ttt"><?php echo $brand['name']; ?></a></li>
                    <?php endforeach; ?>
                </div>
            </ul>
        </li>
        
        <!-- Static Pages (Keep your existing pages) -->
        <li class="lvl1 parent megamenu">
            <a href="<?php echo $url_domain;?>/contact" class="site-nav">Contact</a>
        </li>
        <li class="lvl1 parent megamenu">
            <a href="<?php echo $url_domain;?>/login" class="site-nav">Login</a>
        </li>
    </ul>
</div>
<!-- End Mobile Menu -->
<!-- Minicart Drawer -->
<div class="minicart-right-drawer right modal fade" id="minicart-drawer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="minicart-header">
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><i class="an an-times" aria-hidden="true" data-bs-toggle="tooltip" data-bs-placement="left" title="Close"></i></button>
                <h4 class="modal-title" id="myModalLabel2">Shopping Cart <strong id="cart-item-count">(0)</strong> items</h4>
            </div>
            <div class="minicart-body">
                <div class="empty-cart">
                    <p>You have no items in your shopping cart.</p>
                </div>
            </div>
            <div class="minicart-footer minicart-action" style="display: none;">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>
    </div>
</div>
<!-- End Minicart Drawer -->
<?php
  include 'include/mobile_sidebar.php';
?>
<!-- Page Title -->
<div class="page section-header text-center mb-0">
    <div class="page-title">
        <div class="wrapper"><h1 class="page-width">Login</h1></div>
    </div>
</div>
<!-- End Page Title -->
<!-- Breadcrumbs -->
<div class="bredcrumbWrap bredcrumbWrapPage bredcrumb-style2 text-center">
    <div class="container breadcrumbs">
        <a href="<?php echo $url_domain;?>/index" title="Back to the home page">Home</a><span aria-hidden="true">|</span><span class="title-bold">Login</span>
    </div>
</div>
<!-- End Breadcrumbs -->

<div class="container">
    <div class="row">
        <!-- Main Content -->
        <div class="col-12 col-sm-12 col-md-6 col-lg-6 box mb-4 mb-md-0">
            <h3>New Customers</h3>
            <p>By creating an account with our store, you will be able to move through the checkout process faster, store multiple shipping addresses, view and track your orders in your account and more.</p>
            <a href="register" class="btn">Create an account</a>
        </div>
        <div class="col-12 col-sm-12 col-md-6 col-lg-6 box">
            <div class="mb-4">
                <?php if(isset($error) && !empty($error)): ?>
                    <div class="alert alert-danger">
                        <?php foreach($error as $err): ?>
                            <p><?php echo $err; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" class="customer-form">	
                    <h3>Registered Customers</h3>
                    <p>If you have an account with us, please log in.</p>
                    <div class="row">
                        <div class="col-12 col-sm-12 col-md-12 col-lg-12">
                            <div class="form-group">
                                <label for="CustomerEmail">Email <span class="required">*</span></label>
                                <input type="email" name="email" placeholder="" autofocus />
                            </div>
                        </div>
                        <div class="col-12 col-sm-12 col-md-12 col-lg-12">
                            <div class="form-group">
                                <label for="CustomerPassword">Password <span class="required">*</span></label>
                                <input type="password" value="" name="password" placeholder="" class="">                        	
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="text-left col-12 col-sm-12 col-md-12 col-lg-12">
                            <input type="submit" class="btn mb-3" value="Sign In" name="LOGIN">
                            <a href="<?php echo $url_domain; ?>/forgot-password" style="display:block; margin-top:8px; font-size:13px; color:#1a1a2e;">Forgot your password?</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- End Main Content -->
    </div>
</div>
<?php
    include 'include/footer.php';
?>