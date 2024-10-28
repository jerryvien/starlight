<?php
$to = 'jerryvic0902@outlook.com';  // Replace with your test recipient email
$subject = 'Test Email from Hostinger PHP Mail';
$message = '<html><body><h1>This is a test email from Hostinger PHP mail()</h1></body></html>';
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: sales@navbright.tech"; // Replace with your valid email

if (mail($to, $subject, $message, $headers)) {
    echo "Email sent successfully!";
} else {
    echo "Failed to send email.";
}

?>
