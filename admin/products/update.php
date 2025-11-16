<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

include '../../includes/config.php';

$_SESSION['productName'] = $_POST['productName'] ?? '';
$_SESSION['description'] = $_POST['description'] ?? '';
$_SESSION['brand'] = $_POST['brand_id'] ?? '';   
$_SESSION['category'] = $_POST['category_id'] ?? '';
$_SESSION['length'] = $_POST['length'] ?? '';
$_SESSION['width'] = $_POST['width'] ?? '';
$_SESSION['height'] = $_POST['height'] ?? ''; 

if (isset($_POST['submit'])) { 

    // Sanitize and validate inputs
    $product_id = filter_var($_POST['product_id'] ?? 0, FILTER_VALIDATE_INT);
    $name = htmlspecialchars(trim($_POST['productName'] ?? ''), ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8');
    $brand_id = filter_var($_POST['brand_id'] ?? 0, FILTER_VALIDATE_INT);
    $category_id = filter_var($_POST['category_id'] ?? 0, FILTER_VALIDATE_INT);

    $length = filter_var($_POST['length'] ?? 0, FILTER_VALIDATE_FLOAT);
    $width = filter_var($_POST['width'] ?? 0, FILTER_VALIDATE_FLOAT);
    $height = filter_var($_POST['height'] ?? 0, FILTER_VALIDATE_FLOAT);

    $dimension = "{$length} x {$width} x {$height} cm";

    // Validate required fields
    if(empty($name)) {
        $_SESSION['nameError'] = "Please input a product name.";
        header("Location: edit.php?id={$product_id}");
        exit;
    }
    if(empty($description)) {
        $_SESSION['descriptionError'] = "Please input a product description.";
        header("Location: edit.php?id={$product_id}");
        exit;
    }
    if(empty($brand_id) || $brand_id <= 0) {
        $_SESSION['brandError'] = "Please select a brand.";
        header("Location: edit.php?id={$product_id}");
        exit;
    }
    if(empty($category_id) || $category_id <= 0) {
        $_SESSION['categoryError'] = "Please select a category.";
        header("Location: edit.php?id={$product_id}");
        exit;
    }

    if(empty($length) || empty($width) || empty($height) || $length <= 0 || $width <= 0 || $height <= 0) {
        $_SESSION['dimensionError'] = "Please provide valid positive numeric values for all dimensions.";
        header("Location: edit.php?id={$product_id}");
        exit;
    }

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Update query using prepared statement
        $sql = "UPDATE products 
                SET name = ?, description = ?, brand_id = ?, category_id = ?, dimension = ?
                WHERE product_id = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ssiisi', $name, $description, $brand_id, $category_id, $dimension, $product_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to update product.");
        }
        
        mysqli_stmt_close($stmt);
        
        // Commit transaction
        mysqli_commit($conn);

        // Clear error messages after successful update
        unset($_SESSION['productName']);
        unset($_SESSION['description']);    
        unset($_SESSION['brand']);
        unset($_SESSION['category']);
        unset($_SESSION['length']);
        unset($_SESSION['width']);
        unset($_SESSION['height']);
        
        $_SESSION['success'] = "Product updated successfully.";
        header("Location: index.php");
        exit;
        
    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($conn);
        
        $_SESSION['error'] = "Update failed.";
        header("Location: edit.php?id={$product_id}");
        exit;
    }
    }
?>