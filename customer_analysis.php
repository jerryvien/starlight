<?php
session_start();
include('config/database.php');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure the user is logged in; redirect if not
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Handle filtering parameters
$filters = [];
$customer_name = isset($_POST['customer_name']) ? $_POST['customer_name'] : '';
$purchase_category = isset($_POST['purchase_category']) ? $_POST['purchase_category'] : '';
$status = isset($_POST['status']) ? $_POST['status'] : '';
$from_date = isset($_POST['from_date']) ? $_POST['from_date'] : '';
$to_date = isset($_POST['to_date']) ? $_POST['to_date'] : '';

$query = "
    SELECT c.customer_name, p.purchase_no, p.purchase_amount, p.purchase_category, p.result, p.purchase_datetime
    FROM purchase_entries p
    JOIN customer_details c ON p.customer_id = c.customer_id
    WHERE p.result = 'Win'
";

// Apply filters if provided
if (!empty($customer_name)) {
    $query .= " AND c.customer_name LIKE :customer_name";
    $filters[':customer_name'] = "%$customer_name%";
}

if (!empty($purchase_category)) {
    $query .= " AND p.purchase_category = :purchase_category";
    $filters[':purchase_category'] = $purchase_category;
}

if (!empty($status)) {
    $query .= " AND p.result = :status";
    $filters[':status'] = $status;
}

if (!empty($from_date) && !empty($to_date)) {
    $query .= " AND DATE(p.purchase_datetime) BETWEEN :from_date AND :to_date";
    $filters[':from_date'] = $from_date;
    $filters[':to_date'] = $to_date;
}

// Prepare and execute the query
$stmt = $conn->prepare($query);
foreach ($filters as $key => $value) {
    $stmt->bindParam($key, $value);
}
$stmt->execute();
$winning_customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Customer Win Report</title>

    <!-- Custom fonts for this template -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/select/1.3.3/css/select.dataTables.min.css">

    <!-- jQuery and DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
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
                    <h1 class="h3 mb-4 text-gray-800">Customer Win Report</h1>

                    <!-- Filter Form -->
                    <form method="POST" action="customer_analysis.php" class="mb-4">
                        <div class="form-row">
                            <div class="col-md-3">
                                <label for="customer_name">Customer Name</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" placeholder="Search by customer name" value="<?php echo $customer_name; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="purchase_category">Purchase Category</label>
                                <select class="form-control" id="purchase_category" name="purchase_category">
                                    <option value="">All Categories</option>
                                    <option value="Box" <?php echo $purchase_category == 'Box' ? 'selected' : ''; ?>>Box</option>
                                    <option value="Straight" <?php echo $purchase_category == 'Straight' ? 'selected' : ''; ?>>Straight</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="Win" <?php echo $status == 'Win' ? 'selected' : ''; ?>>Win</option>
                                    <option value="Loss" <?php echo $status == 'Loss' ? 'selected' : ''; ?>>Loss</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="from_date">From Date</label>
                                <input type="date" class="form-control" id="from_date" name="from_date" value="<?php echo $from_date; ?>">
                            </div>
                            <div class="col-md-3 mt-3">
                                <label for="to_date">To Date</label>
                                <input type="date" class="form-control" id="to_date" name="to_date" value="<?php echo $to_date; ?>">
                            </div>
                        </div>
                        <div class="form-row mt-3">
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary mt-4">Filter</button>
                            </div>
                        </div>
                    </form>

                    <!-- Data Table to Show Winning Customers -->
                    <div class="table-responsive">
                        <table id="winReportTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Customer Name</th>
                                    <th>Purchase No</th>
                                    <th>Purchase Amount</th>
                                    <th>Purchase Category</th>
                                    <th>Status</th>
                                    <th>Purchase Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($winning_customers as $record): ?>
                                <tr>
                                    <td><?php echo $record['customer_name']; ?></td>
                                    <td><?php echo $record['purchase_no']; ?></td>
                                    <td><?php echo number_format($record['purchase_amount'], 2); ?></td>
                                    <td><?php echo $record['purchase_category']; ?></td>
                                    <td><?php echo $record['result']; ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($record['purchase_datetime'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Initialize DataTables -->
    <script>
        $(document).ready(function() {
            $('#winReportTable').DataTable({
                "paging": true,       // Enable pagination
                "pageLength": 10,     // Display 10 rows at a time
                "searching": true,    // Enable search/filter functionality
                "ordering": true,     // Enable column sorting
                "info": true,         // Show table information
                "lengthChange": true  // Enable the ability to change rows per page
            });
        });
    </script>

</body>

</html>