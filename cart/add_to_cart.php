<?php
session_start();
include('../includes/config.php');

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect'] = "You need to login to be able to add to cart.";
    header("Location: ../user/login.php");
    exit;
}

$user_id = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);
$product_id = filter_var($_POST['product_id'] ?? 0, FILTER_VALIDATE_INT);
$variant_id = isset($_POST['variant_id']) ? filter_var($_POST['variant_id'], FILTER_VALIDATE_INT) : null;
$quantity = 1;

if (!$user_id || !$product_id) {
    die('Error: Invalid user or product ID.');
}

if (isset($_POST['submit'])) {

    mysqli_begin_transaction($conn);

    try {
        if (!$variant_id) {
            $variant_query = 'SELECT variant_id FROM product_variants WHERE product_id = ? ORDER BY variant_id ASC LIMIT 1';
            $stmt_variant = mysqli_prepare($conn, $variant_query);
            mysqli_stmt_bind_param($stmt_variant, 'i', $product_id);
            mysqli_stmt_execute($stmt_variant);
            mysqli_stmt_bind_result($stmt_variant, $first_variant_id);
            mysqli_stmt_fetch($stmt_variant);
            mysqli_stmt_close($stmt_variant);

            if ($first_variant_id) {
                $variant_id = filter_var($first_variant_id, FILTER_VALIDATE_INT);
            } else {
                throw new Exception('No variants found for this product.');
            }
        }

        $check_sql = 'SELECT cart_item_id, quantity FROM cart_items WHERE variant_id = ? AND user_id = ?';
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, 'ii', $variant_id, $user_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);

        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            mysqli_stmt_bind_result($check_stmt, $cart_item_id, $current_qty);
            mysqli_stmt_fetch($check_stmt);
            mysqli_stmt_close($check_stmt);

            $new_qty = $current_qty + $quantity;
            $update_sql = 'UPDATE cart_items SET quantity = ? WHERE cart_item_id = ? AND user_id = ?';
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, 'iii', $new_qty, $cart_item_id, $user_id);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
        } else {
            mysqli_stmt_close($check_stmt);

            $insert_sql = 'INSERT INTO cart_items (variant_id, user_id, quantity) VALUES (?, ?, ?)';
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, 'iii', $variant_id, $user_id, $quantity);
            mysqli_stmt_execute($insert_stmt);
            mysqli_stmt_close($insert_stmt);
        }

        mysqli_commit($conn);

        header('Location: ../index.php');
        exit;

    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($conn);
        die('Error: ' . htmlspecialchars($e->getMessage()));
    }

} else {
    echo 'Invalid request.';
}
?>
