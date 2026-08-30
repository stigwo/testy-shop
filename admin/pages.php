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

// Get total tickets count for badge
$total_tickets = 0;
$ticket_check = $conn->query("SHOW TABLES LIKE 'support_tickets'");
if($ticket_check && $ticket_check->num_rows > 0) {
    $tickets_result = $conn->query("SELECT COUNT(*) as count FROM support_tickets WHERE status != 'closed'");
    if($tickets_result) {
        $total_tickets = $tickets_result->fetch_assoc()['count'];
    }
}

// Handle page deletion
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $page_id = $_GET['delete'];
    $conn->query("DELETE FROM pages WHERE id = $page_id");
    header("Location: pages.php?msg=deleted");
    exit;
}

// Handle page status toggle
if(isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $page_id = $_GET['toggle'];
    $page = $conn->query("SELECT status FROM pages WHERE id = $page_id")->fetch_assoc();
    $new_status = $page['status'] == 'published' ? 'draft' : 'published';
    $conn->query("UPDATE pages SET status = '$new_status' WHERE id = $page_id");
    header("Location: pages.php?msg=status_updated");
    exit;
}

// Get all pages
$pages = $conn->query("SELECT * FROM pages ORDER BY sort_order, created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Page Management - Admin</title>
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
        .navbar-top {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px 0;
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .page-status {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .status-published { background-color: #28a745; }
        .status-draft { background-color: #ffc107; }
        .table-actions {
            white-space: nowrap;
        }
        .sort-input {
            width: 70px;
            text-align: center;
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
                        <a class="nav-link" href="customers.php">
                            <i class="fas fa-users"></i> Customers
                        </a>
                        <a class="nav-link" href="support_tickets.php">
                            <i class="fas fa-ticket-alt"></i> Support Tickets
                            <?php if($total_tickets > 0): ?>
                                <span class="badge bg-warning float-end"><?php echo $total_tickets; ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link active" href="pages.php">
                            <i class="fas fa-file-alt"></i> Pages
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
                    <h4 class="mb-0">Page Management</h4>
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Pages</h2>
                        <a href="page_edit.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Page
                        </a>
                    </div>

                    <?php if(isset($_GET['msg'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php 
                                if($_GET['msg'] == 'deleted') echo "Page deleted successfully!";
                                if($_GET['msg'] == 'status_updated') echo "Page status updated!";
                                if($_GET['msg'] == 'saved') echo "Page saved successfully!";
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">All Pages</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Slug</th>
                                            <th>Status</th>
                                            <th>Sort Order</th>
                                            <th>Last Modified</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($pages && $pages->num_rows > 0): ?>
                                            <?php while($page = $pages->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $page['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($page['title']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars(substr($page['meta_description'] ?? '', 0, 60)); ?></small>
                                                </td>
                                                <td>
                                                    <code><?php echo htmlspecialchars($page['slug']); ?></code>
                                                    <br>
                                                    <a href="../page.php?slug=<?php echo $page['slug']; ?>" target="_blank" class="small">
                                                        <i class="fas fa-external-link-alt"></i> View
                                                    </a>
                                                </td>
                                                <td>
                                                    <span class="page-status status-<?php echo $page['status']; ?>"></span>
                                                    <?php echo ucfirst($page['status']); ?>
                                                </td>
                                                <td>
                                                    <form method="POST" action="update_page_order.php" class="d-inline">
                                                        <input type="hidden" name="page_id" value="<?php echo $page['id']; ?>">
                                                        <input type="number" name="sort_order" value="<?php echo $page['sort_order']; ?>" class="form-control form-control-sm sort-input d-inline-block">
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Update</button>
                                                    </form>
                                                </td>
                                                <td>
                                                    <?php echo date('M d, Y', strtotime($page['updated_at'] ?? $page['created_at'])); ?>
                                                </td>
                                                <td class="table-actions">
                                                    <a href="page_edit.php?id=<?php echo $page['id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <a href="?toggle=<?php echo $page['id']; ?>" class="btn btn-sm btn-info" onclick="return confirm('Toggle page status?')">
                                                        <i class="fas fa-<?php echo $page['status'] == 'published' ? 'eye-slash' : 'eye'; ?>"></i>
                                                    </a>
                                                    <a href="?delete=<?php echo $page['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this page? This action cannot be undone.')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <i class="fas fa-file-alt fa-3x text-muted mb-3 d-block"></i>
                                                    <p>No pages created yet.</p>
                                                    <a href="page_edit.php" class="btn btn-primary">Create Your First Page</a>
                                                </td>
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