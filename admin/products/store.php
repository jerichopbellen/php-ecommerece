<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

include '../../includes/config.php';

$_SESSION['productName'] = $_POST['name'];
$_SESSION['description'] = $_POST['description'];
$_SESSION['brand'] = $_POST['brand'];   
$_SESSION['category'] = $_POST['category'];
$_SESSION['length'] = $_POST['length'];
$_SESSION['width'] = $_POST['width'];
$_SESSION['height'] = $_POST['height'];

if (isset($_POST['submit'])) {
    $name = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8');
    $brand = filter_var($_POST['brand'] ?? '', FILTER_VALIDATE_INT);
    $category = filter_var($_POST['category'] ?? '', FILTER_VALIDATE_INT);
    $length = filter_var($_POST['length'] ?? '', FILTER_VALIDATE_FLOAT);
    $width = filter_var($_POST['width'] ?? '', FILTER_VALIDATE_FLOAT);
    $height = filter_var($_POST['height'] ?? '', FILTER_VALIDATE_FLOAT);
    $dimension = $length . ' x ' . $width . ' x ' . $height . ' cm';

    if(empty($name)) {
        $_SESSION['nameError'] = "Please input a product name.";
        header("Location: create.php");
        exit;
    }
    if(empty($description)) {
        $_SESSION['descriptionError'] = "Please input a product description.";
        header("Location: create.php");
        exit;
    }
    if(empty($brand)) {
        $_SESSION['brandError'] = "Please select a brand.";
        header("Location: create.php");
        exit;
    }
    if(empty($category)) {
        $_SESSION['categoryError'] = "Please select a category.";
        header("Location: create.php");
        exit;
    }

    if(empty($length) || empty($width) || empty($height) || $length <= 0 || $width <= 0 || $height <= 0) {
        $_SESSION['dimensionError'] = "Please provide valid positive numeric values for all dimensions.";
        header("Location: create.php");
        exit;
    }

    mysqli_begin_transaction($conn);

    try {
        $sql = "INSERT INTO products (name, description, brand_id, category_id, dimension) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ssiis', $name, $description, $brand, $category, $dimension);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to insert product');
        }
        
        mysqli_stmt_close($stmt);
        
        mysqli_commit($conn);

        foreach (['productName','description','brand','category','length','width','height'] as $field) {
        unset($_SESSION[$field]);
        }
        
        $_SESSION['success'] = "Product added successfully.";
        header("Location: index.php");
        exit;
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        
        $_SESSION['error'] = 'Failed to create product. Please try again.';
        header("Location: create.php");
        exit;
    }
}
?>