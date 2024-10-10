<?php
session_start();
include('config/database.php'); // Include database connection

// Check if the user is logged in and has a valid admin ID
if (!isset($_SESSION['admin']) || !isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

// Get logged-in admin ID
$admin_id = $_SESSION['admin_id'];

// Get the last agent ID from the database
$query = $conn->query("SELECT agent_id FROM admin_access ORDER BY agent_id DESC LIMIT 1");
$last_agent = $query->fetch(PDO::FETCH_ASSOC);

// Check if the result is valid and if the agent_id has the "AGENT" prefix
if ($last_agent && strpos($last_agent['agent_id'], 'AGENT') === 0) {
    // Extract the numeric part after the "AGENT" prefix
    $last_agent_id = intval(substr($last_agent['agent_id'], 5));
} else {
    $last_agent_id = 0; // Default to 0 if no valid agent_id is found
}

// Increment and format the new agent ID
$new_agent_id = "AGENT" . str_pad($last_agent_id + 1, 3, "0", STR_PAD_LEFT);

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $agent_name = $_POST['agent_name'];
    $credit_limit = isset($_POST['credit_limit']) ? $_POST['credit_limit'] : 500;
    $access_level = $_POST['access_level']; // Editable field (e.g., Admin, Agent)
    $created_at = date('Y-m-d H:i:s');

    if (!empty($agent_name)) {
        // Insert new agent into the database
        $stmt = $conn->prepare("INSERT INTO admin_access (agent_id, agent_name, created_by, credit_limit, access_level, created_at) 
                                VALUES (:agent_id, :agent_name, :created_by, :credit_limit, :access_level, :created_at)");
        $stmt->bindParam(':agent_id', $new_agent_id);
        $stmt->bindParam(':agent_name', $agent_name);
        $stmt->bindParam(':created_by', $admin_id);
        $stmt->bindParam(':credit_limit', $credit_limit);
        $stmt->bindParam(':access_level', $access_level);
        $stmt->bindParam(':created_at', $created_at);

        if ($stmt->execute()) {
            $success = "Agent created successfully with ID: $new_agent_id";
        } else {
            $error = "Error creating agent. Please try again.";
        }
    } else {
        $error = "Agent name is required!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Agent Registration</title>
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
                    <h1 class="h3 mb-4 text-gray-800">Register New Agent</h1>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php elseif ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label for="agent_name">Agent Name</label>
                            <input type="text" class="form-control" id="agent_name" name="agent_name" required>
                        </div>

                        <div class="form-group">
                            <label for="admin_id">Created By (Admin ID)</label>
                            <input type="text" class="form-control" id="admin_id" name="admin_id" value="<?php echo htmlspecialchars($admin_id); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label for="agent_id">Agent ID (Auto-Generated)</label>
                            <input type="text" class="form-control" id="agent_id" name="agent_id" value="<?php echo htmlspecialchars($new_agent_id); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label for="credit_limit">Credit Limit</label>
                            <input type="number" class="form-control" id="credit_limit" name="credit_limit" value="500" required>
                        </div>

                        <div class="form-group">
                            <label for="access_level">Access Level</label>
                            <select class="form-control" id="access_level" name="access_level" required>
                                <option value="Agent" selected>Agent</option>
                                <option value="Admin">Admin</option>
                                <!-- Add more options if needed -->
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">Create Agent</button>
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
