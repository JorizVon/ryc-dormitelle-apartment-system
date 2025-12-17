<?php
// SEND_OTP.php
session_start();
require_once 'db_connect.php'; // Loads Composer & .env

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Check if email already exists in DB
    $stmt = $conn->prepare("SELECT email_account FROM accounts WHERE email_account = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo "exists"; // Email already registered
        exit;
    }

    // Generate 6-digit OTP
    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_email'] = $email;

    // Send Email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'];
        $mail->Password   = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Port 465 SSL
        $mail->Port       = 465;

        $mail->setFrom($_ENV['MAIL_USERNAME'], $_ENV['MAIL_NAME'] ?? 'RYC Dormitelle');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Your Verification Code - RYC Dormitelle';
        $mail->Body    = "
            <h2>Email Verification</h2>
            <p>Your verification code is:</p>
            <h1 style='color: #2262B8; letter-spacing: 5px;'>$otp</h1>
            <p>Do not share this code with anyone.</p>
        ";

        $mail->send();
        echo "sent";
    } catch (Exception $e) {
        echo "error: " . $mail->ErrorInfo;
    }
}
?>