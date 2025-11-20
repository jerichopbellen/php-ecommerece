<?php
session_start();
    
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

include '../../includes/config.php';

if (isset($_POST['submit'])) {

    $name = trim($_POST['category_name']);
    $category_id = (int)$_POST['category_id'];

    if (empty($name)) {
        $_SESSION['nameError'] = "Category name cannot be empty.";
        header("Location: edit.php?id={$category_id}");
        exit;
    }

    mysqli_begin_transaction($conn);

    try {
        $check_stmt = mysqli_prepare($conn, "SELECT category_id FROM categories WHERE name = ? AND category_id != ?");
        mysqli_stmt_bind_param($check_stmt, "si", $name, $category_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            mysqli_stmt_close($check_stmt);
            throw new Exception("Category name already exists.");
        }
        mysqli_stmt_close($check_stmt);
        
        $update_stmt = mysqli_prepare($conn, "UPDATE categories SET name = ? WHERE category_id = ?");
        mysqli_stmt_bind_param($update_stmt, "si", $name, $category_id);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            mysqli_stmt_close($update_stmt);
            throw new Exception("Update failed.");
        }
        
        mysqli_stmt_close($update_stmt);
        
        mysqli_commit($conn);
        
        $_SESSION['success'] = "Category updated successfully.";
        header("Location: index.php");
        exit;
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        
        $_SESSION['error'] = $e->getMessage();
        header("Location: edit.php?id={$category_id}");
        exit;
    }
}
?>