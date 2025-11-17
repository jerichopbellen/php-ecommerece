<?php
include("config.php");

$cart_count = 0;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT COUNT(*) AS item_count FROM cart_items WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $cart_count = (int) $row['item_count'];
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Furniture Shop</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/includes/style/style.css" rel="stylesheet" type="text/css" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top border-bottom border-secondary" style="z-index: 1030;">
  <div class="container-fluid">
    <a class="navbar-brand fw-semibold text-warning" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/index.php">
      <i class="bi bi-gem me-2"></i>Furniture Shops
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
      aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link active text-light" style="opacity: 0.85;" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-light" style="opacity: 0.85;" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/contact/index.php">Contact</a>
        </li>
        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin'): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle text-light" style="opacity: 0.85;" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              Admin Panel
            </a>
            <ul class="dropdown-menu dropdown-menu-dark">
              <li><a class="dropdown-item" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/admin/products/index.php">Products</a></li>
              <li><a class="dropdown-item" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/admin/product variants/index.php">Product Variants</a></li>
              <li><a class="dropdown-item" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/admin/product_images/index.php">Product Images</a></li>
              <li><a class="dropdown-item" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/admin/product tags/index.php">Product Tags</a></li>
              <li><a class="dropdown-item" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/admin/tags/index.php">Tags</a></li>
              <li><a class="dropdown-item" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/admin/categories/index.php">Categories</a></li>
              <li><a class="dropdown-item" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/admin/brands/index.php">Brands</a></li>
              <li><a class="dropdown-item" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/admin/orders/index.php">Orders</a></li>
              <li><a class="dropdown-item" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/admin/users/index.php">Users</a></li>
              <li><a class="dropdown-item" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/admin/contact/index.php">Contact Messages</a></li>

            </ul>
          </li>
        <?php endif; ?>
      </ul>

      <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="GET" class="d-flex me-3">
        <input class="form-control me-2 bg-dark text-light border-secondary" type="search" placeholder="Search" aria-label="Search" name="search" />
        <button class="btn btn-outline-warning" type="submit">Search</button>
      </form>

      <div class="navbar-nav ms-auto d-flex align-items-center gap-3">
        <?php if (!isset($_SESSION['user_id'])): ?>
          <a href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/user/login.php" class="nav-item nav-link text-light" style="opacity: 0.85;">Login</a>
        <?php else: ?>
          <div class="nav-item">
            <a href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/cart/view_cart.php" class="nav-link text-light" style="opacity: 0.85;">
              <div class="position-relative d-inline-block">
                <i class="bi bi-cart fs-5"></i>
                <?php if ($cart_count > 0): ?>
                  <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark" style="z-index: 10;">
                    <?= $cart_count ?>
                    <span class="visually-hidden">items in cart</span>
                  </span>
                <?php endif; ?>
              </div>
            </a>
          </div>
          <div class="nav-item dropdown">
            <a class="nav-link dropdown-toggle text-light" style="opacity: 0.85;" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <?php
                $user_id = $_SESSION['user_id'];
                $stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                  echo htmlspecialchars($row['email']);
                }
                $stmt->close();
                ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
              <li><a class="dropdown-item" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/user/profile.php">My Profile</a></li>
              <li><a class="dropdown-item" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/orders/view_orders.php">My Orders</a></li>
              <li><a class="dropdown-item" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/orders/order_history.php">Order History</a></li>
              <li><a class="dropdown-item" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/contact/my_messages.php">My Messages</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="http://<?= $_SERVER['SERVER_NAME'] ?>/furnitures/user/logout.php">Logout</a></li>
            </ul>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>