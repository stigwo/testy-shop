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
    $message = trim($_POST['message'] ?? '');
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($message)) {
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
            // Prepare email to admin only (no auto-reply to customer)
            $to = defined('SMTP_TO') ? SMTP_TO : 'support@revobake.cn';
            $subject = "Contact Form Message from $name";
            $email_body = "<h3>New Contact Form Message</h3>
                          <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
                          <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                          <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>";
            
            // Send email only to admin (no auto-reply to customer)
            if (sendEmail($to, $subject, $email_body)) {
                $success = "Message sent successfully! We'll respond within 24 hours.";
                
                // Redirect after 2 seconds
                echo "<script>setTimeout(function(){ window.location.href = 'contact.php?success=1'; }, 2000);</script>";
            } else {
                $error = "Failed to send message. Please try again later.";
                error_log("Contact form email failed to send to $to");
            }
        }
    }
}

// Check for success parameter
if (isset($_GET['success'])) {
    $success = "Message sent successfully! We'll respond within 24 hours.";
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
        <h1 class="display-4">Contact Us</h1>
        <p class="lead">We'd love to hear from you. Get in touch with our team.</p>
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
    <!-- Contact Form -->
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-envelope"></i> Send us a Message</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="contactForm">
                    <div class="mb-3">
                        <label class="form-label">Your Name *</label>
                        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message *</label>
                        <textarea name="message" class="form-control" rows="6" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    </div>
                    
                    <?php if (defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY !== 'YOUR_SITE_KEY' && RECAPTCHA_SITE_KEY !== ''): ?>
                    <div class="mb-3">
                        <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Contact Information -->
    <div class="col-md-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Contact Information</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <i class="fas fa-map-marker-alt text-primary"></i>
                    <strong>Address:</strong><br>
                    Revobake Ltd.<br>
                    123 Bakery Street<br>
                    London, UK, EC1A 1BB
                </div>
                <div class="mb-3">
                    <i class="fas fa-envelope text-primary"></i>
                    <strong>Email:</strong><br>
                    <a href="mailto:sales@revobake.cn">sales@revobake.cn</a><br>
                    <a href="mailto:support@revobake.cn">support@revobake.cn</a>
                </div>
                <div class="mb-3">
                    <i class="fas fa-phone text-primary"></i>
                    <strong>Phone:</strong><br>
                    +1 (555) 123-4567<br>
                    +44 20 1234 5678
                </div>
                <div>
                    <i class="fas fa-clock text-primary"></i>
                    <strong>Business Hours:</strong><br>
                    Monday - Friday: 9:00 AM - 6:00 PM GMT<br>
                    Saturday: 10:00 AM - 2:00 PM GMT<br>
                    Sunday: Closed
                </div>
            </div>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="fas fa-clock"></i> Response Time</h5>
            </div>
            <div class="card-body">
                <p>We aim to respond to all inquiries within:</p>
                <ul>
                    <li><strong>Sales questions:</strong> 4 business hours</li>
                    <li><strong>Technical support:</strong> 24 hours</li>
                    <li><strong>Order status:</strong> 12 hours</li>
                </ul>
                <div class="progress">
                    <div class="progress-bar bg-success" style="width: 95%">95%</div>
                </div>
                <small class="text-muted">Of messages replied within 24 hours</small>
            </div>
        </div>
    </div>
</div>

<!-- Map Section -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-map"></i> Find Us</h5>
            </div>
            <div class="card-body p-0">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2483.123456789!2d-0.123456!3d51.123456!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNTHCsDA3JzI0LjQiTiAwwrAwNyc0OC4wIkc!5e0!3m2!1sen!2suk!4v1234567890" 
                    width="100%" 
                    height="300" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy">
                </iframe>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>