<?php
session_start();
include('config/database.php'); // Include your database connection


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Redirect to login page if the user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Get the current user's access level
$access_level = $_SESSION['access_level'];
$agent_id = $_SESSION['agent_id'];

// Define the WHERE clause based on access level (agent vs. super_admin)
$where_clause = "";
$params = [];

if ($access_level !== 'super_admin') {
    $where_clause = "WHERE p.agent_id = :agent_id";
    $params[':agent_id'] = $agent_id;
}

// Error handling for database queries
function executeQuery($sql, $params, $conn) {
    try {
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log the error and show a generic message
        error_log("Database Query Error: " . $e->getMessage());
        return [];
    }
}

// Fetch total sales for each day (Line Chart: Total Sales Overview)
$sales_by_date_sql = "
    SELECT DATE(p.purchase_datetime) as purchase_date, SUM(p.purchase_amount) as total_sales
    FROM purchase_entries p
    $where_clause
    GROUP BY DATE(p.purchase_datetime)
    ORDER BY DATE(p.purchase_datetime) ASC
";
$sales_by_date_results = executeQuery($sales_by_date_sql, $params, $conn);
$line_chart_labels = json_encode(array_column($sales_by_date_results, 'purchase_date'));
$line_chart_data = json_encode(array_column($sales_by_date_results, 'total_sales'));

// Fetch top 5 customers by sales (Bar Chart: Top 5 Customers)
$top_customers_sql = "
    SELECT c.customer_name, SUM(p.purchase_amount) as total_sales
    FROM purchase_entries p
    JOIN customer_details c ON p.customer_id = c.customer_id
    $where_clause
    GROUP BY c.customer_name
    ORDER BY total_sales DESC
    LIMIT 5
";
$top_customers_results = executeQuery($top_customers_sql, $params, $conn);
$bar_chart_labels = json_encode(array_column($top_customers_results, 'customer_name'));
$bar_chart_data = json_encode(array_column($top_customers_results, 'total_sales'));

// Fetch sales breakdown by category (Pie/Donut Chart: Sales by Category)
$sales_by_category_sql = "
    SELECT p.purchase_category, SUM(p.purchase_amount) as total_sales
    FROM purchase_entries p
    $where_clause
    GROUP BY p.purchase_category
";
$sales_by_category_results = executeQuery($sales_by_category_sql, $params, $conn);
$pie_chart_labels = json_encode(array_column($sales_by_category_results, 'purchase_category'));
$pie_chart_data = json_encode(array_column($sales_by_category_results, 'total_sales'));

// Fetch average order value for the current month (KPI: Average Order Value)
$average_order_value_sql = "
    SELECT AVG(p.purchase_amount) as average_order_value
    FROM purchase_entries p
    WHERE MONTH(p.purchase_datetime) = MONTH(CURRENT_DATE())
    AND YEAR(p.purchase_datetime) = YEAR(CURRENT_DATE())
    " . ($access_level !== 'super_admin' ? "AND p.agent_id = :agent_id" : "");
$average_order_value_results = executeQuery($average_order_value_sql, $params, $conn);
$average_order_value = $average_order_value_results[0]['average_order_value'] ?? 0;

// Fetch customer growth over time (Line Chart: Customer Growth)
$customer_growth_sql = "
    SELECT DATE(c.created_at) as registration_date, COUNT(c.customer_id) as new_customers
    FROM customer_details c
    GROUP BY DATE(c.created_at)
    ORDER BY DATE(c.created_at) ASC
";
$customer_growth_results = executeQuery($customer_growth_sql, [], $conn);
$customer_growth_labels = json_encode(array_column($customer_growth_results, 'registration_date'));
$customer_growth_data = json_encode(array_column($customer_growth_results, 'new_customers'));

