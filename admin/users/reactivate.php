<?php
session_start();
    
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

require_once '../../includes/config.php';

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "User ID not provided";
    header('Location: index.php');
    exit();
}

$user_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

if ($user_id === false || $user_id <= 0) {
    $_SESSION['error'] = "Invalid user ID";
    header('Location: index.php');
    exit();
}

try {
    $conn->begin_transaction();
    
    $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $_SESSION['success'] = "User reactivated successfully.";
        $conn->commit();
    } else {
        $_SESSION['error'] = "No user found with the provided ID.";
        $conn->rollback();
    }

    $stmt->close();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

header('Location: index.php');
exit();
?>