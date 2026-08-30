<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'functions.php';

$order_id = $_GET['order_id'] ?? 0;
$order = $conn->query("SELECT * FROM orders WHERE id = $order_id")->fetch_assoc();

if(!$order) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>PayPal Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-body text-center">
                <h2>PayPal Checkout</h2>
                <p>Order #<?php echo $order['order_number']; ?></p>
                <p>Amount: $<?php echo number_format($order['total_amount'], 2); ?></p>
                
                <!-- Sandbox PayPal Button (Replace with live credentials) -->
                <div id="paypal-button-container"></div>
                <a href="order_complete.php" class="btn btn-secondary mt-3">Cancel</a>
            </div>
        </div>
    </div>
    
    <script src="https://www.paypal.com/sdk/js?client-id=<?php echo defined('PAYPAL_CLIENT_ID') ? PAYPAL_CLIENT_ID : 'test'; ?>&currency=USD"></script>
    <script>
        paypal.Buttons({
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            value: '<?php echo $order['total_amount']; ?>'
                        }
                    }]
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    window.location.href = 'order_complete.php';
                });
            },
            onError: function(err) {
                console.error(err);
                alert('Payment failed. Please try bank transfer.');
                window.location.href = 'order_complete.php';
            }
        }).render('#paypal-button-container');
    </script>
</body>
</html>