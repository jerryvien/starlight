// Set new default font family and font color
Chart.defaults.global.defaultFontFamily = 'Arial, sans-serif';
Chart.defaults.global.defaultFontColor = '#333';

// Function to format numbers with commas
function number_format(number) {
  return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
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
    legend: {
      display: false
    },
    scales: {
      xAxes: [{
        gridLines: {
          display: false
        },
        ticks: {
          fontColor: "#333", // X-axis label color
        },
        barPercentage: 0.8,
        categoryPercentage: 0.6,
      }],
      yAxes: [{
        scaleLabel: {
          display: true,
          labelString: 'Revenue (Tsh)', // Y-axis title with "Tsh" prefix
          fontColor: "#333", // Y-axis title color
        },
        ticks: {
          beginAtZero: true,
          callback: function(value) {
            return number_format(value); // Format y-axis labels with commas
          },
          fontColor: "#333", // Y-axis label color
        },
        gridLines: {
          color: "rgba(0, 0, 0, 0.1)",
        },
      }],
    },
    tooltips: {
      callbacks: {
        label: function(tooltipItem, data) {
          var label = data.datasets[tooltipItem.datasetIndex].label || '';
          label += ': Tsh ' + number_format(tooltipItem.yLabel);
          return label;
        }
      }
    }
  }
});

// Function to generate labels for the last 7 days (excluding month and year)
function generateLast7DaysLabels() {
  var labels = [];
  var today = new Date();
  for (var i = 6; i >= 0; i--) {
    var date = new Date(today);
    date.setDate(today.getDate() - i);
    labels.push(date.getDate().toString()); // Add day of the month as label
  }
  return labels;
}

// Function to generate random revenue data for the last 7 days (for demonstration)
function generateRandomRevenueData() {
  var data = [];
  for (var i = 0; i < 7; i++) {
    data.push(Math.floor(Math.random() * (200000000 - 50000000 + 1)) + 50000000); // Random revenue between 50,000,000 and 200,000,000 Tsh
  }
  return data;
}

// Update chart with dynamic data
function updateChartData() {
  myBarChart.data.labels = generateLast7DaysLabels();
  myBarChart.data.datasets[0].data = generateRandomRevenueData();
  myBarChart.update();
}

// Initial update of chart data
updateChartData();