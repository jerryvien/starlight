<?php
session_start();
include('config/database.php');

// Ensure the user is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Fetch agent data
$agent_id = $_SESSION['agent_id'];
$agent_query = $conn->prepare("SELECT * FROM admin_access WHERE agent_id = :agent_id");
$agent_query->bindParam(':agent_id', $agent_id);
$agent_query->execute();
$agent_data = $agent_query->fetch(PDO::FETCH_ASSOC);

// Fetch recent purchase activity (last 7 days)
$recent_purchase_query = $conn->prepare("SELECT * FROM purchase_entries WHERE agent_id = :agent_id AND purchase_datetime >= NOW() - INTERVAL 7 DAY ORDER BY purchase_datetime DESC");
$recent_purchase_query->bindParam(':agent_id', $agent_id);
$recent_purchase_query->execute();
$recent_purchases = $recent_purchase_query->fetchAll(PDO::FETCH_ASSOC);

// Fetch customers linked to the agent
$customer_query = $conn->prepare("SELECT * FROM customer_details WHERE agent_id = :agent_id");
$customer_query->bindParam(':agent_id', $agent_id);
$customer_query->execute();
$customers = $customer_query->fetchAll(PDO::FETCH_ASSOC);

// Fetch the agent's leader
$leader_query = $conn->prepare("SELECT agent_name FROM admin_access WHERE agent_id = :agent_leader");
$leader_query->bindParam(':agent_leader', $agent_data['agent_leader']);
$leader_query->execute();
$leader_name = $leader_query->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Agent Profile</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Profile</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item">Profile</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section profile">
      <div class="row">
        <!-- Agent Information -->
        <div class="col-xl-4">
          <div class="card">
            <div class="card-body profile-card pt-4 d-flex flex-column align-items-center">
              <img src="assets/img/profile-img.jpg" alt="Profile" class="rounded-circle">
              <h2><?php echo $agent_data['agent_name']; ?></h2>
              <h3>Agent</h3>
              <div class="social-links mt-2">
                <a href="#" class="twitter"><i class="bi bi-twitter"></i></a>
                <a href="#" class="facebook"><i class="bi bi-facebook"></i></a>
                <a href="#" class="instagram"><i class="bi bi-instagram"></i></a>
                <a href="#" class="linkedin"><i class="bi bi-linkedin"></i></a>
              </div>
            </div>
          </div>
        </div>

        <!-- Profile Edit Form -->
        <div class="col-xl-8">
          <div class="card">
            <div class="card-body pt-3">
              <ul class="nav nav-tabs nav-tabs-bordered">
                <li class="nav-item">
                  <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile-overview">Overview</button>
                </li>
                <li class="nav-item">
                  <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-edit">Edit Profile</button>
                </li>
              </ul>
              <div class="tab-content pt-2">
                <!-- Overview Tab -->
                <div class="tab-pane fade show active profile-overview" id="profile-overview">
                  <h5 class="card-title">Profile Details</h5>
                  <div class="row">
                    <div class="col-lg-3 col-md-4 label">Full Name</div>
                    <div class="col-lg-9 col-md-8"><?php echo $agent_data['agent_name']; ?></div>
                  </div>
                  <div class="row">
                    <div class="col-lg-3 col-md-4 label">Market</div>
                    <div class="col-lg-9 col-md-8"><?php echo $agent_data['agent_market']; ?></div>
                  </div>
                  <div class="row">
                    <div class="col-lg-3 col-md-4 label">Credit Limit</div>
                    <div class="col-lg-9 col-md-8"><?php echo $agent_data['agent_credit_limit']; ?></div>
                  </div>
                  <div class="row">
                    <div class="col-lg-3 col-md-4 label">Leader</div>
                    <div class="col-lg-9 col-md-8"><?php echo $leader_name; ?></div>
                  </div>
                </div>

                <!-- Edit Profile Tab -->
                <div class="tab-pane fade profile-edit pt-3" id="profile-edit">
                  <form method="POST" action="update_agent_profile.php">
                    <div class="row mb-3">
                      <label for="agent_name" class="col-md-4 col-lg-3 col-form-label">Full Name</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="agent_name" type="text" class="form-control" value="<?php echo $agent_data['agent_name']; ?>">
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="agent_market" class="col-md-4 col-lg-3 col-form-label">Market</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="agent_market" type="text" class="form-control" value="<?php echo $agent_data['agent_market']; ?>">
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="agent_credit_limit" class="col-md-4 col-lg-3 col-form-label">Credit Limit</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="agent_credit_limit" type="text" class="form-control" value="<?php echo $agent_data['agent_credit_limit']; ?>">
                      </div>
                    </div>
                    <div class="text-center">
                      <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                  </form>
                </div>
              </div><!-- End Bordered Tabs -->
            </div>
          </div>
        </div>

        <!-- Recent Purchases Section -->
        <div class="col-xl-12 mt-4">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Recent Purchase Activity (Last 7 Days)</h5>
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th>Purchase Number</th>
                    <th>Amount</th>
                    <th>Category</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recent_purchases as $purchase): ?>
                  <tr>
                    <td><?php echo $purchase['purchase_no']; ?></td>
                    <td><?php echo $purchase['purchase_amount']; ?></td>
                    <td><?php echo $purchase['purchase_category']; ?></td>
                    <td><?php echo $purchase['purchase_datetime']; ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Linked Customers Section -->
        <div class="col-xl-12 mt-4">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Linked Customers</h5>
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th>Customer Name</th>
                    <th>Credit Limit</th>
                    <th>Total Sales</th>
                    <th>VIP Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($customers as $customer): ?>
                  <tr>
                    <td><?php echo $customer['customer_name']; ?></td>
                    <td><?php echo $customer['credit_limit']; ?></td>
                    <td><?php echo $customer['total_sales']; ?></td>
                    <td><?php echo $customer['vip_status']; ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
    </section>

  </main><!-- End #main -->

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/main.js"></script>

</body>
</html>