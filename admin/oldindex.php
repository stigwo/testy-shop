<?php 
require_once '../functions.php';

// Check if user is logged in FIRST
if(!isAdmin()) { 
    header("Location: login.php"); 
    exit; 
}

// Now process all GET/POST requests (after login check)
$conn = $GLOBALS['conn'];

// Handle order status update
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $status = $_POST['status'];
    $tracking = $_POST['tracking'];
    $order_id = $_POST['order_id'];
    
    $stmt = $conn->prepare("UPDATE orders SET order_status = ?, tracking_number = ? WHERE id = ?");
    $stmt->bind_param("ssi", $status, $tracking, $order_id);
    $stmt->execute();
    
    if($status == 'shipped') {
        $order = $conn->query("SELECT * FROM orders WHERE id = $order_id")->fetch_assoc();
        $email_body = "<h2>Your order #{$order['order_number']} has been shipped!</h2><p>Tracking: {$tracking}</p>";
        sendEmail($order['customer_email'], "Order Shipped #{$order['order_number']}", $email_body);
    }
    
    header("Location: index.php");
    exit;
}

// Handle ticket reply
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_ticket'])) {
    $reply = $_POST['reply'];
    $ticket_id = $_POST['ticket_id'];
    
    $stmt = $conn->prepare("UPDATE support_tickets SET admin_reply = ?, status = 'closed' WHERE id = ?");
    $stmt->bind_param("si", $reply, $ticket_id);
    $stmt->execute();
    
    $ticket = $conn->query("SELECT * FROM support_tickets WHERE id = $ticket_id")->fetch_assoc();
    $email_body = "<h3>Support Ticket Reply</h3><p>" . nl2br(htmlspecialchars($reply)) . "</p>";
    sendEmail($ticket['customer_email'], "Support Ticket #{$ticket_id} - Reply", $email_body);
    
    header("Location: index.php");
    exit;
}

// Handle product deletion
if(isset($_GET['delete_product'])) {
    $id = (int)$_GET['delete_product'];
    $conn->query("DELETE FROM products WHERE id = $id");
    header("Location: index.php#products");
    exit;
}

// Handle variant deletion
if(isset($_GET['delete_variant'])) {
    $id = (int)$_GET['delete_variant'];
    $conn->query("DELETE FROM product_variants WHERE id = $id");
    header("Location: index.php#products");
    exit;
}

// Handle page deletion
if(isset($_GET['delete_page'])) {
    $id = (int)$_GET['delete_page'];
    $conn->query("DELETE FROM pages WHERE id = $id");
    header("Location: index.php#pages");
    exit;
}

// Handle product save
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $name = $_POST['name'];
    $short_desc = $_POST['short_description'];
    $detailed_desc = $_POST['detailed_description'];
    $tech_specs = $_POST['technical_specs'];
    
    if(isset($_POST['product_id']) && $_POST['product_id'] > 0) {
        // Update existing product
        $stmt = $conn->prepare("UPDATE products SET name=?, short_description=?, detailed_description=?, technical_specs=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $short_desc, $detailed_desc, $tech_specs, $_POST['product_id']);
        $stmt->execute();
        $product_id = $_POST['product_id'];
    } else {
        // Insert new product
        $stmt = $conn->prepare("INSERT INTO products (name, short_description, detailed_description, technical_specs) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $short_desc, $detailed_desc, $tech_specs);
        $stmt->execute();
        $product_id = $conn->insert_id;
    }
    
    // Handle image upload
    if(isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = time() . '_' . basename($_FILES['featured_image']['name']);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            move_uploaded_file($_FILES['featured_image']['tmp_name'], "../images/" . $filename);
            $conn->query("UPDATE products SET featured_image = '$filename' WHERE id = $product_id");
        }
    }
    
    header("Location: index.php#products");
    exit;
}

// Handle variant add
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_variant'])) {
    $product_id = (int)$_POST['product_id'];
    $variant_name = $_POST['variant_name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $sku = $_POST['sku'];
    
    $stmt = $conn->prepare("INSERT INTO product_variants (product_id, variant_name, price, stock, sku) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isdss", $product_id, $variant_name, $price, $stock, $sku);
    $stmt->execute();
    header("Location: index.php#products");
    exit;
}

