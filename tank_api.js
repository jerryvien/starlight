import { initializeApp } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-app.js";
import {
  getDatabase,
  ref,
  onValue,
} from "https://www.gstatic.com/firebasejs/10.12.2/firebase-database.js";

// Your web app's Firebase configuration
const firebaseConfig = {
  apiKey: "AIzaSyB8smbF627Xh_zZiagvIPqrKaoSlDumTJ4",
  authDomain: "fsms-48cb1.firebaseapp.com",
  databaseURL: "https://fsms-48cb1-default-rtdb.firebaseio.com",
  projectId: "fsms-48cb1",
  storageBucket: "fsms-48cb1.appspot.com",
  messagingSenderId: "693339377786",
  appId: "1:693339377786:web:a363ef6c5edc94e3a86bc3",
  measurementId: "G-VYE6B01M2N",
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const database = getDatabase(app);

// Initialize EmailJS with your public key
emailjs.init("0kDn7yfvpYkk-DGka");

// Function to get the latest value from a nested date object
function getLatestNestedValue(data) {
  const dates = Object.keys(data).sort(
    (a, b) => new Date(b.replace(/_/g, "-")) - new Date(a.replace(/_/g, "-"))
  );
  if (dates.length === 0) return "N/A";
  const latestDate = dates[0];
  const times = Object.keys(data[latestDate]).sort(
    (a, b) => new Date(`1970/01/01 ${b}`) - new Date(`1970/01/01 ${a}`)
  );
  if (times.length === 0) return "N/A";
  const latestTime = times[0];
  return data[latestDate][latestTime];
}

// Function to update UI with the latest data
function updateRealtimeUI(data) {
  const latestVolume = getLatestNestedValue(data.Volume || {});
  const latestTemperature = getLatestNestedValue(data.Temperature || {});
  const latestHumidity = getLatestNestedValue(data.Humidity || {});

  document.getElementById("cell-volume").textContent = `${latestVolume} L`;
  document.getElementById(
    "cell-temperature"
  ).textContent = `${latestTemperature} °C`;
  document.getElementById("cell-humidity").textContent = `${latestHumidity} %`;
}

// Function to send email notification using EmailJS
// Function to send email notification using EmailJS
function sendEmailNotification(type, value) {
  let templateParams = {
    to_name: 'Admin', // Replace with actual recipient name
    to_email: 'annamnghamba28@gmail.com', // Replace with actual recipient email
    subject: '',
    message: ''
  };

  // Log the type to debug
  console.log('Sending email for type:', type);

  switch (type) {
    case 'volume':
      templateParams.subject = 'Low Volume Alert';
      templateParams.message = `Alert: Tank volume is less than 250. Current volume: ${value} liters.`;
      break;
    case 'temperature':
      templateParams.subject = 'High Temperature Alert';
      templateParams.message = `Alert: Temperature exceeds 35°C. Current temperature: ${value} °C.`;
      break;
    case 'humidity':
      templateParams.subject = 'Low Humidity Alert';
      templateParams.message = `Alert: Humidity is below 50%. Current humidity: ${value} %.`;
      break;
    default:
      console.error('Unknown alert type:', type);
      return; // Exit function if unknown type
  }

  // Replace 'service_9dnlgab' and 'template_2fs8ipf' with your actual service ID and template ID
  emailjs.send('service_klxig5a', 'template_5cdqwde', templateParams)
    .then((response) => {
      console.log('Email sent successfully!', response);
    })
    .catch((error) => {
      console.error('Failed to send email:', error);
    });
}

// Function to setup real-time updates
// Function to setup real-time updates
function setupRealtimeUpdates() {
  const dbRef = ref(database, "/"); // Adjust the path to your data
  let previousVolume = null;
  let previousTemperature = null;
  let previousHumidity = null;

  onValue(dbRef, (snapshot) => {
    const data = snapshot.val();
    console.log("Realtime update:", data);
    updateRealtimeUI(data);

    const latestVolume = getLatestNestedValue(data.Volume || {});
    const latestTemperature = getLatestNestedValue(data.Temperature || {});
    const latestHumidity = getLatestNestedValue(data.Humidity || {});

    if (latestVolume < 250 && latestVolume !== previousVolume) {
      sendEmailNotification('volume', latestVolume);
      previousVolume = latestVolume;
    }

    if (latestTemperature > 35 && latestTemperature !== previousTemperature) {
      sendEmailNotification('temperature', latestTemperature);
      previousTemperature = latestTemperature;
    }

    if (latestHumidity < 50 && latestHumidity !== previousHumidity) {
      sendEmailNotification('humidity', latestHumidity);
      previousHumidity = latestHumidity;
    }
  });
}


// Call setupRealtimeUpdates() to initiate real-time updates
setupRealtimeUpdates();

// Event listener for test email button
document.getElementById("testEmailBtn").addEventListener("click", () => {
  const testVolume = 200; // Test volume value
  const testTemperature = 36; // Test temperature value
  const testHumidity = 45; // Test humidity value
  sendEmailNotification(testVolume, testTemperature, testHumidity);
});

// Add tank to the database
document.addEventListener("DOMContentLoaded", () => {
  const saveTankBtn = document.getElementById("saveTankBtn");
  if (saveTankBtn) {
    saveTankBtn.addEventListener("click", saveTank);
  }
});

function saveTank() {
  const tankName = document.getElementById("tankName").value;
  const tankCapacity = parseInt(document.getElementById("tankCapacity").value);
  const tankLevel = parseInt(document.getElementById("tankLevel").value);

  if (!tankName || isNaN(tankCapacity) || isNaN(tankLevel)) {
    alert("Please fill out all fields with valid values.");
    return;
  }

  const data = {
    identifier: tankName,
    capacity: tankCapacity,
    fuel: tankLevel,
  };

  fetch("https://fsys-api.azurewebsites.net/api/tank", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(data),
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.json();
    })
    .then((data) => {
      console.log("Successfully saved tank:", data);
      alert("Tank saved successfully!");
      // Optionally, clear the form or perform any other actions after successful save
    })
    .catch((error) => {
      console.error("Error saving tank:", error);
      alert("Failed to save tank. Please try again.");
    });
}
