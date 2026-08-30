<?php
session_start();

$db_host = 'localhost';
$db_user = 'revobake_cn';
$db_pass = 'XiIZ$GuR#{7XYLt^';
$db_name = 'revobake_cn';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// reCAPTCHA Keys
define('RECAPTCHA_SITE_KEY', '6LfwuL0sAAAAAJqhIzbom-1wRhL35vk-UO6i-5if');
define('RECAPTCHA_SECRET_KEY', '6LfwuL0sAAAAAGwQDBfc44AujsNTCakoj27LNDlX');

// PayPal configuration
define('PAYPAL_CLIENT_ID', 'YOUR_PAYPAL_CLIENT_ID');
define('PAYPAL_SECRET', 'YOUR_PAYPAL_SECRET');
define('PAYPAL_MODE', 'sandbox');

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM', 'shop@spiralmixers.com');
define('SITE_NAME', 'Spiral Mixers Shop');

// Bank details
define('BANK_NAME', 'Example Bank');
define('ACCOUNT_NAME', 'Spiral Mixers Ltd');
define('ACCOUNT_NUMBER', '12345678');
define('SWIFT_CODE', 'EXAMPL33');
define('IBAN', 'GB00EXAMPLE12345678');
?>