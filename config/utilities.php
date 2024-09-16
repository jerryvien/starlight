<?php
function generateSerialNumber() {
    $key = "KENSTARLIGHT"; // Encryption key
    $random_number = rand(100000, 999999);
    $serial_number = substr(md5($key . $random_number), 0, 12);
    return strtoupper($serial_number);
}

// Function to calculate the total price based on category and amount
function calculateTotalPrice(index) {
    const categoryElement = document.getElementById(`purchase_category_${index}`);
    const amountElement = document.getElementById(`purchase_amount_${index}`);
    const purchaseNoElement = document.getElementById(`purchase_no_${index}`);
    const totalPriceElement = document.getElementById(`total_price_${index}`);

    let amount = parseFloat(amountElement.value) || 0;
    let purchaseNo = purchaseNoElement.value;
    let totalPrice = 0;

    // If category is "Box", apply the permutation factor
    if (categoryElement.value === 'Box') {
        const permutationFactor = calculatePermutationFactor(purchaseNo);
        totalPrice = amount * permutationFactor;
    } else if (categoryElement.value === 'Straight') {
        // For "Straight", amount stays the same
        totalPrice = amount;
    }

    // Set the total price
    totalPriceElement.value = totalPrice.toFixed(2);
}

// Mimic the server-side function to calculate permutation factor
function calculatePermutationFactor(purchaseNo) {
    const uniqueDigits = new Set(purchaseNo.split('')).size;
    switch (uniqueDigits) {
        case 3:
            return 6; // 3 unique digits -> 6 combinations
        case 2:
            return 3; // 2 unique digits -> 3 combinations
        case 1:
            return 1; // 1 unique digit -> 1 combination
        default:
            return 1;
    }
}
?>