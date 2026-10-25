<?php
    // Production: errors are logged, never displayed to users
    error_reporting(0);
    ini_set('display_errors', 0);

    include 'include/head.php';
    if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

    // Allow clearing a stuck coupon from session via GET param
    if (isset($_GET['clear_coupon'])) {
        unset($_SESSION['applied_coupon']);
        header('Location: checkout.php');
        exit;
    }

    include 'include/navbar.php';

    // Get cart items
    $cartItems = getCartItems();
    $cartTotal = getCartTotal();

    if (empty($cartItems)) {
        header('Location: ' . $url_domain . '/cart.php');
        exit;
    }

    // Check if user is logged in
    $isLoggedIn = false;
    $userData = [];
    if (isset($_SESSION['WEBSITE-LOGINSS']) && !empty($_SESSION['WEBSITE-LOGINSS'])) {
        $userId = $_SESSION['WEBSITE-LOGINSS'];
        $userQuery = "SELECT * FROM customers WHERE id = '$userId'";
        $userResult = $connection_database->query($userQuery);
        
        if ($userResult && $userResult->num_rows > 0) {
            $userData = $userResult->fetch_assoc();
            $isLoggedIn = true;
        }
    }

    // Function to clear cart from database and session
    function clearCart() {
        global $connection_database;
        
        // Get user ID
        $userId = getUserId();
        
        // Delete all cart items for this user from database
        $deleteQuery = "DELETE FROM cart WHERE user_id = '$userId'";
        $connection_database->query($deleteQuery);
        
        // Clear session cart
        $_SESSION['cart'] = array();
        
        return true;
    }

    /**
     * Sends an email using PHP's built-in mail() function with proper headers.
     * The key to external delivery is setting the envelope sender with the -f parameter.
     *
     * @param string $to The recipient's email address.
     * @param string $subject The email subject.
     * @param string $emailContent The HTML content of the email.
     * @return bool True on success, false on failure.
     */
    function sendOrderConfirmationEmail($to, $subject, $emailContent) {
        // Use a domain email to improve deliverability
        $from_email = "kapraaofficial@gmail.com";
        $from_name = "Kapraa";
        
        // Set additional headers to improve deliverability
        $headers = "From: " . $from_name . " <" . $from_email . ">\r\n";
        $headers .= "Reply-To: " . $from_email . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        
        // --- CRITICAL CHANGE ---
        // This sets the "envelope sender" (-f). It tells external mail servers that this
        // email is legitimately from alazeemservices.net. This MUST match the From address.
        // This may be disabled on some shared hosts for security reasons.
        $params = "-f" . $from_email;
        
        // Log the attempt for debugging
        error_log("Attempting to send email to: " . $to);
        
        // The @ symbol suppresses the default PHP warning, as we handle errors manually below.
        $result = @mail($to, $subject, $emailContent, $headers, $params);
        
        // Log result and any errors from the mail server
        if ($result) {
            error_log("Email sent successfully to: " . $to);
            return true;
        } else {
            error_log("FAILED to send email to: " . $to);
            // Get the last error, which might contain a message from the mail server
            $error = error_get_last();
            if ($error && !empty($error['message'])) {
                 error_log("Mail server error: " . $error['message']);
            }
            return false;
        }
    }

    // Check for applied coupon from session
    $couponCode = '';
    $couponDiscount = 0;
    if (isset($_SESSION['applied_coupon']) && !empty($_SESSION['applied_coupon'])) {
        $ac = $_SESSION['applied_coupon'];
        $couponCode = $connection_database->real_escape_string($ac['code'] ?? '');
        $couponDiscount = floatval($ac['discount_amount'] ?? 0);
    }
    $finalTotal = $cartTotal - $couponDiscount;
    if ($finalTotal < 0) $finalTotal = 0;

    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            // Use existing database connection from config
            global $connection_database;
            require_once __DIR__ . '/payment/config.php';
            
            // Bot trap
if (!empty($_POST['website_url'])) {
    http_response_code(400);
    exit;
}

// Security token
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    throw new Exception("Session expired. Please refresh the page and try again.");
}

// Check required fields
$vName    = trim($_POST['name'] ?? '');
$vPhone   = preg_replace('/[\s\-]/', '', $_POST['phone'] ?? '');
$vCity    = trim($_POST['city'] ?? '');
$vAddress = trim($_POST['address'] ?? '');
$vCountry = trim($_POST['country'] ?? '');

if (mb_strlen($vName) < 3)     throw new Exception("Please enter your full name.");
if (!preg_match('/^(\+92|0092|92|0)3\d{9}$/', $vPhone)) throw new Exception("Please enter a valid mobile number (03XXXXXXXXX).");
if (mb_strlen($vCity) < 2)     throw new Exception("Please enter your city.");
if (mb_strlen($vAddress) < 10) throw new Exception("Please enter your complete address.");
if ($vCountry === '')          throw new Exception("Please select your country.");

