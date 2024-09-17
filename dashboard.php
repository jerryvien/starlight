<?php
session_start();
include('config/database.php'); // Database connection
include('config/utilities.php'); // utilities connection


// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Check user access level
$access_level = $_SESSION['access_level'];
$agent_id = $_SESSION['agent_id'];

// Fetch total sales, average order value, and total sales per day
try {
    $sales_query = ($access_level === 'super_admin') ? 
        "SELECT 
            SUM(purchase_amount) AS total_sales, 
            AVG(purchase_amount) AS avg_order_value, 
            COUNT(CASE WHEN DATE(purchase_datetime) = CURDATE() THEN 1 END) AS sales_today,
            SUM(CASE WHEN MONTH(purchase_datetime) = MONTH(CURDATE()) AND YEAR(purchase_datetime) = YEAR(CURDATE()) THEN 1 END) AS sales_this_month,
            SUM(CASE WHEN YEAR(purchase_datetime) = YEAR(CURDATE()) THEN 1 END) AS sales_this_year
        FROM purchase_entries" :
        "SELECT 
            SUM(purchase_amount) AS total_sales, 
            AVG(purchase_amount) AS avg_order_value, 
            COUNT(CASE WHEN DATE(purchase_datetime) = CURDATE() THEN 1 END) AS sales_today,
            SUM(CASE WHEN MONTH(purchase_datetime) = MONTH(CURDATE()) AND YEAR(purchase_datetime) = YEAR(CURDATE()) THEN 1 END) AS sales_this_month,
            SUM(CASE WHEN YEAR(purchase_datetime) = YEAR(CURDATE()) THEN 1 END) AS sales_this_year
        FROM purchase_entries 
        WHERE agent_id = :agent_id";

    $sales_stmt = $conn->prepare($sales_query);
    if ($access_level !== 'super_admin') {
        $sales_stmt->bindParam(':agent_id', $agent_id);
    }
    $sales_stmt->execute();
    $sales_data = $sales_stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching sales data: " . $e->getMessage());
}
//Fetch customer who register last 30 days
try {
    // Fetch number of new customers registered in the last 30 days
    $new_customers_query = ($access_level === 'super_admin') ? 
        "SELECT COUNT(*) AS new_customers_last_30_days FROM customer_details WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)" :
        "SELECT COUNT(*) AS new_customers_last_30_days FROM customer_details WHERE agent_id = :agent_id AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

    $new_customers_stmt = $conn->prepare($new_customers_query);
    if ($access_level !== 'super_admin') {
        $new_customers_stmt->bindParam(':agent_id', $agent_id);
    }
    $new_customers_stmt->execute();
    $new_customers_data = $new_customers_stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching new customer data: " . $e->getMessage());
}


// Fetch sales by category (Pie chart)
try {
    $category_query = ($access_level === 'super_admin') ? 
        "SELECT purchase_category, SUM(purchase_amount) AS total_sales FROM purchase_entries GROUP BY purchase_category" : 
        "SELECT purchase_category, SUM(purchase_amount) AS total_sales FROM purchase_entries WHERE agent_id = :agent_id GROUP BY purchase_category";

    $category_stmt = $conn->prepare($category_query);
    if ($access_level !== 'super_admin') {
        $category_stmt->bindParam(':agent_id', $agent_id);
    }
    $category_stmt->execute();
    $category_sales = $category_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching sales by category: " . $e->getMessage());
}

