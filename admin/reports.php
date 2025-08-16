<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../api/db_connect.php';
header('Content-Type: text/html; charset=utf-8');

// --- START: Unique Visitor Count Logic ---
$today_visits = 0;
$yesterday_visits = 0;
$today_date = date('Y-m-d');
$yesterday_date = date('Y-m-d', strtotime('-1 day'));

// Get today's unique visits by counting rows in the new table
$stmt_today_visits = $conn->prepare("SELECT COUNT(id) as visit_count FROM visitor_log WHERE visit_date = ?");
$stmt_today_visits->bind_param("s", $today_date);
$stmt_today_visits->execute();
$result_today_visits = $stmt_today_visits->get_result();
if ($result_today_visits) {
    $today_visits = $result_today_visits->fetch_assoc()['visit_count'] ?? 0;
}
$stmt_today_visits->close();

// Get yesterday's unique visits
$stmt_yesterday_visits = $conn->prepare("SELECT COUNT(id) as visit_count FROM visitor_log WHERE visit_date = ?");
$stmt_yesterday_visits->bind_param("s", $yesterday_date);
$stmt_yesterday_visits->execute();
$result_yesterday_visits = $stmt_yesterday_visits->get_result();
if ($result_yesterday_visits) {
    $yesterday_visits = $result_yesterday_visits->fetch_assoc()['visit_count'] ?? 0;
}
$stmt_yesterday_visits->close();
// --- END: Unique Visitor Count Logic ---

// Count unread feedback messages for notification
$unread_feedback_count = 0;
$count_result = $conn->query("SELECT COUNT(id) as unread_count FROM feedback WHERE is_read = 0");
if ($count_result) {
    $unread_feedback_count = $count_result->fetch_assoc()['unread_count'];
}

