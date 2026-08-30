<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../functions.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

// Get total tickets count for badge
$total_tickets = 0;
$ticket_check = $conn->query("SHOW TABLES LIKE 'support_tickets'");
if($ticket_check && $ticket_check->num_rows > 0) {
    $tickets_result = $conn->query("SELECT COUNT(*) as count FROM support_tickets WHERE status != 'closed'");
    if($tickets_result) {
        $total_tickets = $tickets_result->fetch_assoc()['count'];
    }
}

$page_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = ($page_id > 0);
$page = null;

if($is_edit) {
    $result = $conn->query("SELECT * FROM pages WHERE id = $page_id");
    $page = $result->fetch_assoc();
    if(!$page) {
        die("Page not found.");
    }
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_page'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $slug = $conn->real_escape_string(strtolower(str_replace(' ', '-', preg_replace('/[^a-zA-Z0-9 ]/', '', $_POST['slug'] ?: $_POST['title']))));
    $content = $conn->real_escape_string($_POST['content']);
    $meta_description = $conn->real_escape_string($_POST['meta_description']);
    $status = $_POST['status'];
    $sort_order = (int)$_POST['sort_order'];
    
    if($is_edit) {
        $conn->query("UPDATE pages SET title='$title', slug='$slug', content='$content', meta_description='$meta_description', status='$status', sort_order=$sort_order, updated_at=NOW() WHERE id=$page_id");
        header("Location: pages.php?msg=saved");
    } else {
        $conn->query("INSERT INTO pages (title, slug, content, meta_description, status, sort_order) VALUES ('$title', '$slug', '$content', '$meta_description', '$status', $sort_order)");
        header("Location: pages.php?msg=saved");
    }
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $is_edit ? 'Edit' : 'Add'; ?> Page - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- CKEditor 5 -->
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: white;
            padding: 12px 20px;
            margin: 5px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.2);
            transform: translateX(5px);
        }
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.3);
            font-weight: bold;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        .navbar-top {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px 0;
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .ck-editor__editable {
            min-height: 400px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 p-0">
                <div class="sidebar p-3">
                    <h4 class="text-white text-center mb-4">
                        <i class="fas fa-crown"></i> Admin Panel
                    </h4>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a class="nav-link" href="products.php">
                            <i class="fas fa-box"></i> Products
                        </a>
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-shopping-cart"></i> Orders
                        </a>
                        <a class="nav-link" href="customers.php">
                            <i class="fas fa-users"></i> Customers
                        </a>
                        <a class="nav-link" href="support_tickets.php">
                            <i class="fas fa-ticket-alt"></i> Support Tickets
                            <?php if($total_tickets > 0): ?>
                                <span class="badge bg-warning float-end"><?php echo $total_tickets; ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link active" href="pages.php">
                            <i class="fas fa-file-alt"></i> Pages
                        </a>
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <hr class="bg-light">
                        <a class="nav-link" href="../index.php" target="_blank">
                            <i class="fas fa-store"></i> View Shop
                        </a>
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-0">
                <div class="navbar-top px-4 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><?php echo $is_edit ? 'Edit' : 'Add'; ?> Page</h4>
                    <div>
                        <span class="text-muted me-3">
                            <i class="fas fa-user"></i> <?php echo $_SESSION['admin_username'] ?? 'Admin'; ?>
                        </span>
                        <a href="logout.php" class="btn btn-sm btn-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>

                <div class="main-content p-4">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">Page Information</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Page Title *</label>
                                    <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($page['title'] ?? ''); ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Slug (URL)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">/page.php?slug=</span>
                                        <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($page['slug'] ?? ''); ?>" placeholder="auto-generated-from-title">
                                    </div>
                                    <small class="text-muted">Leave empty to auto-generate from title. Use only letters, numbers, and hyphens.</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Page Content</label>
                                    <textarea name="content" id="content" rows="15"><?php echo htmlspecialchars($page['content'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Meta Description (for SEO)</label>
                                    <textarea name="meta_description" class="form-control" rows="2" placeholder="Brief description for search engines..."><?php echo htmlspecialchars($page['meta_description'] ?? ''); ?></textarea>
                                    <small class="text-muted">Recommended: 150-160 characters</small>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Status</label>
                                            <select name="status" class="form-select">
                                                <option value="draft" <?php echo ($page['status'] ?? '') == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                <option value="published" <?php echo ($page['status'] ?? '') == 'published' ? 'selected' : ''; ?>>Published</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Sort Order</label>
                                            <input type="number" name="sort_order" class="form-control" value="<?php echo $page['sort_order'] ?? 0; ?>">
                                            <small class="text-muted">Lower numbers appear first in menus</small>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" name="save_page" class="btn btn-primary">
                                    <i class="fas fa-save"></i> <?php echo $is_edit ? 'Update' : 'Create'; ?> Page
                                </button>
                                <a href="pages.php" class="btn btn-secondary">Cancel</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize CKEditor for Page Content
        ClassicEditor
            .create(document.querySelector('#content'), {
                toolbar: {
                    items: [
                        'heading', '|',
                        'bold', 'italic', 'underline', 'strikethrough', '|',
                        'bulletedList', 'numberedList', '|',
                        'alignment', '|',
                        'link', 'blockQuote', 'insertTable', '|',
                        'undo', 'redo'
                    ]
                },
                heading: {
                    options: [
                        { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                        { model: 'heading1', view: 'h2', title: 'Heading 1', class: 'ck-heading_heading1' },
                        { model: 'heading2', view: 'h3', title: 'Heading 2', class: 'ck-heading_heading2' },
                        { model: 'heading3', view: 'h4', title: 'Heading 3', class: 'ck-heading_heading3' }
                    ]
                }
            })
            .catch(error => {
                console.error(error);
            });
    </script>
</body>
</html>