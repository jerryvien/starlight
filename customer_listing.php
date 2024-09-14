<?php
session_start();
include('config/database.php'); // Include your database connection

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Fetch the access level from session
$access_level = $_SESSION['access_level']; // Assuming 'access_level' is stored in session
$agent_id = $_SESSION['agent_id'];

$customers = [];

try {
    // Fetch all customers if access_level is 'Admin', or customers related to the agent if access_level is 'Agent'
    if ($access_level === 'Admin') {
        $query = "SELECT * FROM customer_details ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
    } else {
        // For agent-level users, filter by agent_id
        $query = "SELECT * FROM customer_details WHERE agent_id = :agent_id ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':agent_id', $agent_id);
    }

    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching customers: " . $e->getMessage();
}

// Handle update submission (when edit button is clicked)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_customer'])) {
    $customer_id = $_POST['customer_id'];
    $customer_name = $_POST['customer_name'];
    $credit_limit = $_POST['credit_limit'];
    $vip_status = $_POST['vip_status'];

    try {
        $updateQuery = "UPDATE customer_details SET customer_name = :customer_name, credit_limit = :credit_limit, vip_status = :vip_status WHERE customer_id = :customer_id";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bindParam(':customer_name', $customer_name);
        $stmt->bindParam(':credit_limit', $credit_limit);
        $stmt->bindParam(':vip_status', $vip_status);
        $stmt->bindParam(':customer_id', $customer_id);
        
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>Customer details updated successfully.</div>";
        } else {
            echo "<div class='alert alert-danger'>Failed to update customer details.</div>";
        }
    } catch (PDOException $e) {
        echo "Error updating customer: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Customer Listing</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include('sidebar.php'); ?> <!-- Reuse your standard sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <?php include('topbar.php'); ?> <!-- Reuse your standard topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Customer Listing</h1>

                    <!-- Customer Listing Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Customer ID</th>
                                    <th>Customer Name</th>
                                    <th>Agent ID</th>
                                    <th>Credit Limit (RM)</th>
                                    <th>VIP Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $customer): ?>
                                    <tr>
                                        <form method="POST" action="customer_listing.php">
                                            <input type="hidden" name="customer_id" value="<?php echo $customer['customer_id']; ?>">
                                            <td><?php echo $customer['customer_id']; ?></td>
                                            <td><input type="text" name="customer_name" value="<?php echo $customer['customer_name']; ?>" class="form-control" <?php if ($access_level !== 'Admin') echo 'readonly'; ?>></td>
                                            <td><?php echo $customer['agent_id']; ?></td>
                                            <td><input type="number" name="credit_limit" value="<?php echo $customer['credit_limit']; ?>" class="form-control"></td>
                                            <td>
                                                <select name="vip_status" class="form-control">
                                                    <option value="Normal" <?php echo $customer['vip_status'] == 'Normal' ? 'selected' : ''; ?>>Normal</option>
                                                    <option value="VIP" <?php echo $customer['vip_status'] == 'VIP' ? 'selected' : ''; ?>>VIP</option>
                                                </select>
                                            </td>
                                            <td>
                                                <button type="submit" name="edit_customer" class="btn btn-primary">Save</button>
                                            </td>
                                        </form>
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
            <?php include('footer.php'); ?> <!-- Reuse your standard footer -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>

</body>
</html>
