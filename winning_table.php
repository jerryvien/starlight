<?php
session_start();
include('config/database.php'); // Database connection
include('utilities.php'); // Utility functions for hash, etc.

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Function to handle form submission and check for duplicate records
function handle_winning_entry() {
    global $conn;

    // Check if the user is logged in and is super_admin
    if ($_SESSION['access_level'] !== 'super_admin') {
        echo "<script>$(document).ready(function(){ $('#errorModal').modal('show'); });</script>";
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
            echo "<script>$(document).ready(function(){ $('#authFailedModal').modal('show'); });</script>";
            return;
        }

        // Get the winning_game and other data
        $winning_number = $_POST['winning_number'];
        $winning_game = $_POST['winning_game'];
        $winning_period = 'Evening';
        $winning_date = $_POST['winning_date'];
        $created_by_agent = $agent_id;

        // Validate winning number based on winning game
        if ($winning_game == '2-D' && !preg_match('/^\d{2}$/', $winning_number)) {
            echo "<script>$(document).ready(function(){ $('#invalidNumberModal').modal('show'); });</script>";
            return;
        } elseif ($winning_game == '3-D' && !preg_match('/^\d{3}$/', $winning_number)) {
            echo "<script>$(document).ready(function(){ $('#invalidNumberModal').modal('show'); });</script>";
            return;
        }

        // Check if there's already a record for the same date and game
        $stmt = $conn->prepare("SELECT * FROM winning_record WHERE winning_date = :winning_date AND winning_game = :winning_game");
        $stmt->bindParam(':winning_date', $winning_date);
        $stmt->bindParam(':winning_game', $winning_game);
        $stmt->execute();
        $existing_record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_record) {
            // Check if the record has been locked with winning_listing = 1
            if ($existing_record['winning_listing'] == 1) {
                echo "<script>$(document).ready(function(){ $('#lockedRecordModal').modal('show'); });</script>";
                return;
            }

            // If duplicate found, show modal for modification
            echo "<script>
                    $(document).ready(function() {
                        $('#existingWinningNumber').val('{$existing_record['winning_number']}');
                        $('#existingWinningGame').val('{$existing_record['winning_game']}');
                        $('#existingWinningDate').val('{$existing_record['winning_date']}');
                        $('#duplicateRecordModal').modal('show');
                    });
                  </script>";
            return;
        }

        // If no existing record, proceed to insert after confirmation
        if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
            // Generate agent_hashed_secretkey
            $agent_hashed_secretkey = hash('sha256', $password);

            // Insert data into the winning_record table
            $stmt = $conn->prepare("INSERT INTO winning_record (winning_number, winning_period, winning_game, winning_date, created_by_agent, agent_hashed_secretkey, created_at) 
                                    VALUES (:winning_number, :winning_period, :winning_game, :winning_date, :created_by_agent, :agent_hashed_secretkey, NOW())");

            $stmt->bindParam(':winning_number', $winning_number);
            $stmt->bindParam(':winning_period', $winning_period);
            $stmt->bindParam(':winning_game', $winning_game);
            $stmt->bindParam(':winning_date', $winning_date);
            $stmt->bindParam(':created_by_agent', $created_by_agent);
            $stmt->bindParam(':agent_hashed_secretkey', $agent_hashed_secretkey);
            $stmt->execute();

            echo "<script>$(document).ready(function(){ $('#successModal').modal('show'); });</script>";
        } else {
            // If the user has not confirmed, show the confirmation popup
            echo "<script>$(document).ready(function(){ $('#confirmationModal').modal('show'); });</script>";
        }
    }
}
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

                        <!-- Winning Game -->
                        <div class="form-group">
                            <label for="winning_game">Winning Game</label>
                            <select name="winning_game" class="form-control" required>
                                <option value="2-D">2-D</option>
                                <option value="3-D">3-D</option>
                            </select>
                        </div>

                        <!-- Winning Date -->
                        <div class="form-group">
                            <label for="winning_date">Winning Date</label>
                            <input type="date" class="form-control" name="winning_date" value="<?php echo date('Y-m-d'); ?>" required>
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
                    handle_winning_entry();
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

    <!-- Modal Templates -->
    <!-- Duplicate Record Modal (for modification) -->
    <div class="modal fade" id="duplicateRecordModal" tabindex="-1" role="dialog" aria-labelledby="duplicateRecordModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="duplicateRecordModalLabel">Modify Existing Record</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="modify" value="yes">
                        <div class="form-group">
                            <label for="existingWinningNumber">Winning Number</label>
                            <input type="text" class="form-control" id="existingWinningNumber" name="winning_number" required>
                        </div>
                        <div class="form-group">
                            <label for="existingWinningGame">Winning Game</label>
                            <select id="existingWinningGame" name="winning_game" class="form-control" required>
                                <option value="2-D">2-D</option>
                                <option value="3-D">3-D</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="existingWinningDate">Winning Date</label>
                            <input type="date" class="form-control" id="existingWinningDate" name="winning_date" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Re-enter Password for Authentication</label>
                            <input type="password" class="form-control" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Submit Changes</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Other modals (Success, Invalid Number, Locked Record, etc.) -->

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>

</body>
</html>
