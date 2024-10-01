<?php
session_start();
include('config/database.php');

// Set time zone to Kuala Lumpur (GMT +8)
date_default_timezone_set('Asia/Kuala_Lumpur');

// Error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fetch winning records
try {
    $stmt = $conn->query("SELECT * FROM winning_record ORDER BY winning_date DESC");
    $winning_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching winning records: " . $e->getMessage());
}

// Matching logic based on winning record
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['select_winning_record'])) {
    $winning_id = $_POST['select_winning_record'];

    // Fetch winning record
    $stmt = $conn->prepare("SELECT * FROM winning_record WHERE id = :winning_id");
    $stmt->bindParam(':winning_id', $winning_id);
    $stmt->execute();
    $winning_record = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($winning_record) {
        // Generate permutations of the winning number
        $winning_number = $winning_record['winning_number'];
        $winning_combinations = generate_combinations($winning_number);

        // Fetch matching purchase entries
        $purchase_stmt = $conn->prepare("
            SELECT * FROM purchase_entries 
            WHERE result NOT IN ('Win', 'Loss') 
              AND DATE(purchase_datetime) <= :winning_date
              AND purchase_no IN (" . implode(",", array_fill(0, count($winning_combinations), '?')) . ")
        ");

        $params = array_merge([$winning_record['winning_date']], $winning_combinations);
        $purchase_stmt->execute($params);
        $matching_purchases = $purchase_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Generate permutations of a winning number
function generate_combinations($number) {
    $permutations = [];
    if (strlen($number) == 3) {
        $permutations = [
            $number,
            $number[0] . $number[2] . $number[1],
            $number[1] . $number[0] . $number[2],
            $number[1] . $number[2] . $number[0],
            $number[2] . $number[0] . $number[1],
            $number[2] . $number[1] . $number[0],
        ];
    } elseif (strlen($number) == 2) {
        $permutations = [
            $number,
            $number[1] . $number[0],
        ];
    }
    return $permutations;
}

// Handle the finalization of the winning process
if (isset($_POST['finalize_winning'])) {
    $winning_record_id = $_POST['winning_record_id'];

    // Fetch matching purchase entries again for confirmation
    $purchase_stmt = $conn->prepare("
        SELECT * FROM purchase_entries 
        WHERE result NOT IN ('Win', 'Loss') 
          AND winning_record_id IS NULL
          AND DATE(purchase_datetime) <= :winning_date
    ");
    $purchase_stmt->bindParam(':winning_date', $winning_record['winning_date']);
    $purchase_stmt->execute();
    $matching_purchases = $purchase_stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($matching_purchases) {
        foreach ($matching_purchases as $purchase) {
            $is_winner = in_array($purchase['purchase_no'], generate_combinations($winning_record['winning_number']));

            // Calculate winning amount dynamically
            $winning_category = $purchase['purchase_category']; // Box or Straight
            $winning_factor = ($winning_category === 'Box') ? 1 : 2; // 1 for Box, 2 for Straight
            $winning_amount = $is_winner ? $winning_factor * $purchase['purchase_amount'] : 0;

            // Update the purchase record
            $update_stmt = $conn->prepare("
                UPDATE purchase_entries
                SET result = :result,
                    winning_category = :winning_category,
                    winning_amount = :winning_amount,
                    winning_number = :winning_number,
                    winning_record_id = :winning_record_id
                WHERE id = :purchase_id
            ");
            
            $result = $is_winner ? 'Win' : 'Loss';

            // Bind values
            $update_stmt->bindParam(':result', $result);
            $update_stmt->bindParam(':winning_category', $winning_category);
            $update_stmt->bindParam(':winning_amount', $winning_amount);
            $update_stmt->bindParam(':winning_number', $winning_record['winning_number']);
            $update_stmt->bindParam(':winning_record_id', $winning_record_id);
            $update_stmt->bindParam(':purchase_id', $purchase['id']);

            // Execute the update and log any potential error
            if (!$update_stmt->execute()) {
                echo "<div class='alert alert-danger'>Failed to update record for purchase #{$purchase['id']}</div>";
            }
        }
        echo "<div class='alert alert-success'>Winning results have been successfully updated!</div>";
    } else {
        echo "<div class='alert alert-info'>No matching purchase entries found for the selected winning record.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Winning Record Matching</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <link href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" rel="stylesheet">
</head>
<body>

<!-- Winning Records Table -->
<div class="container-fluid">
    <h2>Winning Records</h2>
    <table id="winningRecordsTable" class="table table-bordered">
        <thead>
            <tr>
                <th>Winning Number</th>
                <th>Winning Game</th>
                <th>Winning Period</th>
                <th>Winning Date</th>
                <th>Total Payout</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($winning_records as $record): ?>
            <tr>
                <td><?php echo $record['winning_number']; ?></td>
                <td><?php echo $record['winning_game']; ?></td>
                <td><?php echo $record['winning_period']; ?></td>
                <td><?php echo $record['winning_date']; ?></td>
                <td><?php echo $record['winning_total_payout']; ?></td>
                <td>
                    <form method="POST">
                        <button type="submit" name="select_winning_record" value="<?php echo $record['id']; ?>" class="btn btn-primary">Select</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Matched Purchase Entries Table -->
<?php if (!empty($matching_purchases)): ?>
<div class="container-fluid">
    <h2>Matched Purchase Entries</h2>
    <form method="POST" onsubmit="return confirm('Are you sure you want to finalize the results? This action is irreversible.')">
        <input type="hidden" name="winning_record_id" value="<?php echo $winning_id; ?>">
        <table id="matchedPurchasesTable" class="table table-bordered">
            <thead>
                <tr>
                    <th>Customer Name</th>
                    <th>Purchase No</th>
                    <th>Purchase Amount</th>
                    <th>Purchase Date</th>
                    <th>Agent Name</th>
                    <th>Result</th>
                    <th>Winning Category</th>
                    <th>Winning Amount (Calculated)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($matching_purchases as $purchase): ?>
                <tr>
                    <td><?php echo $purchase['customer_name']; ?></td>
                    <td><?php echo $purchase['purchase_no']; ?></td>
                    <td><?php echo $purchase['purchase_amount']; ?></td>
                    <td><?php echo $purchase['purchase_datetime']; ?></td>
                    <td><?php echo $purchase['agent_name']; ?></td>
                    <td><?php echo $purchase['result']; ?></td>
                    <td><?php echo $purchase['purchase_category']; ?></td>
                    <td><?php echo ($purchase['purchase_category'] === 'Box' ? 1 : 2) * $purchase['purchase_amount']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="submit" name="finalize_winning" class="btn btn-success">Finalize Winning</button>
    </form>
</div>
<?php endif; ?>

<!-- Initialize DataTable -->
<script>
$(document).ready(function() {
    $('#winningRecordsTable').DataTable();
    $('#matchedPurchasesTable').DataTable();
});
</script>

</body>
</html>