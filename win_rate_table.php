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
           IF(cd.win_count > 0, (cd.win_amount / cd.win_count), NULL) AS avg_win_amount,
           IF(cd.loss_count > 0, (cd.loss_amount / cd.loss_count), NULL) AS avg_loss_amount,
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

// Calculate Totals and Averages
$subtotal = [
    'total_sales' => 0,
    'win_amount' => 0,
    'loss_amount' => 0,
    'win_count' => 0,
    'loss_count' => 0,
    'win_rate_percentage' => 0,
];

foreach ($customers as $customer) {
    $subtotal['total_sales'] += $customer['total_sales'] ?? 0;
    $subtotal['win_amount'] += $customer['win_amount'] ?? 0;
    $subtotal['loss_amount'] += $customer['loss_amount'] ?? 0;
    $subtotal['win_count'] += $customer['win_count'] ?? 0;
    $subtotal['loss_count'] += $customer['loss_count'] ?? 0;
}

$total_transactions = $subtotal['win_count'] + $subtotal['loss_count'];
if ($total_transactions > 0) {
    $avg_win_rate = ($subtotal['win_count'] / $total_transactions) * 100;
    $loss_rate = ($subtotal['loss_count'] / $total_transactions) * 100;
    $payout_over_sales = ($subtotal['win_amount'] / $subtotal['total_sales']) * 100;
    $transaction_success_rate = ($subtotal['total_sales'] / $total_transactions);
} else {
    $avg_win_rate = 0;
    $loss_rate = 0;
    $payout_over_sales = 0;
    $transaction_success_rate = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Win Rate Table with Overall Statistics</title>
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
                <h1 class="h3 mb-2 text-gray-800">Win Rate Statistics</h1>

                <!-- Top Cards -->
                <div class="row justify-content-center">
                    <!-- Avg Win Rate -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Avg Win Rate</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($avg_win_rate, 2); ?>%</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-percentage fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Loss Rate -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-danger shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Loss Rate</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($loss_rate, 2); ?>%</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payout Over Sales -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Payout Over Sales</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($payout_over_sales, 2); ?>%</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- New Card: Transaction Success Rate -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Members WIN Rate</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($transaction_success_rate, 2); ?>%</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

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
                                     <!-- Make the customer name a hyperlink, but it will just display as the name itself -->
                                
                                    <td><?php echo $customer['customer_name']; ?></td>
                                    <td><?php echo $customer['agent_name']; ?></td>
                                    <td><?php echo $customer['win_count']; ?></td>
                                    <td><?php echo $customer['loss_count']; ?></td>
                                    <td><?php echo '$' . number_format($customer['total_sales'], 2); ?></td>
                                    <td><?php echo '$' . number_format($customer['win_amount'], 2); ?></td>
                                    <td><?php echo '$' . number_format($customer['loss_amount'], 2); ?></td>
                                    <td>
                                        <?php
                                        $avg_transaction = $customer['total_transactions'] > 0 ? ($customer['total_sales'] / $customer['total_transactions']) : 'N/A';
                                        echo is_numeric($avg_transaction) ? '$' . number_format($avg_transaction, 2) : 'N/A';
                                        ?>
                                    </td>
                                    <td><?php echo number_format($customer['win_rate_percentage'], 2); ?>%</td>
                                    <td><?php echo number_format($customer['win_loss_ratio'], 2); ?></td>
                                    <td><?php echo number_format($customer['predicted_win_rate'], 2); ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2">Subtotal</th>
                                    <th>0</th> <!-- Total Win Count -->
                                    <th>0</th> <!-- Total Loss Count -->
                                    <th>$0.00</th> <!-- Total Sales -->
                                    <th>$0.00</th> <!-- Total Win Amount -->
                                    <th>$0.00</th> <!-- Total Loss Amount -->
                                    <th>N/A</th> <!-- Avg Transaction Count -->
                                    <th>N/A</th> <!-- Win Rate -->
                                    <th>N/A</th> <!-- Win/Loss Ratio -->
                                    <th>N/A</th> <!-- Predicted Win Rate -->
                                </tr>
                            </tfoot>
                        </table>
                        <script>
                            $(document).ready(function() {
                                var table = $('#dataTable').DataTable({
                                    paging: true,
                                    searching: true,
                                    ordering: true,
                                    responsive: true,
                                    footerCallback: function (row, data, start, end, display) {
                                        var api = this.api();
                                        var totalWinCount = 0;
                                        var totalLossCount = 0;
                                        var totalSales = 0;
                                        var totalWinAmount = 0;
                                        var totalLossAmount = 0;
                                        var avgTransactionSum = 0;
                                        var winRateSum = 0;
                                        var winLossRatioSum = 0;
                                        var predictedWinRateSum = 0;
                                        var visibleRows = 0;

                                        // Loop through filtered data to calculate the subtotal
                                        api.rows({ search: 'applied' }).data().each(function(rowData) {
                                            totalWinCount += parseInt(rowData[2], 10) || 0; // Win Count
                                            totalLossCount += parseInt(rowData[3], 10) || 0; // Loss Count
                                            totalSales += parseFloat(rowData[4].replace('$', '').replace(',', '')) || 0; // Total Sales
                                            totalWinAmount += parseFloat(rowData[5].replace('$', '').replace(',', '')) || 0; // Total Win Amount
                                            totalLossAmount += parseFloat(rowData[6].replace('$', '').replace(',', '')) || 0; // Total Loss Amount

                                            // Avg Transaction Count
                                            var avgTransaction = parseFloat(rowData[7].replace('$', '').replace(',', '')) || 0;
                                            avgTransactionSum += avgTransaction;
                                            
                                            // Win Rate
                                            var winRate = parseFloat(rowData[8].replace('%', '')) || 0;
                                            winRateSum += winRate;

                                            // Win/Loss Ratio
                                            var winLossRatio = parseFloat(rowData[9]) || 0;
                                            winLossRatioSum += winLossRatio;

                                            // Predicted Win Rate
                                            var predictedWinRate = parseFloat(rowData[10].replace('%', '')) || 0;
                                            predictedWinRateSum += predictedWinRate;

                                            visibleRows++;
                                        });

                                        // Calculate the averages
                                        var avgTransaction = visibleRows > 0 ? avgTransactionSum / visibleRows : 'N/A';
                                        var avgWinRate = visibleRows > 0 ? winRateSum / visibleRows : 'N/A';
                                        var avgWinLossRatio = visibleRows > 0 ? winLossRatioSum / visibleRows : 'N/A';
                                        var avgPredictedWinRate = visibleRows > 0 ? predictedWinRateSum / visibleRows : 'N/A';

                                        // Update the footer with the subtotal
                                        $(api.column(2).footer()).html(totalWinCount);
                                        $(api.column(3).footer()).html(totalLossCount);
                                        $(api.column(4).footer()).html('$' + totalSales.toFixed(2));
                                        $(api.column(5).footer()).html('$' + totalWinAmount.toFixed(2));
                                        $(api.column(6).footer()).html('$' + totalLossAmount.toFixed(2));

                                        // Update the footer with the averages
                                        $(api.column(7).footer()).html(isNaN(avgTransaction) ? 'N/A' : '$' + avgTransaction.toFixed(2));
                                        $(api.column(8).footer()).html(isNaN(avgWinRate) ? 'N/A' : avgWinRate.toFixed(2) + '%');
                                        $(api.column(9).footer()).html(isNaN(avgWinLossRatio) ? 'N/A' : avgWinLossRatio.toFixed(2));
                                        $(api.column(10).footer()).html(isNaN(avgPredictedWinRate) ? 'N/A' : avgPredictedWinRate.toFixed(2) + '%');
                                    }
                                });
                            });
                            </script>
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



<!-- Core plugin JavaScript-->
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>



<!-- DataTables -->
<script src="vendor/datatables/jquery.dataTables.min.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

<!-- Custom scripts for DataTables -->
<script src="js/demo/datatables-demo.js"></script>

</body>
</html>