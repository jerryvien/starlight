<?php
function generateSerialNumber() {
    $key = "KENSTARLIGHT"; // Encryption key
    $random_number = rand(100000, 999999);
    $serial_number = substr(md5($key . $random_number), 0, 12);
    return strtoupper($serial_number);
}

// Function to populate dynamic entry field 
function populatePurchaseEntries() {
    const count = parseInt(document.getElementById('purchase_count').value);
    const wrapper = document.getElementById('purchase_entries_wrapper');
    wrapper.innerHTML = ''; // Clear existing entries

    // Get today's date in 'YYYY-MM-DD' format
    const today = new Date().toISOString().split('T')[0];

    for (let i = 0; i < count; i++) {
        // Create a new row
        const row = document.createElement('div');
        row.classList.add('form-group', 'row');

        // Purchase Number Field
        const col1 = document.createElement('div');
        col1.classList.add('col-md-3');
        col1.innerHTML = `
            <label for="purchase_no_${i}">Purchase Number</label>
            <input type="text" class="form-control" name="purchase_no[]" id="purchase_no_${i}" pattern="\\d{2,3}" title="Please enter a number with 2 or 3 digits" required>
        `;
        
        // Purchase Category Field
        const col2 = document.createElement('div');
        col2.classList.add('col-md-2');
        col2.innerHTML = `
            <label for="purchase_category_${i}">Category</label>
            <select class="form-control" name="purchase_category[]" id="purchase_category_${i}" onchange="calculateTotalPrice(${i})">
                <option value="Box">Box</option>
                <option value="Straight">Straight</option>
            </select>
        `;

        // Purchase Amount Field
        const col3 = document.createElement('div');
        col3.classList.add('col-md-3');
        col3.innerHTML = `
            <label for="purchase_amount_${i}">Amount</label>
            <input type="number" class="form-control" name="purchase_amount[]" id="purchase_amount_${i}" oninput="calculateTotalPrice(${i})" required>
        `;

        // Purchase Date Field
        const col4 = document.createElement('div');
        col4.classList.add('col-md-4');
        const dateInput = document.createElement('input');
        dateInput.type = 'date';
        dateInput.classList.add('form-control');
        dateInput.name = 'purchase_date[]';
        dateInput.id = `purchase_date_${i}`;
        dateInput.required = true;
        dateInput.value = today;  // Set today's date as default
        col4.innerHTML = `<label for="purchase_date_${i}">Purchase Date</label>`;
        col4.appendChild(dateInput);

        // Total Price Field
        const col5 = document.createElement('div');
        col5.classList.add('col-md-2');
        col5.innerHTML = `
            <label for="total_price_${i}">Total Price</label>
            <input type="text" class="form-control" name="total_price[]" id="total_price_${i}" readonly>
        `;

        // Append all columns to the row
        row.appendChild(col1);
        row.appendChild(col2);
        row.appendChild(col3);
        row.appendChild(col4);
        row.appendChild(col5);

        // Append the row to the wrapper
        wrapper.appendChild(row);

        // Set default date and initialize total price calculation
        calculateTotalPrice(i);
    }
}

// Function to calculate total price based on category and amount
function calculateTotalPrice(index) {
    const categoryElement = document.getElementById(`purchase_category_${index}`);
    const amountElement = document.getElementById(`purchase_amount_${index}`);
    const purchaseNoElement = document.getElementById(`purchase_no_${index}`);
    const totalPriceElement = document.getElementById(`total_price_${index}`);

    let amount = parseFloat(amountElement.value) || 0;
    let totalPrice = 0;

    // Apply permutation factor based on the category
    if (categoryElement.value === 'Box') {
        const permutationFactor = calculatePermutationFactor(purchaseNoElement.value);
        totalPrice = amount * permutationFactor;
    } else {
        totalPrice = amount; // "Straight" uses the same amount
    }

    totalPriceElement.value = totalPrice.toFixed(2); // Update total price
}

// Function to calculate permutation factor for "Box" based on the unique digits
function calculatePermutationFactor(purchaseNo) {
    const digitCounts = {};
    [...purchaseNo.toString()].forEach(digit => {
        digitCounts[digit] = (digitCounts[digit] || 0) + 1;
    });

    const numDigits = purchaseNo.length;
    let numerator = factorial(numDigits);
    let denominator = 1;
    Object.values(digitCounts).forEach(count => {
        denominator *= factorial(count);
    });

    return numerator / denominator; // Using real division to handle non-integer cases
}

// Helper function to calculate factorial
function factorial(n) {
    return n ? n * factorial(n - 1) : 1;
}
?>