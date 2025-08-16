<?php
session_start();

// Security check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../api/db_connect.php';

if (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];

    if ($conn) {
        $conn->begin_transaction();
        try {
            // 1. Get the image path before deleting the record
            $stmt_select = $conn->prepare("SELECT image_path FROM products WHERE id = ?");
            $stmt_select->bind_param("i", $product_id);
            $stmt_select->execute();
            $result = $stmt_select->get_result();

            if ($result->num_rows === 1) {
                $product = $result->fetch_assoc();
                $image_file_path = __DIR__ . '/../' . $product['image_path'];

                // 2. Delete the actual image file from the server
                // Check if file exists and is not the default image
                if (file_exists($image_file_path) && strpos($product['image_path'], 'default.jpg') === false) {
                    unlink($image_file_path);
                }
            }
            $stmt_select->close();

            // 3. Delete the product from the database.
            // Because of "ON DELETE CASCADE" in our database setup,
            // this will also automatically delete any options associated with this product.
            $stmt_delete = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt_delete->bind_param("i", $product_id);
            $stmt_delete->execute();
            $stmt_delete->close();

            // If all steps were successful, commit the transaction
            $conn->commit();

        } catch (Exception $e) {
            // If anything failed, roll back the changes
            $conn->rollback();
            // Optional: You can create an error message system here
        }
        $conn->close();
    }
}

// 4. Redirect the admin back to the product list page
header('Location: manage_products.php');
exit;
?>