// Fetch top 5 spend and winner customers (Bar chart)
try {
    $top_spend_query = ($access_level === 'super_admin') ? 
        "SELECT c.customer_name, SUM(p.purchase_amount) AS total_spent 
         FROM purchase_entries p 
         JOIN customer_details c ON p.customer_id = c.customer_id 
         GROUP BY p.customer_id 
         ORDER BY total_spent DESC 
         LIMIT 5" : 
        "SELECT c.customer_name, SUM(p.purchase_amount) AS total_spent 
         FROM purchase_entries p 
         JOIN customer_details c ON p.customer_id = c.customer_id 
         WHERE p.agent_id = :agent_id 
         GROUP BY p.customer_id 
         ORDER BY total_spent DESC 
         LIMIT 5";

    $top_winner_query = ($access_level === 'super_admin') ? 
        "SELECT c.customer_name, COUNT(*) AS total_wins 
         FROM purchase_entries p 
         JOIN customer_details c ON p.customer_id = c.customer_id 
         WHERE p.result = 'Win' 
         GROUP BY p.customer_id 
         ORDER BY total_wins DESC 
         LIMIT 5" : 
        "SELECT c.customer_name, COUNT(*) AS total_wins 
         FROM purchase_entries p 
         JOIN customer_details c ON p.customer_id = c.customer_id 
         WHERE p.agent_id = :agent_id AND p.result = 'Win' 
         GROUP BY p.customer_id 
         ORDER BY total_wins DESC 
         LIMIT 5";

    $top_spend_stmt = $conn->prepare($top_spend_query);
    $top_winner_stmt = $conn->prepare($top_winner_query);
    if ($access_level !== 'super_admin') {
        $top_spend_stmt->bindParam(':agent_id', $agent_id);
        $top_winner_stmt->bindParam(':agent_id', $agent_id);
    }
    $top_spend_stmt->execute();
    $top_winner_stmt->execute();
    $top_spend_customers = $top_spend_stmt->fetchAll(PDO::FETCH_ASSOC);
    $top_winner_customers = $top_winner_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching top spend/winner customers: " . $e->getMessage());
}

// Fetch customer count growth (Line chart)
try {
    $customer_growth_query = ($access_level === 'super_admin') ? 
        "SELECT DATE(created_at) AS date, COUNT(*) AS new_customers 
         FROM customer_details 
         GROUP BY DATE(created_at)" : 
        "SELECT DATE(created_at) AS date, COUNT(*) AS new_customers 
         FROM customer_details WHERE agent_id = :agent_id 
         GROUP BY DATE(created_at)";

    $customer_growth_stmt = $conn->prepare($customer_growth_query);
    if ($access_level !== 'super_admin') {
        $customer_growth_stmt->bindParam(':agent_id', $agent_id);
    }
    $customer_growth_stmt->execute();
    $customer_growth = $customer_growth_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching customer growth data: " . $e->getMessage());
}

