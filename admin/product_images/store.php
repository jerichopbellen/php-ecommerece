<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

include '../../includes/config.php';

if (isset($_POST['submit'])) {
    $product  = filter_var(trim($_POST['product']), FILTER_VALIDATE_INT);
    $alt_text = htmlspecialchars(trim($_POST['alt-text']), ENT_QUOTES, 'UTF-8');

    $_SESSION['product']   = $_POST['product'];
    $_SESSION['alt-text']  = $_POST['alt-text'];

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

    if (!isset($_FILES['img_path']) || empty($_FILES['img_path']['name'][0])) {
        $_SESSION['imageError'] = "No files uploaded.";
        header("Location: create.php");
        exit;
    }

    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $targetDir    = '../product_images/images/';

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    mysqli_begin_transaction($conn);

    try {
        $stmt = $conn->prepare("INSERT INTO product_images (img_path, alt_text, product_id) VALUES (?, ?, ?)");

        foreach ($_FILES['img_path']['name'] as $key => $fileName) {
            $fileType = $_FILES['img_path']['type'][$key];
            $tmpName  = $_FILES['img_path']['tmp_name'][$key];
            $error    = $_FILES['img_path']['error'][$key];

            if ($error !== UPLOAD_ERR_OK) {
                throw new Exception("Error uploading file: $fileName");
            }

            if (!in_array($fileType, $allowedTypes)) {
                $invalidFiles = [];
                foreach ($_FILES['img_path']['name'] as $k => $name) {
                    if (!in_array($_FILES['img_path']['type'][$k], $allowedTypes)) {
                        $invalidFiles[] = $name;
                    }
                }
                throw new Exception("Wrong file type for: " . implode(", ", $invalidFiles) . ". Only JPG and PNG allowed.");
            }

            $safeName   = uniqid("img_", true) . "_" . basename($fileName);
            $targetPath = $targetDir . $safeName;

            if (!move_uploaded_file($tmpName, $targetPath)) {
                throw new Exception("Failed to upload file: $fileName");
            }

            $dbPath = '/Furnitures/admin/product_images/images/' . $safeName;

            $stmt->bind_param("ssi", $dbPath, $alt_text, $product);
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert record for $fileName");
            }
        }

        mysqli_commit($conn);

        foreach (['product','alt-text'] as $field) {
            unset($_SESSION[$field]);
        }

        $_SESSION['success'] = "Product images added successfully.";
        header("Location: index.php");
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = "Failed to add product images. " . $e->getMessage();
        header("Location: create.php");
        exit;
    }
}
?>