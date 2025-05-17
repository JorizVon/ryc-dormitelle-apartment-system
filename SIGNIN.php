<?php
session_start();
require_once 'db_connect.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $passwordInput = trim($_POST['password']);

    if (empty($email) || empty($passwordInput) || empty($username)) {
        $error = "Please fill in all fields.";
    } else {
        // Check if email already exists to prevent duplicate insertion
        $checkStmt = $conn->prepare("SELECT * FROM accounts WHERE email_account = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $error = "This email is already registered.";
        } else {
            // Hash the password before storing it
            $hashedPassword = password_hash($passwordInput, PASSWORD_DEFAULT);
            $user_type = "user";

            // CORRECTED: Use $stmt consistently
            $stmt = $conn->prepare("INSERT INTO accounts (username, email_account, password, user_type) VALUES (?, ?, ?, ?)");

            if ($stmt) {
                $stmt->bind_param("ssss", $username, $email, $hashedPassword, $user_type);

                if ($stmt->execute()) {
                    $_SESSION['account_created'] = true;
                    header('Location: SIGNIN.php');
                    exit();
                } else {
                    $error = "An error occurred during registration.";
                }

                $stmt->close();
            } else {
                $error = "Database error: " . $conn->error;
            }
        }

        $checkStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>RYC Dormitelle - Sign In</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Google Identity Services -->
  <script src="https://accounts.google.com/gsi/client" async defer></script>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: url('staticImages/logInbg.jpg') no-repeat center center/cover;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .overlay {
      background: rgba(255, 255, 255, 0.9);
      border-radius: 15px;
      padding: 40px;
      width: 100%;
      max-width: 450px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    .logo {
      text-align: center;
      margin-bottom: 15px;
    }

    .logo img {
      width: 60%;
      height: auto;
    }

    h1 {
      margin: 0;
      font-size: 28px;
      font-weight: bold;
      color: #000;
      text-align: center;
    }

    .subtitle {
      font-size: 14px;
      color: #333;
      text-align: center;
      margin-bottom: 30px;
    }

    .form-label {
      display: block;
      font-size: 14px;
      color: #2262B8;
      margin-bottom: 5px;
    }

    .form-input {
      width: 100%;
      padding: 12px;
      margin-bottom: 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
      box-sizing: border-box;
    }

    .forgot-password {
      display: block;
      text-align: right;
      font-size: 13px;
      color: #2262B8;
      text-decoration: none;
      margin-bottom: 20px;
    }

    .forgot-password:hover {
      text-decoration: underline;
    }

    .sign-in-btn {
      width: 100%;
      padding: 12px;
      background-color: #2262B8;
      color: #fff;
      font-weight: bold;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 14px;
      margin-bottom: 10px;
    }

    .sign-in-btn:hover {
      background-color: #1a4d8c;
    }

    .google-btn {
      width: 100%;
      padding: 12px;
      background-color: #fff;
      color: #333;
      border: 1px solid #ddd;
      border-radius: 8px;
      cursor: pointer;
      font-size: 14px;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .google-btn:hover {
      background-color: #f5f5f5;
    }

    .error {
      background-color: #FFCCCC;
      color: red;
      padding: 10px;
      border-radius: 5px;
      text-align: center;
      margin-bottom: 15px;
    }
  </style>
</head>
<body>

  <form method="POST" class="overlay">
    <div class="logo">
      <img src="otherIcons/systemLogo.png" alt="RYC Dormitelle">
    </div>
    
    <h1>Sign Up</h1>
    <p class="subtitle">Register Your email account here.</p>

    <?php if (!empty($error)) { echo "<div class='error'>$error</div>"; } ?>

    <label class="form-label" for="username">Username</label>
    <input type="text" name="username" id="username" class="form-input" placeholder="Enter username" required>

    <label class="form-label" for="email">Email Address</label>
    <input type="email" name="email" id="email" class="form-input" placeholder="Enter your email address" required>

    <label class="form-label" for="password">Password</label>
    <div style="position: relative;">
      <input type="password" name="password" id="password" class="form-input" placeholder="Enter your password" required>
      <button type="button" id="togglePassword" style="position: absolute; right: 12px; top: 12px; background: none; border: none; cursor: pointer;">
        <img id="eyeIcon" src="otherIcons/closedeyeIcon.png" alt="Toggle visibility" style="width: 18px; height: 18px;">
      </button>
    </div>

    <a href="LOGIN.php" class="forgot-password">Already Have an Account?</a>
    
    <button type="submit" class="sign-in-btn">Sign up</button>
    <button type="button" class="google-btn">Continue with Google</button>
  </form>

  <script>
    // Password visibility toggle
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    togglePassword.addEventListener('click', () => {
      const isPassword = password.getAttribute('type') === 'password';
      password.setAttribute('type', isPassword ? 'text' : 'password');
      eyeIcon.src = isPassword ? 'otherIcons/openeyeIcon.png' : 'otherIcons/closedeyeIcon.png';
    });

    // The JavaScript for Google Sign-In would go here
  </script>
  <?php if (isset($_SESSION['account_created']) && $_SESSION['account_created']) : ?>
    <script>
      window.onload = function() {
        // Create the modal container
        const modal = document.createElement('div');
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100%';
        modal.style.height = '100%';
        modal.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        modal.style.display = 'flex';
        modal.style.justifyContent = 'center';
        modal.style.alignItems = 'center';
        modal.style.zIndex = '9999';

        // Create the popup box
        const popup = document.createElement('div');
        popup.style.background = 'white';
        popup.style.padding = '30px 20px';
        popup.style.borderRadius = '12px';
        popup.style.boxShadow = '0 2px 10px rgba(0,0,0,0.3)';
        popup.style.textAlign = 'center';
        popup.innerHTML = `
          <h2 style="margin-bottom: 10px;">Success!</h2>
          <p style="margin-bottom: 20px;">Your account has been created successfully.</p>
          <button id="okBtn" style="padding: 10px 20px; border: none; background-color: #2262B8; color: white; border-radius: 6px; font-size: 14px; cursor: pointer;">OK</button>
        `;

        modal.appendChild(popup);
        document.body.appendChild(modal);

        // Add click event
        document.getElementById('okBtn').addEventListener('click', function() {
          window.location.href = 'LOGIN.php';
        });
      };
    </script>
    <?php unset($_SESSION['account_created']); ?>
  <?php endif; ?>

</body>
</html>