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

$success = '';
$error = '';

// Handle SMTP settings update
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_smtp'])) {
    $smtp_host = trim($_POST['smtp_host']);
    $smtp_port = (int)$_POST['smtp_port'];
    $smtp_user = trim($_POST['smtp_user']);
    $smtp_pass = trim($_POST['smtp_pass']);
    $smtp_from = trim($_POST['smtp_from']);
    $smtp_to = trim($_POST['smtp_to']);
    $site_name = trim($_POST['site_name']);
    
    // Read current config to preserve database settings
    $current_config = file_get_contents('../config.php');
    preg_match("/define\('DB_HOST', '(.*?)'\);/", $current_config, $db_host);
    preg_match("/define\('DB_USER', '(.*?)'\);/", $current_config, $db_user);
    preg_match("/define\('DB_PASS', '(.*?)'\);/", $current_config, $db_pass);
    preg_match("/define\('DB_NAME', '(.*?)'\);/", $current_config, $db_name);
    
    $db_host_val = isset($db_host[1]) ? $db_host[1] : 'localhost';
    $db_user_val = isset($db_user[1]) ? $db_user[1] : 'root';
    $db_pass_val = isset($db_pass[1]) ? $db_pass[1] : '';
    $db_name_val = isset($db_name[1]) ? $db_name[1] : 'revobake';
    
    // Get existing reCAPTCHA keys
    preg_match("/define\('RECAPTCHA_SITE_KEY', '(.*?)'\);/", $current_config, $recaptcha_site);
    preg_match("/define\('RECAPTCHA_SECRET_KEY', '(.*?)'\);/", $current_config, $recaptcha_secret);
    
    $recaptcha_site_val = isset($recaptcha_site[1]) ? $recaptcha_site[1] : '';
    $recaptcha_secret_val = isset($recaptcha_secret[1]) ? $recaptcha_secret[1] : '';
    
    // Create new config content
    $config_content = "<?php
// Database configuration
define('DB_HOST', '$db_host_val');
define('DB_USER', '$db_user_val');
define('DB_PASS', '$db_pass_val');
define('DB_NAME', '$db_name_val');

\$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (\$conn->connect_error) {
    die(\"Connection failed: \" . \$conn->connect_error);
}

// SMTP Configuration
define('SMTP_HOST', '$smtp_host');
define('SMTP_PORT', $smtp_port);
define('SMTP_USER', '$smtp_user');
define('SMTP_PASS', '$smtp_pass');
define('SMTP_FROM', '$smtp_from');
define('SMTP_TO', '$smtp_to');
define('SITE_NAME', '$site_name');

// reCAPTCHA Configuration
define('RECAPTCHA_SITE_KEY', '$recaptcha_site_val');
define('RECAPTCHA_SECRET_KEY', '$recaptcha_secret_val');
";
    
    if(file_put_contents('../config.php', $config_content)) {
        $success = "SMTP settings updated successfully!";
        echo "<script>setTimeout(function(){ window.location.href = 'settings.php'; }, 1500);</script>";
    } else {
        $error = "Failed to write config file. Check file permissions.";
    }
}

// Handle reCAPTCHA settings update
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_recaptcha'])) {
    $recaptcha_site_key = trim($_POST['recaptcha_site_key']);
    $recaptcha_secret_key = trim($_POST['recaptcha_secret_key']);
    
    // Read current config to preserve other settings
    $current_config = file_get_contents('../config.php');
    preg_match("/define\('DB_HOST', '(.*?)'\);/", $current_config, $db_host);
    preg_match("/define\('DB_USER', '(.*?)'\);/", $current_config, $db_user);
    preg_match("/define\('DB_PASS', '(.*?)'\);/", $current_config, $db_pass);
    preg_match("/define\('DB_NAME', '(.*?)'\);/", $current_config, $db_name);
    preg_match("/define\('SMTP_HOST', '(.*?)'\);/", $current_config, $smtp_host);
    preg_match("/define\('SMTP_PORT', (.*?)\);/", $current_config, $smtp_port);
    preg_match("/define\('SMTP_USER', '(.*?)'\);/", $current_config, $smtp_user);
    preg_match("/define\('SMTP_PASS', '(.*?)'\);/", $current_config, $smtp_pass);
    preg_match("/define\('SMTP_FROM', '(.*?)'\);/", $current_config, $smtp_from);
    preg_match("/define\('SMTP_TO', '(.*?)'\);/", $current_config, $smtp_to);
    preg_match("/define\('SITE_NAME', '(.*?)'\);/", $current_config, $site_name);
    
    $db_host_val = isset($db_host[1]) ? $db_host[1] : 'localhost';
    $db_user_val = isset($db_user[1]) ? $db_user[1] : 'root';
    $db_pass_val = isset($db_pass[1]) ? $db_pass[1] : '';
    $db_name_val = isset($db_name[1]) ? $db_name[1] : 'revobake';
    $smtp_host_val = isset($smtp_host[1]) ? $smtp_host[1] : '';
    $smtp_port_val = isset($smtp_port[1]) ? $smtp_port[1] : '587';
    $smtp_user_val = isset($smtp_user[1]) ? $smtp_user[1] : '';
    $smtp_pass_val = isset($smtp_pass[1]) ? $smtp_pass[1] : '';
    $smtp_from_val = isset($smtp_from[1]) ? $smtp_from[1] : '';
    $smtp_to_val = isset($smtp_to[1]) ? $smtp_to[1] : '';
    $site_name_val = isset($site_name[1]) ? $site_name[1] : 'Revobake';
    
    // Create new config content
    $config_content = "<?php
