<?php
require_once 'api/track_visitor.php';

// ====== START: CORRECTED DATABASE CONNECTION AND LOGIC ======
$db_server = "localhost";
$db_username = "root";
$db_password = "";
$db_name = "professor_cafe_db";
// NOTE: After reinstalling XAMPP, you are now on the default port 3306.
// If you change it back to 3307 in the future, you must add the port variable here.
$conn = new mysqli($db_server, $db_username, $db_password, $db_name);

if ($conn->connect_error) { 
    die("Menu connection failed: " . $conn->connect_error); 
}

// First, fetch all available products
$products_result = $conn->query("SELECT * FROM products WHERE is_available = 1 ORDER BY category, name");
$products = $products_result->fetch_all(MYSQLI_ASSOC);

// Second, fetch all advanced options
$options_result = $conn->query("SELECT * FROM product_options ORDER BY product_id");
$options_by_product = [];
if ($options_result) { 
    while ($option = $options_result->fetch_assoc()) { 
        // Group all options by the product they belong to
        $options_by_product[$option['product_id']][] = $option; 
    } 
}

// Third, combine the products with their respective options
$menu_data = [];
foreach ($products as $product) { 
    $product['options'] = $options_by_product[$product['id']] ?? []; 
    $menu_data[] = $product; 
}

// Finally, group the complete product data by category for display
$menu_by_category = [];
foreach ($menu_data as $item) { 
    $menu_by_category[$item['category']][] = $item; 
}
$conn->close();

// Helper function to create the complex JSON data for the new advanced options
function build_advanced_data_options($options_array) {
    if (empty($options_array)) { return "{}"; }
    $grouped_options = [];
    foreach ($options_array as $option) {
        // This now correctly uses the 'option_group' and 'choice_name' columns
        $grouped_options[$option['option_group']][] = [
            'choice' => $option['choice_name'],
            'price' => $option['price_change']
        ];
    }
    return htmlspecialchars(json_encode($grouped_options), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Menu - Professor Cafe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .modal-price-footer { margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; text-align: right; font-size: 1.5rem; }
        .modal-price-footer strong { color: var(--primary-color); font-family: var(--font-primary); }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1 class="logo">professor Cafe</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="menu.php">Our Menu</a></li>
                    <li><a href="track_order.php">Track Order</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="#" id="cart-icon">🛒 Cart (<span id="cart-count">0</span>)</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main>
        <div class="container">
            <section class="menu-page-title"><h2>Our Full Menu</h2><p>All prices are in Birr. Select options to see the final price.</p></section>
            <?php
            $categories = ['Fasting' => 'Fasting Food (የጾም ምግቦች)', 'Non-Fasting' => 'Non-Fasting Food (የፍስክ ምግቦች)', 'Hot-Drinks' => 'Hot Drinks (ትኩስ መጠጦች)', 'Soft-Drinks' => 'Soft Drinks & Juices (ለስላሳ)'];
            foreach ($categories as $category_key => $category_title):
                if (!empty($menu_by_category[$category_key])):
            ?>
            <section id="<?php echo htmlspecialchars(strtolower($category_key)); ?>" class="menu-category">
                <h3 class="category-title"><?php echo $category_title; ?></h3>
                <div class="menu-list-grid">
                    <?php foreach ($menu_by_category[$category_key] as $product): ?>
                    <div class="menu-card" data-item-name="<?php echo htmlspecialchars($product['name']); ?>" data-base-price="<?php echo $product['price']; ?>" data-options='<?php echo build_advanced_data_options($product['options']); ?>'>
                        <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="menu-card-image">
                        <div class="menu-card-content">
                            <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                            <p class="description"><?php echo htmlspecialchars($product['description']); ?></p>
                            <p class="price"><?php echo number_format($product['price'], 2); ?> Birr</p>
                            <a href="#" class="btn-order">Order Now</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; endforeach; ?>
        </div>
    </main>
    <div id="cart-modal" class="cart-modal">
        <div class="cart-modal-content">
            <span class="close-button">×</span>
            <h2>Your Order</h2>
            <div id="cart-items"><p>Your cart is empty.</p></div>
            <h3 id="cart-total">Total: 0.00 Birr</h3>
            <a href="checkout.html" class="btn" id="checkout-btn">Proceed to Checkout</a>
        </div>
    </div>
    <div id="options-modal" class="options-modal">
    <div class="options-modal-content">
        <span class="close-options-button">×</span>
        <h2 id="options-modal-title">Customize Item</h2>
        <form id="options-form">
            <div id="modal-options-content"></div>
            <div class="form-group">
                <label for="quantity">Quantity:</label>
                <input type="number" id="quantity" value="1" min="1" required>
            </div>
            <div class="modal-price-footer">
                <strong>Total Price: <span id="modal-total-price">0.00</span> Birr</strong>
            </div>
            <button type="submit" class="btn-modal-submit">Add to Cart</button>
        </form>
    </div>
</div>
   <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section about">
                    <h2 class="logo-text"><span>Professor</span> Cafe</h2>
                    <p>Your favorite spot on campus for fresh, fast, and delicious meals.</p>
                    <div class="contact-info">
                        <p><i class="fas fa-phone"></i> +251 912 345 678</p>
                        <p><i class="fas fa-envelope"></i> professor@gmail.com</p>
                    </div>
                </div>
                <div class="footer-section social-links">
                    <h2>Follow Us</h2>
                    <div class="socials">
                        <a href="#" target="_blank" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" target="_blank" title="Telegram"><i class="fab fa-telegram"></i></a>
                        <a href="#" target="_blank" title="TikTok"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© 2025 Professor Cafe | All Rights Reserved</span>
            <a href="admin/login.php" class="admin-link">Admin Panel</a>
        </div>
    </footer>
    <div id="toast-container"></div>
 <script src="app.js?v=10"></script>
</body>
</html>