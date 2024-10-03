<?php

include('config/database.php');

// Set time zone to Kuala Lumpur (GMT +8)
date_default_timezone_set('Asia/Kuala_Lumpur');

// Error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Ensure the user is a super_admin
if ($_SESSION['access_level'] !== 'super_admin') {
    echo "<script>alert('You must be a super admin to access this page.'); window.location.href='dashboard.php';</script>";
    exit;
}

// Query the top winning numbers from the past 14 days
try {
    $stmt = $conn->query("
        SELECT winning_number, COUNT(*) as count
        FROM winning_record
        WHERE winning_date >= NOW() - INTERVAL 14 DAY
        GROUP BY winning_number
        ORDER BY count DESC
        LIMIT 10
    ");
    $winning_numbers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching winning numbers: " . $e->getMessage());
}

// Prepare data for Chart.js
$numbers = [];
$counts = [];

foreach ($winning_numbers as $row) {
    $numbers[] = $row['winning_number'];
    $counts[] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Winning Numbers</title>
    
</head>
<body>

<div class="container">
    <h2>Top Winning Numbers in the Past 14 Days</h2>
    <canvas id="winningNumbersChart" width="400" height="200"></canvas>
</div>

<script>
    var ctx = document.getElementById('winningNumbersChart').getContext('2d');
    var chart = new Chart(ctx, {
        type: 'bar',  // You can also use 'pie' for a pie chart
        data: {
            labels: <?php echo json_encode($numbers); ?>,  // Winning numbers
            datasets: [{
                label: 'Frequency',
                data: <?php echo json_encode($counts); ?>,  // Frequency counts
                backgroundColor: 'rgba(75, 192, 192, 0.6)',  // Bar color
                borderColor: 'rgba(75, 192, 192, 1)',  // Bar border color
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            return 'Count: ' + tooltipItem.raw;
                        }
                    }
                }
            }
        }
    });
</script>

</body>
</html>