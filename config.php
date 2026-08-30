<?php
session_start();

$db_host = 'localhost';
$db_user = 'xxxx';
$db_pass = 'Gxxx';
$db_name = 'sxxx';

// Create connection with error handling
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// reCAPTCHA Keys - Replace these with your actual keys from Google
// For testing, you can disable captcha by setting these to empty
define('RECAPTCHA_SITE_KEY', '6LdastwSAAAccccccccjboW2mSRZ6Uv9S_I'); // Google test key
define('RECAPTCHA_SECRET_KEY', '6LdastwSccccccccccK2V6zFoRmM2y'); // Google test key

// PayPal configuration (sandbox for testing)
define('PAYPAL_CLIENT_ID', 'test');
define('PAYPAL_SECRET', 'test');
define('PAYPAL_MODE', 'sandbox');

// SMTP Configuration (using PHP mail() as fallback)
define('SMTP_HOST', 'outbound.');
define('SMTP_PORT', 587);
define('SMTP_USER', 'cccccn');
define('SMTP_PASS', 'Giccccc');
define('SMTP_FROM', 'post@post.cn');
define('SMTP_TO', 'bossplail.com');
define('SITE_NAME', 'Spiral Mixers Shop');

// Bank details
define('BANK_NAME', 'Example Bank');
define('ACCOUNT_NAME', 'Spiral Mixers Ltd');
define('ACCOUNT_NUMBER', '12345678');
define('SWIFT_CODE', 'EXAMPL33');
define('IBAN', 'GB00EXAMPLE12345678');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>