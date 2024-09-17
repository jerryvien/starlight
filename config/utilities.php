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
?>