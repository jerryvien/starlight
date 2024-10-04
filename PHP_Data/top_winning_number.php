<?php
// Assuming you have a valid database connection `$conn`

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

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Chart Container -->
<canvas id="winningNumbersChart" width="400" height="200"></canvas>

<!-- Chart.js Script -->
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