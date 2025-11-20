<?php
require __DIR__ . '/../includes/mail.php';

$recipientEmail = 'customer@example.com'; 
$recipientName  = 'Customer Name'; 

$subject = 'Mailtrap Test';
$body = '<p>If you see this in Mailtrap, PHPMailer is configured correctly.</p>';

$ok = sendMail($recipientEmail, $recipientName, $subject, $body, $mailConfig);

echo $ok ? "Mail sent" : "Mail failed";