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

/// Fetch recent purchases (Table)
try {
    $recent_purchases_query = ($access_level === 'super_admin') ? 
        "SELECT p.*, c.customer_name, a.agent_name 
         FROM purchase_entries p 
         JOIN customer_details c ON p.customer_id = c.customer_id 
         JOIN admin_access a ON p.agent_id = a.agent_id 
         ORDER BY p.purchase_datetime DESC":
    
        "SELECT p.*, c.customer_name, a.agent_name 
         FROM purchase_entries p 
         JOIN customer_details c ON p.customer_id = c.customer_id 
         JOIN admin_access a ON p.agent_id = a.agent_id 
         WHERE p.agent_id = :agent_id 
         ORDER BY p.purchase_datetime DESC;

    $recent_purchases_stmt = $conn->prepare($recent_purchases_query);
    if ($access_level !== 'super_admin') {
        $recent_purchases_stmt->bindParam(':agent_id', $agent_id);
    }
    $recent_purchases_stmt->execute();
    $recent_purchases = $recent_purchases_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching recent purchases: " . $e->getMessage());
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
                <!-- Include DataTables CSS and JS -->
                <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
                    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
                    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>

                    <!-- Recent Purchases (Table) -->
                    <div class="container-fluid d-none d-md-block">
                        <div class="col-md-12">
                            <h5>Recent Purchases</h5>
                            <table id="recentPurchasesTable" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Agent</th>
                                        <th>Purchase No</th>
                                        <th>Category</th>
                                        <th>Amount</th>
                                        <th>Purchase Date</th>
                                        <th>Status</th> <!-- Changed Result to Status -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_purchases as $purchase): ?>
                                        <tr>
                                            <td><?php echo $purchase['customer_name']; ?></td>
                                            <td><?php echo $purchase['agent_name']; ?></td>
                                            <td><?php echo $purchase['purchase_no']; ?></td>
                                            <td><?php echo $purchase['purchase_category']; ?></td>
                                            <td><?php echo number_format($purchase['purchase_amount'], 2); ?></td>
                                            
                                            <!-- Format Purchase Date to show Month and Day -->
                                            <td><?php echo date('M d', strtotime($purchase['purchase_datetime'])); ?></td>

                                            <!-- Status with Color Coding -->
                                            <td>
                                                <?php if ($purchase['result'] == 'Pending'): ?>
                                                    <span style="color: red; font-weight: bold;">Pending</span>
                                                <?php elseif ($purchase['result'] == 'Prize Given'): ?>
                                                    <span style="color: green; font-weight: bold;">Prize Given</span>
                                                <?php else: ?>
                                                    <span><?php echo $purchase['result']; ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Initialize DataTable -->
                    <script>
                        $(document).ready(function() {
                            $('#recentPurchasesTable').DataTable({
                                "paging": true,       // Enable pagination
                                "searching": true,    // Enable search/filter functionality
                                "ordering": true,     // Enable column sorting
                                "info": true,         // Show table information
                                "lengthChange": true  // Enable the ability to change the number of records per page
                            });
                        });
                    </script>
                
                    
                </div>

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