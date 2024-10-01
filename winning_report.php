<?php

include('config/database.php'); // Include your database connection

// Fetch customer count, sales, and transaction count grouped by month
try {
    // Customer growth grouped by month
    $customer_growth_query_monthly = 
        "SELECT DATE_FORMAT(created_at, '%Y-%m') AS period, COUNT(*) AS new_customers 
         FROM customer_details 
         GROUP BY DATE_FORMAT(created_at, '%Y-%m')";
         
    $customer_growth_stmt_monthly = $conn->prepare($customer_growth_query_monthly);
    $customer_growth_stmt_monthly->execute();
    $customer_growth_monthly = $customer_growth_stmt_monthly->fetchAll(PDO::FETCH_ASSOC);

    // Sales and transaction count grouped by month
    $sales_transactions_query_monthly = 
        "SELECT DATE_FORMAT(purchase_datetime, '%Y-%m') AS period, 
                SUM(purchase_amount) AS total_sales, 
                COUNT(*) AS transaction_count
         FROM purchase_entries 
         GROUP BY DATE_FORMAT(purchase_datetime, '%Y-%m')";

    $sales_transactions_stmt_monthly = $conn->prepare($sales_transactions_query_monthly);
    $sales_transactions_stmt_monthly->execute();
    $sales_transactions_monthly = $sales_transactions_stmt_monthly->fetchAll(PDO::FETCH_ASSOC);

    // Customer growth grouped by year
    $customer_growth_query_yearly = 
        "SELECT YEAR(created_at) AS period, COUNT(*) AS new_customers 
         FROM customer_details 
         GROUP BY YEAR(created_at)";

    $customer_growth_stmt_yearly = $conn->prepare($customer_growth_query_yearly);
    $customer_growth_stmt_yearly->execute();
    $customer_growth_yearly = $customer_growth_stmt_yearly->fetchAll(PDO::FETCH_ASSOC);

    // Sales and transaction count grouped by year
    $sales_transactions_query_yearly = 
        "SELECT YEAR(purchase_datetime) AS period, 
                SUM(purchase_amount) AS total_sales, 
                COUNT(*) AS transaction_count
         FROM purchase_entries 
         GROUP BY YEAR(purchase_datetime)";

    $sales_transactions_stmt_yearly = $conn->prepare($sales_transactions_query_yearly);
    $sales_transactions_stmt_yearly->execute();
    $sales_transactions_yearly = $sales_transactions_stmt_yearly->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Growth and Sales Chart</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <script src="vendor/chart.js/Chart.min.js"></script>
</head>
<body>

<div class="container-fluid">
    <h2 class="h4 mb-4 text-gray-800">Customer Growth, Sales, and Transactions</h2>

    <!-- Buttons to Toggle Between Monthly and Yearly Data -->
    <div class="form-group">
        <button id="monthlyBtn" class="btn btn-primary">Monthly</button>
        <button id="yearlyBtn" class="btn btn-secondary">Yearly</button>
    </div>

    <canvas id="customerGrowthSalesChart"></canvas>
</div>

<script>
// Parse PHP arrays to JavaScript
var customerGrowthMonthly = <?php echo json_encode($customer_growth_monthly); ?>;
var salesTransactionsMonthly = <?php echo json_encode($sales_transactions_monthly); ?>;

var customerGrowthYearly = <?php echo json_encode($customer_growth_yearly); ?>;
var salesTransactionsYearly = <?php echo json_encode($sales_transactions_yearly); ?>;

// Function to extract labels and data
function extractChartData(data, key) {
    return {
        labels: data.map(item => item.period),
        values: data.map(item => item[key])
    };
}

// Monthly Data
var monthlyCustomerData = extractChartData(customerGrowthMonthly, 'new_customers');
var monthlySalesData = extractChartData(salesTransactionsMonthly, 'total_sales');
var monthlyTransactionsData = extractChartData(salesTransactionsMonthly, 'transaction_count');

// Yearly Data
var yearlyCustomerData = extractChartData(customerGrowthYearly, 'new_customers');
var yearlySalesData = extractChartData(salesTransactionsYearly, 'total_sales');
var yearlyTransactionsData = extractChartData(salesTransactionsYearly, 'transaction_count');

// Function to Update Chart
function updateChart(chart, labels, customerData, salesData, transactionData) {
    chart.data.labels = labels;
    chart.data.datasets[0].data = customerData;
    chart.data.datasets[1].data = salesData;
    chart.data.datasets[2].data = transactionData;
    chart.update();
}

// Initialize Chart.js Line Chart
var ctx = document.getElementById('customerGrowthSalesChart').getContext('2d');
var customerGrowthSalesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: monthlyCustomerData.labels,
        datasets: [
            {
                label: 'New Customers',
                data: monthlyCustomerData.values,
                backgroundColor: '#28a745',
                borderColor: '#28a745',
                fill: false
            },
            {
                label: 'Total Sales (RM)',
                data: monthlySalesData.values,
                backgroundColor: '#007bff',
                borderColor: '#007bff',
                fill: false
            },
            {
                label: 'Transaction Count',
                data: monthlyTransactionsData.values,
                backgroundColor: '#ffc107',
                borderColor: '#ffc107',
                fill: false
            }
        ]
    }
});

// Event Listeners for Buttons
document.getElementById('monthlyBtn').addEventListener('click', function() {
    updateChart(customerGrowthSalesChart, monthlyCustomerData.labels, monthlyCustomerData.values, monthlySalesData.values, monthlyTransactionsData.values);
});

document.getElementById('yearlyBtn').addEventListener('click', function() {
    updateChart(customerGrowthSalesChart, yearlyCustomerData.labels, yearlyCustomerData.values, yearlySalesData.values, yearlyTransactionsData.values);
});
</script>

</body>
</html>