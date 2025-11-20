<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

include '../../includes/config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    if (!isset($_GET['product_id']) || !is_numeric($_GET['product_id']) ||
        !isset($_GET['tag_id']) || !is_numeric($_GET['tag_id'])) {
        throw new Exception("Invalid product or tag ID.");
    }

    $product_id = intval($_GET['product_id']);
    $tag_id     = intval($_GET['tag_id']);

    mysqli_begin_transaction($conn);

    $stmt = $conn->prepare("DELETE FROM product_tags WHERE product_id = ? AND tag_id = ?");
    $stmt->bind_param("ii", $product_id, $tag_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception("This tag is not linked to the product.");
    }

    mysqli_commit($conn);
    $_SESSION['success'] = "Tag removed from product successfully.";
} catch (Exception $e) {
    if (isset($conn)) {
        mysqli_rollback($conn);
    }
    $_SESSION['error'] = "Error removing tag: " . $e->getMessage();
}

header("Location: index.php");
exit;