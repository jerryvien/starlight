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

function isValidSerialNumber($providedSerialNumber) {
    $key = "KENSTARLIGHT"; // Your encryption key

    // Iterate through the possible random numbers used for serial generation
    for ($random_number = 100000; $random_number <= 999999; $random_number++) {
        // Generate the serial number based on the secret key and current random number
        $generatedSerialNumber = substr(md5($key . $random_number), 0, 12);
        
        // Check if the generated serial number matches the provided one
        if (strtoupper($generatedSerialNumber) === strtoupper($providedSerialNumber)) {
            return true; // Valid serial number
        }
    }

    return false; // Invalid serial number
}


function generateReceiptPopup($customerName, $purchaseDetails, $subtotal, $agentName, $serialNumber) {
    $transactionDateTime = date('Y-m-d H:i:s');

    $html = "
      <style>
        .receipt-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Adds shadow */
            position: relative;
            background-color: rgba(255, 255, 255, 0.8); /* Makes the background slightly opaque */
            overflow: hidden; /* Ensures watermark stays inside */
            align-self: flex-start; /* Aligns the container to the left */
        }
      </style>

      <div class='receipt-container'>
          <!-- Receipt Header -->
          <div class='header' style='text-align: left; font-weight: bold; font-size: 18px; margin-bottom: 20px;'>RECEIPT</div>

          <!-- Receipt Content -->
            <div class='content' style='margin-bottom: 15px; display: flex; flex-direction: column;'>
                <!-- Use a wrapper to align labels and values -->
                <div style='display: flex; align-items: baseline; margin-bottom: 5px;'>
                    <span style='width: 150px; font-weight: bold;'>Customer Name:</span>
                    <span>{$customerName}</span>
                </div>
                <div style='display: flex; align-items: baseline; margin-bottom: 5px;'>
                    <span style='width: 150px; font-weight: bold;'>Agent Name:</span>
                    <span>{$agentName}</span>
                </div>
                <div style='display: flex; align-items: baseline; margin-bottom: 5px;'>
                    <span style='width: 150px; font-weight: bold;'>Serial Number:</span>
                    <span>{$serialNumber}</span>
                </div>
                <div style='display: flex; align-items: baseline;'>
                    <span style='width: 150px; font-weight: bold;'>Transacted:</span>
                    <span>{$transactionDateTime}</span>
                </div>
            </div>

          <!-- Receipt Table -->
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

    // Loop through purchase details to add rows to the table
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

          <!-- Subtotal -->
          <div class='content' style='margin-top: 15px;'>
              <strong>Subtotal:</strong> $" . number_format($subtotal, 2) . "
          </div>

          <!-- Footer -->
          <div class='footer' style='text-align: center; margin-top: 20px; font-size: 12px; color: #777;'>
              All rights reserved © 2024
          </div>
      </div>
    ";

    // Send the message to Telegram
    $url = "https://api.telegram.org/bot7557003684:AAG7AXwE-InlL8avPZeNvR-drzxbY-Z_BeY/sendMessage";
            
    // Start building the Telegram message
    $telegram = "<strong>OFFICIAL RECEIPT</strong>\n";
    $telegram .= "```\n"; // Start of the code block for fixed-width formatting

    // Define column widths
    $colWidth1 = 20; // Width for labels
    $colWidth2 = 25; // Width for values

    // Add customer and transaction details in a table format
    $telegram .= sprintf("%-{$colWidth1}s : %-{$colWidth2}s\n", "Customer Name", $customerName);
    $telegram .= sprintf("%-{$colWidth1}s : %-{$colWidth2}s\n", "Agent Name", $agentName);

    // Bold the serial number and maintain alignment
    $telegram .= sprintf("%-{$colWidth1}s : <strong>%-{$colWidth2}s</strong>\n", "Serial Number", $serialNumber);

    $telegram .= sprintf("%-{$colWidth1}s : %-{$colWidth2}s\n", "Transacted", $transactionDateTime);
    $telegram .= sprintf("%-{$colWidth1}s : $%-{$colWidth2}s\n", "Subtotal", number_format($subtotal, 2));

    $telegram .= "```\n"; // End of the code block for receipt details

    // Add headers to the purchase details table
    $telegram .= "<strong>Purchase Details:</strong>\n";
    $telegram .= "```\n"; // Start of the code block for purchase details

    // Define fixed column headers for purchase details
    $telegram .= sprintf("%-15s %-20s %-15s %-10s\n", "Item Code", "Category", "Date", "Amount");
    $telegram .= str_repeat("-", 60) . "\n"; // Separator line

    // Add purchase details to the table with fixed-width formatting
    foreach ($purchaseDetails as $detail) {
        $telegram .= sprintf(
            "%-15s %-20s %-15s %-10s\n",
            $detail['number'],
            $detail['category'],
            $detail['date'],
            "$" . number_format($detail['amount'], 2)
        );
    }

    $telegram .= "```\n"; // End of the code block for purchase details



    // Set up the message data
    $data = [
        'chat_id' => '-1002250872376',
        'text' => $telegram,
        'parse_mode' => 'HTML' // Allows basic HTML formatting
    ];

   // Use cURL to send the message to Telegram
   $ch = curl_init();
   curl_setopt($ch, CURLOPT_URL, $url);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   curl_setopt($ch, CURLOPT_POST, true);
   curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
   
   $response = curl_exec($ch);
   curl_close($ch);

    // Return the receipt HTML
    return $html;
}





// Function to update record_creation_datetime and other timestamp columns to GMT+8 and set timezone_updated to 1 for all tables
function updateTimeZoneToGMT8($conn) {
    try {
        // Set the time zone for the current session to Malaysia time (GMT+8)
        $conn->exec("SET time_zone = '+08:00'");

        // Array of tables and columns to update
        $tablesToUpdate = [
            "purchase_entries" => ["record_creation_datetime"],
            "admin_access" => ["created_at", "updated_at"],
            "admin_access_change_log" => ["change_time"],
            "customer_details" => ["created_at", "updated_at"],
            "winning_record" => ["created_at", "last_updated"],
            "user_activity_log" => ["login_time", "created_at"]
        ];

        // Loop through each table and update the specified columns
        foreach ($tablesToUpdate as $table => $columns) {
            foreach ($columns as $column) {
                // SQL query to update each column and set timezone_updated to 1
                $sql = "UPDATE $table 
                        SET $column = DATE_ADD($column, INTERVAL 8 HOUR), 
                            timezone_updated = 1
                        WHERE $column IS NOT NULL AND timezone_updated != 1";

                // Prepare and execute the query
                $stmt = $conn->prepare($sql);
                $stmt->execute();
            }
        }

        // Return success message
        return "Records updated successfully for all tables.";
    } catch (PDOException $e) {
        // Return error message if an exception occurs
        return "Error: " . $e->getMessage();
    }
}



?>

