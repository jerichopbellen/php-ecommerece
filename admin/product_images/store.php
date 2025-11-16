<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

include '../../includes/config.php';

if (isset($_POST['submit'])) {
    // Sanitize inputs
    $product  = filter_var(trim($_POST['product']), FILTER_VALIDATE_INT);
    $alt_text = htmlspecialchars(trim($_POST['alt-text']), ENT_QUOTES, 'UTF-8');

    // Persist values in session
    $_SESSION['product']   = $_POST['product'];
    $_SESSION['alt-text']  = $_POST['alt-text'];

    // Validation
    if ($product === false) {
        $_SESSION['productError'] = "Please select a valid product.";
        header("Location: create.php");
        exit;
    }

    if ($alt_text === '') {
        $_SESSION['altError'] = "Alt text is required.";
        header("Location: create.php");
        exit;
    }

    if (!isset($_FILES['img_path']) || $_FILES['img_path']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['imageError'] = "No file uploaded.";
        header("Location: create.php");
        exit;
    }

    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $fileType = $_FILES['img_path']['type'];

    if (!in_array($fileType, $allowedTypes)) {
        $_SESSION['imageError'] = "Wrong file type. Only JPG and PNG allowed.";
        header("Location: create.php");
        exit;
    }

    $fileName   = basename($_FILES['img_path']['name']);
    $targetDir  = '../product_images/images/';
    $targetPath = $targetDir . $fileName;

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    if (!move_uploaded_file($_FILES['img_path']['tmp_name'], $targetPath)) {
        $_SESSION['imageError'] = "Failed to upload file.";
        header("Location: create.php");
        exit;
    }

    $dbPath = '/Furnitures/admin/product_images/images/' . $fileName;

    // Transaction
    mysqli_begin_transaction($conn);

    try {
        $stmt = $conn->prepare("INSERT INTO product_images (img_path, alt_text, product_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $dbPath, $alt_text, $product);

        if ($stmt->execute()) {
            mysqli_commit($conn);

            // Clear session values
            foreach (['product','alt-text'] as $field) {
                unset($_SESSION[$field]);
            }

            $_SESSION['success'] = "Product image added successfully.";
            header("Location: index.php");
            exit;
        } else {
            throw new Exception("Failed to execute statement.");
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = "Failed to add product image. Please try again.";
        header("Location: create.php");
        exit;
    }
}
?>