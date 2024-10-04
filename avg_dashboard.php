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
    SELECT c.customer_name, SUM(p.purchase_amount) as total_sales
    FROM purchase_entries p
    JOIN customer_details c ON p.customer_id = c.customer_id
    GROUP BY c.customer_name
    ORDER BY total_sales DESC
    LIMIT 1
");
$top_customer = $top_customer_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch customer revenue contribution for Pareto chart
$customer_contribution_stmt = $conn->query("
    SELECT c.customer_name, SUM(p.purchase_amount) as total_sales
    FROM purchase_entries p
    JOIN customer_details c ON p.customer_id = c.customer_id
    GROUP BY c.customer_name
    ORDER BY total_sales DESC
");
$customer_contribution = $customer_contribution_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch top customers for bar chart
$top_customers_stmt = $conn->query("
    SELECT c.customer_name, SUM(p.purchase_amount) as total_sales
    FROM purchase_entries p
    JOIN customer_details c ON p.customer_id = c.customer_id
    GROUP BY c.customer_name
    ORDER BY total_sales DESC
    LIMIT 10
");
$top_customers = $top_customers_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch win/loss ratio for pie chart
$win_loss_stmt = $conn->query("
    SELECT 
        COUNT(CASE WHEN result = 'Win' THEN 1 END) AS win_count, 
        COUNT(CASE WHEN result = 'Loss' THEN 1 END) AS loss_count 
    FROM purchase_entries
");
$win_loss = $win_loss_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch average order value for bar chart
$avg_order_value_stmt = $conn->query("
    SELECT c.customer_name, AVG(p.purchase_amount) as avg_order_value
    FROM purchase_entries p
    JOIN customer_details c ON p.customer_id = c.customer_id
    GROUP BY c.customer_name
    ORDER BY avg_order_value DESC
    LIMIT 10
");
$avg_order_value = $avg_order_value_stmt->fetchAll(PDO::FETCH_ASSOC);
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
                        <!-- Win/Loss Ratio (Pie Chart) -->
                        <div class="col-md-6">
                            <div class="chart-container">
                                <canvas id="winLossChart"></canvas>
                            </div>
                        </div>

                        <!-- Average Order Value by Customer (Bar Chart) -->
                        <div class="col-md-6">
                            <div class="chart-container">
                                <canvas id="avgOrderValueChart"></canvas>
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
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

    <script>
    $(document).ready(function() {
        // Customer Revenue Contribution (Pareto Chart)
        var customerContributionCtx = document.getElementById('customerContributionChart').getContext('2d');
        var customerContributionData = {
            labels: <?php echo json_encode(array_column($customer_contribution, 'customer_name')); ?>,
            datasets: [{
                label: 'Total Sales',
                data: <?php echo json_encode(array_column($customer_contribution, 'total_sales')); ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.5)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        };
        new Chart(customerContributionCtx, {
            type: 'bar',
            data: customerContributionData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString(); // Format Y-axis values as currency
                            }
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        display: true,
                        align: 'end',
                        anchor: 'end',
                        formatter: function(value) {
                            return '$' + value.toLocaleString(); // Show formatted value above bars
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });

        // Top Customers (Bar Chart)
        var topCustomersCtx = document.getElementById('topCustomersChart').getContext('2d');
        var topCustomersData = {
            labels: <?php echo json_encode(array_column($top_customers, 'customer_name')); ?>,
            datasets: [{
                label: 'Total Sales',
                data: <?php echo json_encode(array_column($top_customers, 'total_sales')); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        };
        new Chart(topCustomersCtx, {
            type: 'bar',
            data: topCustomersData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString(); // Format Y-axis values as currency
                            }
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        display: true,
                        align: 'end',
                        anchor: 'end',
                        formatter: function(value) {
                            return '$' + value.toLocaleString(); // Show formatted value above bars
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });

        // Win/Loss Ratio (Pie Chart)
        var winLossCtx = document.getElementById('winLossChart').getContext('2d');
        var winLossData = {
            labels: ['Win', 'Loss'],
            datasets: [{
                data: [
                    <?php echo $win_loss['win_count']; ?>, 
                    <?php echo $win_loss['loss_count']; ?>
                ],
                backgroundColor: ['#4e73df', '#e74a3b'],
            }]
        };
        new Chart(winLossCtx, {
            type: 'pie',
            data: winLossData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                var label = data.labels[tooltipItem.dataIndex];
                                var value = data.datasets[0].data[tooltipItem.dataIndex];
                                var total = data.datasets[0].data.reduce((acc, cur) => acc + cur, 0);
                                var percentage = (value / total * 100).toFixed(2);
                                return label + ': ' + percentage + '%';
                            }
                        }
                    }
                }
            }
        });

        // Average Order Value by Customer (Bar Chart)
        var avgOrderValueCtx = document.getElementById('avgOrderValueChart').getContext('2d');
        var avgOrderValueData = {
            labels: <?php echo json_encode(array_column($avg_order_value, 'customer_name')); ?>,
            datasets: [{
                label: 'Average Order Value',
                data: <?php echo json_encode(array_column($avg_order_value, 'avg_order_value')); ?>,
                backgroundColor: 'rgba(255, 159, 64, 0.5)',
                borderColor: 'rgba(255, 159, 64, 1)',
                borderWidth: 1
            }]
        };
        new Chart(avgOrderValueCtx, {
            type: 'bar',
            data: avgOrderValueData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString(); // Format Y-axis values as currency
                            }
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        display: true,
                        align: 'end',
                        anchor: 'end',
                        formatter: function(value) {
                            return '$' + value.toFixed(2).toLocaleString(); // Show formatted value with 2 decimal places above bars
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    });
    </script>

    </body>
    </html>