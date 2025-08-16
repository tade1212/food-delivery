document.addEventListener("DOMContentLoaded", () => {
  // --- ELEMENT SELECTION ---
  const cartIconButton = document.getElementById("cart-icon");
  const cartModal = document.getElementById("cart-modal");
  const closeModalButton = document.querySelector(".close-button");
  const cartItemsContainer = document.getElementById("cart-items");
  const cartTotalElement = document.getElementById("cart-total");
  const cartCountElement = document.getElementById("cart-count");
  const optionsModal = document.getElementById("options-modal");
  const closeOptionsButton = document.querySelector(".close-options-button");
  const optionsForm = document.getElementById("options-form");
  const modalOptionsContent = document.getElementById("modal-options-content");
  const modalTitle = document.getElementById("options-modal-title");
  const quantityInput = document.getElementById("quantity");
  const modalTotalPriceSpan = document.getElementById("modal-total-price");

  // --- STATE & STORAGE ---
  let cart = [];
  let currentItemCard = null;

  function loadCart() {
    const storedCart = sessionStorage.getItem("professorCafeCart");
    if (storedCart) {
      cart = JSON.parse(storedCart);
    }
    updateCartDisplay();
  }

  function saveCart() {
    sessionStorage.setItem("professorCafeCart", JSON.stringify(cart));
  }

  // --- EVENT LISTENERS ---
  // Using event delegation on the body for better performance
  document.body.addEventListener("click", (event) => {
    // Check if an "Order Now" button was clicked
    if (event.target.classList.contains("btn-order")) {
      event.preventDefault();
      currentItemCard = event.target.closest(".menu-card");
      if (currentItemCard) {
        openOptionsModal(currentItemCard);
      }
    }

    // ====== START: CORRECTED CART ICON LOGIC ======
    // Check if the cart icon itself (or its child span) was clicked
    if (event.target.closest("#cart-icon")) {
      event.preventDefault();
      if (cartModal) {
        cartModal.style.display = "block"; // Directly show the modal
      }
    }
    // ====== END: CORRECTED CART ICON LOGIC ======
  });

  if (optionsForm) {
    optionsForm.addEventListener("submit", (event) => {
      event.preventDefault();
      const baseName = currentItemCard.dataset.itemName;
      const quantity = parseInt(quantityInput.value);
      // Use the calculated total price for accuracy
      const finalTotalPrice = parseFloat(modalTotalPriceSpan.textContent);
      const finalPricePerItem = finalTotalPrice / quantity;

      let finalName = baseName;
      const selections = optionsForm.querySelectorAll(
        "select, input[type='radio']:checked"
      );
      let selectedOptions = [];

      selections.forEach((select) => {
        const selectedChoiceText = select.options
          ? select.options[select.selectedIndex].text
          : select.dataset.choiceName;
        const choiceNameOnly = selectedChoiceText.split(" (")[0];
        if (choiceNameOnly) {
          selectedOptions.push(choiceNameOnly);
        }
      });
      if (selectedOptions.length > 0) {
        finalName += ` (${selectedOptions.join(", ")})`;
      }

      addToCart({
        name: finalName,
        price: finalPricePerItem,
        quantity: quantity,
      });
      showToast(`Added ${quantity} x ${baseName} to cart`, "success");
      if (optionsModal) {
        optionsModal.style.display = "none";
      }
    });
  }

  // Modal Close Buttons
  if (closeModalButton) {
    closeModalButton.addEventListener("click", () => {
      if (cartModal) cartModal.style.display = "none";
    });
  }
  if (closeOptionsButton) {
    closeOptionsButton.addEventListener("click", () => {
      if (optionsModal) optionsModal.style.display = "none";
    });
  }

  // Clicking outside the modal to close
  window.addEventListener("click", (event) => {
    if (event.target == cartModal) cartModal.style.display = "none";
    if (event.target == optionsModal) optionsModal.style.display = "none";
  });

  // Checkout Button
  const checkoutButton = document.getElementById("checkout-btn");
  if (checkoutButton) {
    checkoutButton.addEventListener("click", (event) => {
      event.preventDefault();
      if (cart.length === 0) {
        showToast("Your cart is empty!", "error");
        return;
      }
      // Save the cart before leaving the page
      saveCart();
      window.location.href = "checkout.html";
    });
  }

  // --- DYNAMIC PRICE CALCULATION ---
  function calculateModalPrice() {
    if (!currentItemCard || !optionsForm) return;
    let basePrice = parseFloat(currentItemCard.dataset.basePrice);
    let optionsPrice = 0;
    const quantity = parseInt(quantityInput.value) || 1;
    const selections = optionsForm.querySelectorAll(
      "select, input[type='radio']:checked"
    );
    selections.forEach((select) => {
      const priceChange = parseFloat(select.value) || 0;
      optionsPrice += priceChange;
    });
    const finalTotalPrice = (basePrice + optionsPrice) * quantity;
    if (modalTotalPriceSpan) {
      modalTotalPriceSpan.textContent = finalTotalPrice.toFixed(2);
    }
  }

  // --- CORE FUNCTIONS ---
  function openOptionsModal(menuCard) {
    if (!optionsModal) return;
    const itemName = menuCard.dataset.itemName;
    const optionsData = JSON.parse(menuCard.dataset.options || "{}");
    modalTitle.textContent = `Customize ${itemName}`;
    modalOptionsContent.innerHTML = "";
    quantityInput.value = 1;

    for (const groupName in optionsData) {
      const choices = optionsData[groupName];
      const formGroup = document.createElement("div");
      formGroup.classList.add("form-group");
      const label = document.createElement("label");
      label.textContent = `Choose ${groupName}:`;
      formGroup.appendChild(label);

      // Use radio buttons for a better UX when prices differ
      choices.forEach((choice, index) => {
        const radioWrapper = document.createElement("div");
        const radioInput = document.createElement("input");
        radioInput.type = "radio";
        radioInput.name = `option_${groupName}`;
        radioInput.id = `choice_${groupName}_${index}`;
        radioInput.value = choice.price;
        radioInput.dataset.choiceName = choice.choice; // Store the name separately
        if (index === 0) radioInput.checked = true; // Select the first one by default
        radioInput.addEventListener("change", calculateModalPrice);

        const radioLabel = document.createElement("label");
        radioLabel.htmlFor = `choice_${groupName}_${index}`;
        let priceText =
          choice.price > 0
            ? ` (+${choice.price} Birr)`
            : choice.price < 0
            ? ` (${choice.price} Birr)`
            : "";
        radioLabel.textContent = `${choice.choice}${priceText}`;

        radioWrapper.appendChild(radioInput);
        radioWrapper.appendChild(radioLabel);
        formGroup.appendChild(radioWrapper);
      });
      modalOptionsContent.appendChild(formGroup);
    }

    if (quantityInput) {
      quantityInput.removeEventListener("input", calculateModalPrice); // Remove old listener to prevent duplicates
      quantityInput.addEventListener("input", calculateModalPrice);
    }
    optionsModal.style.display = "block";
    calculateModalPrice();
  }

  function addToCart(item) {
    const existingItem = cart.find((cartItem) => cartItem.name === item.name);
    if (existingItem) {
      existingItem.quantity += item.quantity;
    } else {
      cart.push(item);
    }
    saveCart();
    updateCartDisplay();
  }

  function updateCartDisplay() {
    let total = 0;
    let totalItems = 0;
    cart.forEach((item) => {
      total += item.price * item.quantity;
      totalItems += item.quantity;
    });
    if (cartCountElement) cartCountElement.textContent = totalItems;
    if (cartItemsContainer) {
      cartItemsContainer.innerHTML =
        cart.length === 0 ? "<p>Your cart is empty.</p>" : "";
      cart.forEach((item) => {
        const cartItemElement = document.createElement("div");
        cartItemElement.classList.add("cart-item");
        cartItemElement.innerHTML = `<span>${item.name} (x${
          item.quantity
        })</span><span>${(item.price * item.quantity).toFixed(2)} Birr</span>`;
        cartItemsContainer.appendChild(cartItemElement);
      });
    }
    if (cartTotalElement)
      cartTotalElement.textContent = `Total: ${total.toFixed(2)} Birr`;
  }

  function showToast(message, type = "success") {
    const toastContainer = document.getElementById("toast-container");
    if (!toastContainer) return;
    const toast = document.createElement("div");
    toast.className = `toast ${type}`;
    toast.textContent = message;
    toastContainer.appendChild(toast);
    setTimeout(() => toast.classList.add("show"), 100);
    setTimeout(() => {
      toast.classList.remove("show");
      toast.addEventListener("transitionend", () => toast.remove());
    }, 3000);
  }

  // --- INITIALIZATION ---
  loadCart();
});
 