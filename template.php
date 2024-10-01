<?php
session_start();
include('config/database.php'); // Include your database connection

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Determine access level
$access_level = $_SESSION['access_level'];
$agent_id = $_SESSION['agent_id'];

// Fetch agents for the agent filter dropdown (for super_admin only)
$agents = [];
if ($access_level === 'super_admin') {
    $stmt = $conn->query("SELECT agent_id, agent_name FROM admin_access");
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch purchase records based on the access level
$sql = "
    SELECT p.purchase_no, p.purchase_amount, DATE(p.purchase_datetime) AS purchase_date, 
           p.serial_number, a.agent_name, a.agent_id,
           c.customer_name
    FROM purchase_entries p
    JOIN customer_details c ON p.customer_id = c.customer_id
    JOIN admin_access a ON p.agent_id = a.agent_id
";

// If the user is an agent, filter results by agent ID
if ($access_level !== 'super_admin') {
    $sql .= " WHERE p.agent_id = :agent_id";
} else {
    $sql .= " WHERE 1=1"; // Dummy condition for super_admin to add more filters easily
}

$stmt = $conn->prepare($sql);

// Bind agent ID if the user is an agent
if ($access_level !== 'super_admin') {
    $stmt->bindParam(':agent_id', $agent_id);
}

$stmt->execute();
$purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate the grand total of the purchase amounts
$grand_total = 0;
foreach ($purchases as $purchase) {
    $grand_total += $purchase['purchase_amount'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Purchase Listing</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">

    <!-- Custom styles for this template -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <?php include('config/sidebar.php'); ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <?php include('config/topbar.php'); ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Purchase Listing</h1>

                    <!-- Purchase List Table -->
                    <div class="table-responsive">
                        <table id="purchaseListTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Customer Name</th>
                                    <th>Purchase No</th>
                                    <th>Purchase Amount</th>
                                    <th>Purchase Date</th>
                                    <th>Agent Name</th>
                                    <th>Agent ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($purchases) > 0): ?>
                                    <?php foreach ($purchases as $purchase): ?>
                                        <tr>
                                            <td><?php echo $purchase['customer_name']; ?></td>
                                            <td><?php echo $purchase['purchase_no']; ?></td>
                                            <td><?php echo number_format($purchase['purchase_amount'], 2); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($purchase['purchase_date'])); ?></td>
                                            <td><?php echo $purchase['agent_name']; ?></td>
                                            <td><?php echo $purchase['agent_id']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <!-- Grand Total Row -->
                                    <tr>
                                        <td colspan="2" class="text-right font-weight-bold">Grand Total:</td>
                                        <td colspan="4" class="font-weight-bold"><?php echo number_format($grand_total, 2); ?></td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No records found for the applied filters</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Initialize DataTable -->
                    <script>
                        $(document).ready(function() {
                            $('#purchaseListTable').DataTable({
                                "paging": true,       // Enable pagination
                                "searching": true,    // Enable search/filter functionality
                                "ordering": true,     // Enable column sorting
                                "info": true,         // Show table information
                                "lengthChange": true  // Enable the ability to change the number of records per page
                            });
                        });
                    </script>

                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include('config/footer.php'); ?>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

</body>

</html>