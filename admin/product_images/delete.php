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
    if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
        throw new Exception("Invalid image ID.");
    }
    
    $id = intval($_GET['id']);
    
    mysqli_begin_transaction($conn);
    
    $stmt = mysqli_prepare($conn, "DELETE FROM product_images WHERE image_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    
    if (mysqli_stmt_affected_rows($stmt) === 0) {
        throw new Exception("Image not found.");
    }
    
    mysqli_stmt_close($stmt);
    
    mysqli_commit($conn);

    $_SESSION['success'] = "Product image deleted successfully.";
    header("Location: index.php");
    exit;
} catch (mysqli_sql_exception $e) {
    // Rollback on database error
    mysqli_rollback($conn);
    $_SESSION['error'] = "Error deleting product image: " . $e->getMessage();
    header("Location: index.php");
    exit;
} catch (Exception $e) {
    if (isset($conn)) {
        mysqli_rollback($conn);
    }
    $_SESSION['error'] = $e->getMessage();
    header("Location: index.php");
    exit;
}

?>