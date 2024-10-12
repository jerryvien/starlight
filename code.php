<?php
// Set the default time zone to your server's local time zone
date_default_timezone_set('Asia/Kuala_Lumpur');

// Get the current date and time in the server's time zone
$currentDateTime = date('Y-m-d H:i:s');

// Set the time zone to GMT+8
$gmtPlus8TimeZone = new DateTimeZone('Asia/Kuala_Lumpur');
$dateTimeGMTPlus8 = new DateTime('now', $gmtPlus8TimeZone);
$currentDateTimeGMTPlus8 = $dateTimeGMTPlus8->format('Y-m-d H:i:s');

// Display the date and time in both time zones
echo "Current Server Date and Time: " . $currentDateTime . "<br>";
echo "Current GMT+8 Date and Time: " . $currentDateTimeGMTPlus8;
?>
