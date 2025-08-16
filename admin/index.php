<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../api/db_connect.php';
// This line has a small typo 'utf-t', it should be 'utf-8'. I will correct it.
header('Content-Type: text/html; charset=utf-8');

// Count unread feedback messages for notification
$unread_feedback_count = 0;
$count_result = $conn->query("SELECT COUNT(id) as unread_count FROM feedback WHERE is_read = 0");
if ($count_result) {
    $unread_feedback_count = $count_result->fetch_assoc()['unread_count'];
}

// Fetch all orders that are not 'Completed'
$result = $conn->query("SELECT * FROM orders WHERE order_status != 'Completed' ORDER BY order_date DESC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Professor Cafe</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f4f8f9; margin: 0; }
        .header { background-color: black; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .header a { color:white; text-decoration: none; font-weight: bold; }
        .container { padding: 30px; }
        .orders-table { width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 4px 20px rgba(0,0,0,0.07); border-radius: 8px; overflow: hidden; }
        .orders-table th, .orders-table td { padding: 15px; border-bottom: 1px solid #ddd; text-align: left; }
        .orders-table th { background-color: #f2f2f2; }
        .orders-table tr:last-child td { border-bottom: none; }
        .orders-table tr:hover { background-color: #f9f9f9; }
        .status-pending { color: #f0ad4e; font-weight: bold; }
        .status-in-process { color: #0275d8; font-weight: bold; }
        .status-ready-for-delivery { color: #5cb85c; font-weight: bold; }
        .action-button { background-color: #5bc0de; color: white; padding: 8px 12px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; }
        .header-nav { display: flex; gap: 20px; align-items: center; }
        .notification-badge { background-color: #e74c3c; color: white; border-radius: 10px; padding: 2px 8px; font-size: 12px; font-weight: bold; margin-left: 5px; vertical-align: top; }
    </style>
</head>
<body>
    <header class="header">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h1>
    <div class="header-nav">
        <a href="../index.php">Home</a> <!-- Link to the main website home -->
        <a href="index.php">Orders</a> <!-- The notification will attach here -->
        <a href="reports.php">Reports</a>
        <a href="manage_products.php">Manage Products</a>
        <a href="view_feedback.php">
            View Feedback
            <?php if ($unread_feedback_count > 0): ?>
                <span class="notification-badge"><?php echo $unread_feedback_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="change_password.php">Change Password</a>
        <a href="logout.php">Logout</a>
    </div>
</header>
    <div class="container">
        <h2>Active Orders</h2>
        <table class="orders-table">
            <thead>
                <tr><th>Order ID</th><th>Customer Name</th><th>Phone</th><th>Address</th><th>Total Price</th><th>Status</th><th>Order Date</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($order = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['customer_phone']); ?></td>
                            <td><?php echo htmlspecialchars($order['customer_address']); ?></td>
                            <td><?php echo $order['total_price']; ?> Birr</td>
                            <td><span class="status-<?php echo strtolower(str_replace(' ', '-', $order['order_status'])); ?>"><?php echo htmlspecialchars($order['order_status']); ?></span></td>
                            <td><?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?></td>
                            <td><a href="view_order.php?id=<?php echo $order['id']; ?>" class="action-button">View Details</a></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align:center;">No active orders found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script src="admin_script.js"></script> 
</body>
</html>
<?php
if ($conn) { $conn->close(); }
?>