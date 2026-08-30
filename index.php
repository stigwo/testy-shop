<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session is started in header.php
require_once 'functions.php';

// Include header
include 'header.php';

// Get all products
$products_query = "SELECT * FROM products ORDER BY created_at DESC";
$products = $conn->query($products_query);

// Debug - uncomment to see if query works
// if(!$products) {
//     echo "Query error: " . $conn->error;
// }
?>

<!-- Hero Section -->
<div class="hero-section text-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 60px 0; margin-bottom: 40px;">
    <div class="container">
        <h1 class="display-4">Professional Spiral Mixers</h1>
        <p class="lead">Heavy-duty equipment for professional bakeries</p>
    </div>
</div>

<!-- Products Grid -->
<div class="container mb-5">
    <?php if($products && $products->num_rows > 0): ?>
        <div class="row g-4">
            <?php while($product = $products->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="product-image-container" style="background-color: #f8f9fa; text-align: center; padding: 20px; height: 250px; display: flex; align-items: center; justify-content: center;">
                            <?php 
                            // Get the first image from product_images table
                            $image_query = $conn->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY sort_order LIMIT 1");
                            $image_query->bind_param("i", $product['id']);
                            $image_query->execute();
                            $image_result = $image_query->get_result();
                            $product_image = $image_result->fetch_assoc();
                            
                            if($product_image && !empty($product_image['image_path'])) {
                                $image_path = '/shop/images/' . ltrim($product_image['image_path'], '/');
                            } elseif(!empty($product['featured_image'])) {
                                $image_path = '/shop/images/' . ltrim($product['featured_image'], '/');
                            } else {
                                $image_path = 'https://placehold.co/400x300/FFD700/000?text=' . urlencode($product['name']);
                            }
                            ?>
                            <img src="<?php echo $image_path; ?>" 
                                 class="product-image" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 style="max-width: 100%; max-height: 210px; width: auto; height: auto; object-fit: contain;"
                                 onerror="this.onerror=null; this.src='https://placehold.co/400x300/FFD700/000?text=No+Image';">
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text text-muted">
                                <?php 
                                $desc = strip_tags($product['short_description'] ?? $product['detailed_description'] ?? '');
                                echo htmlspecialchars(substr($desc, 0, 80)) . (strlen($desc) > 80 ? '...' : '');
                                ?>
                            </p>
                            <div class="text-center mt-3">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
                                    View Details →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            <h4>No products found</h4>
            <p>Check back soon for our professional spiral mixers!</p>
            <?php if(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                <a href="admin/product_edit.php" class="btn btn-primary">Add Your First Product</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .product-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
    }
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    @media (max-width: 768px) {
        .product-image-container {
            height: 200px;
        }
    }
</style>

<?php include 'footer.php'; ?>