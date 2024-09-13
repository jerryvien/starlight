<?php
session_start();
include('config/database.php'); // Include the database connection

// Temporarily comment out the authentication logic to bypass login
/*
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_id = $_POST['login_id'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM admin_access WHERE agent_login_id = :login_id LIMIT 1");
    $stmt->bindParam(':login_id', $login_id);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['agent_password'])) {
        $_SESSION['admin'] = $admin['agent_name'];
        $_SESSION['agent_id'] = $admin['agent_id'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid login ID or password!";
    }
}
*/
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>SB Admin 2 - Login</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
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
                                    <!-- Bypass the form submission -->
                                    <form method="POST" action="index.php">
                                        <div class="form-group">
                                            <input type="text" class="form-control form-control-user"
                                                id="login_id" name="login_id" placeholder="Enter Login ID" required>
                                        </div>
                                        <div class="form-group">
                                            <input type="password" class="form-control form-control-user"
                                                id="password" name="password" placeholder="Password" required>
                                        </div>
                                        <!-- Direct button to redirect -->
                                        <button type="submit" class="btn btn-primary btn-user btn-block">
                                            Redirect to Dashboard (Testing)
                                        </button>
                                    </form>

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