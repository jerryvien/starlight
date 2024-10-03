<?php
session_start();
include('config/database.php');

// Set time zone to Kuala Lumpur (GMT +8)
date_default_timezone_set('Asia/Kuala_Lumpur');

// Error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure the user is logged in; redirect if not
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Ensure the user is a super_admin
if ($_SESSION['access_level'] !== 'super_admin') {
    echo "<script>alert('You must be a super admin to access this page.'); window.location.href='dashboard.php';</script>";
    exit;
}

// Fetch customers with at least 1 purchase or created within the last 365 days
try {
    $stmt = $conn->prepare("
        SELECT c.customer_id, c.customer_name, a.agent_name, IFNULL(SUM(p.purchase_amount), 0) AS total_sales, c.created_at,
        MAX(CASE WHEN p.result = 'Win' THEN p.purchase_datetime ELSE NULL END) AS last_win,
        MAX(p.purchase_datetime) AS last_purchase
        FROM customer_details c
        LEFT JOIN purchase_entries p ON c.customer_id = p.customer_id
        JOIN admin_access a ON c.agent_id = a.agent_id
        WHERE (p.customer_id IS NOT NULL OR c.created_at >= DATE_SUB(CURDATE(), INTERVAL 365 DAY))
        GROUP BY c.customer_id
        ORDER BY c.customer_name ASC
    ");
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching customers: " . $e->getMessage());
}

// Fetch purchase entries related to the selected customer
$selected_customer = null;
$related_purchases = [];
$win_loss_records = []; // Initialize win/loss record
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['select_customer'])) {
    $customer_id = $_POST['select_customer'];

    // Fetch selected customer info
    $stmt = $conn->prepare("SELECT * FROM customer_details WHERE customer_id = :customer_id");
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    $selected_customer = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch related purchase entries
    $purchase_stmt = $conn->prepare("
        SELECT p.*, a.agent_name
        FROM purchase_entries p
        JOIN admin_access a ON p.agent_id = a.agent_id
        WHERE p.customer_id = :customer_id
    ");
    $purchase_stmt->bindParam(':customer_id', $customer_id);
    $purchase_stmt->execute();
    $related_purchases = $purchase_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch win and loss records for the selected customer
    $win_loss_stmt = $conn->prepare("
        SELECT p.*, a.agent_name
        FROM purchase_entries p
        JOIN admin_access a ON p.agent_id = a.agent_id
        WHERE p.customer_id = :customer_id
          AND p.result IN ('Win', 'Loss')
    ");
    $win_loss_stmt->bindParam(':customer_id', $customer_id);
    $win_loss_stmt->execute();
    $win_loss_records = $win_loss_stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <h1 class="h3 mb-2 text-gray-800">Customer Record</h1>

                <div class="row">
                    <!-- Customer Data Table -->
                    <div class="col-md-6 ml-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Customer Data</h6>
                            </div>
                            <div class="card-body">
                            <div class="table-responsive">
                                    <form method="POST">
                                        <table id="customerTable" class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Customer Name</th>
                                                    <th>Agent Name</th>
                                                    <th>Total Sales</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($customers as $customer): ?>
                                                    <tr>
                                                        <td><?php echo $customer['customer_name']; ?></td>
                                                        <td><?php echo $customer['agent_name']; ?></td>
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

                    <!-- Customer Information -->
                    <div class="col-md-5 ml-4">
                        <?php if ($selected_customer): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Customer Information</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-group">
                                    <li class="list-group-item"><strong>Customer ID: </strong><?php echo $selected_customer['customer_id']; ?></li>
                                    <li class="list-group-item"><strong>Customer Name: </strong><?php echo $selected_customer['customer_name']; ?></li>
                                    <li class="list-group-item"><strong>Agent Name: </strong><?php echo $selected_customer['agent_name']; ?></li>
                                    <li class="list-group-item"><strong>Total Sales: </strong>$<?php echo number_format($selected_customer['total_sales'], 2); ?></li>
                                    <li class="list-group-item"><strong>Last Purchase Date: </strong><?php echo date('d-M-Y', strtotime($selected_customer['last_purchase'])); ?></li>
                                    <li class="list-group-item"><strong>Last Win Date: </strong><?php echo $selected_customer['last_win'] ? date('d-M-Y', strtotime($selected_customer['last_win'])) : 'N/A'; ?></li>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Purchase Entries Table -->
                <?php if (!empty($related_purchases)): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Purchase Entries</h6>
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
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $subtotal = 0; ?>
                                    <?php foreach ($related_purchases as $purchase): ?>
                                        <?php $subtotal += $purchase['purchase_amount']; ?>
                                        <tr>
                                            <td><?php echo $purchase['purchase_no']; ?></td>
                                            <td>$<?php echo number_format($purchase['purchase_amount'], 2); ?></td>
                                            <td><?php echo date('d-M-Y', strtotime($purchase['purchase_datetime'])); ?></td>
                                            <td><?php echo $purchase['agent_name']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <!-- Subtotal row -->
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-right"><strong>Subtotal:</strong></td>
                                        <td><strong>$<?php echo number_format($subtotal, 2); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Win and Loss Records Table -->
                <?php if (!empty($win_loss_records)): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Win and Loss Records</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="winLossTable" class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Purchase No</th>
                                        <th>Result</th>
                                        <th>Winning Amount</th>
                                        <th>Purchase Date</th>
                                        <th>Agent Name</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($win_loss_records as $record): ?>
                                        <tr>
                                            <td><?php echo $record['purchase_no']; ?></td>
                                            <td><?php echo $record['result']; ?></td>
                                            <td>$<?php echo number_format($record['winning_amount'], 2); ?></td>
                                            <td><?php echo date('d-M-Y', strtotime($record['purchase_datetime'])); ?></td>
                                            <td><?php echo $record['agent_name']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Footer -->
        <?php include('config/footer.php'); ?>
    </div>
</div>

<!-- Initialize DataTable -->
<script>
$(document).ready(function() {
    $('#customerTable').DataTable();
    $('#purchaseEntriesTable').DataTable();
    $('#winLossTable').DataTable();
});
</script>

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