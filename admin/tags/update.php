<?php
session_start();
    
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

include '../../includes/config.php';

$tag_id = filter_input(INPUT_POST, 'tag_id', FILTER_VALIDATE_INT);
$name = trim($_POST['name'] ?? '');

mysqli_begin_transaction($conn);

if(isset($_POST['submit'])) {

    $_SESSION['tagName'] = $_POST['name'] ?? '';

    if (empty($_POST['name'])) {
        $_SESSION['nameError'] = "Tag name is required.";
        header("Location: edit.php?id=$tag_id");
        exit;
    }
}
try {
    $check_stmt = mysqli_prepare($conn, "SELECT tag_id FROM tags WHERE name = ? AND tag_id != ?");
    mysqli_stmt_bind_param($check_stmt, "si", $name, $tag_id);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        mysqli_stmt_close($check_stmt);
        throw new Exception("Tag name already exists.");
    }
    mysqli_stmt_close($check_stmt);
    
    $update_stmt = mysqli_prepare($conn, "UPDATE tags SET name = ? WHERE tag_id = ?");
    mysqli_stmt_bind_param($update_stmt, "si", $name, $tag_id);
    
    if (!mysqli_stmt_execute($update_stmt)) {
        mysqli_stmt_close($update_stmt);
        throw new Exception("Failed to update tag.");
    }
    
    mysqli_stmt_close($update_stmt);
    
    mysqli_commit($conn);
    unset($_SESSION['tagName']);
    $_SESSION['success'] = "Tag updated successfully.";
    header("Location: index.php");
    exit;
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    
    $_SESSION['error'] = $e->getMessage();
    header("Location: edit.php?id=$tag_id");
    exit;
}