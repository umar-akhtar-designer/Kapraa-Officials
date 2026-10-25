<?php
    require_once 'config.php';

    if (isset($_GET['id'])) {
        $orderId = $_GET['id'];
        
        // Get order details
        $orderQuery = "SELECT * FROM orders WHERE id = ?";
        $stmt = mysqli_prepare($connection_database, $orderQuery);
        mysqli_stmt_bind_param($stmt, "i", $orderId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $order = mysqli_fetch_assoc($result);
        
        // Get order items with product information
        $itemsQuery = "SELECT oi.*, p.image FROM order_items oi 
                      LEFT JOIN products p ON oi.product_id = p.id 
                      WHERE oi.order_id = ?";
        $stmt = mysqli_prepare($connection_database, $itemsQuery);
        mysqli_stmt_bind_param($stmt, "i", $orderId);
        mysqli_stmt_execute($stmt);
        $itemsResult = mysqli_stmt_get_result($stmt);
        $items = mysqli_fetch_all($itemsResult, MYSQLI_ASSOC);
        
        // Build absolute image URL
        $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $isLocal = (strpos($host,'localhost')!==false||strpos($host,'127.0.0.1')!==false||strpos($host,'192.168.')!==false);
        $baseUrl = $isLocal ? 'http://' . $host . '/kapraa' : 'https://' . $host;

        // Add full image path to each item
        foreach ($items as &$item) {
            // Use product image from JOIN, fallback to stored product_image in order_items
            $imageField = !empty($item['image']) ? $item['image'] : ($item['product_image'] ?? '');
            if (!empty($imageField)) {
                $images = explode(',', $imageField);
                $imagePaths = array_map(function($img) use ($baseUrl) {
                    return $baseUrl . '/admin/images/product/' . trim($img);
                }, $images);
                $item['image_path'] = implode(',', $imagePaths);
            } else {
                $item['image_path'] = '';
            }
        }
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode([
            'order' => $order,
            'items' => $items
        ]);
    }
?>