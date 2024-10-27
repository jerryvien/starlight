<?php
session_start();
include('config/database.php');
include('config/utilities.php');

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

// Fetch agent filter if the user is an agent
$agent_filter = ($_SESSION['access_level'] === 'agent') ? $_SESSION['agent_id'] : null;

// Fetch purchase entries grouped by serial number
try {
    $query = "SELECT serial_number, customer_id, agent_id, purchase_datetime, 
              GROUP_CONCAT(purchase_no SEPARATOR ', ') as purchase_details, 
              SUM(purchase_amount) as subtotal FROM purchase_entries";
    if ($agent_filter) {
        $query .= " WHERE agent_id = :agent_id";
    }
    $query .= " GROUP BY serial_number ORDER BY purchase_datetime DESC";

    $stmt = $conn->prepare($query);
    if ($agent_filter) {
        $stmt->bindParam(':agent_id', $agent_filter);
    }
    $stmt->execute();
    $purchase_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching purchase entries: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Entries Grouped by Serial Number</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css">
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
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

            <div class="container-fluid">
                <h1 class="h3 mb-4 text-gray-800">Purchase Entries Grouped by Serial Number</h1>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
                    </div>
                    <div class="card-body">
                        <form id="filterForm">
                            <div class="row">
                                <?php if ($_SESSION['access_level'] === 'super_admin'): ?>
                                    <div class="col-md-4">
                                        <label for="agentFilter">Agent</label>
                                        <select id="agentFilter" class="form-control" name="agent_id">
                                            <option value="">All Agents</option>
                                            <?php
                                            // Fetch agents for filter
                                            $stmt = $conn->query("SELECT agent_id, agent_name FROM admin_access");
                                            $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            foreach ($agents as $agent) {
                                                echo '<option value="' . $agent['agent_id'] . '">' . htmlspecialchars($agent['agent_name']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                                <div class="col-md-4">
                                    <label for="purchaseDateFilter">Purchase Date</label>
                                    <input type="text" id="purchaseDateFilter" class="form-control" name="purchase_date" placeholder="Select Date Range">
                                </div>
                                <div class="col-md-4">
                                    <label for="purchaseNumberFilter">Purchase Number</label>
                                    <input type="text" id="purchaseNumberFilter" class="form-control" name="purchase_number" placeholder="Enter Purchase Number">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-12 text-right">
                                    <button type="button" id="applyFilter" class="btn btn-primary">Apply Filters</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Purchase Entries</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Serial Number</th>
                                        <th>Customer ID</th>
                                        <th>Agent ID</th>
                                        <th>Purchase Date</th>
                                        <th>Purchase Details</th>
                                        <th>Subtotal</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="dataBody">
                                    <?php foreach ($purchase_entries as $entry): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($entry['serial_number']); ?></td>
                                            <td><?php echo htmlspecialchars($entry['customer_id']); ?></td>
                                            <td><?php echo htmlspecialchars($entry['agent_id']); ?></td>
                                            <td><?php echo date('d-M-Y', strtotime($entry['purchase_datetime'])); ?></td>
                                            <td><?php echo htmlspecialchars($entry['purchase_details']); ?></td>
                                            <td>$<?php echo number_format($entry['subtotal'], 2); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-info" 
                                                        onclick="showReceiptPopup('<?php echo addslashes($entry['customer_id']); ?>', 
                                                                                  '<?php echo addslashes($entry['purchase_details']); ?>', 
                                                                                  '<?php echo number_format($entry['subtotal'], 2); ?>', 
                                                                                  '<?php echo addslashes($entry['agent_id']); ?>', 
                                                                                  '<?php echo addslashes($entry['serial_number']); ?>')">Reprint Receipt</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <?php include('config/footer.php'); ?>
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Wrapper -->

    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                "paging": true,
                "searching": true,
                "ordering": true,
                "pageLength": 10
            });

            $('#purchaseDateFilter').daterangepicker({
                locale: {
                    format: 'YYYY-MM-DD'
                },
                autoUpdateInput: false
            });

            $('#purchaseDateFilter').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
            });

            $('#applyFilter').on('click', function() {
                const agentId = $('#agentFilter').val();
                const purchaseDate = $('#purchaseDateFilter').val();
                const purchaseNumber = $('#purchaseNumberFilter').val();

                $.ajax({
                    url: 'fetch_filtered_data.php',
                    type: 'POST',
                    data: {
                        agent_id: agentId,
                        purchase_date: purchaseDate,
                        purchase_number: purchaseNumber
                    },
                    success: function(response) {
                        $('#dataBody').html(response);
                    },
                    error: function(xhr, status, error) {
                        alert('Error: ' + error);
                    }
                });
            });
        });

        // Function to show receipt popup
        function showReceiptPopup(customerName, purchaseDetails, subtotal, agentName, serialNumber) {
            generateReceiptPopup(customerName, purchaseDetails, subtotal, agentName, serialNumber);
        }

        // Generate the receipt popup
        function generateReceiptPopup(customerName, purchaseDetails, subtotal, agentName, serialNumber) {
            const receiptContent = `
                <div class="modal fade" id="receiptModal" tabindex="-1" role="dialog" aria-labelledby="receiptModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="receiptModalLabel">Receipt for Serial Number: ${serialNumber}</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p><strong>Customer Name:</strong> ${customerName}</p>
                                <p><strong>Agent Name:</strong> ${agentName}</p>
                                <p><strong>Purchase Details:</strong> ${purchaseDetails}</p>
                                <p><strong>Subtotal:</strong> $${subtotal}</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" onclick="downloadReceipt('${serialNumber}')">Download Receipt</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Append the modal to the body and show it
            $('body').append(receiptContent);
            $('#receiptModal').modal('show');

            // Remove the modal from the DOM after hiding it to avoid duplicates
            $('#receiptModal').on('hidden.bs.modal', function() {
                $(this).remove();
            });
        }

        // Function to download the receipt as a text file
        function downloadReceipt(serialNumber) {
            const receiptText = `Receipt for Serial Number: ${serialNumber}\n\nCustomer Name: ${customerName}\nAgent Name: ${agentName}\nPurchase Details: ${purchaseDetails}\nSubtotal: $${subtotal}`;
            
            const blob = new Blob([receiptText], { type: 'text/plain' });
            const link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = `Receipt_${serialNumber}.txt`;
            link.click();
        }
    </script>
</body>
</html>
