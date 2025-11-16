<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

include '../../includes/config.php';

$image_id = (int) $_POST['image_id'];
$alt_text = htmlspecialchars(trim($_POST['alt_text'] ?? ''), ENT_QUOTES, 'UTF-8');
$path     = trim($_POST['existingImage']); // fallback to existing image path

// Persist values in session
$_SESSION['alt_text'] = $_POST['alt_text'];

// Validation
if ($alt_text === '') {
    $_SESSION['altError'] = "Alt text is required.";
    header("Location: edit.php?id={$image_id}");
    exit();
}

if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
    $allowed_types = ['image/png', 'image/jpg', 'image/jpeg'];
    $file_type = $_FILES['image']['type'];

    if (in_array($file_type, $allowed_types)) {
        $source = $_FILES['image']['tmp_name'];
        $safe_filename = basename($_FILES['image']['name']);
        $target = "../product_images/images/" . $safe_filename;
        $path   = '/Furnitures/admin/product_images/images/' . $safe_filename;

        if (!move_uploaded_file($source, $target)) {
            $_SESSION['imageError'] = "File upload failed.";
            header("Location: edit.php?id={$image_id}");
            exit();
        }
    } else {
        $_SESSION['imageError'] = "Wrong file type. Only JPG and PNG allowed.";
        header("Location: edit.php?id={$image_id}");
        exit();
    }
}

// Begin transaction
mysqli_begin_transaction($conn);

try {
    $stmt = $conn->prepare("UPDATE product_images SET alt_text = ?, img_path = ? WHERE image_id = ?");
    $stmt->bind_param("ssi", $alt_text, $path, $image_id);

    if ($stmt->execute()) {
        mysqli_commit($conn);

        // Clear session values
        foreach (['alt_text'] as $field) {
            unset($_SESSION[$field]);
        }

        $_SESSION['success'] = "Product image updated successfully.";
        header("Location: index.php");
        exit;
    } else {
        throw new Exception("Update failed.");
    }
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['error'] = "Update failed. Please try again.";
    header("Location: edit.php?id={$image_id}");
    exit;
}
?>