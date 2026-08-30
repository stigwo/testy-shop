<?php 
require_once "functions.php";

if(\$_SERVER['REQUEST_METHOD'] === 'POST' && isset(\$_POST['update_cart'])) {
    foreach(\$_POST['quantities'] as \$variant_id => \$qty) {
        if(\$qty <= 0) {
            removeFromCart(\$variant_id);
        } else {
            \$_SESSION['cart'][\$variant_id] = \$qty;
        }
    }
    header("Location: cart.php");
    exit;
}

if(\$_SERVER['REQUEST_METHOD'] === 'POST' && isset(\$_POST['remove_item'])) {
    removeFromCart(\$_POST['variant_id']);
    header("Location: cart.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Shopping Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>🛒 Shopping Cart</h1>
        <a href="index.php" class="btn btn-secondary mb-3">← Continue Shopping</a>
        <?php if(empty(getCart())): ?>
            <div class="alert alert-info">Your cart is empty.</div>
        <?php else: ?>
            <form method="POST">
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr><th>Product</th><th>Variant</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php 
                    \$total = 0;
                    foreach(getCart() as \$variant_id => \$qty):
                        \$stmt = \$conn->prepare("SELECT pv.*, pr.name as product_name FROM product_variants pv JOIN products pr ON pv.product_id = pr.id WHERE pv.id = ?");
                        \$stmt->bind_param("i", \$variant_id);
                        \$stmt->execute();
                        \$item = \$stmt->get_result()->fetch_assoc();
                        \$subtotal = \$item['price'] * \$qty;
                        \$total += \$subtotal;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars(\$item['product_name']); ?></td>
                            <td><?php echo htmlspecialchars(\$item['variant_name']); ?></td>
                            <td>$<?php echo number_format(\$item['price'], 2); ?></td>
                            <td><input type="number" name="quantities[<?php echo \$variant_id; ?>]" value="<?php echo \$qty; ?>" min="0" class="form-control" style="width: 80px;"></td>
                            <td>$<?php echo number_format(\$subtotal, 2); ?></td>
                            <td><button type="submit" name="remove_item" value="1" class="btn btn-sm btn-danger">✖ Remove</button>
                                <input type="hidden" name="variant_id" value="<?php echo \$variant_id; ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <th colspan="4" class="text-end">Total:</th>
                            <th>$<?php echo number_format(\$total, 2); ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
                <button type="submit" name="update_cart" class="btn btn-secondary">🔄 Update Cart</button>
                <a href="checkout.php" class="btn btn-success">✅ Proceed to Checkout</a>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>