<?php
    include 'include/head.php';
    include 'include/navbar.php';

    // Get order number from URL
    $orderNumber = isset($_GET['order']) ? $_GET['order'] : '';

    if (empty($orderNumber)) {
        header('Location: ' . $url_domain . '/index.php');
        exit;
    }

    // Use existing database connection
    global $connection_database;

    // Get order details from database
    $orderQuery = "SELECT * FROM orders WHERE order_number = ?";
    $stmt = mysqli_prepare($connection_database, $orderQuery);
    mysqli_stmt_bind_param($stmt, "s", $orderNumber);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $order = mysqli_fetch_assoc($result);

    if (!$order) {
        header('Location: ' . $url_domain . '/index.php');
        exit;
    }

    // Get order items
    $itemsQuery = "SELECT * FROM order_items WHERE order_id = ?";
    $stmt = mysqli_prepare($connection_database, $itemsQuery);
    mysqli_stmt_bind_param($stmt, "i", $order['id']);
    mysqli_stmt_execute($stmt);
    $itemsResult = mysqli_stmt_get_result($stmt);
    $orderItems = mysqli_fetch_all($itemsResult, MYSQLI_ASSOC);
?>
<style>
    .confirmation-page {
        padding: 40px 0;
        background-color: #f8f9fa;
        min-height: 80vh;
    }
    
    .page-title {
        font-size: 2.2rem;
        font-weight: 700;
        margin-bottom: 30px;
        color: #333;
        position: relative;
        padding-bottom: 15px;
    }
    
    .page-title:after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 0;
        width: 60px;
        height: 4px;
        background: black;
        border-radius: 2px;
    }
    
    .confirmation-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        overflow: hidden;
        padding: 40px;
        text-align: center;
    }
    
    .success-icon {
        font-size: 4rem;
        color: #28a745;
        margin-bottom: 20px;
    }
    
    .confirmation-message {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 15px;
        color: #333;
    }
    
    .order-number {
        font-size: 1.2rem;
        margin-bottom: 30px;
        color: #666;
    }
    
    .order-details {
        text-align: left;
        margin-top: 30px;
        border-top: 1px solid #eee;
        padding-top: 30px;
    }
    
    .detail-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        font-size: 1rem;
    }
    
    .detail-label {
        font-weight: 600;
        color: #333;
    }
    
    .detail-value {
        color: #666;
        text-align: right;
        max-width: 60%;
    }
    
    .btn-continue {
        background: black;
        border: none;
        color: white;
        padding: 12px 30px;
        font-weight: 600;
        font-size: 1rem;
        border-radius: 30px;
        margin-top: 30px;
        transition: all 0.3s ease;
        display: inline-block;
        text-decoration: none;
    }
    
    .btn-continue:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        color: white;
        text-decoration: none;
    }
    
    /* Mobile Responsive Styles */
    @media (max-width: 767px) {
        .confirmation-page {
            padding: 20px 0;
        }
        
        .page-title {
            font-size: 1.8rem;
            margin-bottom: 20px;
        }
        
        .confirmation-container {
            padding: 25px 15px;
        }
        
        .success-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        .confirmation-message {
            font-size: 1.2rem;
        }
        
        .order-number {
            font-size: 1rem;
            margin-bottom: 20px;
        }
        
        .order-details {
            margin-top: 20px;
            padding-top: 20px;
        }
        
        .detail-row {
            flex-direction: column;
            margin-bottom: 12px;
        }
        
        .detail-label {
            margin-bottom: 5px;
        }
        
        .detail-value {
            text-align: left;
            max-width: 100%;
            word-break: break-word;
        }
        
        .btn-continue {
            width: 100%;
            padding: 14px 20px;
        }
    }
    
    @media (max-width: 480px) {
        .page-title {
            font-size: 1.5rem;
        }
        
        .confirmation-message {
            font-size: 1.1rem;
        }
        
        .detail-row {
            font-size: 0.9rem;
        }
    }
</style>

