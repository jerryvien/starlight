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

// Fetch customer data (with at least 1 purchase or created in the last 365 days)
try {
    $stmt = $conn->query("SELECT * FROM customer_details WHERE purchase_history_count > 0 AND created_at > NOW() - INTERVAL 365 DAY ORDER BY created_at DESC");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching customer data: " . $e->getMessage());
}

// Default to the first customer if none is selected
if (isset($customers[0])) {
    $default_customer_id = $customers[0]['customer_id'];
}

// Initialize variables
$selected_customer = null;
$related_purchases = [];
$subtotal_purchase_amount = 0;
$subtotal_winning_amount = 0;
$total_win_amount = 0;
$total_loss_amount = 0;

// Handle form submission for selecting customer or load default customer
$customer_id = $default_customer_id ?? null;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['select_customer'])) {
    $customer_id = $_POST['select_customer'];
}

// Fetch selected customer details with total win and loss amount
$stmt = $conn->prepare("
    SELECT cd.*, a.agent_name, 
           MAX(pe.purchase_datetime) AS last_purchase, 
           MAX(CASE WHEN pe.result = 'Win' THEN pe.purchase_datetime ELSE NULL END) AS last_win, 
           SUM(CASE WHEN pe.result = 'Win' THEN pe.winning_amount ELSE 0 END) AS total_win_amount, 
           SUM(CASE WHEN pe.result = 'Loss' THEN pe.purchase_amount ELSE 0 END) AS total_loss_amount 
    FROM customer_details cd 
    LEFT JOIN purchase_entries pe ON cd.customer_id = pe.customer_id 
    LEFT JOIN admin_access a ON a.agent_id = cd.agent_id 
    WHERE cd.customer_id = :customer_id 
    GROUP BY cd.customer_id
");
$stmt->bindParam(':customer_id', $customer_id);
$stmt->execute();
$selected_customer = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch related purchase entries
$stmt = $conn->prepare("
    SELECT pe.*, a.agent_name 
    FROM purchase_entries pe
    LEFT JOIN admin_access a ON a.agent_id = pe.agent_id
    WHERE pe.customer_id = :customer_id
");
$stmt->bindParam(':customer_id', $customer_id);
$stmt->execute();
$related_purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for the charts
$purchase_dates = [];
$purchase_amounts = [];
$winning_amounts = [];
$win_count = 0;
$loss_count = 0;

foreach ($related_purchases as $purchase) {
    $purchase_dates[] = date('d-M-Y', strtotime($purchase['purchase_datetime']));
    $purchase_amounts[] = $purchase['purchase_amount'];
    $winning_amounts[] = $purchase['winning_amount'] ?? 0;

    if ($purchase['result'] == 'Win') {
        $win_count++;
    } elseif ($purchase['result'] == 'Loss') {
        $loss_count++;
    }
}

// Calculate the win/loss ratio for the pie chart
$total_purchases = $win_count + $loss_count;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Analysis</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style>
    /* Set a fixed height for the table container and enable scrolling */
    .table-responsive {
        max-height: 400px;
        overflow-y: auto;
    }

    /* Ensure that cards take up the full height of the column */
    .card {
        height: 100%; /* Stretch the card to fill the container */
    }

    /* Control the layout of the right-side containers */
    #customer-info {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    /* Control the chart area */
    #chart-container {
        max-height: 400px;
    }

    /* Control chart dimensions */
    canvas {
        max-width: 100%;
        height: 300px; /* Adjust the chart height */
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

            <div class="container-fluid">
                <div class="row">
                    <!-- Customer Data Table (left side, with ml-4) -->
                    <div class="col-md-5 ml-4">
                        
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Customers</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <form method="POST">
                                        <table id="customerTable" class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Customer Name</th>
                                                    <th>Total Sales</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($customers as $customer): ?>
                                                    <tr>
                                                        <td><?php echo $customer['customer_name']; ?></td>
                                                        <td>$<?php echo number_format($customer['total_sales'], 2); ?></td>
                                                        <td>
                                                            <button type="submit" name="select_customer" value="<?php echo $customer['customer_id']; ?>" class="btn btn-<?php echo isset($selected_customer) && $selected_customer['customer_id'] === $customer['customer_id'] ? 'warning' : 'primary'; ?>">
                                                                <?php echo isset($selected_customer) && $selected_customer['customer_id'] === $customer['customer_id'] ? 'Selected' : 'Select'; ?>
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

                    <!-- Customer Information and Charts (right side, with mr-4) -->
                    <div class="col-md-6 mr-4">
                        <?php if ($selected_customer): ?>
                        <div class="row">
                            <!-- Customer Information -->
                            <div class="col-md-6">
                                <div class="card shadow mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Customer Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-group">
                                            <li class="list-group-item"><strong>Customer Name: </strong><?php echo $selected_customer['customer_name']; ?></li>
                                            <li class="list-group-item"><strong>Agent Name: </strong><?php echo $selected_customer['agent_name'] ?? 'N/A'; ?></li>
                                            <li class="list-group-item"><strong>Total Sales: </strong>$<?php echo number_format($selected_customer['total_sales'], 2); ?></li>
                                            <li class="list-group-item"><strong>Total Win Amount: </strong>$<?php echo number_format($selected_customer['total_win_amount'], 2); ?></li>
                                            <li class="list-group-item"><strong>Total Loss Amount: </strong>$<?php echo number_format($selected_customer['total_loss_amount'], 2); ?></li>
                                            <li class="list-group-item"><strong>Last Purchase Date: </strong><?php echo $selected_customer['last_purchase'] ? date('d-M-Y', strtotime($selected_customer['last_purchase'])) : 'N/A'; ?></li>
                                            <li class="list-group-item"><strong>Last Win Date: </strong><?php echo $selected_customer['last_win'] ? date('d-M-Y', strtotime($selected_customer['last_win'])) : 'N/A'; ?></li>
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

                        <!-- Line Charts for Purchase and Winning Trends -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card shadow mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Purchase and Winning Trends</h6>
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

                <!-- Combined Purchase and Win/Loss Records Table -->
                <?php if (!empty($related_purchases)): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Purchase Entries and Win/Loss Records</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="purchaseEntriesTable" class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Purchase No</th>
                                        <th>Purchase Amount</th>
                                        <th>Purchase Date</th>
                                        <th>Agent Name</th>
                                        <th>Result</th>
                                        <th>Winning Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $subtotal_purchase_amount = 0;
                                    $subtotal_winning_amount = 0;
                                    ?>
                                    <?php foreach ($related_purchases as $purchase): ?>
                                        <?php 
                                        $subtotal_purchase_amount += $purchase['purchase_amount']; 
                                        $subtotal_winning_amount += $purchase['winning_amount'] ?? 0;
                                        ?>
                                        <tr>
                                            <td><?php echo $purchase['purchase_no']; ?></td>
                                            <td>$<?php echo number_format($purchase['purchase_amount'], 2); ?></td>
                                            <td><?php echo date('d-M-Y', strtotime($purchase['purchase_datetime'])); ?></td>
                                            <td><?php echo $purchase['agent_name'] ?? 'N/A'; ?></td>
                                            <td><?php echo $purchase['result']; ?></td>
                                            <td>$<?php echo number_format($purchase['winning_amount'] ?? 0, 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="text-right"><strong>Subtotal Purchase Amount:</strong></td>
                                        <td><strong>$<?php echo number_format($subtotal_purchase_amount, 2); ?></strong></td>
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
        $('#customerTable').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "pageLength": 10
        });
        $('#purchaseEntriesTable').DataTable({
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
                    <?php echo ($win_count / $total_purchases) * 100; ?>, 
                    <?php echo ($loss_count / $total_purchases) * 100; ?>
                ],
                backgroundColor: ['#4e73df', '#e74a3b'],
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
                            label: function(tooltipItem, data) {
                                var label = data.labels[tooltipItem.dataIndex];
                                var value = data.datasets[0].data[tooltipItem.dataIndex];
                                return label + ': ' + value.toFixed(2) + '%';
                            }
                        }
                    }
                }
            }
        });

        // Initialize Line Chart (Trends)
        var trendData = {
            labels: <?php echo json_encode($purchase_dates); ?>,
            datasets: [
                {
                    label: 'Purchase Amount',
                    data: <?php echo json_encode($purchase_amounts); ?>,
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