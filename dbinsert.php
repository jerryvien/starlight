<?php
// Database connection
include('config/database.php');

// Prepare the insert query for purchase_entries table
$sql = "INSERT INTO purchase_entries (agent_id, customer_id, purchase_no, purchase_amount, purchase_category, purchase_datetime, serial_number) 
        VALUES (:agent_id, :customer_id, :purchase_no, :purchase_amount, :purchase_category, :purchase_datetime, :serial_number)";

// Prepare the statement
$stmt = $conn->prepare($sql);

// Fetch available agent IDs and customer IDs
$agents_stmt = $conn->query("SELECT agent_id FROM admin_access");
$agents = $agents_stmt->fetchAll(PDO::FETCH_COLUMN);

$customers_stmt = $conn->query("SELECT customer_id FROM customer_details");
$customers = $customers_stmt->fetchAll(PDO::FETCH_COLUMN);

// Helper function to generate random serial number
function generateSerialNumber() {
    return strtoupper(bin2hex(random_bytes(6))); // 12-character alphanumeric serial number
}

// Generate 1000 purchase entries
for ($i = 0; $i < 1000; $i++) {
    // Randomly assign an agent ID from the list
    $agent_id = $agents[array_rand($agents)];

    // Randomly assign a customer ID from the list
    $customer_id = $customers[array_rand($customers)];

    // Generate a random purchase number (2-3 digits)
    $purchase_no = str_pad(rand(10, 999), 3, '0', STR_PAD_LEFT);

    // Random purchase amount between 100 and 5000
    $purchase_amount = rand(100, 5000);

    // Random purchase category ("Box" or "Straight")
    $purchase_category = rand(0, 1) == 0 ? 'Box' : 'Straight';

    // Generate random purchase date across 2022-2024
    $year = rand(2022, 2024);
    $month = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
    $day = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
    $hour = str_pad(rand(0, 23), 2, '0', STR_PAD_LEFT);
    $minute = str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT);
    $second = str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT);
    $purchase_datetime = "$year-$month-$day $hour:$minute:$second";

    // Generate a random serial number
    $serial_number = generateSerialNumber();

    // Bind the values to the prepared statement
    $stmt->bindParam(':agent_id', $agent_id);
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->bindParam(':purchase_no', $purchase_no);
    $stmt->bindParam(':purchase_amount', $purchase_amount);
    $stmt->bindParam(':purchase_category', $purchase_category);
    $stmt->bindParam(':purchase_datetime', $purchase_datetime);
    $stmt->bindParam(':serial_number', $serial_number);

    // Execute the insert query
    $stmt->execute();
}

// Success message
echo "1000 purchase entries have been successfully inserted!";
?>
