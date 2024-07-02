import { initializeApp } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-app.js";
import { getDatabase, ref, get } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-database.js";

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

// Function to calculate the total sales
function calculateTotalSales(salesData) {
  let totalSales = 0;
  for (const date in salesData) {
    for (const time in salesData[date]) {
      totalSales += salesData[date][time];
    }
  }
  return totalSales;
}

// Function to fetch and display total sales and revenue
async function fetchAndDisplaySalesAndRevenue() {
  const salesRef = ref(database, 'Sales'); // Adjust the path to your sales data
  const priceRef = ref(database, 'Price'); // Adjust the path to your price data
  try {
    // Fetch sales data
    const salesSnapshot = await get(salesRef);
    const salesData = salesSnapshot.val();
    const totalSales = calculateTotalSales(salesData);

    // Fetch price data
    const priceSnapshot = await get(priceRef);
    const priceData = priceSnapshot.val();
    const price = priceData || 0; // Default to 0 if no price data

    // Calculate total revenue
    const totalRevenue = totalSales * price;

    // Update the UI
    document.getElementById('total-sales').textContent = `${totalSales} units`;
    document.getElementById('total-revenue').textContent = `Tsh ${totalRevenue.toLocaleString('en-US')}`;
  } catch (error) {
    console.error('Error fetching data:', error);
    document.getElementById('total-sales').textContent = 'Error fetching data';
    document.getElementById('total-revenue').textContent = 'Error fetching data';
  }
}

// Call the function to fetch and display total sales and revenue
fetchAndDisplaySalesAndRevenue();

// count number of users
document.addEventListener("DOMContentLoaded", () => {
  fetchEmployees();
});

function fetchEmployees() {
  fetch('https://fsys-api.azurewebsites.net/api/employees', {
      method: 'GET',
      headers: {
          'Content-Type': 'application/json'
      },
  })
  .then(response => {
      if (!response.ok) {
          throw new Error('Network response was not ok');
      }
      return response.json();  // Parse response JSON once
  })
  .then(data => {
      console.log("Fetched employees: ", data);
      const employees = data.value;
      
      // Calculate number of employees
      const numberOfEmployees = employees.length;
      displayEmployeeCount(numberOfEmployees);
  })
  .catch(error => {
      console.error('Error fetching employees:', error);
  });
}

function displayEmployeeCount(count) {
  const employeeCountElement = document.getElementById('employeeCount');
  if (employeeCountElement) {
      employeeCountElement.textContent = count;
  } else {
      console.error("Employee count element not found");
  }
}

//number of tank

document.addEventListener("DOMContentLoaded", () => {
  fetchTanks();
});

function fetchTanks() {
  fetch('https://fsys-api.azurewebsites.net/api/tanks', {
      method: 'GET',
      headers: {
          'Content-Type': 'application/json'
      },
  })
  .then(response => {
      if (!response.ok) {
          throw new Error('Network response was not ok');
      }
      return response.json();  // Parse response JSON once
  })
  .then(data => {
      console.log("Fetched tanks: ", data);
      const tanks = data.value;
      
      // Calculate number of tanks
      const numberOfTanks = tanks.length;
      displayTankCount(numberOfTanks);
  })
  .catch(error => {
      console.error('Error fetching tanks:', error);
  });
}

function displayTankCount(count) {
  const tankCountElement = document.getElementById('tankCount');
  if (tankCountElement) {
      tankCountElement.textContent = count;
  } else {
      console.error("Tank count element not found");
  }
}

// calculate number of dispenser

document.addEventListener("DOMContentLoaded", () => {
  fetchDispensers();
});

function fetchDispensers() {
  fetch('https://fsys-api.azurewebsites.net/api/dispenser', {
      method: 'GET',
      headers: {
          'Content-Type': 'application/json'
      },
  })
  .then(response => {
      if (!response.ok) {
          throw new Error('Network response was not ok');
      }
      return response.json();  // Parse response JSON once
  })
  .then(data => {
      console.log("Fetched dispensers: ", data);
      const dispensers = data.value;
      
      // Calculate number of dispensers
      const numberOfDispensers = dispensers.length;
      displayDispenserCount(numberOfDispensers);
  })
  .catch(error => {
      console.error('Error fetching dispensers:', error);
  });
}

function displayDispenserCount(count) {
  const dispenserCountElement = document.getElementById('dispenserCount');
  if (dispenserCountElement) {
      dispenserCountElement.textContent = count;
  } else {
      console.error("Dispenser count element not found");
  }
}
