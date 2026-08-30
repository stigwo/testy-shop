<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

echo "<h1>Testing PHPMailer</h1>";

// Check if vendor/autoload exists
$vendor_path = __DIR__ . '/vendor/autoload.php';
if (file_exists($vendor_path)) {
    echo "<p style='color:green'>✓ Vendor found at: $vendor_path</p>";
} else {
    echo "<p style='color:red'>✗ Vendor not found at: $vendor_path</p>";
    echo "<p>Please run: composer require phpmailer/phpmailer</p>";
}

// Load config and functions
require_once 'config.php';
require_once 'functions.php';

echo "<p style='color:green'>✓ Functions loaded</p>";

// Test email
echo "<h2>Sending Test Email</h2>";
$result = sendEmail(SMTP_FROM, "PHPMailer Test", "<h3>Test</h3><p>If you see this, PHPMailer is working!</p>");

if($result) {
    echo "<p style='color:green'>✓ Test email sent to: " . SMTP_FROM . "</p>";
} else {
    echo "<p style='color:red'>✗ Email failed. Check error log.</p>";
}

// Show config (hide password)
echo "<h2>Current SMTP Config:</h2>";
echo "<ul>";
echo "<li>Host: " . SMTP_HOST . "</li>";
echo "<li>Port: " . SMTP_PORT . "</li>";
echo "<li>User: " . SMTP_USER . "</li>";
echo "<li>From: " . SMTP_FROM . "</li>";
echo "<li>BCC: " . (defined('SMTP_TO') ? SMTP_TO : 'Not set') . "</li>";
echo "</ul>";
?>