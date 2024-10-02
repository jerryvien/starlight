<?php
include('config/database.php'); // Include your database connection


// Set time zone to Kuala Lumpur (GMT +8)
date_default_timezone_set('Asia/Kuala_Lumpur');

// Error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fetch Daily Sales and Transaction Count
$sales_query = "
    SELECT DATE(purchase_datetime) AS date, 
           SUM(purchase_amount) AS daily_sales, 
           COUNT(*) AS transaction_count
    FROM purchase_entries
    GROUP BY DATE(purchase_datetime)
    ORDER BY date ASC;
";
$sales_stmt = $conn->query($sales_query);
$sales_data = $sales_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Daily Winning Payout
$payout_query = "
    SELECT DATE(winning_date) AS date, 
           SUM(winning_total_payout) AS daily_payout
    FROM winning_record
    GROUP BY DATE(winning_date)
    ORDER BY date ASC;
";
$payout_stmt = $conn->query($payout_query);
$payout_data = $payout_stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for Chart.js
$dates = [];
$daily_sales = [];
$daily_payouts = [];
$transaction_counts = [];

// Combine data for daily sales, payout, and transaction count
foreach ($sales_data as $sale) {
    $dates[] = $sale['date'];
    $daily_sales[] = $sale['daily_sales'];
    $transaction_counts[] = $sale['transaction_count'];
    
    // Find corresponding payout for this date
    $payout = array_filter($payout_data, function ($p) use ($sale) {
        return $p['date'] == $sale['date'];
    });
    
    if (!empty($payout)) {
        $daily_payouts[] = array_values($payout)[0]['daily_payout'];
    } else {
        $daily_payouts[] = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Sales and Payout Comparison</title>
    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="container">
    <h2>Daily Sales, Payouts, and Transaction Count</h2>
    <canvas id="salesPayoutChart"></canvas>
</div>

<script>
// Prepare the data from PHP
var dates = <?php echo json_encode($dates); ?>;
var dailySales = <?php echo json_encode($daily_sales); ?>;
var dailyPayouts = <?php echo json_encode($daily_payouts); ?>;
var transactionCounts = <?php echo json_encode($transaction_counts); ?>;

// Create Chart.js Line Chart
var ctx = document.getElementById('salesPayoutChart').getContext('2d');
var salesPayoutChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: dates, // Dates from SQL data
        datasets: [
            {
                label: 'Daily Sales',
                data: dailySales,
                borderColor: 'blue',
                fill: false
            },
            {
                label: 'Daily Winning Payouts',
                data: dailyPayouts,
                borderColor: 'green',
                fill: false
            },
            {
                label: 'Transaction Count',
                data: transactionCounts,
                borderColor: 'red',
                fill: false
            }
        ]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

</body>
</html>