<?php
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';
require __DIR__ . '/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mailConfig = [
    'host' => 'sandbox.smtp.mailtrap.io',
    'port' => 2525,
    'username' => '170796d24a02e0',
    'password' => '1b5e337450d213',
    'from_email' => 'no-reply@furnitureshop.com',
    'from_name' => 'Furniture Shop',      
];

function sendMail(string $toEmail, string $toName, string $subject, string $bodyHtml, array $mailConfig): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $mailConfig['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailConfig['username'];
        $mail->Password   = $mailConfig['password'];
        $mail->Port       = $mailConfig['port'];

        $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->AltBody = strip_tags($bodyHtml);

        $mail->send();
        return true;
    } 
    catch (Exception $e) {
        $_SESSION['info'] = "Email could not be sent: Internet not available and/or could not reach SMTP server.";
        error_log('Mail error: ' . $mail->ErrorInfo);
        return false;
    }
}