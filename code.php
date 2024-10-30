<?php

$botToken = '7557003684:AAG7AXwE-InlL8avPZeNvR-drzxbY-Z_BeY';

// Telegram API URL to get updates
$telegramUrl = "https://api.telegram.org/bot$botToken/getUpdates";

// cURL setup to fetch updates
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $telegramUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute the request and get the response
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
} else {
    $updates = json_decode($response, true);
    echo '<pre>';
    print_r($updates); // This will print the entire update, including the chat ID
    echo '</pre>';
}

// Close the cURL session
curl_close($ch);

?>