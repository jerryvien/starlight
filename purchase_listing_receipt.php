<?php
session_start();
include('config/database.php');
//include('config/utilities.php');

// Set time zone to Kuala Lumpur (GMT +8)
date_default_timezone_set('Asia/Kuala_Lumpur');

// Error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Fetch agent filter if the user is an agent
$agent_filter = ($_SESSION['access_level'] === 'agent') ? $_SESSION['agent_id'] : null;

// Handle AJAX request to generate receipt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_receipt') {
    $customerName = $_POST['customer_name'];
    $purchaseDetails = explode(', ', $_POST['purchase_details']);
    $subtotal = $_POST['subtotal'];
    $agentName = $_POST['agent_name'];
    $serialNumber = $_POST['serial_number'];

    // Format purchase details as an array of arrays for the receipt table
    $purchaseDetailsFormatted = [];
    foreach ($purchaseDetails as $detail) {
        $purchaseDetailsFormatted[] = [
            'number' => $detail,
            'category' => 'N/A', // Add appropriate category here
            'date' => 'N/A', // Add purchase date here
            'amount' => 'N/A' // Add amount here
        ];
    }

    // Call the function to generate the receipt popup
    generateReceiptPopup($customerName, $purchaseDetailsFormatted, $subtotal, $agentName, $serialNumber);
    exit; // End the script after generating the receipt
}

// Fetch purchase entries with customer and agent names
try {
    $query = "
        SELECT pe.serial_number, c.customer_name, aa.agent_name, pe.purchase_datetime,
               GROUP_CONCAT(pe.purchase_no SEPARATOR ', ') as purchase_details,
               SUM(pe.purchase_amount) as subtotal
        FROM purchase_entries pe
        JOIN customers c ON pe.customer_id = c.customer_id
        JOIN admin_access aa ON pe.agent_id = aa.agent_id
    ";
    if ($agent_filter) {
        $query .= " WHERE pe.agent_id = :agent_id";
    }
    $query .= " GROUP BY pe.serial_number ORDER BY pe.purchase_datetime DESC";

    $stmt = $conn->prepare($query);
    if ($agent_filter) {
        $stmt->bindParam(':agent_id', $agent_filter);
    }
    $stmt->execute();
    $purchase_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching purchase entries: " . $e->getMessage());
}

// Function to generate the receipt popup
function generateReceiptPopup($customerName, $purchaseDetails, $subtotal, $agentName, $serialNumber) {
    $transactionDateTime = date('Y-m-d H:i:s');

    // Start building the receipt HTML content
    $receiptContent = "
        <html>
        <head>
            <title>Receipt</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    margin: 20px;
                }
                .receipt-container {
                    max-width: 500px;
                    margin: auto;
                    padding: 20px;
                    border: 1px solid #ddd;
                    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                }
                .header {
                    text-align: center;
                    font-weight: bold;
                    font-size: 18px;
                    margin-bottom: 20px;
                }
                .content {
                    margin-bottom: 15px;
                }
                .footer {
                    text-align: center;
                    margin-top: 20px;
                    font-size: 12px;
                    color: #777;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 15px;
                }
                table, th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                }
                th {
                    background-color: #f4f4f4;
                    text-align: left;
                }
            </style>
        </head>
        <body>
            <div class=\"receipt-container\">
                <div class=\"header\">Receipt</div>
                <div class=\"content\">
                    <strong>Customer Name:</strong> {$customerName}<br>
                    <strong>Agent Name:</strong> {$agentName}<br>
                    <strong>Serial Number:</strong> {$serialNumber}<br>
                    <strong>Transaction Date and Time:</strong> {$transactionDateTime}<br>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Purchase Number</th>
                            <th>Category</th>
                            <th>Purchase Date</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
    ";

    // Loop through purchase details to add rows to the table
    foreach ($purchaseDetails as $detail) {
        $receiptContent .= "
            <tr>
                <td>{$detail['number']}</td>
                <td>{$detail['category']}</td>
                <td>{$detail['date']}</td>
                <td>$" . number_format($detail['amount'], 2) . "</td>
            </tr>
        ";
    }

    // Add the subtotal and footer to the receipt
    $receiptContent .= "
                    </tbody>
                </table>
                <div class=\"content\">
                    <strong>Subtotal:</strong> $" . number_format($subtotal, 2) . "
                </div>
                <div class=\"footer\">
                    All rights reserved © 2024
                </div>
            </div>
        </body>
        </html>
    ";

    // Generate the popup script
    echo "<script type='text/javascript'>
        var popupWindow = window.open('', 'Receipt', 'width=600,height=700');
        popupWindow.document.open();
        popupWindow.document.write(`" . addslashes($receiptContent) . "`);
        popupWindow.document.close();
    </script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Entries Grouped by Serial Number</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
</head>
<body>
<div id="wrapper">
    <!-- Sidebar -->
    <?php include('config/sidebar.php'); ?>

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">
        <!-- Main Content -->
        <div id="content">
            <!-- Topbar -->
            <?php include('config/topbar.php'); ?>

            <div class="container-fluid">
                <h1 class="h3 mb-4 text-gray-800">Purchase Entries Grouped by Serial Number</h1>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Purchase Entries</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Serial Number</th>
                                        <th>Customer Name</th>
                                        <th>Agent Name</th>
                                        <th>Purchase Date</th>
                                        <th>Purchase Details</th>
                                        <th>Subtotal</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="dataBody">
                                    <?php foreach ($purchase_entries as $entry): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($entry['serial_number']); ?></td>
                                            <td><?php echo htmlspecialchars($entry['customer_name']); ?></td>
                                            <td><?php echo htmlspecialchars($entry['agent_name']); ?></td>
                                            <td><?php echo date('d-M-Y', strtotime($entry['purchase_datetime'])); ?></td>
                                            <td><?php echo htmlspecialchars($entry['purchase_details']); ?></td>
                                            <td>$<?php echo number_format($entry['subtotal'], 2); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-info" 
                                                        onclick="reprintReceipt('<?php echo addslashes($entry['customer_name']); ?>', 
                                                                                 '<?php echo addslashes($entry['purchase_details']); ?>', 
                                                                                 '<?php echo number_format($entry['subtotal'], 2); ?>', 
                                                                                 '<?php echo addslashes($entry['agent_name']); ?>', 
                                                                                 '<?php echo addslashes($entry['serial_number']); ?>')">Reprint Receipt</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <?php include('config/footer.php'); ?>
        </div>
    </div>

    <script>
        // AJAX call to generate the receipt
        function reprintReceipt(customerName, purchaseDetails, subtotal, agentName, serialNumber) {
            $.ajax({
                url: '',
                type: 'POST',
                data: {
                    action: 'generate_receipt',
                    customer_name: customerName,
                    purchase_details: purchaseDetails,
                    subtotal: subtotal,
                    agent_name: agentName,
                    serial_number: serialNumber
                },
                success: function(response) {
                    $('body').append(response);
                },
                error: function(xhr, status, error) {
                    alert('Error: ' + error);
                }
            });
        }
    </script>
</body>
</html>
