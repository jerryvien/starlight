<?php
session_start();
include('config/database.php'); // Include your database connection

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fetch records
try {
    // Set time zone to Kuala Lumpur
    date_default_timezone_set('Asia/Kuala_Lumpur');

    // Build SQL query with named parameters (not positional ones)
    $sql = "
        SELECT p.*, c.customer_name, a.agent_name 
        FROM purchase_entries p 
        JOIN customer_details c ON p.customer_id = c.customer_id 
        JOIN admin_access a ON p.agent_id = a.agent_id 
    ";

    // If the user is an agent (not super_admin), filter by agent_id
    if ($_SESSION['access_level'] !== 'super_admin') {
        $sql .= " WHERE p.agent_id = :agent_id";
    }

    // Add ordering and limit to the query
    $sql .= " ORDER BY p.purchase_datetime DESC LIMIT 100";

    // Prepare the query
    $stmt = $conn->prepare($sql);

    // Bind the agent_id parameter only if the user is an agent
    if ($_SESSION['access_level'] !== 'super_admin') {
        $stmt->bindParam(':agent_id', $_SESSION['agent_id'], PDO::PARAM_INT);
    }

    // Execute the query
    $stmt->execute();

    // Fetch all the records
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Handle any errors that occur during the query execution
    die("Error fetching records: " . $e->getMessage());
}
?>