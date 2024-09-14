<?php
session_start();

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Include database connection
include('config/database.php');

// Set default values
$agent_id = $_SESSION['agent_id']; // Capture logged-in agent's ID
$vip_status = 'Normal'; // Default VIP status

// Fetch the last customer ID and increment for the new one
$query = "SELECT customer_id FROM customer ORDER BY customer_id DESC LIMIT 1";
$result = $conn->query($query);
$last_customer = $result->fetch(PDO::FETCH_ASSOC);
$new_customer_id = $last_customer ? $last_customer['customer_id'] + 1 : 1;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_name = $_POST['customer_name'];
    $credit_limit = $_POST['credit_limit'];
    $vip_status = $_POST['vip_status'];

    // Show summary for confirmation
    if (isset($_POST['confirm_summary'])) {
        echo "<h4>Customer Summary</h4>";
        echo "Customer ID: $new_customer_id<br>";
        echo "Customer Name: $customer_name<br>";
        echo "Agent ID: $agent_id<br>";
        echo "Credit Limit: $credit_limit<br>";
        echo "VIP Status: $vip_status<br>";
        echo "<a href='customer_register.php' class='btn btn-secondary'>Go Back and Edit</a>";
        echo "<form method='post' action='customer_register.php'>";
        echo "<input type='hidden' name='final_confirm' value='1'>";
        echo "<input type='hidden' name='customer_name' value='$customer_name'>";
        echo "<input type='hidden' name='credit_limit' value='$credit_limit'>";
        echo "<input type='hidden' name='vip_status' value='$vip_status'>";
        echo "<button type='submit' class='btn btn-primary'>Confirm and Save</button>";
        echo "</form>";
        exit;
    }

    // Save data to the database after confirmation
    if (isset($_POST['final_confirm'])) {
        $sql = "INSERT INTO customer (customer_id, customer_name, agent_id, credit_limit, vip_status, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$new_customer_id, $_POST['customer_name'], $agent_id, $_POST['credit_limit'], $_POST['vip_status']]);
        
        echo "<div class='alert alert-success'>Customer successfully registered!</div>";
    }
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

    <title>Customer Registration</title>

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
                    <h1 class="h3 mb-4 text-gray-800">Customer Registration</h1>

                    <!-- Registration Form -->
                    <div class="row justify-content-center">
                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-body">
                                    <form method="post" action="customer_register.php">
                                        <div class="form-group">
                                            <label>Customer ID</label>
                                            <input type="text" class="form-control" value="<?php echo $new_customer_id; ?>" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label>Agent ID</label>
                                            <input type="text" class="form-control" value="<?php echo $agent_id; ?>" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label>Customer Name</label>
                                            <input type="text" class="form-control" name="customer_name" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Credit Limit (RM)</label>
                                            <input type="number" class="form-control" name="credit_limit" value="500" required>
                                        </div>
                                        <div class="form-group">
                                            <label>VIP Status</label>
                                            <select class="form-control" name="vip_status">
                                                <option value="Normal" selected>Normal</option>
                                                <option value="VIP">VIP</option>
                                            </select>
                                        </div>
                                        <button type="submit" name="confirm_summary" class="btn btn-primary btn-user btn-block">Submit and View Summary</button>
                                    </form>
                                </div>
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

</body>
</html>
