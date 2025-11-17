<?php   
session_start();
    
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

include '../../includes/config.php';

if (isset($_POST['submit'])) {

    // Input sanitization
    $brand_name = trim($_POST['name']);
    $brand_id = filter_var($_POST['brand_id'], FILTER_VALIDATE_INT);


    if (empty($brand_name)) {
        $_SESSION['nameError'] = "Brand name cannot be empty.";
        header("Location: edit.php?id={$brand_id}");
        exit;
    }

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Check if brand name already exists (excluding current brand)
        $check_stmt = mysqli_prepare($conn, "SELECT brand_id FROM brands WHERE name = ? AND brand_id != ?");
        mysqli_stmt_bind_param($check_stmt, "si", $brand_name, $brand_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            mysqli_stmt_close($check_stmt);
            throw new Exception("Brand name already exists.");
        }
        mysqli_stmt_close($check_stmt);
        
        // Update brand
        $update_stmt = mysqli_prepare($conn, "UPDATE brands SET name = ? WHERE brand_id = ?");
        mysqli_stmt_bind_param($update_stmt, "si", $brand_name, $brand_id);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            throw new Exception("Update failed.");
        }
        
        mysqli_stmt_close($update_stmt);
        
        // Commit transaction
        mysqli_commit($conn);
        $_SESSION['success'] = "Brand updated successfully.";
        header("Location: index.php");
        exit;
        
    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($conn);
        $_SESSION['error'] = $e->getMessage();
        header("Location: edit.php?id={$brand_id}");
        exit;
    }
}
?>
