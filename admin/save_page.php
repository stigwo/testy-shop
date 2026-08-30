<?php 
require_once "../functions.php";
if(!isAdmin()) die("Unauthorized");
if($_SERVER["REQUEST_METHOD"] === "POST") {
    $stmt = $conn->prepare("INSERT INTO pages (slug, title, content) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $_POST["slug"], $_POST["title"], $_POST["content"]);
    $stmt->execute();
    header("Location: index.php");
}
?>