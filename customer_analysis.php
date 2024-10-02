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
    // Fetch the selected customer's data
    $customer_stmt = $conn->prepare("
        SELECT c.*, a.agent_name 
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

    $win_rate = $performance['total_wins'] / max($performance['total_transactions'], 1) * 100;
    $loss_rate = $performance['total_losses'] / max($performance['total_transactions'], 1) * 100;
    $active_ratio = $performance['total_transactions'] / 100;

    // Prepare data for charts
    $sales_dates = [];
    $sales_amounts = [];
    $wins = [];
    $losses = [];

    // Fetch history data for line chart
    $history_stmt = $conn->prepare("
        SELECT DATE(purchase_datetime) AS date, purchase_amount, result 
        FROM purchase_entries
        WHERE customer_id = :customer_id
        ORDER BY purchase_datetime ASC
    ");
    $history_stmt->bindParam(':customer_id', $selected_customer_id);
    $history_stmt->execute();
    $history_data = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($history_data as $history) {
        $sales_dates[] = $history['date'];
        $sales_amounts[] = $history['purchase_amount'];

        if ($history['result'] === 'Win') {
            $wins[] = $history['purchase_amount'];
            $losses[] = 0;
        } elseif ($history['result'] === 'Loss') {
            $losses[] = $history['purchase_amount'];
            $wins[] = 0;
        } else {
            $wins[] = 0;
            $losses[] = 0;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Customer Dashboard</title>

    <!-- Custom fonts for this template -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <?php include('config/sidebar.php'); ?> <!-- Include your sidebar here -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <?php include('config/topbar.php'); ?> <!-- Include your topbar here -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">Customer Dashboard</h1>

                    <!-- Customer Data Table -->
                    <h2>Customer List</h2>
                    <table id="customerTable" class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Customer ID</th>
                                <th>Customer Name</th>
                                <th>Agent Name</th>
                                <th>Total Sales</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><?php echo $customer['customer_id']; ?></td>
                                    <td><?php echo $customer['customer_name']; ?></td>
                                    <td><?php echo $customer['agent_name']; ?></td>
                                    <td><?php echo number_format($customer['total_sales'], 2); ?></td>
                                    <td>
                                        <a href="?customer_id=<?php echo $customer['customer_id']; ?>" class="btn btn-primary">Select</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Customer Analysis Section (only if a customer is selected) -->
                    <?php if (isset($customer)): ?>
                        <h2>Customer Overview</h2>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Customer Name:</strong> <?php echo $customer['customer_name']; ?></p>
                                <p><strong>Customer Age:</strong> <?php echo $customer['age']; ?> years</p>
                                <p><strong>Agent:</strong> <?php echo $customer['agent_name']; ?></p>
                                <p><strong>Last Purchase:</strong> <?php echo $performance['last_purchase_date']; ?></p>
                                <p><strong>Last Win:</strong> <?php echo $performance['last_win_date'] ? $performance['last_win_date'] : 'No Wins'; ?></p>
                            </div>
                        </div>

                        <!-- Performance Analysis (Radar Chart) -->
                        <h2>Customer Performance Analysis</h2>
                        <canvas id="performanceRadarChart"></canvas>

                        <!-- Sales and Win/Loss History (Line Chart) -->
                        <h2>Sales and Win/Loss History</h2>
                        <canvas id="salesHistoryChart"></canvas>
                    <?php endif; ?>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Your Website 2020</span>
                    </div>
                </div>
            </footer>
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

    <!-- DataTables initialization -->
    <script>
        $(document).ready(function() {
            $('#customerTable').DataTable();
        });
    </script>

    <?php if (isset($customer)): ?>
    <!-- Chart.js for radar and line charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Radar Chart for Customer Performance -->
    <script>
        const performanceData = {
            labels: ['Total Wins', 'Total Losses', 'Total Revenue', 'Active Ratio'],
            datasets: [{
                label: 'Performance',
                data: [<?php echo $performance['total_wins']; ?>, <?php echo $performance['total_losses']; ?>, <?php echo $performance['total_revenue']; ?>, <?php echo $active_ratio; ?>],
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        };

        const performanceRadarChart = new Chart(document.getElementById('performanceRadarChart'), {
            type: 'radar',
            data: performanceData,
            options: {
                scales: {
                    r: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

    <!-- Line Chart for Sales History -->
    <script>
        const salesHistoryData = {
            labels: <?php echo json_encode($sales_dates); ?>,
            datasets: [
                {
                    label: 'Sales Amount',
                    data: <?php echo json_encode($sales_amounts); ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Wins',
                    data: <?php echo json_encode($wins); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Losses',
                    data: <?php echo json_encode($losses); ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        };

        const salesHistoryChart = new Chart(document.getElementById('salesHistoryChart'), {
            type: 'line',
            data: salesHistoryData,
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
    <?php endif; ?>

</body>

</html>