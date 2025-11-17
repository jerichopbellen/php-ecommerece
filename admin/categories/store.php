<?php
session_start();
    
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}
include '../../includes/config.php';

if (isset($_POST['submit'])) {

    $_SESSION['categoryName'] = $_POST['name'];
    // Input sanitization
    $name = trim($_POST['name']);
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    
    // Validate input
    if (empty($name)) {
        $_SESSION['nameError'] = "Category name cannot be empty.";
        header("Location: create.php");
        exit;
    }

    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Check if category name already exists using prepared statement
        $check_stmt = mysqli_prepare($conn, "SELECT category_id FROM categories WHERE name = ?");
        mysqli_stmt_bind_param($check_stmt, "s", $name);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            mysqli_stmt_close($check_stmt);
            mysqli_rollback($conn);
            $_SESSION['error'] = "Category name already exists.";
            header("Location: create.php");
            exit;
        }
        mysqli_stmt_close($check_stmt);

        // Insert new category using prepared statement
        $insert_stmt = mysqli_prepare($conn, "INSERT INTO categories (name) VALUES(?)");
        mysqli_stmt_bind_param($insert_stmt, "s", $name);
        $result = mysqli_stmt_execute($insert_stmt);
        mysqli_stmt_close($insert_stmt);

        if ($result) {
            // Commit transaction
            mysqli_commit($conn);
            unset($_SESSION['categoryName']);
            $_SESSION['success'] = "Category added successfully.";
            header("Location: index.php");
            exit;
        } else {
            throw new Exception("Failed to insert category.");
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $_SESSION['error'] = "Failed to add category. Please try again.";
        header("Location: create.php");
        exit;
    }
}
?>