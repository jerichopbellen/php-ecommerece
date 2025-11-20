<?php
session_start();
include('./includes/header.php');
include('./includes/config.php');

$product_id = isset($_GET['product_id']) ? filter_var($_GET['product_id'], FILTER_VALIDATE_INT) : 0;

if ($product_id === false || $product_id <= 0) {
    echo "<div class='container py-5'><div class='alert alert-danger'>Invalid product.</div></div>";
    include './includes/footer.php';
    exit;
}

mysqli_begin_transaction($conn);

try {
    $stmt = mysqli_prepare($conn, "SELECT 
                p.product_id,
                p.name AS product_name,
                p.description,
                p.dimension,
                b.name AS brand_name,
                c.name AS category_name,
                (
                    SELECT pi.img_path 
                    FROM product_images pi 
                    WHERE pi.product_id = p.product_id 
                    ORDER BY pi.image_id ASC LIMIT 1
                ) AS img_path,
                ROUND(AVG(r.rating), 1) AS average_rating,
                COUNT(r.review_id) AS review_count
            FROM products p
            INNER JOIN brands b ON p.brand_id = b.brand_id
            INNER JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN reviews r ON r.product_id = p.product_id
            WHERE p.product_id = ?
            GROUP BY p.product_id");
    
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$product) {
        mysqli_rollback($conn);
        echo "<div class='container py-5'><div class='alert alert-warning'>Product not found.</div></div>";
        include './includes/footer.php';
        exit;
    }

    $stmt = mysqli_prepare($conn, "SELECT img_path FROM product_images WHERE product_id = ? ORDER BY image_id ASC");
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $image_result = mysqli_stmt_get_result($stmt);
    $product_images = [];
    while ($img = mysqli_fetch_assoc($image_result)) {
        $product_images[] = $img['img_path'];
    }
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "SELECT 
                    v.variant_id,
                    v.color,
                    v.material,
                    v.price,
                    s.quantity
                FROM product_variants v
                INNER JOIN stocks s ON v.variant_id = s.variant_id
                WHERE v.product_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $variant_result = mysqli_stmt_get_result($stmt);
    
    $total_stock = 0;
    $variants = [];
    while ($variant = mysqli_fetch_assoc($variant_result)) {
        $variants[] = $variant;
        $total_stock += $variant['quantity'];
    }
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "SELECT 
                    u.first_name,
                    u.last_name,
                    r.rating,
                    r.comment,
                    r.created_at,
                    r.updated_at,
                    pv.color,
                    pv.material
                FROM reviews r
                INNER JOIN users u ON r.user_id = u.user_id
                INNER JOIN product_variants pv ON r.variant_id = pv.variant_id
                WHERE r.product_id = ?
                ORDER BY r.created_at DESC");
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $review_result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "SELECT t.name 
        FROM product_tags pt
        INNER JOIN tags t ON pt.tag_id = t.tag_id
        WHERE pt.product_id = ?
        ORDER BY t.name ASC");
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $tag_result = mysqli_stmt_get_result($stmt);
    $product_tags = [];
    while ($tag = mysqli_fetch_assoc($tag_result)) {
        $product_tags[] = $tag['name'];
    }
    mysqli_stmt_close($stmt);

    mysqli_commit($conn);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "<div class='container py-5'><div class='alert alert-danger'>An error occurred while loading the product.</div></div>";
    include './includes/footer.php';
    exit;
}
?>

