<?php
session_start();
include('config/database.php'); // Include your database connection

// Process login request
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
            // Store session data
            $_SESSION['admin'] = $admin['agent_name'];
            $_SESSION['agent_id'] = $admin['agent_id'];
            $_SESSION['access_level'] = $admin['access_level'];
            $_SESSION['agent_market'] = $admin['agent_market'];
            $_SESSION['agent_credit_limit'] = $admin['agent_credit_limit'];
            $_SESSION['agent_leader'] = $admin['agent_leader'];
            $_SESSION['agent_login_id'] = $admin['agent_login_id'];
            header("Location: dashboard.php"); // Redirect to dashboard
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
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Index - Day Bootstrap Template</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700;800&family=Poppins:wght@100;400;700&family=Raleway:wght@100;400;700&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">

</head>

<body class="index-page">

  <header id="header" class="header fixed-top">
    <!-- Header code as before... -->
    <div class="branding d-flex align-items-center">
      <div class="container position-relative d-flex align-items-center justify-content-between">
        <a href="index.html" class="logo d-flex align-items-center">
          <h1 class="sitename">Day</h1>
        </a>

        <nav id="navmenu" class="navmenu">
          <ul>
            <!-- Other links -->
            <li><a href="#" data-bs-toggle="modal" data-bs-target="#loginModal">Login</a></li>
          </ul>
          <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
        </nav>
      </div>
    </div>
  </header>

  <main class="main">
    <!-- Main content goes here -->
  </main>

  <!-- Login Modal -->
  <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="loginModalLabel">Login</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form action="" method="POST">
            <div class="mb-3">
              <label for="login_id" class="form-label">Login ID</label>
              <input type="text" class="form-control" id="login_id" name="login_id" required>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Password</label>
              <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3 text-danger">
              <?php if (!empty($error)) { echo $error; } ?>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/js/main.js"></script>

</body>

</html>