// Handle page save
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_page'])) {
    $title = $_POST['title'];
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]/', '-', $_POST['slug']), '-'));
    $content = $_POST['content'];
    $meta_description = $_POST['meta_description'];
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    
    if(isset($_POST['page_id']) && $_POST['page_id'] > 0) {
        $stmt = $conn->prepare("UPDATE pages SET title=?, slug=?, content=?, meta_description=?, is_published=? WHERE id=?");
        $stmt->bind_param("ssssii", $title, $slug, $content, $meta_description, $is_published, $_POST['page_id']);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO pages (title, slug, content, meta_description, is_published) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $title, $slug, $content, $meta_description, $is_published);
        $stmt->execute();
    }
    header("Location: index.php#pages");
    exit;
}

// Get product for editing (BEFORE any HTML output)
$edit_product = null;
$edit_product_variants = null;
$edit_product_images = null;

if(isset($_GET['edit_product'])) {
    $id = (int)$_GET['edit_product'];
    $edit_product = $conn->query("SELECT * FROM products WHERE id = $id")->fetch_assoc();
    if($edit_product) {
        $edit_product_variants = $conn->query("SELECT * FROM product_variants WHERE product_id = $id");
        $edit_product_images = $conn->query("SELECT * FROM product_images WHERE product_id = $id ORDER BY sort_order");
    }
}

// Get page for editing
$edit_page = null;
if(isset($_GET['edit_page'])) {
    $id = (int)$_GET['edit_page'];
    $edit_page = $conn->query("SELECT * FROM pages WHERE id = $id")->fetch_assoc();
}

