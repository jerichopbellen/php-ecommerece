<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

include '../../includes/adminHeader.php';
include '../../includes/config.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id <= 0) {
    echo "<div class='container my-5'><div class='alert alert-danger'>Invalid user ID.</div></div>";
    include '../../includes/footer.php';
    exit;
}

$sql = "
    SELECT 
        o.order_id,
        o.created_at,
        o.status,
        o.tracking_number,
        SUM(oi.quantity * oi.price) AS total_amount
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<div class="container my-5">
    <h3><i class="bi bi-box-seam me-2"></i>Order History for User #<?=htmlspecialchars($user_id) ?></h3>

    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Tracking #</th>
                            <th>Total</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                                <?php
                                    $status = strtolower(htmlspecialchars($row['status']));
                                    $badge = match ($status) {
                                        'pending'    => 'warning',
                                        'processing' => 'info',
                                        'shipped'    => 'primary',
                                        'delivered'  => 'success',
                                        'received'   => 'secondary',
                                        'cancelled'  => 'danger',
                                        default      => 'dark text-white'
                                    };
                                ?>
                                <tr>
                                    <td><?=htmlspecialchars($row['order_id']) ?></td>
                                    <td><?=htmlspecialchars(date('Y-m-d H:i', strtotime($row['created_at']))) ?></td>
                                    <td><span class="badge bg-<?=htmlspecialchars($badge) ?> text-capitalize"><?=htmlspecialchars($status) ?></span></td>
                                    <td><?=htmlspecialchars($row['tracking_number'] ?? '') ?></td>
                                    <td>₱<?=htmlspecialchars(number_format($row['total_amount'], 2)) ?></td>
                                    <td class="text-center">
                                        <a href="../orders/view.php?id=<?=urlencode($row['order_id']) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No orders found for this user.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <a href="index.php" class="btn btn-secondary mt-3"><i class="bi bi-arrow-left"></i> Back to Users</a>
        </div>
    </div>
</div>

<?php 
mysqli_stmt_close($stmt);
include '../../includes/footer.php'; 
?>