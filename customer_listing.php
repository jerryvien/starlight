<?php
session_start();
include('config/database.php'); // Include your database connection

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
        <?php include('sidebar.php'); ?> <!-- Include sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <?php include('topbar.php'); ?> <!-- Include topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Customer Listing</h1>

                    <!-- Display success message if redirected with ?success=1 in URL -->
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">Customer details updated successfully.</div>
                    <?php endif; ?>

                    <!-- Display error message -->
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
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
            <?php include('footer.php'); ?> <!-- Include footer -->
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
                    "url": "customer_listing.php", // Fetch data from the same page
                    "type": "POST",
                    "data": {
                        "action": "fetch_customers" // Set a flag to differentiate between display and update operations
                    }
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

<?php
// Server-side processing for DataTables
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetch_customers') {
    $columns = ['customer_id', 'customer_name', 'agent_name', 'credit_limit', 'vip_status'];

    // Base query for both access levels
    $query = ($access_level === 'super_admin') ? 
        "SELECT c.*, a.agent_name FROM customer_details c LEFT JOIN admin_access a ON c.agent_id = a.agent_id" : 
        "SELECT c.*, a.agent_name FROM customer_details c LEFT JOIN admin_access a ON c.agent_id = a.agent_id WHERE c.agent_id = :agent_id";

    // Add search functionality
    if (isset($_POST['search']['value']) && $_POST['search']['value'] != '') {
        $search_value = $_POST['search']['value'];
        $query .= " AND (c.customer_name LIKE '%" . $search_value . "%' OR a.agent_name LIKE '%" . $search_value . "%')";
    }

    // Add ordering functionality
    $query .= " ORDER BY " . $columns[$_POST['order'][0]['column']] . " " . $_POST['order'][0]['dir'];
    $query .= " LIMIT " . $_POST['start'] . ", " . $_POST['length'];

    // Execute query
    $stmt = $conn->prepare($query);
    if ($access_level !== 'super_admin') {
        $stmt->bindParam(':agent_id', $agent_id);
    }
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total records for pagination
    $total_query = ($access_level === 'super_admin') ? 
        "SELECT COUNT(*) FROM customer_details" : 
        "SELECT COUNT(*) FROM customer_details WHERE agent_id = :agent_id";
    
    $total_stmt = $conn->prepare($total_query);
    if ($access_level !== 'super_admin') {
        $total_stmt->bindParam(':agent_id', $agent_id);
    }
    $total_stmt->execute();
    $total_records = $total_stmt->fetchColumn();

    // Prepare data for DataTables
    $data = [];
    foreach ($customers as $customer) {
        $data[] = [
            'customer_id' => $customer['customer_id'],
            'customer_name' => $customer['customer_name'],
            'agent_name' => $customer['agent_name'],
            'credit_limit' => number_format($customer['credit_limit'], 2),
            'vip_status' => $customer['vip_status'],
            'actions' => '<form method="POST" action="customer_listing.php">
                            <input type="hidden" name="customer_id" value="'.$customer['customer_id'].'">
                            <input type="number" name="credit_limit" value="'.$customer['credit_limit'].'" class="form-control" />
                            <select name="vip_status" class="form-control">
                                <option value="Normal" '.($customer['vip_status'] == 'Normal' ? 'selected' : '').'>Normal</option>
                                <option value="VIP" '.($customer['vip_status'] == 'VIP' ? 'selected' : '').'>VIP</option>
                            </select>
                            <button type="submit" name="edit_customer" class="btn btn-primary">Save</button>
                          </form>'
        ];
    }

    // Send output to DataTables
    echo json_encode([
        "draw" => intval($_POST['draw']),
        "recordsTotal" => $total_records,
        "recordsFiltered" => $total_records,
        "data" => $data
    ]);
}
?>
