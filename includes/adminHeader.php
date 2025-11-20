<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="includes/style/style.css" rel="stylesheet" type="text/css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top border-bottom border-secondary px-4">
  <div class="container-fluid">
    <a class="navbar-brand fw-semibold text-warning" href="#">
      <i class="bi bi-person-gear me-2"></i>Admin Panel
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="adminNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link text-light" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/admin/products/index.php"><i class="bi bi-box-seam me-1"></i>Products</a></li>
        <li class="nav-item"><a class="nav-link text-light" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/admin/product variants/index.php"><i class="bi bi-sliders me-1"></i>Product Variants</a></li>
        <li class="nav-item"><a class="nav-link text-light" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/admin/product tags/index.php"><i class="bi bi-bookmark-plus me-1"></i>Product Tags</a></li>
        <li class="nav-item"><a class="nav-link text-light" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/admin/tags/index.php"><i class="bi bi-bookmarks me-1"></i>Tags</a></li>
        <li class="nav-item"><a class="nav-link text-light" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/admin/product_images/index.php"><i class="bi bi-image me-1"></i>Product Images</a></li>
        <li class="nav-item"><a class="nav-link text-light" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/admin/brands/index.php"><i class="bi bi-tags me-1"></i>Brands</a></li>
        <li class="nav-item"><a class="nav-link text-light" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/admin/categories/index.php"><i class="bi bi-grid me-1"></i>Categories</a></li>
        <li class="nav-item"><a class="nav-link text-light" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/admin/orders/index.php"><i class="bi bi-receipt me-1"></i>Orders</a></li>
        <li class="nav-item"><a class="nav-link text-light" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/admin/users/index.php"><i class="bi bi-people me-1"></i>Users</a></li>
        <li class="nav-item"><a class="nav-link text-light" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/admin/contact/index.php"><i class="bi bi-inbox me-1"></i>Contact Messages</a></li>  
      </ul>
      <div class="d-flex align-items-center">
        <a href="http://localhost/furnitures/index.php" class="btn btn-outline-warning">
          <i class="bi bi-arrow-left-circle me-1"></i> Back to Shop
        </a>
      </div>
    </div>
  </div>
</nav>

    <div class="container mt-4">
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>