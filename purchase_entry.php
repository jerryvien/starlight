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

// Fetch default customer list for the agent
$customers = [];
try {
    $stmt = $conn->prepare("SELECT * FROM customer_details WHERE agent_id = :agent_id");
    $stmt->bindParam(':agent_id', $agent_id);
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching customers: " . $e->getMessage();
}

// Handle customer selection and show customer info
$selected_customer = null;
if (isset($_POST['select_customer'])) {
    $customer_id = $_POST['customer_id'];
    try {
        $stmt = $conn->prepare("SELECT * FROM customer_details WHERE customer_id = :customer_id AND agent_id = :agent_id");
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->bindParam(':agent_id', $agent_id);
        $stmt->execute();
        $selected_customer = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Error fetching customer details: " . $e->getMessage();
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

                    <!-- Customer Selection -->
                    <form method="POST">
                        <div class="form-group">
                            <label for="customer_id">Select Customer</label>
                            <select name="customer_id" id="customer_id" class="form-control" required>
                                <option value="">Choose a customer</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?php echo $customer['customer_id']; ?>" <?php echo (isset($selected_customer) && $selected_customer['customer_id'] == $customer['customer_id']) ? 'selected' : ''; ?>>
                                        <?php echo $customer['customer_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="select_customer" class="btn btn-primary mt-2">Select Customer</button>
                        </div>
                    </form>

                    <!-- Display selected customer information -->
                    <?php if ($selected_customer): ?>
                        <div class="form-group">
                            <h5>Customer Information:</h5>
                            <p><strong>Name:</strong> <?php echo $selected_customer['customer_name']; ?></p>
                            <p><strong>Credit Limit:</strong> RM<?php echo $selected_customer['credit_limit']; ?></p>
                            <p><strong>VIP Status:</strong> <?php echo $selected_customer['vip_status']; ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Purchase Entry Form -->
                    <form method="POST" id="purchase_form">
                        <input type="hidden" name="customer_id" value="<?php echo isset($selected_customer) ? $selected_customer['customer_id'] : ''; ?>" required>

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

                        <button type="submit" name="confirm_purchase" class="btn btn-primary mt-4">Submit Purchase Entries</button>
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

    <!-- Bootstrap core JavaScript -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-ui/jquery-ui.min.js"></script> <!-- Datepicker -->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>

    <script>
        // Datepicker initialization
        $(function() {
            $('.purchase_datepicker').datepicker({
                dateFormat: 'yy-mm-dd'
            });
        });

        // Update dynamic purchase fields based on selection
        $('#num_purchases').change(function() {
            $('#purchase_form').submit();
        });
    </script>

</body>
</html>
