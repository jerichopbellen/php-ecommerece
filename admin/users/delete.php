<?php
session_start();
    
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

require_once '../../includes/config.php';

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT) || $_GET['id'] <= 0) {
    $_SESSION['error'] = "Invalid user ID provided";
    header('Location: index.php');
    exit();
}

$user_id = (int)$_GET['id'];

if ($user_id === $_SESSION['user_id']) {
    $_SESSION['error'] = "Cannot delete your own account.";
    header('Location: index.php');
    exit();
}

try {
    $conn->begin_transaction();

    $adminCheckStmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $adminCheckStmt->bind_param("i", $user_id);
    $adminCheckStmt->execute();
    $adminResult = $adminCheckStmt->get_result();
    $userData = $adminResult->fetch_assoc();
    $adminCheckStmt->close();

    if (!$userData) {
        throw new Exception("User not found.");
    }

    if ($userData['role'] === 'admin') {
        throw new Exception("Cannot delete admin users.");
    }

    $checkStmt = $conn->prepare("
        SELECT COUNT(*) as order_count 
        FROM orders 
        WHERE user_id = ? AND status NOT IN ('Received', 'Cancelled')
    ");
    $checkStmt->bind_param("i", $user_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();
    $checkStmt->close();

    if ($row['order_count'] > 0) {
        throw new Exception("Cannot delete user with active orders.");
    }

    $stmt = $conn->prepare("
        UPDATE users 
        SET 
            is_deleted = 1, 
            deleted_at = NOW(),
            is_active = 0,
            email = CONCAT('deleted_', user_id, '@example.com'),
            first_name = 'Deleted',
            last_name = 'User'
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception("Failed to delete user.");
    }
    $stmt->close();

    $cartStmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
    $cartStmt->bind_param("i", $user_id);
    $cartStmt->execute();
    $cartStmt->close();

    $conn->commit();
    $_SESSION['success'] = "User deleted successfully.";

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = $e->getMessage();
}

header('Location: index.php');
exit();
?>