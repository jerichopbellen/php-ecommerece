<?php
session_start();
include("../includes/config.php");

$first_name   = trim(htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8'));
$last_name    = trim(htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8'));
$email        = trim($_POST['email'] ?? '');
$password     = $_POST['password'] ?? '';
$confirmPass  = $_POST['confirmPass'] ?? '';

$_SESSION['first_name'] = $_POST['first_name'];
$_SESSION['last_name']  = $_POST['last_name'];
$_SESSION['email']      = $_POST['email'];

if (!$first_name) {
    $_SESSION['firstNameError'] = 'First name is required.';
    header("Location: register.php"); exit();
}
if (!$last_name) {
    $_SESSION['lastNameError'] = 'Last name is required.';
    header("Location: register.php"); exit();
}
if (!$email) {
    $_SESSION['emailError'] = 'Email is required.';
    header("Location: register.php"); exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['emailError'] = 'Invalid email format.';
    header("Location: register.php"); exit();
}
if (!$password) {
    $_SESSION['passwordError'] = 'Password is required.';
    header("Location: register.php"); exit();
}
if ($password !== $confirmPass) {
    $_SESSION['confirmError'] = 'Passwords do not match.';
    header("Location: register.php"); exit();
}

$emailCheckSql = "SELECT user_id FROM users WHERE email = ?";
$emailCheckStmt = mysqli_prepare($conn, $emailCheckSql);
mysqli_stmt_bind_param($emailCheckStmt, 's', $email);
mysqli_stmt_execute($emailCheckStmt);
mysqli_stmt_store_result($emailCheckStmt);

if (mysqli_stmt_num_rows($emailCheckStmt) > 0) {
    mysqli_stmt_close($emailCheckStmt);
    $_SESSION['emailError'] = 'Email is already registered. Please use a different one.';
    header("Location: register.php"); exit();
}
mysqli_stmt_close($emailCheckStmt);

$img_path = null;
$targetPath = null;

if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    $file = $_FILES['profile_photo'];

    if (in_array($file['type'], $allowedTypes)) {
        $filename = basename($file['name']);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $newName = uniqid('profile_', true) . '.' . $ext;

        $targetDir = __DIR__ . "/avatars/";
        $targetPath = $targetDir . $newName;
        $webPath = "/Furnitures/user/avatars/" . $newName;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $img_path = $webPath;
        } else {
            $_SESSION['photoError'] = "Couldn't save uploaded image.";
            header("Location: register.php"); exit();
        }
    } else {
        $_SESSION['photoError'] = 'Invalid image type. Only JPG and PNG are allowed.';
        header("Location: register.php"); exit();
    }
}

mysqli_begin_transaction($conn);

try {
    $hashed_password = sha1($password);

    $sql = "INSERT INTO users (first_name, last_name, email, password_hash, img_path) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sssss', $first_name, $last_name, $email, $hashed_password, $img_path);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to insert user');
    }

    $userId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    $roleQuery = "SELECT role FROM users WHERE user_id = ?";
    $roleStmt = mysqli_prepare($conn, $roleQuery);
    mysqli_stmt_bind_param($roleStmt, 'i', $userId);
    mysqli_stmt_execute($roleStmt);
    $roleResult = mysqli_stmt_get_result($roleStmt);
    $roleData = mysqli_fetch_assoc($roleResult);
    mysqli_stmt_close($roleStmt);

    mysqli_commit($conn);

    foreach (['first_name','last_name','email'] as $field) {
        unset($_SESSION[$field]);
    }

    $_SESSION['user_id'] = $userId;
    $_SESSION['email']   = $email;
    $_SESSION['role']    = $roleData['role'] ?? 'user';

    $_SESSION['success'] = "Registration successful!";
    header("Location: profile.php");
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);

    if ($img_path && $targetPath && file_exists($targetPath)) {
        unlink($targetPath);
    }

    $_SESSION['error'] = 'Registration failed. Please try again.';
    header("Location: register.php");
    exit();
}