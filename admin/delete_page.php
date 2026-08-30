<?php 
require_once "../functions.php";
if(!isAdmin()) die("Unauthorized");
$conn->query("DELETE FROM pages WHERE id = {$_GET["id"]}");
header("Location: index.php");
?>