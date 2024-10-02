<?php
session_start();
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

// Fetch winning records
try {
    $stmt = $conn->query("SELECT * FROM winning_record ORDER BY winning_date DESC");
    $winning_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching winning records: " . $e->getMessage());
}

// Handle form submission for matching purchases
$winning_record = null;
$matching_purchases = []; // Initialize as an empty array
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['select_winning_record'])) {
    $winning_id = $_POST['select_winning_record'];

    // Fetch winning record
    $stmt = $conn->prepare("SELECT * FROM winning_record WHERE id = :winning_id");
    $stmt->bindParam(':winning_id', $winning_id);
    $stmt->execute();
    $winning_record = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($winning_record) {
        // Generate permutations of winning number
        $winning_number = $winning_record['winning_number'];
        $winning_combinations = generate_combinations($winning_number);

        // Prepare placeholders for the IN clause dynamically
        $in_placeholders = implode(',', array_map(fn($key) => ":winning_comb_{$key}", array_keys($winning_combinations)));

        // Fetch matching purchase entries where purchase date matches exactly with the winning date
        $purchase_stmt = $conn->prepare("
            SELECT p.*, c.customer_name, a.agent_name 
            FROM purchase_entries p
            JOIN customer_details c ON p.customer_id = c.customer_id
            JOIN admin_access a ON p.agent_id = a.agent_id
            WHERE p.result NOT IN ('Win', 'Loss') 
              AND DATE(p.purchase_datetime) = :winning_date
              AND p.purchase_no IN ($in_placeholders)
        ");

        // Bind the :winning_date parameter
        $purchase_stmt->bindParam(':winning_date', $winning_record['winning_date']);

        // Bind the winning combinations dynamically
        foreach ($winning_combinations as $key => $combination) {
            $purchase_stmt->bindValue(":winning_comb_{$key}", $combination);
        }

        // Execute the statement
        $purchase_stmt->execute();
        $matching_purchases = $purchase_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Serialize the matching purchases so we can persist them across form submissions
        $serialized_purchases = base64_encode(serialize($matching_purchases));
    }
}

// Handle form submission for finalizing the winning results
if (isset($_POST['finalize_winning'])) {
    $winning_record_id = $_POST['winning_record_id'];

    // Unserialize the matching purchases from the hidden input
    if (isset($_POST['matching_purchases'])) {
        $matching_purchases = unserialize(base64_decode($_POST['matching_purchases']));
    }

    // Fetch winning record to calculate winning amounts
    $stmt = $conn->prepare("SELECT * FROM winning_record WHERE id = :winning_record_id");
    $stmt->bindParam(':winning_record_id', $winning_record_id);
    $stmt->execute();
    $winning_record = $stmt->fetch(PDO::FETCH_ASSOC);

    // Loop through all matched purchases
    foreach ($matching_purchases as $purchase) {
        // Check if this entry is a winner or not
        $is_winner = in_array($purchase['purchase_no'], generate_combinations($winning_record['winning_number']));

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
        $winning_category = $winning_record['winning_game'] ?? 'Unknown';  // Handle missing key
        $winning_factor = ($winning_category === 'Box') ? 1 : 2;
        $winning_amount = $is_winner ? $winning_factor * $purchase['purchase_amount'] : 0;

        $update_stmt->bindParam(':result', $result);
        $update_stmt->bindParam(':winning_category', $winning_category);
        $update_stmt->bindParam(':winning_amount', $winning_amount);
        $update_stmt->bindParam(':winning_number', $winning_record['winning_number']);
        $update_stmt->bindParam(':winning_record_id', $winning_record_id);
        $update_stmt->bindParam(':purchase_id', $purchase['id']);

        $update_stmt->execute();
    }

    // Update unmatched entries to 'Loss'
    update_loss_status($winning_record, $conn);

    // Update total payout for the winning record
    update_total_payout($winning_record_id, $conn);

    // Mark the winning listing as settled
    $update_winning_record_stmt = $conn->prepare("
        UPDATE winning_record
        SET winning_listing = true
        WHERE id = :winning_record_id
    ");
    $update_winning_record_stmt->bindParam(':winning_record_id', $winning_record_id);
    $update_winning_record_stmt->execute();

    // Success message
    $success_message = "Winning results have been updated successfully!";
}
// Utility function to update unmatched records to 'Loss'
function update_loss_status($winning_record, $conn) {
    try {
        // Update all non-win purchases for the same date as the winning record
        $update_stmt = $conn->prepare("
            UPDATE purchase_entries
            SET result = 'Loss'
            WHERE result NOT IN ('Win')
              AND DATE(purchase_datetime) = :winning_date
              AND winning_record_id IS NULL
        ");
        $update_stmt->bindParam(':winning_date', $winning_record['winning_date']);
        $update_stmt->execute();
    } catch (Exception $e) {
        die("Error updating loss status: " . $e->getMessage());
    }
}

// Function to update the total payout for the winning record
function update_total_payout($winning_record_id, $conn) {
    try {
        // Calculate the total payout by summing up the winning amounts from matched purchase entries
        $stmt = $conn->prepare("
            SELECT SUM(winning_amount) AS total_payout
            FROM purchase_entries
            WHERE winning_record_id = :winning_record_id
              AND result = 'Win'
        ");
        $stmt->bindParam(':winning_record_id', $winning_record_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Update the winning_record table with the total payout
        $update_stmt = $conn->prepare("
            UPDATE winning_record
            SET winning_total_payout = :total_payout
            WHERE id = :winning_record_id
        ");
        $update_stmt->bindParam(':total_payout', $result['total_payout']);
        $update_stmt->bindParam(':winning_record_id', $winning_record_id);
        $update_stmt->execute();
    } catch (Exception $e) {
        die("Error updating total payout: " . $e->getMessage());
    }
}

// Generate number permutations
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Winning Record Matching</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <script src="vendor/chart.js/Chart.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
</head>
<body>

<div id="wrapper">
        <!-- Sidebar -->
        <?php include('config/sidebar.php'); ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <?php include('config/topbar.php'); ?>

                <!-- Success Message -->
                    <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success_message; ?>
                    </div>
                    <?php endif; ?>


                <!-- Winning Records Table -->
                <div class="container-fluid">
                    <h2>Winning Records</h2>
                    <table id="winningRecordsTable" class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Winning Number</th>
                                <th>Winning Game</th>
                                
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
                                
                                <td><?php echo $record['winning_date']; ?></td>
                                <td><?php echo $record['winning_total_payout']; ?></td>
                                <td>
                                <form method="POST">
                                    <?php if ($record['winning_listing']): ?>
                                        <button class="btn btn-success" disabled>Settled</button>
                                    <?php else: ?>
                                        <button type="submit" name="select_winning_record" value="<?php echo $record['id']; ?>" class="btn btn-primary">Huat!!</button>
                                    <?php endif; ?>
                                </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php
                    // Calculate the total sum of the winning amounts for the matched records
                    $subtotal_winning_amount = 0;
                    foreach ($matching_purchases as $purchase) {
                        $winning_category = $winning_record['winning_game'] ?? 'Unknown';
                        $winning_factor = ($winning_category === 'Box') ? 1 : 2;
                        $winning_amount = $winning_factor * $purchase['purchase_amount'];
                        $subtotal_winning_amount += $winning_amount;
                    }
                ?>

                <!-- Matching Purchases Table -->
                <?php if (!empty($matching_purchases)): ?>
                <div class="container-fluid">
                    <h2>Matched Purchase Entries</h2>
                    <form method="POST">
                        <input type="hidden" name="winning_record_id" value="<?php echo $winning_id; ?>">
                        <input type="hidden" name="matching_purchases" value="<?php echo $serialized_purchases; ?>">
                        <table id="matchedPurchasesTable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Customer Name</th>
                                    <th>Purchase No</th>
                                    <th>Purchase Amount</th>
                                    <th>Purchase Date</th>
                                    <th>Agent Name</th>
                                    <th>Winning Category</th>
                                    <th>Winning Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($matching_purchases as $purchase): ?>
                                <?php
                                    $winning_category = $winning_record['winning_game'] ?? 'Unknown';
                                    $winning_factor = ($winning_category === 'Box') ? 1 : 2;
                                    $winning_amount = $winning_factor * $purchase['purchase_amount'];
                                ?>
                                <tr>
                                    <td><?php echo $purchase['customer_name'] ?? 'N/A'; ?></td>
                                    <td><?php echo $purchase['purchase_no']; ?></td>
                                    <td><?php echo $purchase['purchase_amount']; ?></td>
                                    <td><?php echo $purchase['purchase_datetime']; ?></td>
                                    <td><?php echo $purchase['agent_name'] ?? 'N/A'; ?></td>
                                    <td><?php echo $winning_category; ?></td>
                                    <td><?php echo $winning_amount; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <!-- Subtotal Row -->
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="text-right"><strong>Subtotal Winning Amount:</strong></td>
                                    <td><strong><?php echo $subtotal_winning_amount; ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                        <button type="submit" name="finalize_winning" class="btn btn-success" onclick="return confirm('Are you sure you want to finalize the winning entries?');">Finalize Winning</button>
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

            </div>
                <!-- End of Page Content -->

                <!-- Footer -->
                <?php include('config/footer.php'); ?>
            </div>
            <!-- End of Content Wrapper -->
        </div>
        <!-- End of Wrapper -->
    </body>
</html>