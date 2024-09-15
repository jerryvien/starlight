<?php
session_start();
include('config/database.php'); // Include your database connection

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Initialize variables
$success_message = '';
$error_message = '';
$serial_number = gethostname() . '_' . date('YmdHis'); // Generate the serial number on page load
$agent_id = $_SESSION['agent_id'];
$access_level = $_SESSION['access_level']; // Assuming access_level is stored in session

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['confirm'])) {
        // Insert purchase entries into the database after confirmation
        $customer_id = $_POST['customer_id'];
        $purchase_entries = $_POST['purchase_no'];
        $purchase_category = $_POST['purchase_category'];
        $purchase_amount = $_POST['purchase_amount'];
        $purchase_date = $_POST['purchase_date'];
        $agent_id_to_save = ($access_level === 'super_admin') ? $_POST['agent_id'] : $agent_id;

        // Insert each purchase entry into the database
        for ($i = 0; $i < count($purchase_entries); $i++) {
            $sql = "INSERT INTO purchase_entries (customer_id, agent_id, purchase_no, purchase_category, purchase_amount, purchase_datetime, serial_number) 
                    VALUES (:customer_id, :agent_id, :purchase_no, :purchase_category, :purchase_amount, :purchase_datetime, :serial_number)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':customer_id', $customer_id);
            $stmt->bindParam(':agent_id', $agent_id_to_save);
            $stmt->bindParam(':purchase_no', $purchase_entries[$i]);
            $stmt->bindParam(':purchase_category', $purchase_category[$i]);
            $stmt->bindParam(':purchase_amount', $purchase_amount[$i]);
            $stmt->bindParam(':purchase_datetime', $purchase_date[$i]);
            $stmt->bindParam(':serial_number', $serial_number);
            $stmt->execute();
        }

        $success_message = "Purchase entries added successfully with serial number: $serial_number";
    }
}

// Fetch customer details on search
if (isset($_POST['query'])) {
    $query = $_POST['query'];
    $stmt = $conn->prepare("SELECT * FROM customer_details WHERE customer_name LIKE :query AND agent_id = :agent_id LIMIT 1");
    $stmt->bindParam(':query', "%$query%");
    $stmt->bindParam(':agent_id', $_SESSION['agent_id']);
    $stmt->execute();
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
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

    <title>Purchase Entry</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
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
                    <h1 class="h3 mb-4 text-gray-800">Purchase Entry</h1>

                    <!-- Success or error messages -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php elseif ($error_message): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>

                    <!-- Customer search -->
                    <div class="form-group">
                        <label for="customer_search">Search Customer</label>
                        <input type="text" class="form-control" id="customer_search" placeholder="Start typing customer name...">
                        <div id="customer_details">
                            <?php if (isset($customer)): ?>
                                <p>Customer ID: <?php echo $customer['customer_id']; ?></p>
                                <p>Customer Name: <?php echo $customer['customer_name']; ?></p>
                                <p>Credit Limit: <?php echo $customer['credit_limit']; ?></p>
                                <p>VIP Status: <?php echo $customer['vip_status']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Purchase entry form -->
                    <form method="POST" action="purchase_entry.php">
                        <div class="form-group">
                            <label for="purchase_no">Number of Purchase Entries</label>
                            <select class="form-control" id="purchase_no" name="purchase_no[]" required>
                                <?php for ($i = 1; $i <= 10; $i++) { ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <!-- Dynamic purchase entries -->
                        <div id="dynamic_purchase_entries">
                            <!-- Default one entry -->
                            <div class="form-group">
                                <label>Purchase Entry</label>
                                <input type="text" class="form-control" name="purchase_no[]" placeholder="Enter purchase number">
                                <select class="form-control" name="purchase_category[]">
                                    <option value="Box">Box</option>
                                    <option value="Straight">Straight</option>
                                </select>
                                <input type="number" class="form-control" name="purchase_amount[]" placeholder="Enter purchase amount">
                                <input type="date" class="form-control" name="purchase_date[]" placeholder="Enter purchase date">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="serial_number">Serial Number</label>
                            <input type="text" class="form-control" id="serial_number" value="<?php echo $serial_number; ?>" readonly>
                        </div>

                        <!-- Submit button to preview and confirm -->
                        <button type="submit" name="preview_summary" class="btn btn-info">Preview</button>

                        <!-- After preview, show confirmation form -->
                        <?php if (isset($_POST['preview_summary'])) { ?>
                            <h4>Confirm your purchase details</h4>
                            <p>Customer ID: <?php echo $_POST['customer_id']; ?></p>
                            <p>Purchase Amount: <?php echo $_POST['purchase_amount']; ?></p>
                            <p>Purchase Date: <?php echo $_POST['purchase_date']; ?></p>
                            <p>Serial Number: <?php echo $serial_number; ?></p>

                            <input type="hidden" name="customer_id" value="<?php echo $_POST['customer_id']; ?>">
                            <input type="hidden" name="purchase_no" value="<?php echo $_POST['purchase_no']; ?>">
                            <input type="hidden" name="purchase_amount" value="<?php echo $_POST['purchase_amount']; ?>">
                            <input type="hidden" name="purchase_date" value="<?php echo $_POST['purchase_date']; ?>">
                            <input type="hidden" name="serial_number" value="<?php echo $serial_number; ?>">

                            <button type="submit" name="confirm" class="btn btn-primary">Confirm and Save</button>
                        <?php } ?>
                    </form>
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

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>

    <script>
        $('#customer_search').on('input', function () {
            var searchQuery = $(this).val();
            if (searchQuery.length > 0) {
                $.ajax({
                    url: 'fetch_customer_details.php',
                    method: 'POST',
                    data: { query: searchQuery },
                    success: function (data) {
                        $('#customer_details').html(data);
                    }
                });
            }
        });
    </script>

</body>
</html>