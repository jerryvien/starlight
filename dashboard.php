<?php
session_start();

// If the user is not logged in, redirect them to the login page (index.php)
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Include your CSS and JS assets for SB Admin 2 here -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">

    <!-- SB Admin dashboard content here -->
    <div id="wrapper">
        <!-- Sidebar, Navbar, and Main Content from SB Admin -->
        <h1>Welcome, <?php echo $_SESSION['admin']; ?>!</h1>
        <p>Agent Login ID: <?php echo $_SESSION['agent_login_id']; ?></p>
        <p>Market: <?php echo $_SESSION['agent_market']; ?></p>
        <p>Credit Limit: <?php echo $_SESSION['agent_credit_limit']; ?></p>
        <p>Leader: <?php echo $_SESSION['agent_leader']; ?></p>

        <a href="logout.php" class="btn btn-primary">Logout</a>
    </div>

    <!-- Include SB Admin JS Files -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
</body>

</html>