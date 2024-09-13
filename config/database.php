<?php
// Database connection settings
$host = 'localhost'; // Host (usually localhost)
$db   = 'u737908269_lucky2024'; // Database name
$user = 'u737908269_root'; // Database username
$pass = '$1Rv1r@dmInS'; // Database password

try {
    // Create a new PDO connection
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Optional: Uncomment the following line to debug successful connection
    // echo "Connected successfully";
} catch (PDOException $e) {
    // Display error message if connection fails
    echo "Connection failed: " . $e->getMessage();
    die(); // Terminate script if connection fails
}
?>