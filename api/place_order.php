<?php
header('Content-Type: application/json');

// --- Database Connection (with Port Number for reliability) ---
$db_server = "localhost";
$db_username = "root";
$db_password = "";
$db_name = "professor_cafe_db";
$db_port = 3307; // Using the default port after XAMPP reinstall
$conn = new mysqli($db_server, $db_username, $db_password, $db_name, $db_port);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
    exit();
}

// --- START: Receipt Upload and Order Processing ---
$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['receipt_screenshot'])) {
    
    $customer_name = $_POST['customer_name'] ?? '';
    $customer_phone = $_POST['customer_phone'] ?? '';
    $customer_address = $_POST['customer_address'] ?? '';
    $cart_json = $_POST['cart_items'] ?? '[]';
    $cart = json_decode($cart_json, true);

    $conn->begin_transaction();
    try {
        // 1. Handle the Receipt Screenshot Upload
        $receipt_path = null;
        if ($_FILES['receipt_screenshot']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../assets/receipts/';
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

            $file_extension = strtolower(pathinfo($_FILES['receipt_screenshot']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($file_extension, $allowed)) {
                throw new Exception("Invalid file type. Only JPG, PNG, and GIF are allowed.");
            }

            $unique_filename = 'receipt_' . uniqid() . '.' . $file_extension;
            $target_file = $upload_dir . $unique_filename;

            if (move_uploaded_file($_FILES['receipt_screenshot']['tmp_name'], $target_file)) {
                $receipt_path = 'assets/receipts/' . $unique_filename;
            } else {
                throw new Exception("Failed to save the uploaded receipt.");
            }
        } else {
            throw new Exception("Receipt screenshot is required or an upload error occurred.");
        }

        // 2. Calculate total price from the cart
        $total_price = 0;
        if (is_array($cart)) {
            foreach ($cart as $item) {
                $total_price += ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
            }
        }

        // 3. Insert the main order into the `orders` table
        // We define the status here
        $order_status = 'Pending Confirmation'; 
        
        $stmt_order = $conn->prepare("INSERT INTO orders (customer_name, customer_phone, customer_address, total_price, receipt_screenshot_path, order_status) VALUES (?, ?, ?, ?, ?, ?)");
        
        // ====== THIS IS THE CORRECTED LINE ======
        // We now have 6 type definitions ("sssdss") and 6 variables.
        $stmt_order->bind_param("sssdss", $customer_name, $customer_phone, $customer_address, $total_price, $receipt_path, $order_status);
        
        if (!$stmt_order->execute()) {
            throw new Exception("Error inserting order: " . $stmt_order->error);
        }
        $order_id = $conn->insert_id;
        $stmt_order->close();

        // 4. Insert each cart item into the `order_items` table
        if (is_array($cart)) {
            $stmt_items = $conn->prepare("INSERT INTO order_items (order_id, product_name, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($cart as $item) {
                $stmt_items->bind_param("isid", $order_id, $item['name'], $item['quantity'], $item['price']);
                $stmt_items->execute();
            }
            $stmt_items->close();
        }

        $conn->commit();
        $response['success'] = true;
        $response['order_id'] = $order_id;
        
    } catch (Exception $e) {
        $conn->rollback();
        $response['error'] = $e->getMessage();
    }
} else {
    $response['error'] = "Invalid request or receipt file missing.";
}

$conn->close();
echo json_encode($response);
?>