<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'path/to/PHPMailer/src/Exception.php';
require 'path/to/PHPMailer/src/PHPMailer.php';
require 'path/to/PHPMailer/src/SMTP.php';

$mail = new PHPMailer(true);

try {
    // SMTP server settings
    $mail->isSMTP();
    $mail->Host = 'srv1367.main-hosting.eu'; // Your Hostinger SMTP server
    $mail->SMTPAuth = true;
    $mail->Username = 'sales@navbright.tech'; // Your email address
    $mail->Password = 'JerryYee0902'; // Your email password
    $mail->SMTPSecure = 'ssl'; // Use 'ssl' for port 465, 'tls' for port 587
    $mail->Port = 465; // Port number for SSL, use 587 for TLS

    // Sender and recipient settings
    $mail->setFrom('sales@navbright.tech', 'NavBright Sales'); // Sender email and name
    $mail->addAddress('jerryvic0902@outlook.com'); // Replace with recipient's email address
    $mail->addReplyTo('sales@navbright.tech', 'Reply NavBright'); // Optional reply-to

    // Email subject and body
    $mail->Subject = 'Test Email from NavBright';
    $mail->Body = '<h1>This is a test email sent from NavBright using PHPMailer</h1>';
    $mail->isHTML(true);

    // Send the email
    $mail->send();
    echo 'Email sent successfully!';
} catch (Exception $e) {
    echo "Failed to send email. Error: {$mail->ErrorInfo}";
}


?>
