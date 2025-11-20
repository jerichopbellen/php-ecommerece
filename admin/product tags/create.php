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

$stmt_products = mysqli_prepare($conn, "SELECT product_id, name FROM products ORDER BY name ASC");
mysqli_stmt_execute($stmt_products);
$products = mysqli_stmt_get_result($stmt_products);

$stmt_tags = mysqli_prepare($conn, "SELECT tag_id, name FROM tags ORDER BY name ASC");
mysqli_stmt_execute($stmt_tags);
$tags = mysqli_stmt_get_result($stmt_tags);
?>

<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h4 class="card-title mb-4"><i class="bi bi-bookmark-plus me-2 text-dark"></i>Assign Tag to Product</h4>
          <form method="POST" action="store.php">
            <div class="mb-3">
              <label for="product_id" class="form-label">Product</label>
              <small class="text-danger">
                <?php if(isset($_SESSION['productError'])) { echo htmlspecialchars($_SESSION['productError']); unset($_SESSION['productError']); } ?>
              </small>
              <select name="product_id" id="product_id" class="form-select">
                <option value="" <?= !isset($_SESSION['product_id']) ? 'selected' : '' ?>>Select Product</option>
                <?php while ($p = mysqli_fetch_assoc($products)) : ?>
                  <option value="<?= htmlspecialchars($p['product_id'], ENT_QUOTES, 'UTF-8') ?>"
                    <?= isset($_SESSION['product_id']) && $_SESSION['product_id'] == $p['product_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="mb-3">
              <label for="tag_id" class="form-label">Tag</label>
              <small class="text-danger">
                <?php if(isset($_SESSION['tagError'])) { echo htmlspecialchars($_SESSION['tagError']); unset($_SESSION['tagError']); } ?>
              </small>
              <select name="tag_id" id="tag_id" class="form-select">
                <option value="" <?= !isset($_SESSION['tag_id']) ? 'selected' : '' ?>>Select Tag</option>
                <?php while ($t = mysqli_fetch_assoc($tags)) : ?>
                  <option value="<?= htmlspecialchars($t['tag_id'], ENT_QUOTES, 'UTF-8') ?>"
                    <?= isset($_SESSION['tag_id']) && $_SESSION['tag_id'] == $t['tag_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="d-flex justify-content-between">
              <button type="submit" class="btn btn-primary" name="submit" value="submit">
                <i class="bi bi-check-circle me-1"></i>Assign
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
mysqli_stmt_close($stmt_products);
mysqli_stmt_close($stmt_tags);
include '../../includes/footer.php'; 
?>