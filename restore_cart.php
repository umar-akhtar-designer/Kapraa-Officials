<?php
    require_once 'admin/include/config.php';
    require_once 'cart_functions.php';

    session_start();

    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($input['cart_items']) && is_array($input['cart_items'])) {
            // Clear existing cart
            if (isset($_SESSION['cart'])) {
                unset($_SESSION['cart']);
            }
            
            // Initialize empty cart
            $cart = [];
            
            // Add each item from localStorage
            foreach ($input['cart_items'] as $item) {
                if (isset($item['id']) && isset($item['cart_key'])) {
                    // Use the existing cart key
                    $cart[$item['cart_key']] = [
                        'id' => $item['id'],
                        'name' => $item['name'] ?? '',
                        'price' => $item['price'] ?? 0,
                        'image' => $item['image'] ?? '',
                        'quantity' => $item['quantity'] ?? 1,
                        'sizes' => $item['sizes'] ?? '',
                        'colors' => $item['colors'] ?? '',  // Added colors field
                        'stitching' => $item['stitching'] ?? 0,
                        'stitching_price' => $item['stitching_price'] ?? 0,
                        'added_at' => $item['added_at'] ?? time()
                    ];
                }
            }
            
            // Save the restored cart
            $_SESSION['cart'] = $cart;
            
            // Save to cookie
            setcookie('cart_data', json_encode($cart), time() + (30 * 24 * 60 * 60), '/');
            
            echo json_encode([
                'success' => true,
                'message' => 'Cart restored successfully',
                'cart_count' => count($cart)
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No cart items provided'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request method'
        ]);
    }
?>