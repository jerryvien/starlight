<?php
session_start();
include('config/database.php');

// Set time zone
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

// Fetch the statistics from customer_details table
$stats_stmt = $conn->query("
    SELECT 
        cd.customer_name, 
        a.agent_name, 
        cd.win_count,
        cd.loss_count,
        cd.total_sales,
        cd.win_amount,
        cd.loss_amount,
        (cd.win_count / NULLIF(cd.loss_count, 0)) AS win_loss_ratio,
        (cd.win_count / (cd.win_count + cd.loss_count)) * 100 AS win_rate,
        (cd.win_amount / NULLIF(cd.win_count, 0)) AS avg_win_amount_per_transaction,
        (cd.loss_amount / NULLIF(cd.loss_count, 0)) AS avg_loss_amount_per_transaction,
        (cd.win_count + cd.loss_count) AS total_transactions,
        (cd.win_amount + cd.total_sales) AS customer_lifetime_value,
        -- Assuming win rate change needs comparison (last month vs current month)
        (SELECT 
            ((SUM(CASE WHEN pe.result = 'Win' THEN 1 END) / COUNT(*)) * 100) 
         FROM purchase_entries pe 
         WHERE pe.customer_id = cd.customer_id 
         AND MONTH(pe.purchase_datetime) = MONTH(CURRENT_DATE()) - 1
        ) AS win_rate_change,
        -- Predicted win chance for next 5 purchases based on historical data
        (cd.win_count / 5) * 100 AS predicted_win_chance
    FROM 
        customer_details cd
    LEFT JOIN 
        admin_access a ON cd.agent_id = a.agent_id
");
$stats = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Analysis Report</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
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
                <h1 class="h3 mb-2 text-gray-800">Customer Analysis Report</h1>
                
                <!-- Statistics Table -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Customer Performance Analysis</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Customer Name</th>
                                        <th>Agent Name</th>
                                        <th>Total Win Count</th>
                                        <th>Total Loss Count</th>
                                        <th>Total Sales</th>
                                        <th>Total Win Amount</th>
                                        <th>Total Loss Amount</th>
                                        <th>Avg Win Amount/Transaction</th>
                                        <th>Avg Loss Amount/Transaction</th>
                                        <th>Total Transactions</th>
                                        <th>Customer Lifetime Value (CLV)</th>
                                        <th>Win Rate (%)</th>
                                        <th>Win/Loss Ratio</th>
                                        <th>Win Rate Change (%)</th>
                                        <th>Predicted Win Chance (Next 5)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats as $row): ?>
                                    <tr>
                                        <td><?= $row['customer_name']; ?></td>
                                        <td><?= $row['agent_name']; ?></td>
                                        <td><?= $row['win_count']; ?></td>
                                        <td><?= $row['loss_count']; ?></td>
                                        <td>$<?= number_format($row['total_sales'], 2); ?></td>
                                        <td>$<?= number_format($row['win_amount'], 2); ?></td>
                                        <td>$<?= number_format($row['loss_amount'], 2); ?></td>
                                        <td>$<?= number_format($row['avg_win_amount_per_transaction'], 2); ?></td>
                                        <td>$<?= number_format($row['avg_loss_amount_per_transaction'], 2); ?></td>
                                        <td><?= $row['total_transactions']; ?></td>
                                        <td>$<?= number_format($row['customer_lifetime_value'], 2); ?></td>
                                        <td><?= number_format($row['win_rate'], 2); ?>%</td>
                                        <td><?= number_format($row['win_loss_ratio'], 2); ?></td>
                                        <td><?= number_format($row['win_rate_change'], 2); ?>%</td>
                                        <td><?= number_format($row['predicted_win_chance'], 2); ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Main Content -->

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

</body>
</html>