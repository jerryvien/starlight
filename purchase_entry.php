<?php
session_start();
include('config/database.php'); // Include your database connection

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Fetch user details and access level
$access_level = $_SESSION['access_level'];
$agent_id = $_SESSION['agent_id'];

// Fetch all customers if access_level is 'super_admin', or fetch customers by agent_id for agents
if ($access_level === 'super_admin') {
    $query = "SELECT * FROM customer_details ORDER BY created_at DESC";
} else {
    $query = "SELECT * FROM customer_details WHERE agent_id = :agent_id ORDER BY created_at DESC";
}
$stmt = $conn->prepare($query);
if ($access_level !== 'super_admin') {
    $stmt->bindParam(':agent_id', $agent_id);
}
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = $_POST['customer_id'];
    $entries = $_POST['purchase_entries']; // Dynamic purchase entries

    try {
        foreach ($entries as $entry) {
            $serial_number = generate_serial_number(); // Generate serial number
            $stmt = $conn->prepare("INSERT INTO purchase_records (customer_id, agent_id, purchase_no, purchase_category, purchase_amount, purchase_date, serial_number) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $customer_id,
                $agent_id,
                $entry['purchase_no'],
                $entry['purchase_category'],
                $entry['purchase_amount'],
                $entry['purchase_date'],  // Purchase date is now part of dynamic fields
                $serial_number
            ]);
        }
        $success = "Purchases added successfully!";
    } catch (PDOException $e) {
        $error = "Error adding purchase: " . $e->getMessage();
    }
}

// Function to generate a unique serial number based on machine ID and current datetime
function generate_serial_number() {
    return substr(gethostname(), -8) . date('YmdHis');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Purchase Entry</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include('sidebar.php'); ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <?php include('topbar.php'); ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Purchase Entry</h1>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php elseif ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <!-- Customer Selection -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <label for="customer_search">Search Customer:</label>
                            <input type="text" id="customer_search" class="form-control" placeholder="Search by name..." onkeyup="filterCustomers()">

                            <label for="customer_select">Select Customer:</label>
                            <select id="customer_select" class="form-control" onchange="displayCustomerDetails()">
                                <option value="">-- Select Customer --</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?php echo $customer['customer_id']; ?>"><?php echo $customer['customer_name']; ?></option>
                                <?php endforeach; ?>
                            </select>

                            <div id="customer_details" style="margin-top: 20px;">
                                <!-- Customer details will be displayed here after selection -->
                            </div>
                        </div>
                    </div>

                    <!-- Purchase Entry Section -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="POST" id="purchase_form">
                                <input type="hidden" name="customer_id" id="selected_customer_id">
                                
                                <label for="purchase_entries_count">Number of Purchases:</label>
                                <select id="purchase_entries_count" class="form-control mb-3" onchange="generatePurchaseFields()">
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                </select>

                                <div id="purchase_entries_container"></div> <!-- Purchase entry fields will be generated here -->

                                <button type="submit" class="btn btn-primary mt-4">Submit Purchases</button>
                            </form>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include('footer.php'); ?>
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>

    <script>
        // Filter customers based on search input
        function filterCustomers() {
            var input = document.getElementById("customer_search").value.toLowerCase();
            var select = document.getElementById("customer_select");
            var options = select.getElementsByTagName("option");
            for (var i = 0; i < options.length; i++) {
                var optionText = options[i].text.toLowerCase();
                options[i].style.display = optionText.includes(input) ? "" : "none";
            }
        }

        // Display customer details when selected
        function displayCustomerDetails() {
            var select = document.getElementById("customer_select");
            var selectedCustomerId = select.value;
            document.getElementById("selected_customer_id").value = selectedCustomerId;

            // Fetch and display customer details (hardcoded details for now)
            document.getElementById("customer_details").innerHTML = `
                <h5>Customer Details:</h5>
                <p>ID: ${selectedCustomerId}</p>
                <p>Name: ${select.options[select.selectedIndex].text}</p>
            `;
        }

        // Generate dynamic purchase entry fields based on the selected number of purchases
        function generatePurchaseFields() {
            var count = document.getElementById("purchase_entries_count").value;
            var container = document.getElementById("purchase_entries_container");
            container.innerHTML = '';

            for (var i = 0; i < count; i++) {
                container.innerHTML += `
                    <div class="form-group">
                        <label>Purchase Number</label>
                        <input type="text" name="purchase_entries[${i}][purchase_no]" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Purchase Category</label>
                        <select name="purchase_entries[${i}][purchase_category]" class="form-control">
                            <option value="Box">Box</option>
                            <option value="Straight">Straight</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Purchase Amount (RM)</label>
                        <input type="number" name="purchase_entries[${i}][purchase_amount]" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Purchase Date</label>
                        <input type="date" name="purchase_entries[${i}][purchase_date]" class="form-control" required>
                    </div>
                    <hr>
                `;
            }
        }
    </script>
</body>
</html>