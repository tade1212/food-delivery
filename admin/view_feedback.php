<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../api/db_connect.php';
header('Content-Type: text/html; charset=utf-8');

// Count unread feedback messages for notification
$unread_feedback_count = 0;
// We run the count query before any potential updates on this page
$count_result = $conn->query("SELECT COUNT(id) as unread_count FROM feedback WHERE is_read = 0");
if ($count_result) {
    $unread_feedback_count = $count_result->fetch_assoc()['unread_count'];
}

// Action: Mark a message as read if the link is clicked
if (isset($_GET['mark_read'])) {
    $feedback_id = (int)$_GET['mark_read'];
    if ($feedback_id > 0) {
        $stmt = $conn->prepare("UPDATE feedback SET is_read = 1 WHERE id = ?");
        $stmt->bind_param("i", $feedback_id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: view_feedback.php'); // Redirect to clean the URL
    exit;
}

// Action: Delete a message if the link is clicked
if (isset($_GET['delete'])) {
    $feedback_id = (int)$_GET['delete'];
    if ($feedback_id > 0) {
        $stmt = $conn->prepare("DELETE FROM feedback WHERE id = ?");
        $stmt->bind_param("i", $feedback_id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: view_feedback.php'); // Redirect to clean the URL
    exit;
}

// Fetch all feedback messages from the database, newest first
$feedback_messages = [];
$result = $conn->query("SELECT * FROM feedback ORDER BY sent_date DESC");
if ($result) {
    $feedback_messages = $result->fetch_all(MYSQLI_ASSOC);
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Feedback - Admin Panel</title>
    <style>
        body { font-family: 'Segoe UI', 'Roboto', sans-serif; background-color: #f4f7f6; margin: 0; }
        .header { background-color:black; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .header-nav { display: flex; gap: 20px; align-items: center; }
        .header-nav a { color:white; text-decoration: none; font-weight: bold; }
        .container { padding: 30px; max-width: 1000px; margin: auto; }
        .page-title { font-size: 28px; color: #333; margin-top: 0; margin-bottom: 20px; }
        .feedback-card { background: #fff; border-left: 5px solid #ccc; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-radius: 5px; display: flex; flex-direction: column; }
        .feedback-card.unread { border-left-color: #d9534f; background-color: #fffafa; }
        .card-header { padding: 15px 20px; background-color: #f9f9f9; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; font-size: 14px; }
        .card-header .sender-info strong { font-size: 16px; color: #333; }
        .card-header .sender-info span { color: #777; }
        .card-body { padding: 20px; font-size: 16px; line-height: 1.7; color: #555; }
        .card-actions { padding: 10px 20px; background-color: #f9f9f9; border-top: 1px solid #eee; text-align: right; }
        .card-actions a { text-decoration: none; font-weight: bold; padding: 6px 12px; border-radius: 4px; margin-left: 10px; font-size: 13px; }
        .btn-mark-read { background-color: #e7f3fe; color: #0275d8; }
        .btn-delete { background-color: #fdecea; color: #d9534f; }
        .no-feedback { text-align: center; padding: 40px; background-color: #fff; border-radius: 8px; color: #777; }
        .notification-badge { background-color: #e74c3c; color: white; border-radius: 10px; padding: 2px 8px; font-size: 12px; font-weight: bold; margin-left: 5px; vertical-align: top; }
    </style>
</head>
<body>
    <header class="header">
        <h1>Customer Feedback</h1>
        <div class="header-nav">
            <a href="../index.php">Home</a>
            <a href="index.php">Orders</a>
            <a href="reports.php">Reports</a>
            <a href="manage_products.php">Manage Products</a>
            <a href="change_password.php">Change Password</a>
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <div class="container">
        <h2 class="page-title">
            Received Messages
            <?php if ($unread_feedback_count > 0): ?>
                <span class="notification-badge" style="font-size: 14px;"><?php echo $unread_feedback_count; ?> Unread</span>
            <?php endif; ?>
        </h2>
        <?php if (!empty($feedback_messages)): ?>
            <?php foreach ($feedback_messages as $fb): ?>
                <div class="feedback-card <?php echo ($fb['is_read'] == 0) ? 'unread' : ''; ?>">
                    <div class="card-header">
                        <div class="sender-info">
                            <strong><?php echo htmlspecialchars($fb['customer_name']); ?></strong>
                            <br>
                            <span><?php echo htmlspecialchars($fb['customer_email']); ?></span>
                        </div>
                        <div class="date-info">
                            <?php echo date('d M Y, h:i A', strtotime($fb['sent_date'])); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php echo nl2br(htmlspecialchars($fb['message'])); ?>
                    </div>
                    <div class="card-actions">
                        <?php if ($fb['is_read'] == 0): ?>
                            <a href="view_feedback.php?mark_read=<?php echo $fb['id']; ?>" class="btn-mark-read">Mark as Read</a>
                        <?php endif; ?>
                        <a href="view_feedback.php?delete=<?php echo $fb['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this message?');">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-feedback">There are no feedback messages to display.</p>
        <?php endif; ?>
    </div>
</body>
</html>