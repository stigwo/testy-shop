<?php 
require_once '../functions.php';
if(!isAdmin()) { header("Location: login.php"); exit; }

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $short_desc = $_POST['short_description'];
    $detailed_desc = $_POST['detailed_description'];
    $tech_specs = $_POST['technical_specs'];
    
    $stmt = $conn->prepare("INSERT INTO products (name, short_description, detailed_description, technical_specs) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $short_desc, $detailed_desc, $tech_specs);
    $stmt->execute();
    $product_id = $conn->insert_id;
    
    // Handle image upload
    if(isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = time() . '_' . basename($_FILES['featured_image']['name']);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            move_uploaded_file($_FILES['featured_image']['tmp_name'], "../images/" . $filename);
            $conn->query("UPDATE products SET featured_image = '$filename' WHERE id = $product_id");
        }
    }
    
    header("Location: edit_product.php?id=$product_id");
    exit;
}
?>