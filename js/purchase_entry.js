// Filter and display customer list based on search input
function filterCustomers() {
    const searchValue = document.getElementById('customer_search').value.toLowerCase();
    const customerList = document.getElementById('customer_list');
    customerList.innerHTML = ''; // Clear previous list

    customers.forEach(function(customer) {
        if (customer.customer_name.toLowerCase().includes(searchValue)) {
            const li = document.createElement('li');
            li.classList.add('list-group-item');
            li.textContent = customer.customer_name;
            li.onclick = function() {
                selectCustomer(customer.customer_id, customer.customer_name);
            };
            customerList.appendChild(li);
        }
    });
}

// When a customer is selected, populate the hidden input and display customer details
function selectCustomer(customerId, customerName) {
    document.getElementById('customer_search').value = customerName;
    document.getElementById('customer_list').innerHTML = ''; // Clear the list
    document.getElementById('customer_details').innerHTML = `Selected Customer: ${customerName}`;
    
    // Add hidden input to form
    const hiddenCustomerId = `<input type="hidden" name="customer_id" value="${customerId}">`;
    document.getElementById('purchase_entries_wrapper').insertAdjacentHTML('beforebegin', hiddenCustomerId);
}

// Dynamically generate purchase entry rows based on selection
function populatePurchaseEntries() {
    const count = parseInt(document.getElementById('purchase_count').value);
    const wrapper = document.getElementById('purchase_entries_wrapper');
    wrapper.innerHTML = ''; // Clear existing entries

    for (let i = 0; i < count; i++) {
        wrapper.innerHTML += `
            <div class="form-group row">
                <div class="col-md-3">
                    <label for="purchase_no_${i}">Purchase Number</label>
                    <input type="text" class="form-control" name="purchase_no[]" oninput="validatePurchaseNumber(this)" required>
                </div>
                <div class="col-md-2">
                    <label for="purchase_category_${i}">Category</label>
                    <select class="form-control" name="purchase_category[]">
                        <option value="Box">Box</option>
                        <option value="Straight">Straight</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="purchase_amount_${i}">Amount</label>
                    <input type="number" class="form-control" name="purchase_amount[]" oninput="calculateTotal(${i})" required>
                </div>
                <div class="col-md-2">
                    <label for="purchase_total_${i}">Total</label>
                    <input type="text" class="form-control" name="purchase_total[]" id="purchase_total_${i}" readonly>
                </div>
                <div class="col-md-3">
                    <label for="purchase_date_${i}">Purchase Date</label>
                    <input type="date" class="form-control" name="purchase_date[]" required>
                </div>
            </div>
        `;
    }
}

// Validate purchase number to ensure it's 2 or 3 digits and no duplicates in the same category
function validatePurchaseNumber(input) {
    const value = input.value;
    if (value.length < 2 || value.length > 3 || isNaN(value)) {
        alert("Purchase number must be a 2 or 3 digit number.");
        input.value = ''; // Clear invalid input
    }
    checkDuplicateEntries();
}

// Check for duplicate purchase entries in the same category
function checkDuplicateEntries() {
    const purchaseNumbers = document.querySelectorAll('input[name="purchase_no[]"]');
    const categories = document.querySelectorAll('select[name="purchase_category[]"]');
    const seen = {};

    purchaseNumbers.forEach((numInput, i) => {
        const number = numInput.value;
        const category = categories[i].value;
        const key = `${number}_${category}`;
        if (seen[key]) {
            alert(`Duplicate entry: ${number} with ${category} already exists.`);
            numInput.value = ''; // Clear duplicate entry
        } else if (number) {
            seen[key] = true;
        }
    });
}

// Calculate total based on permutation factor and purchase amount
function calculateTotal(index) {
    const purchaseNumber = document.querySelectorAll('input[name="purchase_no[]"]')[index].value;
    const amount = parseFloat(document.querySelectorAll('input[name="purchase_amount[]"]')[index].value) || 0;
    const category = document.querySelectorAll('select[name="purchase_category[]"]')[index].value;

    if (purchaseNumber.length === 2 || purchaseNumber.length === 3) {
        const factor = calculatePermutationFactor(purchaseNumber);
        let total = amount;

        if (category === 'Box') {
            total *= factor;
        }

        document.getElementById(`purchase_total_${index}`).value = total.toFixed(2);
    }
}

// Calculate permutation factor based on purchase number
function calculatePermutationFactor(purchaseNumber) {
    const uniqueDigits = [...new Set(purchaseNumber.split(''))].length;

    if (uniqueDigits === 3) return 6;  // Three unique digits
    if (uniqueDigits === 2) return 3;  // Two unique digits
    return 1;  // All digits the same
}

// Show confirmation popup with all purchase entries
function showConfirmation() {
    let confirmationHtml = "<h4>Confirm Purchase Entries</h4><ul>";
    const purchaseNumbers = document.querySelectorAll('input[name="purchase_no[]"]');
    const categories = document.querySelectorAll('select[name="purchase_category[]"]');
    const amounts = document.querySelectorAll('input[name="purchase_amount[]"]');
    const totals = document.querySelectorAll('input[name="purchase_total[]"]');
    const dates = document.querySelectorAll('input[name="purchase_date[]"]');

    purchaseNumbers.forEach((purchase, i) => {
        confirmationHtml += `
            <li>
                Purchase No: ${purchase.value}, Category: ${categories[i].value}, 
                Amount: ${amounts[i].value}, Total: ${totals[i].value}, Date: ${dates[i].value}
            </li>`;
    });
    confirmationHtml += "</ul>";

    if (confirm(confirmationHtml)) {
        document.forms[0].submit(); // Submit the form if confirmed
    }
}