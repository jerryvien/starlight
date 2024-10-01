<?php

include('config/database.php'); // Include your database connection

$year = isset($_POST['year']) ? $_POST['year'] : '';

// If a specific year is selected, fetch data for that year
if ($year) {
    $customer_growth_query = 
        "SELECT DATE_FORMAT(created_at, '%Y-%m') AS period, COUNT(*) AS new_customers 
         FROM customer_details 
         WHERE YEAR(created_at) = :year
         GROUP BY DATE_FORMAT(created_at, '%Y-%m')";

    $sales_transactions_query = 
        "SELECT DATE_FORMAT(purchase_datetime, '%Y-%m') AS period, 
                SUM(purchase_amount) AS total_sales, 
                COUNT(*) AS transaction_count
         FROM purchase_entries 
         WHERE YEAR(purchase_datetime) = :year
         GROUP BY DATE_FORMAT(purchase_datetime, '%Y-%m')";

    $customer_growth_stmt = $conn->prepare($customer_growth_query);
    $sales_transactions_stmt = $conn->prepare($sales_transactions_query);
    $customer_growth_stmt->bindParam(':year', $year);
    $sales_transactions_stmt->bindParam(':year', $year);

} else {
    // Fetch data for the last 12 months
    $customer_growth_query = 
        "SELECT DATE_FORMAT(created_at, '%Y-%m') AS period, COUNT(*) AS new_customers 
         FROM customer_details 
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
         GROUP BY DATE_FORMAT(created_at, '%Y-%m')";

    $sales_transactions_query = 
        "SELECT DATE_FORMAT(purchase_datetime, '%Y-%m') AS period, 
                SUM(purchase_amount) AS total_sales, 
                COUNT(*) AS transaction_count
         FROM purchase_entries 
         WHERE purchase_datetime >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
         GROUP BY DATE_FORMAT(purchase_datetime, '%Y-%m')";

    $customer_growth_stmt = $conn->prepare($customer_growth_query);
    $sales_transactions_stmt = $conn->prepare($sales_transactions_query);
}

$customer_growth_stmt->execute();
$sales_transactions_stmt->execute();

$response = [
    'customer_growth' => $customer_growth_stmt->fetchAll(PDO::FETCH_ASSOC),
    'sales_transactions' => $sales_transactions_stmt->fetchAll(PDO::FETCH_ASSOC)
];

echo json_encode($response);
?>