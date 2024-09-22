<?php
session_start();
include('config/database.php'); // Include your database connection

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

$agent_id = $_SESSION['agent_id'];

// Fetch agent details
$agent_query = $conn->prepare("SELECT * FROM admin_access WHERE agent_id = :agent_id");
$agent_query->bindParam(':agent_id', $agent_id);
$agent_query->execute();
$agent = $agent_query->fetch(PDO::FETCH_ASSOC);

// Handle form submission for updating agent details
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $agent_name = $_POST['agent_name'];
    $agent_market = $_POST['agent_market'];
    $agent_credit_limit = $_POST['agent_credit_limit'];

    // Update agent profile
    $update_query = $conn->prepare("UPDATE admin_access SET agent_name = :agent_name, agent_market = :agent_market, agent_credit_limit = :agent_credit_limit WHERE agent_id = :agent_id");
    $update_query->bindParam(':agent_name', $agent_name);
    $update_query->bindParam(':agent_market', $agent_market);
    $update_query->bindParam(':agent_credit_limit', $agent_credit_limit);
    $update_query->bindParam(':agent_id', $agent_id);
    
    if ($update_query->execute()) {
        $message = "Profile updated successfully.";
    } else {
        $message = "Failed to update profile. Please try again.";
    }
}

// Fetch recent purchase activities (last 7 days)
$recent_purchases_query = $conn->prepare("SELECT * FROM purchase_entries WHERE agent_id = :agent_id AND purchase_datetime >= NOW() - INTERVAL 7 DAY ORDER BY purchase_datetime DESC");
$recent_purchases_query->bindParam(':agent_id', $agent_id);
$recent_purchases_query->execute();
$recent_purchases = $recent_purchases_query->fetchAll(PDO::FETCH_ASSOC);

// Fetch customers linked to the agent
$customer_query = $conn->prepare("SELECT * FROM customer_details WHERE agent_id = :agent_id");
$customer_query->bindParam(':agent_id', $agent_id);
$customer_query->execute();
$customers = $customer_query->fetchAll(PDO::FETCH_ASSOC);

// Fetch agent leader
$leader_query = $conn->prepare("SELECT agent_name FROM admin_access WHERE agent_login_id = :agent_leader");
$leader_query->bindParam(':agent_leader', $agent['agent_leader']);
$leader_query->execute();
$leader = $leader_query->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Profile</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include('config/sidebar.php'); ?> <!-- Include sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <?php include('config/topbar.php'); ?> <!-- Include topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Agent Profile</h1>

                    <!-- Show messages in content wrapper -->
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <!-- Agent Profile Form -->
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="agent_name">Agent Name</label>
                            <input type="text" class="form-control" name="agent_name" value="<?php echo $agent['agent_name']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="agent_market">Agent Market</label>
                            <input type="text" class="form-control" name="agent_market" value="<?php echo $agent['agent_market']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="agent_credit_limit">Agent Credit Limit</label>
                            <input type="number" class="form-control" name="agent_credit_limit" value="<?php echo $agent['agent_credit_limit']; ?>" required>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </form>

                    <hr>

                    <!-- Recent Purchase Activities -->
                    <h3>Recent Purchases (Last 7 Days)</h3>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Purchase Number</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Purchase Date</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_purchases as $purchase): ?>
                                <tr>
                                    <td><?php echo $purchase['purchase_no']; ?></td>
                                    <td><?php echo $purchase['purchase_category']; ?></td>
                                    <td><?php echo number_format($purchase['purchase_amount'], 2); ?> RM</td>
                                    <td><?php echo $purchase['purchase_datetime']; ?></td>
                                    <td><?php echo $purchase['result']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <hr>

                    <!-- Customers Linked to Agent -->
                    <h3>Customers Linked to Your Agent ID</h3>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Customer ID</th>
                                <th>Customer Name</th>
                                <th>Credit Limit</th>
                                <th>Total Sales</th>
                                <th>VIP Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><?php echo $customer['customer_id']; ?></td>
                                    <td><?php echo $customer['customer_name']; ?></td>
                                    <td><?php echo number_format($customer['credit_limit'], 2); ?> RM</td>
                                    <td><?php echo number_format($customer['total_sales'], 2); ?> RM</td>
                                    <td><?php echo $customer['vip_status']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <hr>

                    <!-- Org Chart (Agent Leader) 
                    <h3>Your Leader</h3>
                    <div class="card">
                        <div class="card-body">
                            <p><strong>Leader: </strong><?php echo $leader['agent_name']; ?></p>
                        </div>
                    </div>-->
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include('config/footer.php'); ?> <!-- Include footer -->
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
