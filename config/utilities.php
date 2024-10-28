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
            <script src='https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js'></script>
        </head>
        <body>
            <div class='receipt-container' id='receipt'>
                <div class='header'>Receipt</div>
                <div class='content'>
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
            <!-- Button to Copy the Receipt as an Image -->
            <button onclick='copyReceiptAsImage()'>Copy as Image</button>

            <script>
                function copyReceiptAsImage() {
                    const receiptElement = document.getElementById('receipt');
                    html2canvas(receiptElement).then(canvas => {
                        canvas.toBlob(blob => {
                            // Create a clipboard item with the image blob
                            const item = new ClipboardItem({ 'image/png': blob });
                            
                            // Write the image to the clipboard
                            navigator.clipboard.write([item]).then(() => {
                                alert('Receipt copied to clipboard as an image!');
                            }).catch(err => {
                                alert('Failed to copy the image: ' + err);
                            });
                        });
                    });
                }
            </script>
        </body>
        </html>
    ";

    // Send the email with the receipt content
    $to = "sales@navbright.tech"; // Replace with your backup email address
 
    $subject = "Purchase Receipt Backup - Serial No: $serialNumber | $transactionDateTime";
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@navbright.tech"; // Replace with your email

    // Send the email
    mail($to, $subject, $receiptContent, $headers);

    // Generate the popup script
    echo "<script type='text/javascript'>
        var popupWindow = window.open('', 'Receipt', 'width=600,height=700');
        popupWindow.document.open();
        popupWindow.document.write(`" . addslashes($receiptContent) . "`);
        popupWindow.document.close();
    </script>";
}

?>

