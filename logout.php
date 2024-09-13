<?php
session_start();
session_destroy(); // Destroy the session
header("Location: index.php"); // Redirect back to the login page
exit;
?>