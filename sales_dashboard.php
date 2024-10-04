<?php
session_start();
include('config/database.php');

// Set time zone
date_default_timezone_set('Asia/Kuala_Lumpur');

// Error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Fetch KPIs
$total_sales_stmt = $conn->query("SELECT SUM(purchase_amount) as total_sales FROM purchase_entries");
$total_sales = $total_sales_stmt->fetch(PDO::FETCH_ASSOC)['total_sales'];

$total_winnings_stmt = $conn->query("SELECT SUM(winning_amount) as total_winnings FROM purchase_entries WHERE result = 'Win'");
$total_winnings = $total_winnings_stmt->fetch(PDO::FETCH_ASSOC)['total_winnings'];

$total_customers_stmt = $conn->query("SELECT COUNT(DISTINCT customer_id) as total_customers FROM customer_details");
$total_customers = $total_customers_stmt->fetch(PDO::FETCH_ASSOC)['total_customers'];

$top_customer_stmt = $conn->query("
    SELECT cd.customer_name, SUM(pe.purchase_amount) as total_sales
    FROM purchase_entries pe
    JOIN customer_details cd ON pe.customer_id = cd.customer_id
    GROUP BY cd.customer_name
    ORDER BY total_sales DESC
    LIMIT 1
");
$top_customer = $top_customer_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch Customer Revenue Contribution Data (Pareto Chart)
$customer_contribution_stmt = $conn->query("
    SELECT cd.customer_name, SUM(pe.purchase_amount) AS total_sales
    FROM purchase_entries pe
    JOIN customer_details cd ON pe.customer_id = cd.customer_id
    GROUP BY cd.customer_name
    ORDER BY total_sales DESC
");
$customer_contribution = $customer_contribution_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Top Customers Data (Bar Chart)
$top_customers_stmt = $conn->query("
    SELECT cd.customer_name, SUM(pe.purchase_amount) AS total_sales
    FROM purchase_entries pe
    JOIN customer_details cd ON pe.customer_id = cd.customer_id
    GROUP BY cd.customer_name
    ORDER BY total_sales DESC
    LIMIT 10
");
$top_customers = $top_customers_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Win/Loss Ratio by Customer Data (Pie Chart)
$win_loss_ratio_stmt = $conn->query("
    SELECT cd.customer_name, 
           SUM(CASE WHEN pe.result = 'Win' THEN 1 ELSE 0 END) AS wins, 
           SUM(CASE WHEN pe.result = 'Loss' THEN 1 ELSE 0 END) AS losses
    FROM purchase_entries pe
    JOIN customer_details cd ON pe.customer_id = cd.customer_id
    GROUP BY cd.customer_name
");
$win_loss_ratio = $win_loss_ratio_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Average Order Value by Customer Data (Bar Chart)
$average_order_value_stmt = $conn->query("
    SELECT cd.customer_name, AVG(pe.purchase_amount) AS average_order_value
    FROM purchase_entries pe
    JOIN customer_details cd ON pe.customer_id = cd.customer_id
    GROUP BY cd.customer_name
");
$average_order_value = $average_order_value_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Performance Dashboard</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style>
        .chart-container {
            width: 700px;
            height: 500px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div id="wrapper">
    <!-- Sidebar -->
    <?php include('config/sidebar.php'); ?>

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">
        <!-- Main Content -->
        <div id="content">
            <!-- Topbar -->
            <?php include('config/topbar.php'); ?>

            <!-- Page Content -->
            <div class="container-fluid mx-auto px-4">
                <h1 class="h3 mb-2 text-gray-800">Customer Performance Dashboard</h1>

                <!-- Top KPI Cards -->
                <div class="row justify-content-center">
                    <!-- Total Sales -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Sales</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($total_sales, 2); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Winnings -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Winnings</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($total_winnings, 2); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-trophy fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Customer -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Top Customer</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $top_customer['customer_name']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Customers -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Customers</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_customers; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart Containers -->
                <div class="row">
                    <!-- Customer Revenue Contribution (Pareto Chart) -->
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="customerContributionChart"></canvas>
                        </div>
                    </div>

                    <!-- Top Customers (Bar Chart) -->
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="topCustomersChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Win/Loss Ratio by Customer (Pie Chart) -->
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="winLossRatioChart"></canvas>
                        </div>
                    </div>

                    <!-- Average Order Value by Customer (Bar Chart) -->
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="averageOrderValueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Content -->

        <!-- Footer -->
        <?php include('config/footer.php'); ?>
    </div>
    <!-- End of Content Wrapper -->
</div>
<!-- End of Wrapper -->

<!-- Bootstrap core JavaScript-->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- Chart.js for pie and line charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    // Customer Revenue Contribution (Pareto Chart)
    var customerContributionCtx = document.getElementById('customerContributionChart').getContext('2d');
    var customerContributionData = {
        labels: <?php echo json_encode(array_column($customer_contribution, 'customer_name')); ?>,
        datasets: [{
            label: 'Total Sales',
            data: <?php echo json_encode(array_column($customer_contribution, 'total_sales')); ?>,
            borderColor: 'rgba(75, 192, 192, 1)',
            backgroundColor: 'rgba(75, 192, 192, 0.5)',
            fill: false
        }, {
            label: 'Cumulative Percentage',
            data: <?php
                $total_sales = array_sum(array_column($customer_contribution, 'total_sales'));
                $running_total = 0;
                $cumulative_percentage = [];
                foreach ($customer_contribution as $customer) {
                    $running_total += $customer['total_sales'];
                    $cumulative_percentage[] = ($running_total / $total_sales) * 100;
                }
                echo json_encode($cumulative_percentage);
            ?>,
            type: 'line',
            borderColor: 'rgba(255, 99, 132, 1)',
            fill: false
        }]
    };
    new Chart(customerContributionCtx, {
        data: customerContributionData,
    });

    // Top Customers (Bar Chart)
    var topCustomersCtx = document.getElementById('topCustomersChart').getContext('2d');
    var topCustomersData = {
        labels: <?php echo json_encode(array_column($top_customers, 'customer_name')); ?>,
        datasets: [{
            label: 'Total Sales',
            data: <?php echo json_encode(array_column($top_customers, 'total_sales')); ?>,
            backgroundColor: 'rgba(75, 192, 192, 0.5)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
        }]
    };
    new Chart(topCustomersCtx, {
        type: 'bar',
        data: topCustomersData,
    });

    // Win/Loss Ratio by Customer (Pie Chart)
    var winLossRatioCtx = document.getElementById('winLossRatioChart').getContext('2d');
    var winLossRatioData = {
        labels: ['Wins', 'Losses'],
        datasets: [{
            data: [<?php echo $win_loss_ratio[0]['wins']; ?>, <?php echo $win_loss_ratio[0]['losses']; ?>],
            backgroundColor: ['#4e73df', '#e74a3b']
        }]
    };
    new Chart(winLossRatioCtx, {
        type: 'pie',
        data: winLossRatioData,
    });

    // Average Order Value by Customer (Bar Chart)
    var averageOrderValueCtx = document.getElementById('averageOrderValueChart').getContext('2d');
    var averageOrderValueData = {
        labels: <?php echo json_encode(array_column($average_order_value, 'customer_name')); ?>,
        datasets: [{
            label: 'Average Order Value',
            data: <?php echo json_encode(array_column($average_order_value, 'average_order_value')); ?>,
            backgroundColor: 'rgba(153, 102, 255, 0.5)',
            borderColor: 'rgba(153, 102, 255, 1)',
            borderWidth: 1
        }]
    };
    new Chart(averageOrderValueCtx, {
        type: 'bar',
        data: averageOrderValueData,
    });
});
</script>

</body>
</html>