<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if (!isset($_POST['email'])) {
    echo "Email missing";
    exit;
}

$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
if (!$email) {
    echo "Invalid email";
    exit;
}

// Generate OTP
$otp = rand(100000, 999999);
$_SESSION['otp'] = $otp;
$_SESSION['otp_expires'] = time() + 120; // 2 minutes

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'deepprajapati1012@gmail.com';   // 👈 your gmail
    $mail->Password   = 'ybfv bmrc rjno bvkv';      // 👈 16 digit password
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('YOUR_GMAIL@gmail.com', 'Alpha Book');
    $mail->addAddress($email);

    $mail->Subject = 'Alpha Book - Email Verification OTP';
    $mail->Body    = "Hello,\n\nYour OTP is: $otp\n\nValid for 2 minutes.\n\nAlpha Book";

    $mail->send();
    echo "OTP sent successfully. OTP: $otp";
} catch (Exception $e) {
    echo "Email failed: {$mail->ErrorInfo}";
}
