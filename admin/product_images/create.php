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

// Fetch products
$stmt = mysqli_prepare($conn, "SELECT product_id, name FROM products ORDER BY name ASC");
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Product Image</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php include '../../includes/alert.php'; ?>
                    <h4 class="card-title mb-4"><i class="bi bi-image me-2"></i>Upload Product Image</h4>

                    <form method="POST" action="store.php" enctype="multipart/form-data">
                        <!-- Product -->
                        <div class="mb-3">
                            <label for="product" class="form-label">Product Name</label>
                            <small class="text-danger">
                                <?php if(isset($_SESSION['productError'])) { echo htmlspecialchars($_SESSION['productError']); unset($_SESSION['productError']); } ?>
                            </small>
                            <select class="form-select" id="product" name="product">
                                <option value="" disabled <?= !isset($_SESSION['product']) ? 'selected' : '' ?>>Select Product</option>
                                <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                                    <option value="<?= intval($row['product_id']) ?>"
                                        <?= isset($_SESSION['product']) && $_SESSION['product'] == $row['product_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Image -->
                        <div class="mb-3">
                            <label for="image" class="form-label">Image Files</label>
                            <small class="text-danger">
                                <?php if(isset($_SESSION['imageError'])) { echo htmlspecialchars($_SESSION['imageError']); unset($_SESSION['imageError']); } ?>
                            </small>
                            <input class="form-control" type="file" name="img_path[]" multiple>
                            <div class="form-text">You can select multiple images (JPG/PNG).</div>
                        </div>

                        <!-- Alt Text -->
                        <div class="mb-3">
                            <label for="alt-text" class="form-label">Alt Text</label>
                            <small class="text-danger">
                                <?php if(isset($_SESSION['altError'])) { echo htmlspecialchars($_SESSION['altError']); unset($_SESSION['altError']); } ?>
                            </small>
                            <input type="text" class="form-control" id="alt-text" name="alt-text" placeholder="Enter alt text"
                                   value="<?php if(isset($_SESSION['alt-text'])) { echo htmlspecialchars($_SESSION['alt-text']); unset($_SESSION['alt-text']); } ?>">
                        </div>

                        <!-- Actions -->
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary" name="submit" value="submit">
                                <i class="bi bi-upload me-1"></i>Submit
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
mysqli_stmt_close($stmt);
include '../../includes/footer.php';
?>
</body>
</html>