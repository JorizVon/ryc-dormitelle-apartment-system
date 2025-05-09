<?php
session_start();
require_once 'db_connect.php';
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $unit_number = trim($_POST['unit_number']);
    $unit_code = trim($_POST['unit_code']);

    if (empty($unit_number) || empty($unit_code)) {
        $error = "Please fill in both unit number and unit code.";
    } else {
        $stmt = $conn->prepare("SELECT admin_ID, username, password FROM admin_account WHERE username = ?");
        $stmt->bind_param("s", $unit_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (password_verify($unit_code, $row['password'])) {
                $_SESSION['admin_ID'] = $row['admin_ID'];
                $_SESSION['username'] = $row['username'];
                header('Location: DASHBOARD.php');
                exit();
            } else {
                $error = "Invalid unit number or unit code.";
            }
        } else {
            $error = "Invalid unit number or unit code.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>RYC Dormitelle Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', sans-serif;
    }

    body {
      background: url('background.jpg') no-repeat center center/cover;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .login-container {
      background: rgba(255, 255, 255, 0.9);
      border-radius: 25px;
      padding: 40px;
      width: 100%;
      max-width: 450px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    .logo {
      text-align: center;
      margin-bottom: 20px;
    }

    .logo img {
      width: 60%;
      height: auto;
    }

    h1 {
      font-size: 28px;
      font-weight: bold;
      color: #000;
      text-align: center;
      margin-bottom: 5px;
    }

    .subtitle {
      font-size: 14px;
      color: #333;
      text-align: center;
      margin-bottom: 30px;
    }

    .form-group {
      margin-bottom: 15px;
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
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
    }

    .password-container {
      position: relative;
    }

    .password-container .form-input {
      width: 100%;
      padding-right: 40px;
    }

    .password-toggle {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
    }

    .password-toggle img {
      width: 18px;
      height: 18px;
    }

    .login-btn {
      width: 100%;
      padding: 12px;
      background-color: #2262B8;
      color: #fff;
      font-weight: bold;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 14px;
      margin-top: 10px;
      margin-bottom: 15px;
    }

    .login-btn:hover {
      background-color: #1a4d8c;
    }

    .signup-link {
      text-align: center;
      font-size: 13px;
    }

    .signup-link a {
      color: #2262B8;
      text-decoration: none;
    }

    .signup-link a:hover {
      text-decoration: underline;
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

  <form method="POST" class="login-container">
    <div class="logo">
      <img src="otherIcons/systemLogo.png" alt="RYC Dormitelle">
    </div>
    
    <h1>Login</h1>
    <p class="subtitle">Log in with your tenant credentials to access your account.</p>

    <?php if (!empty($error)) { echo "<div class='error' id='errorMessage'>$error</div>"; } ?>

    <div class="form-group">
      <label class="form-label" for="unit_number">Unit Number</label>
      <input type="text" name="unit_number" id="unit_number" class="form-input" placeholder="Enter your unit number" required>
    </div>

    <div class="form-group">
      <label class="form-label" for="unit_code">Unit Code</label>
      <div class="password-container">
        <input type="password" name="unit_code" id="password" class="form-input" placeholder="Enter your unit code" required>
        <button type="button" id="togglePassword" class="password-toggle">
          <img id="eyeIcon" src="otherIcons/closedeyeIcon.png" alt="Toggle visibility">
        </button>
      </div>
    </div>
    
    <button type="submit" class="login-btn">Login</button>
    
    <div class="signup-link">
      Not registered yet? <a href="SIGNINPAGE.php">Create an account here</a>
    </div>
  </form>

  <script>
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    togglePassword.addEventListener('click', () => {
      const isPassword = password.getAttribute('type') === 'password';
      password.setAttribute('type', isPassword ? 'text' : 'password');
      eyeIcon.src = isPassword ? 'otherIcons/openeyeIcon.png' : 'otherIcons/closedeyeIcon.png';
    });

    if (document.getElementById('errorMessage')) {
      setTimeout(() => {
        document.getElementById('errorMessage').style.opacity = '0';
        setTimeout(() => {
          document.getElementById('errorMessage').style.display = 'none';
        }, 500);
      }, 2000);
    }
  </script>
</body>
</html>