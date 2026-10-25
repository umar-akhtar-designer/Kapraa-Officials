<?php
    session_start();

    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }

    // Function to get or create a unique identifier for the user
    function getUserId() {
        // If user is logged in, use their ID
        if (isset($_SESSION['user_id'])) {
            return 'user_' . $_SESSION['user_id'];
        }
        
        // For guest users, use a unique identifier stored in a cookie
        if (!isset($_COOKIE['cart_identifier'])) {
            $identifier = 'guest_' . uniqid() . '_' . time();
            setcookie('cart_identifier', $identifier, time() + (86400 * 30), "/"); // 30 days expiry
            return $identifier;
        }
        
        return $_COOKIE['cart_identifier'];
    }

    // Function to sync cart from database to session
    function syncCartFromDatabase() {
        global $connection_database;
        $userId = getUserId();
        
        // Clear session cart
        $_SESSION['cart'] = array();
        
        // Get cart items from database
        $query = "SELECT c.*, p.name, p.image, p.price, p.price_after_discount, p.stitched_price, p.shipp_cost, p.setsuite_price, p.setsuite_discount, p.setsuite_quantity 
                  FROM cart c 
                  JOIN products p ON c.product_id = p.id 
                  WHERE c.user_id = '$userId'";
        $result = $connection_database->query($query);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Determine price based on price_type
                $price = 0;
                $original_price = 0;
                
                if ($row['price_type'] === 'set' && !empty($row['setsuite_price'])) {
                    // Use set suit price
                    $original_price = $row['setsuite_price'];
                    if (!empty($row['setsuite_discount']) && $row['setsuite_discount'] > 0) {
                        $price = $row['setsuite_price'] - $row['setsuite_discount'];
                    } else {
                        $price = $row['setsuite_price'];
                    }
                } else {
                    // Use regular price
                    $original_price = $row['price'];
                    $price = $row['price_after_discount'] > 0 ? $row['price_after_discount'] : $row['price'];
                }
                
                // Create cart key
                $cart_key = $row['product_id'] . '_' . md5($row['sizes'] . $row['colors'] . $row['stitching'] . $row['price_type']);
                
                // Add to session cart
                $_SESSION['cart'][$cart_key] = array(
                    'id' => $row['product_id'],
                    'name' => $row['name'],
                    'price' => $price,
                    'original_price' => $original_price,
                    'price_type' => $row['price_type'],
                    'image' => $row['image'],
                    'quantity' => $row['quantity'],
                    'sizes' => $row['sizes'],
                    'colors' => $row['colors'],
                    'stitching' => $row['stitching'],
                    'stitching_price' => $row['stitching'] == 1 ? $row['stitched_price'] : 0,
                    'shipping_cost' => $row['shipp_cost'],
                    'setsuite_quantity' => $row['price_type'] === 'set' ? $row['setsuite_quantity'] : 0
                );
            }
        }
    }

    // Function to add product to cart
    function addToCart($product_id, $quantity = 1, $sizes = '', $colors = '', $stitching = 0, $price_type = 'regular') {
        global $connection_database;
        
        // Fetch product details
        $query = "SELECT * FROM products WHERE id = $product_id";
        $result = $connection_database->query($query);
        
        if ($result && $result->num_rows > 0) {
            $product = $result->fetch_assoc();
            
            // Determine price based on price_type
            $price = 0;
            $original_price = 0;
            
            if ($price_type === 'set' && !empty($product['setsuite_price'])) {
                // Use set suit price
                $original_price = $product['setsuite_price'];
                if (!empty($product['setsuite_discount']) && $product['setsuite_discount'] > 0) {
                    $price = $product['setsuite_price'] - $product['setsuite_discount'];
                } else {
                    $price = $product['setsuite_price'];
                }
            } else {
                // Use regular price
                $original_price = $product['price'];
                $price = $product['price_after_discount'] > 0 ? $product['price_after_discount'] : $product['price'];
            }
            
            // Check if product already exists in cart with same options (now including colors)
            $cart_key = $product_id . '_' . md5($sizes . $colors . $stitching . $price_type);
            $userId = getUserId();
            
            // Check if item exists in database
            $checkQuery = "SELECT id, quantity FROM cart WHERE user_id = '$userId' AND product_id = $product_id AND sizes = '$sizes' AND colors = '$colors' AND stitching = $stitching AND price_type = '$price_type'";
            $checkResult = $connection_database->query($checkQuery);
            
            if ($checkResult && $checkResult->num_rows > 0) {
                // Update quantity if product already in cart
                $row = $checkResult->fetch_assoc();
                $newQuantity = $row['quantity'] + $quantity;
                $updateQuery = "UPDATE cart SET quantity = $newQuantity WHERE id = " . $row['id'];
                $connection_database->query($updateQuery);
            } else {
                // Add new item to cart
                $insertQuery = "INSERT INTO cart (user_id, product_id, quantity, sizes, colors, stitching, price_type) 
                               VALUES ('$userId', $product_id, $quantity, '$sizes', '$colors', $stitching, '$price_type')";
                $connection_database->query($insertQuery);
            }
            
            // Sync cart from database to session
            syncCartFromDatabase();
            
            return true;
        }
        
        return false;
    }

    // Function to update cart quantity
    function updateCartQuantity($cart_key, $quantity) {
        global $connection_database;
        $userId = getUserId();
        
        if (isset($_SESSION['cart'][$cart_key])) {
            if ($quantity > 0) {
                // Parse cart key to get product details
                $keyParts = explode('_', $cart_key);
                $product_id = $keyParts[0];
                $hash = end($keyParts);
                
                // Find the item in the database
                $query = "SELECT * FROM cart WHERE user_id = '$userId' AND product_id = $product_id";
                $result = $connection_database->query($query);
                
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Check if this is the correct item by comparing the hash
                        $itemHash = md5($row['sizes'] . $row['colors'] . $row['stitching'] . $row['price_type']);
                        if ($itemHash === $hash) {
                            // Update quantity
                            $updateQuery = "UPDATE cart SET quantity = $quantity WHERE id = " . $row['id'];
                            $connection_database->query($updateQuery);
                            
                            // Update session
                            $_SESSION['cart'][$cart_key]['quantity'] = $quantity;
                            return true;
                        }
                    }
                }
            } else {
                // Remove item if quantity is 0
                removeFromCart($cart_key);
            }
        }
        return false;
    }

    // Function to remove item from cart
    function removeFromCart($cart_key) {
        global $connection_database;
        $userId = getUserId();
        
        if (isset($_SESSION['cart'][$cart_key])) {
            // Parse cart key to get product details
            $keyParts = explode('_', $cart_key);
            $product_id = $keyParts[0];
            $hash = end($keyParts);
            
            // Find the item in the database
            $query = "SELECT * FROM cart WHERE user_id = '$userId' AND product_id = $product_id";
            $result = $connection_database->query($query);
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Check if this is the correct item by comparing the hash
                    $itemHash = md5($row['sizes'] . $row['colors'] . $row['stitching'] . $row['price_type']);
                    if ($itemHash === $hash) {
                        // Delete from database
                        $deleteQuery = "DELETE FROM cart WHERE id = " . $row['id'];
                        $connection_database->query($deleteQuery);
                        
                        // Remove from session
                        unset($_SESSION['cart'][$cart_key]);
                        return true;
                    }
                }
            }
        }
        return false;
    }

    // Function to get cart items
    function getCartItems() {
        // Sync cart from database to session
        syncCartFromDatabase();
        
        return isset($_SESSION['cart']) ? $_SESSION['cart'] : array();
    }

    // Function to get cart subtotal (without shipping)
    function getCartSubtotal() {
        $subtotal = 0;
        foreach (getCartItems() as $item) {
            $item_price = $item['price'] + $item['stitching_price'];
            $subtotal += $item_price * $item['quantity'];
        }
        return $subtotal;
    }

    // Function to get total shipping cost
    // function getCartShipping() {
    //     $shipping = 0;
    //     foreach (getCartItems() as $item) {
    //         // Add shipping cost once per item, not per quantity
    //         $shipping += $item['shipping_cost'];
    //     }
    //     return $shipping;
    // }
    
    // Function to get total shipping cost
function getCartShipping() {
    $totalQty = getCartItemCount(); // total quantity across all items
    
    if ($totalQty <= 0) {
        return 0;
    }
    
    // First item = 250, each additional item = +50
    $shipping = 250 + (($totalQty - 1) * 50);
    
    return $shipping;
}

    // Function to get cart total
    function getCartTotal() {
        $subtotal = getCartSubtotal();
        $shipping = getCartShipping();
        return $subtotal + $shipping;
    }

    // Function to get cart item count
    function getCartItemCount() {
        $count = 0;
        foreach (getCartItems() as $item) {
            $count += $item['quantity'];
        }
        return $count;
    }
    
    // Initialize cart on page load
    syncCartFromDatabase();
?>