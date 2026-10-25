<?php
    include 'include/head.php';
    include 'include/navbar.php';

    $cartItems = getCartItems();
    $cartTotal = getCartTotal();
    $cartSubtotal = getCartSubtotal();
    $cartShipping = getCartShipping();
    $cartCount = getCartItemCount();
?>
<style>
    /* Cart Page Styles */
    .cart-page {
        padding: 40px 0;
        background-color: #f8f9fa;
    }

    .cart-empty {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    
    .cart-empty h3 {
        font-size: 1.8rem;
        margin-bottom: 20px;
        color: #333;
    }
    
    .cart-empty p {
        font-size: 1.1rem;
        color: #666;
        margin-bottom: 30px;
    }
    
    .cart-table {
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    
    .cart-table table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
    }
    
    .cart-table thead {
        background-color: #f1f1f1;
    }
    
    .cart-table th {
        padding: 15px;
        text-align: left;
        font-weight: 600;
        color: #333;
        font-size: 1rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .cart-table td {
        padding: 20px 15px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f1f1;
    }
    
    .cart-table tbody tr:last-child td {
        border-bottom: none;
    }
    
    .cart-table .product-info {
        display: flex;
        align-items: center;
    }
    
    .cart-table .product-image {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 8px;
        margin-right: 15px;
    }
    
    .cart-table .product-details p {
        font-weight: 600;
        color: #333;
        text-decoration: none;
        font-size: 1.1rem;
        display: block;
        margin-bottom: 5px;
    }
    
    .cart-table .product-details p:hover {
        color: black;
    }
    
    .cart-table .product-details .text-muted {
        font-size: 0.9rem;
        color: #666;
    }
    
    /* Color swatch styles */
    .color-swatch {
        display: inline-flex;
        align-items: center;
        margin-right: 8px;
        margin-bottom: 4px;
    }
    
    .color-swatch-circle {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        margin-right: 5px;
        border: 1px solid #ddd;
        display: inline-block;
    }
    
    .cart-table .price {
        font-weight: 600;
        font-size: 1.1rem;
        color: #333;
    }
    
    .cart-table .qtyField {
        display: flex;
        align-items: center;
        border: 1px solid #ddd;
        border-radius: 30px;
        overflow: hidden;
        width: 120px;
    }
    
    .cart-table .qtyBtn {
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 20px;
        font-weight: bold;
        color: #333;
        text-decoration: none;
        user-select: none;
    }
    
    .cart-table .qtyBtn:hover {
        background: black;
        color: white;
    }
    
    .cart-table .qtyBtn.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none; /* This prevents click events */
    }
    
    .cart-table .qtyField input {
        width: 50px;
        height: 35px;
        border: none;
        text-align: center;
        font-weight: 600;
        background: transparent;
    }
    
    .cart-table .total {
        font-weight: 700;
        font-size: 1.2rem;
        color: #333;
    }
    
    .cart-table .remove-btn {
        background: #f8f9fa;
        border: 1px solid #ddd;
        color: #dc3545;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    
    .cart-table .remove-btn:hover {
        background: #dc3545;
        color: white;
        border-color: #dc3545;
    }
    
    .cart-summary {
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        padding: 25px;
        height: 100%;
    }
    
    .cart-summary h5 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 20px;
        color: #333;
        position: relative;
        padding-bottom: 15px;
    }
    
    .cart-summary h5:after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 0;
        width: 40px;
        height: 3px;
        background: black;
        border-radius: 2px;
    }
    
    .cart-summary .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        font-size: 1rem;
    }
    
    .cart-summary .summary-row.total {
        font-weight: 700;
        font-size: 1.2rem;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #f1f1f1;
    }
    
    .cart-summary .btn-checkout {
        background: black;
        border: none;
        color: white;
        padding: 12px 20px;
        font-weight: 600;
        border-radius: 30px;
        width: 100%;
        margin-top: 20px;
        transition: all 0.3s ease;
    }
     
    .btn-continue {
        background: white;
        border: 2px solid black;
        color: black;
        padding: 12px 25px;
        font-weight: 600;
        border-radius: 30px;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
    }
    
    .btn-continue:hover {
        background: black;
        color: white;
        transform: translateY(-3px);
    }
    
    /* Notification styles */
    .notification-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
    }
    
    .notification {
        padding: 15px 20px;
        margin-bottom: 10px;
        border-radius: 4px;
        color: white;
        font-weight: bold;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        animation: slideIn 0.3s ease-out;
        display: flex;
        align-items: center;
    }
    
    .notification-success {
        background-color: #28a745;
    }
    
    .notification-error {
        background-color: #dc3545;
    }
    
    .notification i {
        margin-right: 10px;
        font-size: 20px;
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    /* Responsive adjustments */
    @media (max-width: 992px) {
        .cart-summary {
            margin-top: 30px;
        }
    }
    
    @media (max-width: 768px) {
        .page-title {
            font-size: 2rem;
        }
        
        .cart-table {
            overflow-x: auto;
        }
        
        .cart-table table {
            min-width: 600px;
        }
        
        .cart-table .product-image {
            width: 60px;
            height: 60px;
        }
        
        .cart-table .product-details a {
            font-size: 1rem;
        }
        
        .cart-summary {
            padding: 20px;
        }
    }
    
    @media (max-width: 576px) {
        .cart-page {
            padding: 20px 0;
        }
        
        .page-title {
            font-size: 1.8rem;
            margin-bottom: 20px;
        }
        
        .cart-empty {
            padding: 40px 15px;
        }
        
        .cart-empty h3 {
            font-size: 1.5rem;
        }
        
        .cart-table th, .cart-table td {
            padding: 10px;
        }
        
        .cart-table .qtyField {
            width: 100px;
        }
        
        .cart-table .qtyField .qtyBtn {
            width: 30px;
            height: 30px;
            font-size: 18px;
        }
        
        .cart-table .qtyField input {
            width: 40px;
            height: 30px;
        }
        
        /* Mobile-specific cart table adjustments */
        .cart-table {
            overflow-x: scroll;
            -webkit-overflow-scrolling: touch;
        }
        
        .cart-table table {
            min-width: 500px;
        }
        
        .cart-table .product-image {
            width: 50px;
            height: 50px;
            flex-shrink: 0; /* Prevent image from shrinking on mobile */
        }
        
        .cart-table .product-details {
            max-width: 120px; /* Limit width on mobile */
            min-width: 0; /* Allow text truncation */
        }
        
        .cart-table .product-details p {
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }
        
        .cart-table .product-details .text-muted {
            font-size: 0.8rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .cart-table .price, .cart-table .total {
            font-size: 0.9rem;
        }
        
        .cart-table .remove-btn {
            width: 30px;
            height: 30px;
        }
        
        /* Mobile color swatch adjustments */
        .color-swatch {
            margin-right: 5px;
            margin-bottom: 2px;
        }
        
        .color-swatch-circle {
            width: 14px;
            height: 14px;
        }
    }
</style>

<!-- Notification Container -->
<div id="notification-container" class="notification-container"></div>

<div class="page section-header text-center mb-0">
    <div class="page-title">
        <div class="wrapper"><h1 class="page-width">Cart</h1></div>
    </div>
</div>

<div class="bredcrumbWrap bredcrumb-style2">
    <div class="container breadcrumbs">
        <a href="<?php echo $url_domain; ?>/index" title="Back to the home page">Home</a>
        <span aria-hidden="true">|</span>
        <span class="title-bold">Shopping Cart</span>
    </div>
</div>

<div class="cart-page">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="page-title">Shopping Cart</h1>
                
                <?php if (empty($cartItems)): ?>
                    <div class="cart-empty">
                        <i class="icon an an-shopping-cart" style="font-size: 4rem; color: #ddd; margin-bottom: 20px;"></i>
                        <h3>Your shopping cart is empty</h3>
                        <p>Looks like you haven't added any items to your cart yet.</p>
                        <a href="<?php echo $url_domain; ?>/index" class="btn btn-primary">
                            <i class="icon an an-arrow-left me-2"></i> Continue Shopping
                        </a>
                    </div>
                <?php else: ?>
                    <div class="cart-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $cartKey => $item): 
                                    $imageParts = explode(',', $item['image']);
                                    $firstImage = trim($imageParts[0]);
                                    // Calculate item total without shipping (shipping will be added once in summary)
                                    $itemTotal = ($item['price'] + $item['stitching_price']) * $item['quantity'];
                                    
                                    // Process colors if available
                                    $colorHtml = '';
                                    if (!empty($item['colors'])) {
                                        $colorNames = explode(',', $item['colors']);
                                        $colorHtml = '<div class="text-muted">Colors: ';
                                        foreach ($colorNames as $colorName) {
                                            $colorHtml .= '<span class="color-swatch"><span class="color-swatch-circle" style="background-color: ' . htmlspecialchars($colorName) . ';"></span>' . htmlspecialchars($colorName) . '</span>';
                                        }
                                        $colorHtml .= '</div>';
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <div class="product-info">
                                            <img src="<?php echo $url_domain; ?>/admin/images/product/<?php echo $firstImage; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-image">
                                            <div class="product-details">
                                                <p title="<?php echo htmlspecialchars($item['name']); ?>"><?php echo htmlspecialchars($item['name']); ?></p>
                                                <?php if (!empty($item['sizes'])): ?>
                                                    <div class="text-muted">Size: <?php echo htmlspecialchars($item['sizes']); ?></div>
                                                <?php endif; ?>
                                                <?php echo $colorHtml; ?>
                                                <?php if ($item['stitching'] == 1): ?>
                                                    <div class="text-muted">With Stitching</div>
                                                <?php endif; ?>
                                                <?php if ($item['price_type'] === 'set'): ?>
                                                    <div class="text-muted badge bg-info">Set Suit (<?php echo $item['setsuite_quantity']; ?> pieces)</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="price">RS. <?php echo number_format($item['price'] + $item['stitching_price'], 2); ?></td>
                                    <td>
                                        <div class="qtyField">
                                            <a class="qtyBtn minus" href="javascript:void(0);" onclick="updateCartQuantity('<?php echo $cartKey; ?>', <?php echo $item['quantity'] - 1; ?>)">
                                                &#8722;
                                            </a>
                                            <input type="text" name="quantity" value="<?php echo $item['quantity']; ?>" class="qty" readonly>
                                            <?php if ($item['quantity'] < 5): ?>
                                                <a class="qtyBtn plus" href="javascript:void(0);" onclick="updateCartQuantity('<?php echo $cartKey; ?>', <?php echo $item['quantity'] + 1; ?>)">
                                                    &#43;
                                                </a>
                                            <?php else: ?>
                                                <a class="qtyBtn plus disabled" href="javascript:void(0);">
                                                    &#43;
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="total">RS. <?php echo number_format($itemTotal, 2); ?></td>
                                    <td>
                                        <a href="javascript:void(0);" onclick="removeFromCart('<?php echo $cartKey; ?>')" class="remove-btn">
                                            <i class="an an-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <a href="<?php echo $url_domain; ?>/index" class="btn-continue">
                                <i class="an an-arrow-left me-2"></i> Continue Shopping
                            </a>
                        </div>
                        <div class="col-md-6">
                            <div class="cart-summary">
                                <h5>Cart Summary</h5>
                                <div class="summary-row">
                                    <span>Subtotal:</span>
                                    <span>RS. <?php echo number_format($cartSubtotal, 2); ?></span>
                                </div>
                                <div class="summary-row">
                                    <span>Shipping:</span>
                                    <span>RS. <?php echo number_format($cartShipping, 2); ?></span>
                                </div>
                                <div class="summary-row total mb-5">
                                    <span>Total:</span>
                                    <span>RS. <?php echo number_format($cartTotal, 2); ?></span>
                                </div>
                                <div class="text-end">
                                    <a href="<?php echo $url_domain; ?>/checkout" class="btn-checkout">
                                        Proceed to Checkout <i class="an an-arrow-right ms-2"></i>
                                    </a>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Notification function
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        // Add icon based on type
        let icon = '';
        if (type === 'success') {
            icon = '<i class="icon an an-check-circle"></i>';
        } else if (type === 'error') {
            icon = '<i class="icon an an-times-circle"></i>';
        }
        
        notification.innerHTML = `${icon} ${message}`;
        
        const container = document.getElementById('notification-container');
        container.appendChild(notification);
        
        // Remove notification after 3 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
    
    function updateCartQuantity(cartKey, quantity) {
        // Check if quantity is less than 1
        if (quantity < 1) return;
        
        // Check if quantity exceeds maximum (5)
        if (quantity > 5) {
            showNotification('Maximum quantity allowed is 5', 'error');
            return;
        }
        
        fetch('<?php echo $url_domain; ?>/update_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'update_quantity',
                cart_key: cartKey,
                quantity: quantity
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload(); // Simple solution - reload page to update cart
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred while updating the quantity.', 'error');
        });
    }

    function removeFromCart(cartKey) {
        // Create a custom confirmation dialog
        const confirmDialog = document.createElement('div');
        confirmDialog.className = 'confirm-dialog';
        confirmDialog.innerHTML = `
            <div class="confirm-content">
                <h4>Remove Item</h4>
                <p>Are you sure you want to remove this item from your cart?</p>
                <div class="confirm-buttons">
                    <button class="btn btn-cancel">Cancel</button>
                    <button class="btn btn-confirm">Remove</button>
                </div>
            </div>
        `;
        
        // Add styles for the confirmation dialog
        const style = document.createElement('style');
        style.textContent = `
            .confirm-dialog {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
            }
            .confirm-content {
                background-color: white;
                padding: 25px;
                border-radius: 10px;
                max-width: 400px;
                width: 90%;
                text-align: center;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            }
            .confirm-content h4 {
                margin-top: 0;
                margin-bottom: 15px;
                color: #333;
            }
            .confirm-content p {
                margin-bottom: 20px;
                color: #666;
            }
            .confirm-buttons {
                display: flex;
                justify-content: center;
                gap: 10px;
            }
            .confirm-buttons .btn {
                padding: 8px 20px;
                border-radius: 4px;
                border: none;
                cursor: pointer;
                font-weight: 600;
            }
            .btn-cancel {
                background-color: #f1f1f1;
                color: #333;
            }
            .btn-confirm {
                background-color: #dc3545;
                color: white;
            }
        `;
        document.head.appendChild(style);
        document.body.appendChild(confirmDialog);
        
        // Handle button clicks
        const cancelBtn = confirmDialog.querySelector('.btn-cancel');
        const confirmBtn = confirmDialog.querySelector('.btn-confirm');
        
        cancelBtn.addEventListener('click', function() {
            document.body.removeChild(confirmDialog);
            document.head.removeChild(style);
        });
        
        confirmBtn.addEventListener('click', function() {
            document.body.removeChild(confirmDialog);
            document.head.removeChild(style);
            
            fetch('<?php echo $url_domain; ?>/update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'remove',
                    cart_key: cartKey
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Item removed from cart successfully!');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while removing the item from the cart.', 'error');
            });
        });
    }
</script>

<?php include 'include/footer.php'; ?>