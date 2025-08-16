<?php require_once 'api/track_visitor.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Order - Professor Cafe</title>
    <!-- We will use the main style.css for the header and footer -->
    <link rel="stylesheet" href="style.css">
    <style>
        /* Specific styles for this page */
        .track-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 40px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        .track-container h2 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #333;
        }
        .track-form input {
            width: 100%;
            padding: 15px;
            font-size: 18px;
            border: 2px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
            text-align: center;
            margin-bottom: 20px;
        }
        .track-form button {
            width: 100%;
            padding: 15px;
            font-size: 18px;
            font-weight: bold;
            color: white;
            background-color:blue;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .track-form button:hover {
            background-color: green;
        }
        #status-result {
            margin-top: 30px;
            padding: 20px;
            border-radius: 5px;
            display: none; /* Hidden by default */
            font-size: 18px;
            text-align: left;
        }
        #status-result.success {
            background-color: #e8f5e9;
            border: 1px solid #a5d6a7;
        }
        #status-result.error {
            background-color: #fdd;
            border: 1px solid #d9534f;
            text-align: center;
        }
        #status-result p {
            margin: 5px 0;
        }
    </style>
</head>
<body>

    <!-- Header (Consistent with other pages) -->
    <header>
        <div class="container">
            <h1 class="logo">professor Cafe</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="menu.php">Our Menu</a></li>
                    <li><a href="track_order.php">Track Order</a></li>
                    <li><a href="#">About Us</a></li>
                    <li><a href="./contact.php">Contact</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="track-container">
            <h2>Track Your Order Status</h2>
            <p>Please enter the Order ID you received after placing your order.</p>
            <form id="track-form" class="track-form">
                <input type="number" id="order_id_input" placeholder="Enter Your Order ID (e.g., 7)" required>
                <button type="submit">Track Order</button>
            </form>
            
            <!-- This div will show the result from the backend -->
            <div id="status-result"></div>
        </div>
    </main>
    
    <!-- Footer (Consistent with other pages) -->
    <footer>
        <div class="container">
            <!-- Footer content here... -->
        </div>
        <div class="footer-bottom">
            © 2025 professor cafe | All Rights Reserved
        </div>
    </footer>

    <script>
        // JavaScript to handle the form submission
        document.getElementById('track-form').addEventListener('submit', async function(event) {
            event.preventDefault(); // Stop the form from reloading the page

            const orderId = document.getElementById('order_id_input').value;
            const resultDiv = document.getElementById('status-result');
            const submitButton = this.querySelector('button');

            // Show loading state
            resultDiv.style.display = 'none';
            submitButton.disabled = true;
            submitButton.textContent = 'Searching...';

            try {
                // Prepare data to send to the backend API
                const formData = new FormData();
                formData.append('order_id', orderId);

                // Send the request to our new API endpoint
                const response = await fetch('api/check_status.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                // Display the result
                resultDiv.style.display = 'block';
                if (result.success) {
                    resultDiv.className = 'success';
                    resultDiv.innerHTML = `
                        <p>Hello, <strong>${result.data.customer_name}</strong>!</p>
                        <p>The status of your order <strong>#${result.data.id}</strong> is:</p>
                        <h3 style="text-align:center; color:#2c3e50;">${result.data.order_status}</h3>
                    `;
                } else {
                    resultDiv.className = 'error';
                    resultDiv.textContent = result.error;
                }

            } catch (error) {
                // Handle network errors
                resultDiv.style.display = 'block';
                resultDiv.className = 'error';
                resultDiv.textContent = 'A network error occurred. Please try again.';
                console.error('Fetch error:', error);
            } finally {
                // Restore button to normal state
                submitButton.disabled = false;
                submitButton.textContent = 'Track Order';
            }
        });
    </script>

</body>
</html>