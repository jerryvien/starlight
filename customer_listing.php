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

// Fetch the access level and agent ID from session
$access_level = $_SESSION['access_level']; 
$agent_id = $_SESSION['agent_id'];

// Handle customer updates via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_customer') {
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
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update customer details.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Fetch customer data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetch_customers') {
    $search_value = $_POST['search']['value']; // Search value
    $start = $_POST['start']; // Start of the limit (pagination)
    $length = $_POST['length']; // Number of records per page

    try {
        $where_clause = 'WHERE c.is_archived = 0'; // Only show customers that are not archived
        $params = [];

        // Add condition to check if updated_at is within the last 365 days
        $where_clause .= ' AND c.updated_at >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)';

        // Restrict access for non-super_admin agents
        if ($access_level !== 'super_admin') {
            $where_clause .= ' AND c.agent_id = :agent_id';
            $params[':agent_id'] = $agent_id;
        }

        // Search filter condition
        if (!empty($search_value)) {
            $where_clause .= ' AND (c.customer_name LIKE :search_value OR a.agent_name LIKE :search_value)';
            $params[':search_value'] = '%' . $search_value . '%';
        }

        // Fetch total number of records
        $total_query = "SELECT COUNT(*) as total FROM customer_details c LEFT JOIN admin_access a ON c.agent_id = a.agent_id $where_clause";
        $stmt_total = $conn->prepare($total_query);
        $stmt_total->execute($params);
        $total_records = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];

        // Fetch filtered data with pagination
        $query = "SELECT c.*, a.agent_name 
                  FROM customer_details c 
                  LEFT JOIN admin_access a ON c.agent_id = a.agent_id 
                  $where_clause 
                  ORDER BY c.created_at DESC 
                  LIMIT :start, :length";

        $stmt = $conn->prepare($query);
        foreach ($params as $key => &$value) {
            $stmt->bindParam($key, $value);
        }
        $stmt->bindParam(':start', $start, PDO::PARAM_INT);
        $stmt->bindParam(':length', $length, PDO::PARAM_INT);
        $stmt->execute();
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Prepare data for DataTables
        $data = [];
        foreach ($customers as $customer) {
            $data[] = [
                $customer['customer_id'],
                $customer['customer_name'],
                $customer['agent_name'],
                '<input type="number" class="form-control update-credit-limit" data-id="' . $customer['customer_id'] . '" value="' . $customer['credit_limit'] . '">',
                '<select class="form-control update-vip-status" data-id="' . $customer['customer_id'] . '">
                    <option value="Normal"' . ($customer['vip_status'] == 'Normal' ? 'selected' : '') . '>Normal</option>
                    <option value="VIP"' . ($customer['vip_status'] == 'VIP' ? 'selected' : '') . '>VIP</option>
                </select>',
            ];
        }

        // Return JSON response
        echo json_encode([
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $total_records,
            "recordsFiltered" => $total_records,
            "data" => $data,
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
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
    <link href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css" rel="stylesheet"> <!-- DataTables CSS -->
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include('config/sidebar.php'); ?> <!-- Reuse your existing standard sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <?php include('config/topbar.php'); ?> <!-- Reuse your standard topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    

                       <!-- Customer Data Table (left side, with ml-4) -->
                       
                        <h1 class="h3 mb-4 text-gray-800">Customer Listing</h1>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Customers</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                     <!-- Customer Listing Table -->
                                    
                                        <table id="myTable" class="display">
                                            <thead>
                                                <tr>
                                                    <th>Customer ID</th>
                                                    <th>Customer Name</th>
                                                    <th>Agent Name</th>
                                                    <th>Credit Limit (RM)</th>
                                                    <th>VIP Status</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    
                                </div>
                            </div>
                        
                    </div>

                   

                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include('config/footer.php'); ?> <!-- Reuse your standard footer -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- jQuery -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script> <!-- DataTables JS -->

    <script>
        $(document).ready(function() {
            var table = $('#myTable').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "customer_listing.php",
                    "type": "POST",
                    "data": { "action": "fetch_customers" }
                },
                "columns": [
                    { "data": 0 },
                    { "data": 1 },
                    { "data": 2 },
                    { "data": 3 },
                    { "data": 4 }
                ]
            });

            // Handle inline updates for credit limit and VIP status
            $('#myTable').on('change', '.update-credit-limit, .update-vip-status', function() {
                var customer_id = $(this).data('id');
                var credit_limit = $(this).closest('tr').find('.update-credit-limit').val();
                var vip_status = $(this).closest('tr').find('.update-vip-status').val();

                $.ajax({
                    url: 'customer_listing.php',
                    type: 'POST',
                    data: {
                        action: 'edit_customer',
                        customer_id: customer_id,
                        credit_limit: credit_limit,
                        vip_status: vip_status
                    },
                    success: function(response) {
                        var result = JSON.parse(response);
                        if (result.status === 'success') {
                            table.ajax.reload(null, false); // Reload the table without resetting pagination
                        } else {
                            alert(result.message);
                        }
                    }
                });
            });
        });
    </script>
</body>

</html>
