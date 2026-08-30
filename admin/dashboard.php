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

// Get statistics
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];

// Check if orders table exists
$total_orders = 0;
$order_check = $conn->query("SHOW TABLES LIKE 'orders'");
if($order_check->num_rows > 0) {
    $total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
}

// Check if support tickets table exists
$total_tickets = 0;
$ticket_check = $conn->query("SHOW TABLES LIKE 'support_tickets'");
if($ticket_check->num_rows > 0) {
    $total_tickets = $conn->query("SELECT COUNT(*) as count FROM support_tickets WHERE status != 'closed'")->fetch_assoc()['count'];
}

// Check if pages table exists
$total_pages = 0;
$pages_check = $conn->query("SHOW TABLES LIKE 'pages'");
if($pages_check->num_rows > 0) {
    $total_pages = $conn->query("SELECT COUNT(*) as count FROM pages")->fetch_assoc()['count'];
}

// Get recent orders
$recent_orders = null;
if($order_check->num_rows > 0) {
    $recent_orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
}

// Get recent products
$recent_products = $conn->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 5");

// Get total images
$total_images = $conn->query("SELECT COUNT(*) as count FROM product_images")->fetch_assoc()['count'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Revobake</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
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
            width: 20px;
        }
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 3rem;
            opacity: 0.3;
            position: absolute;
            right: 20px;
            bottom: 20px;
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .navbar-top {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px 0;
        }
        .product-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }
        .badge-pending { background-color: #ffc107; }
        .badge-processing { background-color: #17a2b8; }
        .badge-completed { background-color: #28a745; }
        .badge-cancelled { background-color: #dc3545; }
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
                margin-bottom: 20px;
            }
            .stat-card {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation -->
            <div class="col-md-3 col-lg-2 p-0">
                <div class="sidebar p-3">
                    <h4 class="text-white text-center mb-4">
                        <i class="fas fa-crown"></i> Admin Panel
                    </h4>
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a class="nav-link" href="products.php">
                            <i class="fas fa-box"></i> Products
                        </a>
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-shopping-cart"></i> Orders
                            <?php if($total_orders > 0): ?>
                                <span class="badge bg-danger float-end"><?php echo $total_orders; ?></span>
                            <?php endif; ?>
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
                <!-- Top Navbar -->
                <div class="navbar-top px-4 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Dashboard</h4>
                    <div>
                        <span class="text-muted me-3">
                            <i class="fas fa-user"></i> <?php echo $_SESSION['admin_username'] ?? 'Admin'; ?>
                        </span>
                        <a href="logout.php" class="btn btn-sm btn-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>

                <!-- Dashboard Content -->
                <div class="main-content p-4">
                    <!-- Welcome Message -->
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <strong>Welcome back, <?php echo $_SESSION['admin_username'] ?? 'Admin'; ?>!</strong> Here's what's happening with your store today.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stat-card" onclick="window.location.href='products.php'">
                                <div class="card-body">
                                    <h5 class="card-title text-muted">Total Products</h5>
                                    <h2 class="mb-0"><?php echo $total_products; ?></h2>
                                    <i class="fas fa-box stat-icon"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card" onclick="window.location.href='orders.php'">
                                <div class="card-body">
                                    <h5 class="card-title text-muted">Total Orders</h5>
                                    <h2 class="mb-0"><?php echo $total_orders; ?></h2>
                                    <i class="fas fa-shopping-cart stat-icon"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card" onclick="window.location.href='support_tickets.php'">
                                <div class="card-body">
                                    <h5 class="card-title text-muted">Open Tickets</h5>
                                    <h2 class="mb-0"><?php echo $total_tickets; ?></h2>
                                    <i class="fas fa-ticket-alt stat-icon"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card" onclick="window.location.href='pages.php'">
                                <div class="card-body">
                                    <h5 class="card-title text-muted">Total Pages</h5>
                                    <h2 class="mb-0"><?php echo $total_pages; ?></h2>
                                    <i class="fas fa-file-alt stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <a href="product_edit.php" class="btn btn-primary w-100">
                                                <i class="fas fa-plus"></i> Add New Product
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="page_edit.php" class="btn btn-success w-100">
                                                <i class="fas fa-file-alt"></i> Add New Page
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="orders.php" class="btn btn-info w-100 text-white">
                                                <i class="fas fa-truck"></i> Manage Orders
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="support_tickets.php" class="btn btn-warning w-100">
                                                <i class="fas fa-headset"></i> View Tickets
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Products -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Recent Products</h5>
                                    <a href="products.php" class="btn btn-sm btn-primary">View All Products</a>
                                </div>
                                <div class="card-body">
                                    <?php if($recent_products && $recent_products->num_rows > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Image</th>
                                                        <th>Product Name</th>
                                                        <th>Created</th>
                                                        <th>Images</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while($product = $recent_products->fetch_assoc()): 
                                                        $img_count = $conn->query("SELECT COUNT(*) as cnt FROM product_images WHERE product_id = {$product['id']}")->fetch_assoc();
                                                        $first_img = $conn->query("SELECT image_path FROM product_images WHERE product_id = {$product['id']} LIMIT 1")->fetch_assoc();
                                                    ?>
                                                        <tr>
                                                            <td><?php echo $product['id']; ?></td>
                                                            <td>
                                                                <?php if($first_img): ?>
                                                                    <img src="/shop/images/<?php echo $first_img['image_path']; ?>" class="product-thumb" onerror="this.src='https://placehold.co/50x50'">
                                                                <?php else: ?>
                                                                    <span class="text-muted">No image</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                            <td><?php echo date('Y-m-d', strtotime($product['created_at'])); ?></td>
                                                            <td><?php echo $img_count['cnt']; ?> images</td>
                                                            <td>
                                                                <a href="product_edit.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="product_images.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-info">
                                                                    <i class="fas fa-images"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info text-center">
                                            <i class="fas fa-box-open fa-2x mb-2"></i>
                                            <p>No products found. <a href="product_edit.php">Add your first product</a></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Orders -->
                    <?php if($recent_orders && $recent_orders->num_rows > 0): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Recent Orders</h5>
                                    <a href="orders.php" class="btn btn-sm btn-primary">View All Orders</a>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Order #</th>
                                                    <th>Customer</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                    <th>Date</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($order = $recent_orders->fetch_assoc()): ?>
                                                <tr>
                                                    <td><strong>#<?php echo $order['order_number'] ?? $order['id']; ?></strong></td>
                                                    <td><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></td>
                                                    <td>$<?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $order['order_status'] == 'delivered' ? 'success' : ($order['order_status'] == 'cancelled' ? 'danger' : 'warning'); ?>">
                                                            <?php echo ucfirst($order['order_status'] ?? 'Pending'); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                    <td>
                                                        <a href="order_view.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">View</a>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
                  
              