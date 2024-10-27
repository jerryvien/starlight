<?php
session_start();
include('config/database.php');
include('config/utilities.php');

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

// Fetch agent filter if the user is an agent
$agent_filter = ($_SESSION['access_level'] === 'agent') ? $_SESSION['agent_id'] : null;

// Fetch purchase entries grouped by serial number
try {
    $query = "SELECT serial_number, customer_id, agent_id, purchase_datetime, GROUP_CONCAT(purchase_no SEPARATOR ', ') as purchase_details, SUM(purchase_amount) as subtotal FROM purchase_entries";
    if ($agent_filter) {
        $query .= " WHERE agent_id = :agent_id";
    }
    $query .= " GROUP BY serial_number ORDER BY purchase_datetime DESC";

    $stmt = $conn->prepare($query);
    if ($agent_filter) {
        $stmt->bindParam(':agent_id', $agent_filter);
    }
    $stmt->execute();
    $purchase_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching purchase entries: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Entries Grouped by Serial Number</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
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
                <h1 class="h3 mb-4 text-gray-800">Purchase Entries Grouped by Serial Number</h1>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Purchase Entries</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Serial Number</th>
                                        <th>Customer ID</th>
                                        <th>Agent ID</th>
                                        <th>Purchase Date</th>
                                        <th>Purchase Details</th>
                                        <th>Subtotal</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($purchase_entries as $entry): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($entry['serial_number']); ?></td>
                                            <td><?php echo htmlspecialchars($entry['customer_id']); ?></td>
                                            <td><?php echo htmlspecialchars($entry['agent_id']); ?></td>
                                            <td><?php echo date('d-M-Y', strtotime($entry['purchase_datetime'])); ?></td>
                                            <td><?php echo htmlspecialchars($entry['purchase_details']); ?></td>
                                            <td>$<?php echo number_format($entry['subtotal'], 2); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-info" onclick="generateReceiptPopup('<?php echo htmlspecialchars($entry['customer_id']); ?>', '<?php echo htmlspecialchars($entry['purchase_details']); ?>', '<?php echo number_format($entry['subtotal'], 2); ?>', '<?php echo htmlspecialchars($entry['agent_id']); ?>', '<?php echo htmlspecialchars($entry['serial_number']); ?>')">Reprint Receipt</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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

    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                "paging": true,
                "searching": true,
                "ordering": true,
                "pageLength": 10
            });
        });

        function generateReceiptPopup(customerName, purchaseDetails, subtotal, agentName, serialNumber) {
            generateReceiptPopup(customerName, purchaseDetails, subtotal, agentName, serialNumber);
        }
    </script>
</body>
</html>
