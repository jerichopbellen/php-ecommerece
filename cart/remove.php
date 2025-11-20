<?php
    session_start();
    if (!isset($_SESSION['user_id'])) {
        die('User not authenticated.');
    }
    
    include('../includes/config.php'); 
    
    $cart_item_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if ($cart_item_id === false || $cart_item_id === null) {
        die('Invalid cart item ID.');
    }
    
    $stmt = mysqli_prepare($conn, "DELETE FROM cart_items WHERE cart_item_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $cart_item_id);
    
    mysqli_begin_transaction($conn);
    
    try {
        $result = mysqli_stmt_execute($stmt);
        
        if ($result) {
            mysqli_commit($conn);
            mysqli_stmt_close($stmt);
            header("Location: view_cart.php");
            exit();
        } else {
            throw new Exception("Delete failed");
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        mysqli_stmt_close($stmt);
        die('Error removing item from cart.');
    }
?>