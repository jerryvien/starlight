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

// Fetch customer data
try {
    if ($_SESSION['access_level'] === 'super_admin') {
        // Fetch all customers for super_admin
        $stmt = $conn->query("SELECT * FROM customer_details ORDER BY created_at DESC");
    } else {
        // Fetch customers only for the agent
        $stmt = $conn->prepare("SELECT * FROM customer_details WHERE agent_id = :agent_id ORDER BY created_at DESC");
        $stmt->bindParam(':agent_id', $_SESSION['agent_id']);
        $stmt->execute();
    }
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching customer data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Data</title>

    <!-- Custom fonts for this template -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

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

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">Customer Data</h1>

                    <!-- Customer Data Table -->
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
                                            <th>Agent ID</th>
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

                    <!-- Placeholder for matched purchase entries (you can implement further logic here) -->
                    <div class="container-fluid">
                        <h3 class="mb-4 text-gray-800">Matched Purchase Entries</h3>
                        <!-- Add your purchase entries table here -->
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include('config/footer.php'); ?>

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

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

    <!-- Initialize DataTable -->
    <script>
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('#customersTable')) {
                $('#customersTable').DataTable().clear().destroy();  // Destroy previous instance
            }
            $('#customersTable').DataTable();  // Initialize DataTable
        });
    </script>

</body>

</html>