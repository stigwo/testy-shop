<?php
require_once 'functions.php';

$slug = 'about-us';

echo "<h1>Debug Page Lookup</h1>";

// Check what's in the database
$all = $conn->query("SELECT id, title, slug, status FROM pages");
echo "<h2>All pages in database:</h2>";
while($row = $all->fetch_assoc()) {
    echo "ID: {$row['id']} - Title: {$row['title']} - Slug: '{$row['slug']}' - Status: {$row['status']}<br>";
}

// Try to find the page
echo "<h2>Trying to find slug: '$slug'</h2>";

$stmt = $conn->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published'");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();

echo "Number of rows found: " . $result->num_rows . "<br>";

if($result->num_rows > 0) {
    $page = $result->fetch_assoc();
    echo "<p style='color:green'>Page found!</p>";
    echo "<pre>";
    print_r($page);
    echo "</pre>";
} else {
    echo "<p style='color:red'>No page found with slug: $slug</p>";
    
    // Try without status filter
    $stmt2 = $conn->prepare("SELECT * FROM pages WHERE slug = ?");
    $stmt2->bind_param("s", $slug);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    
    if($result2->num_rows > 0) {
        $page2 = $result2->fetch_assoc();
        echo "<p>Page exists but status is: {$page2['status']}</p>";
    }
}
?>