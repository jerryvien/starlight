<?php
session_start();

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve the serial number from the form input
    $providedSerialNumber = strtoupper(trim($_POST['serial_number']));

    // Validate the provided serial number
    if (!empty($providedSerialNumber)) {
        $_SESSION['serial_number'] = $providedSerialNumber;
        $_SESSION['is_valid'] = verifySerialNumber($providedSerialNumber);
    } else {
        $_SESSION['error'] = "Please enter a valid serial number.";
    }

    // Redirect to avoid form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Function to verify the serial number
function verifySerialNumber($providedSerialNumber) {
    $key = "KENSTARLIGHT"; // Secret encryption key

    echo "<div>Starting verification for serial number: <strong>$providedSerialNumber</strong></div>";

    // Iterate through the range of possible random numbers
    for ($random_number = 100000; $random_number <= 999999; $random_number++) {
        // Generate serial number using the key and current random number
        $generatedSerialNumber = substr(md5($key . $random_number), 0, 12);

        // Display the current attempt (for demonstration)
        echo "<div>Checking with random number: <strong>$random_number</strong> | Generated: <strong>$generatedSerialNumber</strong></div>";

        // If a match is found, return true
        if ($generatedSerialNumber === $providedSerialNumber) {
            echo "<div style='color: green; font-weight: bold;'>Match found! Serial number is valid.</div>";
            return true;
        }
    }

    // If no match is found after all iterations, return false
    echo "<div style='color: red; font-weight: bold;'>No match found. Serial number is not valid.</div>";
    return false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serial Number Verification</title>
    <style>
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .input-field, .submit-btn { margin-bottom: 15px; padding: 10px; font-size: 16px; }
        .message { padding: 10px; margin: 10px 0; font-size: 16px; }
        .success { color: green; }
        .error { color: red; }
        .verification-log { background-color: #f9f9f9; padding: 10px; margin-top: 10px; border: 1px solid #ccc; max-height: 300px; overflow-y: scroll; }
    </style>
</head>
<body>

<div class="container">
    <h1>Verify Serial Number</h1>

    <!-- Form for serial number input -->
    <form method="POST" action="">
        <input type="text" name="serial_number" class="input-field" placeholder="Enter Serial Number" required>
        <button type="submit" class="submit-btn">Verify</button>
    </form>

    <!-- Display session messages -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="message error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php elseif (isset($_SESSION['is_valid'])): ?>
        <?php if ($_SESSION['is_valid']): ?>
            <div class="message success">The serial number is valid!</div>
        <?php else: ?>
            <div class="message error">The serial number is not valid!</div>
        <?php endif; ?>
        <?php unset($_SESSION['is_valid']); ?>
    <?php endif; ?>

    <!-- Log of verification process -->
    <div class="verification-log">
        <?php
        // If a serial number is being verified, display the log
        if (isset($_SESSION['serial_number'])) {
            verifySerialNumber($_SESSION['serial_number']);
            unset($_SESSION['serial_number']); // Clear the serial number from session after verification
        }
        ?>
    </div>
</div>

</body>
</html>