<?php
session_start();
require_once '../functions.php';

// Check admin login
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$product_id = $_GET['id'] ?? 0;
if(!$product_id) {
    die("Product ID required");
}

// Get product info
$product = $conn->query("SELECT * FROM products WHERE id = $product_id")->fetch_assoc();
if(!$product) {
    die("Product not found");
}

// Handle image upload
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/shop/images/';
    
    // Create directory if not exists
    if(!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $files = $_FILES['images'];
    $uploaded = 0;
    $errors = [];
    
    foreach($files['tmp_name'] as $key => $tmp_name) {
        if($files['error'][$key] === 0) {
            $filename = time() . '_' . $key . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '', $files['name'][$key]);
            $destination = $upload_dir . $filename;
            
            if(move_uploaded_file($tmp_name, $destination)) {
                // Get max sort order
                $max_sort = $conn->query("SELECT MAX(sort_order) as max FROM product_images WHERE product_id = $product_id")->fetch_assoc();
                $sort_order = ($max_sort['max'] ?? 0) + 1;
                
                // Save to database
                $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_path, sort_order) VALUES (?, ?, ?)");
                $stmt->bind_param("isi", $product_id, $filename, $sort_order);
                $stmt->execute();
                $uploaded++;
            } else {
                $errors[] = "Failed to upload: {$files['name'][$key]}";
            }
        }
    }
    
    $message = "$uploaded images uploaded successfully!";
    if(!empty($errors)) {
        $message .= " Errors: " . implode(", ", $errors);
    }
    header("Location: product_images.php?id=$product_id&msg=" . urlencode($message));
    exit;
}

// Handle image deletion
if(isset($_GET['delete_image'])) {
    $image_id = $_GET['delete_image'];
    $image = $conn->query("SELECT * FROM product_images WHERE id = $image_id AND product_id = $product_id")->fetch_assoc();
    if($image) {
        // Delete file
        $file_path = $_SERVER['DOCUMENT_ROOT'] . '/shop/images/' . $image['image_path'];
        if(file_exists($file_path)) {
            unlink($file_path);
        }
        // Delete from database
        $conn->query("DELETE FROM product_images WHERE id = $image_id");
    }
    header("Location: product_images.php?id=$product_id");
    exit;
}

// Handle sort order update
if(isset($_POST['update_sort'])) {
    foreach($_POST['sort_order'] as $id => $order) {
        $conn->query("UPDATE product_images SET sort_order = $order WHERE id = $id");
    }
    header("Location: product_images.php?id=$product_id&msg=Sort order updated");
    exit;
}

// Get all images for this product
$images = $conn->query("SELECT * FROM product_images WHERE product_id = $product_id ORDER BY sort_order");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Images - <?php echo htmlspecialchars($product['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .image-preview {
            width: 150px;
            height: 150px;
            object-fit: contain;
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 5px;
            background: #f8f9fa;
        }
        .image-card {
            transition: transform 0.2s;
            margin-bottom: 20px;
        }
        .image-card:hover {
            transform: scale(1.02);
        }
        .sort-input {
            width: 60px;
            text-align: center;
        }
        .drop-zone {
            border: 2px dashed #007bff;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
        }
        .drop-zone:hover, .drop-zone.dragover {
            background: #e9ecef;
            border-color: #0056b3;
        }
        .preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }
        .preview-item {
            position: relative;
            width: 100px;
            height: 100px;
        }
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .remove-preview {
            position: absolute;
            top: -8px;
            right: -8px;
            background: red;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            text-align: center;
            font-size: 12px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand">Manage Images: <?php echo htmlspecialchars($product['name']); ?></span>
            <div>
                <a href="products.php" class="btn btn-outline-light">← Back to Products</a>
                <a href="product_edit.php?id=<?php echo $product_id; ?>" class="btn btn-info">Edit Product</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Upload Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h4>Upload Multiple Images</h4>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <div class="drop-zone" id="dropZone">
                        <i class="fas fa-cloud-upload-alt fa-3x mb-3 text-primary"></i>
                        <h5>Drag & Drop Images Here</h5>
                        <p class="text-muted">or click to select files</p>
                        <input type="file" name="images[]" multiple accept="image/*" style="display: none;" id="fileInput">
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('fileInput').click()">
                            Select Images
                        </button>
                    </div>
                    <div class="preview-container" id="previewContainer"></div>
                    <button type="submit" class="btn btn-success mt-3" id="uploadBtn" style="display: none;">
                        <i class="fas fa-upload"></i> Upload Selected Images
                    </button>
                </form>
            </div>
        </div>

        <!-- Existing Images -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>Product Images (<?php echo $images->num_rows; ?> images)</h4>
                <?php if($images->num_rows > 0): ?>
                    <form method="POST" class="d-inline">
                        <button type="submit" name="update_sort" class="btn btn-sm btn-primary">Update Sort Order</button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if($images && $images->num_rows > 0): ?>
                    <form method="POST">
                        <div class="row">
                            <?php while($img = $images->fetch_assoc()): ?>
                                <div class="col-md-3 image-card">
                                    <div class="card">
                                        <img src="/shop/images/<?php echo $img['image_path']; ?>" class="card-img-top image-preview" style="width:100%; height:200px; object-fit:contain;" onerror="this.src='https://placehold.co/200x200'">
                                        <div class="card-body text-center">
                                            <label>Sort Order:</label>
                                            <input type="number" name="sort_order[<?php echo $img['id']; ?>]" value="<?php echo $img['sort_order']; ?>" class="sort-input form-control d-inline-block">
                                            <br><br>
                                            <a href="?id=<?php echo $product_id; ?>&delete_image=<?php echo $img['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this image?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-image fa-3x mb-3"></i>
                        <h5>No images yet</h5>
                        <p>Upload images using the form above</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Drag and drop functionality
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const previewContainer = document.getElementById('previewContainer');
        const uploadBtn = document.getElementById('uploadBtn');
        let selectedFiles = [];

        dropZone.addEventListener('click', () => fileInput.click());
        
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
        
        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });
        
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            const files = Array.from(e.dataTransfer.files);
            handleFiles(files);
        });
        
        fileInput.addEventListener('change', (e) => {
            const files = Array.from(e.target.files);
            handleFiles(files);
        });
        
        function handleFiles(files) {
            files.forEach(file => {
                if(file.type.startsWith('image/')) {
                    selectedFiles.push(file);
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = document.createElement('div');
                        preview.className = 'preview-item';
                        preview.innerHTML = `
                            <img src="${e.target.result}">
                            <div class="remove-preview" onclick="this.parentElement.remove(); removeFile('${file.name}')">×</div>
                        `;
                        previewContainer.appendChild(preview);
                    };
                    reader.readAsDataURL(file);
                }
            });
            uploadBtn.style.display = 'block';
            
            // Update file input with all files
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => dataTransfer.items.add(file));
            fileInput.files = dataTransfer.files;
        }
        
        function removeFile(filename) {
            selectedFiles = selectedFiles.filter(f => f.name !== filename);
            if(selectedFiles.length === 0) {
                uploadBtn.style.display = 'none';
            }
            // Update file input
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => dataTransfer.items.add(file));
            fileInput.files = dataTransfer.files;
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>