<?php 
require_once "../functions.php";
if(!isAdmin()) die("Unauthorized");
$id = $_GET["id"];
if($_SERVER["REQUEST_METHOD"] === "POST") {
    $stmt = $conn->prepare("UPDATE pages SET title = ?, content = ? WHERE id = ?");
    $stmt->bind_param("ssi", $_POST["title"], $_POST["content"], $id);
    $stmt->execute();
    header("Location: index.php");
    exit;
}
$page = $conn->query("SELECT * FROM pages WHERE id = $id")->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head><title>Edit Page</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1>Edit Page</h1>
    <form method="POST">
        <div class="mb-3"><input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($page["title"]); ?>" required></div>
        <div class="mb-3"><textarea id="content" name="content" class="form-control summernote"><?php echo htmlspecialchars($page["content"]); ?></textarea></div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
<script>$(".summernote").summernote({height: 400});</script>
</body>
</html>