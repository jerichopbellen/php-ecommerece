<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect'] = "Please log in to contact us.";
    header("Location: ../user/login.php");
    exit;
}

include '../includes/config.php';
include '../includes/header.php';

$user_id = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);
if (!$user_id) {
    die("Invalid user ID");
}

$stmt = mysqli_prepare($conn, "SELECT first_name, last_name, email FROM users WHERE user_id = ?");
if (!$stmt) {
    die("Database error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    die("User not found");
}
?>

<div class="container my-5">
    <h3><i class="bi bi-envelope me-2"></i>Contact Us</h3>

    <form action="send_contact.php" method="POST" class="row g-3 mt-3">
        <?php include '../includes/alert.php';?>

        <div class="col-md-6">
            <label for="name" class="form-label">Your Name</label>
            <input type="text" name="name" id="name" class="form-control" value="<?=htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8') ?>" readonly>
        </div>
        <div class="col-md-6">
            <label for="email" class="form-label">Your Email</label>
            <input type="email" name="email" id="email" class="form-control" value="<?=htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>" readonly>
        </div>
        <div class="col-12">
            <label for="subject" class="form-label">Subject</label>
            <input type="text" name="subject" id="subject" class="form-control" required>
        </div>
        <div class="col-12">
            <label for="message" class="form-label">Message</label>
            <textarea name="message" id="message" rows="5" class="form-control" required></textarea>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-outline-primary w-100">
                <i class="bi bi-send me-1"></i> Send Message
            </button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>