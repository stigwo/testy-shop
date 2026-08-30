<?php
session_start();
require_once '../functions.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $page_id = (int)$_POST['page_id'];
    $sort_order = (int)$_POST['sort_order'];
    
    $conn->query("UPDATE pages SET sort_order = $sort_order WHERE id = $page_id");
}

header("Location: pages.php");
exit;
?>