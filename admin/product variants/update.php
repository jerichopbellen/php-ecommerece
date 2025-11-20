<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

include '../../includes/config.php';

$variant_id = intval($_POST['variant_id']);

$color      = htmlspecialchars(trim($_POST['color'] ?? ''), ENT_QUOTES, 'UTF-8');
$material   = htmlspecialchars(trim($_POST['material'] ?? ''), ENT_QUOTES, 'UTF-8');
$sell_price = filter_var(trim($_POST['sell_price'] ?? ''), FILTER_VALIDATE_FLOAT);
$quantity   = filter_var(trim($_POST['quantity'] ?? ''), FILTER_VALIDATE_INT);

$_SESSION['color']      = $_POST['color'];
$_SESSION['material']   = $_POST['material'];
$_SESSION['sell_price'] = $_POST['sell_price'];
$_SESSION['quantity']   = $_POST['quantity'];

if ($color === '' && $material === '') {
    $_SESSION['variantError'] = 'Please input at least a color or a material.';
    header("Location: edit.php?id={$variant_id}");
    exit();
}

if ($sell_price === false || $sell_price < 0) {
    $_SESSION['priceError'] = 'Please provide a valid non-negative price.';
    header("Location: edit.php?id={$variant_id}");
    exit();
}

if ($quantity === false || $quantity < 0) {
    $_SESSION['quantityError'] = 'Please provide a valid non-negative quantity.';
    header("Location: edit.php?id={$variant_id}");
    exit();
}

mysqli_begin_transaction($conn);

try {
    $stmt1 = $conn->prepare("UPDATE product_variants SET color=?, material=?, price=? WHERE variant_id=?");
    $stmt1->bind_param("ssdi", $color, $material, $sell_price, $variant_id);
    $result1 = $stmt1->execute();
    $stmt1->close();

    $stmt2 = $conn->prepare("UPDATE stocks SET quantity=? WHERE variant_id=?");
    $stmt2->bind_param("ii", $quantity, $variant_id);
    $result2 = $stmt2->execute();
    $stmt2->close();

    if ($result1 && $result2) {
        mysqli_commit($conn);

        foreach (['color','material','sell_price','quantity'] as $field) {
            unset($_SESSION[$field]);
        }

        $_SESSION['success'] = "Product variant updated successfully.";
        header("Location: index.php");
        exit;
    } else {
        throw new Exception("Update failed.");
    }
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['error'] = "Update failed. Please try again.";
    header("Location: edit.php?id={$variant_id}");
    exit;
}
?>