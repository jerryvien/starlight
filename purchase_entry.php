<?php
session_start();
include('config/database.php'); // Include your database connection
include('config/utilities.php');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set default timezone to ensure consistency
date_default_timezone_set('Asia/Singapore'); // GMT +8 timezone


// Generate form token if not already set
if (!isset($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32)); // Generate a random token
}


// Get current hour and minute in HH:MM format
$current_time = date('H:i');

// Define start and cutoff times
$start_time = '00:00';
$cutoff_time = '23:55';

// Variable to determine if access is allowed
$access_allowed = true;

if ($current_time < $start_time || $current_time > $cutoff_time) {
    $access_allowed = false;
}

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

    if (!isset($_POST['form_token']) || $_POST['form_token'] !== $_SESSION['form_token']) {
        die("Invalid form submission."); // Stop duplicate submission
    }

    // Invalidate the form token after successful submission
    unset($_SESSION['form_token']);


    $customer_id = $_POST['customer_id'];
    $customer_name = '';
    foreach ($customers as $customer) {
        if ($customer['customer_id'] == $customer_id) {
            $customer_name = $customer['customer_name'];
            break;
        }
    }

    $purchase_entries = $_POST['purchase_no'];
    $purchase_category = $_POST['purchase_category'];
    $purchase_amount = $_POST['purchase_amount'];
    $purchase_date = $_POST['purchase_date'];

    // Set agent ID based on access level
    $agent_id_to_save = ($_SESSION['access_level'] === 'super_admin') ? $_POST['agent_id'] : $_SESSION['agent_id'];
    $agent_name = '';
    foreach ($agents as $agent) {
        if ($agent['agent_id'] == $agent_id_to_save) {
            $agent_name = $agent['agent_name'];
            break;
        }
    }

    $purchaseDetails = [];
    $subtotal = 0;

    // Insert each purchase entry into the database
    for ($i = 0; $i < count($purchase_entries); $i++) {
        $total_price = 0;

        // Calculate total price based on category
        if ($purchase_category[$i] === 'Box') {
            $permutation_factor = calculatePermutationFactor($purchase_entries[$i]);
            $total_price = $purchase_amount[$i] * $permutation_factor;
        } else if ($purchase_category[$i] === 'Straight') {
            $total_price = $purchase_amount[$i]; // Straight amount stays the same
        }

        $subtotal += $total_price;
        $purchaseDetails[] = [
            'number' => $purchase_entries[$i], 
            'category' => $purchase_category[$i],
            'amount' => $total_price,
            'date' => $purchase_date[$i]
        ];

        $sql = "INSERT INTO purchase_entries (customer_id, agent_id, purchase_no, purchase_category, purchase_amount, purchase_datetime, serial_number) 
                VALUES (:customer_id, :agent_id, :purchase_no, :purchase_category, :purchase_amount, :purchase_datetime, :serial_number)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->bindParam(':agent_id', $agent_id_to_save);
        $stmt->bindParam(':purchase_no', $purchase_entries[$i]);
        $stmt->bindParam(':purchase_category', $purchase_category[$i]);
        $stmt->bindParam(':purchase_amount', $total_price); // Store total price in purchase_amount
        $stmt->bindParam(':purchase_datetime', $purchase_date[$i]);
        $stmt->bindParam(':serial_number', $serial_number);
        $stmt->execute();

        // Update the updated_at field in customer_details table
        try {
            // First, get the current purchase_history_count for the customer
            $getCountSQL = "SELECT purchase_history_count FROM customer_details WHERE customer_id = :customer_id";
            $countStmt = $conn->prepare($getCountSQL);
            $countStmt->bindParam(':customer_id', $customer_id);
            $countStmt->execute();
        
            $result = $countStmt->fetch(PDO::FETCH_ASSOC);
        
            if ($result) {
                $currentCount = $result['purchase_history_count'];
        
                // Increment the count by 1
                $newCount = $currentCount + 1;
        
                // Update the purchase_history_count and updated_at fields in customer_details table
                $updateCustomerSQL = "UPDATE customer_details 
                                      SET updated_at = NOW(), 
                                          purchase_history_count = :newCount 
                                      WHERE customer_id = :customer_id";
                $updateStmt = $conn->prepare($updateCustomerSQL);
                $updateStmt->bindParam(':newCount', $newCount);
                $updateStmt->bindParam(':customer_id', $customer_id);
                $updateStmt->execute();
            }
        } catch (PDOException $e) {
            echo "Error updating customer details: " . $e->getMessage();
        }
        
    }

  

    // Store the success message in session
    $_SESSION['success_message'] = "Purchase entries added successfully with serial number: $serial_number";

    // Call the generateReceiptPopup function to show the receipt
    $receiptHTML = generateReceiptPopup($customer_name, $purchaseDetails, $subtotal, $agent_name, $serial_number);
}

