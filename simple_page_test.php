<?php
require_once 'functions.php';

$slug = 'about-us';

$result = $conn->query("SELECT * FROM pages WHERE slug = '$slug' AND status = 'published'");
$page = $result->fetch_assoc();

echo "<h1>Test Output</h1>";
echo "Page found: " . ($page ? "YES" : "NO");
if($page) {
    echo "<h2>" . $page['title'] . "</h2>";
    echo $page['content'];
}
?>