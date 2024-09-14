<?php
session_start();
include('config/database.php'); // Include your database connection

// Check if the form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Fetch the login ID and password from the form
    $login_id = $_POST['login_id'];
    $password = $_POST['password'];

    // Default error message to display for both wrong username and password
    $error_message = "Invalid login ID or password!";

    try {
        // Prepare the SQL query to find the user with the provided login ID
        $stmt = $conn->prepare("SELECT * FROM admin_access WHERE agent_login_id = :login_id LIMIT 1");
        $stmt->bindParam(':login_id', $login_id);
        $stmt->execute();

        // Fetch the user data from the database
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['agent_password'])) {
            // Set session variables for the logged-in user
            $_SESSION['admin'] = $admin['agent_name'];
            $_SESSION['agent_id'] = $admin['agent_id'];
            $_SESSION['access_level'] = $admin['access_level'];
            $_SESSION['agent_market'] = $admin['agent_market'];
            $_SESSION['agent_credit_limit'] = $admin['agent_credit_limit'];
            $_SESSION['agent_leader'] = $admin['agent_leader'];
            $_SESSION['agent_login_id'] = $admin['agent_login_id'];

            // Redirect to the dashboard
            header("Location: dashboard.php");
            exit;
        } else {
            // Display the same error message for invalid login ID or password
            $error = $error_message;
        }
    } catch (PDOException $e) {
        // Catch any database errors, but still display the generic error message
        $error = $error_message;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Ken Group Admin - Login</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body class="bg-gradient-primary">

    <div class="container">

        <!-- Outer Row -->
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-6 d-none d-lg-block bg-login-image"></div>
                            <div class="col-lg-6">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Welcome Back!</h1>
                                    </div>
                                    <!-- The login form -->
                                    <form class="user" method="POST" action="index.php">
                                        <div class="form-group">
                                            <input type="text" name="login_id" class="form-control form-control-user"
                                                id="login_id" aria-describedby="loginIDHelp"
                                                placeholder="Enter Login ID" required>
                                        </div>
                                        <div class="form-group">
                                            <input type="password" name="password" class="form-control form-control-user"
                                                id="password" placeholder="Password" required>
                                        </div>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox small">
                                                <input type="checkbox" class="custom-control-input" id="customCheck">
                                                <label class="custom-control-label" for="customCheck">Remember Me</label>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-user btn-block">
                                            Login
                                        </button>
                                    </form>

                                    <!-- Display error message if login fails -->
                                    <?php if (isset($error)) { echo "<p class='text-danger mt-3'>$error</p>"; } ?>

                                    <hr>
                                    <div class="text-center">
                                        <a class="small" href="forgot-password.html">Forgot Password?</a>
                                    </div>
                                    <div class="text-center">
                                        <a class="small" href="register.html">Create an Account!</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

</body>

</html>
