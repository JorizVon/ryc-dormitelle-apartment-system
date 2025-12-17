<?php
// RESET_PASSWORD.php
session_start();
require_once 'db_connect.php';

$error = "";
$success = "";

// 1. GET THE TOKEN FROM URL (First Load) OR FORM (Submission)
$token = $_GET['token'] ?? $_POST['token'] ?? "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $new_pass = trim($_POST['password']);

    if (empty($token)) {
        $error = "Missing reset token.";
    } elseif (strlen($new_pass) < 6) { // Basic validation
        $error = "Password must be at least 6 characters.";
    } else {
        // 2. Validate Token and Expiry in Database
        $stmt = $conn->prepare("SELECT * FROM accounts WHERE reset_token = ? AND reset_expiry > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            // 3. Update Password
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            
            // Clear the token so it can't be used again
            $update = $conn->prepare("UPDATE accounts SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE reset_token = ?");
            $update->bind_param("ss", $hashed, $token);
            
            if ($update->execute()) {
                $success = "Password updated successfully!";
            } else {
                $error = "Failed to update password in database.";
            }
        } else {
            $error = "This link is invalid or has expired. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Set New Password</title>
    <link rel="icon" type="image/png" href="otherIcons/pageicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: url('staticImages/logInbg.jpg') no-repeat center center/cover; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .overlay { background: rgba(255, 255, 255, 0.9); border-radius: 15px; padding: 40px; width: 100%; max-width: 450px; text-align: center; }
        .form-input { width: 100%; padding: 12px; margin: 15px 0; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .btn { width: 100%; padding: 12px; background-color: #2262B8; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
        .message { color: #155724; background: #d4edda; padding: 10px; border-radius: 5px; margin-bottom: 10px; }
        .error { color: #721c24; background: #f8d7da; padding: 10px; border-radius: 5px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <form method="POST" class="overlay">
        <h2>Set New Password</h2>
        
        <?php if ($success): ?>
            <div class="message"><?php echo $success; ?></div>
            <p>Redirecting to login...</p>
            <script>setTimeout(() => window.location.href = 'LOGIN.php', 2000);</script>
        <?php else: ?>
            
            <?php if ($error) echo "<div class='error'>$error</div>"; ?>

            <!-- IMPORTANT: Pass the token to the POST request -->
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <label style="display:block; text-align:left; color:#2262B8; font-size:14px;">New Password</label>
            <input type="password" name="password" class="form-input" placeholder="Enter new password" required>
            
            <button type="submit" class="btn">Update Password</button>
        <?php endif; ?>
    </form>
</body>
</html>