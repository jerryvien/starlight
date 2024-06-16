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
    labels: ["January", "February", "March", "April", "May", "June", 
             "July", "August", "September", "October", "November", "December"],
    datasets: [{
      label: "Revenue",
      backgroundColor: "#4e73df",
      hoverBackgroundColor: "#2e59d9",
      borderColor: "#4e73df",
      borderWidth: 1,
      data: [42150000, 53120000, 62510000, 78410000, 98210000, 149840000, 
             102350000, 85210000, 95600000, 78400000, 63500000, 126560000],
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
            return number_format(value); // No need to add "Tsh" in ticks
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
