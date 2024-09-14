<?php
session_start();

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Include database connection
include('config/database.php');

// Fetch logged-in user info
$agent_id = $_SESSION['agent_id'];
$access_level = $_SESSION['access_level'];

// Fetch customer data based on access level
if ($access_level == 'super_admin') {
    $sql = "SELECT * FROM customer";
} else {
    $sql = "SELECT * FROM customer WHERE agent_id = ?";
}
$stmt = $conn->prepare($sql);
$stmt->execute($access_level != 'super_admin' ? [$agent_id] : []);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission for editing a customer
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_customer'])) {
    $customer_id = $_POST['customer_id'];
    $customer_name = $_POST['customer_name'];
    $credit_limit = $_POST['credit_limit'];
    $vip_status = $_POST['vip_status'];

    // Update the customer in the database
    $update_sql = "UPDATE customer SET customer_name = ?, credit_limit = ?, vip_status = ? WHERE customer_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->execute([$customer_name, $credit_limit, $vip_status, $customer_id]);

    // Redirect to refresh the page and prevent re-submission
    header("Location: customer_listing.php");
    exit;
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

    <title>Customer Listing</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Include sidebar -->
        <?php include('sidebar.php'); ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Include topbar -->
                <?php include('topbar.php'); ?>

                <!-- Page Heading -->
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Customer Listing</h1>

                    <!-- Customer Table -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Customer ID</th>
                                            <th>Customer Name</th>
                                            <th>Agent ID</th>
                                            <th>Credit Limit</th>
                                            <th>VIP Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($customers as $customer) : ?>
                                            <tr>
                                                <td><?php echo $customer['customer_id']; ?></td>
                                                <td><?php echo $customer['customer_name']; ?></td>
                                                <td><?php echo $customer['agent_id']; ?></td>
                                                <td><?php echo $customer['credit_limit']; ?></td>
                                                <td><?php echo $customer['vip_status']; ?></td>
                                                <td>
                                                    <button class="btn btn-warning btn-sm" onclick="editCustomer(<?php echo $customer['customer_id']; ?>)">Edit</button>
                                                </td>
                                            </tr>
                                            <!-- Edit form dynamically displayed for the selected customer -->
                                            <tr id="edit-form-<?php echo $customer['customer_id']; ?>" style="display: none;">
                                                <td colspan="6">
                                                    <form method="post" action="customer_listing.php">
                                                        <input type="hidden" name="customer_id" value="<?php echo $customer['customer_id']; ?>">
                                                        <div class="form-row">
                                                            <div class="form-group col-md-4">
                                                                <label for="customer_name">Customer Name</label>
                                                                <input type="text" class="form-control" name="customer_name" value="<?php echo $customer['customer_name']; ?>" required>
                                                            </div>
                                                            <div class="form-group col-md-4">
                                                                <label for="credit_limit">Credit Limit</label>
                                                                <input type="number" class="form-control" name="credit_limit" value="<?php echo $customer['credit_limit']; ?>" required>
                                                            </div>
                                                            <div class="form-group col-md-4">
                                                                <label for="vip_status">VIP Status</label>
                                                                <select class="form-control" name="vip_status">
                                                                    <option value="Normal" <?php if ($customer['vip_status'] == 'Normal') echo 'selected'; ?>>Normal</option>
                                                                    <option value="VIP" <?php if ($customer['vip_status'] == 'VIP') echo 'selected'; ?>>VIP</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <button type="submit" name="edit_customer" class="btn btn-primary">Save Changes</button>
                                                        <button type="button" class="btn btn-secondary" onclick="cancelEdit(<?php echo $customer['customer_id']; ?>)">Cancel</button>
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
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Ken Group 2024</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <!-- JavaScript to handle the in-line edit functionality -->
    <script>
        function editCustomer(customerId) {
            document.getElementById('edit-form-' + customerId).style.display = 'table-row';
        }

        function cancelEdit(customerId) {
            document.getElementById('edit-form-' + customerId).style.display = 'none';
        }
    </script>

</body>

</html>
