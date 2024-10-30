<?php

$botToken = '7557003684:AAG7AXwE-InlL8avPZeNvR-drzxbY-Z_BeY';
$chatId = '-1002250872376'; // Your private channel ID
$message = "Hello, this is a test message to your private channel!";

// Telegram API URL
$telegramUrl = "https://api.telegram.org/bot$botToken/sendMessage";

// Data to send
$data = [
    'chat_id' => $chatId,
    'text' => $message,
];

// cURL setup to send the message
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $telegramUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute the request and get the response
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
} else {
    echo 'Message sent successfully!';
}

// Close the cURL session
curl_close($ch);

?>