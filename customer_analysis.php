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
    $stmt = $conn->query("SELECT * FROM customer_details WHERE purchase_history_count > 0 OR created_at > NOW() - INTERVAL 365 DAY ORDER BY created_at DESC");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching customer data: " . $e->getMessage());
}

// Initialize variables
$selected_customer = null;
$related_purchases = [];
$subtotal_purchase_amount = 0;
$subtotal_winning_amount = 0;
$total_win_amount = 0;
$total_loss_amount = 0;

// Handle form submission for selecting customer
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['select_customer'])) {
    $customer_id = $_POST['select_customer'];

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

            <div class="container-fluid">
                <div class="row">
                    <!-- Customer Data Table (left side, with ml-4) -->
                    <div class="col-md-4 ml-6">
                        <h1 class="h3 mb-2 text-gray-800">Customer Data</h1>
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

                    <!-- Customer Information (right side, with mr-8) -->
                    <div class="col-md-4 ml-6">
                        <?php if ($selected_customer): ?>
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
                        <?php endif; ?>
                    </div>

                     <!-- Combined Purchase and Win/Loss Records Table -->
                     <?php if (!empty($related_purchases)): ?>
                        <div class="col-md-8 ml-10">
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

    <script>
    $(document).ready(function() {
        $('#customerTable').DataTable();
        $('#purchaseEntriesTable').DataTable();
    });
    </script>

</body>
</html>