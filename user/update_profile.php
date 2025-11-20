<?php
session_start();
include("../includes/config.php");

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    $_SESSION['redirect'] = 'Please log in to update your profile.';
    header("Location: ../user/login.php");
    exit();
}

if (isset($_POST['submit_profile'])) {
    $fname = htmlspecialchars(trim($_POST['fname'] ?? ''), ENT_QUOTES, 'UTF-8');
    $lname = htmlspecialchars(trim($_POST['lname'] ?? ''), ENT_QUOTES, 'UTF-8');
    $email = trim($_POST['email'] ?? '');

    // Validation
    if ($fname === '') {
        $_SESSION['fnameError'] = 'First name is required.';
        header("Location: profile.php"); exit();
    }
    if ($lname === '') {
        $_SESSION['lnameError'] = 'Last name is required.';
        header("Location: profile.php"); exit();
    }
    if ($email === '') {
        $_SESSION['emailError'] = 'Email is required.';
        header("Location: profile.php"); exit();
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['emailError'] = 'Invalid email format.';
        header("Location: profile.php"); exit();
    }

    $check_sql = "SELECT user_id FROM users WHERE email = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, 's', $email);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);

    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        mysqli_stmt_bind_result($check_stmt, $existing_user_id);
        mysqli_stmt_fetch($check_stmt);
        mysqli_stmt_close($check_stmt);

        if ($existing_user_id != $userId) {
            $_SESSION['emailError'] = 'Email is already in use by another account.';
            header("Location: profile.php"); exit();
        }
    } else {
        mysqli_stmt_close($check_stmt);
    }

    $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sssi', $fname, $lname, $email, $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    unset($_SESSION['email']);
    $_SESSION['email'] = $email;
    $_SESSION['success'] = 'Profile updated successfully.';
    header("Location: profile.php");
    exit();
}

if (isset($_POST['remove_avatar'])) {
    $defaultPath = "/Furnitures/user/avatars/default-avatar.png";

    $check_sql = "SELECT img_path FROM users WHERE user_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, 'i', $userId);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $row = mysqli_fetch_assoc($check_result);
    mysqli_stmt_close($check_stmt);

    if ($row && $row['img_path'] === $defaultPath) {
        $_SESSION['info'] = 'You do not have a profile picture to remove.';
    } else {
        $sql = "UPDATE users SET img_path = ? WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'si', $defaultPath, $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $_SESSION['success'] = 'Profile picture removed.';
    }

    header("Location: profile.php");
    exit();
}

if (isset($_POST['submit_password'])) {
    $current = sha1(trim($_POST['current_password'] ?? ''));
    $new     = trim($_POST['new_password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    if ($new === '' || $confirm === '' || $_POST['current_password'] === '') {
        $_SESSION['currentPassError'] = 'All password fields are required.';
        header("Location: profile.php"); exit();
    }

    if ($new !== $confirm) {
        $_SESSION['confirmPassError'] = 'New passwords do not match.';
        header("Location: profile.php"); exit();
    }

    $check_sql = "SELECT user_id FROM users WHERE user_id = ? AND password_hash = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, 'is', $userId, $current);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);

    if (mysqli_stmt_num_rows($check_stmt) === 1) {
        $update_sql = "UPDATE users SET password_hash = ? WHERE user_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        $hashed_new = sha1($new);
        mysqli_stmt_bind_param($update_stmt, 'si', $hashed_new, $userId);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);

        $_SESSION['success'] = 'Password updated successfully.';
    } else {
        $_SESSION['currentPassError'] = 'Current password is incorrect.';
    }

    mysqli_stmt_close($check_stmt);

    header("Location: profile.php");
    exit();
}

if (isset($_POST['submit_avatar'])) {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/png', 'image/jpg', 'image/jpeg'];
        $file = $_FILES['avatar'];

        if (in_array($file['type'], $allowedTypes)) {
            $source = $file['tmp_name'];
            $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file['name']));
            $filename = uniqid('avatar_', true) . '_' . $filename; 
            $targetDir = __DIR__ . "/avatars/";
            $target = $targetDir . $filename;
            $path = "/Furnitures/user/avatars/" . $filename;

            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            if (move_uploaded_file($source, $target)) {
                $update_sql = "UPDATE users SET img_path = ? WHERE user_id = ?";
                $stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($stmt, 'si', $path, $userId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                $_SESSION['success'] = 'Profile picture updated.';
            } else {
                $_SESSION['imageError'] = "Couldn't save uploaded image.";
            }
        } else {
            $_SESSION['imageError'] = 'Invalid image type. Only PNG and JPG are allowed.';
        }
    } else {
        $_SESSION['imageError'] = 'No file uploaded.';
    }

    header("Location: profile.php");
    exit();
}

