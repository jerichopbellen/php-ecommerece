<?php
session_start();
    
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

require_once '../../includes/config.php';

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error'] = "Invalid user ID provided";
    header('Location: index.php');
    exit();
}

$user_id = (int)$_GET['id'];

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $conn->commit();
        $_SESSION['success'] = "User deactivated successfully.";
    } else {
        $conn->rollback();
        $_SESSION['error'] = "No user found with the provided ID.";
    }

    $stmt->close();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

header('Location: index.php');
exit();
?>