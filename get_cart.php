<?php
    require_once 'admin/include/config.php';
    require_once 'cart_functions.php';

    header('Content-Type: application/json');
    header('ngrok-skip-browser-warning: true');

    $cartItems = getCartItems();
    $cartItemsWithKeys = array();

    foreach ($cartItems as $key => $item) {
        // Get product slug for each cart item
        $productQuery = "SELECT slug FROM products WHERE id = " . (int)$item['id'];
        $productResult = $connection_database->query($productQuery);
        
        if ($productResult && $productResult->num_rows > 0) {
            $product = $productResult->fetch_assoc();
            $item['slug'] = $product['slug'];
        } else {
            $item['slug'] = '';
        }
        
        // Make sure cart key is included
        $item['cart_key'] = $key;
        $cartItemsWithKeys[] = $item;
    }

    echo json_encode([
        'success' => true,
        'cart_items' => $cartItemsWithKeys,
        'cart_count' => getCartItemCount(),
        'cart_total' => getCartTotal(),
        'cart_subtotal' => getCartSubtotal(),
        'cart_shipping' => getCartShipping()
    ]);
?>