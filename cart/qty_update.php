<?php
session_start();
include('../includes/config.php');

if (!isset($_SESSION['user_id'])) {
    die('User not authenticated.');
}

if (!isset($_POST['cart_item_id'], $_POST['action'], $_POST['product_qty'])) {
    die('Missing required data.');
}

$user_id = (int) $_SESSION['user_id'];
$cart_item_id = filter_var($_POST['cart_item_id'], FILTER_VALIDATE_INT);
$current_qty = filter_var($_POST['product_qty'], FILTER_VALIDATE_INT);
$action = trim($_POST['action']);

if ($cart_item_id === false || $current_qty === false || $current_qty < 1) {
    die('Invalid input data.');
}

if (!in_array($action, ['increase', 'decrease'], true)) {
    die('Invalid action.');
}

$new_qty = $current_qty;

if ($action === 'increase') {
    $new_qty = $current_qty + 1;
} elseif ($action === 'decrease' && $current_qty > 1) {
    $new_qty = $current_qty - 1;
}

if ($new_qty !== $current_qty) {
    mysqli_begin_transaction($conn);
    
    try {
        $sql = 'UPDATE cart_items SET quantity = ? WHERE cart_item_id = ? AND user_id = ?';
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            throw new Exception('Database error: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, 'iii', $new_qty, $cart_item_id, $user_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Execution error: ' . mysqli_stmt_error($stmt));
        }
        
        mysqli_stmt_close($stmt);
        
        mysqli_commit($conn);
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        die($e->getMessage());
    }
}

header('Location: view_cart.php');
exit;
?>
