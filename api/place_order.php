<?php
// ነቲ ናይ ዳታቤዝ መራኸቢ ፋይል ምጽዋዕ
require_once 'db_connect.php';

// ነቲ ካብ ፍሮንት-ኢንድ ዝመጽእ JSON ዳታ ምንባብ
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// ሓበሬታ ብትኽክል ከምዝመጸ ምርግጋጽ
if (!$data || !isset($data['customerDetails']) || !isset($data['cartItems']) || empty($data['cartItems'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid or empty data received.']);
    exit();
}

$customer = $data['customerDetails'];
$cart = $data['cartItems'];

// ሓደ ነገር እንተተጋግዩ ኩሉ ከምዝበላሾ ንምግባር (Transaction)
$conn->begin_transaction();

try {
    // 1. ነቲ ቀንዲ ትእዛዝ ኣብ `orders` ቴብል ምእታው
    $total_price = 0;
    foreach ($cart as $item) {
        $total_price += $item['price'] * $item['quantity'];
    }

    $stmt_order = $conn->prepare("INSERT INTO orders (customer_name, customer_phone, customer_address, total_price) VALUES (?, ?, ?, ?)");
    $stmt_order->bind_param("sssd", $customer['name'], $customer['phone'], $customer['address'], $total_price);
    $stmt_order->execute();

    $order_id = $conn->insert_id; // ነቲ ዝተፈጥረ ID ምሓዝ
    $stmt_order->close();

    // 2. ንነፍሲ-ወከፍ ምግቢ ኣብ `order_items` ቴብል ምእታው
    $stmt_items = $conn->prepare("INSERT INTO order_items (order_id, product_name, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($cart as $item) {
        $stmt_items->bind_param("isid", $order_id, $item['name'], $item['quantity'], $item['price']);
        $stmt_items->execute();
    }
    $stmt_items->close();

    // ኩሉ ብዓወት ምስ ዝዛዘም፡ ነቲ ለውጥታት ኣብ ዳታቤዝ ምጽዳቕ
    $conn->commit();

    // ናይ ዓወት መልሲ ምስቲ ናይ ትእዛዝ ቁጽሪ (Order ID) ናብ ፍሮንት-ኢንድ ምምላስ
    echo json_encode(['success' => true, 'order_id' => $order_id]);

} catch (Exception $e) {
    // ጌጋ እንተ ኣጋጢሙ፡ ነቲ ዝተጀመረ ለውጥታት ምልሳን
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Failed to place order: ' . $e->getMessage()]);
}

// ምትእስሳር ምዕጻው
$conn->close();
?>