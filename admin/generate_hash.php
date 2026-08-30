<?php
echo "<h1>Generate Correct Password Hash</h1>";

$password = 'Gitsco@66T';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<p>Password: <strong>$password</strong></p>";
echo "<p>Generated Hash: <code>$hash</code></p>";
echo "<hr>";

// Verify the hash
if(password_verify($password, $hash)) {
    echo "<p style='color:green'>✓ Hash verified successfully!</p>";
} else {
    echo "<p style='color:red'>✗ Hash verification failed!</p>";
}

// Create the config file content
$config_content = "<?php
// Admin Configuration
define('ADMIN_USERNAME', 'admin');

// Password hash for 'admin123'
define('ADMIN_PASSWORD_HASH', '$hash');
?>";

echo "<h2>Copy this to admin_config.php:</h2>";
echo "<textarea rows='10' cols='80' style='width:100%;'>" . htmlspecialchars($config_content) . "</textarea>";

// Try to write the file
$file = __DIR__ . '/admin_config.php';
if(file_put_contents($file, $config_content)) {
    echo "<p style='color:green; margin-top:20px;'>✓ File written successfully!</p>";
    echo "<p><a href='index.php' class='btn btn-primary'>Go to Login</a></p>";
} else {
    echo "<p style='color:red; margin-top:20px;'>✗ Could not write file. Please copy the content manually.</p>";
}
?>