<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../functions.php';

global $conn;

// Check if support_tickets table exists
$table_check = $conn->query("SHOW TABLES LIKE 'support_tickets'");
if($table_check->num_rows > 0) {
    echo "<h3>support_tickets table exists</h3>";
    
    // Count tickets
    $count = $conn->query("SELECT COUNT(*) as total FROM support_tickets");
    $total = $count->fetch_assoc();
    echo "<p>Total tickets: " . $total['total'] . "</p>";
    
    // Get all tickets
    $tickets = $conn->query("SELECT * FROM support_tickets");
    if($tickets->num_rows > 0) {
        echo "<h4>Available Tickets:</h4>";
        echo "<ul>";
        while($ticket = $tickets->fetch_assoc()) {
            echo "<li>ID: " . $ticket['id'] . " - " . htmlspecialchars($ticket['subject']) . " - <a href='ticket_debug.php?id=" . $ticket['id'] . "'>View</a></li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No tickets found. Create a test ticket:</p>";
        echo "<a href='create_test_ticket.php' class='btn btn-primary'>Create Test Ticket</a>";
    }
} else {
    echo "<h3>support_tickets table does NOT exist</h3>";
    echo "<p>Run this SQL to create it:</p>";
    echo "<pre>
    CREATE TABLE IF NOT EXISTS support_tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(255) NOT NULL,
        customer_email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        admin_reply TEXT,
        status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        replied_at TIMESTAMP NULL
    );
    </pre>";
}
?>