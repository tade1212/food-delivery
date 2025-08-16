<?php
session_start();

// CORRECTED SYNTAX
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';
$redirect = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../api/db_connect.php';
    header('Content-Type: text/html; charset=utf-8');

    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $category = $_POST['category'] ?? '';
    $options = $_POST['options'] ?? [];

    $conn->begin_transaction();
    try {
        $image_path = 'assets/images/default.jpg';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../assets/images/';
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($file_extension, $allowed)) { throw new Exception("Invalid file type."); }
            $filename = uniqid('prod_', true) . '.' . $file_extension;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                $image_path = 'assets/images/' . $filename;
            } else { throw new Exception("Failed to upload file."); }
        }

        $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, image_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdss", $name, $description, $price, $category, $image_path);
        if (!$stmt->execute()) { throw new Exception("Error inserting product."); }
        $product_id = $conn->insert_id;
        $stmt->close();

        if (!empty($options)) {
            $stmt_options = $conn->prepare("INSERT INTO product_options (product_id, option_group, choice_name, price_change) VALUES (?, ?, ?, ?)");
            foreach ($options as $option) {
                $group = trim($option['group']);
                $choice = trim($option['choice']);
                $price_change = (float)($option['price_change'] ?? 0);
                if (!empty($group) && !empty($choice)) {
                    $stmt_options->bind_param("issd", $product_id, $group, $choice, $price_change);
                    if (!$stmt_options->execute()) { throw new Exception("Error inserting options."); }
                }
            }
            $stmt_options->close();
        }

        $conn->commit();
        $message = "Product with advanced options added successfully! Redirecting...";
        $message_type = 'success';
        $redirect = true;
    } catch (Exception $e) {
        $conn->rollback();
        $message = "An error occurred: " . $e->getMessage();
        $message_type = 'error';
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - Admin Panel</title>
    <?php if ($redirect): ?>
        <meta http-equiv="refresh" content="2;url=manage_products.php">
    <?php endif; ?>
    <style>
        body { font-family: 'Segoe UI', 'Roboto', sans-serif; background-color: #f4f7f6; margin: 0; }
        .header { background-color: #333; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .header a { color: #d9534f; text-decoration: none; font-weight: bold; }
        .container { padding: 30px; max-width: 900px; margin: auto; }
        .content-card { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
        .content-card h2 { font-size: 20px; color: #d9534f; margin-top: 0; border-bottom: 1px solid #ecf0f1; padding-bottom: 15px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 16px; box-sizing: border-box; }
        .form-group textarea { min-height: 100px; }
        .btn-submit { background-color: #5cb85c; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 16px; width: 100%; }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: 600; }
        .message.success { background-color: #e8f5e9; color: #2e7d32; }
        .message.error { background-color: #fdd; color: #d9534f; }
        #options-container { margin-top: 10px; border: 1px solid #eee; padding: 20px; border-radius: 5px; }
        .option-row { display: grid; grid-template-columns: 2fr 3fr 1fr auto; gap: 10px; margin-bottom: 10px; align-items: center; }
        .option-row input { font-size: 14px; padding: 8px; }
        .btn-remove-option, .btn-add-option { background-color: #d9534f; color: white; border: none; border-radius: 5px; padding: 8px 12px; cursor: pointer; }
        .btn-add-option { background-color: #0275d8; margin-top: 10px; }
    </style>
</head>
<body>
    <header class="header">
        <h1>Add New Product (Advanced)</h1>
        <a href="manage_products.php">← Back to Product List</a>
    </header>
    <div class="container">
        <div class="content-card">
            <h2>New Product Details</h2>
            <?php if (!empty($message)): ?> <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div> <?php endif; ?>
            <form action="add_product.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label>Base Price (for the default/smallest version)</label>
                    <input type="number" name="price" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" required>
                        <option value="Fasting">Fasting</option>
                        <option value="Non-Fasting">Non-Fasting</option>
                        <option value="Hot-Drinks">Hot Drinks</option>
                        <option value="Soft-Drinks">Soft Drinks & Juices</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Product Image</label>
                    <input type="file" name="image" accept="image/png, image/jpeg, image/gif">
                </div>

                <div class="form-group">
                    <label>Product Options (with Price Add-ons)</label>
                    <div id="options-container">
                        <!-- JS will add rows here -->
                    </div>
                    <button type="button" class="btn-add-option" onclick="addOption()">+ Add New Option</button>
                </div>

                <button type="submit" class="btn-submit">Save Product</button>
            </form>
        </div>
    </div>
    <script>
        let optionIndex = 0;
        function addOption() {
            const container = document.getElementById('options-container');
            const newRow = document.createElement('div');
            newRow.className = 'option-row';
            newRow.innerHTML = `
                <input type="text" name="options[${optionIndex}][group]" placeholder="Option Group (e.g., Size)">
                <input type="text" name="options[${optionIndex}][choice]" placeholder="Choice Name (e.g., Large)">
                <input type="number" name="options[${optionIndex}][price_change]" step="0.01" placeholder="Price Add-on (+/-)">
                <button type="button" class="btn-remove-option" onclick="this.parentElement.remove()">X</button>
            `;
            container.appendChild(newRow);
            optionIndex++;
        }
    </script>
</body>
</html>