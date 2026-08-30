<?php
// Define the password string
$password = "Gitsco@34";

// Generate the bcrypt hash
$hash = password_hash($password, PASSWORD_BCRYPT);

echo $hash; 
// Outputs a unique 60-character string starting with $2y$10$
?>