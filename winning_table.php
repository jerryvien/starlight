<?php
session_start();
include('config/database.php'); // Include your database connection

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Ensure the user is a super_admin
if ($_SESSION['access_level'] !== 'super_admin') {
    echo "<script>alert('You must be a super admin to access this page.'); window.location.href='index.php';</script>";
    exit;
}

// Handle form submission to insert the winning number
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $winning_number = $_POST['winning_number'];
    $winning_game = $_POST['winning_game'];
    $winning_date = $_POST['winning_date'];
    $created_by_agent = $_SESSION['agent_id'];

    // Validate the winning number based on game type (2-D or 3-D)
    if ($winning_game == '2-D' && !preg_match('/^\d{2}$/', $winning_number)) {
        echo "<script>alert('Invalid 2-D winning number! It must be a 2-digit number.');</script>";
    } elseif ($winning_game == '3-D' && !preg_match('/^\d{3}$/', $winning_number)) {
        echo "<script>alert('Invalid 3-D winning number! It must be a 3-digit number.');</script>";
    } else {
        // Insert into the database
        try {
            $stmt = $conn->prepare("INSERT INTO winning_record (winning_number, winning_game, winning_date, created_by_agent, created_at) 
                                    VALUES (:winning_number, :winning_game, :winning_date, :created_by_agent, NOW())");
            $stmt->bindParam(':winning_number', $winning_number);
            $stmt->bindParam(':winning_game', $winning_game);
            $stmt->bindParam(':winning_date', $winning_date);
            $stmt->bindParam(':created_by_agent', $created_by_agent);
            $stmt->execute();

            echo "<script>alert('Winning record successfully inserted.'); window.location.href='winning_entry.php';</script>";
        } catch (PDOException $e) {
            echo "<script>alert('Error inserting winning record: " . $e->getMessage() . "');</script>";
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

                    <!-- Winning Entry Form -->
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

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary">Submit Winning Entry</button>
                    </form>

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
