<?php

include('config/database.php'); // Include your database connection

// Fetch distinct years for the dropdown selection
try {
    $years_query = "SELECT DISTINCT YEAR(created_at) AS year FROM customer_details ORDER BY year DESC";
    $years_stmt = $conn->prepare($years_query);
    $years_stmt->execute();
    $available_years = $years_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching available years: " . $e->getMessage());
}

// Fetch customer count, sales, and transaction count grouped by the last 12 months
try {
    $customer_growth_query_monthly = 
        "SELECT DATE_FORMAT(created_at, '%Y-%m') AS period, COUNT(*) AS new_customers 
         FROM customer_details 
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
         GROUP BY DATE_FORMAT(created_at, '%Y-%m')";

    $customer_growth_stmt_monthly = $conn->prepare($customer_growth_query_monthly);
    $customer_growth_stmt_monthly->execute();
    $customer_growth_monthly = $customer_growth_stmt_monthly->fetchAll(PDO::FETCH_ASSOC);

    // Sales and transaction count grouped by the last 12 months
    $sales_transactions_query_monthly = 
        "SELECT DATE_FORMAT(purchase_datetime, '%Y-%m') AS period, 
                SUM(purchase_amount) AS total_sales, 
                COUNT(*) AS transaction_count
         FROM purchase_entries 
         WHERE purchase_datetime >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
         GROUP BY DATE_FORMAT(purchase_datetime, '%Y-%m')";

    $sales_transactions_stmt_monthly = $conn->prepare($sales_transactions_query_monthly);
    $sales_transactions_stmt_monthly->execute();
    $sales_transactions_monthly = $sales_transactions_stmt_monthly->fetchAll(PDO::FETCH_ASSOC);
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<div class="container-fluid">
    <h2 class="h4 mb-4 text-gray-800">Customer Growth, Sales, and Transactions</h2>

    <!-- Dropdown for selecting the year -->
    <div class="form-group">
        <label for="yearSelect">Select Year:</label>
        <select id="yearSelect" class="form-control" style="width: 150px;">
            <option value="">Last 12 Months</option>
            <?php foreach ($available_years as $year): ?>
                <option value="<?php echo $year['year']; ?>"><?php echo $year['year']; ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <canvas id="customerGrowthSalesChart"></canvas>
</div>

<script>
// Parse PHP arrays to JavaScript
var customerGrowthMonthly = <?php echo json_encode($customer_growth_monthly); ?>;
var salesTransactionsMonthly = <?php echo json_encode($sales_transactions_monthly); ?>;

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

// Event Listener for Year Dropdown
$('#yearSelect').on('change', function() {
    var selectedYear = $(this).val();

    $.ajax({
        url: 'config/fetch_growth_sales_data.php',
        method: 'POST',
        data: { year: selectedYear },
        dataType: 'json',
        success: function(response) {
            var customerData = extractChartData(response.customer_growth, 'new_customers');
            var salesData = extractChartData(response.sales_transactions, 'total_sales');
            var transactionsData = extractChartData(response.sales_transactions, 'transaction_count');
            
            updateChart(customerGrowthSalesChart, customerData.labels, customerData.values, salesData.values, transactionsData.values);
        }
    });
});
</script>

</body>
</html>