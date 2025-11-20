<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

include '../../includes/config.php';

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$reply = trim($_POST['reply'] ?? '');

if (!$id || $id <= 0 || empty($reply)) {
    $_SESSION['error'] = "Invalid input provided.";
    header("Location: index.php?msg=invalid");
    exit;
}

$reply = strip_tags($reply);
$reply = htmlspecialchars($reply, ENT_QUOTES, 'UTF-8');

mysqli_begin_transaction($conn);

try {
    $sql = "UPDATE contact_messages SET reply = ?, replied_at = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement");
    }
    
    mysqli_stmt_bind_param($stmt, "si", $reply, $id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to execute statement");
    }
    
    if (mysqli_stmt_affected_rows($stmt) === 0) {
        throw new Exception("No message found with that ID");
    }
    
    mysqli_stmt_close($stmt);
    
    mysqli_commit($conn);
    
    $_SESSION['success'] = "Reply sent successfully.";
    header("Location: index.php?msg=replied");
    exit;
    
} catch (Exception $e) {
    mysqli_rollback(mysql: $conn);
    
    $_SESSION['error'] = "Failed to send reply. Please try again.";
    header("Location: index.php?msg=error");
    exit;
}