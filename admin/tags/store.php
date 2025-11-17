<?php
session_start();
    
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

include '../../includes/config.php';

// Input sanitization
$name = trim($_POST['name'] ?? '');

// Begin transaction
mysqli_begin_transaction($conn);


if(isset($_POST['submit'])) {

    $_SESSION['tagName'] = $_POST['name'] ?? '';

    if (empty($_POST['name'])) {
        $_SESSION['nameError'] = "Tag name is required.";
        header("Location: create.php");
        exit;
    }

    try {
        // Check if tag name already exists using prepared statement
        $check_stmt = mysqli_prepare($conn, "SELECT tag_id FROM tags WHERE name = ?");
        mysqli_stmt_bind_param($check_stmt, "s", $name);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            mysqli_stmt_close($check_stmt);
            mysqli_rollback($conn);
            $_SESSION['error'] = "Tag name already exists.";
            header("Location: index.php");
            exit;
        }
        mysqli_stmt_close($check_stmt);
        
        // Insert new tag using prepared statement
        $insert_stmt = mysqli_prepare($conn, "INSERT INTO tags (name) VALUES (?)");
        mysqli_stmt_bind_param($insert_stmt, "s", $name);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            mysqli_commit($conn);
            mysqli_stmt_close($insert_stmt);
            unset($_SESSION['tagName']);
            $_SESSION['success'] = "Tag added successfully.";
            header("Location: index.php");
            exit;
        } else {
            throw new Exception("Failed to insert tag.");
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = "Failed to add tag.";
        header("Location: index.php");
        exit;
    }

}