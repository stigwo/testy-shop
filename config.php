<?php
session_start();

$db_host = 'localhost';
$db_user = 'slettshop_usr';
$db_pass = 'Gitsco@55';
$db_name = 'slettshop';

// Create connection with error handling
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// reCAPTCHA Keys - Replace these with your actual keys from Google
// For testing, you can disable captcha by setting these to empty
define('RECAPTCHA_SITE_KEY', '6LdastwSAAAAAOE92xff6cARjboW2mSRZ6Uv9S_I'); // Google test key
define('RECAPTCHA_SECRET_KEY', '6LdastwSAAAAAClDMUIQUaAzVxQTK2V6zFoRmM2y'); // Google test key

// PayPal configuration (sandbox for testing)
define('PAYPAL_CLIENT_ID', 'test');
define('PAYPAL_SECRET', 'test');
define('PAYPAL_MODE', 'sandbox');

// SMTP Configuration (using PHP mail() as fallback)
define('SMTP_HOST', 'outbound.mailhop.org');
define('SMTP_PORT', 587);
define('SMTP_USER', 'gakken');
define('SMTP_PASS', 'Gitsco@55');
define('SMTP_FROM', 'post@gitstech.cn');
define('SMTP_TO', 'bossplass@gmail.com');
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