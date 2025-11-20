<?php
session_start();
include('../includes/config.php');

if (!isset($_SESSION['user_id']) || !isset($_POST['order_id'])) {
    header("Location: view_orders.php");
    exit;
}

$order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
$user_id = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);

if ($order_id === false || $user_id === false) {
    header("Location: view_orders.php");
    exit;
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Check if order is delivered and belongs to user (already using prepared statement)
    $check_sql = "SELECT status FROM orders WHERE order_id = ? AND user_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $order_id, $user_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    $order = mysqli_fetch_assoc($result);
    mysqli_stmt_close($check_stmt);

    if ($order && $order['status'] === 'Delivered') {
        // Update order status (already using prepared statement)
        $update_sql = "UPDATE orders SET status = 'Received' WHERE order_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "i", $order_id);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
    }

    // Commit transaction
    mysqli_commit($conn);
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
}

header("Location: order_history.php");
exit;