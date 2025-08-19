<?php
// Include the visitor tracker first
require_once 'api/track_visitor.php';

// --- START: CORRECTED LOGIC FOR ADVANCED OPTIONS ---
$special_product = null;

// Create a database connection (with the correct default port 3306)
$db_server = "localhost";
$db_username = "root";
$db_password = "";
$db_name = "professor_cafe_db";
$conn = new mysqli($db_server, $db_username, $db_password, $db_name,$db_port);

if (!$conn->connect_error) {
    date_default_timezone_set('Africa/Addis_Ababa'); // Set timezone for consistency
    $today_date = date('Y-m-d');
    
    // SQL to fetch the special product
    $sql = "
        SELECT p.*
        FROM specials s
        JOIN products p ON s.product_id = p.id
        WHERE s.post_date = ? AND p.is_available = 1
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $today_date);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $special_product = $result->fetch_assoc();
            
            // Now, fetch the advanced options for this specific product
            $options_stmt = $conn->prepare("SELECT option_group, choice_name, price_change FROM product_options WHERE product_id = ?");
            if ($options_stmt) {
                $options_stmt->bind_param("i", $special_product['id']);
                $options_stmt->execute();
                $options_result = $options_stmt->get_result();
                $special_product['options'] = $options_result->fetch_all(MYSQLI_ASSOC);
                $options_stmt->close();
            }
        }
        $stmt->close();
    }
    $conn->close();
}

// Helper function to create the complex JSON data for advanced options
function build_advanced_data_options($options_array) {
    if (empty($options_array)) { return "{}"; }
    $grouped_options = [];
    foreach ($options_array as $option) {
        // Group choices by their 'option_group' (e.g., Size, Spiciness)
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
    <title>Professor Cafe - Home</title>
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
   <link rel="stylesheet" href="style.css?v=3">
    <style>
        /* Your custom styles are preserved */
        .special-section { padding: 60px 0; text-align: center; }
        .scrolling-text-container { width: 100%; overflow: hidden; padding: 20px 0; }
        .scrolling-text { display: inline-block; animation: marquee-scroll 10s linear infinite; color: var(--success-color); white-space: nowrap; padding-left: 100%; }
        .scrolling-text h2 { font-size: 2.5rem; margin: 0; }
        @keyframes marquee-scroll { 0% { transform: translateX(5%); } 100% { transform: translateX(-100%); } }
        .special-menu-card { max-width: 350px; margin: 0 auto; background: var(--white-color); border-radius: 12px; box-shadow: var(--shadow-medium); overflow: hidden; text-align: center; transition: transform 0.3s; }
        .special-menu-card:hover { transform: translateY(-10px); }
        .special-menu-card img { width: 100%; height: 240px; object-fit: cover; }
        .special-details { padding: 25px; }
        .special-details h3 { margin-top: 0; font-size: 1.8rem; }
        .special-details .price { font-size: 1.6rem; font-weight: 700; color: var(--primary-color); font-family: var(--font-primary); margin: 10px 0 20px 0; }
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
                    <li><a href="#">about us</a></li>
                    <li><a href="admin/index.php">admin</a></li>
                    <!-- Cart Icon is now managed by app.js and works here -->
                    <li><a href="#" id="cart-icon">🛒 Cart (<span id="cart-count">0</span>)</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <section class="hero">
                <h2>Welcome!!! To Professor Cafe</h2>
                <p>Your favorite spot on campus for great food and drinks. Ready to order?</p>
                <a href="menu.php" class="btn">Explore Full Menu</a>
            </section>
            
            <?php if ($special_product): ?>
            <section class="special-section">
                <div class="scrolling-text-container">
                    <div class="scrolling-text"><h2>⭐ Today's  special! ⭐</h2></div>
                </div>
                <div class="special-menu-card menu-card" 
                     data-item-name="<?php echo htmlspecialchars($special_product['name']); ?>"
                     data-base-price="<?php echo htmlspecialchars($special_product['price']); ?>"
                     data-options='<?php echo build_advanced_data_options($special_product['options']); ?>'>
                    <img src="<?php echo htmlspecialchars($special_product['image_path']); ?>" alt="<?php echo htmlspecialchars($special_product['name']); ?>">
                    <div class="special-details">
                        <h3><?php echo htmlspecialchars($special_product['name']); ?></h3>
                        <p class="price"><?php echo number_format($special_product['price'], 2); ?> Birr</p>
                        <a href="#" class="btn-order">Order Now</a>
                    </div>
                </div>
            </section>
            <?php endif; ?>
            
            <section class="category-preview">
                <h2>Browse by Category</h2>
                <div class="category-grid">
                    <a href="menu.php#fasting" class="category-button"><h3>Fasting Meals</h3><p>Healthy vegan options</p></a>
                    <a href="menu.php#non-fasting" class="category-button"><h3>Non-Fasting Meals</h3><p>Hearty & filling dishes</p></a>
                    <a href="menu.php#hot-drinks" class="category-button"><h3>Hot Drinks</h3><p>Coffee, tea, and more</p></a>
                    <a href="menu.php#soft-drinks" class="category-button"><h3>Soft Drinks & Juices</h3><p>Cold and refreshing</p></a>
                </div>
            </section>
        </div>
    </main>
    
    <!-- Modals are now needed on this page for the Special to work -->
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