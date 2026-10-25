<?php
    require_once 'admin/include/config.php';
    require_once 'cart_functions.php';

    header('Content-Type: application/json');
    header('ngrok-skip-browser-warning: true');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        $cart_key = isset($_POST['cart_key']) ? $_POST['cart_key'] : '';
        
        // Debug log
        error_log("Cart update request: action=$action, cart_key=$cart_key");
        
        if (!empty($cart_key)) {
            if ($action === 'update_quantity') {
                $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
                if (updateCartQuantity($cart_key, $quantity)) {
                    // Get updated cart items with keys
                    $cartItems = getCartItems();
                    $cartItemsWithKeys = array();
                    
                    foreach ($cartItems as $key => $item) {
                        $item['cart_key'] = $key;
                        $cartItemsWithKeys[] = $item;
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Cart updated successfully',
                        'cart_items' => $cartItemsWithKeys,
                        'cart_count' => getCartItemCount(),
                        'cart_total' => getCartTotal(),
                        'cart_subtotal' => getCartSubtotal(),
                        'cart_shipping' => getCartShipping()
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to update cart'
                    ]);
                }
            } elseif ($action === 'remove') {
                if (removeFromCart($cart_key)) {
                    // Get updated cart items with keys
                    $cartItems = getCartItems();
                    $cartItemsWithKeys = array();
                    
                    foreach ($cartItems as $key => $item) {
                        $item['cart_key'] = $key;
                        $cartItemsWithKeys[] = $item;
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Item removed from cart',
                        'cart_items' => $cartItemsWithKeys,
                        'cart_count' => getCartItemCount(),
                        'cart_total' => getCartTotal(),
                        'cart_subtotal' => getCartSubtotal(),
                        'cart_shipping' => getCartShipping()
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to remove item from cart'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid action'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Cart key not provided'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request method'
        ]);
    }
?>