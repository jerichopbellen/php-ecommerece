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

$selectedTag = isset($_GET['tag']) ? filter_var($_GET['tag'], FILTER_VALIDATE_INT) : 0;
$selectedTag = ($selectedTag !== false && $selectedTag >= 0) ? $selectedTag : 0;

$tagsStmt = mysqli_prepare($conn, "SELECT tag_id, name FROM tags ORDER BY name");
mysqli_stmt_execute($tagsStmt);
$tagsResult = mysqli_stmt_get_result($tagsStmt);

if ($selectedTag > 0) {
    $sql = "
        SELECT 
            pt.product_id,
            pt.tag_id,
            p.name AS product_name,
            t.name AS tag_name
        FROM product_tags pt
        JOIN products p ON pt.product_id = p.product_id
        JOIN tags t ON pt.tag_id = t.tag_id
        WHERE pt.tag_id = ?
        ORDER BY p.name, t.name
    ";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $selectedTag);
} else {
    $sql = "
        SELECT 
            pt.product_id,
            pt.tag_id,
            p.name AS product_name,
            t.name AS tag_name
        FROM product_tags pt
        JOIN products p ON pt.product_id = p.product_id
        JOIN tags t ON pt.tag_id = t.tag_id
        ORDER BY p.name, t.name
    ";
    $stmt = mysqli_prepare($conn, $sql);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$count = mysqli_num_rows($result);
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-bookmark-plus me-2"></i>Product Tags</h3>
        <a href="create.php" class="btn btn-outline-primary">
            <i class="bi bi-plus-circle me-1"></i> Assign Tag
        </a>
    </div>

    <!-- ✅ Tag Filter Form -->
    <form method="GET" class="mb-4">
        <div class="row g-2">
            <div class="col-md-4">
                <select name="tag" class="form-select" onchange="this.form.submit()">
                    <option value="0">All Tags</option>
                    <?php while ($tag = mysqli_fetch_assoc($tagsResult)): ?>
                        <option value="<?= intval($tag['tag_id']) ?>" <?=($selectedTag == $tag['tag_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3">Total Assignments: <?= $count ?></h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Tag</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                            <tr>
                                <td><?=htmlspecialchars($row['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?=htmlspecialchars($row['tag_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-center">
                                    <a href="delete.php?product_id=<?= intval($row['product_id']) ?>&tag_id=<?= intval($row['tag_id']) ?>" class="btn btn-sm btn-outline-danger" title="Remove" onclick="return confirm('Remove this tag from product?');">
                                        <i class="bi bi-x-circle"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if ($count === 0): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted">No tag assignments found.</td>
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
mysqli_stmt_close($tagsStmt);
include '../../includes/footer.php'; 
?>