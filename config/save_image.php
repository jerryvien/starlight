<?php
include('config/utilities.php');

// Example data for testing
$customerName = "Elizabeth Davis";
$purchaseDetails = [
    ["number" => "663", "category" => "Box", "date" => "2024-10-28", "amount" => 3.00],
];
$subtotal = 3.00;
$agentName = "Dragon";
$serialNumber = "18B0776D5CA9";

// Generate the receipt HTML
$receiptHTML = generateReceiptPopup($customerName, $purchaseDetails, $subtotal, $agentName, $serialNumber);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt</title>
    <style>
        /* Add your receipt CSS here */
        .receipt-container {
            max-width: 100%;
            margin: auto;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }
        /* Additional styles for responsiveness and layout */
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="utils.js"></script> <!-- Include the utility JS file -->
</head>
<body>

    <!-- Display the Receipt HTML -->
    <?php echo $receiptHTML; ?>

    <!-- Copy as Image Button -->
    <button onclick="copyElementAsImage('receipt')" style="margin: 20px; padding: 10px 20px; font-size: 16px;">
        Copy as Image
    </button>

</body>
</html>
