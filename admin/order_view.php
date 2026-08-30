<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../functions.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if(!$order_id) {
    die("Order ID required. Please use: order_view.php?id=1");
}

// Get total pages count for badge
$total_pages = 0;
$pages_check = $conn->query("SHOW TABLES LIKE 'pages'");
if($pages_check && $pages_check->num_rows > 0) {
    $total_pages = $conn->query("SELECT COUNT(*) as count FROM pages")->fetch_assoc()['count'];
}

// Get total tickets count
$total_tickets = 0;
$ticket_check = $conn->query("SHOW TABLES LIKE 'support_tickets'");
if($ticket_check && $ticket_check->num_rows > 0) {
    $tickets_result = $conn->query("SELECT COUNT(*) as count FROM support_tickets WHERE status != 'closed'");
    if($tickets_result) {
        $total_tickets = $tickets_result->fetch_assoc()['count'];
    }
}

// Get order details
$result = $conn->query("SELECT * FROM orders WHERE id = $order_id");

if(!$result) {
    die("Database error: " . $conn->error);
}

$order = $result->fetch_assoc();

if(!$order) {
    die("Order not found. Order ID $order_id does not exist.");
}

// Get order items
$items = $conn->query("SELECT * FROM order_items WHERE order_id = $order_id");

// Handle order status update
$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['order_status'];
    $send_email = isset($_POST['notify_customer']) ? true : false;
    $update_sql = "UPDATE orders SET order_status = '$new_status' WHERE id = $order_id";
    
    if($conn->query($update_sql)) {
        $success = "Order status updated to " . ucfirst($new_status) . "!";
        
        // Send email notification if requested
        if($send_email && !empty($order['customer_email']) && function_exists('sendEmail')) {
            $subject = 'Order #' . ($order['order_number'] ?? $order_id) . ' Status Update - ' . (defined('SITE_NAME') ? SITE_NAME : 'Revobake');
            $email_body = "
            <html>
            <body>
                <h3>Order Status Update</h3>
                <p>Dear " . htmlspecialchars($order['customer_name']) . ",</p>
                <p>Your order <strong>#" . ($order['order_number'] ?? $order_id) . "</strong> status has been updated to: <strong>" . ucfirst($new_status) . "</strong></p>
                <p>Thank you for shopping with us!</p>
                <p>Best regards,<br>" . (defined('SITE_NAME') ? SITE_NAME : 'Revobake') . " Team</p>
            </body>
            </html>
            ";
            
            sendEmail($order['customer_email'], $subject, $email_body);
            $success .= " Customer notified via email.";
        }
        
        // Refresh order data
        $result = $conn->query("SELECT * FROM orders WHERE id = $order_id");
        $order = $result->fetch_assoc();
    } else {
        $error = "Failed to update status: " . $conn->error;
    }
}

// Handle tracking number update
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_tracking'])) {
    $tracking_number = trim($_POST['tracking_number']);
    $update_sql = "UPDATE orders SET tracking_number = '$tracking_number' WHERE id = $order_id";
    
    if($conn->query($update_sql)) {
        $success = "Tracking number updated!";
        
        if(isset($_POST['notify_tracking']) && !empty($order['customer_email']) && !empty($tracking_number)) {
            $subject = 'Tracking Information for Order #' . ($order['order_number'] ?? $order_id);
            $email_body = "
            <html>
            <body>
                <h3>Your Order Has Been Shipped!</h3>
                <p>Dear " . htmlspecialchars($order['customer_name']) . ",</p>
                <p>Your order <strong>#" . ($order['order_number'] ?? $order_id) . "</strong> has been shipped.</p>
                <p><strong>Tracking Number:</strong> " . htmlspecialchars($tracking_number) . "</p>
                <p>Thank you for shopping with us!</p>
                <p>Best regards,<br>" . (defined('SITE_NAME') ? SITE_NAME : 'Revobake') . " Team</p>
            </body>
            </html>
            ";
            sendEmail($order['customer_email'], $subject, $email_body);
            $success .= " Tracking notification sent to customer.";
        }
        
        // Refresh order data
        $result = $conn->query("SELECT * FROM orders WHERE id = $order_id");
        $order = $result->fetch_assoc();
    } else {
        $error = "Failed to update tracking: " . $conn->error;
    }
}

