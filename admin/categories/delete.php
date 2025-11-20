<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

include '../../includes/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT) || $_GET['id'] <= 0) {
    $_SESSION['error'] = "Invalid category ID.";
    header("Location: index.php");
    exit;
}

$category_id = (int)$_GET['id'];

try {
    mysqli_begin_transaction($conn);
    
    $check_stmt = mysqli_prepare($conn, "SELECT product_id FROM products WHERE category_id = ? LIMIT 1");
    mysqli_stmt_bind_param($check_stmt, "i", $category_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($check_result) > 0) {
        mysqli_stmt_close($check_stmt);
        mysqli_rollback($conn);
        $_SESSION['error'] = "Cannot delete category with associated products.";
        header("Location: index.php");
        exit;
    }
    mysqli_stmt_close($check_stmt);
    
    $delete_stmt = mysqli_prepare($conn, "DELETE FROM categories WHERE category_id = ?");
    mysqli_stmt_bind_param($delete_stmt, "i", $category_id);
    mysqli_stmt_execute($delete_stmt);
    mysqli_stmt_close($delete_stmt);

    mysqli_commit($conn);

    $_SESSION['success'] = "Category deleted successfully.";
    header("Location: index.php");
    exit;
} catch (mysqli_sql_exception $e) {
    mysqli_rollback($conn);
    $_SESSION['error'] = "Error deleting category: " . $e->getMessage();
    header("Location: index.php");
    exit;
}

?>