// --- START: Order Report Calculations (Date Filtering) ---
$start_date = (isset($_GET['start_date']) && !empty($_GET['start_date'])) ? $_GET['start_date'] : null;
$end_date = (isset($_GET['end_date']) && !empty($_GET['end_date'])) ? $_GET['end_date'] : null;
$where_clause_joined = "WHERE o.order_status = 'Completed'";
$params = [];
$types = "";
if ($start_date) { $where_clause_joined .= " AND DATE(o.order_date) >= ?"; $params[] = $start_date; $types .= "s"; }
if ($end_date) { $where_clause_joined .= " AND DATE(o.order_date) <= ?"; $params[] = $end_date; $types .= "s"; }
$where_clause_simple = str_replace('o.', '', $where_clause_joined);
$summary = ['total_revenue' => 0, 'total_orders' => 0];
$sql_summary = "SELECT COUNT(id) as order_count, SUM(total_price) as total_rev FROM orders " . $where_clause_simple;
$stmt_summary = $conn->prepare($sql_summary);
if (!empty($params)) { $stmt_summary->bind_param($types, ...$params); }
$stmt_summary->execute();
$result_summary = $stmt_summary->get_result();
if ($result_summary) { $summary_data = $result_summary->fetch_assoc(); $summary['total_orders'] = $summary_data['order_count'] ?? 0; $summary['total_revenue'] = $summary_data['total_rev'] ?? 0; }
$stmt_summary->close();
$today_stats = ['today_revenue' => 0, 'today_orders' => 0];
$stmt_today = $conn->prepare("SELECT COUNT(id) as order_count, SUM(total_price) as total_rev FROM orders WHERE order_status = 'Completed' AND DATE(order_date) = ?");
$stmt_today->bind_param("s", $today_date);
$stmt_today->execute();
$result_today = $stmt_today->get_result();
if ($result_today) { $today_data = $result_today->fetch_assoc(); $today_stats['today_orders'] = $today_data['order_count'] ?? 0; $today_stats['today_revenue'] = $today_data['total_rev'] ?? 0; }
$stmt_today->close();
$top_items = [];
$sql_top_items = "SELECT oi.product_name, SUM(oi.quantity) as total_quantity FROM order_items oi JOIN orders o ON oi.order_id = o.id " . $where_clause_joined . " GROUP BY oi.product_name ORDER BY total_quantity DESC LIMIT 5";
$stmt_top = $conn->prepare($sql_top_items);
if (!empty($params)) { $stmt_top->bind_param($types, ...$params); }
$stmt_top->execute();
$result_top_items = $stmt_top->get_result();
if ($result_top_items) { $top_items = $result_top_items->fetch_all(MYSQLI_ASSOC); }
$stmt_top->close();
$recent_completed_orders = [];
$sql_recent = "SELECT * FROM orders " . $where_clause_simple . " ORDER BY order_date DESC LIMIT 10";
$stmt_recent = $conn->prepare($sql_recent);
if (!empty($params)) { $stmt_recent->bind_param($types, ...$params); }
$stmt_recent->execute();
$result_recent = $stmt_recent->get_result();
if ($result_recent) { $recent_completed_orders = $result_recent->fetch_all(MYSQLI_ASSOC); }
$stmt_recent->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Panel</title>
    <style>
        body { font-family: 'Segoe UI', 'Roboto', sans-serif; background-color: #f4f7f6; margin: 0; }
        .header { background-color: black; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .header-nav { display: flex; gap: 20px; align-items: center; }
        .header-nav a { color:white; text-decoration: none; font-weight: bold; }
        .container { padding: 30px; }
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .summary-card { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
        .summary-card .title { font-size: 16px; color: #7f8c8d; margin: 0 0 10px 0; }
        .summary-card .value { font-size: 32px; font-weight: 700; color: #2c3e50; margin: 0; }
        .summary-card .value sup { font-size: 18px; font-weight: 600; }
        .content-card { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); margin-bottom: 30px; }
        .content-card h2 { font-size: 20px; color: #d9534f; margin-top: 0; border-bottom: 1px solid #ecf0f1; padding-bottom: 15px; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 15px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        .data-table thead th { background-color: #f9f9f9; font-weight: 600; }
        .data-table tbody tr:hover { background-color: #fafcff; }
        .filter-form { display: flex; align-items: flex-end; gap: 20px; flex-wrap: wrap; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { margin-bottom: 8px; font-weight: 600; font-size: 14px; }
        .form-group input[type="date"] { padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 16px; }
        .btn-filter { background-color: #0275d8; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 16px; height: 43px; }
        .notification-badge { background-color: #e74c3c; color: white; border-radius: 10px; padding: 2px 8px; font-size: 12px; font-weight: bold; margin-left: 5px; vertical-align: top; }
    </style>
</head>
<body>
    <header class="header">
        <h1>Reports & Analytics</h1>
        <div class="header-nav">
            <a href="../index.php">Home</a> 
            <a href="index.php">orders</a>
            <a href="manage_products.php">Manage Products</a>
            <a href="view_feedback.php">View Feedback <?php if ($unread_feedback_count > 0): ?><span class="notification-badge"><?php echo $unread_feedback_count; ?></span><?php endif; ?></a>
            <a href="change_password.php">Change Password</a>
            <a href="logout.php">Logout</a>
        </div>
    </header>
    <div class="container">
        <div class="content-card">
            <h2>Filter Reports by Date</h2>
            <form action="reports.php" method="GET" class="filter-form">
                <div class="form-group"><label for="start_date">Start Date:</label><input type="date" id="start_date" name="start_date" value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>"></div>
                <div class="form-group"><label for="end_date">End Date:</label><input type="date" id="end_date" name="end_date" value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>"></div>
                <button type="submit" class="btn-filter">Filter</button>
            </form>
        </div>
        <div class="summary-grid">
            <div class="summary-card"><p class="title"><?php echo ($start_date || $end_date) ? 'Revenue for Period' : 'Total Revenue (All Time)'; ?></p><p class="value"><sup>Birr</sup><?php echo number_format($summary['total_revenue'] ?? 0, 2); ?></p></div>
            <div class="summary-card"><p class="title"><?php echo ($start_date || $end_date) ? 'Orders for Period' : 'Total Orders (All Time)'; ?></p><p class="value"><?php echo $summary['total_orders'] ?? 0; ?></p></div>
            <div class="summary-card"><p class="title">Today's Revenue</p><p class="value"><sup>Birr</sup><?php echo number_format($today_stats['today_revenue'] ?? 0, 2); ?></p></div>
            <div class="summary-card"><p class="title">Today's Orders</p><p class="value"><?php echo $today_stats['today_orders'] ?? 0; ?></p></div>
            <!-- ====== START: Updated Visitor Count Cards ====== -->
            <div class="summary-card">
                <p class="title">Today's Unique Visitors</p>
                <p class="value"><?php echo $today_visits; ?></p>
            </div>
            <div class="summary-card">
                <p class="title">Yesterday's Unique Visitors</p>
                <p class="value"><?php echo $yesterday_visits; ?></p>
            </div>
            <!-- ====== END: Updated Visitor Count Cards ====== -->
        </div>
        <div class="content-card">
            <h2>Top 5 Selling Items <?php echo ($start_date || $end_date) ? 'for Period' : '(All Time)'; ?></h2>
            <table class="data-table">
                <thead><tr><th>Item Name</th><th>Total Times Ordered</th></tr></thead>
                <tbody>
                    <?php if (!empty($top_items)): ?>
                        <?php foreach($top_items as $item): ?><tr><td><?php echo htmlspecialchars($item['product_name']); ?></td><td><?php echo $item['total_quantity']; ?></td></tr><?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2">Not enough data available for this period.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="content-card">
            <h2>Completed Orders <?php echo ($start_date || $end_date) ? 'for Period' : '(Most Recent)'; ?></h2>
            <table class="data-table">
                <thead><tr><th>Order ID</th><th>Customer Name</th><th>Total Price</th><th>Date Completed</th></tr></thead>
                <tbody>
                    <?php if (!empty($recent_completed_orders)): ?>
                        <?php foreach($recent_completed_orders as $order): ?><tr><td>#<?php echo $order['id']; ?></td><td><?php echo htmlspecialchars($order['customer_name']); ?></td><td><?php echo number_format($order['total_price'], 2); ?> Birr</td><td><?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?></td></tr><?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4">No completed orders found for this period.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>