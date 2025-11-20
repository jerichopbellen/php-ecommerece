<?php
session_start();
include('../includes/config.php');

if (!isset($_SESSION['user_id'])) {
    die('User not authenticated.');
}

$user_id = intval($_SESSION['user_id']);

if (!empty($_POST['variant_id']) && is_array($_POST['variant_id'])) {
    mysqli_begin_transaction($conn);
    
    try {
        $sql = "UPDATE cart_items SET variant_id = ? WHERE cart_item_id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement");
        }
        
        foreach ($_POST['variant_id'] as $cart_item_id => $variant_id) {
            $variant_id = intval($variant_id);
            $cart_item_id = intval($cart_item_id);
            
            if ($variant_id <= 0 || $cart_item_id <= 0) {
                continue; 
            }
            
            mysqli_stmt_bind_param($stmt, "iii", $variant_id, $cart_item_id, $user_id);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to execute statement");
            }
        }
        
        mysqli_stmt_close($stmt);
        
        mysqli_commit($conn);
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Cart update error: " . $e->getMessage());
        die("An error occurred while updating cart.");
    }
}

header('Location: view_cart.php');
exit;
?>
