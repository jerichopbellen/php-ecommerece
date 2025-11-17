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
  $_SESSION['flash'] = "Invalid variant ID.";
  header("Location: index.php");
  exit;
}

// Prepared statement
$stmt = mysqli_prepare($conn, "
  SELECT 
    v.variant_id,
    v.color,
    v.material,
    v.price,
    s.quantity, 
    p.product_id, 
    p.name AS product_name
  FROM product_variants v
  INNER JOIN products p ON v.product_id = p.product_id
  INNER JOIN stocks s ON v.variant_id = s.variant_id
  WHERE v.variant_id = ?
  LIMIT 1
");

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product_variant = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$product_variant) {
  $_SESSION['flash'] = "Product variant not found.";
  header("Location: index.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Product Variant</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <?php include '../../includes/alert.php'; ?>
          <h4 class="card-title mb-4">
            <i class="bi bi-palette me-2 text-dark"></i>Edit Product Variant
          </h4>
          <form action="update.php" method="POST">
            <input type="hidden" name="variant_id" value="<?= htmlspecialchars($product_variant['variant_id']) ?>">

            <div class="mb-3">
              <label for="product" class="form-label">Product Name</label>
              <h5><?= htmlspecialchars($product_variant['product_name']) ?></h5>
            </div>

            <div class="mb-3">
              <label for="color" class="form-label">Color</label>
              <small class="text-danger">
                <?php if(isset($_SESSION['variantError'])) { echo htmlspecialchars($_SESSION['variantError']); unset($_SESSION['variantError']); } ?>
              </small>
              <input type="text" class="form-control" id="color" name="color"
                     value="<?= isset($_SESSION['color']) ? htmlspecialchars($_SESSION['color']) : htmlspecialchars($product_variant['color']) ?>">
            </div>

            <div class="mb-3">
              <label for="material" class="form-label">Material</label>
              <input type="text" class="form-control" id="material" name="material"
                     value="<?= isset($_SESSION['material']) ? htmlspecialchars($_SESSION['material']) : htmlspecialchars($product_variant['material']) ?>">
            </div>

            <div class="mb-3">
              <label for="sell_price" class="form-label">Sell Price</label>
              <small class="text-danger">
                <?php if(isset($_SESSION['priceError'])) { echo htmlspecialchars($_SESSION['priceError']); unset($_SESSION['priceError']); } ?>
              </small>
              <input type="text" class="form-control" id="sell_price" name="sell_price"
                     value="<?= isset($_SESSION['sell_price']) ? htmlspecialchars($_SESSION['sell_price']) : htmlspecialchars($product_variant['price']) ?>">
            </div>

            <div class="mb-3">
              <label for="quantity" class="form-label">Quantity</label>
              <small class="text-danger">
                <?php if(isset($_SESSION['quantityError'])) { echo htmlspecialchars($_SESSION['quantityError']); unset($_SESSION['quantityError']); } ?>
              </small>
              <input type="text" class="form-control" id="quantity" name="quantity"
                     value="<?= isset($_SESSION['quantity']) ? htmlspecialchars($_SESSION['quantity']) : htmlspecialchars($product_variant['quantity']) ?>">
            </div>

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

<?php include '../../includes/footer.php'; ?>
</body>
</html>