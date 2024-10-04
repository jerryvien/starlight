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

// Fetch data for the win/loss statistics table
$query = "
    SELECT cd.customer_name, a.agent_name, cd.win_count, cd.loss_count, 
           cd.total_sales, cd.win_amount, cd.loss_amount,
           (cd.win_amount / cd.win_count) AS avg_win_amount,
           (cd.loss_amount / cd.loss_count) AS avg_loss_amount,
           (cd.win_count + cd.loss_count) AS total_transactions,
           (cd.win_count / (cd.win_count + cd.loss_count)) * 100 AS win_rate_percentage,
           (cd.win_count / cd.loss_count) AS win_loss_ratio,
           ((cd.win_count / (cd.win_count + cd.loss_count)) * 100) + 5 AS predicted_win_rate -- Example prediction logic
    FROM customer_details cd
    LEFT JOIN admin_access a ON cd.agent_id = a.agent_id
    WHERE cd.win_count > 0 OR cd.loss_count > 0
    ORDER BY cd.customer_name ASC
";
$stmt = $conn->query($query);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Win Rate Table</title>
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

            <!-- Page Content -->
            <div class="container-fluid">
                <h1 class="h3 mb-2 text-gray-800">Win Rate Statistics</h1>

                <!-- DataTable -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Win/Loss Analysis</h6>
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
                                        <th>Avg Transaction Count</th>
                                        <th>Win Rate (%)</th>
                                        <th>Win/Loss Ratio</th>
                                        <th>Predicted Win Rate (%)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($customers as $customer): ?>
                                    <tr>
                                        <td><?php echo $customer['customer_name']; ?></td>
                                        <td><?php echo $customer['agent_name']; ?></td>
                                        <td><?php echo isset($customer['win_count']) && $customer['win_count'] !== null ? $customer['win_count'] : '0'; ?></td>
                                        <td><?php echo isset($customer['loss_count']) && $customer['loss_count'] !== null ? $customer['loss_count'] : '0'; ?></td>
                                        <td><?php echo '$' . (isset($customer['total_sales']) && $customer['total_sales'] !== null ? number_format($customer['total_sales'], 2) : 'N/A'); ?></td>
                                        <td><?php echo '$' . (isset($customer['win_amount']) && $customer['win_amount'] !== null ? number_format($customer['win_amount'], 2) : 'N/A'); ?></td>
                                        <td><?php echo '$' . (isset($customer['loss_amount']) && $customer['loss_amount'] !== null ? number_format($customer['loss_amount'], 2) : 'N/A'); ?></td>
                                        <td><?php echo isset($customer['avg_transaction_count']) && $customer['avg_transaction_count'] !== null ? number_format($customer['avg_transaction_count'], 2) : 'N/A'; ?></td>
                                        <td><?php echo isset($customer['win_rate_percentage']) && $customer['win_rate_percentage'] !== null ? number_format($customer['win_rate_percentage'], 2) . '%' : '0.00%'; ?></td>
                                        <td><?php echo isset($customer['win_loss_ratio']) && $customer['win_loss_ratio'] !== null ? number_format($customer['win_loss_ratio'], 2) : '0.00'; ?></td>
                                        <td><?php echo isset($customer['predicted_win_rate']) && $customer['predicted_win_rate'] !== null ? number_format($customer['predicted_win_rate'], 2) . '%' : '0.00%'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
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

<!-- Page level custom scripts -->
<script src="js/demo/datatables-demo.js"></script>

</body>
</html>