// Fetch sales by agent (for Super_admin) (Bar Chart: Sales by Agent)
if ($access_level === 'super_admin') {
    $sales_by_agent_sql = "
        SELECT a.agent_name, SUM(p.purchase_amount) as total_sales
        FROM purchase_entries p
        JOIN admin_access a ON p.agent_id = a.agent_id
        GROUP BY a.agent_name
    ";
    $sales_by_agent_results = executeQuery($sales_by_agent_sql, [], $conn);
    $agent_bar_chart_labels = json_encode(array_column($sales_by_agent_results, 'agent_name'));
    $agent_bar_chart_data = json_encode(array_column($sales_by_agent_results, 'total_sales'));
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Business Dashboard</title>

    <!-- Custom fonts and styles for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <script src="vendor/jquery/jquery.min.js"></script>
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include('sidebar.php'); ?>
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <?php include('topbar.php'); ?>
                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Business Dashboard</h1>

                    <!-- Top Row: KPIs -->
                    <div class="row">
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Sales</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($average_order_value, 2); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Avg. Order Value</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($average_order_value, 2); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Middle Row: Sales Overview and Breakdown -->
                    <div class="row">
                        <div class="col-lg-6">
                            <!-- Line Chart for Total Sales -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Total Sales Overview</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="salesLineChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <!-- Bar Chart for Top 5 Customers -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Top 5 Customers</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="salesBarChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Row: Sales Breakdown by Category and Customer Growth -->
                    <div class="row">
                        <div class="col-lg-6">
                            <!-- Pie Chart for Sales Breakdown by Category -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Sales Breakdown by Category</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="salesCategoryPieChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <!-- Line Chart for Customer Growth -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Customer Growth Over Time</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="customerGrowthChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Super Admin Only: Sales by Agent -->
                    <?php if ($access_level === 'super_admin'): ?>
                    <div class="row">
                        <div class="col-lg-12">
                            <!-- Bar Chart for Sales by Agent -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Sales by Agent</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="salesAgentBarChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include('footer.php'); ?>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Chart.js Script -->
    <script>
        // Line Chart for Sales by Date
        const lineChartCtx = document.getElementById('salesLineChart').getContext('2d');
        const salesLineChart = new Chart(lineChartCtx, {
            type: 'line',
            data: {
                labels: <?php echo $line_chart_labels; ?>,
                datasets: [{
                    label: 'Total Sales',
                    data: <?php echo $line_chart_data; ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Bar Chart for Top 5 Customers
        const barChartCtx = document.getElementById('salesBarChart').getContext('2d');
        const salesBarChart = new Chart(barChartCtx, {
            type: 'bar',
            data: {
                labels: <?php echo $bar_chart_labels; ?>,
                datasets: [{
                    label: 'Total Sales',
                    data: <?php echo $bar_chart_data; ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Pie Chart for Sales Breakdown by Category
        const pieChartCtx = document.getElementById('salesCategoryPieChart').getContext('2d');
        const salesCategoryPieChart = new Chart(pieChartCtx, {
            type: 'pie',
            data: {
                labels: <?php echo $pie_chart_labels; ?>,
                datasets: [{
                    label: 'Sales by Category',
                    data: <?php echo $pie_chart_data; ?>,
                    backgroundColor: ['rgba(75, 192, 192, 0.2)', 'rgba(153, 102, 255, 0.2)'],
                    borderColor: ['rgba(75, 192, 192, 1)', 'rgba(153, 102, 255, 1)'],
                    borderWidth: 1
                }]
            }
        });

        // Line Chart for Customer Growth Over Time
        const customerGrowthCtx = document.getElementById('customerGrowthChart').getContext('2d');
        const customerGrowthChart = new Chart(customerGrowthCtx, {
            type: 'line',
            data: {
                labels: <?php echo $customer_growth_labels; ?>,
                datasets: [{
                    label: 'New Customers',
                    data: <?php echo $customer_growth_data; ?>,
                    backgroundColor: 'rgba(255, 206, 86, 0.2)',
                    borderColor: 'rgba(255, 206, 86, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Bar Chart for Sales by Agent (only for Super Admin)
        <?php if ($access_level === 'super_admin'): ?>
        const agentBarChartCtx = document.getElementById('salesAgentBarChart').getContext('2d');
        const salesAgentBarChart = new Chart(agentBarChartCtx, {
            type: 'bar',
            data: {
                labels: <?php echo $agent_bar_chart_labels; ?>,
                datasets: [{
                    label: 'Total Sales',
                    data: <?php echo $agent_bar_chart_data; ?>,
                    backgroundColor: 'rgba(255, 159, 64, 0.2)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1
                }]
            });
        <?php endif; ?>
    </script>
</body>

</html>
