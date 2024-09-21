<?php
session_start();
include('config/database.php'); // Include your database connection

// Process login request (your existing PHP login code stays here)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_id = $_POST['login_id'];
    $password = $_POST['password'];
    $error_message = "Invalid login ID or password!";
    try {
        $stmt = $conn->prepare("SELECT * FROM admin_access WHERE agent_login_id = :login_id LIMIT 1");
        $stmt->bindParam(':login_id', $login_id);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['agent_password'])) {
            $_SESSION['admin'] = $admin['agent_name'];
            $_SESSION['agent_id'] = $admin['agent_id'];
            $_SESSION['access_level'] = $admin['access_level'];
            $_SESSION['agent_market'] = $admin['agent_market'];
            $_SESSION['agent_credit_limit'] = $admin['agent_credit_limit'];
            $_SESSION['agent_leader'] = $admin['agent_leader'];
            $_SESSION['agent_login_id'] = $admin['agent_login_id'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = $error_message;
        }
    } catch (PDOException $e) {
        $error = $error_message;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Company Profile - Easehubs</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/custom.css" rel="stylesheet"> <!-- Add custom styles if needed -->
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="#">Easehubs</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="bg-primary text-light text-center p-5">
        <div class="container">
            <h1>Welcome to Easehubs</h1>
            <p>Your partner for premium custom corporate gifts.</p>
        </div>
    </section>

    <!-- About Us Section -->
    <section class="container my-5">
        <div class="row">
            <div class="col-md-6">
                <h2>About Us</h2>
                <p>Easehubs is a leading provider of high-quality custom corporate gifts. We help businesses of all sizes create memorable, branded products for their clients, partners, and employees.</p>
            </div>
            <div class="col-md-6">
                <img src="images/company-profile.jpg" class="img-fluid" alt="Company Profile">
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="bg-light text-center p-5">
        <div class="container">
            <h2>Contact Us</h2>
            <p>Contact us for premium corporate gifts. Reach us at <strong>contact@easehubs.com</strong> or call us at <strong>+123-456-7890</strong>.</p>
        </div>
    </section>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loginModalLabel">Login</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="index.php">
                        <div class="mb-3">
                            <label for="login_id" class="form-label">Login ID</label>
                            <input type="text" class="form-control" id="login_id" name="login_id" placeholder="Enter Login ID" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Login</button>
                    </form>
                    <?php if (isset($error)) { echo "<p class='text-danger mt-3'>$error</p>"; } ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>