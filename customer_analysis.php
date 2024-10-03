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
        $stmt = $conn->query("SELECT c.customer_name, c.total_sales, a.agent_name 
                              FROM customer_details c 
                              JOIN admin_access a ON c.agent_id = a.agent_id 
                              ORDER BY c.created_at DESC");
    } else {
        // Fetch customers only for the agent
        $stmt = $conn->prepare("SELECT c.customer_name, c.total_sales, a.agent_name 
                                FROM customer_details c 
                                JOIN admin_access a ON c.agent_id = a.agent_id 
                                WHERE c.agent_id = :agent_id 
                                ORDER BY c.created_at DESC");
        $stmt->bindParam(':agent_id', $_SESSION['agent_id']);
        $stmt->execute();
    }
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching customer data: " . $e->getMessage());
}

// Fetch customer details when selected
$selected_customer = null;
if (isset($_POST['select_customer'])) {
    $customer_name = $_POST['select_customer'];
    $stmt = $conn->prepare("SELECT c.*, a.agent_name 
                            FROM customer_details c 
                            JOIN admin_access a ON c.agent_id = a.agent_id 
                            WHERE c.customer_name = :customer_name");
    $stmt->bindParam(':customer_name', $customer_name);
    $stmt->execute();
    $selected_customer = $stmt->fetch(PDO::FETCH_ASSOC);
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

    <style>
        .customer-details {
            background-color: #f8f9fc;
            padding: 15px;
            border-radius: 5px;
        }

        .customer-details h4 {
            font-size: 1.25rem;
        }

        .customer-details ul {
            list-style-type: none;
            padding: 0;
        }

        .customer-details ul li {
            margin-bottom: 8px;
        }
    </style>
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

                    <!-- Row for customer table and customer details -->
                    <div class="row">
                        <!-- Left column for Customer Data Table -->
                        <div class="col-md-6 ml-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Customer Data</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="customersTable" class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Customer Name</th>
                                                    <th>Agent Name</th>
                                                    <th>Total Sales</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($customers as $customer): ?>
                                                <tr>
                                                    <td><?php echo $customer['customer_name']; ?></td>
                                                    <td><?php echo $customer['agent_name']; ?></td>
                                                    <td>$ <?php echo number_format($customer['total_sales'], 2); ?></td>
                                                    <td>
                                                        <form method="POST">
                                                            <button type="submit" name="select_customer" value="<?php echo $customer['customer_name']; ?>" class="btn btn-warning">Select</button>
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

                        <!-- Right column for selected customer details -->
                        <div class="col-md-6">
                            <?php if ($selected_customer): ?>
                            <div class="customer-details">
                                <h4>Customer Information</h4>
                                <ul>
                                    <li><strong>Customer Name:</strong> <?php echo $selected_customer['customer_name']; ?></li>
                                    <li><strong>Customer ID:</strong> <?php echo $selected_customer['customer_id']; ?></li>
                                    <li><strong>Agent Name:</strong> <?php echo $selected_customer['agent_name']; ?></li>
                                    <li><strong>Total Sales:</strong> $<?php echo number_format($selected_customer['total_sales'], 2); ?></li>
                                    <li><strong>Purchase History Count:</strong> <?php echo $selected_customer['purchase_history_count']; ?></li>
                                    <li><strong>Credit Limit:</strong> $<?php echo number_format($selected_customer['credit_limit'], 2); ?></li>
                                    <li><strong>VIP Status:</strong> <?php echo $selected_customer['vip_status']; ?></li>
                                    <li><strong>Created At:</strong> <?php echo date('d-M-Y', strtotime($selected_customer['created_at'])); ?></li>
                                    <li><strong>Last Updated:</strong> <?php echo date('d-M-Y', strtotime($selected_customer['updated_at'])); ?></li>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
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