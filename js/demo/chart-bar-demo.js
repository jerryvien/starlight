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
      data: [4215000, 5312000, 6251000, 7841000, 9821000, 14984000, 
             10235000, 8521000, 9560000, 7840000, 6350000, 7256000],
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
