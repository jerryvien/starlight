<?php
function testMailFunction() {
    $to = 'jerryvic0902@outlook.com'; // Replace with your email for testing
    $subject = 'Test Email from PHP mail()';
    $message = 'This is a test email to verify the mail() function.';
    $headers = "From: noreply@navbright.tech\r\n"; // Replace with your sender email

    if (mail($to, $subject, $message, $headers)) {
        echo 'Test email sent successfully.';
    } else {
        echo 'Failed to send the test email.';
    }
}

// Call the test function
testMailFunction();
?>