<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash'] = "Please log in to view your messages.";
    header("Location: login.php");
    exit;
}

include '../includes/config.php';
include '../includes/header.php';

$user_email = filter_var($_SESSION['email'], FILTER_SANITIZE_EMAIL);

$stmt = mysqli_prepare($conn, "SELECT * FROM contact_messages WHERE email = ? ORDER BY submitted_at DESC");
mysqli_stmt_bind_param($stmt, "s", $user_email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<div class="container my-5">
    <h3><i class="bi bi-chat-dots me-2"></i>Your Messages</h3>
    <?php if (mysqli_num_rows($result) === 0): ?>
        <div class="alert alert-info mt-4">You haven't sent any messages yet.</div>
    <?php else: ?>
        <div class="list-group mt-4">
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="list-group-item">
                    <h5><?=htmlspecialchars($row['subject']) ?></h5>
                    <p class="mb-1"><?= nl2br(htmlspecialchars($row['message'])) ?></p>
                    <small class="text-muted">Sent: <?= date('Y-m-d H:i', strtotime($row['submitted_at'])) ?></small>

                    <?php if ($row['reply']): ?>
                        <div class="mt-3 border-top pt-2">
                            <strong class="text-success">Admin Reply:</strong>
                            <p class="mb-1"><?= nl2br(htmlspecialchars($row['reply'])) ?></p>
                            <small class="text-muted">Replied: <?= date('Y-m-d H:i', strtotime($row['replied_at'])) ?></small>
                        </div>
                    <?php else: ?>
                        <div class="mt-3 text-muted"><em>No reply yet.</em></div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php 
mysqli_stmt_close($stmt);
include '../includes/footer.php'; 
?>