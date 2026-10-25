<?php
    require_once 'config.php';

    if (isset($_POST['order_id'])) {
        $orderId = $_POST['order_id'];
        
        // Start transaction
        mysqli_begin_transaction($connection_database);
        
        try {
            // Delete order items first
            $deleteItemsQuery = "DELETE FROM order_items WHERE order_id = ?";
            $stmt = mysqli_prepare($connection_database, $deleteItemsQuery);
            mysqli_stmt_bind_param($stmt, "i", $orderId);
            mysqli_stmt_execute($stmt);
            
            // Delete order
            $deleteOrderQuery = "DELETE FROM orders WHERE id = ?";
            $stmt = mysqli_prepare($connection_database, $deleteOrderQuery);
            mysqli_stmt_bind_param($stmt, "i", $orderId);
            mysqli_stmt_execute($stmt);
            
            // Commit transaction
            mysqli_commit($connection_database);
            
            // Return success response
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            // Rollback transaction
            mysqli_rollback($connection_database);
            
            // Return error response
            header('Content-Type: application/json');
            echo json_encode(['success' => false]);
        }
    }
?>