<div class="bredcrumbWrap bredcrumb-style2">
    <div class="container breadcrumbs">
        <a href="<?php echo $url_domain; ?>/index" title="Back to the home page">Home</a>
        <span aria-hidden="true">|</span>
        <span class="title-bold">Order Confirmation</span>
    </div>
</div>

<div class="confirmation-page">
    <div class="container">
        <h1 class="page-title">Order Confirmation</h1>
        
        <div class="confirmation-container">
            <?php if ($order['payment_method'] === 'qr_transfer'): ?>
            <div class="success-icon" style="color:#ff6b35;"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></div>
            <div class="confirmation-message">Payment Proof Received!</div>
            <div class="order-number">Order Number : <?php echo $order['order_number']; ?></div>
            <div style="background:#fff8f5;border:2px solid #ff6b35;border-radius:12px;padding:16px 20px;margin-bottom:20px;text-align:left;">
                <p style="margin:0 0 8px;font-weight:700;color:#ff6b35;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Awaiting Payment Verification</p>
                <p style="margin:0;color:#555;font-size:14px;">Your order is placed and payment screenshot has been received. Our team will verify your payment within <strong>1–2 hours</strong> and confirm your order. You will receive a WhatsApp/SMS notification once verified.</p>
            </div>
            <?php elseif (isset($_GET['paid']) && $_GET['paid'] == 1): ?>
            <div class="success-icon" style="color:#28a745;"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>
            <div class="confirmation-message">Payment Successful!</div>
            <div class="order-number">Order Number : <?php echo $order['order_number']; ?></div>
            <p class="mb-4">Your payment was confirmed and your order is being processed. You will receive an email with your order details.</p>
            <?php else: ?>
            <div class="success-icon">
                <i class="an an-check-circle"></i>
            </div>
            <div class="confirmation-message">Thank you for your order!</div>
            <div class="order-number">Order Number : <?php echo $order['order_number']; ?></div>
            <p class="mb-4">Your order has been successfully placed and will be processed shortly. You will receive an email confirmation with your order details.</p>
            <?php endif; ?>
            
            <div class="order-details">
                <h4 class="mb-4">Order Details</h4>
                
                <div class="detail-row">
                    <span class="detail-label">Customer Name :</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Email :</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order['email']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Phone :</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order['phone']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Shipping Address :</span>
                    <span class="detail-value">
                        <?php echo htmlspecialchars($order['address']); ?>, 
                        <?php echo htmlspecialchars($order['city']); ?>, 
                        <?php echo htmlspecialchars($order['state']); ?>, 
                        <?php echo htmlspecialchars($order['country']); ?> - 
                        <?php echo htmlspecialchars($order['postal_code']); ?>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Payment Method :</span>
                    <span class="detail-value"><?php echo match($order['payment_method']) {
                        'cod'         => 'Cash on Delivery',
                        'jazzcash'    => 'JazzCash',
                        'stripe'      => 'Credit/Debit Card (Stripe)',
                        'qr_transfer' => 'Bank Transfer / QR Code',
                        'easypaisa'   => 'EasyPaisa',
                        default       => 'Online Payment'
                    }; ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Order Status :</span>
                    <span class="detail-value"><?php echo ucfirst($order['order_status']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Total Amount :</span>
                    <span class="detail-value">RS. <?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>
            
            <a href="<?php echo $url_domain; ?>/index" class="btn-continue">Continue Shopping</a>
        </div>
    </div>
</div>
<?php if (!empty($order) && $order['order_status'] !== 'cancelled'): ?>
<!-- Meta Pixel Purchase Event -->
<script>
fbq('track', 'Purchase', {
    value: <?php echo json_encode((float)$order['total_amount']); ?>,
    currency: 'PKR',
    content_type: 'product',
    content_ids: [<?php echo implode(',', array_map(fn($i) => json_encode((string)$i['product_id']), $orderItems)); ?>],
    num_items: <?php echo count($orderItems); ?>
});
</script>
<?php endif; ?>
<?php include 'include/footer.php'; ?>