<?php
// This script is included, so it should not have any visual output.
// We also start a session here to make sure we don't count the same person
// multiple times within the same browsing session.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create a database connection
$db_server = "localhost";
$db_username = "root";
$db_password = ""; // ====== FIX: SET PASSWORD TO EMPTY (as per our previous fixes) ======
$db_name = "professor_cafe_db";
$db_port = 3307; // ====== FIX: ADDED THE CORRECT PORT ======
$conn_tracker = new mysqli($db_server, $db_username, $db_password, $db_name,$db_port); // ====== FIX: USE THE PORT VARIABLE ======

// Only proceed if the connection is successful
if (!$conn_tracker->connect_error) {
    
    $today_date = date('Y-m-d');
    
    // Check if we have already tracked this user in the current session
    if (!isset($_SESSION['visitor_tracked_today']) || $_SESSION['visitor_tracked_today'] !== $today_date) {
        
        // Get the user's IP address
        $ip_address = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
        
        // Check if this IP has already been logged for today
        // NOTE: Your database table needs to be named 'visitor_log' for this to work.
        $stmt_check = $conn_tracker->prepare("SELECT id FROM visitor_log WHERE ip_address = ? AND visit_date = ?");
        $stmt_check->bind_param("ss", $ip_address, $today_date);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        // If no record is found, it's a unique visitor for today
        if ($result->num_rows === 0) {
            $stmt_insert = $conn_tracker->prepare("INSERT INTO visitor_log (ip_address, visit_date) VALUES (?, ?)");
            $stmt_insert->bind_param("ss", $ip_address, $today_date);
            $stmt_insert->execute();
            $stmt_insert->close();
        }
        $stmt_check->close();

        // Set a session variable to prevent re-checking for this user during their session
        $_SESSION['visitor_tracked_today'] = $today_date;
    }
    
    $conn_tracker->close();
}
?>