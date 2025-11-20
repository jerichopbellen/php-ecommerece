<?php
session_start();
include('../includes/config.php');

if (!isset($_SESSION['user_id']) || !isset($_POST['order_id'])) {
    header("Location: view_orders.php");
    exit;
}

$order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
$user_id = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);

if ($order_id === false || $user_id === false || $order_id <= 0 || $user_id <= 0) {
    $_SESSION['error'] = 'Invalid order ID.';
    header("Location: view_orders.php");
    exit;
}

mysqli_begin_transaction($conn);

try {
    $check_sql = "SELECT status FROM orders WHERE order_id = ? AND user_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $order_id, $user_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    $order = mysqli_fetch_assoc($result);

    if ($order && in_array($order['status'], ['Pending', 'Processing'])) {
        $cancel_sql = "UPDATE orders SET status = 'Cancelled', cancelled_at = NOW() WHERE order_id = ?";
        $cancel_stmt = mysqli_prepare($conn, $cancel_sql);
        mysqli_stmt_bind_param($cancel_stmt, "i", $order_id);
        mysqli_stmt_execute($cancel_stmt);
        
        mysqli_commit($conn);
        $_SESSION['success'] = 'Order cancelled successfully.';
    } else {
        mysqli_rollback($conn);
        $_SESSION['error'] = 'Order cannot be cancelled.';
    }
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['error'] = 'An error occurred while cancelling the order.';
}

header("Location: view_orders.php");
exit;