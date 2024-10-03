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

// Fetch all customers for the first table
$customers = [];
try {
    $stmt = $conn->query("SELECT * FROM customer_details ORDER BY customer_name ASC");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching customer records: " . $e->getMessage());
}

// Handle customer selection
$matching_purchases = [];  // Initialize an empty array
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['select_customer'])) {
    $customer_id = $_POST['select_customer'];

    // Fetch related purchase entries for the selected customer
    try {
        $purchase_stmt = $conn->prepare("
            SELECT p.*, c.customer_name, a.agent_name 
            FROM purchase_entries p
            JOIN customer_details c ON p.customer_id = c.customer_id
            JOIN admin_access a ON p.agent_id = a.agent_id
            WHERE p.customer_id = :customer_id 
              AND p.result IN ('Win', 'Loss')
        ");
        $purchase_stmt->bindParam(':customer_id', $customer_id);
        $purchase_stmt->execute();
        $matching_purchases = $purchase_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        die("Error fetching purchase records: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer and Purchase Entries</title>
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

            <!-- Customer Data Table -->
            <div class="container-fluid">
                <h1 class="h3 mb-4 text-gray-800">Customer Records</h1>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Customer Data</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="customersTable" class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Customer ID</th>
                                        <th>Customer Name</th>
                                        <th>Total Sales</th>
                                        <th>Agent Name</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($customers as $customer): ?>
                                    <tr>
                                        <td><?php echo $customer['customer_id']; ?></td>
                                        <td><?php echo $customer['customer_name']; ?></td>
                                        <td>$$ <?php echo $customer['total_sales']; ?></td>
                                        <td><?php echo $customer['agent_id']; ?></td>
                                        <td>
                                            <form method="POST">
                                                <button type="submit" name="select_customer" value="<?php echo $customer['customer_id']; ?>" class="btn btn-warning">Select</button>
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

            <!-- Related Purchase Entries Table -->
            <?php if (!empty($matching_purchases)): ?>
            <div class="container-fluid">
                <h1 class="h3 mb-4 text-gray-800">Related Purchase Entries</h1>
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
                                        <th>Winning Category</th>
                                        <th>Winning Amount</th>
                                        <th>Agent Name</th>
                                        <th>Result</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($matching_purchases as $purchase): ?>
                                    <?php
                                        // Determine winning factor
                                        $winning_factor = ($purchase['winning_category'] === 'Box') ? 1 : 2;
                                        $winning_amount = $winning_factor * $purchase['purchase_amount'];
                                    ?>
                                    <tr>
                                        <td><?php echo $purchase['purchase_no']; ?></td>
                                        <td><?php echo $purchase['purchase_amount']; ?></td>
                                        <td><?php echo $purchase['purchase_datetime']; ?></td>
                                        <td><?php echo $purchase['winning_category']; ?></td>
                                        <td><?php echo $winning_amount; ?></td>
                                        <td><?php echo $purchase['agent_name']; ?></td>
                                        <td><?php echo $purchase['result']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

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
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="vendor/datatables/jquery.dataTables.min.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

<!-- Initialize DataTables -->
<script>
    $(document).ready(function() {
        $('#customersTable').DataTable();
        $('#purchaseEntriesTable').DataTable();
    });
</script>

</body>
</html>