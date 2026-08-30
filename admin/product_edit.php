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

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = ($product_id > 0);
$product = null;

if($is_edit) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if(!$product) {
        die("Product not found.");
    }
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $short_description = $conn->real_escape_string($_POST['short_description']);
    $detailed_description = $conn->real_escape_string($_POST['detailed_description']);
    $technical_specs = $conn->real_escape_string($_POST['technical_specs']);
    $featured_image = $conn->real_escape_string($_POST['featured_image']);
    
    if($is_edit) {
        $stmt = $conn->prepare("UPDATE products SET name=?, short_description=?, detailed_description=?, technical_specs=?, featured_image=? WHERE id=?");
        $stmt->bind_param("sssssi", $name, $short_description, $detailed_description, $technical_specs, $featured_image, $product_id);
        $stmt->execute();
        $msg = "Product updated successfully!";
    } else {
        $stmt = $conn->prepare("INSERT INTO products (name, short_description, detailed_description, technical_specs, featured_image) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $short_description, $detailed_description, $technical_specs, $featured_image);
        $stmt->execute();
        $product_id = $conn->insert_id;
        $msg = "Product added successfully!";
    }
    
    header("Location: product_edit.php?id=$product_id&msg=" . urlencode($msg));
    exit;
}

// Get existing images for this product
$images = $conn->query("SELECT * FROM product_images WHERE product_id = $product_id ORDER BY sort_order");
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $is_edit ? 'Edit' : 'Add'; ?> Product - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- CKEditor 5 (Free, No API Key) -->
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
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
        .image-list {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
        }
        .image-item {
            position: relative;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
        }
        .image-item img {
            max-width: 100px;
            max-height: 100px;
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
        .ck-editor__editable {
            min-height: 300px;
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
                    <h4 class="mb-0"><?php echo $is_edit ? 'Edit' : 'Add'; ?> Product</h4>
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
                    <?php if(isset($_GET['msg'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="mb-0">Product Information</h4>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="productForm">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Product Name *</label>
                                            <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Short Description</label>
                                            <textarea name="short_description" class="form-control" rows="3"><?php echo htmlspecialchars($product['short_description'] ?? ''); ?></textarea>
                                            <small class="text-muted">Brief description shown in product listings</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Detailed Description</label>
                                            <textarea name="detailed_description" id="detailed_description" rows="10"><?php echo htmlspecialchars($product['detailed_description'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Technical Specifications</label>
                                            <textarea name="technical_specs" id="technical_specs" rows="8"><?php echo htmlspecialchars($product['technical_specs'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Featured Image Filename</label>
                                            <input type="text" name="featured_image" class="form-control" value="<?php echo htmlspecialchars($product['featured_image'] ?? ''); ?>">
                                            <small class="text-muted">Enter the filename (e.g., mixer1.jpg). Image must be in /shop/images/ folder</small>
                                        </div>
                                        
                                        <button type="submit" name="save_product" class="btn btn-primary">
                                            <i class="fas fa-save"></i> <?php echo $is_edit ? 'Update' : 'Create'; ?> Product
                                        </button>
                                        
                                        <?php if($is_edit): ?>
                                            <a href="products.php" class="btn btn-secondary">Cancel</a>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <?php if($is_edit): ?>
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Product Images</h5>
                                    </div>
                                    <div class="card-body">
                                        <a href="product_images.php?id=<?php echo $product_id; ?>" class="btn btn-info w-100 mb-3">
                                            <i class="fas fa-images"></i> Manage Multiple Images
                                        </a>
                                        
                                        <?php if($images && $images->num_rows > 0): ?>
                                            <h6>Current Images:</h6>
                                            <div class="image-list">
                                                <?php while($img = $images->fetch_assoc()): ?>
                                                    <div class="image-item">
                                                        <img src="/shop/images/<?php echo $img['image_path']; ?>" onerror="this.src='https://placehold.co/100x100'">
                                                        <small class="d-block">Order: <?php echo $img['sort_order']; ?></small>
                                                    </div>
                                                <?php endwhile; ?>
                                            </div>
                                            <div class="mt-3">
                                                <small class="text-muted">
                                                    <i class="fas fa-info-circle"></i> 
                                                    <a href="product_images.php?id=<?php echo $product_id; ?>">Click here</a> to add more images or reorder
                                                </small>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-warning text-center">
                                                <i class="fas fa-image fa-2x mb-2"></i>
                                                <p>No images yet</p>
                                                <a href="product_images.php?id=<?php echo $product_id; ?>" class="btn btn-sm btn-primary">
                                                    Upload Images
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Tips</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="small">
                                        <li>Use the editor to format descriptions</li>
                                        <li>Upload images via the "Manage Multiple Images" button</li>
                                        <li>Images go in <code>/shop/images/</code> folder</li>
                                        <li>Supported formats: JPG, PNG, GIF, WebP</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Initialize CKEditor for Detailed Description
        ClassicEditor
            .create(document.querySelector('#detailed_description'), {
                toolbar: {
                    items: [
                        'heading', '|',
                        'bold', 'italic', 'underline', 'strikethrough', '|',
                        'bulletedList', 'numberedList', '|',
                        'alignment', '|',
                        'undo', 'redo'
                    ]
                }
            })
            .catch(error => {
                console.error(error);
            });
        
        // Initialize CKEditor for Technical Specifications
        ClassicEditor
            .create(document.querySelector('#technical_specs'), {
                toolbar: {
                    items: [
                        'bold', 'italic', 'underline', '|',
                        'bulletedList', 'numberedList', '|',
                        'undo', 'redo'
                    ]
                }
            })
            .catch(error => {
                console.error(error);
            });
    </script>
</body>
</html>