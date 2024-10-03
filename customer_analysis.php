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
$selected_customer = null;
$related_purchases = [];

try {
    $stmt = $conn->query("SELECT * FROM customer_details WHERE purchase_history_count > 0 OR created_at > NOW() - INTERVAL 365 DAY ORDER BY created_at DESC");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Automatically select the first customer if available
    if (!empty($customers)) {
        $selected_customer_id = $customers[0]['customer_id'];

        // Fetch the first customer's data
        $stmt = $conn->prepare("SELECT cd.*, a.agent_name FROM customer_details cd LEFT JOIN admin_access a ON a.agent_id = cd.agent_id WHERE cd.customer_id = :customer_id");
        $stmt->bindParam(':customer_id', $selected_customer_id);
        $stmt->execute();
        $selected_customer = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch related purchases
        $stmt = $conn->prepare("SELECT * FROM purchase_entries WHERE customer_id = :customer_id");
        $stmt->bindParam(':customer_id', $selected_customer['customer_id']);
        $stmt->execute();
        $related_purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    die("Error fetching customer data: " . $e->getMessage());
}
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
            height: 100%;
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
            height: 300px;
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
                    <div class="col-md-4">
                        <h1 class="h3 mb-2 text-gray-800">Customer Data</h1>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Customers</h6>
                            </div>
                            <div class="card-body table-responsive">
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

                    <!-- Customer Information (right side, with mr-8) -->
                    <div class="col-md-4 ml-4">
                        <?php if ($selected_customer): ?>
                        <div class="card shadow mb-4" id="customer-info">
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
                        <?php endif; ?>
                    </div>

                    <!-- Win/Loss Pie Chart (right of customer information) -->
                    <div class="col-md-4 mr-8">
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

                <!-- Purchase Entries and Win/Loss Records Table -->
                <?php if (!empty($related_purchases)): ?>
                <div class="row">
                    <div class="col-md-12">
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
                    </div>
                </div>
                <?php endif; ?>

                <!-- Purchase and Winning Trends Line Chart -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Purchase and Winning Trends</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="purchaseWinningChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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

    <!-- Page level custom scripts -->
    <script src="js/demo/datatables-demo.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
    $(document).ready(function() {
        $('#customerTable').DataTable();
        $('#purchaseEntriesTable').DataTable();
    });

    // Pie chart for win/loss ratio
    var winLossCtx = document.getElementById('winLossChart').getContext('2d');
    var winLossChart = new Chart(winLossCtx, {
        type: 'pie',
        data: {
            labels: ['Win', 'Loss'],
            datasets: [{
                label: 'Win/Loss Ratio',
                data: [
                    <?php echo $selected_customer['total_win_amount']; ?>,
                    <?php echo $selected_customer['total_loss_amount']; ?>
                ],
                backgroundColor: ['#36A2EB', '#FF6384'],
                hoverOffset: 4
            }]
        },
        options: {
            plugins: {
                legend: {
                    display: true,
                    position: 'right'
                }
            }
        }
    });

    // Line chart for purchase and winning trends
    var purchaseWinningCtx = document.getElementById('purchaseWinningChart').getContext('2d');
    var purchaseWinningChart = new Chart(purchaseWinningCtx, {
        type: 'line',
        data: {
            labels: [<?php
                foreach ($related_purchases as $purchase) {
                    echo '"' . date('d-M-Y', strtotime($purchase['purchase_datetime'])) . '", ';
                }
            ?>],
            datasets: [{
                label: 'Purchase Amount',
                data: [<?php
                    foreach ($related_purchases as $purchase) {
                        echo $purchase['purchase_amount'] . ', ';
                    }
                ?>],
                borderColor: '#36A2EB',
                fill: false
            },
            {
                label: 'Winning Amount',
                data: [<?php
                    foreach ($related_purchases as $purchase) {
                        echo $purchase['winning_amount'] ?? 0 . ', ';
                    }
                ?>],
                borderColor: '#4CAF50',
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    </script>
</body>
</html>