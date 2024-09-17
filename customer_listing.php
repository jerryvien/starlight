<?php
session_start();
include('config/database.php'); // Include your database connection


// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Redirect to login page if the user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Fetch the access level from session
$access_level = $_SESSION['access_level']; 
$agent_id = $_SESSION['agent_id'];

// Handle update submission (when edit button is clicked)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_customer'])) {
    $customer_id = $_POST['customer_id'];
    $credit_limit = $_POST['credit_limit'];
    $vip_status = $_POST['vip_status'];

    try {
        $updateQuery = "UPDATE customer_details SET credit_limit = :credit_limit, vip_status = :vip_status WHERE customer_id = :customer_id";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bindParam(':credit_limit', $credit_limit);
        $stmt->bindParam(':vip_status', $vip_status);
        $stmt->bindParam(':customer_id', $customer_id);
        
        if ($stmt->execute()) {
            header("Location: customer_listing.php?success=1");
            exit;
        } else {
            $error_message = "Failed to update customer details.";
        }
    } catch (PDOException $e) {
        $error_message = "Error updating customer: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Customer Listing</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include('sidebar.php'); ?> 

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <?php include('topbar.php'); ?> 

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Customer Listing</h1>

                    <!-- Display success message -->
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">Customer details updated successfully.</div>
                    <?php endif; ?>

                    <!-- Customer Listing Table -->
                    <div class="table-responsive">
                        <table id="myTable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Customer ID</th>
                                    <th>Customer Name</th>
                                    <th>Agent Name</th>
                                    <th>Credit Limit (RM)</th>
                                    <th>VIP Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include('footer.php'); ?> 
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#myTable').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "fetch_customer_data.php", // Fetch data from server
                    "type": "POST"
                },
                "columns": [
                    { "data": "customer_id" },
                    { "data": "customer_name" },
                    { "data": "agent_name" },
                    { "data": "credit_limit" },
                    { "data": "vip_status" },
                    { "data": "actions" }
                ],
                "paging": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": true
            });
        });
    </script>
</body>

</html>
