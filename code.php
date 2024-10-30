<?php
function sendImageToTelegram($chatId) {
    $telegramToken = '7557003684:AAG7AXwE-InlL8avPZeNvR-drzxbY-Z_BeY';
    $url = "https://api.telegram.org/bot$telegramToken/sendPhoto";

    $postData = [
        'chat_id' => $chatId,
        'photo' => new CURLFile($imagePath)
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

// Example usage (after generating the image file)
$imagePath = __DIR__ . '/img/logo.png'; // Provide the correct path
$chatId = '2001353148'; // Replace with your Telegram chat ID

$response = sendImageToTelegram($chatId);
echo $response;
?>