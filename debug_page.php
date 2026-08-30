<?php
require_once 'functions.php';

$slug = 'about-us';

echo "<h1>Debug Page Lookup</h1>";

// Check what's in the database
$result = $conn->query("SELECT id, title, slug FROM pages");
echo "<h2>All pages in database:</h2>";
while($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']} - Title: {$row['title']} - Slug: '{$row['slug']}'<br>";
}

// Try to find the page
echo "<h2>Looking for slug: '$slug'</h2>";
$find = $conn->query("SELECT * FROM pages WHERE slug = '$slug'");

if($find && $find->num_rows > 0) {
    $page = $find->fetch_assoc();
    echo "<p style='color:green'>✓ Page found!</p>";
    echo "<pre>";
    print_r($page);
    echo "</pre>";
} else {
    echo "<p style='color:red'>✗ No page found with slug: '$slug'</p>";
    
    // Try case insensitive
    $find2 = $conn->query("SELECT * FROM pages WHERE LOWER(slug) = LOWER('$slug')");
    if($find2 && $find2->num_rows > 0) {
        $page2 = $find2->fetch_assoc();
        echo "<p>Found with different case: slug is '{$page2['slug']}'</p>";
    }
}

// Test the page.php directly
echo "<h2>Test page.php include:</h2>";
echo "<a href='page.php?slug=about-us'>Click here to test page.php?slug=about-us</a><br>";
echo "<a href='page.php?slug=aboutus'>Click here to test page.php?slug=aboutus</a>";
?>