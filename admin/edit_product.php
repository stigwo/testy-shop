<?php 
require_once '../functions.php';
if(!isAdmin()) { header("Location: login.php"); exit; }

$product_id = (int)$_GET['id'];
$product = $conn->query("SELECT * FROM products WHERE id = $product_id")->fetch_assoc();
if(!$product) { header("Location: products.php"); exit; }

// Handle product update
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $name = $_POST['name'];
    $short_desc = $_POST['short_description'];
    $detailed_desc = $_POST['detailed_description'];
    $tech_specs = $_POST['technical_specs'];
    
    $stmt = $conn->prepare("UPDATE products SET name=?, short_description=?, detailed_description=?, technical_specs=? WHERE id=?");
    $stmt->bind_param("ssssi", $name, $short_desc, $detailed_desc, $tech_specs, $product_id);
    $stmt->execute();
    
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
    
    header("Location: edit_product.php?id=$product_id");
    exit;
}

// Handle add variant
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_variant'])) {
    $variant_name = $_POST['variant_name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $sku = $_POST['sku'];
    
    $stmt = $conn->prepare("INSERT INTO product_variants (product_id, variant_name, price, stock, sku) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isdss", $product_id, $variant_name, $price, $stock, $sku);
    $stmt->execute();
    header("Location: edit_product.php?id=$product_id");
    exit;
}

// Handle update variant
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_variant'])) {
    $variant_id = $_POST['variant_id'];
    $variant_name = $_POST['variant_name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $sku = $_POST['sku'];
    
    $stmt = $conn->prepare("UPDATE product_variants SET variant_name=?, price=?, stock=?, sku=? WHERE id=?");
    $stmt->bind_param("sdssi", $variant_name, $price, $stock, $sku, $variant_id);
    $stmt->execute();
    header("Location: edit_product.php?id=$product_id");
    exit;
}

// Handle add image
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_image'])) {
    if(isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = time() . '_' . basename($_FILES['gallery_image']['name']);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            move_uploaded_file($_FILES['gallery_image']['tmp_name'], "../images/" . $filename);
            $sort_order = $conn->query("SELECT COUNT(*) as count FROM product_images WHERE product_id = $product_id")->fetch_assoc()['count'];
            $conn->query("INSERT INTO product_images (product_id, image_path, sort_order) VALUES ($product_id, '$filename', $sort_order)");
        }
    }
    header("Location: edit_product.php?id=$product_id");
    exit;
}

$variants = $conn->query("SELECT * FROM product_variants WHERE product_id = $product_id");
$images = $conn->query("SELECT * FROM product_images WHERE product_id = $product_id ORDER BY sort_order");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Product - <?php echo htmlspecialchars($product['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand">✏️ Edit Product: <?php echo htmlspecialchars($product['name']); ?></span>
            <div>
                <a href="products.php" class="btn btn-outline-light">← Back to Products</a>
                <a href="index.php" class="btn btn-outline-info">Admin Home</a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <ul class="nav nav-tabs">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#basic">📝 Basic Info</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#variants">🔧 Variants</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#images">🖼️ Gallery Images</a></li>
        </ul>
        
        <div class="tab-content mt-3">
            <!-- Basic Info Tab -->
            <div class="tab-pane active" id="basic">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Product Name *</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Short Description</label>
                                <textarea name="short_description" class="form-control" rows="2"><?php echo htmlspecialchars($product['short_description']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Detailed Description</label>
                                <textarea name="detailed_description" class="form-control summernote" rows="5"><?php echo htmlspecialchars($product['detailed_description']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Technical Specifications (HTML allowed)</label>
                                <textarea name="technical_specs" class="form-control" rows="3"><?php echo htmlspecialchars($product['technical_specs']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Current Featured Image</label>
                                <?php if($product['featured_image']): ?>
                                    <div class="mb-2">
                                        <img src="../images/<?php echo $product['featured_image']; ?>" style="max-height: 100px;" class="rounded">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="featured_image" class="form-control" accept="image/*">
                                <small class="text-muted">Leave empty to keep current image</small>
                            </div>
                            <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Variants Tab -->
            <div class="tab-pane" id="variants">
                <div class="row">
                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-header bg-success text-white">Add New Variant</div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Variant Name *</label>
                                        <input type="text" name="variant_name" class="form-control" placeholder="e.g., 20kg capacity" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Price *</label>
                                        <input type="number" step="0.01" name="price" class="form-control" placeholder="0.00" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Stock</label>
                                        <input type="number" name="stock" class="form-control" value="0">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">SKU</label>
                                        <input type="text" name="sku" class="form-control" placeholder="Unique product code">
                                    </div>
                                    <button type="submit" name="add_variant" class="btn btn-success w-100">Add Variant</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header bg-primary text-white">Existing Variants</div>
                            <div class="card-body">
                                <?php if($variants->num_rows == 0): ?>
                                    <div class="alert alert-info">No variants yet. Add your first variant above.</div>
                                <?php else: ?>
                                    <?php while($variant = $variants->fetch_assoc()): ?>
                                    <div class="card mb-2">
                                        <div class="card-body">
                                            <form method="POST" class="row g-2">
                                                <input type="hidden" name="variant_id" value="<?php echo $variant['id']; ?>">
                                                <div class="col-md-3">
                                                    <input type="text" name="variant_name" class="form-control form-control-sm" value="<?php echo htmlspecialchars($variant['variant_name']); ?>" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <input type="number" step="0.01" name="price" class="form-control form-control-sm" value="<?php echo $variant['price']; ?>" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <input type="number" name="stock" class="form-control form-control-sm" value="<?php echo $variant['stock']; ?>">
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="text" name="sku" class="form-control form-control-sm" value="<?php echo htmlspecialchars($variant['sku']); ?>">
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="submit" name="update_variant" class="btn btn-warning btn-sm w-100">Update</button>
                                                    <a href="?delete_variant=<?php echo $variant['id']; ?>&id=<?php echo $product_id; ?>" class="btn btn-danger btn-sm w-100 mt-1" onclick="return confirm('Delete this variant?')">Delete</a>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Images Tab -->
            <div class="tab-pane" id="images">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-success text-white">Add Gallery Image</div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label class="form-label">Select Image</label>
                                        <input type="file" name="gallery_image" class="form-control" accept="image/*" required>
                                    </div>
                                    <button type="submit" name="add_image" class="btn btn-success w-100">Upload Image</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-primary text-white">Product Gallery</div>
                            <div class="card-body">
                                <div class="row">
                                    <?php if($images->num_rows == 0): ?>
                                        <div class="col-12">
                                            <div class="alert alert-info">No gallery images yet.</div>
                                        </div>
                                    <?php else: ?>
                                        <?php while($image = $images->fetch_assoc()): ?>
                                        <div class="col-md-3 mb-3">
                                            <div class="card">
                                                <img src="../images/<?php echo $image['image_path']; ?>" class="card-img-top" style="height: 150px; object-fit: cover;">
                                                <div class="card-body p-2 text-center">
                                                    <a href="?delete_image=<?php echo $image['id']; ?>&id=<?php echo $product_id; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this image?')">Delete</a>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
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
        $('.summernote').summernote({
            height: 200,
            toolbar: [
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link']],
                ['view', ['codeview']]
            ]
        });
    </script>
</body>
</html>