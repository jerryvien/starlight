<?php

include('config/database.php'); // Include your database connection

// Default grouping by month
$group_by = 'month';

// Fetch customer count grouped by month
try {
    $customer_growth_query_monthly = 
        "SELECT DATE_FORMAT(created_at, '%Y-%m') AS period, COUNT(*) AS new_customers 
         FROM customer_details 
         GROUP BY DATE_FORMAT(created_at, '%Y-%m')";
         
    $customer_growth_stmt_monthly = $conn->prepare($customer_growth_query_monthly);
    $customer_growth_stmt_monthly->execute();
    $customer_growth_monthly = $customer_growth_stmt_monthly->fetchAll(PDO::FETCH_ASSOC);

    // Fetch customer count grouped by year
    $customer_growth_query_yearly = 
        "SELECT YEAR(created_at) AS period, COUNT(*) AS new_customers 
         FROM customer_details 
         GROUP BY YEAR(created_at)";

    $customer_growth_stmt_yearly = $conn->prepare($customer_growth_query_yearly);
    $customer_growth_stmt_yearly->execute();
    $customer_growth_yearly = $customer_growth_stmt_yearly->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching customer growth data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Growth Chart</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <script src="vendor/chart.js/Chart.min.js"></script>
</head>
<body>

<div class="container-fluid">
    <h2 class="h4 mb-4 text-gray-800">Customer Growth</h2>

    <!-- Buttons to Toggle Between Monthly and Yearly Data -->
    <div class="form-group">
        <button id="monthlyBtn" class="btn btn-primary">Monthly</button>
        <button id="yearlyBtn" class="btn btn-secondary">Yearly</button>
    </div>

    <canvas id="customerGrowthChart"></canvas>
</div>

<script>
// Parse PHP arrays to JavaScript
var customerGrowthMonthly = <?php echo json_encode($customer_growth_monthly); ?>;
var customerGrowthYearly = <?php echo json_encode($customer_growth_yearly); ?>;

// Function to extract labels and data
function extractChartData(data) {
    return {
        labels: data.map(item => item.period),
        values: data.map(item => item.new_customers)
    };
}

// Monthly Data
var monthlyData = extractChartData(customerGrowthMonthly);
// Yearly Data
var yearlyData = extractChartData(customerGrowthYearly);

// Function to Update Chart
function updateChart(chart, labels, data) {
    chart.data.labels = labels;
    chart.data.datasets[0].data = data;
    chart.update();
}

// Initialize Chart.js Line Chart
var ctx = document.getElementById('customerGrowthChart').getContext('2d');
var customerGrowthChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: monthlyData.labels,
        datasets: [{
            label: 'New Customers',
            data: monthlyData.values,
            backgroundColor: '#28a745',
            borderColor: '#28a745',
            fill: false
        }]
    }
});

// Event Listeners for Buttons
document.getElementById('monthlyBtn').addEventListener('click', function() {
    updateChart(customerGrowthChart, monthlyData.labels, monthlyData.values);
});

document.getElementById('yearlyBtn').addEventListener('click', function() {
    updateChart(customerGrowthChart, yearlyData.labels, yearlyData.values);
});
</script>

</body>
</html>