<?php
session_start();
include('config/database.php');

// Set time zone to Kuala Lumpur (GMT +8)
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

// Fetch high-level stats (Total Sales, Total Winnings, Top Agent, Total Customers)
$high_level_stmt = $conn->query("
    SELECT SUM(purchase_amount) as total_sales,
           SUM(winning_amount) as total_winnings,
           (SELECT agent_name FROM admin_access a JOIN purchase_entries p ON a.agent_id = p.agent_id GROUP BY agent_name ORDER BY SUM(purchase_amount) DESC LIMIT 1) as top_agent,
           (SELECT COUNT(*) FROM customer_details) as total_customers
");
$high_level_stats = $high_level_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch sales over time
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
    <!-- Bootstrap and FontAwesome are assumed to be loaded already -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="container-fluid">
    <!-- Top Cards for high-level statistics -->
    <div class="row">
        <!-- Total Sales Card -->
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Sales</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($high_level_stats['total_sales'], 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Winnings Card -->
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Winnings</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($high_level_stats['total_winnings'], 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-trophy fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Agent Card -->
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Top Agent</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $high_level_stats['top_agent']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Customers Card -->
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Customers</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $high_level_stats['total_customers']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row">
        <!-- Total Sales Over Time Chart -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Total Sales Over Time</h6>
                </div>
                <div class="card-body">
                    <canvas id="salesOverTimeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Sales by Agent Chart -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Sales by Agent</h6>
                </div>
                <div class="card-body">
                    <canvas id="salesByAgentChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales by Category Chart -->
    <div class="row">
        <div class="col-lg-6 mb-4">
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
</div>

<!-- Chart.js for charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Total Sales Over Time
var salesOverTimeCtx = document.getElementById('salesOverTimeChart').getContext('2d');
var salesOverTimeData = {
    labels: <?php echo json_encode(array_column($sales_over_time, 'sale_date')); ?>,
    datasets: [{
        label: 'Total Sales',
        data: <?php echo json_encode(array_column($sales_over_time, 'total_sales')); ?>,
        borderColor: '#4e73df',
        backgroundColor: 'rgba(78, 115, 223, 0.1)',
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
        label: 'Total Sales',
        data: <?php echo json_encode(array_column($sales_by_category, 'total_sales')); ?>,
        backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc'],
    }]
};
new Chart(salesByCategoryCtx, {
    type: 'pie',
    data: salesByCategoryData,
});
</script>
</body>
</html>