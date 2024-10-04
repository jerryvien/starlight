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
    // Debugging output
    console.log(<?php echo json_encode(array_column($customer_contribution, 'customer_name')); ?>);
    console.log(<?php echo json_encode(array_column($top_customers, 'total_sales')); ?>);

    // Pareto Chart
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

    // Other chart code here...
});
</script>

</body>
</html>