<?php
session_start();
require_once 'db_connect.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $passwordInput = trim($_POST['password']);

    if (empty($username) || empty($passwordInput)) {
        $error = "Please fill in both email and password.";
    } else {
        $stmt = $conn->prepare("SELECT admin_ID, username, password FROM admin_account WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (password_verify($passwordInput, $row['password'])) {
                $_SESSION['admin_ID'] = $row['admin_ID'];
                $_SESSION['username'] = $row['username'];
                header('Location: DASHBOARD.php');
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
        $stmt->close();
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
    
    <h1>Sign In</h1>
    <p class="subtitle">Use your registered tenant details to sign in securely.</p>

    <?php if (!empty($error)) { echo "<div class='error'>$error</div>"; } ?>

    <label class="form-label" for="username">Email Address</label>
    <input type="email" name="username" id="username" class="form-input" placeholder="Enter your email address" required>

    <label class="form-label" for="password">Password</label>
    <div style="position: relative;">
      <input type="password" name="password" id="password" class="form-input" placeholder="Enter your password" required>
      <button type="button" id="togglePassword" style="position: absolute; right: 12px; top: 12px; background: none; border: none; cursor: pointer;">
        <img id="eyeIcon" src="otherIcons/closedeyeIcon.png" alt="Toggle visibility" style="width: 18px; height: 18px;">
      </button>
    </div>

    <a href="#" class="forgot-password">Already Have an Account?</a>
    
    <button type="submit" class="sign-in-btn">Sign In</button>
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
</body>
</html>