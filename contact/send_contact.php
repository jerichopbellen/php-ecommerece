<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

include('../includes/config.php');

$user_id = $_SESSION['user_id'];
$name    = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$subject = htmlspecialchars(trim($_POST['subject'] ?? ''), ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars(trim($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8');

if (!$name || !$email || !$subject || !$message) {
    $_SESSION['flash'] = "All fields are required.";
    header("Location: contact.php");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['flash'] = "Invalid email format.";
    header("Location: contact.php");
    exit;
}

mysqli_begin_transaction($conn);

try {
    $sql = "INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $subject, $message);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to insert message.");
    }
    
    mysqli_stmt_close($stmt);
    
    mysqli_commit($conn);
    
    $_SESSION['success'] = "Your message has been sent successfully.";
    header("Location: index.php");
    exit;
    
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    $_SESSION['flash'] = "An error occurred. Please try again.";
    header("Location: contact.php");
    exit;
}