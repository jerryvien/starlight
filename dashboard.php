<?php
session_start();
include('config/database.php'); // Include your database connection

// Fetch agent or super_admin access level from session
if (!isset($_SESSION['agent_id'])) {
    // Redirect to login page if the user is not logged in
    header('Location: index.php');
    exit;
}

$agent_id = $_SESSION['agent_id'];
$access_level = $_SESSION['access_level'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Dashboard</title>
    <!-- Load chart libraries -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <h1>Business Dashboard</h1>

    <?php if ($access_level == 'super_admin'): ?>
        <h2>Super Admin Dashboard</h2>
        <!-- Total Sales -->
        <div id="total-sales">
            <h3>Total Sales by Agent</h3>
            <canvas id="salesChart"></canvas>
            <?php
            $sql = "SELECT agent_name, SUM(total_sales) as total_sales 
                    FROM customer_details 
                    JOIN admin_access ON customer_details.agent_id = admin_access.agent_id 
                    GROUP BY customer_details.agent_id";
            $stmt = $pdo->query($sql);
            $sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <script>
                const salesData = <?= json_encode($sales_data) ?>;
                const ctx = document.getElementById('salesChart').getContext('2d');
                const salesChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: salesData.map(data => data.agent_name),
                        datasets: [{
                            label: 'Total Sales',
                            data: salesData.map(data => data.total_sales),
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            </script>
        </div>

        <!-- Total Purchase History -->
        <div id="purchase-history">
            <h3>Purchase History Summary</h3>
            <canvas id="purchaseHistoryChart"></canvas>
            <?php
            $sql = "SELECT purchase_category, COUNT(*) as total 
                    FROM purchase_entries 
                    GROUP BY purchase_category";
            $stmt = $pdo->query($sql);
            $purchase_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <script>
                const purchaseData = <?= json_encode($purchase_data) ?>;
                const ctx2 = document.getElementById('purchaseHistoryChart').getContext('2d');
                const purchaseChart = new Chart(ctx2, {
                    type: 'pie',
                    data: {
                        labels: purchaseData.map(data => data.purchase_category),
                        datasets: [{
                            data: purchaseData.map(data => data.total),
                            backgroundColor: ['rgba(255, 99, 132, 0.2)', 'rgba(54, 162, 235, 0.2)'],
                            borderColor: ['rgba(255, 99, 132, 1)', 'rgba(54, 162, 235, 1)'],
                            borderWidth: 1
                        }]
                    },
                });
            </script>
        </div>

    <?php elseif ($access_level == 'agent'): ?>
        <h2>Agent Dashboard</h2>
        
        <!-- Agent's Total Sales -->
        <div id="agent-sales">
            <h3>Your Total Sales</h3>
            <canvas id="agentSalesChart"></canvas>
            <?php
            $sql = "SELECT customer_name, total_sales 
                    FROM customer_details 
                    WHERE agent_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$agent_id]);
            $agent_sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <script>
                const agentSalesData = <?= json_encode($agent_sales_data) ?>;
                const ctx3 = document.getElementById('agentSalesChart').getContext('2d');
                const agentSalesChart = new Chart(ctx3, {
                    type: 'bar',
                    data: {
                        labels: agentSalesData.map(data => data.customer_name),
                        datasets: [{
                            label: 'Sales',
                            data: agentSalesData.map(data => data.total_sales),
                            backgroundColor: 'rgba(153, 102, 255, 0.2)',
                            borderColor: 'rgba(153, 102, 255, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            </script>
        </div>

        <!-- Recent Purchases -->
        <div id="recent-purchases">
            <h3>Recent Purchases</h3>
            <table border="1">
                <thead>
                    <tr>
                        <th>Purchase No</th>
                        <th>Amount</th>
                        <th>Category</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT purchase_no, purchase_amount, purchase_category, purchase_datetime 
                            FROM purchase_entries 
                            WHERE agent_id = ? 
                            ORDER BY purchase_datetime DESC LIMIT 10";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$agent_id]);
                    $recent_purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($recent_purchases as $purchase) {
                        echo "<tr>
                                <td>{$purchase['purchase_no']}</td>
                                <td>{$purchase['purchase_amount']}</td>
                                <td>{$purchase['purchase_category']}</td>
                                <td>{$purchase['purchase_datetime']}</td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>

</body>
</html>
