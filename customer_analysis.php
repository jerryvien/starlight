<?php
session_start();
include('config/database.php'); // Include your database connection

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure the user is logged in; redirect if not
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Determine access level
$access_level = $_SESSION['access_level'];
$agent_id = $_SESSION['agent_id'];

// Fetch customer data based on access level (super_admin sees all, agent sees their own customers)
$customers_query = $access_level === 'super_admin' ? 
    "SELECT c.*, a.agent_name FROM customer_details c LEFT JOIN admin_access a ON c.agent_id = a.agent_id" : 
    "SELECT c.*, a.agent_name FROM customer_details c LEFT JOIN admin_access a ON c.agent_id = a.agent_id WHERE c.agent_id = :agent_id";

$customers_stmt = $conn->prepare($customers_query);
if ($access_level !== 'super_admin') {
    $customers_stmt->bindParam(':agent_id', $agent_id);
}
$customers_stmt->execute();
$customers = $customers_stmt->fetchAll(PDO::FETCH_ASSOC);

// If a customer is selected from the table
$selected_customer_id = isset($_GET['customer_id']) ? $_GET['customer_id'] : null;

if ($selected_customer_id) {
    // Fetch the selected customer's data and calculate the duration since account creation
    $customer_stmt = $conn->prepare("
        SELECT c.*, a.agent_name, 
               TIMESTAMPDIFF(YEAR, c.created_at, CURDATE()) AS customer_age_years,  -- Age in years
               TIMESTAMPDIFF(MONTH, c.created_at, CURDATE()) AS customer_age_months  -- Age in months
        FROM customer_details c
        LEFT JOIN admin_access a ON c.agent_id = a.agent_id
        WHERE c.customer_id = :customer_id
    ");
    $customer_stmt->bindParam(':customer_id', $selected_customer_id);
    $customer_stmt->execute();
    $customer = $customer_stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch performance, sales, and win/loss data for the selected customer
    $performance_stmt = $conn->prepare("
        SELECT 
            COUNT(CASE WHEN result = 'Win' THEN 1 END) AS total_wins,
            COUNT(CASE WHEN result = 'Loss' THEN 1 END) AS total_losses,
            SUM(purchase_amount) AS total_revenue,
            COUNT(*) AS total_transactions,
            MAX(DATE(purchase_datetime)) AS last_purchase_date,
            MAX(CASE WHEN result = 'Win' THEN DATE(purchase_datetime) END) AS last_win_date
        FROM purchase_entries
        WHERE customer_id = :customer_id
    ");
    $performance_stmt->bindParam(':customer_id', $selected_customer_id);
    $performance_stmt->execute();
    $performance = $performance_stmt->fetch(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Customer Analysis</title>

    <!-- Custom fonts for this template -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">

    <!-- Page Wrapper -->
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

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">Customer Analysis</h1>

                    <!-- Data Table to Select Customer -->
                    <div class="table-responsive">
                        <table id="customersTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Customer ID</th>
                                    <th>Customer Name</th>
                                    <th>Agent Name</th>
                                    <th>Created At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><?php echo $customer['customer_id']; ?></td>
                                    <td><?php echo $customer['customer_name']; ?></td>
                                    <td><?php echo $customer['agent_name']; ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($customer['created_at'])); ?></td>
                                    <td>
                                        <a href="customer_analysis.php?customer_id=<?php echo $customer['customer_id']; ?>" class="btn btn-primary">Select</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Selected Customer Details and Analysis -->
                    <?php if (isset($customer)): ?>
                    <h2>Customer Details</h2>
                    <p>Customer ID: <?php echo $customer['customer_id']; ?></p>
                    <p>Customer Name: <?php echo $customer['customer_name']; ?></p>
                    <p>Agent Name: <?php echo $customer['agent_name']; ?></p>
                    <p>Customer Age: <?php echo $customer['customer_age_years']; ?> years (<?php echo $customer['customer_age_months']; ?> months)</p>
                    <p>Last Purchase Date: <?php echo isset($performance['last_purchase_date']) ? $performance['last_purchase_date'] : 'N/A'; ?></p>
                    <p>Last Win Date: <?php echo isset($performance['last_win_date']) ? $performance['last_win_date'] : 'N/A'; ?></p>

                    <!-- Performance Analysis Charts -->
                    <div class="row">
                        <div class="col-md-6">
                            <canvas id="revenueChart"></canvas>
                        </div>
                        <div class="col-md-6">
                            <canvas id="winLossChart"></canvas>
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

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <!-- Chart.js for Analysis Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <?php if (isset($customer)): ?>
    <script>
        // Chart for Revenue over Time
        var ctxRevenue = document.getElementById('revenueChart').getContext('2d');
        var revenueChart = new Chart(ctxRevenue, {
            type: 'line',
            data: {
                labels: ['January', 'February', 'March'], // Placeholder labels; replace with actual data
                datasets: [{
                    label: 'Revenue',
                    data: [1200, 1500, 1800], // Placeholder data; replace with actual data
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            }
        });

        // Chart for Win/Loss Ratio
        var ctxWinLoss = document.getElementById('winLossChart').getContext('2d');
        var winLossChart = new Chart(ctxWinLoss, {
            type: 'doughnut',
            data: {
                labels: ['Wins', 'Losses'],
                datasets: [{
                    label: 'Win/Loss',
                    data: [<?php echo $performance['total_wins']; ?>, <?php echo $performance['total_losses']; ?>],
                    backgroundColor: ['#28a745', '#dc3545']
                }]
            }
        });
    </script>
    <?php endif; ?>

</body>

</html>