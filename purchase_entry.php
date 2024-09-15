<?php
session_start();
include('config/database.php');
include('utilities.php'); // Include utilities for serial number and other helper functions

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Generate serial number using function from utilities.php
$serial_number = generateSerialNumber();

// Fetch customer data for search filter
$query = ($_SESSION['access_level'] === 'super_admin') ? 
    "SELECT customer_id, customer_name FROM customer_details" : 
    "SELECT customer_id, customer_name FROM customer_details WHERE agent_id = :agent_id";
$stmt = $conn->prepare($query);
if ($_SESSION['access_level'] !== 'super_admin') {
    $stmt->bindParam(':agent_id', $_SESSION['agent_id']);
}
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch agents for super_admin dropdown (optional for super_admin only)
$agents = [];
if ($_SESSION['access_level'] === 'super_admin') {
    $stmt = $conn->prepare("SELECT agent_id, agent_name FROM admin_access");
    $stmt->execute();
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm'])) {
    $customer_id = $_POST['customer_id'];
    $purchase_entries = $_POST['purchase_no'];
    $purchase_category = $_POST['purchase_category'];
    $purchase_amount = $_POST['purchase_amount'];
    $purchase_date = $_POST['purchase_date'];
    $agent_id_to_save = ($_SESSION['access_level'] === 'super_admin') ? $_POST['agent_id'] : $_SESSION['agent_id'];

    // Insert each purchase entry into the database
    for ($i = 0; $i < count($purchase_entries); $i++) {
        $total_amount = calculateTotal($purchase_entries[$i], $purchase_category[$i], $purchase_amount[$i]);
        $sql = "INSERT INTO purchase_entries (customer_id, agent_id, purchase_no, purchase_category, purchase_amount, purchase_datetime, serial_number) 
                VALUES (:customer_id, :agent_id, :purchase_no, :purchase_category, :purchase_amount, :purchase_datetime, :serial_number)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->bindParam(':agent_id', $agent_id_to_save);
        $stmt->bindParam(':purchase_no', $purchase_entries[$i]);
        $stmt->bindParam(':purchase_category', $purchase_category[$i]);
        $stmt->bindParam(':purchase_amount', $total_amount); // Save the calculated total
        $stmt->bindParam(':purchase_datetime', $purchase_date[$i]);
        $stmt->bindParam(':serial_number', $serial_number);
        $stmt->execute();
    }

    $success_message = "Purchase entries added successfully with serial number: $serial_number";
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
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="js/validation.js"></script> <!-- Include validation JavaScript -->
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include('sidebar.php'); ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include('topbar.php'); ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Purchase Entry</h1>

                    <!-- Success message -->
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>

                    <!-- Display Serial Number -->
                    <div class="form-group">
                        <label for="serial_number">Serial Number</label>
                        <input type="text" class="form-control" id="serial_number" value="<?php echo $serial_number; ?>" readonly>
                    </div>

                    <!-- Customer and Agent Details -->
                    <form method="POST" action="purchase_entry.php">
                        <div class="form-group">
                            <label for="customer_search">Search Customer</label>
                            <input type="text" class="form-control" id="customer_search" placeholder="Start typing to search..." onkeyup="filterCustomers()">
                            <ul id="customer_list" class="list-group mt-2"></ul>
                        </div>

                        <!-- Agent Dropdown (only for super_admin) -->
                        <?php if ($_SESSION['access_level'] === 'super_admin'): ?>
                        <div class="form-group">
                            <label for="agent_dropdown">Agent</label>
                            <select id="agent_dropdown" class="form-control" name="agent_id" required>
                                <option value="">Select Agent</option>
                                <?php foreach ($agents as $agent): ?>
                                    <option value="<?php echo $agent['agent_id']; ?>"><?php echo $agent['agent_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <!-- Dynamic Purchase Entries -->
                        <div id="purchase_entries_wrapper">
                            <!-- Initially display 1 row by default -->
                            <div class="form-group row">
                                <div class="col-md-3">
                                    <label for="purchase_no_0">Purchase Number</label>
                                    <input type="text" class="form-control" name="purchase_no[]" required oninput="validatePurchaseNumber(this)">
                                </div>
                                <div class="col-md-2">
                                    <label for="purchase_category_0">Category</label>
                                    <select class="form-control" name="purchase_category[]">
                                        <option value="Box">Box</option>
                                        <option value="Straight">Straight</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="purchase_amount_0">Amount</label>
                                    <input type="number" class="form-control" name="purchase_amount[]" required>
                                </div>
                                <div class="col-md-2">
                                    <label for="purchase_total_0">Total</label>
                                    <input type="text" class="form-control" name="purchase_total[]" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label for="purchase_date_0">Purchase Date</label>
                                    <input type="date" class="form-control" name="purchase_date[]" required>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="button" class="btn btn-warning" onclick="showConfirmation()">Preview and Confirm</button>
                    </form>
                </div>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>

    <script src="js/purchase_entry.js"></script> <!-- Include the validation and purchase entry script -->
</body>
</html>