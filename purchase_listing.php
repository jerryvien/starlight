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
}

// Apply date and customer filters if provided
$filters = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from_date = !empty($_POST['from_date']) ? $_POST['from_date'] : null;
    $to_date = !empty($_POST['to_date']) ? $_POST['to_date'] : null;
    $customer_name = !empty($_POST['customer_name']) ? $_POST['customer_name'] : null;

    if ($from_date && $to_date) {
        $sql .= " AND p.purchase_datetime BETWEEN :from_date AND :to_date";
        $filters['from_date'] = $from_date;
        $filters['to_date'] = $to_date;
    }

    if ($customer_name) {
        $sql .= " AND c.customer_name LIKE :customer_name";
        $filters['customer_name'] = '%' . $customer_name . '%';
    }
}

$stmt = $conn->prepare($sql);

// Bind agent ID if the user is an agent
if ($access_level !== 'super_admin') {
    $stmt->bindParam(':agent_id', $agent_id);
}

// Bind date and customer name filters if provided
if (isset($filters['from_date']) && isset($filters['to_date'])) {
    $stmt->bindParam(':from_date', $filters['from_date']);
    $stmt->bindParam(':to_date', $filters['to_date']);
}

if (isset($filters['customer_name'])) {
    $stmt->bindParam(':customer_name', $filters['customer_name']);
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

    <!-- Custom fonts and styles for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <script src="vendor/jquery/jquery.min.js"></script>
    <link href="css/bootstrap.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include('sidebar.php'); ?>
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <?php include('topbar.php'); ?>
                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Purchase Listing</h1>

                    <!-- Filter Form -->
                    <form method="POST" action="purchase_listing.php" class="mb-4">
                        <div class="form-row">
                            <div class="col-md-3">
                                <label for="from_date">From Date</label>
                                <input type="date" class="form-control" id="from_date" name="from_date" value="<?php echo isset($from_date) ? $from_date : ''; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="to_date">To Date</label>
                                <input type="date" class="form-control" id="to_date" name="to_date" value="<?php echo isset($to_date) ? $to_date : ''; ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="customer_name">Customer Name</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" placeholder="Search by customer name" value="<?php echo isset($customer_name) ? $customer_name : ''; ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary mt-4">Filter</button>
                            </div>
                        </div>
                    </form>

                    <!-- Purchase List Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Customer Name</th>
                                    <th>Purchase No</th>
                                    <th>Purchase Amount</th>
                                    <th>Purchase Date</th>
                                    <th>Serial Number</th>
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
                                            <td><?php echo $purchase['purchase_date']; ?></td> <!-- Purchase Date only -->
                                            <td><?php echo $purchase['serial_number']; ?></td> <!-- Serial Number -->
                                            <td><?php echo $purchase['agent_name']; ?></td> <!-- Agent Name -->
                                            <td><?php echo $purchase['agent_id']; ?></td> <!-- Agent ID -->
                                        </tr>
                                    <?php endforeach; ?>
                                    <!-- Grand Total Row -->
                                    <tr>
                                        <td colspan="2" class="text-right font-weight-bold">Grand Total:</td>
                                        <td colspan="5" class="font-weight-bold"><?php echo number_format($grand_total, 2); ?></td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No purchases found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include('footer.php'); ?>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
</body>

</html>
