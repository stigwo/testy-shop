<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session properly
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'functions.php';

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Verify captcha
        $captcha_valid = true;
        if (defined('RECAPTCHA_SECRET_KEY') && RECAPTCHA_SECRET_KEY !== 'YOUR_SECRET_KEY' && RECAPTCHA_SECRET_KEY !== '') {
            $captcha_valid = verifyRecaptcha($_POST['g-recaptcha-response'] ?? '');
            if (!$captcha_valid) {
                $error = "Please complete the captcha verification.";
            }
        }
        
        if (empty($error)) {
            // Insert into database
            $stmt = $conn->prepare("INSERT INTO support_tickets (customer_name, customer_email, subject, message, status) VALUES (?, ?, ?, ?, 'open')");
            $stmt->bind_param("ssss", $name, $email, $subject, $message);
            
            if ($stmt->execute()) {
                $ticket_id = $conn->insert_id;
                
                // Send email notification to admin
                $admin_body = "<h3>New Support Ticket #$ticket_id</h3>
                              <p><strong>From:</strong> $name ($email)</p>
                              <p><strong>Subject:</strong> $subject</p>
                              <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>
                              <p><a href='https://revobake.cn/shop/admin/ticket_view.php?id=$ticket_id'>View in Admin Panel</a></p>";
                sendEmail(defined('SMTP_FROM') ? SMTP_FROM : 'support@revobake.cn', "New Support Ticket #$ticket_id", $admin_body);
                
                // Send auto-reply to customer
                $customer_body = "<h3>Thank you for contacting us!</h3>
                                 <p>Dear $name,</p>
                                 <p>We have received your support ticket and will respond within 24 hours.</p>
                                 <p><strong>Ticket #:</strong> $ticket_id</p>
                                 <p><strong>Subject:</strong> $subject</p>
                                 <p>We appreciate your patience.</p>
                                 <hr>
                                 <small>Revobake Support Team</small>";
                sendEmail($email, "Support Ticket #$ticket_id - Confirmation", $customer_body);
                
                $success = "Ticket submitted successfully! We'll respond within 24 hours.";
                
                // Clear form via redirect after 3 seconds
                echo "<script>setTimeout(function(){ window.location.href = 'support.php?success=1'; }, 3000);</script>";
            } else {
                $error = "Database error. Please try again later.";
                error_log("Support ticket insert failed: " . $conn->error);
            }
            $stmt->close();
        }
    }
}

// Check for success parameter
if (isset($_GET['success'])) {
    $success = "Ticket submitted successfully! We'll respond within 24 hours.";
}

// Include header
include 'header.php';
?>

<!-- reCAPTCHA Script -->
<?php if (defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY !== 'YOUR_SITE_KEY' && RECAPTCHA_SITE_KEY !== ''): ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>

<!-- Hero Section -->
<div class="hero-section text-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 0; margin-bottom: 40px; border-radius: 10px;">
    <div class="container">
        <h1 class="display-4">Support Center</h1>
        <p class="lead">Having issues with your equipment? We're here to help.</p>
    </div>
</div>

<div class="row">
    <?php if ($success): ?>
        <div class="col-12">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>✓ <?php echo htmlspecialchars($success); ?></strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>✗ <?php echo htmlspecialchars($error); ?></strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="row">
    <!-- Support Ticket Form -->
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-ticket-alt"></i> Submit a Support Ticket</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="supportForm">
                    <div class="mb-3">
                        <label class="form-label">Your Name *</label>
                        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject *</label>
                        <input type="text" name="subject" class="form-control" required value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message *</label>
                        <textarea name="message" class="form-control" rows="5" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        <small class="text-muted">Please provide as much detail as possible about your issue.</small>
                    </div>
                    
                    <?php if (defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY !== 'YOUR_SITE_KEY' && RECAPTCHA_SITE_KEY !== ''): ?>
                    <div class="mb-3">
                        <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-paper-plane"></i> Submit Ticket
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- FAQ Section -->
    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-question-circle"></i> Frequently Asked Questions</h5>
            </div>
            <div class="card-body">
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        <i class="fas fa-chevron-right text-primary"></i> How do I clean my spiral mixer?
                    </div>
                    <div class="faq-answer">Use a soft cloth with mild detergent. Avoid using high-pressure water or abrasive cleaners. The bowl and hook are dishwasher safe.</div>
                </div>
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        <i class="fas fa-chevron-right text-primary"></i> What is your warranty policy?
                    </div>
                    <div class="faq-answer">All spiral mixers come with a 2-year warranty covering manufacturing defects. Wear parts like belts and bearings are covered for 6 months.</div>
                </div>
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        <i class="fas fa-chevron-right text-primary"></i> How long does shipping take?
                    </div>
                    <div class="faq-answer">International shipping typically takes 5-10 business days. Tracking information is provided once shipped.</div>
                </div>
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        <i class="fas fa-chevron-right text-primary"></i> Do you offer bulk discounts?
                    </div>
                    <div class="faq-answer">Yes! For orders of 3 or more units, please contact our sales team for special pricing.</div>
                </div>
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        <i class="fas fa-chevron-right text-primary"></i> What voltage do your mixers use?
                    </div>
                    <div class="faq-answer">Our mixers are available in 220V/380V 3-phase or 220V single-phase. Please specify your requirement when ordering.</div>
                </div>
            </div>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-phone"></i> Need Immediate Help?</h5>
            </div>
            <div class="card-body text-center">
                <p><strong>Phone Support:</strong> +1 (555) 123-4567</p>
                <p><strong>Hours:</strong> Monday-Friday, 9AM-6PM GMT</p>
                <a href="contact.php" class="btn btn-outline-primary">
                    <i class="fas fa-envelope"></i> Contact Us Directly →
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    .faq-item {
        border-bottom: 1px solid #eee;
        padding: 12px 0;
    }
    .faq-question {
        font-weight: bold;
        color: #333;
        cursor: pointer;
        transition: color 0.3s ease;
    }
    .faq-question:hover {
        color: #007bff;
    }
    .faq-answer {
        display: none;
        padding-top: 10px;
        padding-left: 20px;
        color: #666;
        line-height: 1.6;
    }
</style>

<script>
    function toggleAnswer(element) {
        var answer = element.nextElementSibling;
        if (answer.style.display === "none" || answer.style.display === "") {
            answer.style.display = "block";
        } else {
            answer.style.display = "none";
        }
    }
    
    // Initialize FAQ answers as hidden
    document.querySelectorAll('.faq-answer').forEach(function(el) {
        el.style.display = 'none';
    });
</script>

<?php include 'footer.php'; ?>