<?php
session_start();
include('config/database.php'); // Include your database connection
include('config/utilities.php');

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Generate a unique serial number based on computer ID and current datetime
$serial_number = generateSerialNumber();

// Fetch customer data for search filter
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = $_POST['customer_id'];
    $purchase_entries = $_POST['purchase_no'];
    $purchase_category = $_POST['purchase_category'];
    $purchase_amount = $_POST['purchase_amount'];
    $purchase_date = $_POST['purchase_date'];
    
    // Set agent ID based on access level
    $agent_id_to_save = ($_SESSION['access_level'] === 'super_admin') ? $_POST['agent_id'] : $_SESSION['agent_id'];

    // Insert each purchase entry into the database
    for ($i = 0; $i < count($purchase_entries); $i++) {
        $total_price = 0;

        // Calculate total price based on category
        if ($purchase_category[$i] === 'Box') {
            $permutation_factor = calculatePermutationFactor($purchase_entries[$i]);
            $total_price = $purchase_amount[$i] * $permutation_factor;
        } else if ($purchase_category[$i] === 'Straight') {
            $total_price = $purchase_amount[$i];
        }

        $sql = "INSERT INTO purchase_entries (customer_id, agent_id, purchase_no, purchase_category, purchase_amount, purchase_datetime, serial_number) 
                VALUES (:customer_id, :agent_id, :purchase_no, :purchase_category, :purchase_amount, :purchase_datetime, :serial_number)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->bindParam(':agent_id', $agent_id_to_save);
        $stmt->bindParam(':purchase_no', $purchase_entries[$i]);
        $stmt->bindParam(':purchase_category', $purchase_category[$i]);
        $stmt->bindParam(':purchase_amount', $total_price);
        $stmt->bindParam(':purchase_datetime', $purchase_date[$i]);
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
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Purchase Record Entry</title>

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
                    <h1 class="h3 mb-4 text-gray-800">Purchase Record Entry</h1>

                    <!-- Display Serial Number -->
                    <div class="form-group">
                        <label for="serial_number">Serial Number</label>
                        <input type="text" class="form-control" id="serial_number" value="<?php echo $serial_number; ?>" readonly>
                    </div>

                    <!-- Customer Search and Display -->
                    <form method="POST" action="purchase_entry.php">
                        <div class="form-group">
                            <label for="customer_search">Search Customer</label>
                            <input type="text" class="form-control" id="customer_search" placeholder="Start typing to search..." onkeyup="filterCustomers()">
                            <ul id="customer_list" class="list-group mt-2"></ul>
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

                        <!-- Dynamic Purchase Entry Count Selection -->
                        <div class="form-group">
                            <label for="purchase_count">Number of Purchases</label>
                            <select id="purchase_count" class="form-control" onchange="populatePurchaseEntries()" required>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                                <option value="6">6</option>
                                <option value="7">7</option>
                                <option value="8">8</option>
                                <option value="9">9</option>
                                <option value="10">10</option>
                            </select>
                        </div>

                        <!-- Dynamic Purchase Entries -->
                        <div id="purchase_entries_wrapper">
                            <!-- Initially display 1 row by default -->
                            <div class="form-group row">
                                <div class="col-md-3">
                                    <label for="purchase_no_0">Purchase Number</label>
                                    <input type="text" class="form-control" name="purchase_no[]" id="purchase_no_0" pattern="\d{2,3}" title="Please enter a number with 2 or 3 digits" required>
                                </div>
                                <div class="col-md-2">
                                    <label for="purchase_category_0">Category</label>
                                    <select class="form-control" name="purchase_category[]">
                                        <option value="Box">Box</option>
                                        <option value="Straight">Straight</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="purchase_amount_0">Amount</label>
                                    <input type="number" class="form-control" name="purchase_amount[]" required>
                                </div>
                                <div class="col-md-2">
                                    <label for="total_price_0">Total Price</label>
                                    <input type="text" class="form-control" name="total_price[]" id="total_price_0" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label for="purchase_date_0">Purchase Date</label>
                                    <input type="date" class="form-control" name="purchase_date[]" id="purchase_date_0" required>
                                </div>

                                <script>
                                // Set the default date to today for the initial column
                                const today = new Date().toISOString().split('T')[0];
                                document.getElementById('purchase_date_0').value = today;
                                </script>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-success mt-3">Submit Purchase Entry</button>
                    </form>
                </div>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>

    <script>
        const customers = <?php echo json_encode($customers); ?>;

        // Filter and display customer list
        function filterCustomers() {
            const searchValue = document.getElementById('customer_search').value.toLowerCase();
            const customerList = document.getElementById('customer_list');
            customerList.innerHTML = ''; // Clear previous list

            customers.forEach(function(customer) {
                if (customer.customer_name.toLowerCase().includes(searchValue)) {
                    const li = document.createElement('li');
                    li.classList.add('list-group-item');
                    li.textContent = customer.customer_name;
                    li.onclick = function() {
                        selectCustomer(customer.customer_id, customer.customer_name);
                    };
                    customerList.appendChild(li);
                }
            });
        }

        // Select customer and hide list
        function selectCustomer(customerId, customerName) {
            document.getElementById('customer_search').value = customerName;
            document.getElementById('customer_list').innerHTML = '';
            const customerField = `<input type="hidden" name="customer_id" value="${customerId}">`;
            document.getElementById('purchase_entries_wrapper').insertAdjacentHTML('beforebegin', customerField);
        }


        // Function to populate dynamic entry field 
        function populatePurchaseEntries() {
            const count = parseInt(document.getElementById('purchase_count').value);
            const wrapper = document.getElementById('purchase_entries_wrapper');
            wrapper.innerHTML = ''; // Clear existing entries

            // Get today's date in 'YYYY-MM-DD' format
            const today = new Date().toISOString().split('T')[0];

            for (let i = 0; i < count; i++) {
                // Create a new row
                const row = document.createElement('div');
                row.classList.add('form-group', 'row');

                // Purchase Number Field
                const col1 = document.createElement('div');
                col1.classList.add('col-md-3');
                col1.innerHTML = `
                    <label for="purchase_no_${i}">Purchase Number</label>
                    <input type="text" class="form-control" name="purchase_no[]" id="purchase_no_${i}" pattern="\\d{2,3}" title="Please enter a number with 2 or 3 digits" required>
                `;
                
                // Purchase Category Field
                const col2 = document.createElement('div');
                col2.classList.add('col-md-2');
                col2.innerHTML = `
                    <label for="purchase_category_${i}">Category</label>
                    <select class="form-control" name="purchase_category[]">
                        <option value="Box">Box</option>
                        <option value="Straight">Straight</option>
                    </select>
                `;

                // Purchase Amount Field
                const col3 = document.createElement('div');
                col3.classList.add('col-md-3');
                col3.innerHTML = `
                    <label for="purchase_amount_${i}">Amount</label>
                    <input type="number" class="form-control" name="purchase_amount[]" required>
                `;

                // Purchase Date Field
                const col4 = document.createElement('div');
                col4.classList.add('col-md-4');
                const dateInput = document.createElement('input');
                dateInput.type = 'date';
                dateInput.classList.add('form-control');
                dateInput.name = 'purchase_date[]';
                dateInput.id = `purchase_date_${i}`;
                dateInput.required = true;
                dateInput.value = today;  // Set today's date as default
                col4.innerHTML = `<label for="purchase_date_${i}">Purchase Date</label>`;
                col4.appendChild(dateInput);  // Append the input to the column

                // Total Price Field
                const col5 = document.createElement('div');
                col5.classList.add('col-md-2');
                col5.innerHTML = `
                    <label for="total_price_${i}">Total Price</label>
                    <input type="text" class="form-control" name="total_price[]" id="total_price_${i}" readonly>
                `;

                // Append all columns to the row
                row.appendChild(col1);
                row.appendChild(col2);
                row.appendChild(col3);
                row.appendChild(col4);

                // Append the row to the wrapper
                wrapper.appendChild(row);
            }
        }


    </script>
</body>
</html>
