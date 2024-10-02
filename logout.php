<?php

include('config/database.php'); // Optional: Include DB connection if necessary
include('config/utilities.php'); // Optional: Include DB connection if necessary

// Check if the agent is logged in
if (isset($_SESSION['agent_id'])) {
    $user_id = $_SESSION['agent_id']; // Get the agent's user ID
    $ip_address = getUserIP(); // Get the public IP address of the user
    $user_agent = $_SERVER['HTTP_USER_AGENT']; // Get the user agent

    try {
        // Log the logout activity
        $stmt = $conn->prepare("
            INSERT INTO user_activity_log (user_id, activity_type, ip_address, logout_time, user_agent)
            VALUES (:user_id, 'logout', :ip_address, NOW(), :user_agent)
        ");

        // Bind the values to the SQL query
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':ip_address', $ip_address);
        $stmt->bindParam(':user_agent', $user_agent);

        // Execute the query
        $stmt->execute();

        echo "Logout logged successfully.";

    } catch (PDOException $e) {
        // Catch and display any errors
        echo "Error logging logout activity: " . $e->getMessage();
    }
}


// Destroy the session to log the user out
session_destroy();

// Redirect back to the login page
header("Location: index.php");
exit;
?>