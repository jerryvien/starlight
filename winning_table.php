<?php
session_start();
include('config/database.php'); // Include your database connection

// Redirect to login page if the user is not logged in or not authenticated
if (!isset($_SESSION['admin']) || !isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: index.php"); 
    exit;
}

// Ensure the user is a super_admin
if ($_SESSION['access_level'] !== 'super_admin') {
    echo "<script>alert('You must be a super admin to access this page.'); window.location.href='index.php';</script>";
    exit;
}

// Initialize variables for messages
$message = '';
$error = '';

// Handle authentication
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
        // Store the hashed password in the session
        $_SESSION['hashed_password'] = $agent['agent_password']; 

        $message = "Authenticated successfully!";
    } else {
        $error = 'Invalid password! Please try again.';
    }
}

// Handle form submission to insert the winning number
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_winning'])) {
    // ... (Validation and other logic remains the same)

    // Insert into the database along with the hashed password
    try {
        $stmt = $conn->prepare("INSERT INTO winning_record (winning_number, winning_game, winning_date, created_by_agent, created_at, hashed_secretkey) 
                                VALUES (:winning_number, :winning_game, :winning_date, :created_by_agent, NOW(), :hashed_secretkey)");
        $stmt->bindParam(':winning_number', $winning_number);
        $stmt->bindParam(':winning_game', $winning_game);
        $stmt->bindParam(':winning_date', $winning_date);
        $stmt->bindParam(':created_by_agent', $created_by_agent);
        // Fetch the hashed password from the session
        $hashed_password = $_SESSION['hashed_password']; 
        $stmt->bindParam(':hashed_secretkey', $hashed_password);
        $stmt->execute();

        $message = 'Winning record successfully inserted.';
    } catch (PDOException $e) {
        $error = 'Error inserting winning record: ' . $e->getMessage();
    }

    // ... 
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

    <div id="wrapper">
        <?php include('sidebar.php'); ?> <div id="content-wrapper" class="d-flex flex-column">

            <div id="content">
                <?php include('topbar.php'); ?> <div class="container-fluid">
                    <h2 class="h3 mb-4 text-gray-800">Enter Winning Number</h2>

                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php elseif ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true): ?>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="password">Authenticate Yourself</label>
                                <input type="password" class="form-control" name="password" placeholder="Enter your password" required>
                            </div>
                            <button type="submit" name="authenticate" class="btn btn-primary">Authenticate</button>
                        </form>
                    <?php elseif ($_SESSION['authenticated']): ?>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="winning_number">Winning Number</label>
                                <input type="text" class="form-control" name="winning_number" placeholder="Enter 2 or 3-digit number" required>
                            </div>

                            <div class="form-group">
                                <label for="winning_game">Winning Game</label>
                                <select name="winning_game" class="form-control" required>
                                    <option value="2-D">2-D</option>
                                    <option value="3-D">3-D</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="winning_date">Winning Date</label>
                                <input type="date" class="form-control" name="winning_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <button type="submit" name="submit_winning" class="btn btn-success">Submit Winning Entry</button>
                        </form>
                    <?php endif; ?>

                </div>
                </div>
            <?php include('footer.php'); ?> </div>
        </div>
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>

</body>
</html>