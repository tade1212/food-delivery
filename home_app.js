document.addEventListener("DOMContentLoaded", () => {
  // --- STATE MANAGEMENT ---
  let cart = [];

  // Function to load the cart from sessionStorage
  function loadCart() {
    const storedCart = sessionStorage.getItem("professorCafeCart");
    if (storedCart) {
      cart = JSON.parse(storedCart);
    }
  }

  // Function to save the cart to sessionStorage
  function saveCart() {
    sessionStorage.setItem("professorCafeCart", JSON.stringify(cart));
  }

  // Function to add an item to the cart array
  function addToCart(item) {
    loadCart(); // Always get the latest cart first
    const existingItem = cart.find((cartItem) => cartItem.name === item.name);
    if (existingItem) {
      existingItem.quantity += item.quantity;
    } else {
      cart.push(item);
    }
    saveCart(); // Save the updated cart immediately
  }

  // --- EVENT LISTENER for the Special of the Day button ---
  const specialCard = document.querySelector(".special-menu-card");
  if (specialCard) {
    const orderButton = specialCard.querySelector(".btn-order");
    orderButton.addEventListener("click", (event) => {
      event.preventDefault();

      // For the special, we assume a quantity of 1 and no options for simplicity
      const itemName = specialCard.dataset.itemName;
      const itemPrice = parseFloat(specialCard.dataset.basePrice);

      const itemToAdd = {
        name: itemName,
        price: itemPrice,
        quantity: 1,
      };

      addToCart(itemToAdd);

      // Redirect to the menu page where the user can see the cart
      window.location.href = "menu.php#cart-modal-open";
    });
  }

  // Initial load of the cart when the page opens
  loadCart();
});
