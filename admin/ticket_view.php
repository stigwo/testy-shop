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

$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if(!$ticket_id) {
    die("Ticket ID required. Please use: ticket_view.php?id=1");
}

// Get ticket details
global $conn;
$result = $conn->query("SELECT * FROM support_tickets WHERE id = $ticket_id");

if(!$result) {
    die("Database error: " . $conn->error);
}

$ticket = $result->fetch_assoc();

if(!$ticket) {
    die("Ticket not found. Ticket ID $ticket_id does not exist.");
}

// Check if replied_at column exists
$has_replied_at = false;
$columns = $conn->query("SHOW COLUMNS FROM support_tickets");
while($col = $columns->fetch_assoc()) {
    if($col['Field'] == 'replied_at') {
        $has_replied_at = true;
        break;
    }
}

// Handle reply submission
$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    $admin_reply = trim($_POST['admin_reply']);
    $new_status = $_POST['status'];
    $send_email = isset($_POST['send_email']) ? true : false;
    
    if(empty($admin_reply)) {
        $error = "Reply message cannot be empty.";
    } else {
        // Escape the reply for database
        $escaped_reply = addslashes($admin_reply);
        
        // Save reply to database (with or without replied_at)
        if($has_replied_at) {
            $update_sql = "UPDATE support_tickets SET status = '$new_status', admin_reply = '$escaped_reply', replied_at = NOW() WHERE id = $ticket_id";
        } else {
            $update_sql = "UPDATE support_tickets SET status = '$new_status', admin_reply = '$escaped_reply' WHERE id = $ticket_id";
        }
        
        if($conn->query($update_sql)) {
            $success = "Reply saved successfully!";
            
            // Send email notification if requested
            if($send_email && !empty($ticket['customer_email']) && function_exists('sendEmail')) {
                $subject = 'Re: Support Ticket #' . $ticket_id . ' - ' . $ticket['subject'];
                
                $email_body = "
                <html>
                <body>
                    <h3>Support Ticket Update</h3>
                    <p>Dear " . htmlspecialchars($ticket['customer_name']) . ",</p>
                    <p>Your support ticket <strong>#" . $ticket_id . "</strong> has been updated.</p>
                    <hr>
                    <p><strong>Our Reply:</strong></p>
                    <p>" . nl2br(htmlspecialchars($admin_reply)) . "</p>
                    <hr>
                    <p><strong>Status:</strong> " . ucfirst($new_status) . "</p>
                    <p>Best regards,<br>Revobake Support Team</p>
                </body>
                </html>
                ";
                
                $email_sent = sendEmail($ticket['customer_email'], $subject, $email_body);
                $success .= $email_sent ? " Email sent to customer." : " Email failed to send.";
            }
            
            // Refresh ticket data
            $result = $conn->query("SELECT * FROM support_tickets WHERE id = $ticket_id");
            $ticket = $result->fetch_assoc();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}

// Handle status change only
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    $new_status = $_POST['status'];
    $update_sql = "UPDATE support_tickets SET status = '$new_status' WHERE id = $ticket_id";
    if($conn->query($update_sql)) {
        $success = "Status updated!";
        $result = $conn->query("SELECT * FROM support_tickets WHERE id = $ticket_id");
        $ticket = $result->fetch_assoc();
    } else {
        $error = "Failed to update status: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ticket #<?php echo $ticket_id; ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .ticket-message {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .admin-reply {
            background: #e8f4f8;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .reply-box {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        .email-checkbox {
            background: #e7f3ff;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .status-badge {
            font-size: 14px;
            padding: 5px 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand">Support Ticket #<?php echo $ticket_id; ?></span>
            <div>
                <a href="support_tickets.php" class="btn btn-outline-light">← Back to Tickets</a>
                <a href="dashboard.php" class="btn btn-outline-info">Dashboard</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
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

        <div class="row">
            <div class="col-md-8">
                <!-- Ticket Details -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Ticket Details</h4>
                        <span class="badge bg-<?php echo $ticket['status'] == 'closed' ? 'secondary' : ($ticket['status'] == 'in_progress' ? 'warning' : 'danger'); ?> status-badge">
                            <i class="fas fa-circle"></i> <?php echo ucfirst($ticket['status'] ?? 'Open'); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong><i class="fas fa-user"></i> Customer:</strong><br>
                                <?php echo htmlspecialchars($ticket['customer_name'] ?? 'N/A'); ?>
                            </div>
                            <div class="col-md-6">
                                <strong><i class="fas fa-envelope"></i> Email:</strong><br>
                                <a href="mailto:<?php echo $ticket['customer_email']; ?>"><?php echo htmlspecialchars($ticket['customer_email'] ?? 'N/A'); ?></a>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong><i class="fas fa-calendar"></i> Created:</strong><br>
                                <?php echo date('F j, Y g:i A', strtotime($ticket['created_at'])); ?>
                            </div>
                            <div class="col-md-6">
                                <strong><i class="fas fa-tag"></i> Subject:</strong><br>
                                <?php echo htmlspecialchars($ticket['subject']); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Message -->
                <div class="ticket-message">
                    <div class="d-flex justify-content-between mb-2">
                        <strong><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($ticket['customer_name'] ?? 'Customer'); ?></strong>
                        <small class="text-muted"><?php echo date('F j, Y g:i A', strtotime($ticket['created_at'])); ?></small>
                    </div>
                    <div class="mt-2">
                        <?php echo nl2br(htmlspecialchars($ticket['message'])); ?>
                    </div>
                </div>

                <!-- Admin Reply (if exists) -->
                <?php if(!empty($ticket['admin_reply'])): ?>
                    <div class="admin-reply">
                        <div class="d-flex justify-content-between mb-2">
                            <strong><i class="fas fa-headset"></i> Support Team Reply</strong>
                            <small class="text-muted">
                                <?php 
                                if($has_replied_at && !empty($ticket['replied_at'])) {
                                    echo date('F j, Y g:i A', strtotime($ticket['replied_at']));
                                } else {
                                    echo date('F j, Y g:i A', strtotime($ticket['updated_at'] ?? $ticket['created_at']));
                                }
                                ?>
                            </small>
                        </div>
                        <div class="mt-2">
                            <?php echo nl2br(htmlspecialchars($ticket['admin_reply'])); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Reply Form -->
                <div class="reply-box">
                    <h5><i class="fas fa-reply"></i> Reply to Customer</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Your Reply:</label>
                            <textarea name="admin_reply" class="form-control" rows="6" required placeholder="Type your response here..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Update Status:</label>
                            <select name="status" class="form-select">
                                <option value="open" <?php echo $ticket['status'] == 'open' ? 'selected' : ''; ?>>Open</option>
                                <option value="in_progress" <?php echo $ticket['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="closed" <?php echo $ticket['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                            </select>
                        </div>
                        <div class="email-checkbox mb-3">
                            <input type="checkbox" name="send_email" id="send_email" value="1" checked>
                            <label for="send_email" class="mb-0">
                                <i class="fas fa-envelope"></i> Send email notification to customer
                            </label>
                            <br>
                            <small class="text-muted">Customer will receive an email with your reply</small>
                        </div>
                        <button type="submit" name="reply" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send Reply
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Status Update Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Change Status:</label>
                                <select name="status" class="form-select">
                                    <option value="open" <?php echo $ticket['status'] == 'open' ? 'selected' : ''; ?>>Open</option>
                                    <option value="in_progress" <?php echo $ticket['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="closed" <?php echo $ticket['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                            <button type="submit" name="change_status" class="btn btn-warning w-100">
                                <i class="fas fa-save"></i> Update Status Only
                            </button>
                        </form>
                        <hr>
                        <a href="mailto:<?php echo $ticket['customer_email']; ?>" class="btn btn-outline-secondary w-100 mb-2">
                            <i class="fas fa-envelope"></i> Open in Email Client
                        </a>
                        <button class="btn btn-outline-danger w-100" onclick="window.print();">
                            <i class="fas fa-print"></i> Print Ticket
                        </button>
                    </div>
                </div>

                <!-- Ticket Info Card -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Ticket Information</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-hashtag text-muted"></i> <strong>ID:</strong> #<?php echo $ticket['id']; ?>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-clock text-muted"></i> <strong>Created:</strong><br>
                                <?php echo date('M d, Y g:i A', strtotime($ticket['created_at'])); ?>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-sync-alt text-muted"></i> <strong>Last Updated:</strong><br>
                                <?php echo date('M d, Y g:i A', strtotime($ticket['updated_at'] ?? $ticket['created_at'])); ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>