<?php require_once 'api/track_visitor.php'; ?>
<?php
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // We don't need the full db_connect.php, just a simple connection
    $db_server = "localhost";
    $db_username = "root";
    $db_password = "";
    $db_name = "professor_cafe_db";
    $conn = new mysqli($db_server, $db_username, $db_password, $db_name);

    if ($conn->connect_error) {
        $message = "Could not connect to the database. Please try again later.";
        $message_type = 'error';
    } else {
        $name = $_POST['customer_name'] ?? '';
        $email = $_POST['customer_email'] ?? '';
        $user_message = $_POST['message'] ?? '';

        // Basic validation
        if (!empty($name) && !empty($user_message)) {
            // Validate email format
            if (empty($email) || filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $stmt = $conn->prepare("INSERT INTO feedback (customer_name, customer_email, message) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $email, $user_message);
                
                if ($stmt->execute()) {
                    $message = "Thank you! Your feedback has been sent successfully.";
                    $message_type = 'success';
                } else {
                    $message = "Sorry, there was an error sending your message.";
                    $message_type = 'error';
                }
                $stmt->close();
            } else {
                $message = "Please enter a valid email address.";
                $message_type = 'error';
            }
        } else {
            $message = "Please fill in your name and message.";
            $message_type = 'error';
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Professor Cafe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .contact-container { max-width: 700px; margin: 50px auto; padding: 40px; background: #fff; border-radius: 8px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .contact-container h2 { text-align: center; margin-top: 0; margin-bottom: 20px; color: #333; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 16px; box-sizing: border-box; }
        .form-group textarea { resize: vertical; min-height: 120px; }
        .btn-submit { display: block; width: 100%; padding: 15px; font-size: 18px; font-weight: bold; color: white; background-color: green; border: none; border-radius: 5px; cursor: pointer; transition: background-color 0.3s; }
        .btn-submit:hover { background-color: blue; }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: 600; text-align: center; }
        .message.success { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .message.error { background-color: #fdd; color: #d9534f; border: 1px solid #d9534f; }
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
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="contact-container">
            <h2>Send Us Your Feedback</h2>
            
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form action="contact.php" method="POST">
                <div class="form-group">
                    <label for="customer_name">Your Name</label>
                    <input type="text" id="customer_name" name="customer_name" required>
                </div>
                <div class="form-group">
                    <label for="customer_email">Your Email (Optional, for replies)</label>
                    <input type="email" id="customer_email" name="customer_email">
                </div>
                <div class="form-group">
                    <label for="message">Your Message</label>
                    <textarea id="message" name="message" required></textarea>
                </div>
                <button type="submit" class="btn-submit">Send Message</button>
            </form>
        </div>
    </main>

    <footer>
    <div class="container">
        <div class="footer-content">

            <!-- Section 1: About the Cafe -->
            <div class="footer-section about">
                <h2 class="logo-text"><span>Professor</span> Cafe</h2>
                <p>
                    Your favorite spot on campus for fresh, fast, and delicious meals.
                </p>
                <div class="contact-info">
                    <p><i class="fas fa-phone"></i> +251 912 345 678</p>
                    <p><i class="fas fa-envelope"></i> professor@gmail.com</p>
                    <!-- <p><i class="fas fa-map-marker-alt"></i> Main Campus, Building A, Mekelle</p> -->
                </div>
            </div>

            <!-- Section 2: Social Media Links -->
            <div class="footer-section social-links">
                <h2>Follow Us</h2>
                <div class="socials">
                    <!-- Using Font Awesome icons instead of images -->
                    <a href="#" target="_blank" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" target="_blank" title="Telegram"><i class="fab fa-telegram"></i></a>
                    <a href="#" target="_blank" title="TikTok"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>
            
            <!-- Section 3: Google Map -->
        

        </div>
    </div>
    <div class="footer-bottom">
        <span>© 2025 Professor Cafe | All Rights Reserved</span>
        <a href="admin/login.php" class="admin-link">Admin Panel</a>
    </div>
</footer>
</body>
</html>