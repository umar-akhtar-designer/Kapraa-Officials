<?php
    require_once 'admin/include/config.php';
    require_once 'cart_functions.php';

    header('Content-Type: application/json');
    header('ngrok-skip-browser-warning: true'); // bypass ngrok interstitial for AJAX

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        $sizes = isset($_POST['sizes']) ? $_POST['sizes'] : '';
        $colors = isset($_POST['colors']) ? $_POST['colors'] : ''; // Added colors parameter
        $stitching = isset($_POST['stitching']) ? (int)$_POST['stitching'] : 0;
        $price_type = isset($_POST['price_type']) ? $_POST['price_type'] : 'regular';
        
        if ($product_id > 0) {
            if (addToCart($product_id, $quantity, $sizes, $colors, $stitching, $price_type)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Product added to cart successfully',
                    'cart_count' => getCartItemCount()
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to add product to cart'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid product ID'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request method'
        ]);
    }
?>