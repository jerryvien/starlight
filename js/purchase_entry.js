

// Filter and display customer list
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

// Select customer and display customer information
function selectCustomer(customerId, customerName) {
    document.getElementById('customer_search').value = customerName;
    document.getElementById('customer_list').innerHTML = '';
    const customerField = `<input type="hidden" name="customer_id" value="${customerId}">`;
    document.getElementById('purchase_entries_wrapper').insertAdjacentHTML('beforebegin', customerField);

    // Display customer details on screen (just a placeholder; you can customize this)
    document.getElementById('customer_details').innerHTML = `Customer: ${customerName}`;
}

// Validate the purchase number (only 2 or 3 digits allowed)
function validatePurchaseNo(input) {
    const value = input.value;
    if (!/^\d{2,3}$/.test(value)) {
        input.setCustomValidity('Please enter a 2 or 3 digit number.');
    } else {
        input.setCustomValidity('');
    }
}

// Validate for duplicate purchase numbers with the same category
function validateDuplicateEntries() {
    const purchaseNos = Array.from(document.querySelectorAll('.purchase_no')).map(input => input.value);
    const purchaseCategories = Array.from(document.querySelectorAll('[name="purchase_category[]"]')).map(select => select.value);

    const entries = purchaseNos.map((purchaseNo, index) => ({
        purchaseNo: purchaseNo,
        category: purchaseCategories[index]
    }));

    const seenEntries = new Set();
    for (const entry of entries) {
        const uniqueKey = `${entry.purchaseNo}-${entry.category}`;
        if (seenEntries.has(uniqueKey)) {
            alert(`Duplicate entry found for purchase number ${entry.purchaseNo} with category ${entry.category}.`);
            return false;
        }
        seenEntries.add(uniqueKey);
    }
    return true;
}

// Handle form submission (add more validation before submit)
document.querySelector('form').onsubmit = function (e) {
    if (!validateDuplicateEntries()) {
        e.preventDefault(); // Prevent form submission if there are validation errors
    }
};
