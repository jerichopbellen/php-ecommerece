<?php
session_start();
include('../includes/config.php');
include('../includes/header.php');

if (!isset($_SESSION['user_id'])) {
  $_SESSION['redirect'] = "Please log in to update your review.";
  header("Location: ../user/login.php");
  exit;
}

$user_id = intval($_SESSION['user_id']);
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$variant_id = isset($_GET['variant_id']) ? intval($_GET['variant_id']) : 0;

if ($product_id <= 0 || $order_id <= 0) {
  die("Invalid parameters.");
}

$stmt = mysqli_prepare($conn, "
  SELECT 
    p.name,
    (
      SELECT img_path 
      FROM product_images 
      WHERE product_id = p.product_id 
      ORDER BY image_id ASC 
      LIMIT 1
    ) AS img_path,
    pv.color,
    pv.material,
    r.rating,
    r.comment
  FROM products p
  LEFT JOIN product_variants pv ON pv.product_id = p.product_id AND pv.variant_id = ?
  LEFT JOIN reviews r ON r.user_id = ? AND r.product_id = p.product_id AND r.variant_id = ?
  WHERE p.product_id = ?
  LIMIT 1
");

mysqli_stmt_bind_param($stmt, "iiii", $variant_id, $user_id, $variant_id, $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$product) {
  die("Product not found.");
}

$color = isset($product['color']) ? trim($product['color']) : '';
$material = isset($product['material']) ? trim($product['material']) : '';
if ($color && $material) {
  $variant_label = htmlspecialchars("$color / $material", ENT_QUOTES, 'UTF-8');
} elseif ($color) {
  $variant_label = htmlspecialchars($color, ENT_QUOTES, 'UTF-8');
} elseif ($material) {
  $variant_label = htmlspecialchars($material, ENT_QUOTES, 'UTF-8');
} else {
  $variant_label = "N/A";
}

$existing_rating = intval($product['rating'] ?? 0);
$existing_comment = htmlspecialchars($product['comment'] ?? '', ENT_QUOTES, 'UTF-8');
?>

<div class="container my-5">
  <div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card shadow-sm">
    <div class="card-body">
      <h4 class="card-title mb-4">
      <i class="bi bi-pencil-fill me-2 text-dark"></i>Edit Your Review
      </h4>

      <?php if (!empty($product['img_path'])): ?>
      <div class="text-center mb-4">
        <img src="<?= htmlspecialchars($product['img_path'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>" class="img-fluid rounded" style="max-height: 200px;">
      </div>
      <?php endif; ?>

      <form action="update_review.php" method="POST">
      <input type="hidden" name="product_id" value="<?= htmlspecialchars($product_id, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_id, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="variant_id" value="<?= htmlspecialchars($variant_id, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="rating" id="rating-value" value="<?= htmlspecialchars($existing_rating, ENT_QUOTES, 'UTF-8') ?>">

      <div class="mb-3">
        <label class="form-label">Product</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>" disabled>
      </div>

      <?php if ($color || $material): ?>
        <div class="mb-3">
        <label class="form-label">Variant</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($variant_label, ENT_QUOTES, 'UTF-8') ?>" disabled>
        </div>
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label">Rating</label>
        <div id="star-picker" class="fs-4 text-warning">
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <i class="bi <?= $i <= $existing_rating ? 'bi-star-fill' : 'bi-star' ?>" data-value="<?= $i ?>"></i>
        <?php endfor; ?>
        </div>
        <small class="text-muted">Click to adjust your rating</small>
      </div>

      <div class="mb-3">
        <label for="comment" class="form-label">Comment</label>
        <textarea name="comment" id="comment" rows="4" class="form-control"><?= $existing_comment ?></textarea>
      </div>

      <div class="d-flex justify-content-between">
        <button type="submit" class="btn btn-warning">
        <i class="bi bi-pencil-fill me-1"></i>Update Review
        </button>
        <a href="order_history.php" class="btn btn-secondary">
        <i class="bi bi-x-circle me-1"></i>Cancel
        </a>
      </div>
      </form>
    </div>
    </div>
  </div>
  </div>
</div>

<script>
  const stars = document.querySelectorAll('#star-picker i');
  const ratingInput = document.getElementById('rating-value');

  stars.forEach(star => {
  star.addEventListener('click', () => {
    const rating = star.getAttribute('data-value');
    ratingInput.value = rating;

    stars.forEach(s => {
    s.classList.remove('bi-star-fill');
    s.classList.add('bi-star');
    });

    for (let i = 0; i < rating; i++) {
    stars[i].classList.remove('bi-star');
    stars[i].classList.add('bi-star-fill');
    }
  });
  });
</script>

<?php include('../includes/footer.php'); ?>