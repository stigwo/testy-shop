               <?php
require_once 'config.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if(empty($slug)) {
    header("Location: index.php");
    exit;
}

// Get the page
$result = $conn->query("SELECT * FROM pages WHERE slug = '$slug' AND status = 'published'");

if(!$result || $result->num_rows == 0) {
    // Page not found - use header/footer
    include 'header.php';
    ?>
    <div class="row mt-5">
        <div class="col-md-6 mx-auto text-center">
            <h1 class="display-1 text-muted">404</h1>
            <h2>Page Not Found</h2>
            <p>The page "<?php echo htmlspecialchars($slug); ?>" doesn't exist.</p>
            <a href="index.php" class="btn btn-primary">Return to Shop</a>
        </div>
    </div>
    <?php
    include 'footer.php';
    exit;
}

$page = $result->fetch_assoc();

// Include header
include 'header.php';
?>

<div class="row mt-4">
    <div class="col-lg-10 mx-auto">
        <h1 class="mb-4"><?php echo htmlspecialchars($page['title']); ?></h1>
        <div class="page-content">
            <?php echo $page['content']; ?>
        </div>
    </div>
</div>

<style>
    .page-content {
        font-size: 1.1rem;
        line-height: 1.8;
    }
    .page-content h2 {
        font-size: 1.8rem;
        margin-top: 30px;
        margin-bottom: 15px;
        border-bottom: 2px solid #eee;
        padding-bottom: 10px;
    }
    .page-content h3 {
        font-size: 1.4rem;
        margin-top: 25px;
        margin-bottom: 10px;
        color: #555;
    }
    .page-content p {
        margin-bottom: 20px;
    }
    .page-content ul, .page-content ol {
        margin-bottom: 20px;
        padding-left: 20px;
    }
    .page-content li {
        margin-bottom: 8px;
    }
</style>

<?php include 'footer.php'; ?>