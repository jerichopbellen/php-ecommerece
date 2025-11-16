<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

include '../../includes/config.php';

// store form data in session to repopulate in case of error
$_SESSION['productName'] = $_POST['name'];
$_SESSION['description'] = $_POST['description'];
$_SESSION['brand'] = $_POST['brand'];   
$_SESSION['category'] = $_POST['category'];
$_SESSION['length'] = $_POST['length'];
$_SESSION['width'] = $_POST['width'];
$_SESSION['height'] = $_POST['height'];

if (isset($_POST['submit'])) {
    // Input sanitization
    $name = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8');
    $brand = filter_var($_POST['brand'] ?? '', FILTER_VALIDATE_INT);
    $category = filter_var($_POST['category'] ?? '', FILTER_VALIDATE_INT);
    $length = filter_var($_POST['length'] ?? '', FILTER_VALIDATE_FLOAT);
    $width = filter_var($_POST['width'] ?? '', FILTER_VALIDATE_FLOAT);
    $height = filter_var($_POST['height'] ?? '', FILTER_VALIDATE_FLOAT);
    // Construct dimension string
    $dimension = $length . ' x ' . $width . ' x ' . $height . ' cm';

    // Validate required fields
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

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Insert into database using prepared statement
        $sql = "INSERT INTO products (name, description, brand_id, category_id, dimension) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ssiis', $name, $description, $brand, $category, $dimension);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to insert product');
        }
        
        mysqli_stmt_close($stmt);
        
        // Commit transaction
        mysqli_commit($conn);

        // Clear form data from session after successful insertion
        foreach (['productName','description','brand','category','length','width','height'] as $field) {
        unset($_SESSION[$field]);
        }
        
        $_SESSION['success'] = "Product added successfully.";
        header("Location: index.php");
        exit;
        
    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($conn);
        
        $_SESSION['error'] = 'Failed to create product. Please try again.';
        header("Location: create.php");
        exit;
    }
}
?>