$email = trim($_POST['email'] ?? '');
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new Exception("Please enter a valid email address.");
}
            
            

            // Add coupon columns only if they don't already exist (safe one-time migration)
            $colCheck = $connection_database->query("SHOW COLUMNS FROM orders LIKE 'coupon_code'");
            if ($colCheck && $colCheck->num_rows === 0) {
                $connection_database->query("ALTER TABLE orders ADD COLUMN coupon_code VARCHAR(50) DEFAULT '' AFTER notes");
                $connection_database->query("ALTER TABLE orders ADD COLUMN coupon_discount DECIMAL(10,2) DEFAULT 0 AFTER coupon_code");
            }

            // Re-read coupon from session (POST context)
            $couponCode = '';
            $couponDiscount = 0;
            if (isset($_SESSION['applied_coupon']) && !empty($_SESSION['applied_coupon'])) {
                $ac = $_SESSION['applied_coupon'];
                $couponCode = $connection_database->real_escape_string($ac['code'] ?? '');
                $couponDiscount = floatval($ac['discount_amount'] ?? 0);
            }
            $finalTotal = $cartTotal - $couponDiscount;
            if ($finalTotal < 0) $finalTotal = 0;
            
            // Check database connection
            if (!$connection_database) {
                throw new Exception("Database connection failed");
            }
            
            // Generate unique order number
            $orderNumber = 'ORD-' . date('Y') . '-' . strtoupper(uniqid());
            
            // Get form data and sanitize
            $name = mysqli_real_escape_string($connection_database, $_POST['name']);
            $email = mysqli_real_escape_string($connection_database, $_POST['email'] ?? '');
            $phone = mysqli_real_escape_string($connection_database, $_POST['phone']);
            $country = mysqli_real_escape_string($connection_database, $_POST['country']);
            $state = mysqli_real_escape_string($connection_database, $_POST['state'] ?? '');
            $city = mysqli_real_escape_string($connection_database, $_POST['city']);
            $postalCode = mysqli_real_escape_string($connection_database, $_POST['postal_code'] ?? '');
            $address = mysqli_real_escape_string($connection_database, $_POST['address']);
            $notes = isset($_POST['notes']) ? mysqli_real_escape_string($connection_database, $_POST['notes']) : '';
            $rawMethod     = $_POST['payment_method'] ?? 'cod';
            $allowedMethods = ['cod','qr_transfer']; // stripe disabled
            $paymentMethod = in_array($rawMethod, $allowedMethods) ? $rawMethod : 'cod';
            $paymentMethod = mysqli_real_escape_string($connection_database, $paymentMethod);
            
            // Calculate shipping and tax (if applicable)
            $shippingCost = 0; // You can modify this based on your shipping logic
            $tax = 0; // You can modify this based on your tax logic
            
            // Start transaction
            mysqli_begin_transaction($connection_database);
            
            // Insert order into orders table
            $insertOrderQuery = "INSERT INTO orders (order_number, customer_name, email, phone, country, state, city, postal_code, address, notes, coupon_code, coupon_discount, payment_method, subtotal, shipping_cost, tax, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($connection_database, $insertOrderQuery);
            if (!$stmt) {
                throw new Exception("Failed to prepare order statement: " . mysqli_error($connection_database));
            }

            mysqli_stmt_bind_param($stmt, "sssssssssssdsdddd", $orderNumber, $name, $email, $phone, $country, $state, $city, $postalCode, $address, $notes, $couponCode, $couponDiscount, $paymentMethod, $cartTotal, $shippingCost, $tax, $finalTotal);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error inserting order: " . mysqli_error($connection_database));
            }
            
            $orderId = mysqli_insert_id($connection_database);
            
            // Insert order items
            foreach ($cartItems as $item) {
                $imageParts = explode(',', $item['image']);
                $firstImage = trim($imageParts[0]);
                $itemTotal = ($item['price'] + $item['stitching_price'] + $item['shipping_cost']) * $item['quantity'];
                
                // Get color information if available
                $colors = isset($item['colors']) ? $item['colors'] : '';
                
                $insertItemQuery = "INSERT INTO order_items (order_id, product_id, product_name, product_image, size, colors, quantity, price, stitching_price, shipping_cost, total_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = mysqli_prepare($connection_database, $insertItemQuery);
                if (!$stmt) {
                    throw new Exception("Failed to prepare item statement: " . mysqli_error($connection_database));
                }
                
                mysqli_stmt_bind_param($stmt, "iissssidddd", $orderId, $item['id'], $item['name'], $firstImage, $item['sizes'], $colors, $item['quantity'], $item['price'], $item['stitching_price'], $item['shipping_cost'], $itemTotal);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error inserting order item: " . mysqli_error($connection_database));
                }
            }
            
            // Commit transaction
            if (!mysqli_commit($connection_database)) {
                throw new Exception("Error committing transaction: " . mysqli_error($connection_database));
            }
            unset($_SESSION['csrf_token']);
            // Increment coupon used_count and clear session coupon
            if ($couponCode) {
                $connection_database->query("UPDATE coupons SET used_count = used_count + 1 WHERE code = '$couponCode'");
                unset($_SESSION['applied_coupon']);
            }

            // ── Loyalty Points ───────────────────────────────────────
            // Create tables if not yet created on this server
            $connection_database->query("CREATE TABLE IF NOT EXISTS loyalty_scores (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL,
                score DECIMAL(6,2) DEFAULT 0,
                total_orders INT DEFAULT 0,
                is_winner TINYINT(1) DEFAULT 0,
                winner_notified TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_customer (customer_id)
            )");
            $connection_database->query("CREATE TABLE IF NOT EXISTS loyalty_transactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL,
                order_id INT NOT NULL,
                points_earned DECIMAL(5,2) DEFAULT 0,
                score_before DECIMAL(5,2) DEFAULT 0,
                score_after DECIMAL(5,2) DEFAULT 0,
                description VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            if (isset($_SESSION['WEBSITE-LOGINSS']) && $orderId) {
                $loyCustomerId = (int)$_SESSION['WEBSITE-LOGINSS'];
                $loyItemCount  = count($cartItems);

                // Determine base points: multi-item or any product_type suit/set = 15, else 5
                $isSuitOrder = $loyItemCount > 1;
                if (!$isSuitOrder) {
                    foreach ($cartItems as $ci) {
                        $pt = strtolower($ci['product_type'] ?? '');
                        if (strpos($pt,'suit')!==false || strpos($pt,'set')!==false || strpos($pt,'salwar')!==false) {
                            $isSuitOrder = true; break;
                        }
                    }
                }
                $basePoints = $isSuitOrder ? 15 : 5;

                // Get current score
                $loyRow = mysqli_fetch_assoc(mysqli_query($connection_database,
                    "SELECT * FROM loyalty_scores WHERE customer_id=$loyCustomerId LIMIT 1"));
                $scoreBefore = $loyRow ? (float)$loyRow['score'] : 0;

                // Slow-fill rules: >=80 → +1, >=50 → +2, else base points
                if ($scoreBefore >= 80) {
                    $earnedPoints = 1;
                    $desc = 'Order reward (+1 pt, final stretch)';
                } elseif ($scoreBefore >= 50) {
                    $earnedPoints = 2;
                    $desc = 'Order reward (+2 pts, halfway boost)';
                } else {
                    $earnedPoints = $basePoints;
                    $desc = $isSuitOrder ? 'Suit/Set order (+15 pts)' : 'Single product order (+5 pts)';
                }

                $scoreAfter = min(100, $scoreBefore + $earnedPoints);
                $isWinner   = $scoreAfter >= 100 ? 1 : 0;

                if ($loyRow) {
                    $connection_database->query(
                        "UPDATE loyalty_scores SET score=$scoreAfter, total_orders=total_orders+1, is_winner=$isWinner, updated_at=NOW()
                         WHERE customer_id=$loyCustomerId");
                } else {
                    $connection_database->query(
                        "INSERT INTO loyalty_scores (customer_id, score, total_orders, is_winner)
                         VALUES ($loyCustomerId, $scoreAfter, 1, $isWinner)");
                }

                $safeDesc = $connection_database->real_escape_string($desc);
                $connection_database->query(
                    "INSERT INTO loyalty_transactions (customer_id, order_id, points_earned, score_before, score_after, description)
                     VALUES ($loyCustomerId, $orderId, $earnedPoints, $scoreBefore, $scoreAfter, '$safeDesc')");
            }
            // ── End Loyalty Points ───────────────────────────────────

            // --- EMAIL SENDING SECTION ---
            
            // 1. Send email to the USER
            $userSubject = "Order Confirmation - " . $orderNumber;
            $userEmailContent = "<html><body>";
            $userEmailContent .= "<h2>Order Confirmation</h2>";
            $userEmailContent .= "<p>Thank you for your order, " . htmlspecialchars($name) . "!</p>";
            $userEmailContent .= "<p>Your order number is: <strong>" . $orderNumber . "</strong></p>";
            $userEmailContent .= "<h3>Order Details:</h3>";
            $userEmailContent .= "<table border='1' cellpadding='10' cellspacing='0' width='100%'>";
            $userEmailContent .= "<tr><th>Product</th><th>Size</th><th>Quantity</th><th>Price</th></tr>";
            
            foreach ($cartItems as $item) {
                $imageParts = explode(',', $item['image']);
                $firstImage = trim($imageParts[0]);
                $itemTotal = ($item['price'] + $item['stitching_price'] + $item['shipping_cost']) * $item['quantity'];
                
                $userEmailContent .= "<tr>";
                $userEmailContent .= "<td>" . htmlspecialchars($item['name']) . "</td>";
                $userEmailContent .= "<td>" . htmlspecialchars($item['sizes']) . "</td>";
                $userEmailContent .= "<td>" . $item['quantity'] . "</td>";
                $userEmailContent .= "<td>RS. " . number_format($itemTotal, 2) . "</td>";
                $userEmailContent .= "</tr>";
            }
            
            $userEmailContent .= "</table>";
            $userEmailContent .= "<h3>Shipping Information:</h3>";
            $userEmailContent .= "<p>" . htmlspecialchars($name) . "<br>";
            $userEmailContent .= htmlspecialchars($address) . "<br>";
            $userEmailContent .= htmlspecialchars($city) . ", " . htmlspecialchars($state) . "<br>";
            $userEmailContent .= htmlspecialchars($country) . " - " . htmlspecialchars($postalCode) . "</p>";
            $userEmailContent .= "<p>Phone: " . htmlspecialchars($phone) . "</p>";
            $userEmailContent .= "<p>Payment Method: " . (match($paymentMethod) { 'cod'=>'Cash on Delivery', 'stripe'=>'Credit/Debit Card (Stripe)', 'qr_transfer'=>'Bank Transfer / QR Code', default=>'Online Payment' }) . "</p>";
            $userEmailContent .= "<h3>Order Total: RS. " . number_format($finalTotal, 2) . "</h3>";
            $userEmailContent .= "<p>We will send you another email when your order ships.</p>";
            $userEmailContent .= "<p>Thank you for shopping with us!</p>";
            $userEmailContent .= "</body></html>";
            
            $mailSentToUser = sendOrderConfirmationEmail($email, $userSubject, $userEmailContent);

            // 2. Send notification email to the ADMIN
            $adminEmail = "contact@kapraa.pk"; // Or another admin email
            $adminSubject = "New Order Received - " . $orderNumber;
            $adminEmailContent = "<html><body>";
            $adminEmailContent .= "<h2>New Order Received</h2>";
            $adminEmailContent .= "<p><strong>Order Number:</strong> " . $orderNumber . "</p>";
            $adminEmailContent .= "<p><strong>Customer:</strong> " . htmlspecialchars($name) . " (" . htmlspecialchars($email) . ")</p>";
            $adminEmailContent .= "<p><strong>Phone:</strong> " . htmlspecialchars($phone) . "</p>";
            $adminEmailContent .= "<p><strong>Total Amount:</strong> RS. " . number_format($finalTotal, 2) . "</p>";
            $adminEmailContent .= "<p><strong>Payment Method:</strong> " . (match($paymentMethod) { 'cod'=>'Cash on Delivery', 'stripe'=>'Credit/Debit Card (Stripe)', 'qr_transfer'=>'Bank Transfer / QR Code', default=>'Online Payment' }) . "</p>";
            $adminEmailContent .= "<p>Check the admin panel for full details.</p>";
            $adminEmailContent .= "</body></html>";

            $mailSentToAdmin = sendOrderConfirmationEmail($adminEmail, $adminSubject, $adminEmailContent);
            
            // --- END EMAIL SECTION ---

            // ── Route based on payment method ─────────────────
            if ($paymentMethod === 'cod') {
                // Cash on Delivery — complete order immediately
                $cartCleared = clearCart();
                $_SESSION['order_success'] = true;
                $_SESSION['order_number']  = $orderNumber;
                $_SESSION['cart_cleared']  = $cartCleared;
                $redirectUrl = $url_domain . '/order-confirmation.php?order=' . $orderNumber;

            } elseif ($paymentMethod === 'qr_transfer') {
                // QR / Bank Transfer — user uploads payment screenshot
                $proofSaved = false;
                if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir  = __DIR__ . '/uploads/payment_proofs/';
                    $ext        = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
                    $allowedExt = ['jpg','jpeg','png','gif','webp'];
                    if (in_array($ext, $allowedExt) && $_FILES['payment_proof']['size'] <= 5 * 1024 * 1024) {
                        $filename = 'proof_' . preg_replace('/[^a-z0-9]/i', '_', $orderNumber) . '_' . time() . '.' . $ext;
                        if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $uploadDir . $filename)) {
                            $proofPath = 'uploads/payment_proofs/' . $filename;
                            $connection_database->query(
                                "UPDATE orders SET payment_proof='" . $connection_database->real_escape_string($proofPath) . "' WHERE order_number='" . $connection_database->real_escape_string($orderNumber) . "'"
                            );
                            $proofSaved = true;
                        }
                    }
                }
                clearCart();
                $_SESSION['order_success'] = true;
                $_SESSION['order_number']  = $orderNumber;
                $redirectUrl = $url_domain . '/order-confirmation.php?order=' . $orderNumber . '&qr=1';

            } else {
                // Online payment gateway (stripe only)
                $_SESSION['pending_order_id']     = $orderId;
                $_SESSION['pending_order_number'] = $orderNumber;
                $_SESSION['pending_order_amount'] = $finalTotal;
                $_SESSION['pending_order_email']  = $email;
                $redirectUrl = $url_domain . '/payment/' . $paymentMethod . '.php';
            }

            if (!headers_sent()) {
                header('Location: ' . $redirectUrl);
                exit;
            } else {
                echo '<script>window.location.href = "' . $redirectUrl . '";</script>';
                exit;
            }
            
        } catch (Exception $e) {
            // Rollback transaction on error
            if (isset($connection_database) && mysqli_ping($connection_database)) {
                mysqli_rollback($connection_database);
            }
            
            // Store error message to display on page
            $errorMessage = "There was an error processing your order. Please try again. Error: " . $e->getMessage();
            
            // Log error for debugging
            error_log("Checkout error: " . $e->getMessage());
        }
    }
