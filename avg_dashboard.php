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
            <div class="container-fluid">
                <h1 class="h3 mb-2 text-gray-800">Customer Performance Dashboard</h1>

                <!-- Customer Revenue Contribution (Pareto Chart) -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Customer Revenue Contribution (Pareto Chart)</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="customerContributionChart"></canvas>
                    </div>
                </div>

                <!-- Top Customers (Bar Chart) -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Top Customers (Bar Chart)</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="topCustomersChart"></canvas>
                    </div>
                </div>

                <!-- Win/Loss Ratio by Customer (Pie Chart) -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Win/Loss Ratio by Customer (Pie Chart)</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="winLossRatioChart"></canvas>
                    </div>
                </div>

                <!-- Average Order Value by Customer (Bar Chart) -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Average Order Value by Customer (Bar Chart)</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="averageOrderValueChart"></canvas>
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

<!-- Custom scripts for all pages-->
<script src="js/sb-admin-2.min.js"></script>

<!-- Chart.js for charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Pareto Chart (Customer Revenue Contribution)
var customerContributionCtx = document.getElementById('customerContributionChart').getContext('2d');
var customerContributionData = {
    labels: <?php echo json_encode(array_column($customer_contribution, 'customer_name')); ?>,
    datasets: [{
        type: 'bar',
        label: 'Total Sales',
        data: <?php echo json_encode(array_column($customer_contribution, 'total_sales')); ?>,
        backgroundColor: 'rgba(54, 162, 235, 0.5)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 1
    }, {
        type: 'line',
        label: 'Cumulative Percentage',
        data: <?php
            $cumulative_percentage = [];
            $total_sales = array_sum(array_column($customer_contribution, 'total_sales'));
            $running_total = 0;
            foreach ($customer_contribution as $customer) {
                $running_total += $customer['total_sales'];
                $cumulative_percentage[] = ($running_total / $total_sales) * 100;
            }
            echo json_encode($cumulative_percentage);
        ?>,
        backgroundColor: 'rgba(255, 99, 132, 0.5)',
        borderColor: 'rgba(255, 99, 132, 1)',
        fill: false
    }]
};
new Chart(customerContributionCtx, {
    type: 'bar',
    data: customerContributionData,
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Top Customers Bar Chart
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
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Win/Loss Ratio Pie Chart
var winLossRatioCtx = document.getElementById('winLossRatioChart').getContext('2d');
var winLossRatioData = {
    labels: ['Wins', 'Losses'],
    datasets: [{
        data: [
            <?php echo array_sum(array_column($win_loss_ratio, 'wins')); ?>,
            <?php echo array_sum(array_column($win_loss_ratio, 'losses')); ?>
        ],
        backgroundColor: ['#4e73df', '#e74a3b'],
    }]
};
new Chart(winLossRatioCtx, {
    type: 'pie',
    data: winLossRatioData
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