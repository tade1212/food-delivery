<?php
// This script is for AJAX calls only. It returns a JSON response.
require_once 'db_connect.php'; // Includes session_start() and the DB connection

// Security check: only logged-in admins can access this data
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit;
}

$new_order_count = 0;
// Query the database to count orders with "Pending" status
$result = $conn->query("SELECT COUNT(id) as pending_count FROM orders WHERE order_status = 'Pending'");

if ($result) {
    $new_order_count = (int)$result->fetch_assoc()['pending_count'];
}

$conn->close();

// Send the result back as a JSON object
echo json_encode([
    'success' => true,
    'new_order_count' => $new_order_count
]);
?>