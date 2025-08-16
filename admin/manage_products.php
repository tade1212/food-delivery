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
$count_result = $conn->query("SELECT COUNT(id) as unread_count FROM feedback WHERE is_read = 0");
if ($count_result) {
    $unread_feedback_count = $count_result->fetch_assoc()['unread_count'];
}

// Fetch all products from the database to display in the table
$products = [];
$result = $conn->query("SELECT * FROM products ORDER BY category, name");
if ($result) {
    $products = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin Panel</title>
    <style>
        body { font-family: 'Segoe UI', 'Roboto', sans-serif; background-color: #f4f7f6; margin: 0; }
        .header { background-color:black; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .header-nav { display: flex; gap: 20px; align-items: center; }
        .header-nav a { color:white; text-decoration: none; font-weight: bold; }
        .container { padding: 30px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .page-header h2 { margin: 0; color: #333; }
        .btn-add-new { background-color: #5cb85c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; transition: background-color 0.3s; }
        .btn-add-new:hover { background-color: #4cae4c; }
        .content-card { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 15px; text-align: left; border-bottom: 1px solid #ecf0f1; vertical-align: middle; }
        .data-table thead th { background-color: #f9f9f9; font-weight: 600; }
        .data-table tbody tr:hover { background-color: #fafcff; }
        .actions { white-space: nowrap; }
        .actions a { margin-right: 10px; text-decoration: none; font-weight: bold; padding: 5px 10px; border-radius: 4px; transition: background-color 0.3s, color 0.3s; display: inline-block; margin-top: 5px;}
        .actions .edit-link { color: #0275d8; background-color: #e7f3fe; }
        .actions .edit-link:hover { background-color: #0275d8; color: white; }
        .actions .delete-link { color: #d9534f; background-color: #fdecea; }
        .actions .delete-link:hover { background-color: #d9534f; color: white; }
        .actions .special-link { color: #e67e22; background-color: #fef5e7; }
        .actions .special-link:hover { background-color: #e67e22; color: white; }
        .status-available { color: #2e7d32; font-weight: bold; }
        .status-unavailable { color: #7f8c8d; font-style: italic; }
        .product-image { width: 60px; height: 60px; object-fit: cover; border-radius: 5px; }
        .notification-badge { background-color: #e74c3c; color: white; border-radius: 10px; padding: 2px 8px; font-size: 12px; font-weight: bold; margin-left: 5px; vertical-align: top; }
    </style>
</head>
<body>
    <header class="header">
        <h1>Manage Products</h1>
        <div class="header-nav">
            <a href="../index.php">Home</a>
            <a href="index.php">Orders</a>
            <a href="reports.php">Reports</a>
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
        <div class="page-header">
            <h2>All Menu Items</h2>
            <a href="add_product.php" class="btn-add-new">+ Add New Product</a>
        </div>
        
        <div class="content-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Availability</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><img src="../<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image"></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td><?php echo number_format($product['price'], 2); ?> Birr</td>
                                <td>
                                    <?php if ($product['is_available']): ?>
                                        <span class="status-available">Available</span>
                                    <?php else: ?>
                                        <span class="status-unavailable">Unavailable</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="edit-link">Edit</a>
                                    <a href="delete_product.php?id=<?php echo $product['id']; ?>" class="delete-link" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                                    <a href="make_special.php?id=<?php echo $product['id']; ?>" class="special-link" onclick="return confirm('Make this the Special of the Day? This will replace any other special.');">Make Special</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center;">No products found. Click "Add New Product" to get started.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php
if ($conn) {
    $conn->close();
}
?>