// Function to calculate permutation factor for "Box"
function calculatePermutationFactor($purchase_no) {
    $unique_digits = count(array_unique(str_split($purchase_no)));
    switch ($unique_digits) {
        case 3: return 6; // 123 -> 6 combinations
        case 2: return 3; // 223 -> 3 combinations
        case 1: return 1; // 111 -> 1 combination
        default: return 1;
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

    <title>Purchase Record Entry</title>

    <!-- Custom fonts and styles for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <link href="css/bootstrap.min.css" rel="stylesheet"> <!-- Ensure Bootstrap is correctly linked -->

</head>

<body id="page-top">
    <div id="wrapper">
        <?php include('config/sidebar.php'); ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include('config/topbar.php'); ?>

                <?php if (!$access_allowed): ?>
                    <style>
                        .warning-popup {
                            margin: 50px auto;
                            background-color: #ffdddd;
                            border: 2px solid #f44336;
                            padding: 20px;
                            box-shadow: 0 0 10px rgba(0,0,0,0.5);
                            font-family: Arial, sans-serif;
                            text-align: center;
                            max-width: 600px;
                            z-index: 1000;
                        }
                        .warning-popup h3 {
                            color: #f44336;
                            margin-bottom: 15px;
                        }
                        .warning-popup p {
                            color: #333;
                            font-size: 14px;
                            text-align: justify;
                        }
                        .warning-popup .footer {
                            margin-top: 15px;
                            font-size: 12px;
                            color: #777;
                        }
                    </style>
                    <div class='warning-popup'>
                        <h3>Access Denied</h3>
                        <p>The system is currently closed for transactions as the results are being populated.<br>
                        Page accessibility is available every day from <b>00:00</b> until <b>18:55</b>.<br>
                        The current system time is: <b><?php echo $current_time; ?></b>.<br>
                        The system time is final, and no exceptions will be accepted. Any record found with fraud or timezone manipulation will result in termination of agent rights, and the transaction will be void.</p>
                        <div class='footer'>All rights reserved © 2024</div>
                    </div>
                
                <?php else: ?>
                    <div class="container-fluid">
                        <h1 class="h3 mb-4 text-gray-800">Purchase Record Entry</h1>
                        <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">

                        
                            <!-- Display success message if it exists -->
                            <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success">
                                <?php echo $_SESSION['success_message']; ?>
                                <?php unset($_SESSION['success_message']); // Clear message after displaying ?>
                            </div>
                            <?php endif; ?>
                             <!-- Display the receipt if generated -->
                             <?php if (!empty($receiptHTML)): ?>
                                <div id="receipt-section" class="text-left align-items-left mt-4">
                                    <!-- Rendered Receipt -->
                                    <?php echo $receiptHTML; ?>
                                
                                    <!-- Copy Receipt Section -->
                                    <div class="d-flex justify-content-center align-items-center mt-3">
                                        <!-- Label Text -->
                                        <span class="mr-2">Copy Receipt</span>
                                        
                                        <!-- Copy Icon Button -->
                                        <button id="copy-image-btn" class="btn btn-link" style="font-size: 24px; color: #007bff;" title="Copy as Image">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <button id="download-image-btn" class="btn btn-link ml-2" style="font-size: 24px; color: #007bff;" title="Download as Image">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        <!-- Copy Notification -->
                                        <div id="copy-notification" class="alert alert-success" style="display: none; position: fixed; top: 400px; left: 50%; transform: translateX(-50%); z-index: 1000; opacity: 0.9; background-color: rgba(72, 187, 120, 0.5); color: #fff; padding: 10px 20px; border-radius: 5px;">
                                            Receipt copied to clipboard as an image!
                                        </div>
                                        <div id="download-notification" class="alert alert-success" style="display: none; position: fixed; top: 400px; right: 20px; z-index: 1000; opacity: 0.8; background: rgba(0, 128, 0, 0.5); color: white; padding: 10px; border-radius: 5px;">
                                            Receipt saved as an image!
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <script>
                                document.addEventListener("DOMContentLoaded", function() {
                                    const copyButton = document.getElementById("copy-image-btn");
                                    const notification = document.getElementById("copy-notification");

                                    if (copyButton) {
                                        copyButton.addEventListener("click", function() {
                                            const receiptElement = document.querySelector(".receipt-container");

                                            if (receiptElement) {
                                                html2canvas(receiptElement).then(canvas => {
                                                    canvas.toBlob(blob => {
                                                        if (blob) {
                                                            const item = new ClipboardItem({ 'image/png': blob });
                                                            navigator.clipboard.write([item]).then(() => {
                                                                // Show notification
                                                                showNotification();
                                                            }).catch(err => {
                                                                console.error("Failed to copy image:", err);
                                                                alert("Failed to copy image.");
                                                            });
                                                        } else {
                                                            alert("Failed to generate image.");
                                                        }
                                                    });
                                                }).catch(err => {
                                                    console.error("Error generating image:", err);
                                                    alert("Error generating image.");
                                                });
                                            } else {
                                                alert("Receipt element not found.");
                                            }
                                        });
                                    }

                                    // Function to show the notification
                                    function showNotification() {
                                        notification.style.display = 'block';
                                        notification.style.opacity = 0.9;

                                        // Fade out after 3 seconds
                                        setTimeout(() => {
                                            fadeOut(notification);
                                        }, 3000);
                                    }

                                    // Function to fade out the notification smoothly
                                    function fadeOut(element) {
                                        let opacity = 0.9;
                                        const fadeEffect = setInterval(() => {
                                            if (opacity <= 0) {
                                                clearInterval(fadeEffect);
                                                element.style.display = 'none';
                                            } else {
                                                opacity -= 0.1;
                                                element.style.opacity = opacity;
                                            }
                                        }, 100); // Adjust speed of fading (0.1 decrease every 100ms)
                                    }
                                });

                                document.addEventListener("DOMContentLoaded", function() {
                                    const downloadButton = document.getElementById("download-image-btn");

                                    if (downloadButton) {
                                        downloadButton.addEventListener("click", function() {
                                            const receiptElement = document.querySelector(".receipt-container");

                                            if (receiptElement) {
                                                // Convert the receipt to an image using html2canvas
                                                html2canvas(receiptElement).then(canvas => {
                                                    // Create a temporary link to download the image
                                                    const link = document.createElement('a');
                                                    link.href = canvas.toDataURL("image/png");
                                                    link.download = "receipt.png";  // Name of the image file
                                                    link.click();

                                                    // Show temporary notification
                                                    const notification = document.getElementById("download-notification");
                                                    if (notification) {
                                                        notification.style.display = "block";
                                                        setTimeout(() => {
                                                            notification.style.display = "none";
                                                        }, 3000); // 3-second fade out
                                                    }
                                                }).catch(err => {
                                                    console.error("Error generating image:", err);
                                                    alert("Error generating image.");
                                                });
                                            } else {
                                                alert("Receipt element not found.");
                                            }
                                        });
                                    }
                                });
                            </script>
                        <!-- Customer Search and Display -->
                        <form id="purchaseForm" method="POST" action="purchase_entry.php">
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
                                        <input type="text" class="form-control" name="purchase_no[]" id="purchase_no_0" " title="Please enter a number with 2 or 3 digits" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="purchase_category_0">Category</label>
                                        <select class="form-control" name="purchase_category[]" id="purchase_category_0" onchange="calculateTotalPrice(0)">
                                            <option value="Box">Box</option>
                                            <option value="Straight">Straight</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="purchase_amount_0">Amount</label>
                                        <input type="number" class="form-control" name="purchase_amount[]" id="purchase_amount_0" oninput="calculateTotalPrice(0)" required>
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
                            <button type="submit" id="submitBtn123" class="btn btn-success mt-3">Submit Purchase Entry</button>
                            <script>
                                document.addEventListener("DOMContentLoaded", function() {
                                    const form = document.getElementById("purchaseForm");
                                    const submitBtn = document.getElementById("submitBtn");

                                    if (form && submitBtn) {
                                        form.addEventListener("submit", function(e) {
                                            // Disable submit button after the form is submitted
                                            submitBtn.disabled = true;
                                            submitBtn.textContent = "Processing...";

                                            // Add a slight delay to ensure the form doesn't submit before the button is disabled
                                        setTimeout(() => {
                                            form.submit(); // Ensure the form submits
                                        }, 100);
                                        });
                                    }
                                });
                            </script>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <?php include('config/footer.php'); ?>
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
                    <input type="text" class="form-control" name="purchase_no[]" id="purchase_no_${i}" " title="Please enter a number with 2 or 3 digits" required>
                `;
                
                // Purchase Category Field
                const col2 = document.createElement('div');
                col2.classList.add('col-md-2');
                col2.innerHTML = `
                    <label for="purchase_category_${i}">Category</label>
                    <select class="form-control" name="purchase_category[]" id="purchase_category_${i}" onchange="calculateTotalPrice(${i})">
                        <option value="Box">Box</option>
                        <option value="Straight">Straight</option>
                    </select>
                `;

                // Purchase Amount Field
                const col3 = document.createElement('div');
                col3.classList.add('col-md-3');
                col3.innerHTML = `
                    <label for="purchase_amount_${i}">Amount</label>
                    <input type="number" class="form-control" name="purchase_amount[]" id="purchase_amount_${i}" oninput="calculateTotalPrice(${i})" required>
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
                col4.appendChild(dateInput);

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
                row.appendChild(col5);

                // Append the row to the wrapper
                wrapper.appendChild(row);

                // Set default date and initialize total price calculation
                calculateTotalPrice(i);
            }
        }

        // Function to calculate total price based on category and amount
        function calculateTotalPrice(index) {
            const categoryElement = document.getElementById(`purchase_category_${index}`);
            const amountElement = document.getElementById(`purchase_amount_${index}`);
            const purchaseNoElement = document.getElementById(`purchase_no_${index}`);
            const totalPriceElement = document.getElementById(`total_price_${index}`);

            let amount = parseFloat(amountElement.value) || 0;
            let totalPrice = 0;

            // Apply permutation factor based on the category
            if (categoryElement.value === 'Box') {
                const permutationFactor = calculatePermutationFactor(purchaseNoElement.value);
                totalPrice = amount * permutationFactor;
            } else {
                totalPrice = amount; // "Straight" uses the same amount
            }

            totalPriceElement.value = totalPrice.toFixed(2); // Update total price
        }

        // Function to calculate permutation factor for "Box" based on the unique digits
        function calculatePermutationFactor(purchaseNo) {
            const digitCounts = {};
            [...purchaseNo.toString()].forEach(digit => {
                digitCounts[digit] = (digitCounts[digit] || 0) + 1;
            });

            const numDigits = purchaseNo.length;
            let numerator = factorial(numDigits);
            let denominator = 1;
            Object.values(digitCounts).forEach(count => {
                denominator *= factorial(count);
            });

            return numerator / denominator; // Using real division to handle non-integer cases
        }

        // Helper function to calculate factorial
        function factorial(n) {
            return n ? n * factorial(n - 1) : 1;
        }

        
    </script>
</body>

</html>

<?php

?>