?>
<style>
    /* Checkout Page Styles */
    .checkout-page {
        padding: 20px 0;
        background-color: #f8f9fa;
        min-height: 80vh;
    }
    
    .page-title {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 20px;
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
    
    .checkout-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    
    .checkout-steps {
        display: flex;
        justify-content: center;
        background: #f8f9fa;
        padding: 20px 10px;
        border-bottom: 1px solid #eee;
        flex-wrap: wrap;
    }
    
    .step {
        display: flex;
        align-items: center;
        margin: 5px 10px;
        font-weight: 600;
        color: #666;
        font-size: 0.85rem;
    }
    
    .step.active {
        color: black;
    }
    
    .step-number {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #ddd;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 8px;
        font-size: 0.8rem;
        flex-shrink: 0;
    }
    
    .step.active .step-number {
        background: black;
        color: white;
    }
    
    .step-text {
        white-space: nowrap;
    }
    
    .checkout-content {
        padding: 20px;
    }
    
    .checkout-form h4,
    .order-summary h4 {
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 20px;
        color: #333;
        position: relative;
        padding-bottom: 15px;
    }
    
    .checkout-form h4:after,
    .order-summary h4:after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 0;
        width: 40px;
        height: 3px;
        background: black;
        border-radius: 2px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        font-weight: 600;
        margin-bottom: 8px;
        color: #333;
        display: flex;
        align-items: center;
        font-size: 0.9rem;
    }
    
    .form-label .required {
        color: black;
        margin-left: 4px;
    }
    
    .form-control {
        height: 40px;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 10px 15px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        border-color: black;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    
    .form-control.is-invalid {
        border-color: black;
    }
    
    .invalid-feedback {
        color: black;
        font-size: 0.8rem;
        margin-top: 5px;
    }
    
    textarea.form-control {
        height: 100px;
        resize: none;
    }
    
    /* Login Section Styles */
    .login-section {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .login-info {
        flex: 1;
    }
    
    .login-info h4 {
        font-size: 1.2rem;
        margin-bottom: 10px;
        color: #333;
    }
    
    .login-info p {
        margin: 0;
        font-size: 0.9rem;
        color: #666;
    }
    
    .login-actions {
        display: flex;
        gap: 10px;
    }
    
    .btn-signin {
        background: black;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-signin:hover {
        background: #333;
        color: white;
        text-decoration: none;
    }
    
    .btn-register {
        background: transparent;
        color: black;
        border: 1px solid black;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-register:hover {
        background: black;
        color: white;
        text-decoration: none;
    }
    
    .user-info {
        background: #e8f5e9;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .user-info p {
        margin: 0;
        font-weight: 600;
    }
    
    .user-info .btn-logout {
        background: #f44336;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 5px;
        font-size: 0.85rem;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    
    .user-info .btn-logout:hover {
        background: #d32f2f;
        color: white;
        text-decoration: none;
    }
    
    /* Payment Method Styles */
    .payment-method {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-top: 20px;
    }
    
    /* ── Payment Options — unified card style ── */
    .payment-option { margin-bottom: 10px; }
    .payment-option:last-child { margin-bottom: 0; }
    .payment-option input[type="radio"] { position:absolute;opacity:0;width:0;height:0; }

    .pay-card {
        display: flex;
        align-items: center;
        gap: 14px;
        cursor: pointer;
        padding: 14px 16px;
        background: #fff;
        border-radius: 12px;
        border: 1.5px solid #e5e5e5;
        transition: border-color .2s, box-shadow .2s, background .2s;
        position: relative;
        user-select: none;
    }
    .pay-card:hover { border-color: #bbb; box-shadow: 0 2px 10px rgba(0,0,0,.06); }
    .payment-option input[type="radio"]:checked + .pay-card {
        border-color: #222;
        background: #fafafa;
        box-shadow: 0 2px 12px rgba(0,0,0,.1);
    }
    /* Radio dot indicator */
    .pay-card::after {
        content: '';
        width: 18px; height: 18px;
        border-radius: 50%;
        border: 2px solid #ccc;
        margin-left: auto;
        flex-shrink: 0;
        transition: border-color .2s, background .2s;
    }
    .payment-option input[type="radio"]:checked + .pay-card::after {
        border-color: #222;
        background: radial-gradient(circle, #222 5px, transparent 5px);
    }
    /* Disabled state */
    .payment-option-disabled .pay-card {
        opacity: .55; cursor: not-allowed; background: #f8f8f8;
    }
    .payment-option-disabled .pay-card:hover { border-color: #e5e5e5; box-shadow: none; }
    /* Logo box */
    .pay-logo {
        width: 46px; height: 46px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; overflow: hidden; flex-direction: column;
    }
    .pay-info h6 { margin: 0 0 2px; font-size: .9rem; font-weight: 700; color: #222; }
    .pay-info p  { margin: 0; font-size: .78rem; color: #888; line-height: 1.3; }
    .pay-badge {
        font-size: .68rem; font-weight: 700; padding: 3px 9px;
        border-radius: 20px; white-space: nowrap; flex-shrink: 0;
        display: inline-flex; align-items: center; gap: 3px;
    }
    
    .payment-option input[type="radio"] {
        display: none;
    }
    
    .payment-icon {
        margin-right: 15px;
        font-size: 1.3rem;
        color: black;
        flex-shrink: 0;
    }
    
    .payment-info h6 {
        margin-bottom: 5px;
        font-weight: 600;
        color: #333;
        font-size: 0.95rem;
    }
    
    .payment-info p {
        font-size: 0.8rem;
        color: #666;
        margin: 0;
    }
    
    /* Order Summary Styles */
    .order-summary-card {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        position: relative;
        margin-top: 20px;
    }
    
    .order-items {
        max-height: 250px;
        overflow-y: auto;
        margin-bottom: 20px;
    }
    
    .order-item {
        display: flex;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid #eee;
    }
    
    .order-item:last-child {
        border-bottom: none;
    }
    
    .order-item-image {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 8px;
        margin-right: 15px;
        flex-shrink: 0;
    }
    
    .order-item-details {
        flex: 1;
        min-width: 0;
    }
    
    .order-item-name {
        font-weight: 600;
        font-size: 0.85rem;
        margin-bottom: 5px;
        color: #333;
        word-break: break-word;
    }
    
    .order-item-meta {
        font-size: 0.75rem;
        color: #666;
        margin-bottom: 3px;
    }
    
    .order-item-price {
        font-weight: 600;
        color: #333;
        font-size: 0.85rem;
        flex-shrink: 0;
        margin-left: 10px;
    }
    
    .order-totals {
        border-top: 2px solid #ddd;
        padding-top: 15px;
        margin-top: 15px;
    }
    
    .order-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 0.85rem;
    }
    
    .order-row.total {
        font-weight: 700;
        font-size: 0.95rem;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #eee;
    }
    
    .btn-place-order {
        background: black;
        border: none;
        color: white;
        padding: 12px 20px;
        font-weight: 600;
        font-size: 1rem;
        border-radius: 30px;
        width: 100%;
        margin-top: 20px;
        transition: all 0.3s ease;
    }
    
    .btn-place-order:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }
    
    .btn-place-order:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none !important;
    }
    
    /* Loading spinner */
    .spinner-border {
        width: 1rem;
        height: 1rem;
        border-width: 0.2em;
        margin-right: 8px;
    }
    
    /* Error and success states */
    .alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 0.9rem;
    }
    
    .alert-danger {
        background-color: #f8d7da;
        border-color: #f5c6cb;
        color: black;
    }
    
    .alert-success {
        background-color: #d4edda;
        border-color: #c3e6cb;
        color: #155724;
    }
    
    /* Breadcrumb styles */
    .bredcrumbWrap {
        padding: 10px 0;
        background-color: #f8f9fa;
        border-bottom: 1px solid #eee;
    }
    
    .breadcrumbs {
        font-size: 0.85rem;
    }
    
    .breadcrumbs a, .breadcrumbs span {
        margin-right: 5px;
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
    
    /* ── Payment logo images ──────────────────────────────── */
    /* JazzCash/EasyPaisa full-fill logos (inline styles handle sizing) */
    /* Card network logos row inside pay-info */
    .pay-card-nets {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-top: 4px;
        flex-wrap: wrap;
    }
    .pay-card-nets img {
        height: 18px;
        width: auto;
        border-radius: 3px;
        object-fit: contain;
    }
    .pay-card-nets .net-label {
        font-size: .7rem;
        color: #999;
    }

    /* ── Mobile responsive for payment cards ─────────────── */
    @media (max-width: 480px) {
        .pay-card { padding: 10px 12px; gap: 10px; }
        .pay-logo { width: 40px; height: 40px; border-radius: 8px; }
        .pay-info h6 { font-size: .82rem; }
        .pay-info p  { font-size: .7rem; }
        .pay-badge { font-size: .6rem; padding: 2px 7px; display: none; }
        .pay-card-nets img { height: 14px; }
        .payment-method { padding: 14px; }
        .payment-method > h4 { font-size: 1.1rem; }
    }

    /* ── Responsive styles for larger screens ─────────────── */
    @media (min-width: 768px) {
        .checkout-page {
            padding: 30px 0;
        }
        
        .page-title {
            font-size: 2.2rem;
            margin-bottom: 30px;
        }
        
        .checkout-steps {
            padding: 20px;
            flex-wrap: nowrap;
        }
        
        .step {
            margin: 0 20px;
            font-size: 1rem;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            margin-right: 10px;
            font-size: 0.9rem;
        }
        
        .checkout-content {
            padding: 30px;
        }
        
        .checkout-form h4,
        .order-summary h4 {
            font-size: 1.5rem;
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            font-size: 1rem;
        }
        
        .form-control {
            height: 45px;
            font-size: 1rem;
        }
        
        .payment-method {
            padding: 25px;
        }
        
        .payment-icon {
            font-size: 1.5rem;
        }
        
        .payment-info h6 {
            font-size: 1rem;
        }
        
        .payment-info p {
            font-size: 0.875rem;
        }
        
        .order-summary-card {
            padding: 25px;
            position: sticky;
            top: 20px;
            margin-top: 0;
        }
        
        .order-items {
            max-height: 300px;
        }
        
        .order-item-image {
            width: 60px;
            height: 60px;
        }
        
        .order-item-name {
            font-size: 0.95rem;
        }
        
        .order-item-meta {
            font-size: 0.85rem;
        }
        
        .order-item-price {
            font-size: 0.95rem;
        }
        
        .order-totals {
            padding-top: 20px;
            margin-top: 20px;
        }
        
        .order-row {
            font-size: 0.95rem;
        }
        
        .order-row.total {
            font-size: 1.1rem;
            margin-top: 15px;
            padding-top: 15px;
        }
        
        .btn-place-order {
            padding: 14px 25px;
            font-size: 1.1rem;
            margin-top: 25px;
        }
        
        .alert {
            font-size: 1rem;
        }
        
        .breadcrumbs {
            font-size: 1rem;
        }
    }
    .form-control.is-invalid, .was-validated .form-control:invalid {
        border-color: black !important;
      
    }
    /* Add this responsive fix for login section */
    @media (max-width: 767px) {
        .login-section {
            flex-direction: column;
            text-align: center;
            padding: 15px;
        }
        
        .login-info {
            margin-bottom: 10px;
        }
        
        .login-actions {
            width: 100%;
            justify-content: center;
            flex-wrap: wrap;
            gap: 0px !important;
        }
        
        .btn-signin, .btn-register {
            margin: 5px;
            flex: 1 1 150px;
        }
      
    }
</style>

<div class="bredcrumbWrap bredcrumb-style2">
    <div class="container breadcrumbs">
        <a href="<?php echo $url_domain; ?>/index" title="Back to home page">Home</a>
        <span aria-hidden="true">|</span>
        <a href="<?php echo $url_domain; ?>/cart">Cart</a>
        <span aria-hidden="true">|</span>
        <span class="title-bold">Checkout</span>
    </div>
</div>

<div class="checkout-page">
    <div class="container">
        <h1 class="page-title">Checkout</h1>
        
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger">
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>
        
        <div class="checkout-container">
            <div class="checkout-steps">
                <div class="step active">
                    <span class="step-number">1</span>
                    <span class="step-text">Shopping Cart</span>
                </div>
                <div class="step active">
                    <span class="step-number">2</span>
                    <span class="step-text">Checkout</span>
                </div>
                <div class="step">
                    <span class="step-number">3</span>
                    <span class="step-text">Order Complete</span>
                </div>
            </div>
            
            <div class="checkout-content">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="checkout-form">
                            <!-- Login Section -->
                            <?php if (!$isLoggedIn): ?>
                            <div class="login-section">
                                <div class="login-info">
                                    <h4>Already have an account?</h4>
                                    <p>Sign in for faster checkout and to access your order history</p>
                                </div>
                                <div class="login-actions">
                                    <a href="<?php echo $url_domain; ?>/login" class="btn-signin">Sign In</a>
                                    <a href="<?php echo $url_domain; ?>/register" class="btn-register">Register</a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <h4>Shipping Information</h4>
                            
                            <form id="checkoutForm" method="POST" enctype="multipart/form-data" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
<div style="position:absolute;left:-9999px;" aria-hidden="true">
    <input type="text" name="website_url" tabindex="-1" autocomplete="off">
</div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">
                                                Full Name <span class="required">*</span>
                                            </label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   name="name" 
                                                   required
                                                   value="<?php echo $isLoggedIn ? htmlspecialchars($userData['name']) : (isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''); ?>"
                                                   placeholder="Enter your full name">
                                            <div class="invalid-feedback">Please enter your name.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">
                                                Email Address (Optional)
                                            </label>
                                            <input type="email"
                                                   class="form-control"
                                                   name="email"
                                                   value="<?php echo $isLoggedIn ? htmlspecialchars($userData['email']) : (isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''); ?>"
                                                   placeholder="Enter your email">
                                            <div class="invalid-feedback">Please enter a valid email address.</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">
                                                Phone Number <span class="required">*</span>
                                            </label>
                                            <input type="tel" 
                                                   class="form-control" 
                                                   name="phone" 
                                                   required
                                                  value="<?php echo $isLoggedIn ? htmlspecialchars($userData['number']) : (isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''); ?>"
                                                   placeholder="Enter your phone number">
                                            <div class="invalid-feedback">Please enter your phone number.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">
                                                Country <span class="required">*</span>
                                            </label>
                                            <select class="form-control" name="country" required>
                                                <option value="">Select Country</option>
                                                <option value="Pakistan" <?php echo (isset($_POST['country']) && $_POST['country'] == 'Pakistan') ? 'selected' : ''; ?>>Pakistan</option>
                                                <!-- Add more countries as needed -->
                                            </select>
                                            <div class="invalid-feedback">Please select your country.</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">
                                                City <span class="required">*</span>
                                            </label>
                                            <input type="text"
                                                   class="form-control"
                                                   name="city"
                                                   required
                                                   value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>"
                                                   placeholder="Enter your city">
                                            <div class="invalid-feedback">Please enter your city.</div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">
                                                Address <span class="required">*</span>
                                            </label>
                                            <input type="text"
                                                   class="form-control"
                                                   name="address"
                                                   required
                                                   value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>"
                                                   placeholder="Enter your full address">
                                            <div class="invalid-feedback">Please enter your address.</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        Additional Notes (Optional)
                                    </label>
                                    <textarea class="form-control" 
                                              name="notes" 
                                              placeholder="Any special instructions for delivery?"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                                </div>
                                
                                <div class="payment-method">
                                    <h4><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;margin-bottom:2px"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>Payment Method</h4>

                                    <?php if (isset($_GET['payment_error'])): ?>
                                    <?php $pErrMsg = urldecode($_GET['payment_error'] ?? ''); ?>
                                    <div class="alert alert-danger" style="border-radius:10px;padding:14px 16px;margin-bottom:14px;font-size:13px;line-height:1.6;">
                                        <?php if ($pErrMsg === 'stripe_cancelled'): ?>
                                            ↩️ <strong>Card payment was cancelled.</strong> You can try again or choose another method.
                                        <?php elseif (stripos($pErrMsg, 'Minimum order') !== false): ?>
                                            ⚠️ <?= htmlspecialchars($pErrMsg) ?>
                                        <?php elseif ($pErrMsg): ?>
                                            ❌ <strong>Payment failed.</strong> <?= htmlspecialchars($pErrMsg) ?><br>
                                            <span style="font-size:12px;">Please try again or choose Cash on Delivery.</span>
                                        <?php else: ?>
                                            ⚠️ <strong>Payment failed or was cancelled.</strong> Please try again or choose another method.
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>

                                    <!-- COD -->
                                    <div class="payment-option">
                                        <input type="radio" id="cod" name="payment_method" value="cod" required
                                               <?php echo (!isset($_POST['payment_method']) || $_POST['payment_method'] == 'cod') ? 'checked' : ''; ?>>
                                        <label class="pay-card" for="cod">
                                            <div class="pay-logo" style="background:#f0f4ff;">
                                                <!-- Delivery truck icon for COD -->
                                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#3b5bdb" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="1" y="3" width="15" height="13" rx="1"/>
                                                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                                                    <circle cx="5.5" cy="18.5" r="2.5"/>
                                                    <circle cx="18.5" cy="18.5" r="2.5"/>
                                                </svg>
                                            </div>
                                            <div class="pay-info">
                                                <h6>Cash on Delivery</h6>
                                                <p>Pay cash when your order arrives</p>
                                            </div>
                                            <span class="pay-badge" style="background:#eef2ff;color:#3b5bdb;">COD</span>
                                        </label>
                                    </div>

                                    <!-- Stripe / Credit Card — disabled -->
                                    <?php if (false): // Stripe payment disabled ?>
                                    <div class="payment-option">
                                        <input type="radio" id="stripe" name="payment_method" value="stripe"
                                               <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == 'stripe') ? 'checked' : ''; ?>>
                                        <label class="pay-card" for="stripe">
                                            <div class="pay-logo" style="background:#f0f4ff;border:1.5px solid #c7d2fe;flex-direction:column;gap:3px;padding:5px;align-items:center;justify-content:center;">
                                                <div style="background:#1a1f71;border-radius:3px;padding:2px 5px;line-height:1;">
                                                    <span style="color:#fff;font-size:9px;font-weight:900;font-style:italic;font-family:Arial,sans-serif;letter-spacing:0.5px;">VISA</span>
                                                </div>
                                                <div style="display:flex;align-items:center;gap:0;">
                                                    <div style="width:14px;height:14px;border-radius:50%;background:#eb001b;"></div>
                                                    <div style="width:14px;height:14px;border-radius:50%;background:#f79e1b;margin-left:-6px;"></div>
                                                </div>
                                            </div>
                                            <div class="pay-info">
                                                <h6>Credit / Debit Card</h6>
                                                <p>Visa &amp; Mastercard — secured by Stripe</p>
                                            </div>
                                            <span class="pay-badge" style="background:#f0f0ff;color:#635bff;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                                Secure
                                            </span>
                                        </label>
                                    </div>
                                    <?php endif; // end Stripe disabled ?>

                                    <!-- Bank / QR Transfer -->
                                    <div class="payment-option">
                                        <input type="radio" id="qr_transfer" name="payment_method" value="qr_transfer"
                                               <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == 'qr_transfer') ? 'checked' : ''; ?>>
                                        <label class="pay-card" for="qr_transfer">
                                            <div class="pay-logo" style="background:#ff6b35;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                                                    <rect x="3" y="14" width="7" height="7" rx="1"/>
                                                    <rect x="14" y="14" width="3" height="3"/><rect x="18" y="18" width="3" height="3"/>
                                                    <rect x="14" y="18" width="3" height="3" style="display:none"/>
                                                </svg>
                                            </div>
                                            <div class="pay-info">
                                                <h6>Bank Transfer / QR</h6>
                                                <p>JazzCash QR · Bank transfer — upload proof</p>
                                            </div>
                                            <span class="pay-badge" style="background:#fff4f0;color:#ff6b35;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                                Upload
                                            </span>
                                        </label>
                                    </div>

                                    <!-- QR Transfer Upload Panel (shown when QR selected) -->
                                    <div id="qrUploadPanel" style="display:none;background:#fff8f5;border:2px dashed #ff6b35;border-radius:12px;padding:20px;margin-top:12px;">
                                        <div style="text-align:center;margin-bottom:14px;">
                                            <h6 style="color:#ff6b35;font-weight:700;margin-bottom:4px;"><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>Scan QR Code or Bank Transfer</h6>
                                            <p style="font-size:12px;color:#666;margin:0 0 12px;">Scan QR with JazzCash / EasyPaisa / any app, or do a bank transfer — then upload your payment screenshot.</p>
                                            <!-- QR Code Image -->
                                            <div style="display:inline-block;background:#fff;border:3px solid #ff6b35;border-radius:12px;padding:10px;box-shadow:0 4px 16px rgba(255,107,53,.15);">
                                                <img src="<?= $url_domain ?>/assets/qr/jazzcash_qr.jpg"
                                                     alt="QR Code — Umar Akhtar"
                                                     style="width:160px;height:160px;object-fit:contain;display:block;border-radius:6px;"
                                                     onerror="this.parentElement.innerHTML='<p style=\'color:#ff6b35;font-size:12px;padding:20px;\'>QR image not found — use bank details below</p>'">
                                                <p style="margin:6px 0 0;font-size:10px;color:#ff6b35;font-weight:700;">Umar Akhtar — 0342-6874805</p>
                                            </div>
                                        </div>
                                        <!-- Bank / Transfer Details -->
                                        <div style="background:#fff;border-radius:8px;padding:15px;margin-bottom:16px;border:1px solid #ffe0d0;">
                                            <table style="width:100%;font-size:13px;border-collapse:collapse;">
                                                <tr>
                                                    <td style="padding:4px 0;color:#888;width:120px;">Mobile No.</td>
                                                    <td style="padding:4px 0;font-weight:700;color:#ff6b35;">0342-6874805</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:4px 0;color:#888;">Account Name</td>
                                                    <td style="padding:4px 0;font-weight:700;">Umar Akhtar</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:4px 0;color:#888;">Bank Name</td>
                                                    <td style="padding:4px 0;font-weight:700;">Faysal Bank</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:4px 0;color:#888;">IBAN</td>
                                                    <td style="padding:4px 0;font-weight:700;font-size:12px;">PK82FAYS3589301000005053</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:4px 0;color:#888;">Amount</td>
                                                    <td style="padding:4px 0;font-weight:800;color:#ff6b35;font-size:15px;">RS. <?= number_format($cartTotal, 2) ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                        <!-- File Upload Zone -->
                                        <div>
                                            <label style="font-weight:600;font-size:13px;color:#333;margin-bottom:8px;display:block;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>Upload Payment Screenshot <span style="color:red;">*</span>
                                            </label>

                                            <!-- Hidden real file input -->
                                            <input type="file"
                                                   id="payment_proof"
                                                   name="payment_proof"
                                                   accept="image/*"
                                                   style="display:none;">

                                            <!-- Clickable Upload Zone (shown before image selected) -->
                                            <div id="proofUploadZone"
                                                 style="border:2px dashed #ff6b35;border-radius:10px;padding:22px 16px;text-align:center;cursor:pointer;background:#fff;transition:background .2s;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ff6b35" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:8px"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                                <p style="margin:0 0 10px;color:#555;font-size:13px;">Tap to choose your payment screenshot</p>
                                                <button type="button" id="proofPickBtn"
                                                        style="display:inline-flex;align-items:center;gap:6px;padding:8px 20px;background:#ff6b35;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                                    Choose Image
                                                </button>
                                                <p style="font-size:10px;color:#aaa;margin:8px 0 0;">JPG, PNG, WEBP — Max 5MB</p>
                                            </div>

                                            <!-- Preview (shown after image selected) -->
                                            <div id="proofPreview" style="display:none;margin-top:10px;background:#fff;border:2px solid #ff6b35;border-radius:10px;padding:12px;text-align:center;position:relative;">
                                                <!-- Delete / Cancel button -->
                                                <button type="button" id="proofDeleteBtn"
                                                        title="Remove image — choose a different one"
                                                        style="position:absolute;top:-10px;right:-10px;width:28px;height:28px;border-radius:50%;background:#dc3545;border:2px solid #fff;color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 2px 6px rgba(0,0,0,.2);">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                </button>
                                                <img id="proofImg" src="" alt="Payment proof"
                                                     style="max-width:100%;max-height:220px;border-radius:8px;display:block;margin:0 auto 10px;">
                                                <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
                                                    <span id="proofFileName" style="font-size:12px;color:#555;display:flex;align-items:center;gap:4px;">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#ff6b35" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                                        <strong></strong>
                                                    </span>
                                                    <button type="button" id="proofChangeBtn"
                                                            style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;background:#fff;color:#ff6b35;border:1.5px solid #ff6b35;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                                        Change Image
                                                    </button>
                                                </div>
                                                <p style="margin:8px 0 0;font-size:11px;color:#28a745;font-weight:600;">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle"><polyline points="20 6 9 17 4 12"/></svg>
                                                    Screenshot ready — click Place Order to submit
                                                </p>
                                            </div>

                                            <p style="font-size:11px;color:#999;margin-top:6px;margin-bottom:0;">Your order will be verified within 1-2 hours after screenshot review.</p>
                                        </div>
                                    </div>

                                    <p style="font-size:11px;color:#aaa;margin-top:10px;text-align:center;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>All online payments are encrypted and secure
                                    </p>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="order-summary-card">
                            <h4>Order Summary</h4>
                            
                            <div class="order-items">
                                <?php foreach ($cartItems as $item): 
                                    $imageParts = explode(',', $item['image']);
                                    $firstImage = trim($imageParts[0]);
                                    $itemTotal = ($item['price'] + $item['stitching_price']) * $item['quantity'];
                                    
                                    // Process colors if available
                                    $colorHtml = '';
                                    if (!empty($item['colors'])) {
                                        $colorNames = explode(',', $item['colors']);
                                        $colorHtml = '<div class="order-item-meta">Colors: ';
                                        foreach ($colorNames as $colorName) {
                                            $colorHtml .= '<span class="color-swatch"><span class="color-swatch-circle" style="background-color: ' . htmlspecialchars($colorName) . ';"></span>' . htmlspecialchars($colorName) . '</span>';
                                        }
                                        $colorHtml .= '</div>';
                                    }
                                ?>
                                <div class="order-item">
                                    <img src="<?php echo $url_domain; ?>/admin/images/product/<?php echo $firstImage; ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         class="order-item-image">
                                    <div class="order-item-details">
                                        <div class="order-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <?php if (!empty($item['sizes'])): ?>
                                            <div class="order-item-meta">Size: <?php echo htmlspecialchars($item['sizes']); ?></div>
                                        <?php endif; ?>
                                        <?php echo $colorHtml; ?>
                                        <?php if ($item['stitching'] == 1): ?>
                                            <div class="order-item-meta">With Stitching</div>
                                        <?php endif; ?>
                                        <?php if ($item['price_type'] === 'set'): ?>
                                            <div class="order-item-meta">Set Suit (<?php echo $item['setsuite_quantity']; ?> pieces)</div>
                                        <?php endif; ?>
                                        <div class="order-item-meta">Qty: <?php echo $item['quantity']; ?></div>
                                    </div>
                                    <div class="order-item-price">RS. <?php echo number_format($itemTotal, 2); ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Coupon Code Section -->
                            <div class="coupon-section mt-3 mb-3">
                              <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="fas fa-ticket-alt" style="color:#7c3aed;"></i>
                                <strong style="font-size:14px;">Have a coupon code?</strong>
                              </div>
                              <div class="d-flex gap-2">
                                <input type="text" id="couponInput" class="form-control" placeholder="Enter coupon code"
                                       style="text-transform:uppercase;font-size:13px;"
                                       value="<?= htmlspecialchars($_SESSION['applied_coupon']['code'] ?? '') ?>">
                                <button type="button" id="applyCouponBtn" class="btn btn-dark btn-sm px-3" style="white-space:nowrap;">Apply</button>
                                <?php if (!empty($_SESSION['applied_coupon'])): ?>
                                <button type="button" id="removeCouponBtn" class="btn btn-outline-danger btn-sm px-3">Remove</button>
                                <?php endif; ?>
                                <!-- Fallback hard-remove link in case JS fetch fails -->
                                <?php if (!empty($_SESSION['applied_coupon'])): ?>
                                <a href="checkout.php?clear_coupon=1" id="removeCouponFallback"
                                   class="btn btn-outline-secondary btn-sm px-2" title="Force remove coupon"
                                   style="font-size:11px;">✕</a>
                                <?php endif; ?>
                              </div>
                              <div id="couponMsg" class="mt-2" style="font-size:13px;"></div>
                              <?php if (!empty($_SESSION['applied_coupon'])): ?>
                              <div class="alert alert-success py-2 mt-2 mb-0" style="font-size:13px;">
                                <i class="fas fa-check-circle me-1"></i>
                                Coupon <strong><?= htmlspecialchars($_SESSION['applied_coupon']['code']) ?></strong> applied —
                                You save <strong>RS. <?= number_format($_SESSION['applied_coupon']['discount_amount'], 2) ?></strong>
                              </div>
                              <?php endif; ?>
                            </div>

                            <div class="order-totals">
                                <div class="order-row">
                                    <span>Subtotal:</span>
                                    <span>RS. <?php echo number_format($cartTotal, 2); ?></span>
                                </div>
                                <?php if ($couponDiscount > 0): ?>
                                <div class="order-row" style="color:#16a34a;">
                                    <span><i class="fas fa-tag me-1"></i>Coupon Discount:</span>
                                    <span>- RS. <?php echo number_format($couponDiscount, 2); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="row total">
                                    <span>Total Amount:</span>
                                    <span>RS. <?php echo number_format($finalTotal, 2); ?></span>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" 
                                        form="checkoutForm" 
                                        class="btn-place-order"
                                        id="placeOrderBtn">
                                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                    Place Order
                                </button>
                                
                                <p class="text-muted mt-3" style="font-size: 0.8rem;">
                                    By placing your order, you agree to our 
                                    <a href="<?php echo $url_domain; ?>/term-condition" class="text-primary">Terms & Conditions</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkoutForm   = document.getElementById('checkoutForm');
        const placeOrderBtn  = document.getElementById('placeOrderBtn');
        const spinner        = placeOrderBtn.querySelector('.spinner-border');
        const qrUploadPanel  = document.getElementById('qrUploadPanel');
        const proofInput     = document.getElementById('payment_proof');
        const proofPreview   = document.getElementById('proofPreview');
        const proofImg       = document.getElementById('proofImg');
        const proofUploadZone= document.getElementById('proofUploadZone');
        const proofPickBtn   = document.getElementById('proofPickBtn');
        const proofDeleteBtn = document.getElementById('proofDeleteBtn');
        const proofChangeBtn = document.getElementById('proofChangeBtn');
        const proofFileName  = document.getElementById('proofFileName');

        // ── Show/hide QR panel when payment method changes ──────
        function toggleQrPanel() {
            const selected = document.querySelector('input[name="payment_method"]:checked');
            if (selected && selected.value === 'qr_transfer') {
                qrUploadPanel.style.display = 'block';
            } else {
                qrUploadPanel.style.display = 'none';
            }
        }
        document.querySelectorAll('input[name="payment_method"]').forEach(function(r) {
            r.addEventListener('change', toggleQrPanel);
        });
        toggleQrPanel();

        // ── Open file picker ────────────────────────────────────
        function openFilePicker() { proofInput.click(); }
        proofUploadZone.addEventListener('click', openFilePicker);
        proofPickBtn.addEventListener('click',   function(e){ e.stopPropagation(); openFilePicker(); });
        if (proofChangeBtn) proofChangeBtn.addEventListener('click', openFilePicker);

        // ── Handle file selected ────────────────────────────────
        proofInput.addEventListener('change', function() {
            const file = this.files && this.files[0];
            if (!file) return;

            // Validate size (5 MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('Image is too large. Please choose a file under 5 MB.');
                this.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                proofImg.src = e.target.result;
                // Show file name
                const nameEl = proofFileName.querySelector('strong');
                if (nameEl) nameEl.textContent = file.name;
                // Switch views
                proofUploadZone.style.display = 'none';
                proofPreview.style.display    = 'block';
            };
            reader.readAsDataURL(file);
        });

        // ── Delete / Cancel — remove selected image ─────────────
        proofDeleteBtn.addEventListener('click', function() {
            proofInput.value = '';           // clear the file input
            proofImg.src     = '';
            proofUploadZone.style.display = 'block';
            proofPreview.style.display    = 'none';
        });

        // ── Drag-and-drop support ───────────────────────────────
        proofUploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.background = '#fff0eb';
        });
        proofUploadZone.addEventListener('dragleave', function() {
            this.style.background = '#fff';
        });
        proofUploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.background = '#fff';
            const file = e.dataTransfer.files && e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                // Assign to the file input via DataTransfer
                const dt = new DataTransfer();
                dt.items.add(file);
                proofInput.files = dt.files;
                proofInput.dispatchEvent(new Event('change'));
            }
        });

        // ── Coupon Apply / Remove ───────────────────────────────
        var cartTotal       = <?= floatval($cartTotal) ?>;
        var applyBtn        = document.getElementById('applyCouponBtn');
        var removeBtn       = document.getElementById('removeCouponBtn');
        var couponInput     = document.getElementById('couponInput');
        var couponMsg       = document.getElementById('couponMsg');

        function showMsg(text, ok) {
            couponMsg.innerHTML = '<span style="color:' + (ok ? '#16a34a' : '#dc2626') + ';">'
                + (ok ? '✔ ' : '✖ ') + text + '</span>';
        }

        if (applyBtn) {
            applyBtn.addEventListener('click', function() {
                var code = couponInput.value.trim();
                if (!code) { showMsg('Please enter a coupon code.', false); return; }
                applyBtn.disabled = true;
                applyBtn.textContent = '...';
                fetch('coupon_validate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'apply', code: code, cart_total: cartTotal })
                })
                .then(function(r){ return r.json(); })
                .then(function(data) {
                    applyBtn.disabled = false;
                    applyBtn.textContent = 'Apply';
                    if (data.success) {
                        showMsg(data.message, true);
                        // Reload page so totals and Remove button update
                        setTimeout(function(){ location.reload(); }, 800);
                    } else {
                        showMsg(data.message, false);
                    }
                })
                .catch(function() {
                    applyBtn.disabled = false;
                    applyBtn.textContent = 'Apply';
                    showMsg('Connection error. Please try again.', false);
                });
            });

            // Allow pressing Enter in the coupon input
            couponInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') { e.preventDefault(); applyBtn.click(); }
            });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                fetch('coupon_validate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'remove' })
                })
                .then(function(r){ return r.json(); })
                .then(function(data) {
                    if (data.success) location.reload();
                })
                .catch(function(){});
            });
        }

        // ── Form submit validation ───────────────────────────────
        checkoutForm.addEventListener('submit', function(e) {
            e.preventDefault();

            checkoutForm.classList.remove('was-validated');

            if (!checkoutForm.checkValidity()) {
                checkoutForm.classList.add('was-validated');
                return;
            }

            // QR transfer requires a proof image
            const selected = document.querySelector('input[name="payment_method"]:checked');
            if (selected && selected.value === 'qr_transfer') {
                if (!proofInput.files || proofInput.files.length === 0) {
                    // Flash the upload zone red
                    proofUploadZone.style.borderColor = 'red';
                    proofUploadZone.style.background  = '#fff5f5';
                    proofUploadZone.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    const errId = 'proofErr';
                    if (!document.getElementById(errId)) {
                        proofUploadZone.insertAdjacentHTML('afterend',
                            '<p style="color:red;font-size:12px;margin-top:4px;" id="'+errId+'">Please upload your payment screenshot before placing the order.</p>');
                    }
                    return;
                }
            }

            // Remove any error styling
            proofUploadZone.style.borderColor = '';
            proofUploadZone.style.background  = '';
            const errEl = document.getElementById('proofErr');
            if (errEl) errEl.remove();

            placeOrderBtn.disabled = true;
            spinner.classList.remove('d-none');
            checkoutForm.submit();
        });
    });
</script>
<script>
fbq('track', 'InitiateCheckout', {
    content_ids: [<?php
        $ids = array_map(function($item) { return json_encode((string)$item['id']); }, $cartItems);
        echo implode(',', $ids);
    ?>],
    content_type: 'product',
    num_items: <?php echo (int)count($cartItems); ?>,
    value: <?php echo json_encode((float)$finalTotal); ?>,
    currency: 'PKR'
});
</script>
<?php include 'include/footer.php'; ?>