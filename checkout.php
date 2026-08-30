<?php
// Enable error reporting for debugging (remove after fixing)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session properly
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'functions.php';

// Check if cart is empty
if(empty(getCart())) {
    header("Location: index.php");
    exit;
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify captcha (skip if keys not set)
    $captcha_valid = true;
    if(defined('RECAPTCHA_SECRET_KEY') && RECAPTCHA_SECRET_KEY != 'YOUR_SECRET_KEY') {
        $captcha_valid = verifyRecaptcha($_POST['g-recaptcha-response'] ?? '');
        if(!$captcha_valid) {
            $error = "Please complete the captcha verification.";
        }
    }
    
    if($captcha_valid) {
        $order_number = generateOrderNumber();
        $total = getCartTotal();
        
        // Escape and prepare data
        $customer_name = $_POST['name'] ?? '';
        $customer_email = $_POST['email'] ?? '';
        $customer_phone = $_POST['phone'] ?? '';
        $shipping_address = $_POST['address'] ?? '';
        $city = $_POST['city'] ?? '';
        $postal_code = $_POST['postal_code'] ?? '';
        $country = $_POST['country'] ?? '';
        $payment_method = $_POST['payment_method'] ?? 'bank_transfer';
        $notes = $_POST['notes'] ?? '';
        
        // Validate required fields
        if(empty($customer_name) || empty($customer_email) || empty($shipping_address)) {
            $error = "Please fill in all required fields.";
        } else {
            // Insert order
            $stmt = $conn->prepare("INSERT INTO orders (order_number, customer_name, customer_email, customer_phone, shipping_address, city, postal_code, country, total_amount, payment_method, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssss", $order_number, $customer_name, $customer_email, $customer_phone, $shipping_address, $city, $postal_code, $country, $total, $payment_method, $notes);
            
            if($stmt->execute()) {
                $order_id = $conn->insert_id;
                
                // Insert order items
                foreach(getCart() as $variant_id => $qty) {
                    $stmt2 = $conn->prepare("SELECT pv.*, pr.name as product_name FROM product_variants pv JOIN products pr ON pv.product_id = pr.id WHERE pv.id = ?");
                    $stmt2->bind_param("i", $variant_id);
                    $stmt2->execute();
                    $item = $stmt2->get_result()->fetch_assoc();
                    
                    if($item) {
                        $stmt3 = $conn->prepare("INSERT INTO order_items (order_id, product_name, variant_name, quantity, unit_price) VALUES (?, ?, ?, ?, ?)");
                        $stmt3->bind_param("issii", $order_id, $item['product_name'], $item['variant_name'], $qty, $item['price']);
                        $stmt3->execute();
                    }
                }
                
                // Send confirmation email (don't stop if fails)
                $email_body = "<h2>Order Confirmation #$order_number</h2>
                              <p>Thank you for your order, $customer_name!</p>
                              <p>Order Total: $" . number_format($total, 2) . "</p>
                              <p>We will notify you when your order ships.</p>";
                sendEmail($customer_email, "Order Confirmation #$order_number", $email_body);
                
                // Clear cart
                clearCart();
                
                // Store order number for completion page
                $_SESSION['order_completed'] = $order_number;
                
                // Redirect based on payment method
                if($payment_method == 'paypal') {
                    header("Location: paypal_payment.php?order_id=$order_id");
                } else {
                    header("Location: order_complete.php");
                }
                exit;
            } else {
                $error = "Database error: " . $conn->error;
            }
        }
    }
}

// Get cart total for display
$cart_total = getCartTotal();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div class="container mt-4">
        <h1>Checkout</h1>
        <a href="cart.php" class="btn btn-secondary mb-3">← Back to Cart</a>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-7">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Billing & Shipping Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="checkoutForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Street Address *</label>
                                <textarea name="address" class="form-control" rows="2" required></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-5 mb-3">
                                    <label class="form-label">City *</label>
                                    <input type="text" name="city" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Postal Code</label>
                                    <input type="text" name="postal_code" class="form-control">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Country *</label>
                                    <input type="text" name="country" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Order Notes (Optional)</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Special instructions, delivery notes, etc."></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Payment Method *</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" value="paypal" id="paypal">
                                    <label class="form-check-label" for="paypal">
                                        <img src="https://www.paypal.com/webapps/mpp/assets/logo/images/paypal-logo.png" height="20"> PayPal
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" value="bank_transfer" id="bank_transfer" checked>
                                    <label class="form-check-label" for="bank_transfer">
                                        Bank Transfer (Manual)
                                    </label>
                                </div>
                            </div>
                            
                            <?php if(defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY != 'YOUR_SITE_KEY'): ?>
                            <div class="mb-3">
                                <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                            </div>
                            <?php endif; ?>
                            
                            <button type="submit" class="btn btn-success btn-lg w-100">Place Order</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <h3 class="text-center">Total: $<?php echo number_format($cart_total, 2); ?></h3>
                        <hr>
                        <small class="text-muted">Shipping calculated at checkout</small>
                    </div>
                </div>
                
                <?php if(isset($_GET['debug'])): ?>
                <div class="card mt-3 bg-light">
                    <div class="card-body">
                        <h6>Debug Info:</h6>
                        <pre><?php
                            echo "Cart items: " . print_r(getCart(), true);
                            echo "\nTotal: " . $cart_total;
                            echo "\nSession ID: " . session_id();
                        ?></pre>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>