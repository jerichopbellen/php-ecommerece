<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

include '../../includes/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if ($id === false || $id === null) {
        $_SESSION['error'] = "Invalid brand ID.";
        header("Location: index.php");
        exit;
    }

    mysqli_begin_transaction($conn);

    $check_stmt = mysqli_prepare($conn, "SELECT product_id FROM products WHERE brand_id = ? LIMIT 1");
    mysqli_stmt_bind_param($check_stmt, "i", $id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($check_result) > 0) {
        mysqli_stmt_close($check_stmt);
        throw new Exception("Cannot delete brand with associated products.");
    }
    mysqli_stmt_close($check_stmt);

    $delete_stmt = mysqli_prepare($conn, "DELETE FROM brands WHERE brand_id = ?");
    mysqli_stmt_bind_param($delete_stmt, "i", $id);
    mysqli_stmt_execute($delete_stmt);
    mysqli_stmt_close($delete_stmt);

    mysqli_commit($conn);

    $_SESSION['success'] = "Brand deleted successfully.";
    header("Location: index.php");
    exit;
} catch (Exception $e) {
    if (isset($conn)) {
        mysqli_rollback($conn);
    }
    $_SESSION['error'] = "Error deleting brand: " . $e->getMessage();
    header("Location: index.php");
    exit;
}
