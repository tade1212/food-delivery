<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Complete Database Setup</title>";
echo "<style>body { font-family: sans-serif; background-color: #f4f4f4; color: #333; line-height: 1.6; padding: 20px; } .container { max-width: 800px; margin: auto; background: #fff; padding: 20px; border-radius: 5px; } .success { color: #28a745; } .error { color: #d9534f; } pre { background: #eee; padding: 10px; border-radius: 3px; white-space: pre-wrap;}</style>";
echo "</head><body><div class='container'>";
echo "<h1>Professor Cafe: Complete Database & Table Setup</h1>";

// --- Database Connection Details ---
$db_server = "localhost";
$db_username = "root";
$db_password = "";
$db_name = "professor_cafe_db";

// 1. Connect to MySQL Server
$conn = new mysqli($db_server, $db_username, $db_password);
if ($conn->connect_error) { die("<p class='error'>Connection Failed: " . $conn->connect_error . "</p>"); }
echo "<p class='success'>Successfully connected to MySQL server.</p>";

// 2. Create Database
echo "<h2>Creating Database '{$db_name}'...</h2>";
$sql_create_db = "CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;";
if ($conn->query($sql_create_db) === TRUE) {
    echo "<p class='success'>Database '{$db_name}' created successfully or already exists.</p>";
} else { die("<p class='error'>Error creating database: " . $conn->error . "</p>"); }
$conn->select_db($db_name);

// 3. Create All Tables
echo "<h2>Creating All Tables...</h2>";

$sql_commands = [
    "admins" => "
    CREATE TABLE `admins` (
      `id` int(11) NOT NULL AUTO_INCREMENT, `username` varchar(50) NOT NULL, `password` varchar(255) NOT NULL, PRIMARY KEY (`id`), UNIQUE KEY `username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "orders" => "
    CREATE TABLE `orders` (
      `id` int(11) NOT NULL AUTO_INCREMENT, `customer_name` varchar(100) NOT NULL, `customer_phone` varchar(20) NOT NULL, `customer_address` text NOT NULL, `total_price` decimal(10,2) NOT NULL, `order_status` varchar(50) NOT NULL DEFAULT 'Pending', `order_date` timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "products" => "
    CREATE TABLE `products` (
      `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(255) NOT NULL, `description` text NOT NULL, `price` decimal(10,2) NOT NULL, `category` varchar(100) NOT NULL, `image_path` varchar(255) DEFAULT 'assets/images/default.jpg', `is_available` tinyint(1) NOT NULL DEFAULT 1, PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "order_items" => "
    CREATE TABLE `order_items` (
      `item_id` int(11) NOT NULL AUTO_INCREMENT, `order_id` int(11) NOT NULL, `product_name` varchar(255) NOT NULL, `quantity` int(11) NOT NULL, `price` decimal(10,2) NOT NULL, PRIMARY KEY (`item_id`), KEY `order_id` (`order_id`),
      CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "product_options" => "
    CREATE TABLE `product_options` (
      `id` int(11) NOT NULL AUTO_INCREMENT, `product_id` int(11) NOT NULL, `option_name` varchar(255) NOT NULL, `choices` text NOT NULL, PRIMARY KEY (`id`),
      CONSTRAINT `product_options_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "feedback" => "
    CREATE TABLE `feedback` (
      `id` int(11) NOT NULL AUTO_INCREMENT, `customer_name` varchar(255) NOT NULL, `customer_email` varchar(255) DEFAULT NULL, `message` text NOT NULL, `is_read` tinyint(1) NOT NULL DEFAULT 0, `sent_date` timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "daily_visits" => "
    CREATE TABLE `daily_visits` (
      `id` INT NOT NULL AUTO_INCREMENT, `visit_date` DATE NOT NULL, `visit_count` INT NOT NULL DEFAULT 1, PRIMARY KEY (`id`), UNIQUE KEY `visit_date` (`visit_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    "visitor_log" => "
    CREATE TABLE `visitor_log` (
      `id` int(11) NOT NULL AUTO_INCREMENT, `ip_address` varchar(45) NOT NULL, `visit_date` date NOT NULL, PRIMARY KEY (`id`), UNIQUE KEY `unique_visitor_per_day` (`ip_address`,`visit_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "specials" => "
    CREATE TABLE `specials` (
      `id` INT NOT NULL AUTO_INCREMENT, `product_id` INT NOT NULL, `post_date` DATE NOT NULL, PRIMARY KEY (`id`), UNIQUE KEY `post_date` (`post_date`),
      CONSTRAINT `specials_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

foreach ($sql_commands as $name => $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "<p class='success'>Table '{$name}' created successfully.</p>";
    } else {
        echo "<p class='error'>Error creating table '{$name}': " . $conn->error . "</p>";
    }
}

// 4. Insert Default Admin User
echo "<h2>Inserting Default Admin User...</h2>";
$admin_user = 'admin';
$admin_pass_hashed = password_hash('password123', PASSWORD_DEFAULT);
$stmt_check = $conn->prepare("SELECT id FROM admins WHERE username = ?");
$stmt_check->bind_param("s", $admin_user);
$stmt_check->execute();
$result = $stmt_check->get_result();
if ($result->num_rows === 0) {
    $stmt_insert = $conn->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
    $stmt_insert->bind_param("ss", $admin_user, $admin_pass_hashed);
    if ($stmt_insert->execute()) {
        echo "<p class='success'>Default admin user ('admin' / 'password123') created.</p>";
    } else { echo "<p class='error'>Error creating admin user: " . $stmt_insert->error . "</p>"; }
    $stmt_insert->close();
} else { echo "<p class='success'>Default admin user ('admin') already exists.</p>"; }
$stmt_check->close();

echo "<h2>Setup Complete!</h2>";
echo "<p>Your database and all tables are ready. Please **DELETE THIS FILE (`final_setup.php`)** now for security.</p>";
echo "</div></body></html>";

$conn->close();
?>