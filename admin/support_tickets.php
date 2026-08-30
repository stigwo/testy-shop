<?php
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
if($pages_check->num_rows > 0) {
    $total_pages = $conn->query("SELECT COUNT(*) as count FROM pages")->fetch_assoc()['count'];
}

// Get total tickets count
$total_tickets = 0;
$ticket_check = $conn->query("SHOW TABLES LIKE 'support_tickets'");
if($ticket_check->num_rows > 0) {
    $total_tickets = $conn->query("SELECT COUNT(*) as count FROM support_tickets WHERE status != 'closed'")->fetch_assoc()['count'];
}

// Get statistics
$total_customers = $conn->query("SELECT COUNT(DISTINCT customer_email) as count FROM orders")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid' OR order_status = 'delivered'")->fetch_assoc()['total'] ?? 0;

// Get customers
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = "";
if($search) {
    $where_clause = "WHERE customer_name LIKE '%$search%' OR customer_email LIKE '%$search%'";
}
$customers_result = $conn->query("SELECT * FROM orders $where_clause GROUP BY customer_email ORDER BY created_at DESC");

// Get customer order counts
$customer_orders = [];
$order_counts = $conn->query("SELECT customer_email, COUNT(*) as order_count, SUM(total_amount) as total_spent FROM orders GROUP BY customer_email");
while($row = $order_counts->fetch_assoc()) {
    $customer_orders[$row['customer_email']] = [
        'order_count' => $row['order_count'],
        'total_spent' => $row['total_spent']
    ];
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
                <div class="p-4">
                    <h2 class="mb-4">Customer Management</h2>

                    <!-- Statistics -->
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
                                        <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
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
                                                
                                                $dates = $conn->query("SELECT MIN(created_at) as first, MAX(created_at) as last FROM orders WHERE customer_email = '{$order['customer_email']}'")->fetch_assoc();
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
                                                    <i class="fas fa-envelope text-muted"></i> <?php echo htmlspecialchars($order['customer_email']); ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary"><?php echo $stats['order_count']; ?> orders</span>
                                                </td>
                                                <td>$<?php echo number_format($stats['total_spent'], 2); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($dates['first'])); ?></td>
                                                <td>
                                                    <a href="mailto:<?php echo $order['customer_email']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-envelope"></i>
                                                    </a>
                                                    <a href="orders.php?customer=<?php echo urlencode($order['customer_email']); ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-shopping-cart"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No customers found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
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