// Database configuration
define('DB_HOST', '$db_host_val');
define('DB_USER', '$db_user_val');
define('DB_PASS', '$db_pass_val');
define('DB_NAME', '$db_name_val');

\$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (\$conn->connect_error) {
    die(\"Connection failed: \" . \$conn->connect_error);
}

// SMTP Configuration
define('SMTP_HOST', '$smtp_host_val');
define('SMTP_PORT', $smtp_port_val);
define('SMTP_USER', '$smtp_user_val');
define('SMTP_PASS', '$smtp_pass_val');
define('SMTP_FROM', '$smtp_from_val');
define('SMTP_TO', '$smtp_to_val');
define('SITE_NAME', '$site_name_val');

// reCAPTCHA Configuration
define('RECAPTCHA_SITE_KEY', '$recaptcha_site_key');
define('RECAPTCHA_SECRET_KEY', '$recaptcha_secret_key');
";
    
    if(file_put_contents('../config.php', $config_content)) {
        $success = "reCAPTCHA settings updated successfully!";
        echo "<script>setTimeout(function(){ window.location.href = 'settings.php'; }, 1500);</script>";
    } else {
        $error = "Failed to write config file. Check file permissions.";
    }
}

// Handle admin password update
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_admin'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get current admin config
    $admin_config_file = __DIR__ . '/admin_config.php';
    
    // Default values
    $admin_username = 'admin';
    $admin_password_hash = '';
    
    if(file_exists($admin_config_file)) {
        include $admin_config_file;
    }
    
    // Verify current password
    if(empty($admin_password_hash) || !password_verify($current_password, $admin_password_hash)) {
        $error = "Current password is incorrect.";
    } elseif(strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } else {
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Create new config content
        $admin_config = "<?php
// Admin Configuration
define('ADMIN_USERNAME', '$admin_username');

// Password hash for new password
define('ADMIN_PASSWORD_HASH', '$new_hash');
?>";
        
        if(file_put_contents($admin_config_file, $admin_config)) {
            $success = "Admin password updated successfully! You will be redirected to login page.";
            echo "<script>setTimeout(function(){ window.location.href = 'logout.php'; }, 2000);</script>";
        } else {
            $error = "Failed to update admin password. Check file permissions.";
        }
    }
}

// Get total pages count for badge
$total_pages = 0;
$pages_check = $conn->query("SHOW TABLES LIKE 'pages'");
if($pages_check && $pages_check->num_rows > 0) {
    $total_pages = $conn->query("SELECT COUNT(*) as count FROM pages")->fetch_assoc()['count'];
}

