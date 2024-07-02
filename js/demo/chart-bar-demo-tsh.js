import { initializeApp, getApps } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-app.js";
import {
  getDatabase,
  ref,
  query,
  orderByKey,
  limitToLast,
  get
} from "https://www.gstatic.com/firebasejs/10.12.2/firebase-database.js";

// Your Firebase configuration
const firebaseConfig = {
  apiKey: "your-api-key",
  authDomain: "your-auth-domain",
  databaseURL: "https://fsms-48cb1-default-rtdb.firebaseio.com",
  projectId: "fsms-48cb1",
  storageBucket: "your-storage-bucket",
  messagingSenderId: "your-messaging-sender-id",
  appId: "your-app-id",
  measurementId: "your-measurement-id"
};

// Initialize Firebase if it hasn't been initialized already
let app;
if (!getApps().length) {
  app = initializeApp(firebaseConfig);
} else {
  app = getApps()[0]; // Use the already initialized app
}

const database = getDatabase(app);




// Function to fetch latest five days of sales data
async function fetchSalesData() {
  try {
    const salesRef = ref(database, 'Sales');
    const salesSnapshot = await query(salesRef, orderByKey(), limitToLast(5));

    const salesData = [];

    salesSnapshot.forEach((childSnapshot) => {
      const salesByDay = childSnapshot.val();
      let totalSales = 0;

      // Iterate over keys in salesByDay assuming it's an object with sales data
      Object.keys(salesByDay).forEach((key) => {
        const sales = salesByDay[key];
        if (sales.total_sales !== undefined) { // Check if total_sales is defined
          totalSales += sales.total_sales;
        }
      });

      salesData.push({
        date: childSnapshot.key, // Assuming childSnapshot.key is the date key
        total_sales: totalSales
      });
    });

    return salesData.reverse(); // Reverse to get latest day first
  } catch (error) {
    // console.error('Error fetching sales data:', error);
    throw error;
  }
}


// Function to fetch single price data
async function fetchPriceData() {
  try {
    const priceRef = ref(database, 'Price');
    const priceSnapshot = await get(priceRef);
    const priceData = priceSnapshot.val();
    
    console.log('Fetched price data:', priceData); // Log the fetched price data

    return priceData;
  } catch (error) {
    console.error('Error fetching price data:', error);
    throw error;
  }
}

// Function to calculate revenue based on fetched data
async function calculateRevenue() {
  try {
    const salesData = await fetchSalesData();
    const price = await fetchPriceData();

    // Calculate revenue for each date
    const revenueData = salesData.reduce((acc, sales) => {
      const revenue = sales.total_sales * price;
      if (!acc[sales.date]) {
        acc[sales.date] = { date: sales.date, revenue: 0 };
      }
      acc[sales.date].revenue += revenue;
      return acc;
    }, {});

    // Convert revenueData to an array and sort by date
    const revenueArray = Object.values(revenueData).sort((a, b) => new Date(a.date) - new Date(b.date));

    return revenueArray;
  } catch (error) {
    console.error('Error calculating revenue:', error);
    throw error;
  }
}

// Bar Chart Example
var ctx = document.getElementById("myBarChart").getContext('2d');
var myBarChart = new Chart(ctx, {
  type: 'bar',
  data: {
    labels: [], // Initialize empty array for labels
    datasets: [{
      label: "Revenue",
      backgroundColor: "#4e73df",
      hoverBackgroundColor: "#2e59d9",
      borderColor: "#4e73df",
      borderWidth: 1,
      data: [], // Initialize empty array for data
    }],
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false
      },
      tooltip: {
        callbacks: {
          label: function(tooltipItem) {
            return 'Tsh ' + number_format(tooltipItem.raw);
          }
        }
      }
    },
    scales: {
      x: { // Use new notation for scales
        grid: {
          display: false
        },
        ticks: {
          color: "#333", // X-axis label color
        },
        barPercentage: 0.8,
        categoryPercentage: 0.6,
      },
      y: { // Use new notation for scales
        title: {
          display: true,
          text: 'Revenue (Tsh)', // Y-axis title with "Tsh" prefix
          color: "#333", // Y-axis title color
        },
        ticks: {
          beginAtZero: true,
          callback: function(value) {
            return 'Tsh ' + number_format(value); // Format y-axis labels with commas
          },
          color: "#333", // Y-axis label color
        },
        grid: {
          color: "rgba(0, 0, 0, 0.1)",
        },
      },
    }
  }
});


// Function to update chart with revenue data
async function updateChartWithRevenue() {
  try {
    const salesData = await fetchSalesData();
    const priceData = await fetchPriceData();

    const price = priceData.price || 0; // Ensure price is defined or default to 0

    // Extract latest 5 dates and corresponding revenues
    const labels = salesData.slice(0, 5).map(entry => entry.date);
    const data = salesData.slice(0, 5).map(entry => entry.total_sales * price);

    myBarChart.data.labels = labels.reverse(); // Reverse to display latest date first
    myBarChart.data.datasets[0].data = data.reverse(); // Reverse to match labels order
    myBarChart.update();
  } catch (error) {
    // console.error('Error updating chart with revenue data:', error);
  }
}

// Initial update of chart with revenue data
updateChartWithRevenue();