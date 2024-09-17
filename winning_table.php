<?php
session_start();
include('config/database.php'); // Database connection
include('utilities.php'); // Utility functions for hash, etc.

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Function to check super_admin and authenticate user before accessing the form
function check_access_and_authenticate() {
    global $conn;

    // Check if the user is a super_admin
    if ($_SESSION['access_level'] !== 'super_admin') {
        header("Location: index.php");
        exit;
    }

    // If password is submitted for authentication
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['authenticate'])) {
        $agent_id = $_SESSION['agent_id'];
        $password = $_POST['password'];

        // Fetch the agent's hashed password from the database
        $stmt = $conn->prepare("SELECT agent_password FROM admin_access WHERE agent_id = :agent_id");
        $stmt->bindParam(':agent_id', $agent_id);
        $stmt->execute();
        $agent = $stmt->fetch(PDO::FETCH_ASSOC);

        // If the password is valid, proceed to the form; otherwise, logout the user
        if (!$agent || !password_verify($password, $agent['agent_password'])) {
            // If password is invalid, log out the user
            header("Location: logout.php");
            exit;
        }
    } else {
        // If no password is provided, show authentication form
        echo "
        <form method='POST' action=''>
            <div class='form-group'>
                <label for='password'>Enter Password for Authentication</label>
                <input type='password' class='form-control' name='password' placeholder='Enter your password' required>
            </div>
            <button type='submit' name='authenticate' class='btn btn-primary'>Authenticate</button>
        </form>
        ";
        exit; // Prevent the rest of the page from loading until authenticated
    }
}

// Function to handle form submission and check for duplicate records
function handle_winning_entry() {
    global $conn;

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_winning'])) {
        // Get the winning_game and other data
        $winning_number = $_POST['winning_number'];
        $winning_game = $_POST['winning_game'];
        $winning_period = 'Evening';
        $winning_date = $_POST['winning_date'];
        $created_by_agent = $_SESSION['agent_id'];

        // Validate winning number based on winning game
        if ($winning_game == '2-D' && !preg_match('/^\d{2}$/', $winning_number)) {
            echo "<script>alert('Invalid 2-D winning number! Please enter a valid 2-digit number.');</script>";
            return;
        } elseif ($winning_game == '3-D' && !preg_match('/^\d{3}$/', $winning_number)) {
            echo "<script>alert('Invalid 3-D winning number! Please enter a valid 3-digit number.');</script>";
            return;
        }

        // Check if there's already a record for the same date and game
        $stmt = $conn->prepare("SELECT * FROM winning_record WHERE winning_date = :winning_date AND winning_game = :winning_game");
        $stmt->bindParam(':winning_date', $winning_date);
        $stmt->bindParam(':winning_game', $winning_game);
        $stmt->execute();
        $existing_record = $stmt->fetch(PDO::FETCH_ASSOC);

        // If a duplicate record is found, check if it is locked
        if ($existing_record) {
            if ($existing_record['winning_listing'] == 1) {
                echo "<script>alert('Winning listing has already been generated. This record cannot be modified.');</script>";
                return;
            }
        }

        try {
            // Insert or update the record in the database
            if ($existing_record) {
                // Update the existing record
                $stmt = $conn->prepare("UPDATE winning_record SET winning_number = :winning_number, created_by_agent = :created_by_agent, updated_at = NOW() WHERE winning_date = :winning_date AND winning_game = :winning_game");
            } else {
                // Insert a new record
                $stmt = $conn->prepare("INSERT INTO winning_record (winning_number, winning_period, winning_game, winning_date, created_by_agent, created_at) 
                                        VALUES (:winning_number, :winning_period, :winning_game, :winning_date, :created_by_agent, NOW())");
            }

            $stmt->bindParam(':winning_number', $winning_number);
            $stmt->bindParam(':winning_period', $winning_period);
            $stmt->bindParam(':winning_game', $winning_game);
            $stmt->bindParam(':winning_date', $winning_date);
            $stmt->bindParam(':created_by_agent', $created_by_agent);
            $stmt->execute();

            echo "<div class='alert alert-success'>Winning number successfully recorded.</div>";
        } catch (PDOException $e) {
            echo "<div class='alert alert-danger'>Error inserting winning number: " . $e->getMessage() . "</div>";
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

                    <?php
                    // Check access and authenticate the user before showing the form
                    check_access_and_authenticate();
                    ?>

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

                        <button type="submit" name="submit_winning" class="btn btn-primary">Submit Winning Entry</button>
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

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>

</body>

</html>
