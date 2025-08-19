document.addEventListener("DOMContentLoaded", function () {
  // --- 1. SELECT ALL ELEMENTS ---
  const checkoutForm = document.getElementById("checkout-form");
  const summaryItemsContainer = document.getElementById("order-summary-items");
  const summaryTotalElement = document.getElementById("summary-total");
  const receiptInput = document.getElementById("receipt");
  const submitBtn = document.getElementById("submit-order-btn");
  // NEW: Select the span to display the file name
  const fileNameDisplay = document.getElementById("file-name-display");

  // --- 2. GET CART DATA ---
  const cartData = sessionStorage.getItem("professorCafeCart");
  if (!cartData || cartData === "[]") {
    alert("Your cart is empty. Redirecting to the menu.");
    window.location.href = "menu.php";
    return;
  }
  const cart = JSON.parse(cartData);

  // --- 3. DISPLAY CART SUMMARY ---
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
  summaryTotalElement.textContent = `Total to Pay: ${total.toFixed(2)} Birr`;

  // --- 4. REFINED: HANDLE FILE INPUT AND ENABLE SUBMIT BUTTON ---
  if (receiptInput && submitBtn) {
    receiptInput.addEventListener("change", function () {
      const file = this.files[0];
      if (file) {
        // Validate that the selected file is an image
        if (!file.type.startsWith("image/")) {
          showToast(
            "Please upload a valid image file (jpg, png, gif).",
            "error"
          );
          this.value = ""; // Clear the invalid file selection
          if (fileNameDisplay)
            fileNameDisplay.textContent = "No file selected.";
          submitBtn.disabled = true;
          submitBtn.classList.remove("enabled");
          return;
        }

        // If the file is valid, enable the button and show the file name
        submitBtn.disabled = false;
        submitBtn.classList.add("enabled");
        if (fileNameDisplay)
          fileNameDisplay.textContent = `Selected: ${file.name}`;
      } else {
        // If no file is selected (or the selection is cancelled), disable the button
        submitBtn.disabled = true;
        submitBtn.classList.remove("enabled");
        if (fileNameDisplay) fileNameDisplay.textContent = "No file selected.";
      }
    });
  }

  // --- 5. HANDLE FORM SUBMISSION ---
  if (checkoutForm) {
    checkoutForm.addEventListener("submit", async function (event) {
      event.preventDefault();
      submitBtn.disabled = true;
      submitBtn.textContent = "Completing Order...";

      // FormData correctly handles all form fields, including the file
      const formData = new FormData(this);
      formData.append("cart_items", JSON.stringify(cart));

      try {
        const response = await fetch("api/place_order.php", {
          method: "POST",
          body: formData, // Send FormData directly
        });

        // Check if the response is valid before trying to parse it
        if (!response.ok) {
          throw new Error(
            `Server responded with an error: ${response.statusText}`
          );
        }

        const result = await response.json();

        if (result.success) {
          const successMessage = `
            <strong>Order Placed Successfully!</strong><br>
            Your Order ID is: <strong>#${result.order_id}</strong><br>
            We will confirm your payment shortly.
          `;
          showToast(successMessage, "success", 8000);
          sessionStorage.removeItem("professorCafeCart");
          setTimeout(() => {
            window.location.href = "index.php";
          }, 8000);
        } else {
          // If the server responded with success:false, throw its error message
          throw new Error(
            result.error || "An unknown error occurred on the server."
          );
        }
      } catch (error) {
        // This catch block handles network errors or errors thrown from the .then() block
        showToast(error.message, "error");
        submitBtn.disabled = false;
        submitBtn.textContent = "Complete Order";
      }
    });
  }

  // --- 6. TOAST NOTIFICATION FUNCTION ---
  function showToast(message, type = "success", duration = 4000) {
    const toastContainer = document.getElementById("toast-container");
    if (!toastContainer) return;

    const toast = document.createElement("div");
    toast.className = `toast ${type}`;
    toast.innerHTML = message; // Use innerHTML to allow for HTML tags like <strong>

    toastContainer.appendChild(toast);

    setTimeout(() => {
      toast.classList.add("show");
    }, 100);

    setTimeout(() => {
      toast.classList.remove("show");
      toast.addEventListener("transitionend", () => toast.remove());
    }, duration);
  }
});
