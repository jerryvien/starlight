<?php
session_start();
include('config/database.php'); // Include database connection

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Fetch the logged-in agent ID
$agent_id = $_SESSION['agent_id'];

// Initialize variables
$customer_name = '';
$purchase_entries = [];
$error_message = '';
$success_message = '';
$purchase_rows = isset($_POST['num_purchases']) ? $_POST['num_purchases'] : 1;

// Handle customer search
if (isset($_POST['search_customer'])) {
    $customer_name = $_POST['customer_name'];
    try {
        // Fetch customer details for the agent level
        $stmt = $conn->prepare("SELECT * FROM customer_details WHERE agent_id = :agent_id AND customer_name LIKE :customer_name");
        $stmt->bindParam(':agent_id', $agent_id);
        $stmt->bindValue(':customer_name', '%' . $customer_name . '%');
        $stmt->execute();
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Error fetching customers: " . $e->getMessage();
    }
}

// Handle purchase entry submission
if (isset($_POST['confirm_purchase'])) {
    $customer_id = $_POST['customer_id'];
    $total_purchase_amount = 0;
    $purchase_entries = [];

    for ($i = 0; $i < $purchase_rows; $i++) {
        $purchase_number = $_POST['purchase_number'][$i];
        $purchase_category = $_POST['purchase_category'][$i];
        $purchase_amount = $_POST['purchase_amount'][$i];
        $purchase_date = $_POST['purchase_date'][$i];

        // Serial number generation (combination of computer ID and date-time)
        $serial_number = substr(gethostname(), -8) . '-' . date('YmdHis');

        // Calculate total amount based on purchase category
        $total_purchase_amount += $purchase_amount;

        $purchase_entries[] = [
            'purchase_number' => $purchase_number,
            'purchase_category' => $purchase_category,
            'purchase_amount' => $purchase_amount,
            'purchase_date' => $purchase_date,
            'serial_number' => $serial_number
        ];
    }

    // Insert purchase entries into the database
    try {
        foreach ($purchase_entries as $entry) {
            $stmt = $conn->prepare("INSERT INTO purchase_entries (customer_id, agent_id, purchase_number, purchase_category, purchase_amount, purchase_date, serial_number, created_at) 
                                    VALUES (:customer_id, :agent_id, :purchase_number, :purchase_category, :purchase_amount, :purchase_date, :serial_number, NOW())");
            $stmt->bindParam(':customer_id', $customer_id);
            $stmt->bindParam(':agent_id', $agent_id);
            $stmt->bindParam(':purchase_number', $entry['purchase_number']);
            $stmt->bindParam(':purchase_category', $entry['purchase_category']);
            $stmt->bindParam(':purchase_amount', $entry['purchase_amount']);
            $stmt->bindParam(':purchase_date', $entry['purchase_date']);
            $stmt->bindParam(':serial_number', $entry['serial_number']);
            $stmt->execute();
        }
        $success_message = "Purchase entries saved successfully.";
    } catch (PDOException $e) {
        $error_message = "Error saving purchase entries: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Purchase Entry</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet"> <!-- For Datepicker -->
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
                    <h1 class="h3 mb-4 text-gray-800">Purchase Entry</h1>

                    <!-- Error and Success Messages -->
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>

                    <!-- Customer Search Form -->
                    <form method="POST">
                        <div class="form-group">
                            <label for="customer_name">Search Customer by Name</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                            <button type="submit" name="search_customer" class="btn btn-primary mt-2">Search</button>
                        </div>
                    </form>

                    <!-- Show search results -->
                    <?php if (isset($customers) && count($customers) > 0): ?>
                        <div class="form-group">
                            <label for="customer_id">Select Customer</label>
                            <select name="customer_id" id="customer_id" class="form-control">
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?php echo $customer['customer_id']; ?>"><?php echo $customer['customer_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Purchase Entry Form -->
                    <form method="POST" id="purchase_form">
                        <div class="form-group">
                            <label for="num_purchases">Number of Purchase Entries</label>
                            <select id="num_purchases" name="num_purchases" class="form-control">
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php if ($i == $purchase_rows) echo 'selected'; ?>><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- Dynamic Purchase Entry Fields -->
                        <div id="purchase_entries">
                            <?php for ($i = 0; $i < $purchase_rows; $i++): ?>
                                <div class="form-row">
                                    <div class="form-group col-md-3">
                                        <label for="purchase_number">Purchase Number</label>
                                        <input type="text" class="form-control" name="purchase_number[]" required>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="purchase_category">Category</label>
                                        <select class="form-control" name="purchase_category[]">
                                            <option value="Box">Box</option>
                                            <option value="Straight">Straight</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="purchase_amount">Amount (RM)</label>
                                        <input type="number" class="form-control" name="purchase_amount[]" required>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="purchase_date">Purchase Date</label>
                                        <input type="text" class="form-control purchase_datepicker" name="purchase_date[]" required>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <button type="submit" name="confirm_purchase" class="btn btn-primary mt-4">Submit and Confirm</button>
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

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-ui/jquery-ui.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>

    <!-- Dynamic Field JS -->
    <script>
        $(function () {
            // Datepicker for Purchase Date
            $(".purchase_datepicker").datepicker({
                dateFormat: "yy-mm-dd"
            });

            // Update form dynamically based on number of purchases selected
            $("#num_purchases").change(function () {
                $("#purchase_form").submit();
            });
        });
    </script>
</body>
</html>
