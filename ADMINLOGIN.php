<?php
session_start();

// Database connection
require_once 'db_connect.php';

// Handle login form submission
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $passwordInput = trim($_POST['password']);

    if (empty($username) || empty($passwordInput)) {
        $error = "Please fill in both username and password.";
    } else {
        $stmt = $conn->prepare("SELECT admin_ID, username, password FROM admin_account WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (password_verify($passwordInput, $row['password'])) {
                // Password is correct, set session
                $_SESSION['admin_ID'] = $row['admin_ID'];
                $_SESSION['username'] = $row['username'];

                header('Location: DASHBOARD.php');
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login Page</title>
  <style>
    body {
      padding: 0;
      margin: 0;
      background-color: #2262B8;
      font-family: Arial, sans-serif;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .loginContainer {
      width: 300px;
      display: flex;
      flex-direction: column;
      gap: 15px;
      color: white;
    }
    .loginContainer label {
      font-size: 14px;
    }
    .loginContainer input {
      padding: 10px;
      border: 1px solid white;
      background-color: transparent;
      color: white;
      font-size: 14px;
    }
    .loginContainer input::placeholder {
      color: white;
      opacity: 0.7;
    }
    .loginContainer .logInbtn {
      padding: 10px;
      background-color: white;
      color: #01214B;
      border: none;
      cursor: pointer;
      font-weight: bold;
      font-size: 14px;
    }
    .loginContainer .logInbtn:hover {
      background-color: #01214B;
      color: #FFFFFF;
    }
    .passwordContainer {
      position: relative;
      display: flex;
    }
    .passwordContainer input {
      flex: 1;
    }
    .passwordContainer button {
      position: absolute;
      right: 0;
      top: 0;
      height: 100%;
      padding: 0 10px;
      background-color: transparent;
      border: none;
      cursor: pointer;
    }
    .passwordContainer button img {
      height: 16px;
      width: 16px;
    }
    .error {
      background-color: #FFCCCC;
      color: red;
      padding: 10px;
      margin-bottom: 10px;
      border-radius: 5px;
      text-align: center;
      opacity: 1;
      transition: opacity 2s ease-out;
    }
  </style>
</head>
<body>
  <form method="POST" action="" class="loginContainer">
      <?php if (!empty($error)) { echo "<div class='error' id='errorMessage'>$error</div>"; } ?>

      <label for="username">Username:</label>
      <input type="text" name="username" id="username" placeholder="Enter Username" required>

      <label for="password">Password:</label>
      <div class="passwordContainer">
        <input type="password" name="password" id="password" placeholder="Enter Password" required>
        <button type="button" id="togglePassword">
          <img id="eyeIcon" src="otherIcons/closedeyeIcon.png" alt="Toggle visibility" style="width: 20px; height: 20px;">
        </button>
      </div>
      
      <button type="submit" class="logInbtn">LOGIN</button>
  </form>
  <script>
    // Show/hide password toggle with image icon
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    togglePassword.addEventListener('click', () => {
      const isPassword = password.getAttribute('type') === 'password';
      password.setAttribute('type', isPassword ? 'text' : 'password');
      eyeIcon.src = isPassword ? 'otherIcons/openeyeIcon.png' : 'otherIcons/closedeyeIcon.png';
    });

    // Fade out error message after 2 seconds
    if (document.getElementById('errorMessage')) {
      setTimeout(function() {
        document.getElementById('errorMessage').style.opacity = '0'; // Start fade-out
      }, 2000); // 2000 milliseconds = 2 seconds
    }
  </script>

</body>
</html>