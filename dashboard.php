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

// Assuming you have verified the user login and set the user session
$user_id = $_SESSION['agent_id']; // The logged-in user's ID
$ip_address = getUserIP();
$user_agent = $_SERVER['HTTP_USER_AGENT']; // Optional to store browser/device info

// Log the login activity
$stmt = $conn->prepare("
    INSERT INTO user_activity_log (user_id, activity_type, ip_address, login_time, user_agent)
    VALUES (:user_id, 'login', :ip_address, NOW(), :user_agent)
");
$stmt->bindParam(':user_id', $agent_id);
$stmt->bindParam(':ip_address', $ip_address);
$stmt->bindParam(':user_agent', $user_agent);
$stmt->execute();



// Fetch total sales, average order value, and total sales per day
try {
    $sales_query = ($access_level === 'super_admin') ? 
        "SELECT 
            SUM(purchase_amount) AS total_sales, 
            AVG(purchase_amount) AS avg_order_value, 
            SUM(CASE WHEN DATE(purchase_datetime) = CURDATE() THEN purchase_amount END) AS sales_today,  -- Total sales today
            SUM(CASE WHEN MONTH(purchase_datetime) = MONTH(CURDATE()) AND YEAR(purchase_datetime) = YEAR(CURDATE()) THEN purchase_amount END) AS sales_this_month, -- Total sales this month
            SUM(CASE WHEN YEAR(purchase_datetime) = YEAR(CURDATE()) THEN purchase_amount END) AS sales_this_year -- Total sales this year
        FROM purchase_entries" :
        "SELECT 
            SUM(purchase_amount) AS total_sales, 
            AVG(purchase_amount) AS avg_order_value, 
            SUM(CASE WHEN DATE(purchase_datetime) = CURDATE() THEN purchase_amount END) AS sales_today,  -- Total sales today
            SUM(CASE WHEN MONTH(purchase_datetime) = MONTH(CURDATE()) AND YEAR(purchase_datetime) = YEAR(CURDATE()) THEN purchase_amount END) AS sales_this_month, -- Total sales this month
            SUM(CASE WHEN YEAR(purchase_datetime) = YEAR(CURDATE()) THEN purchase_amount END) AS sales_this_year -- Total sales this year
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
         LIMIT 100" : 
        "SELECT p.*, c.customer_name, a.agent_name 
         FROM purchase_entries p 
         JOIN customer_details c ON p.customer_id = c.customer_id 
         JOIN admin_access a ON p.agent_id = a.agent_id 
         WHERE p.agent_id = :agent_id 
         ORDER BY p.purchase_datetime DESC 
         LIMIT 100";

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
        <?php include('config/sidebar.php'); ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <?php include('config/topbar.php'); ?>

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
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Sales for <?php echo date('F Y'); ?> <!-- Dynamically display the current month and year -->
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">RM <?php echo format_number_short($sales_data['sales_this_month']); ?></div> <!-- Monthly sales data -->
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar-alt fa-2x text-gray-300"></i> <!-- Icon representing the calendar/month -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-secondary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                                Sales for <?php echo date('Y'); ?> <!-- Dynamically display the current year -->
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">RM <?php echo format_number_short($sales_data['sales_this_year']); ?></div> <!-- Yearly sales data -->
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
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">RM <?php echo number_format($sales_data['avg_order_value']); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-bolt fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-danger shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                Sales Today (<?php echo date('F j, Y'); ?>) <!-- Display today's date -->
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">RM <?php echo format_number_short($sales_data['sales_today']); ?></div>
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
                    <!-- Enf Of Top Row: KPIs -->

                    


                    <!-- Top 5 Spend and Winner Customers (Bar Charts) -->
                    <div class="container-fluid d-none d-md-block">
                        <!-- Tab Navigation -->
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="top-spend-tab" data-toggle="tab" href="#top-spend" role="tab" aria-controls="top-spend" aria-selected="true">Top Spend</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="top-winner-tab" data-toggle="tab" href="#top-winner" role="tab" aria-controls="top-winner" aria-selected="false">Top Winner</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="top-number-tab" data-toggle="tab" href="#top-number" role="tab" aria-controls="top-number" aria-selected="false">Top Number</a>
                            </li>
                        </ul>

                        <!-- Tab Panes -->
                        <div class="tab-content" id="myTabContent">
                            <!-- First Tab: Top Spend Customers -->
                            <div class="tab-pane fade show active" id="top-spend" role="tabpanel" aria-labelledby="top-spend-tab">
                                <div class="row">
                                    <div class="col-md-6">
                                        <canvas id="topSpendCustomersChart" width="400" height="300"></canvas>
                                    </div>
                                    <div class="col-md-6">
                                        <canvas id="topWinnerCustomersChart" width="400" height="300"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Second Tab: Winning Report -->
                            <div class="tab-pane fade" id="top-winner" role="tabpanel" aria-labelledby="top-winner-tab">
                                <div class="row">
                                    <div class="col-md-6">
                                        <?php include('PHP_Data/growth_report.php'); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?php include('PHP_Data/sales_compare_chart.php'); ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Third Tab: Winning Report -->
                            <div class="tab-pane fade" id="top-number" role="tabpanel" aria-labelledby="top-number-tab">
                                <div class="row">
                                    <div class="col-md-6">
                                        <?php include('PHP_Data/top_winning_number.php'); ?>
                                    </div>
                                    <div class="col-md-6">
                                        
                                    </div>
                                </div>
                            </div>
                            <!-- Third Tab: Winning Report -->
                            <div class="tab-pane fade" id="top-Dashboard" role="tabpanel" aria-labelledby="top-number-tab">
                                <div class="row">
                                    <div class="col-md-6">
                                    <iframe width="600" height="450" src="https://lookerstudio.google.com/embed/reporting/05b5a899-3b5b-44f1-84ba-999a184be778/page/UkPIE" frameborder="0" style="border:0" allowfullscreen sandbox="allow-storage-access-by-user-activation allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox"></iframe>
                                    </div>
                                    <div class="col-md-6">
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Initialize Bootstrap Tabs -->
                    <script>
                        $(document).ready(function () {
                            $('#myTab a').on('click', function (e) {
                                e.preventDefault();
                                $(this).tab('show');
                            });
                        });
                    </script>

                <hr>
                <style>
                    hr {
                        border: none;              /* Remove the default border */
                        border-top: 2px solid #000; /* Create a solid line with 2px thickness and black color */
                        margin: 20px 0;            /* Add spacing above and below the line */
                    }
                </style>

                

                    <!-- Include DataTables CSS and JS -->
                    
                    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
                    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>

                    <!-- Recent Purchases (Table) -->
                    <div class="container-fluid d-none d-md-block">
                        <div class="col-md-12">
                            <h5>Recent Purchases</h5>
                            <table id="recentPurchasesTable" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Agent</th>
                                        <th>Purchase No</th>
                                        <th>Category</th>
                                        <th>Amount</th>
                                        <th>Purchase Date</th>
                                        <th>Status</th> <!-- Changed Result to Status -->
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
                                            
                                            <!-- Format Purchase Date to show Month and Day -->
                                            <td><?php echo date('M d', strtotime($purchase['purchase_datetime'])); ?></td>

                                            <!-- Status with Color Coding -->
                                            <td>
                                                <?php if ($purchase['result'] == 'Pending'): ?>
                                                    <span style="color: red; font-weight: bold;">Pending</span>
                                                <?php elseif ($purchase['result'] == 'Prize Given'): ?>
                                                    <span style="color: green; font-weight: bold;">Prize Given</span>
                                                <?php else: ?>
                                                    <span><?php echo $purchase['result']; ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Initialize DataTable -->
                    <script>
                        $(document).ready(function() {
                            $('#recentPurchasesTable').DataTable({
                                "paging": true,       // Enable pagination
                                "searching": true,    // Enable search/filter functionality
                                "ordering": true,     // Enable column sorting
                                "info": true,         // Show table information
                                "lengthChange": true  // Enable the ability to change the number of records per page
                            });
                        });
                    </script>
                
                    
                </div>
                <!-- End of Page Content -->

                <!-- Footer -->
                <?php include('config/footer.php'); ?>
            </div>
            <!-- End of Content Wrapper -->
        </div>
        <!-- End of Wrapper -->
         <!-- Scroll to Top Button-->
            <a class="scroll-to-top rounded" href="#page-top">
                <i class="fas fa-angle-up"></i>
            </a>

    <script>
    

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


    </script>
</body>
</html>
