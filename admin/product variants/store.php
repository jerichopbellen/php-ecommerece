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
    $product_id = filter_var(trim($_POST['product'] ?? ''), FILTER_VALIDATE_INT);
    $color      = htmlspecialchars(trim($_POST['color'] ?? ''), ENT_QUOTES, 'UTF-8');
    $material   = htmlspecialchars(trim($_POST['material'] ?? ''), ENT_QUOTES, 'UTF-8');
    $price      = filter_var(trim($_POST['price'] ?? ''), FILTER_VALIDATE_FLOAT);
    $quantity   = filter_var(trim($_POST['quantity'] ?? ''), FILTER_VALIDATE_INT);

    // Persist values in session for repopulation
    $_SESSION['product']  = $_POST['product'];
    $_SESSION['color']    = $_POST['color'];
    $_SESSION['material'] = $_POST['material'];
    $_SESSION['price']    = $_POST['price'];
    $_SESSION['quantity'] = $_POST['quantity'];

    // Validation
    if ($product_id === false) {
        $_SESSION['productError'] = "Please select a valid product.";
        header("Location: create.php");
        exit;
    }

    if ($color === '' && $material === '') {
        $_SESSION['variantError'] = "Please input at least a color or a material.";
        header("Location: create.php");
        exit;
    }

    if ($price === false || $price < 0) {
        $_SESSION['priceError'] = "Please provide a valid non-negative price.";
        header("Location: create.php");
        exit;
    }

    if ($quantity === false || $quantity < 0) {
        $_SESSION['quantityError'] = "Please provide a valid non-negative quantity.";
        header("Location: create.php");
        exit;
    }

    // Transaction
    mysqli_begin_transaction($conn);

    try {
        // Insert variant
        $stmt1 = $conn->prepare("INSERT INTO product_variants (color, material, price, product_id) VALUES (?, ?, ?, ?)");
        $stmt1->bind_param("ssdi", $color, $material, $price, $product_id);
        if (!$stmt1->execute()) {
            throw new Exception("Failed to insert product variant");
        }
        $variant_id = $conn->insert_id;
        $stmt1->close();

        // Insert stock
        $stmt2 = $conn->prepare("INSERT INTO stocks (quantity, variant_id) VALUES (?, ?)");
        $stmt2->bind_param("ii", $quantity, $variant_id);
        if (!$stmt2->execute()) {
            throw new Exception("Failed to insert stock");
        }
        $stmt2->close();

        mysqli_commit($conn);

        // Clear session values after success
        foreach (['product','color','material','price','quantity'] as $field) {
            unset($_SESSION[$field]);
        }

        $_SESSION['success'] = "Product variant added successfully.";
        header("Location: index.php");
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = "Failed to add product variant. Please try again.";
        header("Location: create.php");
        exit;
    }
}
?>