// Import Firebase modules
import { initializeApp } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-app.js";
import { getDatabase, ref, set } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-database.js";

// Your web app's Firebase configuration
const firebaseConfig = {
  apiKey: "AIzaSyB8smbF627Xh_zZiagvIPqrKaoSlDumTJ4",
  authDomain: "fsms-48cb1.firebaseapp.com",
  databaseURL: "https://fsms-48cb1-default-rtdb.firebaseio.com",
  projectId: "fsms-48cb1",
  storageBucket: "fsms-48cb1.appspot.com",
  messagingSenderId: "693339377786",
  appId: "1:693339377786:web:a363ef6c5edc94e3a86bc3",
  measurementId: "G-VYE6B01M2N"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const database = getDatabase(app);

// Function to update prices
async function updatePrices() {
  const petrolPrice = document.getElementById("petrolPrice").value;

  if (!petrolPrice) {
    alert("Please enter a valid price.");
    return;
  }

  try {
    const priceRef = ref(database, 'Price');
    await set(priceRef, parseFloat(petrolPrice));
    localStorage.setItem('petrolPrice', petrolPrice); // Save to local storage
    alert("Price updated successfully!");
    displayPrice(petrolPrice); // Update UI immediately
  } catch (error) {
    console.error("Error updating price:", error);
    alert("Error updating price. Please try again.");
  }
}

// Function to display price from local storage
function displayPrice(price) {
  document.getElementById('displayedPrice').textContent = `Petrol Price: ${price} Tsh/L`;
}

// Add event listener to ensure DOM is loaded before accessing elements
document.addEventListener("DOMContentLoaded", () => {
  // Make updatePrices accessible to HTML
  window.updatePrices = updatePrices;

  // Check if there's a saved price in local storage and display it
  const savedPrice = localStorage.getItem('petrolPrice');
  if (savedPrice) {
    displayPrice(savedPrice);
  }
});


function updateDieselPrices() {
    const dieselPrice = document.getElementById("dieselPrice").value;
  
    if (!dieselPrice) {
      alert("Please enter a valid price.");
      return;
    }
  
    try {
      localStorage.setItem('dieselPrice', dieselPrice); // Save to local storage
      alert("Price updated successfully!");
      displayDieselPrice(dieselPrice); // Update UI immediately
    } catch (error) {
      console.error("Error updating price:", error);
      alert("Error updating price. Please try again.");
    }
  }
  
  // Function to display diesel price from local storage
  function displayDieselPrice(price) {
    const priceElement = document.getElementById('displayedPriceDiesel');
    if (priceElement) {
      priceElement.textContent = `Diesel Price: ${price} Tsh/L`;
    } else {
      console.error("Price element not found");
    }
  }
  
  // Add event listener to ensure DOM is loaded before accessing elements
  document.addEventListener("DOMContentLoaded", () => {
    // Make updateDieselPrices accessible to HTML
    window.updateDieselPrices = updateDieselPrices;
  
    // Check if there's a saved price in local storage and display it
    const savedPrice = localStorage.getItem('dieselPrice');
    if (savedPrice) {
      displayDieselPrice(savedPrice);
    }
  });
  