<?php

include('config/database.php'); // Optional: Include DB connection if necessary

// Check if the user is logged in
if (isset($_SESSION['agent_id'])) {
    $user_id = $_SESSION['agent_id'];
    $ip_address = getUserIP();
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    // Log the logout activity
    $stmt = $conn->prepare("
        INSERT INTO user_activity_log (user_id, activity_type, ip_address, logout_time, user_agent)
        VALUES (:user_id, 'logout', :ip_address, NOW(), :user_agent)
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':ip_address', $ip_address);
    $stmt->bindParam(':user_agent', $user_agent);
    $stmt->execute();
}


// Destroy the session to log the user out
session_destroy();

// Redirect back to the login page
header("Location: index.php");
exit;
?>