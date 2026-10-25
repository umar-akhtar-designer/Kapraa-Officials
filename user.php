<?php
    require_once 'admin/include/config.php';
    if (!isset($_SESSION['WEBSITE-LOGINSS'])) {
        header("Location: login");
        exit();
    }

    // Get user ID from session
    $userId = $_SESSION['WEBSITE-LOGINSS'];

    // Get user information
    $user_query = "SELECT * FROM customers WHERE id = '$userId' OR email = '$userId'";
    $user_result = $connection_database->query($user_query);
    $user = $user_result->fetch_assoc();
    $user_email = $user['email'];
    
    // Get user orders
    $orders_query = "SELECT * FROM orders WHERE email = '$user_email' ORDER BY created_at DESC";
    $orders_result = $connection_database->query($orders_query);
    
    // Function to get order items
    function getOrderItems($connection_database, $order_id) {
        $items_query = "SELECT * FROM order_items WHERE order_id = '$order_id'";
        $items_result = $connection_database->query($items_query);
        $items = [];
        
        if ($items_result->num_rows > 0) {
            while($row = $items_result->fetch_assoc()) {
                $items[] = $row;
            }
        }
        
        return $items;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Kapraa </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
       
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header Styles */
        header {
            border-bottom: 1px solid #e1e1e1;
            padding: 20px 0;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            text-decoration: none;
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 30px;
        }
        
        nav ul li a {
            text-decoration: none;
            color: #333;
            font-size: 16px;
            font-weight: 500;
            padding-bottom: 5px;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        nav ul li a.active {
            border-bottom: 2px solid #333;
        }
        
        nav ul li a:hover {
            color: #555;
        }
        
        /* Main Content Styles */
        main {
            padding: 60px 0;
            min-height: calc(100vh - 250px);
        }
        
        .orders-container {
            text-align: center;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .orders-title {
            font-size: 32px;
            margin-bottom: 30px;
            font-weight: 600;
            text-align: center;
        }
        
        .order-card {
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .order-header {
            background-color: #f8f8f8;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e1e1e1;
        }
        
        .order-number {
            font-weight: 600;
            font-size: 18px;
        }
        
        .order-date {
            color: #666;
            font-size: 14px;
        }
        
        .order-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-packaging {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-out-of-delivery {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-return {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-failed-deliver {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-cancel {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .order-body {
            padding: 20px;
        }
        
        .order-items {
            margin-bottom: 20px;
        }
        
        .order-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
        }
        
        .item-details {
            flex-grow: 1;
            text-align: left;
        }
        
        .item-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .item-variants {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .item-price {
            font-weight: 600;
        }
        
        .order-summary {
            display: flex;
            justify-content: space-between;
            padding-top: 15px;
            border-top: 1px solid #e1e1e1;
        }
        
        .summary-left {
            text-align: left;
        }
        
        .summary-right {
            text-align: right;
        }
        
        .summary-row {
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .summary-total {
            font-weight: 600;
            font-size: 16px;
            margin-top: 10px;
        }
        
        .no-orders {
            text-align: center;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .orders-icon {
            font-size: 80px;
            color: #e1e1e1;
            margin-bottom: 30px;
        }
        
        .orders-message {
            font-size: 18px;
            color: #666;
            margin-bottom: 30px;
        }
        
        .btn-shop {
            display: inline-block;
            background-color: #333;
            color: white;
            padding: 12px 30px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }
        
        .btn-shop:hover {
            background-color: #555;
        }
        
        /* Footer Styles */
        footer {
            border-top: 1px solid #e1e1e1;
            padding: 30px 0;
            margin-top: 60px;
        }
        
        .footer-content {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .footer-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .footer-links a {
            color: #666;
            text-decoration: none;
            margin: 0 15px 10px;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        
        .footer-links a:hover {
            color: #333;
            text-decoration: underline;
        }
        
        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .header-content {
                position: relative;
            }
            
            .mobile-menu-toggle {
                display: block;
            }
            
            nav {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background-color: white;
                border-top: 1px solid #e1e1e1;
                padding: 20px;
                display: none;
                z-index: 100;
            }
            
            nav.active {
                display: block;
            }
            
            nav ul {
                flex-direction: column;
            }
            
            nav ul li {
                margin: 10px 0;
            }
            
            .orders-title {
                font-size: 28px;
            }
            
            .orders-message {
                font-size: 16px;
            }
            
            .orders-icon {
                font-size: 60px;
            }
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .order-status {
                margin-top: 10px;
            }
            
            .order-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .item-image {
                margin-bottom: 10px;
                margin-right: 0;
            }
            
            .order-summary {
                flex-direction: column;
            }
            
            .summary-right {
                text-align: left;
                margin-top: 15px;
            }
        }
        
        /* NEW ENHANCEMENT STYLES - ADDED ONLY */
        
        /* Background and overall page styling */
        body {
            background-color: #f7f9fc;
            background-image: linear-gradient(135deg, #f7f9fc 0%, #e9f0f8 100%);
        }
        
        /* Header enhancements */
        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
     
        .logo:before {
            content: "\f290";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            margin-right: 10px;
            font-size: 24px;
        }
        
        /* Navigation enhancements */
        nav ul li a {
            position: relative;
        }
        
    
        
        /* Main content enhancements */
        .orders-container {
            text-align: left;
        }
        
        .orders-title {
            position: relative;
            padding-bottom: 15px;
            margin-bottom: 40px;
        }
        
        .orders-title:after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 4px;
           
            border-radius: 2px;
        }
        
        /* Order card enhancements */
        .order-card {
            background-color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border: none;
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }
        
        .order-header {
            background: linear-gradient(135deg, #f8f9fe 0%, #e9ecef 100%);
            border-bottom: 1px solid #e9ecef;
            padding: 20px;
        }
        
        .order-number {
            display: flex;
            align-items: center;
            color: #2d3748;
        }
        
        .order-number:before {
            content: "\f15c";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            margin-right: 8px;
          
        }
        
        .order-date {
            display: flex;
            align-items: center;
            margin-top: 5px;
        }
        
        .order-date:before {
            content: "\f073";
            font-family: "Font Awesome 6 Free";
            font-weight: 400;
            margin-right: 5px;
            color: #718096;
        }
        
        /* Status badge enhancements */
        .order-status {
            display: flex;
            align-items: center;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 30px;
        }
        
        .order-status:before {
            margin-right: 6px;
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
        }
        
        .status-pending:before {
            content: "\f017";
        }
        
        .status-confirmed:before {
            content: "\f00c";
        }
        
        .status-packaging:before {
            content: "\f187";
        }
        
        .status-out-of-delivery:before {
            content: "\f0d1";
        }
        
        .status-return:before {
            content: "\f3e5";
        }
        
        .status-failed-deliver:before {
            content: "\f071";
        }
        
        .status-cancel:before {
            content: "\f00d";
        }
        
        /* Order item enhancements */
        .order-item {
            transition: background-color 0.2s ease;
            border-radius: 8px;
            margin: 0 -10px;
            padding: 20px 10px;
        }
        
        .order-item:hover {
            background-color: rgba(74, 107, 223, 0.05);
        }
        
        .item-image {
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            transition: transform 0.3s ease;
        }
        
        .order-item:hover .item-image {
            transform: scale(1.05);
        }
        
        .item-name {
            color: #2d3748;
            display: flex;
            align-items: center;
        }
        
        .item-name:before {
            content: "\f02b";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            margin-right: 8px;
          
        }
        
        .item-variants {
            display: flex;
            align-items: center;
        }
        
        .item-variants:before {
            content: "\f111";
            font-family: "Font Awesome 6 Free";
            font-weight: 400;
            margin-right: 5px;
            color: #718096;
            font-size: 8px;
        }
        
        .item-price {
           
            font-size: 18px;
            display: flex;
            align-items: center;
        }
        
        .item-price:before {
            
            font-family: "Font Awesome 6 Free";
            font-weight: 400;
            margin-right: 5px;
            
        }
        
        /* Order summary enhancements */
        .order-summary {
            border-top: 1px solid #e9ecef;
            padding-top: 20px;
        }
        
        .summary-row {
            display: flex;
            align-items: center;
            color: #4a5568;
        }
        
        .summary-row:nth-child(1):before {
            content: "\f09d";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            margin-right: 8px;
           
        }
        
        .summary-row:nth-child(2):before {
            content: "\f00c";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            margin-right: 8px;
           
        }
        
        .summary-row:nth-child(3):before {
            content: "\f3c5";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            margin-right: 8px;
            
        }
        
        .summary-right .summary-row:before {
            content: "\f1ec";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            margin-right: 8px;
            
        }
        
        .summary-total {
            color: #2d3748;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }
        
        .summary-total:before {
            content: "\f3d1";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            margin-right: 8px;
            
        }
        
        /* No orders section enhancements */
        .no-orders {
            background-color: white;
            border-radius: 12px;
            padding: 60px 40px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .orders-icon {
            color: #cbd5e0;
            margin-bottom: 30px;
        }
        
        .btn-shop {
            
            border-radius: 30px;
            padding: 14px 30px;
            box-shadow: 0 4px 10px rgba(74, 107, 223, 0.3);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
        }
        
        .btn-shop:before {
            content: "\f07a";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            margin-right: 8px;
        }
        
        .btn-shop:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(74, 107, 223, 0.4);
        }
        
        /* Footer enhancements */
        footer {
            background-color: white;
            margin-top: 80px;
        }
        
        .footer-links a {
            position: relative;
            transition: color 0.3s ease;
        }
        
        .footer-links a:after {
            content: "";
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background-
            transition: width 0.3s ease;
        }
        
        .footer-links a:hover {
            
            text-decoration: none;
        }
        
        .footer-links a:hover:after {
            width: 100%;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="<?php echo $url_domain;?>/index" class="logo">Kapraa</a>
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <nav id="mainNav">
                    <ul>
                        <li><a href="<?php echo $url_domain;?>/index">Shop</a></li>
                        <li><a href="<?php echo $url_domain;?>/user" class="active">Orders</a></li>
                        <li><a href="<?php echo $url_domain;?>/account">Account</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>
    
    <main>
        <div class="container">
            <div class="orders-container">
                <h1 class="orders-title">My Orders</h1>
                
                <?php if ($orders_result->num_rows > 0): ?>
                    <?php while($order = $orders_result->fetch_assoc()): ?>
                        <?php 
                            $order_items = getOrderItems($connection_database, $order['id']);
                            $status_class = '';
                            
                            switch(strtolower($order['order_status'])) {
                                case 'pending':
                                    $status_class = 'status-pending';
                                    break;
                                case 'confirmed':
                                    $status_class = 'status-confirmed';
                                    break;
                                case 'packaging':
                                    $status_class = 'status-packaging';
                                    break;
                                case 'out of delivery':
                                    $status_class = 'status-out-of-delivery';
                                    break;
                                case 'return':
                                    $status_class = 'status-return';
                                    break;
                                case 'failed deliver':
                                    $status_class = 'status-failed-deliver';
                                    break;
                                case 'cancel':
                                    $status_class = 'status-cancel';
                                    break;
                                default:
                                    $status_class = 'status-pending';
                            }
                        ?>
                        
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <div class="order-number">Order # <?php echo $order['order_number']; ?></div>
                                    <div class="order-date"><?php echo date('F j, Y', strtotime($order['created_at'])); ?></div>
                                </div>
                                <div class="order-status <?php echo $status_class; ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </div>
                            </div>
                            <div class="order-body">
                                <div class="order-items">
                                    <?php foreach($order_items as $item): ?>
                                        <div class="order-item">
                                            <img src="<?php echo $url_domain; ?>/admin/images/product/<?php echo $item['product_image']; ?>" alt="Product Image" class="item-image">
                                            <div class="item-details">
                                                <div class="item-name">Product ID : <?php echo $item['product_id']; ?></div>
                                                <div class="item-variants">
                                                    Size : <?php echo $item['size']; ?> | 
                                                    Color : <?php echo $item['colors']; ?>
                                                </div>
                                                <div class="item-price">RS : <?php echo number_format($item['total_price'], 2); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="order-summary">
                                    <div class="summary-left">
                                        <div class="summary-row">Payment Method : <?php echo ucfirst($order['payment_method']); ?></div>
                                        <div class="summary-row">Shipping Address : <?php echo $order['address']; ?>, <?php echo $order['city']; ?>, <?php echo $order['state']; ?>, <?php echo $order['postal_code']; ?>, <?php echo $order['country']; ?></div>
                                    </div>
                                    <div class="summary-right">
                                        <div class="summary-row">Subtotal : <?php echo number_format($order['subtotal'], 2); ?></div>
                                        <div class="summary-total">Total : <?php echo number_format($order['total_amount'], 2); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-orders">
                        <div class="orders-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <h1 class="orders-title">No orders yet</h1>
                        <p class="orders-message">Go to store to place an order.</p>
                        <a href="<?php echo $url_domain;?>/index" class="btn-shop">Go to store</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-links">
                    <a href="<?php echo $url_domain;?>/faqs">Faq's</a>
                    <a href="<?php echo $url_domain;?>/returns-exchange">Exchange Policy</a>
                    <a href="<?php echo $url_domain;?>/privacy-policy">Privacy Policy</a>
                    <a href="<?php echo $url_domain;?>/term-condition">Terms of Condition</a>
                    <a href="<?php echo $url_domain;?>/contact">Contact information</a>
                </div>
            </div>
        </div>
    </footer>
    
    <script>
        // Mobile menu toggle
        document.getElementById('mobileMenuToggle').addEventListener('click', function() {
            document.getElementById('mainNav').classList.toggle('active');
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const nav = document.getElementById('mainNav');
            const toggle = document.getElementById('mobileMenuToggle');
            
            if (!nav.contains(event.target) && !toggle.contains(event.target)) {
                nav.classList.remove('active');
            }
        });
    </script>
</body>
</html>