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

// Ensure the user is a super_admin
if ($_SESSION['access_level'] !== 'super_admin') {
    echo "<script>alert('You must be a super admin to access this page.'); window.location.href='dashboard.php';</script>";
    exit;
}

// Fetch agent data (with at least 1 sale)
try {
    $stmt = $conn->query("SELECT * FROM admin_access WHERE total_sales > 0 ORDER BY agent_name ASC");
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching agent data: " . $e->getMessage());
}

// Default to the first agent if none is selected
if (isset($agents[0])) {
    $default_agent_id = $agents[0]['agent_id'];
}

// Initialize variables
$selected_agent = null;
$related_sales = [];
$subtotal_sales_amount = 0;
$subtotal_winning_amount = 0;
$total_win_amount = 0;
$total_loss_amount = 0;
$win_count = 0;
$loss_count = 0;

// Handle form submission for selecting agent or load default agent
$agent_id = $default_agent_id ?? null;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['select_agent'])) {
    $agent_id = $_POST['select_agent'];
}

// Fetch selected agent details with total win and loss amount
$stmt = $conn->prepare("
    SELECT aa.*, 
           SUM(CASE WHEN pe.result = 'Win' THEN pe.winning_amount ELSE 0 END) AS total_win_amount, 
           SUM(CASE WHEN pe.result = 'Loss' THEN pe.purchase_amount ELSE 0 END) AS total_loss_amount,
           COUNT(CASE WHEN pe.result = 'Win' THEN 1 ELSE NULL END) AS win_count,
           COUNT(CASE WHEN pe.result = 'Loss' THEN 1 ELSE NULL END) AS loss_count
    FROM admin_access aa
    LEFT JOIN purchase_entries pe ON aa.agent_id = pe.agent_id
    WHERE aa.agent_id = :agent_id
    GROUP BY aa.agent_id
");
$stmt->bindParam(':agent_id', $agent_id);
$stmt->execute();
$selected_agent = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch related sales entries for this agent
$stmt = $conn->prepare("
    SELECT pe.*, c.customer_name
    FROM purchase_entries pe
    LEFT JOIN customer_details c ON pe.customer_id = c.customer_id
    WHERE pe.agent_id = :agent_id
");
$stmt->bindParam(':agent_id', $agent_id);
$stmt->execute();
$related_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for the charts
$sale_dates = [];
$sale_amounts = [];
$winning_amounts = [];

foreach ($related_sales as $sale) {
    $sale_dates[] = date('d-M-Y', strtotime($sale['purchase_datetime']));
    $sale_amounts[] = $sale['purchase_amount'];
    $winning_amounts[] = $sale['winning_amount'] ?? 0;
}

// Calculate the win/loss ratio for the pie chart
$total_sales = $selected_agent['win_count'] + $selected_agent['loss_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Analysis</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
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

            <div class="container-fluid">
            <h1 class="h3 mb-2 ml-4 text-gray-800">Agent Analysis Report</h1>
                <div class="row">
                    <!-- Agent Data Table (left side, with ml-4) -->
                    <div class="col-md-5 ml-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Agents</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <form method="POST">
                                        <table id="agentTable" class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Agent Name</th>
                                                    <th>Total Sales</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($agents as $agent): ?>
                                                    <tr>
                                                        <td><?php echo $agent['agent_name']; ?></td>
                                                        <td>$<?php echo number_format($agent['total_sales'], 2); ?></td>
                                                        <td>
                                                            <button type="submit" name="select_agent" value="<?php echo $agent['agent_id']; ?>" class="btn btn-<?php echo isset($selected_agent) && $selected_agent['agent_id'] === $agent['agent_id'] ? 'warning' : 'primary'; ?>">
                                                                <?php echo isset($selected_agent) && $selected_agent['agent_id'] === $agent['agent_id'] ? 'Selected' : 'Select'; ?>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Agent Information and Charts (right side, with mr-4) -->
                    <div class="col-md-6 mr-4">
                        <?php if ($selected_agent): ?>
                        <div class="row">
                            <!-- Agent Information -->
                            <div class="col-md-6">
                                <div class="card shadow mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Agent Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-group">
                                            <li class="list-group-item"><strong>Agent Name: </strong><?php echo $selected_agent['agent_name']; ?></li>
                                            <li class="list-group-item"><strong>Total Sales: </strong>$<?php echo number_format($selected_agent['total_sales'], 2); ?></li>
                                            <li class="list-group-item"><strong>Total Win Amount: </strong>$<?php echo number_format($selected_agent['total_win_amount'], 2); ?></li>
                                            <li class="list-group-item"><strong>Total Loss Amount: </strong>$<?php echo number_format($selected_agent['total_loss_amount'], 2); ?></li>
                                            <li class="list-group-item"><strong>Win Count: </strong><?php echo $selected_agent['win_count']; ?></li>
                                            <li class="list-group-item"><strong>Loss Count: </strong><?php echo $selected_agent['loss_count']; ?></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Win/Loss Pie Chart -->
                            <div class="col-md-6">
                                <div class="card shadow mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Win/Loss Ratio</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="winLossChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Line Charts for Sales and Winning Trends -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card shadow mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Sales and Winning Trends</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="trendChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Combined Sales and Win/Loss Records Table -->
                <?php if (!empty($related_sales)): ?>
                <div class="card shadow mb-4 ml-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Sales Entries and Win/Loss Records</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="salesEntriesTable" class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Customer Name</th>
                                        <th>Purchase No</th>
                                        <th>Purchase Amount</th>
                                        <th>Purchase Date</th>
                                        <th>Result</th>
                                        <th>Winning Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $subtotal_sales_amount = 0;
                                    $subtotal_winning_amount = 0;
                                    ?>
                                    <?php foreach ($related_sales as $sale): ?>
                                        <?php 
                                        $subtotal_sales_amount += $sale['purchase_amount']; 
                                        $subtotal_winning_amount += $sale['winning_amount'] ?? 0;
                                        ?>
                                        <tr>
                                            <td><?php echo $sale['customer_name']; ?></td>
                                            <td><?php echo $sale['purchase_no']; ?></td>
                                            <td>$<?php echo number_format($sale['purchase_amount'], 2); ?></td>
                                            <td><?php echo date('d-M-Y', strtotime($sale['purchase_datetime'])); ?></td>
                                            <td><?php echo $sale['result']; ?></td>
                                            <td>$<?php echo number_format($sale['winning_amount'] ?? 0, 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="text-right"><strong>Subtotal Sales Amount:</strong></td>
                                        <td><strong>$<?php echo number_format($subtotal_sales_amount, 2); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="text-right"><strong>Subtotal Winning Amount:</strong></td>
                                        <td><strong>$<?php echo number_format($subtotal_winning_amount, 2); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

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

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <!-- Chart.js for pie and line charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
    $(document).ready(function() {
        $('#agentTable').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "pageLength": 10
        });
        $('#salesEntriesTable').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "pageLength": 10
        });

        // Initialize Pie Chart (Win/Loss Ratio)
        var winLossData = {
            labels: ['Win', 'Loss'],
            datasets: [{
                data: [
                    <?php echo ($loss_count / $total_purchases) * 100; ?>, 
                    <?php echo ($win_count / $total_purchases) * 100; ?>
                ],
                backgroundColor: ['#FFD700', '#4e73df'],
            }],
        };

        var winLossCtx = document.getElementById('winLossChart').getContext('2d');
        new Chart(winLossCtx, {
            type: 'pie',
            data: winLossData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.label || '';
                                var value = context.raw || 0;
                                var total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                                var percentage = (value / total * 100).toFixed(2);
                                return label + ': ' + percentage + '%';
                            }
                        }
                    }
                }
            }
        });

        // Initialize Line Chart (Sales and Winning Trends)
        var trendData = {
            labels: <?php echo json_encode($sale_dates); ?>,
            datasets: [
                {
                    label: 'Sales Amount',
                    data: <?php echo json_encode($sale_amounts); ?>,
                    borderColor: '#4e73df',
                    fill: false,
                },
                {
                    label: 'Winning Amount',
                    data: <?php echo json_encode($winning_amounts); ?>,
                    borderColor: '#1cc88a',
                    fill: false,
                },
            ],
        };

        var trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: trendData,
        });
    });
    </script>
</body>
</html>