<?php
session_start();
include('../includes/config.php');

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect'] = "Please log in to update your review.";
    header("Location: ../user/login.php");
    exit;
}

$user_id    = intval($_SESSION['user_id']);
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$order_id   = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$variant_id = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : 0;
$rating     = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$comment    = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if ($product_id <= 0 || $variant_id <= 0 || $rating < 1 || $rating > 5) {
    $_SESSION['error'] = "Invalid review data provided.";
    header("Location: order_history.php");
    exit;
}

$comment = substr($comment, 0, 1000);

$badWords = [
    'fuck', 'shit', 'bitch', 'asshole', 'bastard', 'damn', 'crap',
    'tangina', 'putangina', 'bobo', 'tanga', 'gago', 'ulol'
];

foreach ($badWords as $word) {
    $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
    $comment = preg_replace_callback($pattern, function($matches) {
        return str_repeat('*', strlen($matches[0]));
    }, $comment);
}

mysqli_begin_transaction($conn);

try {
    $check_sql = "SELECT review_id FROM reviews WHERE user_id = ? AND product_id = ? AND variant_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "iii", $user_id, $product_id, $variant_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $existing = mysqli_fetch_assoc($check_result);
    mysqli_stmt_close($check_stmt);

    if ($existing) {
        // Update review (using prepared statement)
        $update_sql = "UPDATE reviews SET rating = ?, comment = ?, updated_at = NOW() WHERE review_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "isi", $rating, $comment, $existing['review_id']);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);

        $_SESSION['success'] = "Your review has been updated.";
    } else {
        $_SESSION['info'] = "No existing review found to update.";
    }

    // Commit transaction
    mysqli_commit($conn);
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    $_SESSION['error'] = "Failed to update review. Please try again.";
}

header("Location: order_history.php");
exit;