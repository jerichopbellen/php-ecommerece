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

// Input sanitization
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id === false || $id === null) {
    $_SESSION['flash'] = "Invalid product ID.";
    header("Location: index.php");
    exit;
}

// Prepared statement for product query
$stmt = mysqli_prepare($conn, "
    SELECT 
        p.product_id, 
        p.name AS product_name, 
        p.description, 
        p.brand_id, 
        b.name AS brand_name,
        p.category_id,
        c.name AS category_name,
        p.dimension
    FROM products p
    INNER JOIN brands b ON p.brand_id = b.brand_id
    INNER JOIN categories c ON p.category_id = c.category_id
    WHERE p.product_id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Query preparation failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$product) {
    $_SESSION['flash'] = "Product not found.";
    header("Location: index.php");
    exit;
}

// Prepared statement for brands query
$stmt = mysqli_prepare($conn, "SELECT brand_id, name FROM brands WHERE brand_id != ? ORDER BY name");
mysqli_stmt_bind_param($stmt, "i", $product['brand_id']);
mysqli_stmt_execute($stmt);
$brands = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Prepared statement for categories query
$stmt = mysqli_prepare($conn, "SELECT category_id, name FROM categories WHERE category_id != ? ORDER BY name");
mysqli_stmt_bind_param($stmt, "i", $product['category_id']);
mysqli_stmt_execute($stmt);
$categories = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Parse dimension into length, width, height
$length = $width = $height = '';
if (!empty($product['dimension']) && preg_match('/(\d+(\.\d+)?)\s*x\s*(\d+(\.\d+)?)\s*x\s*(\d+(\.\d+)?)/', $product['dimension'], $matches)) {
    $length = $matches[1];
    $width  = $matches[3];
    $height = $matches[5];
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="card-title mb-4">
                        <i class="bi bi-box-seam me-2"></i>Edit Product
                    </h4>

                    <form action="update.php" method="POST">
                        <input type="hidden" name="product_id" value="<?=htmlspecialchars($product['product_id'], ENT_QUOTES, 'UTF-8') ?>">

                        <!-- Product Name -->
                        <div class="mb-3">
                            <label for="productName" class="form-label">Product Name</label>
                            <small class="text-danger"><?php if (isset($_SESSION['nameError'])) { echo $_SESSION['nameError']; unset($_SESSION['nameError']); } ?></small>
                            <input type="text" class="form-control" id="productName" name="productName" value="<?=htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                                    <small class="text-danger"><?php if (isset($_SESSION['descriptionError'])) { echo $_SESSION['descriptionError']; unset($_SESSION['descriptionError']); } ?></small>
                            <input type="text" class="form-control" id="description" name="description" value="<?= htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <!-- Brand -->
                        <div class="mb-3">
                            <label for="brand" class="form-label">Brand</label>
                            <small class="text-danger"><?php if (isset($_SESSION['brandError'])) { echo $_SESSION['brandError']; unset($_SESSION['brandError']); } ?></small>
                            <select class="form-select" id="brand" name="brand_id" >
                                <option value="<?= htmlspecialchars($product['brand_id'], ENT_QUOTES, 'UTF-8') ?>" selected><?= htmlspecialchars($product['brand_name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php while ($row = mysqli_fetch_assoc($brands)) : ?>
                                    <option value="<?= htmlspecialchars($row['brand_id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Category -->
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <small class="text-danger"><?php if (isset($_SESSION['categoryError'])) { echo $_SESSION['categoryError']; unset($_SESSION['categoryError']); } ?></small>
                            <select class="form-select" id="category" name="category_id">
                                <option value="<?= htmlspecialchars($product['category_id'], ENT_QUOTES, 'UTF-8') ?>" selected><?= htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php while ($row = mysqli_fetch_assoc($categories)) : ?>
                                    <option value="<?= htmlspecialchars($row['category_id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Dimensions -->
                        <div class="mb-3">
                            <label class="form-label">Dimensions (cm)</label>
                            <small class="text-danger"><?php if (isset($_SESSION['dimensionError'])) { echo $_SESSION['dimensionError']; unset($_SESSION['dimensionError']); } ?></small>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <input type="number" step="0.01" class="form-control" name="length" placeholder="Length" value="<?= htmlspecialchars($length, ENT_QUOTES, 'UTF-8') ?>" >
                                </div>
                                <div class="col-md-4">
                                    <input type="number" step="0.01" class="form-control" name="width" placeholder="Width" value="<?= htmlspecialchars($width, ENT_QUOTES, 'UTF-8') ?>" >
                                </div>
                                <div class="col-md-4">
                                    <input type="number" step="0.01" class="form-control" name="height" placeholder="Height" value="<?= htmlspecialchars($height, ENT_QUOTES, 'UTF-8') ?>" >
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary" name="submit" value="submit">
                                <i class="bi bi-check-circle me-1"></i>Update
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

<?php include '../../includes/footer.php'; ?>red>red>red>