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

// Fetch winning records
try {
    $winning_records_query = "
        SELECT w.*, a.agent_name 
        FROM winning_record w
        JOIN admin_access a ON w.created_by_agent = a.agent_id
        ORDER BY w.winning_date DESC
    ";

    $winning_records_stmt = $conn->prepare($winning_records_query);
    $winning_records_stmt->execute();
    $winning_records = $winning_records_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching winning records: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Winning Records</title>

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
                    <h1 class="h3 mb-4 text-gray-800">Winning Records</h1>

                    <!-- Winning Records Table -->
                    <div class="table-responsive mt-3">
                        <table id="winningRecordsTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Winning Number</th>
                                    <th>Winning Game</th>
                                    <th>Winning Period</th>
                                    <th>Winning Date</th>
                                    <th>Total Payout</th>
                                    <th>Created By Agent</th>
                                    <th>Created At</th>
                                    <th>Winning Listing</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($winning_records) > 0): ?>
                                    <?php foreach ($winning_records as $record): ?>
                                        <tr>
                                            <td><?php echo $record['winning_number']; ?></td>
                                            <td><?php echo $record['winning_game']; ?></td>
                                            <td><?php echo $record['winning_period']; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($record['winning_date'])); ?></td>
                                            <td><?php echo number_format($record['winning_total_payout'], 2); ?></td>
                                            <td><?php echo $record['agent_name']; ?></td>
                                            <td><?php echo date('M d, Y h:i A', strtotime($record['created_at'])); ?></td>
                                            <td>
                                                <?php echo $record['winning_listing'] ? '<span class="text-success">Listed</span>' : '<span class="text-danger">Not Listed</span>'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No records found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Initialize DataTable -->
                    <script>
                        $(document).ready(function() {
                            $('#winningRecordsTable').DataTable({
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