function getStatusBadge($status) {
    if(!$status) return 'secondary';
    switch($status) {
        case 'new': return 'warning';
        case 'processing': return 'info';
        case 'shipped': return 'primary';
        case 'delivered': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

function getPaymentStatusBadge($status) {
    if(!$status) return 'secondary';
    switch($status) {
        case 'pending': return 'warning';
        case 'paid': return 'success';
        case 'failed': return 'danger';
        default: return 'secondary';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Order #<?php echo $order['order_number'] ?? $order_id; ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: white;
            padding: 12px 20px;
            margin: 5px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.2);
            transform: translateX(5px);
        }
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.3);
            font-weight: bold;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        .status-badge {
            font-size: 14px;
            padding: 5px 12px;
        }
        .print-area {
            background: white;
        }
        .navbar-top {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px 0;
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .email-checkbox {
            background: #e7f3ff;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        @media print {
            .no-print {
                display: none;
            }
            .print-area {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 p-0">
                <div class="sidebar p-3">
                    <h4 class="text-white text-center mb-4">
                        <i class="fas fa-crown"></i> Admin Panel
                    </h4>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a class="nav-link" href="products.php">
                            <i class="fas fa-box"></i> Products
                        </a>
                        <a class="nav-link active" href="orders.php">
                            <i class="fas fa-shopping-cart"></i> Orders
                        </a>
                        <a class="nav-link" href="customers.php">
                            <i class="fas fa-users"></i> Customers
                        </a>
                        <a class="nav-link" href="support_tickets.php">
                            <i class="fas fa-ticket-alt"></i> Support Tickets
                            <?php if($total_tickets > 0): ?>
                                <span class="badge bg-warning float-end"><?php echo $total_tickets; ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link" href="pages.php">
                            <i class="fas fa-file-alt"></i> Pages
                            <?php if($total_pages > 0): ?>
                                <span class="badge bg-info float-end"><?php echo $total_pages; ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <hr class="bg-light">
                        <a class="nav-link" href="../index.php" target="_blank">
                            <i class="fas fa-store"></i> View Shop
                        </a>
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-0">
                <div class="navbar-top px-4 d-flex justify-content-between align-items-center no-print">
                    <h4 class="mb-0">Order #<?php echo $order['order_number'] ?? $order_id; ?></h4>
                    <div>
                        <a href="orders.php" class="btn btn-outline-secondary me-2">← Back to Orders</a>
                        <span class="text-muted me-3">
                            <i class="fas fa-user"></i> <?php echo $_SESSION['admin_username'] ?? 'Admin'; ?>
                        </span>
                        <a href="logout.php" class="btn btn-sm btn-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>

                <div class="main-content p-4 print-area">
                    <?php if($success): ?>
                        <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-8">
                            <!-- Order Items -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h4 class="mb-0">Order Items</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Quantity</th>
                                                    <th>Unit Price</th>
                                                    <th>Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $subtotal = 0;
                                                if($items && $items->num_rows > 0): 
                                                    while($item = $items->fetch_assoc()): 
                                                        $item_subtotal = $item['price'] * $item['quantity'];
                                                        $subtotal += $item_subtotal;
                                                ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                                                        <td class="text-end">$<?php echo number_format($item['price'], 2); ?></td>
                                                        <td class="text-end">$<?php echo number_format($item_subtotal, 2); ?></td>
                                                    </tr>
                                                <?php 
                                                    endwhile; 
                                                else: 
                                                ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center">No items found for this order</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-secondary">
                                                    <td colspan="3" class="text-end fw-bold">Subtotal:</td>
                                                    <td class="text-end fw-bold">$<?php echo number_format($subtotal, 2); ?></td>
                                                </tr>
                                                <tr class="table-primary">
                                                    <td colspan="3" class="text-end fw-bold">Total:</td>
                                                    <td class="text-end fw-bold">$<?php echo number_format($order['total_amount'], 2); ?></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Customer Details -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h4 class="mb-0">Customer Details</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong><i class="fas fa-user"></i> Name:</strong><br>
                                            <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                            
                                            <p><strong><i class="fas fa-envelope"></i> Email:</strong><br>
                                            <a href="mailto:<?php echo $order['customer_email']; ?>"><?php echo htmlspecialchars($order['customer_email']); ?></a></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong><i class="fas fa-phone"></i> Phone:</strong><br>
                                            <?php echo htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?></p>
                                            
                                            <p><strong><i class="fas fa-calendar"></i> Order Date:</strong><br>
                                            <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    <p><strong><i class="fas fa-truck"></i> Shipping Address:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                                    
                                    <?php if(!empty($order['notes'])): ?>
                                    <hr>
                                    <p><strong><i class="fas fa-sticky-note"></i> Order Notes:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- Order Status Card -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Order Status</h5>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-3">
                                        <span class="badge bg-<?php echo getStatusBadge($order['order_status']); ?> status-badge">
                                            <i class="fas fa-circle"></i> <?php echo ucfirst($order['order_status'] ?? 'New'); ?>
                                        </span>
                                    </div>
                                    
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label class="form-label">Update Status:</label>
                                            <select name="order_status" class="form-select">
                                                <option value="new" <?php echo ($order['order_status'] ?? '') == 'new' ? 'selected' : ''; ?>>New</option>
                                                <option value="processing" <?php echo ($order['order_status'] ?? '') == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="shipped" <?php echo ($order['order_status'] ?? '') == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                <option value="delivered" <?php echo ($order['order_status'] ?? '') == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                <option value="cancelled" <?php echo ($order['order_status'] ?? '') == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </div>
                                        <div class="email-checkbox mb-3">
                                            <input type="checkbox" name="notify_customer" id="notify_customer" value="1" checked>
                                            <label for="notify_customer" class="mb-0">
                                                <i class="fas fa-envelope"></i> Send email notification to customer
                                            </label>
                                        </div>
                                        <button type="submit" name="update_status" class="btn btn-primary w-100">
                                            <i class="fas fa-save"></i> Update Status
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Payment Info Card -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Payment Information</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Payment Method:</strong></td>
                                            <td class="text-end"><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'] ?? 'N/A')); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Payment Status:</strong></td>
                                            <td class="text-end">
                                                <span class="badge bg-<?php echo getPaymentStatusBadge($order['payment_status']); ?>">
                                                    <?php echo ucfirst($order['payment_status'] ?? 'Pending'); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Total Amount:</strong></td>
                                            <td class="text-end fw-bold">$<?php echo number_format($order['total_amount'], 2); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Tracking Info Card -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Tracking Information</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <input type="text" name="tracking_number" class="form-control" placeholder="Enter tracking number" value="<?php echo htmlspecialchars($order['tracking_number'] ?? ''); ?>">
                                        </div>
                                        <div class="email-checkbox mb-3">
                                            <input type="checkbox" name="notify_tracking" id="notify_tracking" value="1">
                                            <label for="notify_tracking" class="mb-0">
                                                <i class="fas fa-envelope"></i> Notify customer of tracking
                                            </label>
                                        </div>
                                        <button type="submit" name="update_tracking" class="btn btn-outline-info w-100">
                                            <i class="fas fa-truck"></i> Update Tracking
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Actions Card -->
                            <div class="card no-print">
                                <div class="card-header">
                                    <h5 class="mb-0">Actions</h5>
                                </div>
                                <div class="card-body">
                                    <button class="btn btn-outline-secondary w-100 mb-2" onclick="window.print();">
                                        <i class="fas fa-print"></i> Print Order
                                    </button>
                                    <a href="mailto:<?php echo $order['customer_email']; ?>" class="btn btn-outline-info w-100 mb-2">
                                        <i class="fas fa-envelope"></i> Email Customer
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>