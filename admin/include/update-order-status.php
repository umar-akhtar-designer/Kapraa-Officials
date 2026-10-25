<?php
    require_once 'config.php';

    if (isset($_POST['order_id']) && isset($_POST['order_status'])) {
        $orderId = $_POST['order_id'];
        $orderStatus = $_POST['order_status'];
        $note = isset($_POST['note']) ? $_POST['note'] : '';
        
        // Update order status with note
        $updateQuery = "UPDATE orders SET order_status = ?, note = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($connection_database, $updateQuery);
        
        // Fixed: Added note parameter and corrected type string to "ssi"
        mysqli_stmt_bind_param($stmt, "ssi", $orderStatus, $note, $orderId);
        
        if (mysqli_stmt_execute($stmt)) {
            // Return success response
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            // Return error response
            header('Content-Type: application/json');
            echo json_encode(['success' => false]);
        }
    }
?>