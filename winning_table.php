<?php
session_start();
include('config/database.php'); // Database connection
include('utilities.php'); // Include utility functions like for generating hashes, serial numbers, etc.


// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Function to validate and store winning entry
function enter_winning_entry() {
    global $conn;

    // Check if the user is logged in and is super_admin
    if ($_SESSION['access_level'] !== 'super_admin') {
        echo "<div class='alert alert-danger'>You must be a super_admin to access this feature. Please get approval from a super_admin.</div>";
        return;
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Re-authenticate the user by checking password
        $agent_id = $_SESSION['agent_id'];
        $password = $_POST['password'];

        // Fetch the agent's hashed password from the database
        $stmt = $conn->prepare("SELECT agent_password FROM admin_access WHERE agent_id = :agent_id");
        $stmt->bindParam(':agent_id', $agent_id);
        $stmt->execute();
        $agent = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$agent || !password_verify($password, $agent['agent_password'])) {
            echo "<div class='alert alert-danger'>Authentication failed. Please enter the correct password.</div>";
            return;
        }

        // Winning number validation (must be 2 or 3 digits)
        $winning_number = $_POST['winning_number'];
        if (!preg_match('/^\d{2,3}$/', $winning_number)) {
            echo "<div class='alert alert-danger'>Invalid winning number. It must be 2 or 3 digits.</div>";
            return;
        }

        // Set default values for winning period and created_by_agent
        $winning_period = 'Evening';
        $winning_date = $_POST['winning_date'];
        $winning_total_payout = $_POST['winning_total_payout'];
        $created_by_agent = $agent_id;

        // Generate a hashed secret key for the agent
        $agent_hashed_secretkey = hash('sha256', $password);

        // Show a confirmation pop-up
        if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
            // Insert data into the winning_record table
            $stmt = $conn->prepare("INSERT INTO winning_record (winning_number, winning_period, winning_date, winning_total_payout, created_by_agent, agent_hashed_secretkey, created_at) 
                                    VALUES (:winning_number, :winning_period, :winning_date, :winning_total_payout, :created_by_agent, :agent_hashed_secretkey, NOW())");

            $stmt->bindParam(':winning_number', $winning_number);
            $stmt->bindParam(':winning_period', $winning_period);
            $stmt->bindParam(':winning_date', $winning_date);
            $stmt->bindParam(':winning_total_payout', $winning_total_payout);
            $stmt->bindParam(':created_by_agent', $created_by_agent);
            $stmt->bindParam(':agent_hashed_secretkey', $agent_hashed_secretkey);
            $stmt->execute();

            echo "<div class='alert alert-success'>Winning entry for $winning_number has been added successfully!</div>";
        } else {
            // If the user has not confirmed, show the confirmation popup
            echo "<div class='alert alert-info'>
                    Are you sure you want to add the winning number: $winning_number for date: $winning_date?<br>
                    <form method='POST' action=''>
                        <input type='hidden' name='winning_number' value='$winning_number'>
                        <input type='hidden' name='winning_date' value='$winning_date'>
                        <input type='hidden' name='winning_total_payout' value='$winning_total_payout'>
                        <input type='hidden' name='password' value='$password'>
                        <button type='submit' name='confirm' value='yes' class='btn btn-success'>Yes</button>
                        <button type='submit' name='confirm' value='no' class='btn btn-danger'>No</button>
                    </form>
                  </div>";
        }
    }
}

// Example HTML form for entering winning data with sidebar, topbar, and footer
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Winning Number</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include('sidebar.php'); ?> <!-- Include sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <?php include('topbar.php'); ?> <!-- Include topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <h2 class="h3 mb-4 text-gray-800">Enter Winning Number</h2>

                    <form method="POST" action="">
                        <!-- Winning Number -->
                        <div class="form-group">
                            <label for="winning_number">Winning Number</label>
                            <input type="text" class="form-control" name="winning_number" placeholder="Enter 2 or 3-digit number" required>
                        </div>

                        <!-- Winning Date -->
                        <div class="form-group">
                            <label for="winning_date">Winning Date</label>
                            <input type="date" class="form-control" name="winning_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <!-- Total Payout -->
                        <div class="form-group">
                            <label for="winning_total_payout">Total Payout (RM)</label>
                            <input type="number" class="form-control" name="winning_total_payout" placeholder="Enter total payout" required>
                        </div>

                        <!-- Re-enter Password for Authentication -->
                        <div class="form-group">
                            <label for="password">Enter Password for Authentication</label>
                            <input type="password" class="form-control" name="password" placeholder="Enter your password" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>

                    <?php
                    // Call the function to handle form submission
                    enter_winning_entry();
                    ?>
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include('footer.php'); ?> <!-- Include footer -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>
</body>
</html>
