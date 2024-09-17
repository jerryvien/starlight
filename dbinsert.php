<?php
// Database connection
include('config/database.php');

// Array of common Asian names (you can expand this list as needed)
$asian_names = [
    "Lee", "Wang", "Li", "Chen", "Zhang", "Liu", "Kim", "Huang", "Choi", "Park", 
    "Tan", "Lim", "Yamamoto", "Takahashi", "Ito", "Kobayashi", "Nguyen", "Tran", 
    "Pham", "Jiang", "Khanh", "Chung", "Rizwan", "Sato", "Murata", "Shen"
];

// List of Asian countries for the agent's market
$asian_countries = [
    "China", "India", "Japan", "South Korea", "Vietnam", "Thailand", 
    "Malaysia", "Singapore", "Indonesia", "Philippines", "Pakistan", 
    "Sri Lanka", "Bangladesh", "Cambodia", "Myanmar", "Nepal"
];

// Prepare the insert query for admin_access table
$sql = "INSERT INTO admin_access (agent_id, agent_name, agent_login_id, agent_password, agent_market, agent_credit_limit, access_level, created_at) 
        VALUES (:agent_id, :agent_name, :agent_login_id, :agent_password, :agent_market, :agent_credit_limit, :access_level, :created_at)";

// Prepare the statement
$stmt = $conn->prepare($sql);

// Starting agent ID
$starting_agent_id = 4;

// Generate 30 agent records
for ($i = 0; $i < 35; $i++) {
    // Sequentially increment agent_id
    $agent_id = $starting_agent_id + $i;

    // Randomly select an Asian name
    $agent_name = $asian_names[array_rand($asian_names)];

    // Generate agent login ID (e.g., "agent3", "agent4", etc.)
    $agent_login_id = 'AG' . $agent_id;

    // Generate random password (this should be hashed in a real system)
    $agent_password = password_hash('admin123' . $agent_id, PASSWORD_BCRYPT); // Random password for demo

    // Randomly select an Asian country for the market
    $agent_market = $asian_countries[array_rand($asian_countries)];

    // Random agent credit limit between 5000 and 10000
    $agent_credit_limit = rand(5000, 10000);

    // Assign "agent" access level
    $access_level = 'agent';

    // Generate random creation date across 2023-2024
    $year = rand(2023, 2024);
    $month = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
    $day = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT); // Use 28 days to avoid issues with February
    $hour = str_pad(rand(0, 23), 2, '0', STR_PAD_LEFT);
    $minute = str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT);
    $second = str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT);

    // Construct the created_at timestamp
    $created_at = "$year-$month-$day $hour:$minute:$second";

    // Bind the values to the prepared statement
    $stmt->bindParam(':agent_id', $agent_id);
    $stmt->bindParam(':agent_name', $agent_name);
    $stmt->bindParam(':agent_login_id', $agent_login_id);
    $stmt->bindParam(':agent_password', $agent_password);
    $stmt->bindParam(':agent_market', $agent_market);
    $stmt->bindParam(':agent_credit_limit', $agent_credit_limit);
    $stmt->bindParam(':access_level', $access_level);
    $stmt->bindParam(':created_at', $created_at);

    // Execute the insert query
    $stmt->execute();
}

// Success message
echo "30 agents have been successfully inserted!";
?>
