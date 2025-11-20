<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

include '../../includes/config.php';

$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$tag_id     = filter_input(INPUT_POST, 'tag_id', FILTER_VALIDATE_INT);

$_SESSION['product_id'] = $_POST['product_id'];
$_SESSION['tag_id']     = $_POST['tag_id'];

if (!$product_id) {
    $_SESSION['productError'] = "Please select a valid product.";
    header("Location: create.php"); exit();
}
if (!$tag_id) {
    $_SESSION['tagError'] = "Please select a valid tag.";
    header("Location: create.php"); exit();
}

mysqli_begin_transaction($conn);

try {
    $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO product_tags (product_id, tag_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "ii", $product_id, $tag_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($result) {
        mysqli_commit($conn);

        foreach (['product_id','tag_id'] as $field) {
            unset($_SESSION[$field]);
        }

        $_SESSION['success'] = "Product tag added successfully.";
        header("Location: index.php"); exit();
    } else {
        throw new Exception("Failed to execute statement");
    }
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['error'] = "Failed to add product tag. Please try again.";
    header("Location: create.php"); exit();
}
?>