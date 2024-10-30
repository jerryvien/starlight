<?php
session_start();
include('config/database.php'); // Include your database connection
include('config/utilities.php');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set default timezone to ensure consistency
date_default_timezone_set('Asia/Singapore'); // GMT +8 timezone

// Generate form token if not already set or empty
if (!isset($_SESSION['form_token']) || empty($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32)); // Generate a new random token
}

// Debug: Display generated token (for testing)
echo "Generated Token: " . $_SESSION['form_token'] . "<br>";

// Get current hour and minute in HH:MM format
$current_time = date('H:i');

// Define start and cutoff times
$start_time = '00:00';
$cutoff_time = '23:55';

// Variable to determine if access is allowed
$access_allowed = !($current_time < $start_time || $current_time > $cutoff_time);

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Generate a unique serial number based on computer ID and current datetime
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
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if form token exists in POST and session
    if (!isset($_POST['form_token']) || !isset($_SESSION['form_token'])) {
        die("Form token is missing.");
    }

    // Check if the session token matches the form token
    if ($_POST['form_token'] !== $_SESSION['form_token']) {
        die("Invalid form submission. Token mismatch.");
    }

    // Invalidate the form token after successful submission to prevent reuse
    unset($_SESSION['form_token']);

    $customer_id = $_POST['customer_id'];
    $customer_name = '';
    foreach ($customers as $customer) {
        if ($customer['customer_id'] == $customer_id) {
            $customer_name = $customer['customer_name'];
            break;
        }
    }

    $purchase_entries = $_POST['purchase_no'];
    $purchase_category = $_POST['purchase_category'];
    $purchase_amount = $_POST['purchase_amount'];
    $purchase_date = $_POST['purchase_date'];

    // Set agent ID based on access level
    $agent_id_to_save = ($_SESSION['access_level'] === 'super_admin') ? $_POST['agent_id'] : $_SESSION['agent_id'];
    $agent_name = '';
    foreach ($agents as $agent) {
        if ($agent['agent_id'] == $agent_id_to_save) {
            $agent_name = $agent['agent_name'];
            break;
        }
    }

    $purchaseDetails = [];
    $subtotal = 0;

    // Insert each purchase entry into the database
    for ($i = 0; $i < count($purchase_entries); $i++) {
        $total_price = 0;

        // Calculate total price based on category
        if ($purchase_category[$i] === 'Box') {
            $permutation_factor = calculatePermutationFactor($purchase_entries[$i]);
            $total_price = $purchase_amount[$i] * $permutation_factor;
        } else if ($purchase_category[$i] === 'Straight') {
            $total_price = $purchase_amount[$i]; // Straight amount stays the same
        }

        $subtotal += $total_price;
        $purchaseDetails[] = [
            'number' => $purchase_entries[$i], 
            'category' => $purchase_category[$i],
            'amount' => $total_price,
            'date' => $purchase_date[$i]
        ];

        $sql = "INSERT INTO purchase_entries (customer_id, agent_id, purchase_no, purchase_category, purchase_amount, purchase_datetime, serial_number) 
                VALUES (:customer_id, :agent_id, :purchase_no, :purchase_category, :purchase_amount, :purchase_datetime, :serial_number)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->bindParam(':agent_id', $agent_id_to_save);
        $stmt->bindParam(':purchase_no', $purchase_entries[$i]);
        $stmt->bindParam(':purchase_category', $purchase_category[$i]);
        $stmt->bindParam(':purchase_amount', $total_price);
        $stmt->bindParam(':purchase_datetime', $purchase_date[$i]);
        $stmt->bindParam(':serial_number', $serial_number);
        $stmt->execute();

        // Update the updated_at field in customer_details table
        try {
            // Get the current purchase_history_count for the customer
            $getCountSQL = "SELECT purchase_history_count FROM customer_details WHERE customer_id = :customer_id";
            $countStmt = $conn->prepare($getCountSQL);
            $countStmt->bindParam(':customer_id', $customer_id);
            $countStmt->execute();
        
            $result = $countStmt->fetch(PDO::FETCH_ASSOC);
        
            if ($result) {
                $currentCount = $result['purchase_history_count'];
        
                // Increment the count by 1
                $newCount = $currentCount + 1;
        
                // Update the purchase_history_count and updated_at fields in customer_details table
                $updateCustomerSQL = "UPDATE customer_details 
                                      SET updated_at = NOW(), 
                                          purchase_history_count = :newCount 
                                      WHERE customer_id = :customer_id";
                $updateStmt = $conn->prepare($updateCustomerSQL);
                $updateStmt->bindParam(':newCount', $newCount);
                $updateStmt->bindParam(':customer_id', $customer_id);
                $updateStmt->execute();
            }
        } catch (PDOException $e) {
            echo "Error updating customer details: " . $e->getMessage();
        }
    }

    // Store the success message in session
    $_SESSION['success_message'] = "Purchase entries added successfully with serial number: $serial_number";

    // Call the generateReceiptPopup function to show the receipt
    $receiptHTML = generateReceiptPopup($customer_name, $purchaseDetails, $subtotal, $agent_name, $serial_number);

    // Redirect to the same page to avoid form resubmission
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Function to calculate permutation factor for "Box"
function calculatePermutationFactor($purchase_no) {
    $unique_digits = count(array_unique(str_split($purchase_no)));
    switch ($unique_digits) {
        case 3: return 6; // 123 -> 6 combinations
        case 2: return 3; // 223 -> 3 combinations
        case 1: return 1; // 111 -> 1 combination
        default: return 1;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Purchase Record Entry</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <script src="vendor/jquery/jquery.min.js"></script>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include('config/sidebar.php'); ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include('config/topbar.php'); ?>

                <?php if (!$access_allowed): ?>
                    <div class="alert alert-danger">
                        <strong>Access Denied!</strong> System is closed for transactions.
                    </div>
                <?php else: ?>
                    <div class="container-fluid">
                        <h1 class="h3 mb-4 text-gray-800">Purchase Record Entry</h1>

                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success">
                                <?php echo $_SESSION['success_message']; ?>
                                <?php unset($_SESSION['success_message']); ?>
                            </div>
                        <?php endif; ?>

                        <form id="purchaseForm" method="POST" action="">
                            <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">

                            <!-- Customer Selection -->
                            <div class="form-group">
                                <label for="customer_id">Select Customer</label>
                                <select id="customer_id" name="customer_id" class="form-control" required>
                                    <option value="">Choose Customer</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?php echo $customer['customer_id']; ?>"><?php echo $customer['customer_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
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
                            <!-- Add your dynamic purchase entries code here -->

                            <button type="submit" id="submitBtn" class="btn btn-success mt-3">Submit Purchase Entry</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <?php include('config/footer.php'); ?>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.getElementById("purchaseForm");
            const submitBtn = document.getElementById("submitBtn");

            if (form && submitBtn) {
                form.addEventListener("submit", function(e) {
                    submitBtn.disabled = true; // Disable submit button
                    submitBtn.textContent = "Processing...";
                });
            }
        });
    </script>
</body>
</html>
