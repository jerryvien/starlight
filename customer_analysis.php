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
        SELECT c.customer_id, c.customer_name, a.agent_name, IFNULL(SUM(p.purchase_amount), 0) AS total_sales, c.created_at
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
                                    <table id="customerDataTable" class="table table-bordered" width="100%" cellspacing="0">
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
                                                <td><?php echo number_format($customer['total_sales'], 2); ?></td>
                                                <td>
                                                    <form method="POST">
                                                        <button type="submit" name="select_customer" value="<?php echo $customer['customer_id']; ?>" class="btn btn-primary">Select</button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Details -->
                    <div class="col-md-5 ml-4">
                        <?php if ($selected_customer): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Customer Information</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-group">
                                    <li class="list-group-item"><strong>Customer ID:</strong> <?php echo $selected_customer['customer_id']; ?></li>
                                    <li class="list-group-item"><strong>Customer Name:</strong> <?php echo $selected_customer['customer_name']; ?></li>
                                    <li class="list-group-item"><strong>Total Sales:</strong> <?php echo number_format($selected_customer['total_sales'], 2); ?></li>
                                    <li class="list-group-item"><strong>Credit Limit:</strong> <?php echo number_format($selected_customer['credit_limit'], 2); ?></li>
                                    <li class="list-group-item"><strong>VIP Status:</strong> <?php echo $selected_customer['vip_status']; ?></li>
                                    <li class="list-group-item"><strong>Created At:</strong> <?php echo date('d-M-Y', strtotime($selected_customer['created_at'])); ?></li>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>
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
                                            <?php foreach ($related_purchases as $purchase): ?>
                                            <tr>
                                                <td><?php echo $purchase['purchase_no']; ?></td>
                                                <td><?php echo number_format($purchase['purchase_amount'], 2); ?></td>
                                                <td><?php echo date('d-M-Y', strtotime($purchase['purchase_datetime'])); ?></td>
                                                <td><?php echo $purchase['agent_name']; ?></td>
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
            <!-- End of Page Content -->

            <!-- Footer -->
            <?php include('config/footer.php'); ?>
        </div>

        <!-- Scroll to Top Button-->
        <a class="scroll-to-top rounded" href="#page-top">
            <i class="fas fa-angle-up"></i>
        </a>
    </div>

    <!-- Core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>

    <!-- DataTables -->
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <!-- Initialize DataTables -->
    <script>
    $(document).ready(function() {
        $('#customerDataTable').DataTable();
        $('#purchaseEntriesTable').DataTable();
    });
    </script>
</body>
</html>