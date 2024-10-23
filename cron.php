<?php
include('config/database.php'); // Include your database connection

// Function to update timezone to GMT+8 for records with timezone_updated = 0
function updateTimezoneToGMT8() {
    $pdo = $db;
    
    $query = "UPDATE user_activity_log 
              SET login_time = CONVERT_TZ(login_time, '+00:00', '+08:00'),
                  created_at = CONVERT_TZ(created_at, '+00:00', '+08:00'),
                  timezone_updated = 1
              WHERE timezone_updated = 0";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        echo "Timezone updated successfully for affected records.";
    } catch (PDOException $e) {
        echo "Error updating timezone: " . $e->getMessage();
    }
}

// Call the function to update the timezone
updateTimezoneToGMT8();
?>
