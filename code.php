
<?php

function generateReceiptPopup($customerName, $purchaseDetails, $subtotal, $agentName, $serialNumber) {
    $transactionDateTime = date('Y-m-d H:i:s');

    // Format the HTML for Telegram
    $html = "
        <strong>RECEIPT</strong>\n
        <strong>Customer Name:</strong> {$customerName}\n
        <strong>Agent Name:</strong> {$agentName}\n
        <strong>Serial Number:</strong> {$serialNumber}\n
        <strong>Transacted:</strong> {$transactionDateTime}\n
        <strong>Subtotal:</strong> $" . number_format($subtotal, 2) . "\n
    ";

    // Add purchase details to the Telegram message
    $html .= "\n<strong>Purchase Details:</strong>\n";
    foreach ($purchaseDetails as $detail) {
        $html .= "Purchase Number: {$detail['number']}, ";
        $html .= "Category: {$detail['category']}, ";
        $html .= "Date: {$detail['date']}, ";
        $html .= "Amount: $" . number_format($detail['amount'], 2) . "\n";
    }

    // Send the message to Telegram
    $url = "https://api.telegram.org/bot7557003684:AAG7AXwE-InlL8avPZeNvR-drzxbY-Z_BeY/sendMessage";
    
    // Set up the message data
    $data = [
        'chat_id' => '-1002250872376',
        'text' => $html,
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

    // Return response for debugging
    return $response;
}

// Example usage
$telegramToken = '7557003684:AAG7AXwE-InlL8avPZeNvR-drzxbY-Z_BeY'; // Replace with your Telegram bot token
$chatId = '-1002250872376'; // Replace with your channel or chat ID

$customerName = "John Doe";
$agentName = "Agent Smith";
$serialNumber = "ABC12345";
$subtotal = 250.00;
$purchaseDetails = [
    ['number' => '001', 'category' => 'Box', 'date' => '2024-10-27', 'amount' => 100],
    ['number' => '002', 'category' => 'Straight', 'date' => '2024-10-27', 'amount' => 150]
];

$response = generateReceiptPopup($customerName, $purchaseDetails, $subtotal, $agentName, $serialNumber, $telegramToken, $chatId);
echo $response;

?>
