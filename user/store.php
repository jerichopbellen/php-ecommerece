<?php
session_start();
include("../includes/config.php");
include("../includes/header.php");

// Sanitize and validate input
$first_name   = trim(htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8'));
$last_name    = trim(htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8'));
$email        = trim($_POST['email'] ?? '');
$password     = $_POST['password'] ?? '';
$confirmPass  = $_POST['confirmPass'] ?? '';

if (!$first_name || !$last_name || !$email || !$password || !$confirmPass) {
    $_SESSION['error'] = 'All fields are required.';
    header("Location: register.php");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Invalid email format.';
    header("Location: register.php");
    exit();
}

if ($password !== $confirmPass) {
    $_SESSION['error'] = 'Passwords do not match.';
    header("Location: register.php");
    exit();
}

// Check if email already exists
$emailCheckSql = "SELECT user_id FROM users WHERE email = ?";
$emailCheckStmt = mysqli_prepare($conn, $emailCheckSql);
mysqli_stmt_bind_param($emailCheckStmt, 's', $email);
mysqli_stmt_execute($emailCheckStmt);
mysqli_stmt_store_result($emailCheckStmt);

if (mysqli_stmt_num_rows($emailCheckStmt) > 0) {
    mysqli_stmt_close($emailCheckStmt);
    $_SESSION['error'] = 'Email is already registered. Please use a different one.';
    header("Location: register.php");
    exit();
}
mysqli_stmt_close($emailCheckStmt);

// Handle profile photo upload
$img_path = null;

if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    $file = $_FILES['profile_photo'];

    if (in_array($file['type'], $allowedTypes)) {
        $filename = basename($file['name']);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $newName = uniqid('profile_', true) . '.' . $ext;

        $targetDir =  __DIR__ . "/avatars/";
        $targetPath = $targetDir . $newName;
        $webPath = "/Furnitures/user/avatars/" . $newName;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $img_path = $webPath;
        } else {
            $_SESSION['error'] = "Couldn't save uploaded image.";
            header("Location: register.php");
            exit();
        }
    } else {
        $_SESSION['error'] = 'Invalid image type. Only JPG, PNG, and GIF are allowed.';
        header("Location: register.php");
        exit();
    }
}

// Begin transaction
mysqli_begin_transaction($conn);

try {
    // Hash password (consider using password_hash() instead of sha1)
    $hashed_password = sha1($password);

    // Insert user
    $sql = "INSERT INTO users (first_name, last_name, email, password_hash, img_path) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sssss', $first_name, $last_name, $email, $hashed_password, $img_path);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to insert user');
    }
    
    $userId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // Fetch role
    $roleQuery = "SELECT role FROM users WHERE user_id = ?";
    $roleStmt = mysqli_prepare($conn, $roleQuery);
    mysqli_stmt_bind_param($roleStmt, 'i', $userId);
    mysqli_stmt_execute($roleStmt);
    $roleResult = mysqli_stmt_get_result($roleStmt);
    $roleData = mysqli_fetch_assoc($roleResult);
    mysqli_stmt_close($roleStmt);

    // Commit transaction
    mysqli_commit($conn);

    // Set session
    $_SESSION['user_id'] = $userId;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $roleData['role'] ?? 'user';

    header("Location: profile.php");
    exit();
    
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    
    // Delete uploaded file if exists
    if ($img_path && file_exists($targetPath)) {
        unlink($targetPath);
    }
    
    $_SESSION['error'] = 'Registration failed. Please try again.';
    header("Location: register.php");
    exit();
}