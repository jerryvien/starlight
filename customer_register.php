<?php
session_start();
include('config/database.php'); // Include database connection

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Get logged-in agent ID
$agent_id = $_SESSION['agent_id'];

// Get the last customer ID from the database
$query = $conn->query("SELECT customer_id FROM customer_details ORDER BY customer_id DESC LIMIT 1");
$last_customer = $query->fetch(PDO::FETCH_ASSOC);

// Check if the result is valid and if the customer_id has the "CUST" prefix
if ($last_customer && strpos($last_customer['customer_id'], 'CUST') === 0) {
    // Extract the numeric part after the "CUST" prefix
    $last_customer_id = intval(substr($last_customer['customer_id'], 4));
} else {
    $last_customer_id = 0; // Default to 0 if no valid customer_id is found
}

// Increment and format the new customer ID
$new_customer_id = "CUST" . str_pad($last_customer_id + 1, 3, "0", STR_PAD_LEFT);


$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_name = $_POST['customer_name'];
    $credit_limit = isset($_POST['credit_limit']) ? $_POST['credit_limit'] : 500;
    $vip_status = $_POST['vip_status']; // Editable field
    $created_at = date('Y-m-d H:i:s');

    if (!empty($customer_name)) {
        // Insert new customer into the database
        $stmt = $conn->prepare("INSERT INTO customer_details (customer_id, customer_name, agent_id, credit_limit, vip_status, created_at) 
                                VALUES (:customer_id, :customer_name, :agent_id, :credit_limit, :vip_status, :created_at)");
        $stmt->bindParam(':customer_id', $new_customer_id);
        $stmt->bindParam(':customer_name', $customer_name);
        $stmt->bindParam(':agent_id', $agent_id);
        $stmt->bindParam(':credit_limit', $credit_limit);
        $stmt->bindParam(':vip_status', $vip_status);
        $stmt->bindParam(':created_at', $created_at);

        if ($stmt->execute()) {
            $success = "Customer created successfully with ID: $new_customer_id";
        } else {
            $error = "Error creating customer. Please try again.";
        }
    } else {
        $error = "Customer name is required!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Customer Registration</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include('config/sidebar.php'); ?> <!-- Reuse your standard sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <?php include('config/topbar.php'); ?> <!-- Reuse your standard topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Register New Customer</h1>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php elseif ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label for="customer_name">Customer Name</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                        </div>

                        <div class="form-group">
                            <label for="agent_id">Agent ID (Auto-Filled)</label>
                            <input type="text" class="form-control" id="agent_id" name="agent_id" value="<?php echo $agent_id; ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label for="customer_id">Customer ID (Auto-Generated)</label>
                            <input type="text" class="form-control" id="customer_id" name="customer_id" value="<?php echo $new_customer_id; ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label for="credit_limit">Credit Limit</label>
                            <input type="number" class="form-control" id="credit_limit" name="credit_limit" value="500" required>
                        </div>

                        <div class="form-group">
                            <label for="vip_status">VIP Status</label>
                            <select class="form-control" id="vip_status" name="vip_status" required>
                                <option value="Normal" selected>Normal</option>
                                <option value="VIP">VIP</option>
                                <option value="Premium">Premium</option> <!-- Add more options if needed -->
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">Create Customer</button>
                    </form>

                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include('config/footer.php'); ?> <!-- Reuse your standard footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->
</body>
</html>
