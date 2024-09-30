<?php
session_start();
include('config/database.php'); // Include your database connection

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

$agent_id = $_SESSION['agent_id'];

// Fetch agent data
$agent_query = $conn->prepare("SELECT * FROM admin_access WHERE agent_id = :agent_id");
$agent_query->bindParam(':agent_id', $agent_id);
$agent_query->execute();
$agent_data = $agent_query->fetch(PDO::FETCH_ASSOC);

// Fetch recent purchases (last 7 days with customer name)
$recent_purchases_query = $conn->prepare("
    SELECT p.purchase_no, p.purchase_category, p.purchase_amount, p.purchase_datetime, c.customer_name 
    FROM purchase_entries p 
    JOIN customer_details c ON p.customer_id = c.customer_id 
    WHERE p.agent_id = :agent_id AND p.purchase_datetime >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
    ORDER BY p.purchase_datetime DESC
");
$recent_purchases_query->bindParam(':agent_id', $agent_id);
$recent_purchases_query->execute();
$recent_purchases = $recent_purchases_query->fetchAll(PDO::FETCH_ASSOC);

// Fetch customers updated in the last 3 months linked with the agent
$customers_query = $conn->prepare("
    SELECT * FROM customer_details 
    WHERE agent_id = :agent_id AND updated_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
");
$customers_query->bindParam(':agent_id', $agent_id);
$customers_query->execute();
$customers = $customers_query->fetchAll(PDO::FETCH_ASSOC);

// Fetch agent leader for the org chart
$leader_query = $conn->prepare("SELECT agent_name FROM admin_access WHERE agent_id = :leader_id");
$leader_query->bindParam(':leader_id', $agent_data['agent_leader']);
$leader_query->execute();
$agent_leader = $leader_query->fetchColumn();

// Handle profile update form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agent_name'])) {
    $updated_name = $_POST['agent_name'];
    $updated_market = $_POST['agent_market'];
    $updated_credit_limit = $_POST['agent_credit_limit'];

    // Update the agent's details in the database
    $update_stmt = $conn->prepare("UPDATE admin_access SET agent_name = :agent_name, agent_market = :agent_market, agent_credit_limit = :agent_credit_limit WHERE agent_id = :agent_id");
    $update_stmt->bindParam(':agent_name', $updated_name);
    $update_stmt->bindParam(':agent_market', $updated_market);
    $update_stmt->bindParam(':agent_credit_limit', $updated_credit_limit);
    $update_stmt->bindParam(':agent_id', $agent_id);

    if ($update_stmt->execute()) {
        $message = "<div class='alert alert-success'>Profile updated successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Failed to update profile. Please try again.</div>";
    }

    // Refresh the agent data
    $agent_query->execute();
    $agent_data = $agent_query->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Profile</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <script src="vendor/chart.js/Chart.min.js"></script>
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <?php include('config/sidebar.php'); ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <?php include('config/topbar.php'); ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">Agent Profile</h1>

                    <!-- Show Message in Content Wrapper -->
                    <?php if (isset($message)) echo $message; ?>

                    <!-- Profile Overview -->
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    Overview
                                </div>
                                <div class="card-body">
                                    <p><strong>Agent Name:</strong> <?php echo $agent_data['agent_name']; ?></p>
                                    <p><strong>Market:</strong> <?php echo $agent_data['agent_market']; ?></p>
                                    <p><strong>Credit Limit:</strong> RM <?php echo number_format($agent_data['agent_credit_limit'], 2); ?></p>
                                    <p><strong>Profile Picture:</strong></p>
                                    <img src="img/team/team-1.jpg" alt="Profile Picture" class="img-fluid rounded-circle">
                                </div>
                            </div>
                        </div>

                        <!-- Edit Profile -->
                        <div class="col-lg-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    Edit Profile
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="profile_agent.php">
                                        <div class="form-group">
                                            <label for="agent_name">Agent Name</label>
                                            <input type="text" class="form-control" id="agent_name" name="agent_name" value="<?php echo $agent_data['agent_name']; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="agent_market">Market</label>
                                            <input type="text" class="form-control" id="agent_market" name="agent_market" value="<?php echo $agent_data['agent_market']; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="agent_credit_limit">Credit Limit (RM)</label>
                                            <input type="number" class="form-control" id="agent_credit_limit" name="agent_credit_limit" value="<?php echo $agent_data['agent_credit_limit']; ?>" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Update Profile</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Purchases Chart -->
                    <h2 class="h4 mb-4 text-gray-800">Purchase Chart (Last 7 Days)</h2>
                    <canvas id="purchaseChart"></canvas>

                    <!-- Linked Customers (Paginated Table) -->
                    <h2 class="h4 mb-4 text-gray-800">Linked Customers (Updated Last 3 Months)</h2>
                    <div class="table-responsive">
                        <table id="customerTable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Customer ID</th>
                                    <th>Customer Name</th>
                                    <th>Credit Limit (RM)</th>
                                    <th>Total Sales (RM)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $customer): ?>
                                    <tr>
                                        <td><?php echo $customer['customer_id']; ?></td>
                                        <td><?php echo $customer['customer_name']; ?></td>
                                        <td><?php echo number_format($customer['credit_limit'], 2); ?></td>
                                        <td><?php echo number_format($customer['total_sales'], 2); ?></td>
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
            <?php include('config/footer.php'); ?>
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <script>
        // Initialize DataTable for customers
        $(document).ready(function() {
            $('#customerTable').DataTable();
        });

        // Recent Purchases Chart
        var ctx = document.getElementById('purchaseChart').getContext('2d');
        var purchaseChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($recent_purchases, 'customer_name')); ?>,
                datasets: [{
                    label: 'Total Purchase (RM)',
                    data: <?php echo json_encode(array_column($recent_purchases, 'purchase_amount')); ?>,
                    backgroundColor: '#007bff'
                }]
            }
        });
    </script>
</body>
</html>