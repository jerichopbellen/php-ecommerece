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

$stmt = mysqli_prepare($conn, "SELECT product_id, name FROM products ORDER BY name ASC");
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Product Variant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php include '../../includes/alert.php'; ?>
                    <h4 class="card-title mb-4"><i class="bi bi-sliders me-2"></i>Create Product Variant</h4>

                    <form method="POST" action="store.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="product" class="form-label">Product Name</label>
                            <small class="text-danger">
                                <?php if(isset($_SESSION['productError'])) { echo htmlspecialchars($_SESSION['productError'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['productError']); } ?>
                            </small>
                            <select class="form-select" id="product" name="product">
                                <option value="" disabled <?= !isset($_SESSION['product']) ? 'selected' : '' ?>>Select Product</option>
                                <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                                    <option value="<?= htmlspecialchars($row['product_id'], ENT_QUOTES, 'UTF-8') ?>"
                                        <?= isset($_SESSION['product']) && $_SESSION['product'] == $row['product_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <small class="text-danger">
                            <?php if(isset($_SESSION['variantError'])) { echo htmlspecialchars($_SESSION['variantError'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['variantError']); } ?>
                        </small>
                        <div class="mb-3">
                            <label for="color" class="form-label">Color</label>
                            <input type="text" class="form-control" id="color" name="color" placeholder="Enter color"
                                   value="<?php if(isset($_SESSION['color'])) { echo htmlspecialchars($_SESSION['color'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['color']); } ?>">
                        </div>

                        <div class="mb-3">
                            <label for="material" class="form-label">Material</label>
                            <input type="text" class="form-control" id="material" name="material" placeholder="Enter material"
                                   value="<?php if(isset($_SESSION['material'])) { echo htmlspecialchars($_SESSION['material'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['material']); } ?>">
                        </div>

                        <div class="mb-3">
                            <label for="price" class="form-label">Sell Price</label>
                            <small class="text-danger">
                                <?php if(isset($_SESSION['priceError'])) { echo htmlspecialchars($_SESSION['priceError'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['priceError']); } ?>
                            </small>
                            <input type="text" class="form-control" id="price" name="price" placeholder="Enter price"
                                   value="<?php if(isset($_SESSION['price'])) { echo htmlspecialchars($_SESSION['price'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['price']); } ?>">
                        </div>

                        <div class="mb-4">
                            <label for="quantity" class="form-label">Stock Quantity</label>
                            <small class="text-danger">
                                <?php if(isset($_SESSION['quantityError'])) { echo htmlspecialchars($_SESSION['quantityError'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['quantityError']); } ?>
                            </small>
                            <input type="text" class="form-control" id="quantity" name="quantity" placeholder="Enter quantity"
                                   value="<?php if(isset($_SESSION['quantity'])) { echo htmlspecialchars($_SESSION['quantity'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['quantity']); } ?>">
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary" name="submit" value="submit">
                                <i class="bi bi-check-circle me-1"></i>Submit
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