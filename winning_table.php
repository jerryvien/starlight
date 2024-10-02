<?php
session_start();
include('config/database.php'); // Include your database connection

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Ensure the user is a super_admin
if ($_SESSION['access_level'] !== 'super_admin') {
    echo "<script>alert('You must be a super admin to access this page.'); window.location.href='dashboard.php';</script>";
    exit;
}

// Initialize variables for messages and form fields
$message = '';
$error = '';
$existingRecord = null;
$winning_number = '';
$winning_game = '';
$winning_date = date('Y-m-d'); // Default value for winning date

// Handle password authentication
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['authenticate'])) {
    $password = $_POST['password'];
    $agent_id = $_SESSION['agent_id'];

    // Fetch the agent's password from the database
    $stmt = $conn->prepare("SELECT agent_password FROM admin_access WHERE agent_id = :agent_id");
    $stmt->bindParam(':agent_id', $agent_id);
    $stmt->execute();
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify the password
    if ($agent && password_verify($password, $agent['agent_password'])) {
        $_SESSION['authenticated'] = true; // Set an authenticated flag
    } else {
        $error = 'Invalid password! Please try again.';
    }
}

// Handle form submission to insert or modify the winning number
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['submit_winning']) || isset($_POST['modify_winning']))) {
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        $error = 'Please authenticate to access this function.';
    } else {
        $winning_number = $_POST['winning_number'];
        $winning_game = $_POST['winning_game'];
        $winning_date = $_POST['winning_date'];
        $created_by_agent = $_SESSION['agent_id'];
        $hashed_secret_key = password_hash($winning_number, PASSWORD_BCRYPT); // Example hash key

        // Validate the winning number based on game type (2-D or 3-D)
        if ($winning_game == '2-D' && !preg_match('/^\d{2}$/', $winning_number)) {
            $error = 'Invalid 2-D winning number! It must be a 2-digit number.';
        } elseif ($winning_game == '3-D' && !preg_match('/^\d{3}$/', $winning_number)) {
            $error = 'Invalid 3-D winning number! It must be a 3-digit number.';
        } else {
            // Check if a record with the same date and winning game exists
            $checkQuery = "SELECT * FROM winning_record WHERE winning_date = :winning_date AND winning_game = :winning_game";
            $stmt = $conn->prepare($checkQuery);
            $stmt->bindParam(':winning_date', $winning_date);
            $stmt->bindParam(':winning_game', $winning_game);
            $stmt->execute();
            $existingRecord = $stmt->fetch(PDO::FETCH_ASSOC);

            // Debugging: Check if a record was found
            if ($existingRecord) {
                $message = "Existing record found for $winning_game on $winning_date. Ready to modify.";
            } else {
                $message = "No existing record found for $winning_game on $winning_date.";
            }

            if ($existingRecord && isset($_POST['modify_winning'])) {
                // If modifying an existing record, update it in the database
                try {
                    $stmt = $conn->prepare("UPDATE winning_record 
                                            SET winning_number = :winning_number, 
                                                winning_game = :winning_game, 
                                                winning_date = :winning_date 
                                            WHERE id = :id");
                    $stmt->bindParam(':winning_number', $winning_number);
                    $stmt->bindParam(':winning_game', $winning_game);
                    $stmt->bindParam(':winning_date', $winning_date);
                    $stmt->bindParam(':id', $existingRecord['id']);
                    if ($stmt->execute()) {
                        $message = "Winning record successfully modified. Game: $winning_game, Winning Number: $winning_number.";
                    } else {
                        $error = "Failed to modify the winning record. Please try again.";
                    }
                } catch (PDOException $e) {
                    $error = 'Error modifying winning record: ' . $e->getMessage();
                }
            } elseif (!$existingRecord && isset($_POST['submit_winning'])) {
                // Insert new record into the database if no duplicate exists
                try {
                    $stmt = $conn->prepare("INSERT INTO winning_record (winning_number, winning_game, winning_date, created_by_agent, agent_hashed_secretkey, created_at) 
                                            VALUES (:winning_number, :winning_game, :winning_date, :created_by_agent, :hashed_secret_key, NOW())");
                    $stmt->bindParam(':winning_number', $winning_number);
                    $stmt->bindParam(':winning_game', $winning_game);
                    $stmt->bindParam(':winning_date', $winning_date);
                    $stmt->bindParam(':created_by_agent', $created_by_agent);
                    $stmt->bindParam(':hashed_secret_key', $hashed_secret_key);
                    if ($stmt->execute()) {
                        $message = "Winning record successfully inserted. Game: $winning_game, Winning Number: $winning_number.";
                    } else {
                        $error = "Failed to insert the winning record. Please try again.";
                    }
                } catch (PDOException $e) {
                    $error = 'Error inserting winning record: ' . $e->getMessage();
                }
            } elseif ($existingRecord && isset($_POST['submit_winning'])) {
                $error = "A record for $winning_game on $winning_date already exists. Please modify the existing record.";
            }
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
        <?php include('config/sidebar.php'); ?> <!-- Include sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <?php include('config/topbar.php'); ?> <!-- Include topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <h2 class="h3 mb-4 text-gray-800">Enter Winning Number</h2>

                    <!-- Show Messages in Content Wrapper -->
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php elseif ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true): ?>
                        <!-- Authentication Form -->
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="password">Authenticate Yourself</label>
                                <input type="password" class="form-control" name="password" placeholder="Enter your password" required>
                            </div>
                            <button type="submit" name="authenticate" class="btn btn-primary">Authenticate</button>
                        </form>
                    <?php else: ?>
                        <!-- Winning Entry Form -->
                        <form method="POST" action="">

                            <!-- Winning Number -->
                            <div class="form-group">
                                <label for="winning_number">Winning Number</label>
                                <input type="text" class="form-control" name="winning_number" id="winning_number" value="<?php echo $winning_number; ?>" placeholder="Enter 2 or 3-digit number" required>
                            </div>

                            <!-- Winning Game -->
                            <div class="form-group">
                                <label for="winning_game">Winning Game</label>
                                <select name="winning_game" id="winning_game" class="form-control" required>
                                    <option value="2-D" <?php if($winning_game == '2-D') echo 'selected'; ?>>2-D</option>
                                    <option value="3-D" <?php if($winning_game == '3-D') echo 'selected'; ?>>3-D</option>
                                </select>
                            </div>

                            <!-- Winning Date -->
                            <div class="form-group">
                                <label for="winning_date">Winning Date</label>
                                <input type="date" class="form-control" name="winning_date" id="winning_date" value="<?php echo $winning_date; ?>" required>
                            </div>

                            <!-- Submit Button -->
                            <?php if ($existingRecord): ?>
                                <button type="submit" name="modify_winning" class="btn btn-warning">Modify Winning Entry</button>
                            <?php else: ?>
                                <button type="submit" name="submit_winning" class="btn btn-success">Submit Winning Entry</button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>

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


</body>
</html>
