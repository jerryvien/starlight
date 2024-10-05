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

// Fetch Month, Year, and Day from GET request
$month = isset($_GET['month']) ? $_GET['month'] : '';
$year = isset($_GET['year']) ? $_GET['year'] : '';
$day = isset($_GET['day']) ? $_GET['day'] : '';

// Construct the WHERE clause for filtering
$where_clauses = [];
if (!empty($year)) { $where_clauses[] = "YEAR(purchase_datetime) = '$year'"; }
if (!empty($month)) { $where_clauses[] = "MONTH(purchase_datetime) = '$month'"; }
if (!empty($day)) { $where_clauses[] = "DAY(purchase_datetime) = '$day'"; }

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Fetch KPIs
$total_sales_stmt = $conn->query("
    SELECT SUM(purchase_amount) as total_sales 
    FROM purchase_entries 
    $where_sql
");
$total_sales = $total_sales_stmt->fetch(PDO::FETCH_ASSOC)['total_sales'] ?? 0;

$total_winnings_stmt = $conn->query("
    SELECT SUM(winning_amount) as total_winnings 
    FROM purchase_entries 
    WHERE result = 'Win'
    $where_sql
");
$total_winnings = $total_winnings_stmt->fetch(PDO::FETCH_ASSOC)['total_winnings'] ?? 0;

$total_customers_stmt = $conn->query("
    SELECT COUNT(DISTINCT customer_id) as total_customers 
    FROM customer_details
");
$total_customers = $total_customers_stmt->fetch(PDO::FETCH_ASSOC)['total_customers'] ?? 0;

$top_agent_stmt = $conn->query("
    SELECT a.agent_name, SUM(p.purchase_amount) as total_sales
    FROM purchase_entries p
    JOIN admin_access a ON p.agent_id = a.agent_id
    $where_sql
    GROUP BY a.agent_name
    ORDER BY total_sales DESC
    LIMIT 1
");
$top_agent = $top_agent_stmt->fetch(PDO::FETCH_ASSOC)['agent_name'] ?? 'N/A';

// Fetch total sales over time
$sales_over_time_stmt = $conn->query("
    SELECT DATE(purchase_datetime) as sale_date, SUM(purchase_amount) as total_sales
    FROM purchase_entries
    $where_sql
    GROUP BY sale_date
    ORDER BY sale_date ASC
");
$sales_over_time = $sales_over_time_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch sales by agent
$sales_by_agent_stmt = $conn->query("
    SELECT a.agent_name, SUM(p.purchase_amount) as total_sales
    FROM purchase_entries p
    JOIN admin_access a ON p.agent_id = a.agent_id
    $where_sql
    GROUP BY a.agent_name
    ORDER BY total_sales DESC
");
$sales_by_agent = $sales_by_agent_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch sales by category
$sales_by_category_stmt = $conn->query("
    SELECT purchase_category, SUM(purchase_amount) as total_sales
    FROM purchase_entries
    $where_sql
    GROUP BY purchase_category
");
$sales_by_category = $sales_by_category_stmt->fetchAll(PDO::FETCH_ASSOC);

// New Chart: Fetch sales by month
$sales_by_month_stmt = $conn->query("
    SELECT MONTHNAME(purchase_datetime) as sale_month, SUM(purchase_amount) as total_sales
    FROM purchase_entries
    $where_sql
    GROUP BY sale_month
    ORDER BY MONTH(purchase_datetime)
");
$sales_by_month = $sales_by_month_stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <style>
        .chart-container {
            width: 400px;
            height: 300px;
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
                <h1 class="h3 mb-2 text-gray-800">Sales Performance Dashboard</h1>

                <!-- Filter Form -->
                <form method="GET" id="filter-form" class="mb-4">
                    <div class="row">
                        <!-- Month Filter -->
                        <div class="col-md-4">
                            <label for="month">Select Month:</label>
                            <select id="month" name="month" class="form-control">
                                <option value="">All</option>
                                <option value="01">January</option>
                                <option value="02">February</option>
                                <option value="03">March</option>
                                <!-- Add remaining months -->
                            </select>
                        </div>

                        <!-- Year Filter -->
                        <div class="col-md-4">
                            <label for="year">Select Year:</label>
                            <select id="year" name="year" class="form-control">
                                <option value="">All</option>
                                <?php for ($i = date("Y"); $i >= 2000; $i--) { echo "<option value='$i'>$i</option>"; } ?>
                            </select>
                        </div>

                        <!-- Day Filter -->
                        <div class="col-md-4">
                            <label for="day">Select Day:</label>
                            <select id="day" name="day" class="form-control">
                                <option value="">All</option>
                                <?php for ($i = 1; $i <= 31; $i++) { echo "<option value='$i'>$i</option>"; } ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">Filter</button>
                </form>

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

                    <!-- Top Agent -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Top Agent</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $top_agent; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-tie fa-2x text-gray-300"></i>
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
                    <!-- Total Sales Over Time (Left) -->
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="salesOverTimeChart"></canvas>
                        </div>
                    </div>

                    <!-- Sales by Agent (Right) -->
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="salesByAgentChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Sales by Category (Left) -->
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="salesByCategoryChart"></canvas>
                        </div>
                    </div>

                    <!-- Sales by Month (New Chart) (Right) -->
                    <div class="col-md-6">
                        <div class="chart-container">
                            <canvas id="salesByMonthChart"></canvas>
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

<!-- Chart.js for charts -->
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
            fill: true
        }]
    };
    new Chart(salesOverTimeCtx, {
        type: 'line',
        data: salesOverTimeData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    ticks: { autoSkip: true }
                },
                y: { beginAtZero: true }
            }
        }
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
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Sales by Category (Bar Chart)
    var salesByCategoryCtx = document.getElementById('salesByCategoryChart').getContext('2d');
    var salesByCategoryData = {
        labels: <?php echo json_encode(array_column($sales_by_category, 'purchase_category')); ?>,
        datasets: [{
            label: 'Total Sales',
            data: <?php echo json_encode(array_column($sales_by_category, 'total_sales')); ?>,
            backgroundColor: 'rgba(75, 192, 192, 0.5)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
        }]
    };
    new Chart(salesByCategoryCtx, {
        type: 'bar',
        data: salesByCategoryData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Sales by Month (New Chart)
    var salesByMonthCtx = document.getElementById('salesByMonthChart').getContext('2d');
    var salesByMonthData = {
        labels: <?php echo json_encode(array_column($sales_by_month, 'sale_month')); ?>,
        datasets: [{
            label: 'Total Sales',
            data: <?php echo json_encode(array_column($sales_by_month, 'total_sales')); ?>,
            backgroundColor: 'rgba(255, 206, 86, 0.5)',
            borderColor: 'rgba(255, 206, 86, 1)',
            borderWidth: 1
        }]
    };
    new Chart(salesByMonthCtx, {
        type: 'bar',
        data: salesByMonthData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
});
</script>

</body>
</html>