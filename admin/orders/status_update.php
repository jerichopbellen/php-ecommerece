<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

include '../../includes/config.php';
include '../../includes/mail.php';
include '../../includes/order_email_helpers.php';

$order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['status']) || $order_id <= 0) {
    header("Location: view.php?id=$order_id");
    exit;
}

$newStatus = trim($_POST['status']);
$allowed = ['pending', 'processing', 'shipped', 'delivered', 'received', 'cancelled'];
if (!in_array($newStatus, $allowed, true)) {
    header("Location: view.php?id=$order_id&msg=invalid_status");
    exit;
}

function redirect_with_msg($order_id, $msg) {
    header("Location: view.php?id=" . (int)$order_id . "&msg=" . urlencode($msg));
    exit;
}

function generateTrackingNumber($conn) {
    $prefix = 'ORD-' . date('Ymd') . '-';
    do {
        $suffix = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $tracking_number = $prefix . $suffix;
        $check_stmt = mysqli_prepare($conn, "SELECT 1 FROM orders WHERE tracking_number = ?");
        mysqli_stmt_bind_param($check_stmt, "s", $tracking_number);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        $exists = mysqli_stmt_num_rows($check_stmt) > 0;
        mysqli_stmt_close($check_stmt);
    } while ($exists);
    return $tracking_number;
}

// Fetch current status using prepared statement
$stmt = mysqli_prepare($conn, "SELECT status FROM orders WHERE order_id = ?");
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if (!$res || mysqli_num_rows($res) === 0) {
    mysqli_stmt_close($stmt);
    redirect_with_msg($order_id, 'order_not_found');
}
$currentStatus = strtolower(mysqli_fetch_assoc($res)['status']);
mysqli_stmt_close($stmt);

// Define transition that requires stock deduction
$requiresStockDeduction = ($currentStatus === 'processing' && $newStatus === 'shipped');

// If no stock deduction needed, just update
if (!$requiresStockDeduction) {
    $stmt = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE order_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $newStatus, $order_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Send status update email
    $details = buildOrderDetailsHtml($conn, $order_id);
    $meta    = $details['meta'];
    $items   = $details['html'];
    $address = buildAddressBlock($meta);

    $stmt = mysqli_prepare($conn, "
        SELECT u.email, u.first_name
        FROM orders o
        JOIN users u ON u.user_id = o.user_id
        WHERE o.order_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $user_res = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($user_res);
    mysqli_stmt_close($stmt);

    if ($user) {
        $subject = "Order #{$meta['order_id']} Status Updated";
        $body = "
            <h2>Status Update</h2>
            <p>Your order <strong>#{$meta['order_id']}</strong> status is now: <strong>" . htmlspecialchars($newStatus) . "</strong>.</p>
            " . ($newStatus !== 'processing' ? "<p><strong>Tracking:</strong> " . htmlspecialchars($meta['tracking_number'] ?? '—') . "</p>" : "") . "
            <p><strong>Ship to:</strong> {$address}</p>
            <h3>Order Details</h3>
            {$items}
        ";

        sendMail($user['email'], $user['first_name'], $subject, $body, $mailConfig);
    }

    $_SESSION['success'] = "Order status updated successfully.";
    redirect_with_msg($order_id, 'status_updated');
}

// Begin stock deduction transaction
mysqli_begin_transaction($conn);

try {
    $stmt = mysqli_prepare($conn, "
        SELECT variant_id, quantity 
        FROM view_order_transaction_details 
        WHERE order_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $items = mysqli_stmt_get_result($stmt);

    if (!$items || mysqli_num_rows($items) === 0) {
        mysqli_stmt_close($stmt);
        throw new Exception('no_items');
    }
    mysqli_stmt_close($stmt);

    // Prepare stock update statement
    $update_stmt = mysqli_prepare($conn, "
        UPDATE stocks 
        SET quantity = quantity - ? 
        WHERE variant_id = ? AND quantity >= ?
    ");

    while ($row = mysqli_fetch_assoc($items)) {
        $vid = (int) $row['variant_id'];
        $qty = (int) $row['quantity'];
        
        if ($vid <= 0 || $qty <= 0) {
            throw new Exception('invalid_item_data');
        }

        mysqli_stmt_bind_param($update_stmt, "iii", $qty, $vid, $qty);
        mysqli_stmt_execute($update_stmt);

        if (mysqli_stmt_affected_rows($update_stmt) === 0) {
            throw new Exception('stock_deduction_failed');
        }
    }
    mysqli_stmt_close($update_stmt);

    // Generate tracking number and update order
    $tracking_number = generateTrackingNumber($conn);
    $order_stmt = mysqli_prepare($conn, "
        UPDATE orders 
        SET status = ?, tracking_number = ? 
        WHERE order_id = ?
    ");
    mysqli_stmt_bind_param($order_stmt, "ssi", $newStatus, $tracking_number, $order_id);
    mysqli_stmt_execute($order_stmt);
    mysqli_stmt_close($order_stmt);

    mysqli_commit($conn);

    // Send status update email
    $details = buildOrderDetailsHtml($conn, $order_id);
    $meta    = $details['meta'];
    $items   = $details['html'];
    $address = buildAddressBlock($meta);

    $stmt = mysqli_prepare($conn, "
        SELECT u.email, u.first_name
        FROM orders o
        JOIN users u ON u.user_id = o.user_id
        WHERE o.order_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $user_res = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($user_res);
    mysqli_stmt_close($stmt);

    if ($user) {
        $subject = "Order #{$meta['order_id']} Status Updated";
        $body = "
            <h2>Status Update</h2>
            <p>Your order <strong>#{$meta['order_id']}</strong> status is now: <strong>" . htmlspecialchars($newStatus) . "</strong>.</p>
            <p><strong>Tracking:</strong> " . htmlspecialchars($tracking_number) . "</p>
            <p><strong>Ship to:</strong> {$address}</p>
            <h3>Order Details</h3>
            {$items}
        ";

        sendMail($user['email'], $user['first_name'], $subject, $body, $mailConfig);
    }

    $_SESSION['success'] = "Order status updated successfully. Stock deducted.";
    redirect_with_msg($order_id, 'shipped_and_stock_deducted');

} catch (Exception $e) {
    mysqli_rollback($conn);
    redirect_with_msg($order_id, $e->getMessage());
}