<?php
// Database connection
include('config/database.php');

// Fetch all valid agent IDs from the admin_access table
$agent_query = "SELECT agent_id FROM admin_access";
$agent_stmt = $conn->prepare($agent_query);
$agent_stmt->execute();
$agents = $agent_stmt->fetchAll(PDO::FETCH_COLUMN); // Fetch all agent IDs as an array

if (empty($agents)) {
    die("No agents found. Please add agents before adding customers.");
}

// Array of common European names (you can expand this list as needed)
$european_first_names = [
    "John", "Paul", "George", "David", "Michael", "James", "Robert", "Peter", "William", "Richard",
    "Mary", "Patricia", "Linda", "Barbara", "Elizabeth", "Jennifer", "Susan", "Jessica", "Sarah", "Karen"
];
$european_last_names = [
    "Smith", "Johnson", "Williams", "Brown", "Jones", "Miller", "Davis", "Garcia", "Rodriguez", "Wilson",
    "Martinez", "Anderson", "Taylor", "Thomas", "Moore", "Martin", "Jackson", "Thompson", "White", "Harris"
];

// Prepare the insert query for customer_details table
$sql = "INSERT INTO customer_details (customer_id, customer_name, agent_id, credit_limit, vip_status, created_at) 
        VALUES (:customer_id, :customer_name, :agent_id, :credit_limit, :vip_status, :created_at)";

// Prepare the statement
$stmt = $conn->prepare($sql);

// Starting customer ID (CUST040)
$starting_customer_id = 40;

// Set the base date for 2024
$base_year = 2024;
$base_month = 1;
$base_day = 1;

// Random VIP statuses
$vip_statuses = ['Normal', 'VIP'];

// Generate 4-5 customers per day for each day in 2024
for ($day = 1; $day <= 365; $day++) {
    // Calculate the day, month, and year
    $date = new DateTime("$base_year-01-01");
    $date->modify("+$day days");
    $formatted_date = $date->format('Y-m-d');

    // Generate 4-5 customers for this day
    $num_customers = rand(1, 10);
    for ($i = 0; $i < $num_customers; $i++) {
        // Sequential customer ID
        $customer_id = 'CUST' . str_pad($starting_customer_id++, 3, '0', STR_PAD_LEFT);

        // Generate a random customer name (first name + last name)
        $first_name = $european_first_names[array_rand($european_first_names)];
        $last_name = $european_last_names[array_rand($european_last_names)];
        $customer_name = "$first_name $last_name";

        // Randomly assign a valid agent_id from the admin_access table
        $agent_id = $agents[array_rand($agents)];

        // Random credit limit between 1000 and 10000
        $credit_limit = rand(1000, 10000);

        // Randomly select a VIP status
        $vip_status = $vip_statuses[array_rand($vip_statuses)];

        // Use the current formatted date for the created_at field
        $created_at = "$formatted_date " . str_pad(rand(0, 23), 2, '0', STR_PAD_LEFT) . ":" . str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT) . ":00";

        // Bind the values to the prepared statement
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->bindParam(':customer_name', $customer_name);
        $stmt->bindParam(':agent_id', $agent_id); // Random agent_id from the fetched list
        $stmt->bindParam(':credit_limit', $credit_limit);
        $stmt->bindParam(':vip_status', $vip_status);
        $stmt->bindParam(':created_at', $created_at);

        // Execute the insert query
        $stmt->execute();
    }
}

// Success message
echo "Customers have been successfully inserted!";
?>
