<?php
echo "<h1>PHP Error Log Check</h1>";

// Display PHP errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check log file
$log_file = ini_get('error_log');
echo "<p>Error log location: " . ($log_file ?: 'Not set') . "</p>";

if($log_file && file_exists($log_file)) {
    echo "<h3>Last 20 errors:</h3>";
    echo "<pre>";
    system("tail -20 " . escapeshellarg($log_file));
    echo "</pre>";
}

// Test simple script
echo "<h3>Testing simple PHP execution:</h3>";
$test = "Working";
echo "✓ PHP is working: $test<br>";

// Test database
echo "<h3>Testing database query:</h3>";
global $conn;
require_once 'config.php';
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$row = $result->fetch_assoc();
echo "✓ Database has " . $row['count'] . " users<br>";
?>