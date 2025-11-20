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

if ($keyword !== '' && $keyword !== false) {
    $sql = "SELECT * FROM tags WHERE name LIKE ? ORDER BY name ASC";
    $stmt = mysqli_prepare($conn, $sql);
    $searchParam = "%$keyword%";
    mysqli_stmt_bind_param($stmt, "s", $searchParam);
} else {
    $sql = "SELECT * FROM tags ORDER BY name ASC";
    $stmt = mysqli_prepare($conn, $sql);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$count = mysqli_num_rows($result);
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-bookmarks me-2"></i>Tags</h3>
        <a href="create.php" class="btn btn-outline-primary">
            <i class="bi bi-plus-circle me-1"></i> New Tag
        </a>
    </div>

    <form method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search tags..." value="<?=htmlspecialchars($keyword) ?>">
            <button class="btn btn-outline-secondary" type="submit">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3">Total Tags: <?=$count ?></h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                            <tr>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td class="text-center">
                                    <a href="edit.php?id=<?=(int)$row['tag_id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="delete.php?id=<?= (int)$row['tag_id'] ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Delete this tag?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if ($count === 0): ?>
                            <tr>
                                <td colspan="2" class="text-center text-muted">No tags found.</td>
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