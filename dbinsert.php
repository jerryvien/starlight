<?php
// Database connection
include('config/database.php');

// Starting agent ID (adjust based on your existing data)
$starting_agent_id = 100;

// Prepare the insert query for admin_access table
$sql = "INSERT INTO admin_access (agent_id, agent_name, agent_login_id, agent_password, agent_market, agent_credit_limit, access_level, created_at) 
        VALUES (:agent_id, :agent_name, :agent_login_id, :agent_password, :agent_market, :agent_credit_limit, :access_level, :created_at)";

// Prepare the statement
$stmt = $conn->prepare($sql);

// Generate 30 agent records
for ($i = 0; $i < 30; $i++) {
    // Generate agent_id (e.g., 100, 101, etc.)
    $agent_id = $starting_agent_id + $i;

    // Generate agent name (e.g., "Agent 100", "Agent 101", etc.)
    $agent_name = 'Agent ' . $agent_id;

    // Generate agent login ID (e.g., "agent100", "agent101", etc.)
    $agent_login_id = 'agent' . $agent_id;

    // Generate random password (this should be hashed in a real system)
    $agent_password = password_hash('password' . $agent_id, PASSWORD_BCRYPT); // Random password for demo

    // Random agent market (e.g., "North", "South", "East", "West")
    $agent_market_options = ['North', 'South', 'East', 'West'];
    $agent_market = $agent_market_options[rand(0, 3)];

    // Random agent credit limit between 5000 and 10000
    $agent_credit_limit = rand(5000, 10000);

    // Assign "agent" access level
    $access_level = 'agent';

    // Random creation date across 2023-2024
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
