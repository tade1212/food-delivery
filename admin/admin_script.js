document.addEventListener("DOMContentLoaded", function () {
  // --- Real-time New Order Notification ---
  const dashboardLink = document.querySelector(
    '.header-nav a[href="index.php"]'
  );
  let currentOrderCount = 0;

  // Create an audio object for the notification sound
  const notificationSound = new Audio("../assets/sounds/notification.mp3"); // We will add this sound file later

  // Function to check for new orders
  async function checkNewOrders() {
    try {
      // Fetch the count from our new API endpoint
      const response = await fetch("../api/check_new_orders.php");
      const data = await response.json();

      if (data.success) {
        const newCount = data.new_order_count;
        let badge = document.getElementById("order-notification-badge");

        // If a badge doesn't exist, create one
        if (!badge && dashboardLink) {
          badge = document.createElement("span");
          badge.className = "notification-badge";
          badge.id = "order-notification-badge";
          dashboardLink.appendChild(badge);
        }

        if (badge) {
          // If the count has increased, play a sound
          if (newCount > currentOrderCount && currentOrderCount !== 0) {
            notificationSound
              .play()
              .catch((e) => console.error("Error playing sound:", e));
          }

          // Update the badge display
          if (newCount > 0) {
            badge.textContent = newCount;
            badge.style.display = "inline-block";
          } else {
            badge.style.display = "none";
          }
        }

        // Update the current count for the next check
        currentOrderCount = newCount;
      }
    } catch (error) {
      console.error("Error checking for new orders:", error);
    }
  }

  // Check immediately on page load, and then every 15 seconds
  checkNewOrders();
  setInterval(checkNewOrders, 15000); // 15000 milliseconds = 15 seconds
});
