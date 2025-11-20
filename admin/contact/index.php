<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

include '../../includes/config.php';
include '../../includes/adminHeader.php';
include '../../includes/alert.php';

$replyFilter = isset($_GET['reply']) ? trim($_GET['reply']) : 'all';
// Whitelist allowed values
$allowedFilters = ['all', 'replied', 'notyet'];
if (!in_array($replyFilter, $allowedFilters)) {
    $replyFilter = 'all';
}

$sql = "SELECT * FROM contact_messages";
$types = "";
$params = [];

if ($replyFilter === 'replied') {
    $sql .= " WHERE reply IS NOT NULL AND reply <> ''";
} elseif ($replyFilter === 'notyet') {
    $sql .= " WHERE reply IS NULL OR reply = ''";
}

$sql .= " ORDER BY submitted_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    die("Error preparing statement: " . mysqli_error($conn));
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$count = mysqli_num_rows($result);
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-inbox me-2"></i>Contact Messages</h3>
    </div>

    <form method="GET" class="mb-4">
        <div class="row g-2">
            <div class="col-md-3">
                <select name="reply" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?= $replyFilter === 'all' ? 'selected' : '' ?>>All Messages</option>
                    <option value="replied" <?= $replyFilter === 'replied' ? 'selected' : '' ?>>Replied</option>
                    <option value="notyet" <?= $replyFilter === 'notyet' ? 'selected' : '' ?>>Not Yet Replied</option>
                </select>
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3">Total Messages: <?= $count ?></h5>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Submitted</th>
                            <th>Reply</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?=htmlspecialchars($row['name']) ?></td>
                                <td><?=htmlspecialchars($row['email']) ?></td>
                                <td><?=htmlspecialchars($row['subject']) ?></td>
                                <td><?=nl2br(htmlspecialchars($row['message'])) ?></td>
                                <td><?=date('Y-m-d H:i', strtotime($row['submitted_at'])) ?></td>
                                <td>
                                    <?php if ($row['reply']): ?>
                                        <div class="text-success mb-2"><?=nl2br(htmlspecialchars($row['reply'])) ?></div>
                                        <small class="text-muted">Replied: <?=date('Y-m-d H:i', strtotime($row['replied_at'])) ?></small>
                                    <?php else: ?>
                                        <form action="reply_contact.php" method="POST">
                                            <textarea name="reply" class="form-control mb-2" rows="3" required></textarea>
                                            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Send Reply</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if ($count === 0): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No messages found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php 
mysqli_stmt_close($stmt);
include '../../includes/footer.php'; 
?>