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

// Fetch total sales over time
$sales_over_time_stmt = $conn->query("
    SELECT DATE(purchase_datetime) as sale_date, SUM(purchase_amount) as total_sales
    FROM purchase_entries
    GROUP BY sale_date
    ORDER BY sale_date ASC
");
$sales_over_time = $sales_over_time_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch sales by agent
$sales_by_agent_stmt = $conn->query("
    SELECT a.agent_name, SUM(p.purchase_amount) as total_sales
    FROM purchase_entries p
    JOIN admin_access a ON p.agent_id = a.agent_id
    GROUP BY a.agent_name
    ORDER BY total_sales DESC
");
$sales_by_agent = $sales_by_agent_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch sales by category
$sales_by_category_stmt = $conn->query("
    SELECT purchase_category, SUM(purchase_amount) as total_sales
    FROM purchase_entries
    GROUP BY purchase_category
");
$sales_by_category = $sales_by_category_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Performance Dashboard</title>
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
                <h1 class="h3 mb-2 text-gray-800">Sales Performance Dashboard</h1>

                <!-- Total Sales Over Time Chart -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Total Sales Over Time</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="salesOverTimeChart"></canvas>
                    </div>
                </div>

                <!-- Sales by Agent Chart -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Sales by Agent</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="salesByAgentChart"></canvas>
                    </div>
                </div>

                <!-- Sales by Category Chart -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Sales by Category</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="salesByCategoryChart"></canvas>
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

<!-- Chart.js for pie and line charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    // Total Sales Over Time
    var salesOverTimeCtx = document.getElementById('salesOverTimeChart').getContext('2d');
    var salesOverTimeData = {
        labels: <?php echo json_encode(array_column($sales_over_time, 'sale_date')); ?>,
        datasets: [{
            label: 'Total Sales',
            data: <?php echo json_encode(array_column($sales_over_time, 'total_sales')); ?>,
            borderColor: '#4e73df',
            backgroundColor: 'rgba(78, 115, 223, 0.05)',
            pointBackgroundColor: '#4e73df',
            pointBorderColor: '#4e73df',
            fill: true
        }]
    };
    new Chart(salesOverTimeCtx, {
        type: 'line',
        data: salesOverTimeData,
    });

    // Sales by Agent
    var salesByAgentCtx = document.getElementById('salesByAgentChart').getContext('2d');
    var salesByAgentData = {
        labels: <?php echo json_encode(array_column($sales_by_agent, 'agent_name')); ?>,
        datasets: [{
            label: 'Total Sales',
            data: <?php echo json_encode(array_column($sales_by_agent, 'total_sales')); ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    };
    new Chart(salesByAgentCtx, {
        type: 'bar',
        data: salesByAgentData,
    });

    // Sales by Category
    var salesByCategoryCtx = document.getElementById('salesByCategoryChart').getContext('2d');
    var salesByCategoryData = {
        labels: <?php echo json_encode(array_column($sales_by_category, 'purchase_category')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($sales_by_category, 'total_sales')); ?>,
            backgroundColor: ['#4e73df', '#1cc88a'],
        }]
    };
    new Chart(salesByCategoryCtx, {
        type: 'pie',
        data: salesByCategoryData,
    });
});
</script>

</body>
</html>