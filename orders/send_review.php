<?php
session_start();
include('../includes/config.php');

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect'] = "Please log in to submit a review.";
    header("Location: ../user/login.php");
    exit;
}

$user_id    = $_SESSION['user_id'];
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$order_id   = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
$variant_id = filter_input(INPUT_POST, 'variant_id', FILTER_VALIDATE_INT);
$rating     = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
$comment    = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if (!$product_id || !$order_id || !$variant_id || !$rating || $rating < 1 || $rating > 5) {
    $_SESSION['error'] = "Invalid input data.";
    header("Location: order_history.php");
    exit;
}

$comment = htmlspecialchars($comment, ENT_QUOTES, 'UTF-8');

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
    $verify_stmt = mysqli_prepare($conn, "
        SELECT order_id FROM orders 
        WHERE order_id = ? AND user_id = ? AND status = 'Received'
    ");
    mysqli_stmt_bind_param($verify_stmt, "ii", $order_id, $user_id);
    mysqli_stmt_execute($verify_stmt);
    $verify_result = mysqli_stmt_get_result($verify_stmt);
    
    if (mysqli_num_rows($verify_result) === 0) {
        throw new Exception("Invalid order or order not received.");
    }
    mysqli_stmt_close($verify_stmt);

    $stmt = mysqli_prepare($conn, "
        INSERT INTO reviews (user_id, product_id, variant_id, rating, comment)
        VALUES (?, ?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "iiiis", $user_id, $product_id, $variant_id, $rating, $comment);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to submit review.");
    }
    mysqli_stmt_close($stmt);

    mysqli_commit($conn);
    
    $_SESSION['success'] = "Thank you! Your review has been submitted.";
    header("Location: order_history.php");
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['error'] = $e->getMessage();
    header("Location: order_history.php");
    exit;
}