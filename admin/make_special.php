<?php
session_start();

// ====== THIS IS THE CORRECTED LOGIC ======
// The check for the session variable is now written in a way that all PHP versions accept.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../api/db_connect.php';

// Check if an ID was passed in the URL
if (isset($_GET['id']) && $conn) {
    $product_id = (int)$_GET['id'];
    $today_date = date('Y-m-d');

    $conn->begin_transaction();
    try {
        // Step 1: Delete any existing special for today to ensure only one.
        $stmt_delete = $conn->prepare("DELETE FROM specials WHERE post_date = ?");
        $stmt_delete->bind_param("s", $today_date);
        $stmt_delete->execute();
        $stmt_delete->close();
        
        // Step 2: Insert the new special for today.
        $stmt_insert = $conn->prepare("INSERT INTO specials (product_id, post_date) VALUES (?, ?)");
        $stmt_insert->bind_param("is", $product_id, $today_date);
        $stmt_insert->execute();
        $stmt_insert->close();

        // If successful, commit changes
        $conn->commit();

    } catch (Exception $e) {
        // If anything fails, roll back
        $conn->rollback();
        // Optional: You can create a system to log this error message: $e->getMessage();
    }
    $conn->close();
}

// Redirect back to the products list page regardless of success or failure
header('Location: manage_products.php');
exit;
?>