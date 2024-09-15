<?php
function generateSerialNumber() {
    $key = "KENSTARLIGHT"; // Encryption key
    $random_number = rand(100000, 999999);
    $serial_number = substr(md5($key . $random_number), 0, 12);
    return strtoupper($serial_number);
}

function calculateTotal($purchase_no, $category, $amount) {
    // Determine permutation factor based on purchase number
    $factor = calculatePermutationFactor($purchase_no);
    
    // If category is Box, multiply amount by factor
    if ($category === 'Box') {
        return $amount * $factor;
    }
    return $amount;
}

function calculatePermutationFactor($purchase_no) {
    // Calculate permutation factor based on uniqueness of digits
    $unique_digits = count(array_unique(str_split($purchase_no)));
    switch ($unique_digits) {
        case 3: return 6; // 123 -> 6 combinations
        case 2: return 3; // 223 -> 3 combinations
        case 1: return 1; // 111 -> 1 combination
        default: return 1;
    }
}
?>