// Fetch recent purchases (Table)
try {
    $recent_purchases_query = ($access_level === 'super_admin') ? 
        "SELECT p.*, c.customer_name, a.agent_name 
         FROM purchase_entries p 
         JOIN customer_details c ON p.customer_id = c.customer_id 
         JOIN admin_access a ON p.agent_id = a.agent_id 
         ORDER BY p.purchase_datetime DESC 
         LIMIT 10" : 
        "SELECT p.*, c.customer_name, a.agent_name 
         FROM purchase_entries p 
         JOIN customer_details c ON p.customer_id = c.customer_id 
         JOIN admin_access a ON p.agent_id = a.agent_id 
         WHERE p.agent_id = :agent_id 
         ORDER BY p.purchase_datetime DESC 
         LIMIT 10";

    $recent_purchases_stmt = $conn->prepare($recent_purchases_query);
    if ($access_level !== 'super_admin') {
        $recent_purchases_stmt->bindParam(':agent_id', $agent_id);
    }
    $recent_purchases_stmt->execute();
    $recent_purchases = $recent_purchases_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching recent purchases: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Dashboard</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <script src="vendor/chart.js/Chart.min.js"></script>
</head>
<body id="page-top">
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
                    <!-- Total Sales, Total Sales Per Day, Average Order Value -->
                    <!-- Top Row: KPIs -->
                    <div class="row">
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Sales</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">RM <?php echo format_number_short($sales_data['total_sales']); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Sales for <?php echo date('F Y'); ?> <!-- Dynamically display the current month and year -->
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo format_number_short($sales_data['sales_this_month'];) ?></div> <!-- Monthly sales data -->
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar-alt fa-2x text-gray-300"></i> <!-- Icon representing the calendar/month -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Sales for <?php echo date('Y'); ?> <!-- Dynamically display the current year -->
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo format_number_short($sales_data['sales_this_year'];) ?></div> <!-- Yearly sales data -->
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-bar fa-2x text-gray-300"></i> <!-- Icon representing a bar chart for yearly sales -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Average Purchase Value</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">RM <?php echo format_number_short($sales_data['avg_order_value']); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-bolt fa-2x text-gray-300"></i>
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
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Sales Per Day (<?php echo date('F j, Y'); ?>) <!-- Display today's date -->
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $sales_data['sales_today']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2"> <!-- Gold border -->
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">New Customers (Last 30 Days)</div> <!-- Updated text -->
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $new_customers_data['new_customers_last_30_days']; ?></div> <!-- New customer data -->
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-user fa-2x text-gray-300"></i> <!-- Customer/user icon -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    

                    

                    <!-- Top 5 Spend and Winner Customers (Bar Charts) -->
                    <div class="row">
                        <div class="col-md-6">
                            <canvas id="topSpendCustomersChart"></canvas>
                        </div>
                        <div class="col-md-6">
                            <canvas id="topWinnerCustomersChart"></canvas>
                        </div>
                    </div>

                    <!-- Customer Performance (Line Chart) -->
                    <div class="row">
                        <div class="col-md-12">
                            <canvas id="customerGrowthChart"></canvas>
                        </div>
                    </div>

                    <!-- Sales by Category (Pie Chart) -->
                    <div class="row">
                        <div class="col-md-6">
                            <canvas id="salesByCategoryChart"></canvas>
                        </div>
                    </div>

                    <!-- Recent Purchases (Table) -->
                    <div class="row">
                        <div class="col-md-12">
                            <h5>Recent Purchases</h5>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Agent</th>
                                        <th>Purchase No</th>
                                        <th>Category</th>
                                        <th>Amount</th>
                                        <th>Purchase Date</th>
                                        <th>Result</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_purchases as $purchase): ?>
                                        <tr>
                                            <td><?php echo $purchase['customer_name']; ?></td>
                                            <td><?php echo $purchase['agent_name']; ?></td>
                                            <td><?php echo $purchase['purchase_no']; ?></td>
                                            <td><?php echo $purchase['purchase_category']; ?></td>
                                            <td><?php echo number_format($purchase['purchase_amount'], 2); ?></td>
                                            <td><?php echo $purchase['purchase_datetime']; ?></td>
                                            <td><?php echo $purchase['result']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- End of Page Content -->

                <!-- Footer -->
                <?php include('footer.php'); ?>
            </div>
            <!-- End of Content Wrapper -->
        </div>
        <!-- End of Wrapper -->

    <script>
    // Sales by Category Chart
    var ctx = document.getElementById('salesByCategoryChart').getContext('2d');
    var salesByCategoryChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_column($category_sales, 'purchase_category')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($category_sales, 'total_sales')); ?>,
                backgroundColor: ['#007bff', '#28a745']
            }]
        }
    });

    // Top Spend Customers Chart
    var ctx = document.getElementById('topSpendCustomersChart').getContext('2d');
    var topSpendCustomersChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($top_spend_customers, 'customer_name')); ?>,
            datasets: [{
                label: 'Total Spent (RM)',
                data: <?php echo json_encode(array_column($top_spend_customers, 'total_spent')); ?>,
                backgroundColor: '#ffc107'
            }]
        }
    });

    // Top Winner Customers Chart
    var ctx = document.getElementById('topWinnerCustomersChart').getContext('2d');
    var topWinnerCustomersChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($top_winner_customers, 'customer_name')); ?>,
            datasets: [{
                label: 'Total Wins',
                data: <?php echo json_encode(array_column($top_winner_customers, 'total_wins')); ?>,
                backgroundColor: '#17a2b8'
            }]
        }
    });

    // Customer Growth Chart
    var ctx = document.getElementById('customerGrowthChart').getContext('2d');
    var customerGrowthChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($customer_growth, 'date')); ?>,
            datasets: [{
                label: 'New Customers',
                data: <?php echo json_encode(array_column($customer_growth, 'new_customers')); ?>,
                backgroundColor: '#28a745',
                borderColor: '#28a745',
                fill: false
            }]
        }
    });
    </script>
</body>
</html>
