<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'functions.php';

$order_number = $_SESSION['order_completed'] ?? 'N/A';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Order Complete</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-body text-center">
                <div class="alert alert-success">
                    <h1>✓ Order Completed!</h1>
                    <p class="lead">Thank you for your purchase!</p>
                    <p>Your order number is: <strong><?php echo $order_number; ?></strong></p>
                    <hr>
                    <h5>Bank Transfer Instructions:</h5>
                    <p>
                        Bank: <?php echo defined('BANK_NAME') ? BANK_NAME : 'Bank Name'; ?><br>
                        Account Name: <?php echo defined('ACCOUNT_NAME') ? ACCOUNT_NAME : 'Account Name'; ?><br>
                        Account Number: <?php echo defined('ACCOUNT_NUMBER') ? ACCOUNT_NUMBER : '12345678'; ?><br>
                        IBAN: <?php echo defined('IBAN') ? IBAN : 'IBAN Number'; ?><br>
                        SWIFT: <?php echo defined('SWIFT_CODE') ? SWIFT_CODE : 'SWIFT Code'; ?>
                    </p>
                    <p class="text-muted">Please use your order number as payment reference.</p>
                    <a href="index.php" class="btn btn-primary mt-3">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php unset($_SESSION['order_completed']); ?>