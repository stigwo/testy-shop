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

// Get statistics
$total_customers = 0;
$customers_result = $conn->query("SELECT COUNT(DISTINCT customer_email) as count FROM orders");
if($customers_result) {
    $total_customers = $customers_result->fetch_assoc()['count'];
}

$total_orders = 0;
$orders_result = $conn->query("SELECT COUNT(*) as count FROM orders");
if($orders_result) {
    $total_orders = $orders_result->fetch_assoc()['count'];
}

$total_revenue = 0;
$revenue_result = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid' OR order_status = 'delivered'");
if($revenue_result) {
    $total_revenue = $revenue_result->fetch_assoc()['total'] ?? 0;
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = "";
if($search) {
    $where_clause = "WHERE customer_name LIKE '%$search%' OR customer_email LIKE '%$search%' OR customer_phone LIKE '%$search%'";
}

// Get customers
$customers_query = "SELECT * FROM orders $where_clause GROUP BY customer_email ORDER BY created_at DESC";
$customers_result = $conn->query($customers_query);

// Get customer order counts
$customer_orders = [];
$order_counts = $conn->query("SELECT customer_email, COUNT(*) as order_count, SUM(total_amount) as total_spent FROM orders GROUP BY customer_email");
if($order_counts) {
    while($row = $order_counts->fetch_assoc()) {
        $customer_orders[$row['customer_email']] = [
            'order_count' => $row['order_count'],
            'total_spent' => $row['total_spent']
        ];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Customer Management - Admin</title>
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
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.3;
            position: absolute;
            right: 20px;
            bottom: 20px;
        }
        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #007bff;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
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
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-shopping-cart"></i> Orders
                        </a>
                        <a class="nav-link active" href="customers.php">
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
                <div class="navbar-top px-4 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Customer Management</h4>
                    <div>
                        <span class="text-muted me-3">
                            <i class="fas fa-user"></i> <?php echo $_SESSION['admin_username'] ?? 'Admin'; ?>
                        </span>
                        <a href="logout.php" class="btn btn-sm btn-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>

                <div class="main-content p-4">
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <h5 class="card-title text-muted">Total Customers</h5>
                                    <h2 class="mb-0"><?php echo $total_customers; ?></h2>
                                    <i class="fas fa-users stat-icon"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <h5 class="card-title text-muted">Total Orders</h5>
                                    <h2 class="mb-0"><?php echo $total_orders; ?></h2>
                                    <i class="fas fa-shopping-cart stat-icon"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <h5 class="card-title text-muted">Total Revenue</h5>
                                    <h2 class="mb-0">$<?php echo number_format($total_revenue, 2); ?></h2>
                                    <i class="fas fa-dollar-sign stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-10">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" name="search" class="form-control" placeholder="Search by name, email, or phone..." value="<?php echo htmlspecialchars($search); ?>">
                                        <button type="submit" class="btn btn-primary">Search</button>
                                        <?php if($search): ?>
                                            <a href="customers.php" class="btn btn-secondary">Clear</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Customers Table -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">Customer List</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Customer</th>
                                            <th>Contact</th>
                                            <th>Orders</th>
                                            <th>Total Spent</th>
                                            <th>First Order</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $customers_displayed = [];
                                        if($customers_result && $customers_result->num_rows > 0):
                                            while($order = $customers_result->fetch_assoc()):
                                                if(in_array($order['customer_email'], $customers_displayed)) continue;
                                                $customers_displayed[] = $order['customer_email'];
                                                
                                                $dates = $conn->query("SELECT MIN(created_at) as first, MAX(created_at) as last FROM orders WHERE customer_email = '{$order['customer_email']}'");
                                                $dates_row = $dates ? $dates->fetch_assoc() : ['first' => $order['created_at'], 'last' => $order['created_at']];
                                                $stats = $customer_orders[$order['customer_email']] ?? ['order_count' => 0, 'total_spent' => 0];
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="customer-avatar me-2">
                                                            <?php echo strtoupper(substr($order['customer_name'], 0, 1)); ?>
                                                        </div>
                                                        <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                                    </div>
                                                </td>
                                                <td>
                                                    <i class="fas fa-envelope text-muted"></i> <?php echo htmlspecialchars($order['customer_email']); ?><br>
                                                    <?php if(!empty($order['customer_phone'])): ?>
                                                        <i class="fas fa-phone text-muted"></i> <?php echo htmlspecialchars($order['customer_phone']); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary"><?php echo $stats['order_count']; ?> orders</span>
                                                </td>
                                                <td>$<?php echo number_format($stats['total_spent'], 2); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($dates_row['first'])); ?></td>
                                                <td>
                                                    <a href="mailto:<?php echo $order['customer_email']; ?>" class="btn btn-sm btn-info" title="Email Customer">
                                                        <i class="fas fa-envelope"></i>
                                                    </a>
                                                    <a href="orders.php?customer=<?php echo urlencode($order['customer_email']); ?>" class="btn btn-sm btn-primary" title="View Orders">
                                                        <i class="fas fa-shopping-cart"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
                                                    <p>No customers found.</p>
                                                    <?php if($search): ?>
                                                        <a href="customers.php" class="btn btn-primary">Clear Search</a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Export Section -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-download text-primary"></i>
                                            <strong>Export Customer Data</strong>
                                            <small class="text-muted">Download customer list as CSV</small>
                                        </div>
                                        <a href="export_customers.php" class="btn btn-success">
                                            <i class="fas fa-file-csv"></i> Export to CSV
                                        </a>
                                    </div>
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