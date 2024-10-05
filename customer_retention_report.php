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

// Fetch agents for the filter dropdown
$agents_stmt = $conn->query("SELECT agent_id, agent_name FROM admin_access");
$agents = $agents_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle filter form submission
$start_date = $_POST['start_date'] ?? date('Y-m-01'); // Default to the first day of the current month
$end_date = $_POST['end_date'] ?? date('Y-m-d');      // Default to today's date
$agent_id = $_POST['agent'] ?? '';

// SQL for Inactive Customers
$inactive_days = 90; // Adjust to 30, 60, or 90 as needed
$inactive_customers_query = "
    SELECT cd.customer_name, cd.total_sales, cd.updated_at, a.agent_name 
    FROM customer_details cd
    LEFT JOIN admin_access a ON cd.agent_id = a.agent_id
    WHERE DATEDIFF(NOW(), cd.updated_at) > :inactive_days
      AND cd.total_sales > 0";

if ($agent_id) {
    $inactive_customers_query .= " AND cd.agent_id = :agent_id";
}

$inactive_customers_stmt = $conn->prepare($inactive_customers_query);
$inactive_customers_stmt->bindParam(':inactive_days', $inactive_days, PDO::PARAM_INT);
if ($agent_id) {
    $inactive_customers_stmt->bindParam(':agent_id', $agent_id);
}
$inactive_customers_stmt->execute();
$inactive_customers = $inactive_customers_stmt->fetchAll(PDO::FETCH_ASSOC);

// SQL for Customer Activity Trends
$customer_activity_query = "
    SELECT DATE(purchase_datetime) as purchase_date, COUNT(*) as purchase_count, c.customer_name
    FROM purchase_entries pe
    JOIN customer_details c ON pe.customer_id = c.customer_id
    WHERE DATE(purchase_datetime) BETWEEN :start_date AND :end_date";

if ($agent_id) {
    $customer_activity_query .= " AND pe.agent_id = :agent_id";
}

$customer_activity_query .= " GROUP BY purchase_date, c.customer_name ORDER BY purchase_date ASC";
$customer_activity_stmt = $conn->prepare($customer_activity_query);
$customer_activity_stmt->bindParam(':start_date', $start_date);
$customer_activity_stmt->bindParam(':end_date', $end_date);
if ($agent_id) {
    $customer_activity_stmt->bindParam(':agent_id', $agent_id);
}
$customer_activity_stmt->execute();
$customer_activity = $customer_activity_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Retention and Activity Dashboard</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <h1 class="h3 mb-2 text-gray-800">Customer Retention & Activity Dashboard</h1>

                <!-- Filters: Date Range & Agent -->
                <form method="POST" action="">
                    <label for="start_date">Start Date:</label>
                    <input type="date" name="start_date" id="start_date" value="<?php echo $start_date; ?>">
                    <label for="end_date">End Date:</label>
                    <input type="date" name="end_date" id="end_date" value="<?php echo $end_date; ?>">
                    <label for="agent">Agent:</label>
                    <select name="agent">
                        <option value="">All Agents</option>
                        <!-- Dynamically populate agents -->
                        <?php foreach ($agents as $agent): ?>
                            <option value="<?php echo $agent['agent_id']; ?>" <?php echo $agent_id == $agent['agent_id'] ? 'selected' : ''; ?>><?php echo $agent['agent_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>

                <!-- Inactive Customers (Bar Chart or Table) -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h4>Inactive Customers (Bar Chart)</h4>
                        <canvas id="inactiveCustomersChart"></canvas>
                    </div>
                    <div class="col-md-6">
                        <h4>Inactive Customers (Table)</h4>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Customer Name</th>
                                    <th>Agent</th>
                                    <th>Total Sales</th>
                                    <th>Last Purchase</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inactive_customers as $customer): ?>
                                    <tr>
                                        <td><?php echo $customer['customer_name']; ?></td>
                                        <td><?php echo $customer['agent_name']; ?></td>
                                        <td>$<?php echo number_format($customer['total_sales'], 2); ?></td>
                                        <td><?php echo date('d-M-Y', strtotime($customer['updated_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Customer Activity Trends (Line Chart) -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h4>Customer Activity Trends</h4>
                        <canvas id="customerActivityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Content Wrapper -->
    </div>
</div>

<script>
    // Inactive Customers Bar Chart
    var ctx = document.getElementById('inactiveCustomersChart').getContext('2d');
    var inactiveCustomersChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($inactive_customers, 'customer_name')); ?>,
            datasets: [{
                label: 'Total Sales',
                data: <?php echo json_encode(array_column($inactive_customers, 'total_sales')); ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.5)',
                borderColor: 'rgba(75, 192, 192, 1)',
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

    // Customer Activity Trends Line Chart
    var ctx = document.getElementById('customerActivityChart').getContext('2d');
    var customerActivityChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($customer_activity, 'purchase_date')); ?>,
            datasets: [{
                label: 'Purchase Count',
                data: <?php echo json_encode(array_column($customer_activity, 'purchase_count')); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                fill: false
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
</script>
</body>
</html>