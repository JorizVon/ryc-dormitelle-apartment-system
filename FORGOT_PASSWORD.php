<?php
// FORGOT_PASSWORD.php
session_start();
require_once 'db_connect.php'; // This loads Composer & .env variables

// Use PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = "Please enter your email.";
    } else {
        // 1. Check if email exists in DB
        $stmt = $conn->prepare("SELECT * FROM accounts WHERE email_account = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // 2. Generate Token
            $token = bin2hex(random_bytes(50)); 
            $expiry = date("Y-m-d H:i:s", strtotime('+1 hour'));

            // 3. Update Database
            $update = $conn->prepare("UPDATE accounts SET reset_token = ?, reset_expiry = ? WHERE email_account = ?");
            $update->bind_param("sss", $token, $expiry, $email);
            
            if ($update->execute()) {
                // 4. SEND EMAIL VIA PHPMAILER
                $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/RESET_PASSWORD.php?token=" . $token;
                
                $mail = new PHPMailer(true);

                try {
                    // Server settings
                    $mail->isSMTP();                                            
                    $mail->Host       = $_ENV['MAIL_HOST']; // This will be 'ryc-dormitelle.com'
                    $mail->SMTPAuth   = true;                                   
                    $mail->Username   = $_ENV['MAIL_USERNAME'];                 
                    $mail->Password   = $_ENV['MAIL_PASSWORD'];                 
                    
                    // IMPORTANT: For Port 465, use ENCRYPTION_SMTPS (SSL)
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;         
                    $mail->Port       = 465;                                    

                    // Recipients
                    $fromEmail = $_ENV['MAIL_USERNAME']; 
                    $fromName  = $_ENV['MAIL_NAME'] ?? 'RYC Dormitelle';
                    
                    $mail->setFrom($fromEmail, $fromName);
                    $mail->addAddress($email);     

                    // Content
                    $mail->isHTML(true);                                  
                    $mail->Subject = 'Reset Your Password - RYC Dormitelle';
                    $mail->Body    = "
                        <div style='font-family: Arial, sans-serif; color: #333;'>
                            <h2 style='color: #2262B8;'>Password Reset Request</h2>
                            <p>We received a request to reset your password.</p>
                            <p>Click the button below to create a new password:</p>
                            <p>
                                <a href='$resetLink' style='background-color: #2262B8; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a>
                            </p>
                            <p style='font-size: 12px; color: #666;'>Or copy this link: $resetLink</p>
                            <p style='font-size: 12px; color: #888;'>This link expires in 1 hour.</p>
                        </div>
                    ";
                    $mail->AltBody = "Reset your password by visiting: $resetLink";

                    $mail->send();
                    $message = "A reset link has been sent to your email address.";
                } catch (Exception $e) {
                    $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }

            } else {
                $error = "Database error. Please try again later.";
            }
        } else {
            // Security: Pretend it worked
            $message = "If an account exists with this email, a reset link has been sent.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="icon" type="image/png" href="otherIcons/pageicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: url('staticImages/logInbg.jpg') no-repeat center center/cover; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .overlay { background: rgba(255, 255, 255, 0.9); border-radius: 15px; padding: 40px; width: 100%; max-width: 450px; text-align: center; }
        .form-input { width: 100%; padding: 12px; margin: 15px 0; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .btn { width: 100%; padding: 12px; background-color: #2262B8; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
        .btn:hover { background-color: #1a4d8c; }
        .message { color: #155724; background: #d4edda; padding: 15px; border-radius: 5px; margin-bottom: 15px; text-align: left; border: 1px solid #c3e6cb;}
        .error { color: #721c24; background: #f8d7da; padding: 10px; border-radius: 5px; margin-bottom: 10px; border: 1px solid #f5c6cb; }
        .back-link { display: block; margin-top: 15px; color: #2262B8; text-decoration: none; }
    </style>
</head>
<body>
    <form method="POST" class="overlay">
        <h2>Reset Password</h2>
        <p>Enter your email address to receive a reset link.</p>
        
        <?php if (!empty($message)) echo "<div class='message'>$message</div>"; ?>
        <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>

        <input type="email" name="email" class="form-input" placeholder="Enter your email" required>
        <button type="submit" class="btn">Send Reset Link</button>
        <a href="LOGIN.php" class="back-link">Back to Login</a>
    </form>
</body>
</html>