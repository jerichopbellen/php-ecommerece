<?php

session_start();
    
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

include '../../includes/config.php';

$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);

if ($user_id === false || $user_id === null || empty($role)) {
    $_SESSION['error'] = "Invalid input data.";
    header("Location: index.php");
    exit;
}

mysqli_begin_transaction($conn);

try {
    $stmt = mysqli_prepare($conn, "UPDATE users SET role = ? WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $role, $user_id);
    $result = mysqli_stmt_execute($stmt);
    
    if ($result && mysqli_stmt_affected_rows($stmt) > 0) {
        mysqli_commit($conn);
        mysqli_stmt_close($stmt);
        $_SESSION['success'] = "User role updated successfully.";
        header("Location: index.php");
        exit;
    } else {
        mysqli_rollback($conn);
        mysqli_stmt_close($stmt);
        $_SESSION['info'] = "No changes made.";
        header("Location: edit.php?id={$user_id}");
        exit;
    }
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['error'] = "Update failed.";
    header("Location: edit.php?id={$user_id}");
    exit;
}

?>