if (isset($_POST['submit_address'])) {
    $recipient = htmlspecialchars(trim($_POST['recipient'] ?? ''), ENT_QUOTES, 'UTF-8');
    $street    = htmlspecialchars(trim($_POST['street'] ?? ''), ENT_QUOTES, 'UTF-8');
    $barangay  = htmlspecialchars(trim($_POST['barangay'] ?? ''), ENT_QUOTES, 'UTF-8');
    $city      = htmlspecialchars(trim($_POST['city'] ?? ''), ENT_QUOTES, 'UTF-8');
    $province  = htmlspecialchars(trim($_POST['province'] ?? ''), ENT_QUOTES, 'UTF-8');
    $zipcode   = htmlspecialchars(trim($_POST['zipcode'] ?? ''), ENT_QUOTES, 'UTF-8');
    $country   = htmlspecialchars(trim($_POST['country'] ?? ''), ENT_QUOTES, 'UTF-8');
    $phone     = htmlspecialchars(trim($_POST['phone'] ?? ''), ENT_QUOTES, 'UTF-8');

    $_SESSION['recipient'] = $_POST['recipient'];
    $_SESSION['street']    = $_POST['street'];
    $_SESSION['barangay']  = $_POST['barangay'];
    $_SESSION['city']      = $_POST['city'];
    $_SESSION['province']  = $_POST['province'];
    $_SESSION['zipcode']   = $_POST['zipcode'];
    $_SESSION['country']   = $_POST['country'];
    $_SESSION['phone']     = $_POST['phone'];

    if ($recipient === '') { $_SESSION['recipientError'] = 'Recipient name is required.'; header("Location: profile.php"); exit(); }
    if ($phone === '' || !ctype_digit($phone)) { $_SESSION['phoneError'] = 'Phone must not be empty and must contain numbers only.'; header("Location: profile.php"); exit(); }
    if ($street === '') { $_SESSION['streetError'] = 'Street is required.'; header("Location: profile.php"); exit(); }
    if ($barangay === '') { $_SESSION['barangayError'] = 'Barangay is required.'; header("Location: profile.php"); exit(); }
    if ($city === '') { $_SESSION['cityError'] = 'City is required.'; header("Location: profile.php"); exit(); }
    if ($province === '') { $_SESSION['provinceError'] = 'Province is required.'; header("Location: profile.php"); exit(); }
    if ($zipcode === '' || !ctype_digit($zipcode)) { $_SESSION['zipcodeError'] = 'Zipcode must not be empty and must contain numbers only.'; header("Location: profile.php"); exit(); }
    if ($country === '') { $_SESSION['countryError'] = 'Country is required.'; header("Location: profile.php"); exit(); }

    $sql = "INSERT INTO addresses 
            (recipient, street, barangay, city, province, zipcode, country, phone, user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ssssssssi',
        $recipient, $street, $barangay, $city, $province, $zipcode, $country, $phone, $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    foreach (['recipient','street','barangay','city','province','zipcode','country','phone'] as $field) {
        unset($_SESSION[$field]);
    }

    $_SESSION['success'] = 'Address added successfully.';
    header("Location: profile.php");
    exit();
}

if (isset($_GET['delete_address'])) {
    $addressId = (int) $_GET['delete_address'];

    mysqli_begin_transaction($conn);

    try {
        $check_sql = "
            SELECT 1 
            FROM orders 
            WHERE address_id = ? 
              AND user_id = ? 
            LIMIT 1
        ";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, 'ii', $addressId, $userId);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);

        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            mysqli_stmt_close($check_stmt);
            mysqli_rollback($conn);
            
            $_SESSION['error'] = 'This address cannot be deleted because it is linked to your active and previous orders.';
            header("Location: profile.php");
            exit();
        }
        mysqli_stmt_close($check_stmt);

        $sql = "DELETE FROM addresses WHERE address_id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ii', $addressId, $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);

        $_SESSION['success'] = 'Address removed successfully.';
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = 'Failed to delete address.';
    }

    header("Location: profile.php");
    exit();
}

header("Location: profile.php");
exit();