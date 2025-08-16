<?php
// Include the database connection.
// The header is already set to 'application/json' inside this file.
require_once 'db_connect.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Check if the order_id is provided
    if (isset($_POST['order_id']) && !empty($_POST['order_id'])) {
        
        $order_id = (int)$_POST['order_id'];

        if ($conn) {
            // Prepare a statement to find the order by its ID
            $stmt = $conn->prepare("SELECT id, customer_name, order_status FROM orders WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                // Order found, fetch the data
                $order_data = $result->fetch_assoc();
                
                // Send a success response with the order data
                echo json_encode([
                    'success' => true,
                    'data' => $order_data
                ]);

            } else {
                // Order not found in the database
                echo json_encode([
                    'success' => false,
                    'error' => "Order with ID #${order_id} was not found. Please check the ID and try again."
                ]);
            }
            
            $stmt->close();
            $conn->close();
        } else {
            // This case is handled by db_connect.php but is here as a fallback
             echo json_encode(['success' => false, 'error' => 'Database connection error.']);
        }
    } else {
        // order_id was not provided in the request
        echo json_encode(['success' => false, 'error' => 'Please provide an Order ID.']);
    }
} else {
    // The request was not a POST request
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
?>