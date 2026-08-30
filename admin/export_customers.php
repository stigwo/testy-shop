<?php
session_start();
require_once '../functions.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

global $conn;

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=customers_' . date('Y-m-d') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, ['Customer Name', 'Email', 'Phone', 'Total Orders', 'Total Spent', 'First Order Date', 'Last Order Date']);

// Get customer data
$customers = $conn->query("
    SELECT 
        customer_name, 
        customer_email, 
        MAX(customer_phone) as customer_phone,
        COUNT(*) as order_count, 
        SUM(total_amount) as total_spent,
        MIN(created_at) as first_order,
        MAX(created_at) as last_order
    FROM orders 
    GROUP BY customer_email 
    ORDER BY total_spent DESC
");

while($customer = $customers->fetch_assoc()) {
    fputcsv($output, [
        $customer['customer_name'],
        $customer['customer_email'],
        $customer['customer_phone'] ?? 'N/A',
        $customer['order_count'],
        '$' . number_format($customer['total_spent'], 2),
        date('Y-m-d', strtotime($customer['first_order'])),
        date('Y-m-d', strtotime($customer['last_order']))
    ]);
}

fclose($output);
exit;
?>