// Get total tickets count
$total_tickets = 0;
$ticket_check = $conn->query("SHOW TABLES LIKE 'support_tickets'");
if($ticket_check && $ticket_check->num_rows > 0) {
    $tickets_result = $conn->query("SELECT COUNT(*) as count FROM support_tickets WHERE status != 'closed'");
    if($tickets_result) {
        $total_tickets = $tickets_result->fetch_assoc()['count'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Settings - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        .settings-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .settings-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 15px 20px;
        }
        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 10px 20px;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .password-hint {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
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
                        <a class="nav-link" href="pages.php">
                            <i class="fas fa-file-alt"></i> Pages
                            <?php if($total_pages > 0): ?>
                                <span class="badge bg-info float-end"><?php echo $total_pages; ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link active" href="settings.php">
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
                <div class="p-4">
                    <h2 class="mb-4"><i class="fas fa-cog"></i> System Settings</h2>
                    
                    <?php if($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- SMTP Settings -->
                    <div class="card settings-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-envelope"></i> SMTP Email Settings</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">SMTP Host</label>
                                        <input type="text" name="smtp_host" class="form-control" value="<?php echo defined('SMTP_HOST') ? htmlspecialchars(SMTP_HOST) : ''; ?>" required>
                                        <small class="text-muted">e.g., smtp.gmail.com, smtp.office365.com</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">SMTP Port</label>
                                        <input type="number" name="smtp_port" class="form-control" value="<?php echo defined('SMTP_PORT') ? SMTP_PORT : '587'; ?>" required>
                                        <small class="text-muted">587 for TLS, 465 for SSL</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">SMTP Username</label>
                                        <input type="text" name="smtp_user" class="form-control" value="<?php echo defined('SMTP_USER') ? htmlspecialchars(SMTP_USER) : ''; ?>" required>
                                        <small class="text-muted">Your email address or username</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">SMTP Password</label>
                                        <input type="password" name="smtp_pass" class="form-control" value="<?php echo defined('SMTP_PASS') ? htmlspecialchars(SMTP_PASS) : ''; ?>" required>
                                        <small class="text-muted">Your email password or app password</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">From Email Address</label>
                                        <input type="email" name="smtp_from" class="form-control" value="<?php echo defined('SMTP_FROM') ? htmlspecialchars(SMTP_FROM) : ''; ?>" required>
                                        <small class="text-muted">Emails will be sent from this address</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">To Email Address (Admin)</label>
                                        <input type="email" name="smtp_to" class="form-control" value="<?php echo defined('SMTP_TO') ? htmlspecialchars(SMTP_TO) : ''; ?>" required>
                                        <small class="text-muted">Contact forms and notifications go here</small>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Site Name</label>
                                        <input type="text" name="site_name" class="form-control" value="<?php echo defined('SITE_NAME') ? htmlspecialchars(SITE_NAME) : 'Revobake'; ?>" required>
                                    </div>
                                </div>
                                <button type="submit" name="update_smtp" class="btn-save">
                                    <i class="fas fa-save"></i> Save SMTP Settings
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- reCAPTCHA Settings -->
                    <div class="card settings-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-shield-alt"></i> reCAPTCHA Settings</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">reCAPTCHA Site Key</label>
                                        <input type="text" name="recaptcha_site_key" class="form-control" value="<?php echo defined('RECAPTCHA_SITE_KEY') ? htmlspecialchars(RECAPTCHA_SITE_KEY) : ''; ?>">
                                        <small class="text-muted">Get your keys from <a href="https://www.google.com/recaptcha/admin" target="_blank">Google reCAPTCHA</a></small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">reCAPTCHA Secret Key</label>
                                        <input type="password" name="recaptcha_secret_key" class="form-control" value="<?php echo defined('RECAPTCHA_SECRET_KEY') ? htmlspecialchars(RECAPTCHA_SECRET_KEY) : ''; ?>">
                                    </div>
                                </div>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> 
                                    <strong>reCAPTCHA v2 Checkbox:</strong> Once configured, the captcha will appear on contact and support pages.
                                    <br>Leave empty to disable captcha.
                                </div>
                                <button type="submit" name="update_recaptcha" class="btn-save">
                                    <i class="fas fa-save"></i> Save reCAPTCHA Settings
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Admin Password Settings -->
                    <div class="card settings-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-user-shield"></i> Admin Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" name="current_password" class="form-control" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">New Password</label>
                                        <input type="password" name="new_password" class="form-control" required>
                                        <div class="password-hint">Minimum 6 characters</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Confirm New Password</label>
                                        <input type="password" name="confirm_password" class="form-control" required>
                                    </div>
                                </div>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    <strong>Note:</strong> After changing your password, you will be logged out and need to log in again with your new password.
                                </div>
                                <button type="submit" name="update_admin" class="btn-save">
                                    <i class="fas fa-key"></i> Update Password
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- System Info -->
                    <div class="card settings-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-info-circle"></i> System Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                                    <p><strong>MySQL Version:</strong> <?php echo $conn->server_info; ?></p>
                                    <p><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Upload Max Size:</strong> <?php echo ini_get('upload_max_filesize'); ?></p>
                                    <p><strong>Post Max Size:</strong> <?php echo ini_get('post_max_size'); ?></p>
                                    <p><strong>Memory Limit:</strong> <?php echo ini_get('memory_limit'); ?></p>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-12">
                                    <p><strong>Config File Path:</strong> <?php echo realpath('../config.php'); ?></p>
                                    <p><strong>Config File Writable:</strong> 
                                        <?php if(is_writable('../config.php')): ?>
                                            <span class="badge bg-success">Yes</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">No - Set chmod 666</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>