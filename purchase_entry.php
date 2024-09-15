<?php
session_start();
include('config/database.php'); // Include your database connection

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = $_POST['customer_id'];
    $purchase_entries = $_POST['purchase_no'];
    $purchase_category = $_POST['purchase_category'];
    $purchase_amount = $_POST['purchase_amount'];
    $purchase_date = $_POST['purchase_date'];
    
    // Set agent ID based on access level
    $agent_id_to_save = ($_SESSION['access_level'] === 'super_admin') ? $_POST['agent_id'] : $_SESSION['agent_id'];

    // Generate a unique serial number based on computer ID and current datetime
    $computer_id = gethostname(); // Example computer ID
    $serial_number = $computer_id . '_' . date('YmdHis');

    // Insert each purchase entry into the database
    for ($i = 0; $i < count($purchase_entries); $i++) {
        $sql = "INSERT INTO purchase_entries (customer_id, agent_id, purchase_no, purchase_category, purchase_amount, purchase_datetime, serial_number) 
                VALUES (:customer_id, :agent_id, :purchase_no, :purchase_category, :purchase_amount, :purchase_datetime, :serial_number)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->bindParam(':agent_id', $agent_id_to_save);
        $stmt->bindParam(':purchase_no', $purchase_entries[$i]);
        $stmt->bindParam(':purchase_category', $purchase_category[$i]);
        $stmt->bindParam(':purchase_amount', $purchase_amount[$i]);
        $stmt->bindParam(':purchase_datetime', $purchase_date[$i]);
        $stmt->bindParam(':serial_number', $serial_number);
        $stmt->execute();
    }

    echo "<div class='alert alert-success'>Purchase entries added successfully with serial number: $serial_number</div>";
}

// Fetch customer data for the dropdown or search filter
$query = ($_SESSION['access_level'] === 'super_admin') ? 
    "SELECT customer_id, customer_name FROM customer_details" : 
    "SELECT customer_id, customer_name FROM customer_details WHERE agent_id = :agent_id";
$stmt = $conn->prepare($query);

if ($_SESSION['access_level'] !== 'super_admin') {
    $stmt->bindParam(':agent_id', $_SESSION['agent_id']);
}

$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch agents for super_admin dropdown (optional for super_admin only)
$agents = [];
if ($_SESSION['access_level'] === 'super_admin') {
    $stmt = $conn->prepare("SELECT agent_id, agent_name FROM admin_access");
    $stmt->execute();
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    <title>Purchase Entry</title>

    <!-- Custom fonts and styles for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <script src="vendor/jquery/jquery.min.js"></script>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include('sidebar.php'); ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include('topbar.php'); ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Purchase Entry</h1>

                    <form method="POST" action="purchase_entry.php">
                        <!-- Customer Search and Display -->
                        <div class="form-group">
                            <label for="customer_search">Search Customer</label>
                            <input type="text" class="form-control" id="customer_search" placeholder="Search customer..." onkeyup="filterCustomers()">
                            <select id="customer_dropdown" class="form-control mt-2" name="customer_id" required>
                                <option value="">Select Customer</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?php echo $customer['customer_id']; ?>"><?php echo $customer['customer_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Agent Dropdown (only for super_admin) -->
                        <?php if ($_SESSION['access_level'] === 'super_admin'): ?>
                        <div class="form-group">
                            <label for="agent_dropdown">Agent</label>
                            <select id="agent_dropdown" class="form-control" name="agent_id" required>
                                <option value="">Select Agent</option>
                                <?php foreach ($agents as $agent): ?>
                                    <option value="<?php echo $agent['agent_id']; ?>"><?php echo $agent['agent_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <!-- Dynamic Purchase Entries -->
                        <div id="purchase_entries_wrapper">
                            <h5>Purchase Entries</h5>
                            <div class="form-group">
                                <label for="purchase_no_0">Purchase Number</label>
                                <input type="text" class="form-control" name="purchase_no[]" required>

                                <label for="purchase_category_0">Category</label>
                                <select class="form-control" name="purchase_category[]">
                                    <option value="Box">Box</option>
                                    <option value="Straight">Straight</option>
                                </select>

                                <label for="purchase_amount_0">Amount</label>
                                <input type="number" class="form-control" name="purchase_amount[]" required>

                                <label for="purchase_date_0">Purchase Date</label>
                                <input type="date" class="form-control" name="purchase_date[]" required>
                            </div>
                        </div>

                        <!-- Button to add more dynamic fields -->
                        <button type="button" class="btn btn-primary" onclick="addPurchaseEntry()">Add More Entries</button>
                        
                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-success mt-3">Submit Purchase Entry</button>
                    </form>
                </div>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>

    <script>
        let entryCount = 1;

        // Add dynamic purchase entry fields (up to 10 max)
        function addPurchaseEntry() {
            if (entryCount >= 10) {
                alert('Maximum 10 purchase entries allowed');
                return;
            }

            const wrapper = document.getElementById('purchase_entries_wrapper');
            const div = document.createElement('div');
            div.className = 'form-group mt-3';
            div.innerHTML = `
                <label for="purchase_no_${entryCount}">Purchase Number</label>
                <input type="text" class="form-control" name="purchase_no[]" required>

                <label for="purchase_category_${entryCount}">Category</label>
                <select class="form-control" name="purchase_category[]">
                    <option value="Box">Box</option>
                    <option value="Straight">Straight</option>
                </select>

                <label for="purchase_amount_${entryCount}">Amount</label>
                <input type="number" class="form-control" name="purchase_amount[]" required>

                <label for="purchase_date_${entryCount}">Purchase Date</label>
                <input type="date" class="form-control" name="purchase_date[]" required>
            `;
            wrapper.appendChild(div);
            entryCount++;
        }

        // Customer filtering functionality
        function filterCustomers() {
            const input = document.getElementById('customer_search');
            const filter = input.value.toLowerCase();
            const dropdown = document.getElementById('customer_dropdown');
            const options = dropdown.getElementsByTagName('option');

            for (let i = 1; i < options.length; i++) {
                const textValue = options[i].textContent || options[i].innerText;
                options[i].style.display = textValue.toLowerCase().includes(filter) ? '' : 'none';
            }
        }
    </script>

</body>

</html>