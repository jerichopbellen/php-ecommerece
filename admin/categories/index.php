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
$keyword = filter_var($keyword, FILTER_SANITIZE_STRING);

if ($keyword !== '') {
    $sql = "
        SELECT 
            category_id,
            name AS category_name
        FROM categories
        WHERE name LIKE ?
        ORDER BY category_name ASC
    ";
    
    $stmt = mysqli_prepare($conn, $sql);
    $searchTerm = "%$keyword%";
    mysqli_stmt_bind_param($stmt, "s", $searchTerm);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $sql = "
        SELECT 
            category_id,
            name AS category_name
        FROM categories
        ORDER BY category_name ASC
    ";
    
    $result = mysqli_query($conn, $sql);
}

$itemCount = mysqli_num_rows($result);
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-grid me-2"></i>Categories</h3>
        <a href="create.php" class="btn btn-outline-primary">
            <i class="bi bi-plus-circle me-1"></i> Add Category
        </a>
    </div>

    <form method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search by category name..." value="<?= htmlspecialchars($keyword) ?>">
            <button class="btn btn-outline-secondary" type="submit">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3">Total Categories: <?=$itemCount ?></h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                            <tr>
                                <td><?=htmlspecialchars($row['category_name']) ?></td>
                                <td class="text-center">
                                    <a href="edit.php?id=<?= urlencode($row['category_id']) ?>" class="text-primary me-2" title="Edit">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </a>
                                    <a href="delete.php?id=<?= urlencode($row['category_id']) ?>" class="text-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this category?')">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if ($itemCount === 0): ?>
                            <tr>
                                <td colspan="2" class="text-center text-muted">No categories found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php 
if (isset($stmt)) {
    mysqli_stmt_close($stmt);
}
include '../../includes/footer.php'; 
?>