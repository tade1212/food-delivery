document.addEventListener("DOMContentLoaded", function () {
  // --- 1. SELECT ELEMENTS ---
  const checkoutForm = document.getElementById("checkout-form");
  const summaryItemsContainer = document.getElementById("order-summary-items");
  const summaryTotalElement = document.getElementById("summary-total");

  // --- 2. GET CART DATA ---
  const cartData = sessionStorage.getItem("professorCafeCart");
  if (!cartData || cartData === "[]") {
    alert("Your cart is empty. Redirecting to the menu.");
    window.location.href = "menu.php";
    return;
  }
  const cart = JSON.parse(cartData);

  // --- 3. DISPLAY THE CART SUMMARY ---
  let total = 0;
  summaryItemsContainer.innerHTML = "";
  cart.forEach((item) => {
    const itemElement = document.createElement("div");
    itemElement.classList.add("order-summary-item");
    itemElement.innerHTML = `<span>${item.name} (x${
      item.quantity
    })</span><span>${(item.price * item.quantity).toFixed(2)} Birr</span>`;
    summaryItemsContainer.appendChild(itemElement);
    total += item.price * item.quantity;
  });
  summaryTotalElement.textContent = `Total: ${total.toFixed(2)} Birr`;

  // --- 4. HANDLE THE FORM SUBMISSION ---
  if (checkoutForm) {
    checkoutForm.addEventListener("submit", function (event) {
      event.preventDefault();
      const placeOrderBtn = document.getElementById("place-order-btn");
      placeOrderBtn.disabled = true;
      placeOrderBtn.textContent = "Placing Order...";

      const customerDetails = {
        name: document.getElementById("name").value,
        phone: document.getElementById("phone").value,
        address: document.getElementById("address").value,
      };

      const orderData = { customerDetails: customerDetails, cartItems: cart };

      fetch("api/place_order.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(orderData),
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error(
              `Network response was not ok. Status: ${response.status}`
            );
          }
          return response.json();
        })
        .then((result) => {
          if (result.success) {
            // ====== START: IMPROVED NOTIFICATION ======
            // Create a more detailed message with HTML
            const successMessage = `
                        <strong>Order Placed Successfully!</strong><br>
                        Your Order ID is: <strong>#${result.order_id}</strong><br>
                        Please save this ID to track your order.
                    `;
            // Show the toast and make it stay for 8 seconds (8000 milliseconds)
            showToast(successMessage, "success", 8000);
            // ====== END: IMPROVED NOTIFICATION ======

            sessionStorage.removeItem("professorCafeCart");

            // Wait a longer time before redirecting so the user can read the message
            setTimeout(() => {
              window.location.href = "index.php";
            }, 8000);
          } else {
            showToast(
              result.error || "An error occurred on the server.",
              "error"
            );
            placeOrderBtn.disabled = false;
            placeOrderBtn.textContent = "Place Order";
          }
        })
        .catch((error) => {
          console.error("Fetch Error:", error);
          showToast("A critical error occurred. Please try again.", "error");
          placeOrderBtn.disabled = false;
          placeOrderBtn.textContent = "Place Order";
        });
    });
  }

  // --- 5. TOAST NOTIFICATION FUNCTION (NOW ACCEPTS DURATION) ---
  function showToast(message, type = "success", duration = 3000) {
    // Added duration parameter
    const toastContainer = document.getElementById("toast-container");
    if (!toastContainer) return;

    const toast = document.createElement("div");
    toast.className = `toast ${type}`;
    // Use innerHTML to allow for bold tags and line breaks in the message
    toast.innerHTML = message;

    toastContainer.appendChild(toast);

    setTimeout(() => {
      toast.classList.add("show");
    }, 100);

    // Use the new duration parameter
    setTimeout(() => {
      toast.classList.remove("show");
      toast.addEventListener("transitionend", () => toast.remove());
    }, duration);
  }
});
