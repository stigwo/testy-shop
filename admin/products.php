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

// Handle product deletion
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = $_GET['delete'];
    
    // Delete product images first
    $conn->query("DELETE FROM product_images WHERE product_id = $product_id");
    
    // Delete product variants
    $conn->query("DELETE FROM product_variants WHERE product_id = $product_id");
    
    // Delete product
    $conn->query("DELETE FROM products WHERE id = $product_id");
    
    header("Location: products.php?msg=deleted");
    exit;
}

// Get all products
$products = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Products - Admin</title>
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
        .product-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
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
        .table-actions {
            white-space: nowrap;
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
                        <a class="nav-link active" href="products.php">
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
                    <h4 class="mb-0">Product Management</h4>
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
                        <h2>Products</h2>
                        <a href="product_edit.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Product
                        </a>
                    </div>

                    <?php if(isset($_GET['msg'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php 
                                if($_GET['msg'] == 'deleted') echo "Product deleted successfully!";
                                if($_GET['msg'] == 'saved') echo "Product saved successfully!";
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">All Products</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Image</th>
                                            <th>Product Name</th>
                                            <th>Images Count</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($products && $products->num_rows > 0): ?>
                                            <?php while($product = $products->fetch_assoc()): 
                                                $img_count = $conn->query("SELECT COUNT(*) as cnt FROM product_images WHERE product_id = {$product['id']}")->fetch_assoc();
                                                $first_img = $conn->query("SELECT image_path FROM product_images WHERE product_id = {$product['id']} LIMIT 1")->fetch_assoc();
                                            ?>
                                                <tr>
                                                    <td><?php echo $product['id']; ?></td>
                                                    <td>
                                                        <?php if($first_img): ?>
                                                            <img src="/shop/images/<?php echo $first_img['image_path']; ?>" class="product-thumb" onerror="this.src='https://placehold.co/60x60'">
                                                        <?php elseif(!empty($product['featured_image'])): ?>
                                                            <img src="/shop/images/<?php echo $product['featured_image']; ?>" class="product-thumb" onerror="this.src='https://placehold.co/60x60'">
                                                        <?php else: ?>
                                                            <span class="text-muted">No image</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                                    <td class="text-center"><?php echo $img_count['cnt']; ?></td>
                                                    <td><?php echo date('Y-m-d', strtotime($product['created_at'])); ?></td>
                                                    <td class="table-actions">
                                                        <a href="product_edit.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </a>
                                                        <a href="product_images.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-images"></i> Images
                                                        </a>
                                                        <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this product? This will also delete all images and variants.')">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <i class="fas fa-box-open fa-3x text-muted mb-3 d-block"></i>
                                                    <p>No products found.</p>
                                                    <a href="product_edit.php" class="btn btn-primary">Add Your First Product</a>
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