<div class="container my-5">
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-5">
                    <?php if (count($product_images) > 0): ?>
                        <div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner rounded">
                                <?php foreach ($product_images as $index => $img_path): ?>
                                    <div class="carousel-item <?=$index === 0 ? 'active' : '' ?>">
                                        <img src="<?=htmlspecialchars($img_path) ?>" 
                                             class="d-block w-100 rounded" 
                                             alt="Product Image <?=$index + 1 ?>"
                                             data-bs-toggle="modal" 
                                             data-bs-target="#imageModal"
                                             onclick="showZoomedImage('<?=htmlspecialchars($img_path, ENT_QUOTES, 'UTF-8') ?>')">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($product_images) > 1): ?>
                                <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <img src="placeholder.jpg" class="img-fluid rounded shadow-sm" alt="No image available">
                    <?php endif; ?>
                </div>

                <div class="col-md-7">
                    <h3 class="mb-3"><?=htmlspecialchars($product['product_name']) ?></h3>
                    <p class="text-muted"><?=nl2br(htmlspecialchars($product['description'])) ?></p>

                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item"><strong>Brand:</strong> <?=htmlspecialchars($product['brand_name']) ?></li>
                        <li class="list-group-item"><strong>Category:</strong> <?=htmlspecialchars($product['category_name']) ?></li>
                        <?php if (!empty($product['dimension'])): ?>
                            <li class="list-group-item"><strong>Dimensions:</strong> <?=htmlspecialchars($product['dimension']) ?></li>
                        <?php endif; ?>
                        <li class="list-group-item"><strong>Total Stock:</strong> <?= (int)$total_stock ?></li>
                    </ul>

                    <?php if (count($product_tags) > 0): ?>
                        <div class="mb-3">
                            <strong>Tags:</strong><br>
                            <?php foreach ($product_tags as $tag): ?>
                                <span class="badge bg-secondary me-1"><?=htmlspecialchars($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="rating mb-3">
                        <strong>Rating:</strong><br>
                        <?php
                        $rating = (float)$product['average_rating'];
                        for ($i = 1; $i <= 5; $i++) {
                            echo "<i class='bi " . ($rating >= $i ? "bi-star-fill" : ($rating >= $i - 0.5 ? "bi-star-half" : "bi-star")) . " text-warning'></i>";
                        }
                        echo " <span class='text-muted'>(" . number_format($rating, 1) . ")</span>";
                        ?>
                    </div>
                    
                    <label for="variant" class="form-label">Choose Variant:</label>                     <small class="text-danger"><?php if (isset($_SESSION['variantError'])) { echo $_SESSION['variantError']; unset($_SESSION['variantError']); } ?></small>
                    <select name="variant_id" id="variant" class="form-select mb-3" form="addToCartForm">
                        <?php foreach ($variants as $v): ?>
                            <option value="<?= (int)$v['variant_id'] ?>" <?=$v['quantity'] <= 0 ? 'disabled' : '' ?>>
                                <?php
                                $color = trim($v['color'] ?? '');
                                $material = trim($v['material'] ?? '');
                                if ($color && $material) {
                                    echo htmlspecialchars("$color / $material");
                                } elseif ($color) {
                                    echo htmlspecialchars($color);
                                } elseif ($material) {
                                    echo htmlspecialchars($material);
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                                - ₱<?=number_format((float)$v['price'], 2) ?> 
                                (<?= $v['quantity'] > 0 ? (int)$v['quantity'] . ' in stock' : 'Out of stock' ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="d-flex gap-2">
                        <form id="addToCartForm" method="POST" action="./cart/add_to_cart.php">
                            <input type="hidden" name="product_id" value="<?=(int)$product_id ?>">
                            <input type="hidden" name="action" value="add">
                            <button type="submit" name="submit" class="btn btn-outline-primary" <?=$total_stock <= 0 ? 'disabled' : '' ?>>
                                <i class="bi bi-cart-plus me-1"></i> Add to Cart
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <h4 class="mb-4">
            <i class="bi bi-chat-left-text me-2"></i>
            Reviews (<?=(int)$product['review_count'] ?>)
        </h4>

        <?php while ($review = mysqli_fetch_assoc($review_result)): ?>
            <div class="card mb-3 shadow-sm">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong><?=htmlspecialchars("{$review['first_name']} {$review['last_name']}") ?></strong>
                        <div class="text-muted small">
                            <?php
                                $created = date('m/d/Y \a\t H:i', strtotime($review['created_at']));
                                $updated = !empty($review['updated_at']) && $review['updated_at'] !== $review['created_at']
                                    ? date('m/d/Y \a\t H:i', strtotime($review['updated_at']))
                                    : null;
                            ?>

                            <?php if ($updated): ?>
                                <span>Updated <?=htmlspecialchars($updated) ?></span>
                            <?php else: ?>
                                <span><?=htmlspecialchars($created) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <?php
                            $color = trim($review['color'] ?? '');
                            $material = trim($review['material'] ?? '');

                            if ($color && $material) {
                                $variant_name = "$color / $material";
                            } elseif ($color) {
                                $variant_name = $color;
                            } elseif ($material) {
                                $variant_name = $material;
                            } else {
                                $variant_name = "N/A";
                            }
                        ?>
                        <p class="text-muted mb-0">
                            <i class="bi bi-tag me-1"></i>
                            Variant: <?=htmlspecialchars($variant_name) ?>
                        </p>

                        <div class="rating">
                            <?php
                            $r = (int)$review['rating'];
                            for ($i = 1; $i <= 5; $i++) {
                                echo "<i class='bi " . ($r >= $i ? "bi-star-fill" : ($r >= $i - 0.5 ? "bi-star-half" : "bi-star")) . " text-warning'></i>";
                            }
                            ?>
                        </div>
                    </div>

                    <p class="mb-0"><?=nl2br(htmlspecialchars($review['comment'])) ?></p>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <img id="zoomedImage" src="" alt="Zoomed Product Image">
            </div>
        </div>
    </div>
</div>

<script>
    function showZoomedImage(imagePath) {
        document.getElementById('zoomedImage').src = imagePath;
    }
</script>

<?php include('./includes/footer.php'); ?></p>
