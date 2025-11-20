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

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    echo "<div class='container my-5'><div class='alert alert-warning'>Invalid order ID.</div></div>";
    include '../../includes/footer.php';
    exit;
}

$sql = "
    SELECT 
        o.order_id,
        o.created_at,
        o.status,
        o.tracking_number,
        o.customer_name,
        o.customer_email,
        a.recipient,
        a.street,
        a.barangay,
        a.city,
        a.province,
        a.zipcode,
        a.country,
        a.phone,
        o.product_name,
        o.brand_name,
        o.category_name,
        o.variant_id,
        o.color,
        o.material,
        o.unit_price,
        o.quantity,
        o.subtotal
    FROM view_order_transaction_details o
    LEFT JOIN addresses a ON o.address_id = a.address_id
    WHERE o.order_id = ?
    ORDER BY o.product_name ASC
";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo "<div class='container my-5'><div class='alert alert-danger'>Database error occurred.</div></div>";
    include '../../includes/footer.php';
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    echo "<div class='container my-5'><div class='alert alert-warning'>Order not found.</div></div>";
    mysqli_stmt_close($stmt);
    include '../../includes/footer.php';
    exit;
}

$items = [];
$total = 0;
$orderMeta = null;

while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
    $total += $row['subtotal'];
    if (!$orderMeta) {
        $orderMeta = [
            'order_id' => $row['order_id'],
            'created_at' => $row['created_at'],
            'status' => $row['status'],
            'tracking_number' => $row['tracking_number'],
            'customer_name' => $row['customer_name'],
            'customer_email' => $row['customer_email'],
            'recipient' => $row['recipient'],
            'street' => $row['street'],
            'barangay' => $row['barangay'],
            'city' => $row['city'],
            'province' => $row['province'],
            'zipcode' => $row['zipcode'],
            'country' => $row['country'],
            'phone' => $row['phone']
        ];
    }
}

mysqli_stmt_close($stmt);
?>

<div class="container my-5">
    <h3><i class="bi bi-box me-2"></i>Order #<?= htmlspecialchars($orderMeta['order_id']) ?></h3>
    <div class="mb-4">
        <p><strong>Date:</strong> <?=htmlspecialchars(date('Y-m-d H:i', strtotime($orderMeta['created_at']))) ?></p>
        <?php $disabled = (strtolower($orderMeta['status']) === 'cancelled') ? 'disabled' : ''; ?>        
        <form action="status_update.php" method="POST" class="d-flex align-items-center gap-2 mb-2">
            <label for="status" class="form-label mb-0"><strong>Status:</strong></label>
            <select name="status" id="status" class="form-select form-select-sm w-auto" onchange="this.form.submit()" <?= $disabled ?>>
                <?php
                $statuses = ['pending', 'processing', 'shipped', 'delivered', 'received', 'cancelled'];
                $currentStatus = strtolower($orderMeta['status']);
                $statusOrder = array_flip($statuses);
                $currentIndex = $statusOrder[$currentStatus];

                foreach ($statuses as $status) {
                    $selected = ($currentStatus === $status) ? 'selected' : '';
                    $disabledOption = '';

                    if ($statusOrder[$status] < $currentIndex && $status !== $currentStatus) {
                        $disabledOption = 'disabled';
                    }

                    if ($statusOrder[$status] > $currentIndex + 1 && $status !== 'cancelled') {
                        $disabledOption = 'disabled';
                    }

                    if ($status === 'cancelled' && $statusOrder[$currentStatus] >= $statusOrder['shipped']) {
                        $disabledOption = 'disabled';
                    }

                    if ($status === 'received') {
                        $disabledOption = 'disabled';
                    }

                    echo "<option value='" . htmlspecialchars($status) . "' $selected $disabledOption>" . htmlspecialchars(ucfirst($status)) . "</option>";
                }
                ?>
            </select>
            <input type="hidden" name="order_id" value="<?=htmlspecialchars($orderMeta['order_id']) ?>">
        </form>
        <p><strong>Tracking #:</strong> <?=htmlspecialchars($orderMeta['tracking_number'] ?? '-') ?></p>
        <p><strong>Customer:</strong> <?=htmlspecialchars($orderMeta['customer_name']) ?> (<?=htmlspecialchars($orderMeta['customer_email']) ?>)</p>
        <p><strong>Address:</strong><br>
            <?= htmlspecialchars($orderMeta['recipient']) ?><br>
            <?= htmlspecialchars($orderMeta['street']) ?><br>
            <?= htmlspecialchars($orderMeta['barangay']) ?><br>
            <?= htmlspecialchars($orderMeta['city']) ?>, <?= htmlspecialchars($orderMeta['province']) ?><br>
            <?= htmlspecialchars($orderMeta['zipcode']) ?><br>
            <?= htmlspecialchars($orderMeta['country']) ?><br>
            Phone: <?= htmlspecialchars($orderMeta['phone']) ?>
        </p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3">Items</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Brand</th>
                            <th>Category</th>
                            <th>Variant</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?=htmlspecialchars($item['product_name']) ?></td>
                                <td><?=htmlspecialchars($item['brand_name']) ?></td>
                                <td><?=htmlspecialchars($item['category_name']) ?></td>
                                <td><?php 
                                    $parts = array_filter([
                                        htmlspecialchars($item['color']),
                                        htmlspecialchars($item['material'])
                                    ]);
                                    echo implode(' / ', $parts);
                                ?></td>
                                <td>₱<?=number_format($item['unit_price'], 2) ?></td>
                                <td><?=htmlspecialchars($item['quantity']) ?></td>
                                <td>₱<?=number_format($item['subtotal'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="6" class="text-end"><strong>Total:</strong></td>
                            <td><strong>₱<?=number_format($total, 2) ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to Orders</a>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>/td>/td>