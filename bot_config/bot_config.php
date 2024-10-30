<?php

function sendHtmlAsImageToTelegram($htmlContent, $telegramToken, $chatId) {
    // 1. Convert HTML to an image using GD
    $image = imagecreatetruecolor(600, 800); // Adjust the size as needed
    $bgColor = imagecolorallocate($image, 255, 255, 255); // White background
    imagefilledrectangle($image, 0, 0, 600, 800, $bgColor);

    // Load the HTML content into GD
    $font = __DIR__ . '/arial.ttf'; // Ensure you have a TTF font file available
    $black = imagecolorallocate($image, 0, 0, 0);
    imagettftext($image, 12, 0, 10, 20, $black, $font, strip_tags($htmlContent));

    // Save the image as a temporary file
    $imagePath = '/tmp/receipt_image.png';
    imagepng($image, $imagePath);
    imagedestroy($image);

    // 2. Send the image to Telegram
    $url = "https://api.telegram.org/bot$telegramToken/sendPhoto";
    
    $postFields = [
        'chat_id' => $chatId,
        'photo' => new CURLFile($imagePath)
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

    $response = curl_exec($ch);
    curl_close($ch);

    // Remove the temporary image file
    unlink($imagePath);

    return $response;
}

// Example usage
$telegramToken = 'YOUR_TELEGRAM_BOT_TOKEN'; // Replace with your bot token
$chatId = 'YOUR_TELEGRAM_CHAT_ID'; // Replace with your chat ID

$htmlContent = "
    <div class='receipt-container'>
        <div class='header'>RECEIPT</div>
        <div class='content'>
            <strong>Customer Name: </strong> John Doe<br>
            <strong>Agent Name: </strong> Agent Smith<br>
            <strong>Serial Number: </strong> ABC12345<br>
            <strong>Transacted: </strong> 2024-10-27 14:00:00<br>
        </div>
    </div>
";

$response = sendHtmlAsImageToTelegram($htmlContent, $telegramToken, $chatId);
echo $response;


function sendImageToTelegram($imagePath, $chatId) {
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

$response = sendImageToTelegram($imagePath, $chatId);
echo $response;
?>