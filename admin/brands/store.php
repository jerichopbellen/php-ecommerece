<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

include '../../includes/config.php';

if (isset($_POST['submit'])) {
    $_SESSION['brandName'] = $_POST['name'];
    $name = trim($_POST['name']);
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    
    if (empty($name)) {
        $_SESSION['nameError'] = "Brand name cannot be empty.";
        header("Location: create.php");
        exit;
    }
    
    mysqli_begin_transaction($conn);
    
    try {
        $check_sql = "SELECT brand_id FROM brands WHERE name = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "s", $name);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            mysqli_stmt_close($check_stmt);
            mysqli_rollback($conn);
            $_SESSION['error'] = "Brand name already exists.";
            header("Location: create.php");
            exit;
        }
        mysqli_stmt_close($check_stmt);
        
        $sql = "INSERT INTO brands (name) VALUES (?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $name);
        $result = mysqli_stmt_execute($stmt);
        
        if ($result) {
            mysqli_commit($conn);
            mysqli_stmt_close($stmt);
            unset($_SESSION['brandName']);
            $_SESSION['success'] = "Brand added successfully.";
            header("Location: index.php");
            exit;
        } else {
            throw new Exception("Failed to insert brand");
        }
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = "Failed to add brand. Please try again.";
        header("Location: create.php");
        exit;
    }
}