       <?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'functions.php';

// Handle add to cart - MUST be before any output
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $variant_id = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    if($variant_id > 0 && $quantity > 0) {
        addToCart($variant_id, $quantity);
    }
    
    // Redirect to cart page
    header("Location: cart.php");
    exit;
}

$product_id = $_GET['id'] ?? 0;

if (!$product_id) {
    die("No product ID provided.");
}

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if(!$product) {
    die("Product not found.");
}

// Get variants - with error checking
$variants = $conn->query("SELECT * FROM product_variants WHERE product_id = $product_id");
if (!$variants) {
    $variants = false;
}

// Get images - with error checking
$images_result = $conn->query("SELECT * FROM product_images WHERE product_id = $product_id ORDER BY sort_order");

// Check if images table exists or has data
$has_images = false;
$image_list = [];
if ($images_result && $images_result->num_rows > 0) {
    $has_images = true;
    while($img = $images_result->fetch_assoc()) {
        $image_list[] = $img;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($product['name']); ?> | Revobake</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Mobile-first responsive styles */
        .product-image {
            max-height: 400px;
            object-fit: contain;
            width: 100%;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        /* Mobile adjustments */
        @media (max-width: 767.98px) {
            .product-image {
                max-height: 300px;
                border-radius: 8px;
            }
            
            .product-details {
                padding-top: 1.5rem;
            }
            
            h1 {
                font-size: 1.5rem;
            }
            
            .lead {
                font-size: 1rem;
            }
            
            .btn-lg {
                width: 100%;
                padding: 0.75rem;
                font-size: 1.1rem;
            }
            
            .form-select, .form-control {
                font-size: 16px; /* Prevents zoom on iOS */
                padding: 0.75rem;
            }
            
            .container {
                padding-left: 15px;
                padding-right: 15px;
            }
            
            .technical-specs, .detailed-description {
                font-size: 0.95rem;
            }
            
            .carousel-control-prev-icon, 
            .carousel-control-next-icon {
                padding: 15px;
                background-size: 60% 60%;
            }
            
            .click-hint {
                font-size: 11px;
            }
            
            .modal-image {
                max-width: 95vw;
                max-height: 80vh;
            }
        }
        
        /* Tablet adjustments */
        @media (min-width: 768px) and (max-width: 991.98px) {
            .product-image {
                max-height: 350px;
            }
        }
        
        /* Desktop adjustments */
        @media (min-width: 992px) {
            .product-image:hover {
                transform: scale(1.02);
                opacity: 0.9;
            }
        }
        
        .product-image:hover {
            transform: scale(1.02);
            opacity: 0.9;
        }
        
        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
            font-size: 12px;
            display: none;
        }
        
        .debug-info.show {
            display: block;
        }
        
        .technical-specs ul, .detailed-description ul {
            padding-left: 1.5rem;
        }
        
        /* Modal styles */
        .modal-backdrop {
            z-index: 1040 !important;
        }
        .modal {
            z-index: 1050 !important;
        }
        .modal-image {
            max-width: 90vw;
            max-height: 85vh;
            border-radius: 8px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.3);
        }
        
        .carousel-control-prev-icon, .carousel-control-next-icon {
            background-color: rgba(0,0,0,0.5);
            border-radius: 50%;
            padding: 20px;
        }
        
        .click-hint {
            font-size: 12px;
            margin-top: 8px;
        }
        
        .modal-close-btn {
            z-index: 1060;
            background-color: rgba(0,0,0,0.5);
            border-radius: 50%;
            padding: 10px;
            border: 2px solid white;
        }
        
        .modal-close-btn:hover {
            background-color: rgba(0,0,0,0.8);
        }
        
        /* Add smooth transitions */
        .row {
            transition: all 0.3s ease;
        }
        
        /* Better spacing for mobile */
        .back-button {
            margin-bottom: 1rem;
        }
        
        @media (max-width: 767.98px) {
            .back-button {
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-3 mt-md-4">
        <a href="index.php" class="btn btn-secondary mb-3 back-button">← Back to Shop</a>
        
        <?php if(isset($_GET['debug'])): ?>
        <div class="alert alert-info debug-info show">
            <strong>Debug Mode Active</strong><br>
            Product ID: <?php echo $product_id; ?><br>
            Images found: <?php echo count($image_list); ?><br>
            Images table exists: <?php echo $images_result ? 'Yes' : 'No'; ?><br>
            <?php if(empty($image_list)): ?>
            <hr>
            <strong>To add an image, run this SQL:</strong><br>
            <code>INSERT INTO product_images (product_id, image_path, sort_order) VALUES (<?php echo $product_id; ?>, 'your-image-name.jpg', 1);</code>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Image Column - Will be first on mobile (order-first), second on desktop -->
            <div class="col-md-6 order-first order-md-0">
                <?php if($has_images && !empty($image_list)): ?>
                    <div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <?php $first = true; foreach($image_list as $index => $img): ?>
                            <div class="carousel-item <?php echo $first ? 'active' : ''; ?>">
                                <img src="/shop/images/<?php echo htmlspecialchars(ltrim($img['image_path'], '/')); ?>" 
                                     class="d-block w-100 product-image" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?> - Image <?php echo $index + 1; ?>"
                                     onclick="openImageModal(this.src, this.alt)"
                                     loading="lazy">
                            </div>
                            <?php $first = false; endforeach; ?>
                        </div>
                        <?php if(count($image_list) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="text-center click-hint">
                        <small class="text-muted">🔍 Tap image to enlarge</small>
                    </div>
                <?php else: ?>
                    <div class="bg-light p-5 text-center border rounded">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-image mb-3 text-muted" viewBox="0 0 16 16">
                            <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                            <path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z"/>
                        </svg>
                        <h5>No Images Available</h5>
                        <p class="text-muted mb-0">No product images have been uploaded yet.</p>
                        <?php if(isset($_GET['debug'])): ?>
                        <hr>
                        <small class="text-muted">To add images:<br>
                        1. Upload image to <code>/shop/images/</code><br>
                        2. Run: <code>INSERT INTO product_images (product_id, image_path, sort_order) VALUES (<?php echo $product_id; ?>, 'filename.jpg', 1);</code>
                        </small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Product Details Column - Will be second on mobile, first on desktop -->
            <div class="col-md-6 order-last order-md-1 product-details">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="lead"><?php echo htmlspecialchars($product['short_description'] ?? 'Professional bakery equipment'); ?></p>
                <hr>
                
                <?php if(!empty($product['technical_specs'])): ?>
                <h4>Technical Specifications</h4>
                <div class="mb-3 technical-specs"><?php echo $product['technical_specs']; ?></div>
                <?php endif; ?>
                
                <?php if(!empty($product['detailed_description'])): ?>
                <h4>Detailed Description</h4>
                <div class="mb-3 detailed-description"><?php echo $product['detailed_description']; ?></div>
                <?php endif; ?>
                <hr>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Model:</label>
                        <select name="variant_id" class="form-select" required>
                            <?php if($variants && $variants->num_rows > 0): ?>
                                <?php while($var = $variants->fetch_assoc()): ?>
                                <option value="<?php echo $var['id']; ?>">
                                    <?php echo htmlspecialchars($var['variant_name']); ?> - $<?php echo number_format($var['price'], 2); ?>
                                    (Stock: <?php echo $var['stock']; ?>)
                                </option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="">No variants available</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Quantity:</label>
                        <input type="number" name="quantity" value="1" min="1" class="form-control" style="max-width: 120px;" aria-label="Quantity">
                    </div>
                    <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg">Add to Cart</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body p-0 text-center position-relative">
                    <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                    <img src="" alt="" id="modalImage" class="modal-image">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple modal function
        function openImageModal(imageUrl, imageAlt) {
            // Set the image source
            const modalImage = document.getElementById('modalImage');
            modalImage.src = imageUrl;
            modalImage.alt = imageAlt;
            
            // Get modal element
            const modalElement = document.getElementById('imageModal');
            
            // Create new modal instance and show it
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            
            // Clean up after modal is closed
            modalElement.addEventListener('hidden.bs.modal', function() {
                // Remove any leftover backdrops
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(function(backdrop) {
                    backdrop.remove();
                });
                // Reset body
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }, { once: true });
        }
        
        // Also add click handlers to any dynamically loaded images
        document.addEventListener('DOMContentLoaded', function() {
            // Find all product images and ensure they have the onclick attribute
            const images = document.querySelectorAll('.product-image');
            images.forEach(function(img) {
                if (!img.hasAttribute('onclick')) {
                    img.addEventListener('click', function() {
                        openImageModal(this.src, this.alt);
                    });
                }
            });
        });
    </script>
</body>
</html>
               
               