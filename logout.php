<?php
session_start();
include('config/database.php'); // Optional: Include DB connection if necessary

// Destroy the session to log the user out
session_destroy();

// Redirect back to the login page
header("Location: index.php");
exit;
?>