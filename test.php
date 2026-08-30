<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>System Check</h1>";

// Check PHP version
echo "<p>PHP Version: " . PHP_VERSION . "</p>";

// Check config
echo "<h3>1. Loading config.php...</h3>";
require_once 'config.php';
echo "✓ config.php loaded<br>";

// Check database
echo "<h3>2. Testing Database...</h3>";
if($conn) {
    echo "✓ Database connected<br>";
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch_assoc();
    echo "✓ Users in database: " . $row['count'] . "<br>";
} else {
    echo "✗ Database connection failed<br>";
}

// Check session
echo "<h3>3. Testing Session...</h3>";
session_start();
$_SESSION['test'] = 'working';
echo "✓ Session working: " . $_SESSION['test'] . "<br>";

// Check reCAPTCHA keys
echo "<h3>4. reCAPTCHA Configuration...</h3>";
echo "Site Key: " . (RECAPTCHA_SITE_KEY != 'YOUR_SITE_KEY' ? "✓ Set" : "✗ Not set (use default keys for now)") . "<br>";
echo "Secret Key: " . (RECAPTCHA_SECRET_KEY != 'YOUR_SECRET_KEY' ? "✓ Set" : "✗ Not set") . "<br>";

// List all required files
echo "<h3>5. File Check...</h3>";
$files = ['index.php', 'product.php', 'cart.php', 'checkout.php', 'contact.php', 'support.php', 'functions.php', 'config.php'];
foreach($files as $file) {
    echo (file_exists($file) ? "✓" : "✗") . " $file<br>";
}

echo "<h3>6. Admin Files...</h3>";
$admin_files = ['admin/index.php', 'admin/login.php', 'admin/logout.php'];
foreach($admin_files as $file) {
    echo (file_exists($file) ? "✓" : "✗") . " $file<br>";
}

echo "<h3>7. Quick Fix Options:</h3>";
echo "<ul>";
echo "<li><a href='reset_password.php'>Reset Admin Password</a></li>";
echo "<li><a href='admin/login.php'>Go to Admin Login</a></li>";
echo "<li><a href='index.php'>View Shop</a></li>";
echo "</ul>";
?>