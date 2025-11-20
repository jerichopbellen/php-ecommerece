<?php
session_start();
include('../includes/header.php');
include('../includes/config.php');

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect'] = "You need to login to view your cart.";
    header("Location: ../user/login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);

$sql = "
    SELECT 
        ci.cart_item_id,
        ci.quantity,
        pv.variant_id,
        pv.product_id,
        pv.price AS variant_price,
        pv.color,
        pv.material,
        p.name AS product_name
    FROM cart_items ci
    INNER JOIN product_variants pv ON pv.variant_id = ci.variant_id
    INNER JOIN products p ON p.product_id = pv.product_id
    WHERE ci.user_id = ?
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<div class="container my-5">
    <div class="card shadow-sm">
        <div class="card-body">
            <h3 class="text-center mb-4"><i class="bi bi-cart me-2"></i>Your Shopping Cart</h3>

            <?php $checkout_blocked = false; ?>

            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>Quantity</th>
                            <th>Product</th>
                            <th>Variant</th>
                            <th>Price</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total = 0;

                        while ($row = mysqli_fetch_assoc($result)) {
                            $cart_item_id = intval($row['cart_item_id']);
                            $product_name = htmlspecialchars($row['product_name'], ENT_QUOTES, 'UTF-8');
                            $product_id = intval($row['product_id']);
                            $current_variant_id = intval($row['variant_id']);
                            $price = floatval($row['variant_price']);
                            $qty = max(1, intval($row['quantity']));
                            $subtotal = $price * $qty;
                            $total += $subtotal;

                            $stock_sql = "SELECT quantity FROM stocks WHERE variant_id = ?";
                            $stock_stmt = mysqli_prepare($conn, $stock_sql);
                            mysqli_stmt_bind_param($stock_stmt, "i", $current_variant_id);
                            mysqli_stmt_execute($stock_stmt);
                            $stock_result = mysqli_stmt_get_result($stock_stmt);
                            $stock_row = mysqli_fetch_assoc($stock_result);
                            $current_stock = isset($stock_row['quantity']) ? intval($stock_row['quantity']) : 0;
                            mysqli_stmt_close($stock_stmt);

                            $exceeds_stock = $qty > $current_stock;
                            if ($exceeds_stock) {
                                $checkout_blocked = true;
                            }

                            $variant_sql = "SELECT variant_id, color, material, price FROM product_variants WHERE product_id = ?";
                            $variant_stmt = mysqli_prepare($conn, $variant_sql);
                            mysqli_stmt_bind_param($variant_stmt, "i", $product_id);
                            mysqli_stmt_execute($variant_stmt);
                            $variant_result = mysqli_stmt_get_result($variant_stmt);

                            $variant_dropdown = "<select name='variant_id[$cart_item_id]' class='form-select text-center'>";
                            while ($variant = mysqli_fetch_assoc($variant_result)) {
                                $vid = intval($variant['variant_id']);
                                $color = trim($variant['color']);
                                $material = trim($variant['material']);
                                
                                if ($color && $material) {
                                    $label = htmlspecialchars("$color / $material", ENT_QUOTES, 'UTF-8');
                                } elseif ($color) {
                                    $label = htmlspecialchars($color, ENT_QUOTES, 'UTF-8');
                                } elseif ($material) {
                                    $label = htmlspecialchars($material, ENT_QUOTES, 'UTF-8');
                                } else {
                                    $label = "N/A";
                                }

                                $selected = ($vid === $current_variant_id) ? "selected" : "";
                                $variant_dropdown .= "<option value='$vid' $selected>$label</option>";
                            }
                            $variant_dropdown .= "</select>";

                            echo "<tr>";
                            echo "<td>
                                    <form action='qty_update.php' method='POST' class='d-flex justify-content-center align-items-center gap-2'>
                                        <input type='hidden' name='cart_item_id' value='$cart_item_id'>
                                        <button type='submit' name='action' value='decrease' class='btn btn-sm btn-outline-secondary'>
                                            <i class='bi bi-dash'></i>
                                        </button>
                                        <input type='number' name='product_qty' value='$qty' min='1' readonly class='form-control form-control-sm text-center' style='width: 50px;'>
                                        <button type='submit' name='action' value='increase' class='btn btn-sm btn-outline-secondary'>
                                            <i class='bi bi-plus'></i>
                                        </button>
                                    </form>
                                  </td>";

                            echo "<td>$product_name</td>";
                            echo "<td>
                                    <form action='variant_update.php' method='POST'>
                                        <input type='hidden' name='cart_item_id' value='$cart_item_id'>
                                        $variant_dropdown
                                        <script>
                                            document.currentScript.previousElementSibling.onchange = function() {
                                                this.form.submit();
                                            };
                                        </script>
                                    </form>
                                  </td>";
                            echo "<td>₱" . number_format($price, 2) . "</td>";
                            echo "<td>₱" . number_format($subtotal, 2);
                            if ($exceeds_stock) {
                                echo "<br><span class='text-danger small'>Only $current_stock in stock</span>";
                            }
                            echo "</td>";
                            echo "<td>
                                    <a href='remove.php?id=$cart_item_id' onclick=\"return confirm('Remove this item from your cart?')\" class='text-danger'>
                                        <i class='fa-solid fa-trash'></i>
                                    </a>
                                  </td>";
                            echo "</tr>";

                            mysqli_stmt_close($variant_stmt);
                        }
                        mysqli_stmt_close($stmt);
                        ?>
                        <tr class="table-secondary">
                            <td colspan="6" class="text-end">
                                <strong>Total: ₱<?=number_format($total, 2) ?></strong>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="6" class="text-end">
                                <a href="../index.php" class="btn btn-outline-primary me-2">
                                    <i class="bi bi-arrow-left"></i> Continue Shopping
                                </a>
                                <?php if ($checkout_blocked): ?>
                                    <button class="btn btn-secondary" disabled>
                                        <i class="bi bi-exclamation-circle"></i> Checkout
                                    </button>
                                <?php else: ?>
                                    <a href="../cart/checkout.php" class="btn btn-success">
                                        <i class="bi bi-bag-check"></i> Checkout
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <?php if ($checkout_blocked): ?>
                <div class="alert alert-danger mt-3 text-center">
                    One or more items exceed available stock. Please adjust quantities before checking out.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>