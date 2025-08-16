<?php
session_start();
// 1. ውሕስነት: ኣድሚን ኣትዩ ከምዘየለ ምርግጋጽ
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// 2. ናይ ዳታቤዝ መራኸቢ ፋይል ምጽዋዕ
require_once __DIR__ . '/../api/db_connect.php';
header('Content-Type: text/html; charset=utf-8');

// 3. ናይ ትእዛዝ ቁጽሪ (Order ID) ካብ URL ምውሳድ
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id === 0) {
    die("Invalid Order ID provided.");
}

// 4. ሓበሬታ ካብ ዳታቤዝ ምምጻእ
$order = null;
$order_items = [];

if ($conn) {
    // ሀ. ቀንዲ ሓበሬታ ትእዛዝ
    $stmt_order = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt_order->bind_param("i", $order_id);
    $stmt_order->execute();
    $result_order = $stmt_order->get_result();
    if ($result_order->num_rows === 1) {
        $order = $result_order->fetch_assoc();
    }
    $stmt_order->close();

    // ለ. ዝርዝር ምግቢታት
    if ($order) {
        $stmt_items = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt_items->bind_param("i", $order_id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        $order_items = $result_items->fetch_all(MYSQLI_ASSOC);
        $stmt_items->close();
    }
}

if (!$order) {
    die("Order with ID #{$order_id} not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - #<?php echo $order['id']; ?></title>
    <style>
       :root {
    --primary-color: #d9534f;
    --primary-dark: #c9302c;
    --text-dark: #2c3e50;
    --text-light: #7f8c8d;
    --background-light: #f4f7f6;
    --white-color: #ffffff;
    --border-color: #ecf0f1;
    --success-bg: #e8f5e9;
    --success-text: #2e7d32;
    --success-border: #a5d6a7;
}

body {
    font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
    background-color: var(--background-light);
    margin: 0;
    color: var(--text-dark);
}
/* --- HEADER STYLES (Copied from index.php for consistency) --- */
.header {
    background-color: #333;
    color: white;
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.header h1 {
    margin: 0;
    font-size: 24px;
}
.header a {
    color: #d9534f;
    text-decoration: none;
    font-weight: bold;
}

.container {
    padding: 40px;
    max-width: 1100px;
    margin: auto;
}

.card {
    background: var(--white-color);
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.07);
    margin-bottom: 30px;
}

.section-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--primary-color);
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 15px;
    margin-bottom: 25px;
}

.customer-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.info-item {
    display: flex;
    align-items: center;
    background-color: var(--background-light);
    padding: 15px;
    border-radius: 8px;
}

.info-item .icon {
    margin-right: 15px;
    color: var(--primary-color);
    font-size: 20px;
    width: 25px;
    text-align: center;
}

.info-item .label {
    font-weight: 600;
    color: var(--text-light);
    margin-right: 8px;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.items-table th, .items-table td {
    padding: 16px;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

.items-table thead th {
    background-color: var(--background-light);
    font-weight: 700;
    color: var(--text-dark);
    font-size: 14px;
    text-transform: uppercase;
}

.items-table tbody tr:hover {
    background-color: #fafcff;
}

.items-table td:nth-child(2), .items-table th:nth-child(2) { text-align: center; } /* Quantity */
.items-table td:nth-child(3), .items-table th:nth-child(3) { text-align: right; } /* Unit Price */
.items-table td:nth-child(4), .items-table th:nth-child(4) { text-align: right; } /* Subtotal */

.total-row td {
    font-weight: 700;
    font-size: 1.3em;
    border-top: 3px solid var(--primary-color);
    padding-top: 20px;
}

.update-form-container {
    margin-top: 30px;
}

.update-form {
    background: var(--background-light);
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.update-form label {
    font-weight: 700;
    font-size: 16px;
}

.update-form select {
    flex-grow: 1;
    padding: 14px;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    font-size: 16px;
    background-color: var(--white-color);
    min-width: 200px;
}

.btn-update {
    background-color: #27ae60;
    color: white;
    padding: 14px 25px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
    font-size: 16px;
    transition: background-color 0.3s, transform 0.2s;
}

.btn-update:hover {
    background-color: #229954;
    transform: translateY(-2px);
}

.success-message {
    background-color: var(--success-bg);
    color: var(--success-text);
    border: 1px solid var(--success-border);
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: 600;
}
    </style>
</head>
<body>

    <header class="header">
    <h1>Order #<?php echo htmlspecialchars($order['id']); ?> Details</h1>
    <a href="index.php">← Back to Dashboard</a>
</header>

    <div class="container">
        <!-- ናይ ዓወት መልእኽቲ እንተ ኣልዩ ዝርኣየሉ -->
        <?php if(isset($_GET['status']) && $_GET['status'] == 'updated'): ?>
            <div class="success-message">Order status has been updated successfully!</div>
        <?php endif; ?>

        <div class="card">
            <!-- ናይ ዓሚል ሓበሬታ -->
            <h3 class="section-title">Customer & Order Details</h3>
            <div class="customer-info-grid">
                <div class="info-item"><span class="icon">👤</span><span class="label">Name:</span>  <?php echo htmlspecialchars($order['customer_name']); ?></div>
                <div class="info-item"><span class="icon">📞</span><span class="label">Phone:</span>  <?php echo htmlspecialchars($order['customer_phone']); ?></div>
                <div class="info-item"><span class="icon">📅</span><span class="label">Date:</span>  <?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?></div>
                <div class="info-item"><span class="icon">📍</span><span class="label">Address:</span>  <?php echo nl2br(htmlspecialchars($order['customer_address'])); ?></div>
            </div>

            <!-- ናይ ትእዛዝ ዝርዝር -->
            <h3 class="section-title">Order Items</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($order_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo number_format($item['price'], 2); ?> Birr</td>
                            <td><?php echo number_format($item['price'] * $item['quantity'], 2); ?> Birr</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3" style="text-align:right;">Grand Total:</td>
                        <td><?php echo number_format($order['total_price'], 2); ?> Birr</td>
                    </tr>
                </tfoot>
            </table>

            <!-- ናይ ኩነታት መቕየሪ ፎርም -->
            <div class="update-form-container">
                 <form class="update-form" action="update_status.php" method="POST">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    <label for="status"><strong>Current Status:</strong></label>
                    <select name="new_status" id="status">
                        <option value="Pending" <?php if($order['order_status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                        <option value="In Process" <?php if($order['order_status'] == 'In Process') echo 'selected'; ?>>In Process</option>
                        <option value="Ready for Delivery" <?php if($order['order_status'] == 'Ready for Delivery') echo 'selected'; ?>>Ready for Delivery</option>
                        <option value="Completed" <?php if($order['order_status'] == 'Completed') echo 'selected'; ?>>Completed</option>
                    </select>
                    <button type="submit" class="btn-update">Update Status</button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
<?php
if ($conn) {
    $conn->close();
}
?>