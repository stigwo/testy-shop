<?php
// Session is already started in header.php, so no need to start again
require_once 'functions.php';

include 'header.php';

$cart_items = [];
$total = 0;

// Debug - show what's in session
if(isset($_SESSION['cart'])) {
    echo "<!-- Cart contents: " . print_r($_SESSION['cart'], true) . " -->";
}

if(isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach($_SESSION['cart'] as $variant_id => $quantity) {
        // Skip if quantity is 0
        if($quantity == 0) continue;
        
        $stmt = $conn->prepare("SELECT pv.*, p.name as product_name FROM product_variants pv JOIN products p ON pv.product_id = p.id WHERE pv.id = ?");
        $stmt->bind_param("i", $variant_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if($item = $result->fetch_assoc()) {
            $item['quantity'] = $quantity;
            $item['subtotal'] = $item['price'] * $quantity;
            $cart_items[] = $item;
            $total += $item['subtotal'];
        }
    }
}
?>

<div class="container mt-4">
    <h1>Shopping Cart</h1>
    
    <?php if(empty($cart_items)): ?>
        <div class="alert alert-info">
            <i class="fas fa-shopping-cart"></i> Your cart is empty. 
            <a href="index.php">Continue shopping</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Product</th>
                        <th>Model</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($cart_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['variant_name']); ?></td>
                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                        <td>
                            <form method="POST" action="update_cart.php" style="display: inline;">
                                <input type="hidden" name="variant_id" value="<?php echo $item['id']; ?>">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" style="width: 70px;" onchange="this.form.submit()">
                            </form>
                        </td>
                        <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
                        <td>
                            <a href="remove_from_cart.php?id=<?php echo $item['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove item?')">Remove</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-primary">
                        <td colspan="4" class="text-end fw-bold">Total:</td>
                        <td colspan="2" class="fw-bold">$<?php echo number_format($total, 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="text-end mt-3">
            <a href="index.php" class="btn btn-secondary">Continue Shopping</a>
            <a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>