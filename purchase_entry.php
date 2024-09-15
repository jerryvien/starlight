<?php
session_start();

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

include('config/database.php'); // Include your database connection

// Fetch access level from session
$access_level = $_SESSION['access_level'];
$agent_id = $_SESSION['agent_id'];

// Fetch customer list based on access level
$customers = [];
if ($access_level === 'super_admin') {
    $query = "SELECT * FROM customer_details";
} else {
    $query = "SELECT * FROM customer_details WHERE agent_id = :agent_id";
}
$stmt = $conn->prepare($query);
if ($access_level !== 'super_admin') {
    $stmt->bindParam(':agent_id', $agent_id);
}
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = $_POST['customer_id'];
    $purchase_entries = $_POST['purchase_no'];
    $purchase_category = $_POST['purchase_category'];
    $purchase_amount = $_POST['purchase_amount'];
    $purchase_date = $_POST['purchase_date'];
    
    // Generate a unique serial number based on computer ID and current datetime
    $computer_id = gethostname(); // Example computer ID
    $serial_number = $computer_id . '_' . date('YmdHis');

    // Insert each purchase entry into the database
    for ($i = 0; $i < count($purchase_entries); $i++) {
        $sql = "INSERT INTO purchase_entries (customer_id, agent_id, purchase_no, purchase_category, purchase_amount, purchase_date, serial_number) 
                VALUES (:customer_id, :agent_id, :purchase_no, :purchase_category, :purchase_amount, :purchase_date, :serial_number)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->bindParam(':agent_id', $agent_id);
        $stmt->bindParam(':purchase_no', $purchase_entries[$i]);
        $stmt->bindParam(':purchase_category', $purchase_category[$i]);
        $stmt->bindParam(':purchase_amount', $purchase_amount[$i]);
        $stmt->bindParam(':purchase_date', $purchase_date[$i]);
        $stmt->bindParam(':serial_number', $serial_number);
        $stmt->execute();
    }

    echo "<div class='alert alert-success'>Purchase entries added successfully with serial number: $serial_number</div>";
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

    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include('sidebar.php'); ?> <!-- Reuse your standard sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <?php include('topbar.php'); ?> <!-- Reuse your standard topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Purchase Entry</h1>

                    <!-- Customer Search Section -->
                    <div class="form-group">
                        <label for="customerSearch">Search Customer</label>
                        <input type="text" class="form-control" id="customerSearch" placeholder="Search by customer name..." onkeyup="filterCustomers()">
                    </div>

                    <!-- Customer Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered" id="customerTable">
                            <thead>
                                <tr>
                                    <th>Customer ID</th>
                                    <th>Customer Name</th>
                                    <th>Agent Name</th>
                                    <th>Credit Limit</th>
                                    <th>VIP Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $customer): ?>
                                    <tr class="customer-row" onclick="selectCustomer('<?php echo $customer['customer_id']; ?>', '<?php echo $customer['customer_name']; ?>')">
                                        <td><?php echo $customer['customer_id']; ?></td>
                                        <td><?php echo $customer['customer_name']; ?></td>
                                        <td><?php echo $customer['agent_id']; ?></td>
                                        <td><?php echo $customer['credit_limit']; ?></td>
                                        <td><?php echo $customer['vip_status']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Purchase Entry Form -->
                    <form method="POST" action="purchase_entry.php">
                        <!-- Hidden input to store selected customer ID -->
                        <input type="hidden" name="customer_id" id="selectedCustomerId" required>
                        <div class="form-group">
                            <label for="customerNameDisplay">Customer Name</label>
                            <input type="text" id="customerNameDisplay" class="form-control" readonly>
                        </div>

                        <!-- Dynamic Purchase Entry -->
                        <div class="form-group">
                            <label for="purchaseEntries">Number of Purchase Entries</label>
                            <select id="purchaseEntries" class="form-control" onchange="createPurchaseEntries()">
                                <option value="1">1 Entry</option>
                                <option value="2">2 Entries</option>
                                <option value="3">3 Entries</option>
                                <option value="4">4 Entries</option>
                                <option value="5">5 Entries</option>
                                <option value="6">6 Entries</option>
                                <option value="7">7 Entries</option>
                                <option value="8">8 Entries</option>
                                <option value="9">9 Entries</option>
                                <option value="10">10 Entries</option>
                            </select>
                        </div>

                        <!-- Table for Dynamic Purchase Entries -->
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Purchase No.</th>
                                        <th>Category</th>
                                        <th>Amount</th>
                                        <th>Purchase Date</th>
                                    </tr>
                                </thead>
                                <tbody id="purchaseEntryRows">
                                    <!-- Dynamic Rows Will be Added Here -->
                                </tbody>
                            </table>
                        </div>

                        <button type="submit" class="btn btn-primary">Submit Purchase</button>
                    </form>
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include('footer.php'); ?> <!-- Reuse your standard footer -->

        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>

    <!-- JavaScript for handling customer search -->
    <script>
        function filterCustomers() {
            var input = document.getElementById("customerSearch");
            var filter = input.value.toLowerCase();
            var rows = document.getElementsByClassName("customer-row");

            for (var i = 0; i < rows.length; i++) {
                var customerName = rows[i].cells[1].innerText.toLowerCase();
                if (customerName.includes(filter)) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }

        function selectCustomer(customerId, customerName) {
            document.getElementById("selectedCustomerId").value = customerId;
            document.getElementById("customerNameDisplay").value = customerName;
        }
    </script>

    <!-- JavaScript for handling dynamic purchase entries with max 10 records -->
    <script>
        function createPurchaseEntries() {
            var numEntries = document.getElementById("purchaseEntries").value;
            var tbody = document.getElementById("purchaseEntryRows");
            tbody.innerHTML = ""; // Clear existing rows

            for (var i = 0; i < numEntries; i++) {
                var row = document.createElement("tr");

                var purchaseNo = document.createElement("td");
                var purchaseCategory = document.createElement("td");
                var purchaseAmount = document.createElement("td");
                var purchaseDate = document.createElement("td");

                purchaseNo.innerHTML = '<input type="text" class="form-control" name="purchase_no[]" required>';
                purchaseCategory.innerHTML = '<select class="form-control" name="purchase_category[]"><option value="Box">Box</option><option value="Straight">Straight</option></select>';
                purchaseAmount.innerHTML = '<input type="number" class="form-control" name="purchase_amount[]" required>';
                purchaseDate.innerHTML = '<input type="date" class="form-control" name="purchase_date[]" required>';

                row.appendChild(purchaseNo);
                row.appendChild(purchaseCategory);
                row.appendChild(purchaseAmount);
                row.appendChild(purchaseDate);

                tbody.appendChild(row);
            }
        }
    </script>

</body>

</html>