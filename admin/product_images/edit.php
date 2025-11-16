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

if (!$id || $id <= 0) {
  $_SESSION['flash'] = "Invalid image ID.";
  header("Location: index.php");
  exit;
}

// Prepared statement
$stmt = mysqli_prepare($conn, "
  SELECT 
    i.image_id, 
    i.img_path,
    p.product_id,
    p.name AS product_name,
    i.alt_text  
  FROM product_images i
  INNER JOIN products p ON i.product_id = p.product_id
  WHERE i.image_id = ?
  LIMIT 1
");

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$image = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$image) {
  $_SESSION['flash'] = "Image not found.";
  header("Location: index.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Product Image</title>
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
            <i class="bi bi-image me-2 text-dark"></i>Edit Product Image
          </h4>
          <form method="POST" enctype="multipart/form-data" action="update.php" novalidate>
            <input type="hidden" name="image_id" value="<?= htmlspecialchars($image['image_id']) ?>">
            <input type="hidden" name="existingImage" value="<?= htmlspecialchars($image['img_path']) ?>">

            <div class="mb-3">
              <label for="product" class="form-label">Product Name</label>
              <select class="form-select" id="product" name="product_id" disabled>
                <option value="<?= htmlspecialchars($image['product_id']) ?>" selected>
                  <?= htmlspecialchars($image['product_name']) ?>
                </option>
              </select>
            </div>

            <div class="mb-3">
              <label for="alt_text" class="form-label">Alt Text</label>
              <small class="text-danger">
                <?php if(isset($_SESSION['altError'])) { echo htmlspecialchars($_SESSION['altError']); unset($_SESSION['altError']); } ?>
              </small>
              <input type="text" class="form-control" id="alt_text" name="alt_text"
                     value="<?= isset($_SESSION['alt_text']) ? htmlspecialchars($_SESSION['alt_text']) : htmlspecialchars($image['alt_text']) ?>">
            </div>

            <div class="mb-3">
              <label for="image" class="form-label">Image File</label>
              <small class="text-danger">
                <?php if(isset($_SESSION['imageError'])) { echo htmlspecialchars($_SESSION['imageError']); unset($_SESSION['imageError']); } ?>
              </small>
              <input type="file" class="form-control" id="image" name="image">
              <div class="mt-3">
                <img src="<?= htmlspecialchars($image['img_path']) ?>" alt="<?= htmlspecialchars($image['alt_text']) ?>" width="250" height="250" class="border rounded">
              </div>
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