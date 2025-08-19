
<?php
// Set headers to return JSON content for API responses
header('Content-Type: application/json');

// Database credentials
$db_server = "localhost";
$db_username = "root";
// ====== FIX: PASSWORD MUST BE EMPTY to match your MySQL setup ======
$db_password = ""; 
$db_name = "professor_cafe_db";
$db_port = 3307;   // The correct port for YOUR XAMPP MySQL

// Create connection using all five parameters
$conn = new mysqli($db_server, $db_username, $db_password, $db_name,$db_port);

// Check connection for errors
if ($conn->connect_error) {
    $response = [
        'success' => false,
        'error' => 'Database connection failed: ' . $conn->connect_error
    ];
    die(json_encode($response));
}
?>