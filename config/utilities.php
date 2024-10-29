<?php
function generateSerialNumber() {
    $key = "KENSTARLIGHT"; // Encryption key
    $random_number = rand(100000, 999999);
    $serial_number = substr(md5($key . $random_number), 0, 12);
    return strtoupper($serial_number);
}

function format_number_short($number) {
    if ($number >= 1000000) {
        return number_format($number / 1000000, 1) . 'M'; // 1M for millions
    } elseif ($number >= 1000) {
        return number_format($number / 1000, 1) . 'k'; // 1k for thousands
    }
    return $number; // No formatting for numbers less than 1000
}

function getUserIP() {
    if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}


function generateReceiptPopup($customerName, $purchaseDetails, $subtotal, $agentName, $serialNumber) {
    $transactionDateTime = date('Y-m-d H:i:s');

    

    $html = "

    <style>
        .receipt-container {
            max-width: 500px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Adds shadow */
            position: relative;
            background-color: rgba(255, 255, 255, 0.8); /* Makes the background slightly opaque */
            align-self: flex-start; /* Aligns the container to the left */
        }

        /* Add the background image */
        .receipt-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('/img/about.jpg'); /* Replace with your image path */
            background-size: cover;
            background-position: center;
            opacity: 0.3; /* Set opacity to 30% */
            z-index: -1; /* Set behind the content */
        }

        .receipt-content {
            position: relative; /* Ensure text appears above the background image */
            z-index: 1;
        }
    </style>
    
        <div class='receipt-container' style='max-width: 600px; margin: 20px auto;'>
        <div class='header' style='text-align: left; font-weight: bold; font-size: 18px; margin-bottom: 20px;'>RECEIPT</div>
        <div class='content' style='margin-bottom: 15px;'>
            <strong>Customer Name:</strong> {$customerName}<br>
            <strong>Agent Name:</strong> {$agentName}<br>
            <strong>Serial Number:</strong> {$serialNumber}<br>
            <strong>Transaction Date and Time:</strong> {$transactionDateTime}<br>
        </div>
        <table style='width: 100%; border-collapse: collapse; margin-bottom: 15px;'>
            <thead>
                <tr>
                    <th style='border: 1px solid #ddd; padding: 8px; background-color: #f4f4f4; text-align: left;'>Purchase Number</th>
                    <th style='border: 1px solid #ddd; padding: 8px; background-color: #f4f4f4; text-align: left;'>Category</th>
                    <th style='border: 1px solid #ddd; padding: 8px; background-color: #f4f4f4; text-align: left;'>Purchase Date</th>
                    <th style='border: 1px solid #ddd; padding: 8px; background-color: #f4f4f4; text-align: left;'>Amount</th>
                </tr>
            </thead>
            <tbody>
    ";

    foreach ($purchaseDetails as $detail) {
    $html .= "
        <tr>
            <td style='border: 1px solid #ddd; padding: 8px;'>{$detail['number']}</td>
            <td style='border: 1px solid #ddd; padding: 8px;'>{$detail['category']}</td>
            <td style='border: 1px solid #ddd; padding: 8px;'>{$detail['date']}</td>
            <td style='border: 1px solid #ddd; padding: 8px;'>$" . number_format($detail['amount'], 2) . "</td>
        </tr>
    ";
    }

    $html .= "
            </tbody>
        </table>
        <div class='content' style='margin-top: 15px;'>
            <strong>Subtotal:</strong> $" . number_format($subtotal, 2) . "
        </div>
        <div class='footer' style='text-align: center; margin-top: 20px; font-size: 12px; color: #777;'>
            All rights reserved © 2024
        </div>
    </div>
    ";


    // Send the email with the receipt content
    $to = "sales@navbright.tech"; // Replace with your backup email address
    $subject = "Purchase Receipt Backup - Serial No: $serialNumber | $transactionDateTime | $agentName";
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@navbright.tech"; // Replace with your email

    // Send the email
    mail($to, $subject, $html, $headers);

    // Return the receipt HTML
    return $html;
    
}

?>

