
<?php
session_start();

// CORRECTED SYNTAX
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../api/db_connect.php';
header('Content-Type: text/html; charset=utf-8');

$message = '';
$message_type = '';
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id === 0) {
    header('Location: manage_products.php');
    exit;
}

// Handle form submission for UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $category = $_POST['category'] ?? '';
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $options = $_POST['options'] ?? [];
    
    $conn->begin_transaction();
    try {
        $image_path_update_sql = "";
        $current_image_path = $_POST['current_image_path'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../assets/images/';
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $filename = uniqid('prod_', true) . '.' . $file_extension;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                if (file_exists(__DIR__ . '/../' . $current_image_path) && strpos($current_image_path, 'default.jpg') === false) {
                    unlink(__DIR__ . '/../' . $current_image_path);
                }
                $image_path_update_sql = ", image_path = '" . 'assets/images/' . $filename . "'";
            }
        }
        
        $sql = "UPDATE products SET name = ?, description = ?, price = ?, category = ?, is_available = ? " . $image_path_update_sql . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdssi", $name, $description, $price, $category, $is_available, $product_id);
        $stmt->execute();
        $stmt->close();
        
        $stmt_delete_options = $conn->prepare("DELETE FROM product_options WHERE product_id = ?");
        $stmt_delete_options->bind_param("i", $product_id);
        $stmt_delete_options->execute();
        $stmt_delete_options->close();
        
        if (!empty($options)) {
            $stmt_options = $conn->prepare("INSERT INTO product_options (product_id, option_group, choice_name, price_change) VALUES (?, ?, ?, ?)");
            foreach ($options as $option) {
                $group = trim($option['group']);
                $choice = trim($option['choice']);
                $price_change = (float)($option['price_change'] ?? 0);
                if (!empty($group) && !empty($choice)) {
                    $stmt_options->bind_param("issd", $product_id, $group, $choice, $price_change);
                    $stmt_options->execute();
                }
            }
            $stmt_options->close();
        }
        
        $conn->commit();
        $message = "Product updated successfully!";
        $message_type = 'success';
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error updating product: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Fetch existing product data to pre-fill the form
$product = null;
$product_options = [];
$stmt_product = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt_product->bind_param("i", $product_id);
$stmt_product->execute();
$result = $stmt_product->get_result();
if ($result->num_rows === 1) {
    $product = $result->fetch_assoc();
    $stmt_product->close();
    $stmt_options = $conn->prepare("SELECT * FROM product_options WHERE product_id = ?");
    $stmt_options->bind_param("i", $product_id);
    $stmt_options->execute();
    $product_options = $stmt_options->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_options->close();
} else {
    header('Location: manage_products.php');
    exit;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - <?php echo htmlspecialchars($product['name']); ?></title>
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
        .current-image { max-width: 100px; border-radius: 5px; margin-bottom: 10px; }
        .availability-label { display: flex; align-items: center; gap: 10px; }
    </style>
</head>
<body>
    <header class="header">
        <h1>Edit Product</h1>
        <a href="manage_products.php">← Back to Product List</a>
    </header>
    
    <div class="container">
        <div class="content-card">
            <h2>Editing: <?php echo htmlspecialchars($product['name']); ?></h2>
            <?php if (!empty($message)): ?> <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div> <?php endif; ?>
            <form action="edit_product.php?id=<?php echo $product_id; ?>" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Base Price</label>
                    <input type="number" name="price" step="0.01" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" required>
                        <option value="Fasting" <?php if($product['category'] == 'Fasting') echo 'selected'; ?>>Fasting</option>
                        <option value="Non-Fasting" <?php if($product['category'] == 'Non-Fasting') echo 'selected'; ?>>Non-Fasting</option>
                        <option value="Hot-Drinks" <?php if($product['category'] == 'Hot-Drinks') echo 'selected'; ?>>Hot Drinks</option>
                        <option value="Soft-Drinks" <?php if($product['category'] == 'Soft-Drinks') echo 'selected'; ?>>Soft Drinks & Juices</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Availability</label>
                    <label class="availability-label"><input type="checkbox" name="is_available" value="1" <?php if($product['is_available']) echo 'checked'; ?>>Product is available</label>
                </div>
                <div class="form-group">
                    <label>Change Product Image (Optional)</label>
                    <p><img src="../<?php echo htmlspecialchars($product['image_path']); ?>" alt="Current Image" class="current-image"></p>
                    <input type="file" name="image" accept="image/png, image/jpeg, image/gif">
                    <input type="hidden" name="current_image_path" value="<?php echo htmlspecialchars($product['image_path']); ?>">
                </div>

                <div class="form-group">
                    <label>Product Options (with Price Add-ons)</label>
                    <div id="options-container">
                        <?php foreach($product_options as $index => $option): ?>
                        <div class="option-row">
                            <input type="text" name="options[<?php echo $index; ?>][group]" placeholder="Option Group" value="<?php echo htmlspecialchars($option['option_group']); ?>">
                            <input type="text" name="options[<?php echo $index; ?>][choice]" placeholder="Choice Name" value="<?php echo htmlspecialchars($option['choice_name']); ?>">
                            <input type="number" name="options[<?php echo $index; ?>][price_change]" step="0.01" placeholder="Price Add-on" value="<?php echo htmlspecialchars($option['price_change']); ?>">
                            <button type="button" class="btn-remove-option" onclick="this.parentElement.remove()">X</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn-add-option" onclick="addOption()">+ Add New Option</button>
                </div>

                <button type="submit" class="btn-submit">Save Changes</button>
            </form>
        </div>
    </div>
    <script>
        let optionIndex = <?php echo count($product_options); ?>;
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