// Determine active tab based on URL parameters
$active_tab = 'orders';
if(isset($_GET['edit_product']) || isset($_GET['delete_product'])) $active_tab = 'products';
if(isset($_GET['edit_page']) || isset($_GET['delete_page'])) $active_tab = 'pages';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - Spiral Mixers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    <style>
        .product-card, .page-card { transition: transform 0.2s; margin-bottom: 20px; }
        .product-card:hover, .page-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .variant-item { border-left: 3px solid #ffc107; margin-bottom: 10px; }
        .table-actions { white-space: nowrap; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark sticky-top">
        <div class="container-fluid">
            <span class="navbar-brand"><strong>⚙️ Admin Panel</strong> - Spiral Mixers</span>
            <div class="d-flex">
                <a href="../index.php" class="btn btn-outline-light me-2" target="_blank">🛒 View Shop</a>
                <a href="logout.php" class="btn btn-danger">🚪 Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid mt-3">
        <ul class="nav nav-tabs" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab == 'orders' ? 'active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab">
                    📦 Orders <span class="badge bg-danger" id="newOrdersCount"></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab == 'products' ? 'active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab">
                    🏷️ Products
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab == 'pages' ? 'active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#pages" type="button" role="tab">
                    📄 Pages
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tickets" type="button" role="tab">
                    🎫 Support Tickets <span class="badge bg-warning" id="openTicketsCount"></span>
                </button>
            </li>
        </ul>
        
        <div class="tab-content mt-3">
            
            <!-- ORDERS TAB -->
            <div class="tab-pane fade <?php echo $active_tab == 'orders' ? 'show active' : ''; ?>" id="orders" role="tabpanel">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white"><h5 class="mb-0">📋 All Orders</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">...</thead>
                                <tbody>
                                    <?php 
                                    $orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
                                    while($order = $orders->fetch_assoc()): ?>
                                    <tr>...</tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- PRODUCTS TAB -->
            <div class="tab-pane fade <?php echo $active_tab == 'products' ? 'show active' : ''; ?>" id="products" role="tabpanel">
                <div class="row">
                    <!-- Add/Edit Product Form -->
                    <div class="col-md-4">
                        <div class="card shadow mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><?php echo $edit_product ? '✏️ Edit Product' : '➕ Add New Product'; ?></h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <?php if($edit_product): ?>
                                        <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                                    <?php endif; ?>
                                    <div class="mb-3">
                                        <label class="form-label">Product Name *</label>
                                        <input type="text" name="name" class="form-control" value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Short Description</label>
                                        <textarea name="short_description" class="form-control" rows="2"><?php echo $edit_product ? htmlspecialchars($edit_product['short_description']) : ''; ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Detailed Description</label>
                                        <textarea name="detailed_description" class="form-control summernote-sm" rows="3"><?php echo $edit_product ? htmlspecialchars($edit_product['detailed_description']) : ''; ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Technical Specifications</label>
                                        <textarea name="technical_specs" class="form-control" rows="3"><?php echo $edit_product ? htmlspecialchars($edit_product['technical_specs']) : ''; ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Featured Image</label>
                                        <?php if($edit_product && $edit_product['featured_image']): ?>
                                            <div class="mb-2"><img src="../images/<?php echo $edit_product['featured_image']; ?>" class="img-thumbnail" style="max-height: 80px;"></div>
                                        <?php endif; ?>
                                        <input type="file" name="featured_image" class="form-control" accept="image/*">
                                    </div>
                                    <button type="submit" name="save_product" class="btn btn-success w-100">
                                        <?php echo $edit_product ? 'Update Product' : 'Save Product'; ?>
                                    </button>
                                    <?php if($edit_product): ?>
                                        <a href="index.php#products" class="btn btn-secondary w-100 mt-2">Cancel Edit</a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Add Variant Form -->
                        <?php if($edit_product): ?>
                        <div class="card shadow">
                            <div class="card-header bg-warning"><h5 class="mb-0">🔧 Add Variant</h5></div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                                    <div class="mb-2"><input type="text" name="variant_name" class="form-control" placeholder="Variant name (e.g., 20kg capacity)" required></div>
                                    <div class="row">
                                        <div class="col-md-6 mb-2"><input type="number" step="0.01" name="price" class="form-control" placeholder="Price" required></div>
                                        <div class="col-md-6 mb-2"><input type="number" name="stock" class="form-control" placeholder="Stock quantity"></div>
                                    </div>
                                    <div class="mb-2"><input type="text" name="sku" class="form-control" placeholder="SKU (optional)"></div>
                                    <button type="submit" name="add_variant" class="btn btn-warning w-100">Add Variant</button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Products List -->
                    <div class="col-md-8">
                        <div class="card shadow">
                            <div class="card-header bg-primary text-white"><h5 class="mb-0">📋 All Products</h5></div>
                            <div class="card-body">
                                <?php
                                $products = $conn->query("SELECT * FROM products ORDER BY id DESC");
                                while($product = $products->fetch_assoc()):
                                    $variants = $conn->query("SELECT * FROM product_variants WHERE product_id = {$product['id']}");
                                ?>
                                <div class="card product-card mb-3">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-2">
                                                <?php if($product['featured_image']): ?>
                                                    <img src="../images/<?php echo $product['featured_image']; ?>" class="img-fluid rounded" style="max-height: 80px;">
                                                <?php else: ?>
                                                    <div class="bg-secondary text-white p-2 text-center rounded">No Image</div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-10">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                                                        <p class="text-muted small"><?php echo substr(htmlspecialchars($product['short_description']), 0, 100); ?></p>
                                                        <span class="badge bg-info"><?php echo $variants->num_rows; ?> variants</span>
                                                    </div>
                                                    <div>
                                                        <a href="?edit_product=<?php echo $product['id']; ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
                                                        <a href="?delete_product=<?php echo $product['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product?')">🗑️ Delete</a>
                                                    </div>
                                                </div>
                                                
                                                <?php if($variants->num_rows > 0): ?>
                                                <div class="mt-2">
                                                    <small><strong>Variants:</strong></small>
                                                    <div class="row mt-1">
                                                        <?php while($variant = $variants->fetch_assoc()): ?>
                                                        <div class="col-md-4 mb-1">
                                                            <div class="variant-item p-1 ps-2">
                                                                <small><?php echo htmlspecialchars($variant['variant_name']); ?> - $<?php echo number_format($variant['price'], 2); ?> (Stock: <?php echo $variant['stock']; ?>)</small>
                                                                <a href="?delete_variant=<?php echo $variant['id']; ?>" class="text-danger ms-2" onclick="return confirm('Delete this variant?')">✖</a>
                                                            </div>
                                                        </div>
                                                        <?php endwhile; ?>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- PAGES TAB -->
            <div class="tab-pane fade <?php echo $active_tab == 'pages' ? 'show active' : ''; ?>" id="pages" role="tabpanel">
                <div class="row">
                    <div class="col-md-5">
                        <div class="card shadow mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><?php echo $edit_page ? '✏️ Edit Page' : '➕ Create New Page'; ?></h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <?php if($edit_page): ?>
                                        <input type="hidden" name="page_id" value="<?php echo $edit_page['id']; ?>">
                                    <?php endif; ?>
                                    <div class="mb-3"><input type="text" name="title" class="form-control" placeholder="Page Title" value="<?php echo $edit_page ? htmlspecialchars($edit_page['title']) : ''; ?>" required></div>
                                    <div class="mb-3"><input type="text" name="slug" class="form-control" placeholder="URL Slug (e.g., about-us)" value="<?php echo $edit_page ? htmlspecialchars($edit_page['slug']) : ''; ?>" required></div>
                                    <div class="mb-3"><textarea name="meta_description" class="form-control" rows="2" placeholder="Meta Description (SEO)"><?php echo $edit_page ? htmlspecialchars($edit_page['meta_description']) : ''; ?></textarea></div>
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" name="is_published" class="form-check-input" value="1" <?php echo ($edit_page && $edit_page['is_published']) ? 'checked' : 'checked'; ?>>
                                        <label class="form-check-label">Publish this page</label>
                                    </div>
                                    <div class="mb-3"><textarea id="pageContent" name="content" class="form-control summernote" rows="8"><?php echo $edit_page ? htmlspecialchars($edit_page['content']) : ''; ?></textarea></div>
                                    <button type="submit" name="save_page" class="btn btn-success w-100"><?php echo $edit_page ? 'Update Page' : 'Create Page'; ?></button>
                                    <?php if($edit_page): ?>
                                        <a href="index.php#pages" class="btn btn-secondary w-100 mt-2">Cancel Edit</a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="card shadow">
                            <div class="card-header bg-primary text-white"><h5 class="mb-0">📋 All Pages</h5></div>
                            <div class="card-body">
                                <?php $pages = $conn->query("SELECT * FROM pages ORDER BY id DESC");
                                while($page = $pages->fetch_assoc()): ?>
                                <div class="list-group-item page-card mb-2 border rounded p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($page['title']); ?></h6>
                                            <small class="text-muted">/page.php?slug=<?php echo $page['slug']; ?></small>
                                            <?php if($page['is_published']): ?><span class="badge bg-success ms-2">Published</span><?php else: ?><span class="badge bg-secondary ms-2">Draft</span><?php endif; ?>
                                        </div>
                                        <div>
                                            <a href="?edit_page=<?php echo $page['id']; ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
                                            <a href="?delete_page=<?php echo $page['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this page?')">🗑️ Delete</a>
                                            <a href="../page.php?slug=<?php echo $page['slug']; ?>" target="_blank" class="btn btn-info btn-sm">👁️ View</a>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- TICKETS TAB -->
            <div class="tab-pane fade" id="tickets" role="tabpanel">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white"><h5 class="mb-0">🎫 Support Tickets</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-dark">...</thead>
                                <tbody>
                                    <?php $tickets = $conn->query("SELECT * FROM support_tickets ORDER BY created_at DESC");
                                    while($ticket = $tickets->fetch_assoc()): ?>
                                    <tr>...</tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    <script>
        $('.summernote').summernote({ height: 300 });
        $('.summernote-sm').summernote({ height: 150, toolbar: [['style', ['bold', 'italic']], ['view', ['codeview']]] });
        
        // Set active tab based on URL parameter
        <?php if($active_tab == 'products'): ?>
        var productsTab = new bootstrap.Tab(document.querySelector('[data-bs-target="#products"]'));
        productsTab.show();
        <?php elseif($active_tab == 'pages'): ?>
        var pagesTab = new bootstrap.Tab(document.querySelector('[data-bs-target="#pages"]'));
        pagesTab.show();
        <?php endif; ?>
    </script>
</body>
</html>