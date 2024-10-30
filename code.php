<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer library files
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
require 'phpmailer/src/Exception.php';

function testPHPMailer() {
    // Create an instance of PHPMailer
    $mail = new PHPMailer(true);

    try {
        // SMTP server configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com'; // Change this to your SMTP server (e.g., smtp.hostinger.com)
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sales@navbright.tech'; // Replace with your SMTP username
        $mail->Password   = 'JerryYee0902@'; // Replace with your SMTP password or app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use TLS encryption
        $mail->Port       = 465; // SMTP port (465 for SSL, 587 for TLS)

        // Set email sender and recipient
        $mail->setFrom('sales@navbright.tech', 'Account Navbright'); // Sender's email and name
        $mail->addAddress('account@navbright.tech', 'Account Team'); // Recipient's email and name

        // Email content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = 'PHPMailer Test Email';
        $mail->Body    = '<h1>Test Email</h1><p>This is a test email sent using PHPMailer with SMTP configuration.</p>';
        $mail->AltBody = 'This is the plain text version of the email content for non-HTML clients.';

        // Attempt to send the email
        if ($mail->send()) {
            echo 'Test email has been sent successfully!';
        } else {
            echo 'Test email could not be sent.';
        }
    } catch (Exception $e) {
        // Display error message if email sending fails
        echo 'Email could not be sent. PHPMailer Error: ' . $mail->ErrorInfo;
    }
}

// Call the test function
testPHPMailer();
?>