<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash'] = "Please log in to access this page.";
    header("Location: ../../user/login.php");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    echo "
    <html>
    <head>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
        <title>Access Denied</title>
    </head>
    <body class='bg-light'>
        <div class='container py-5'>
            <div class='alert alert-danger text-center'>
                Access denied. This page is restricted to administrators.
            </div>
        </div>
    </body>
    </html>";
    exit;
}

include '../../includes/adminHeader.php';
include '../../includes/config.php';
include '../../includes/alert.php';

$keyword = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : '';

$allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'received', 'cancelled'];
if ($statusFilter && !in_array($statusFilter, $allowedStatuses)) {
    $statusFilter = '';
}

$sql = "
    SELECT 
        order_id,
        created_at,
        status,
        tracking_number,
        user_id,
        customer_name,
        customer_email,
        SUM(subtotal) AS total_amount
    FROM view_order_transaction_details
";

$conditions = [];
$params = [];
$types = '';

if ($keyword !== '') {
    $conditions[] = "(customer_name LIKE ? OR customer_email LIKE ? OR status LIKE ? OR tracking_number LIKE ? OR order_id LIKE ?)";
    $likeKeyword = "%$keyword%";
    $params[] = &$likeKeyword;
    $params[] = &$likeKeyword;
    $params[] = &$likeKeyword;
    $params[] = &$likeKeyword;
    $params[] = &$likeKeyword;
    $types .= 'sssss';
}

if ($statusFilter !== '') {
    $conditions[] = "status = ?";
    $params[] = &$statusFilter;
    $types .= 's';
}

if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " GROUP BY order_id ORDER BY created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt === false) {
    die("Error preparing statement: " . mysqli_error($conn));
}

if (!empty($params)) {
    array_unshift($params, $types);
    call_user_func_array([$stmt, 'bind_param'], $params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$itemCount = mysqli_num_rows($result);
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-receipt me-2"></i>Orders</h3>
    </div>

    <form method="GET" class="row g-3 align-items-end mb-4">
        <div class="col-md-6">
            <label for="search" class="form-label">Search</label>
            <input type="text" name="search" id="search" class="form-control" placeholder="Customer, email, status, tracking #, or ID..." value="<?=htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-md-3">
            <label for="status" class="form-label">Filter by Status</label>
            <select name="status" id="status" class="form-select">
                <option value="">All</option>
                <?php
                foreach ($allowedStatuses as $status) {
                    $selected = $statusFilter === $status ? 'selected' : '';
                    echo "<option value='" . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . "' $selected>" . htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-outline-secondary w-100" type="submit">
                <i class="bi bi-search me-1"></i>Filter
            </button>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3">Total Orders: <?=$itemCount ?></h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Tracking #</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Total</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                            <tr>
                                <td>#<?=htmlspecialchars($row['order_id'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?=htmlspecialchars(date('Y-m-d H:i', strtotime($row['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php
                                        $status = strtolower($row['status']);
                                        $badge = match ($status) {
                                            'pending'    => 'warning',
                                            'processing' => 'info',
                                            'shipped'    => 'primary',
                                            'delivered'  => 'success',
                                            'received'   => 'secondary',
                                            'cancelled'  => 'danger',
                                            default      => 'dark text-white'
                                        };
                                        echo "<span class='badge bg-" . htmlspecialchars($badge, ENT_QUOTES, 'UTF-8') . " text-capitalize'>" . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . "</span>";
                                    ?>
                                </td>
                                <td><?=htmlspecialchars($row['tracking_number'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?=htmlspecialchars($row['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?=htmlspecialchars($row['customer_email'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>₱<?=number_format($row['total_amount'], 2) ?></td>
                                <td class="text-center">
                                    <a href="view.php?id=<?= urlencode($row['order_id']) ?>" class="btn btn-sm btn-outline-secondary me-1" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if ($itemCount === 0): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No orders found.</td>
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
?>ew">/td>/td>/td>/td>