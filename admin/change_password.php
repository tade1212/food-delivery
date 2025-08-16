<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../api/db_connect.php';
header('Content-Type: text/html; charset=utf-8');
$unread_feedback_count = 0;
$count_result = $conn->query("SELECT COUNT(id) as unread_count FROM feedback WHERE is_read = 0");
if ($count_result) { $unread_feedback_count = $count_result->fetch_assoc()['unread_count']; }
$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password']; $new_password = $_POST['new_password']; $confirm_password = $_POST['confirm_password']; $username = $_SESSION['admin_username'];
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) { $message = "All fields are required."; $message_type = 'error'; }
    elseif ($new_password !== $confirm_password) { $message = "Passwords do not match."; $message_type = 'error'; }
    elseif (strlen($new_password) < 8) { $message = "Password must be at least 8 characters."; $message_type = 'error'; }
    else {
        $stmt = $conn->prepare("SELECT password FROM admins WHERE username = ?"); $stmt->bind_param("s", $username); $stmt->execute(); $result = $stmt->get_result(); $admin = $result->fetch_assoc(); $stmt->close();
        if ($admin && password_verify($current_password, $admin['password'])) {
            $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE admins SET password = ? WHERE username = ?"); $update_stmt->bind_param("ss", $new_password_hashed, $username);
            if ($update_stmt->execute()) { $message = "Password updated successfully!"; $message_type = 'success'; }
            else { $message = "Error updating password."; $message_type = 'error'; }
            $update_stmt->close();
        } else { $message = "Incorrect current password."; $message_type = 'error'; }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Admin Panel</title>
    <style>
        body { font-family: 'Segoe UI', 'Roboto', sans-serif; background-color: #f4f7f6; margin: 0; }
        .header { background-color:black; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .header-nav { display: flex; gap: 20px; align-items: center; }
        .header-nav a { color: white; text-decoration: none; font-weight: bold; }
        .container { padding: 30px; max-width: 600px; margin: auto; }
        .content-card { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
        .content-card h2 { font-size: 20px; color: #d9534f; margin-top: 0; border-bottom: 1px solid #ecf0f1; padding-bottom: 15px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 16px; box-sizing: border-box; }
        .btn-submit { background-color: #5cb85c; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 16px; width: 100%; }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: 600; }
        .message.success { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .message.error { background-color: #fdd; color: #d9534f; border: 1px solid #d9534f; }
        .notification-badge { background-color: #e74c3c; color: white; border-radius: 10px; padding: 2px 8px; font-size: 12px; font-weight: bold; margin-left: 5px; vertical-align: top; }
    </style>
</head>
<body>
    <header class="header">
        <h1>Change Admin Password</h1>
        <div class="header-nav">
            <a href="index.php">Orders</a>
            <a href="reports.php">Reports</a>
            <a href="manage_products.php">Manage Products</a>
            <a href="view_feedback.php">
                View Feedback
                <?php if ($unread_feedback_count > 0): ?>
                    <span class="notification-badge"><?php echo $unread_feedback_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="logout.php">Logout</a>
        </div>
    </header>
    <div class="container">
        <div class="content-card">
            <h2>Update Your Password</h2>
            <?php if (!empty($message)): ?> <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div> <?php endif; ?>
            <form action="change_password.php" method="POST">
                <div class="form-group"><label for="current_password">Current Password</label><input type="password" id="current_password" name="current_password" required></div>
                <div class="form-group"><label for="new_password">New Password (min. 8 characters)</label><input type="password" id="new_password" name="new_password" required></div>
                <div class="form-group"><label for="confirm_password">Confirm New Password</label><input type="password" id="confirm_password" name="confirm_password" required></div>
                <button type="submit" class="btn-submit">Update Password</button>
            </form>
        </div>
    </div>
</body>
</html>