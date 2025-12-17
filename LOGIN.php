<?php
session_start();
require_once 'db_connect.php'; // This loads your .env via Composer

$raw_id = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
$google_client_id = trim($raw_id, " \t\n\r\0\x0B\"'"); 

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $passwordInput = trim($_POST['password']);

    if (empty($email) || empty($passwordInput)) {
        $error = "Please fill in both email and password.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM accounts WHERE email_account = ? OR username = ?");
        $stmt->bind_param("ss", $email, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($passwordInput, $user['password'])) {
                $_SESSION['email_account'] = $user['email_account'];
                $_SESSION['user_type'] = $user['user_type']; 
                $_SESSION['login_success'] = true;
            } else {
                $error = "Invalid email/username or password.";
            }
        } else {
            $error = "Account not found.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>RYC Dormitelle - Log In</title>
  <link rel="icon" type="image/png" href="otherIcons/pageicon.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://accounts.google.com/gsi/client?hl=en" async defer></script>
  <style>
    body { margin: 0; font-family: 'Segoe UI', sans-serif; background: url('staticImages/homepagebg.png') no-repeat center center/cover; display: flex; justify-content: center; align-items: center; height: 100vh; }
    .overlay { background: rgba(255, 255, 255, 0.9); border-radius: 15px; padding: 40px; width: 100%; max-width: 450px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
    .logo { text-align: center; margin-bottom: 15px; }
    .logo img { width: 60%; height: auto; }
    h1 { margin: 0; font-size: 28px; font-weight: bold; color: #000; text-align: center; }
    .subtitle { font-size: 14px; color: #333; text-align: center; margin-bottom: 30px; }
    .form-label { display: block; font-size: 14px; color: #2262B8; margin-bottom: 5px; }
    .form-input { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; box-sizing: border-box; }
    
    .link-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .small-link { font-size: 13px; color: #2262B8; text-decoration: none; }
    .small-link:hover { text-decoration: underline; }

    .sign-in-btn { width: 100%; padding: 12px; background-color: #2262B8; color: #fff; font-weight: bold; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; margin-bottom: 10px; }
    .sign-in-btn:hover { background-color: #1a4d8c; }
    .google-btn-wrapper { width: 100%; margin-top: 10px; display: flex; justify-content: center; }
    .error { background-color: #FFCCCC; color: red; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 15px; }
  </style>
</head>
<body>

  <form method="POST" class="overlay">
    <div class="logo"><img src="otherIcons/systemLogo.png" alt="RYC Dormitelle"></div>
    <h1>Login</h1>
    <p class="subtitle">Access your account.</p>

    <?php if (!empty($error)) { echo "<div class='error'>$error</div>"; } ?>

    <label class="form-label" for="email">Email Address or Username</label>
    <input type="text" name="email" id="email" class="form-input" placeholder="Enter your email or username" required>

    <label class="form-label" for="password">Password</label>
    <div style="position: relative;">
      <input type="password" name="password" id="password" class="form-input" placeholder="Enter your password" required>
      <button type="button" id="togglePassword" style="position: absolute; right: 12px; top: 12px; background: none; border: none; cursor: pointer;">
        <img id="eyeIcon" src="otherIcons/closedeyeIcon.png" alt="Toggle visibility" style="width: 18px; height: 18px;">
      </button>
    </div>

    <div class="link-container">
        <a href="FORGOT_PASSWORD.php" class="small-link">Forgot Password?</a>
        <a href="SIGNUP.php" class="small-link">Create Account</a>
    </div>

    <button type="submit" class="sign-in-btn">Login</button>
    
   <div class="google-btn-wrapper notranslate" translate="no">
        <div id="g_id_onload"
             data-client_id="<?php echo htmlspecialchars($google_client_id); ?>"
             data-context="signin"
             data-ux_mode="popup"
             data-callback="handleCredentialResponse"
             data-auto_prompt="false">
        </div>
        <div class="g_id_signin"
             data-type="standard"
             data-shape="rectangular"
             data-theme="outline"
             data-text="continue_with"
             data-size="large"
             data-logo_alignment="left"
             data-width="400">
        </div>
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

    function handleCredentialResponse(response) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'google_auth.php');
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                var resp = xhr.responseText.trim();
                if (resp === 'success') {
                    window.location.reload(); 
                } else if (resp === 'error_not_found') {
                    alert('This Google account is not registered. Please Sign Up first.');
                } else {
                    alert('Google Login Failed: ' + resp);
                }
            }
        };
        xhr.send('credential=' + response.credential + '&context=signin');
    }
  </script>

  <?php if (isset($_SESSION['login_success']) && $_SESSION['login_success'] === true): ?>
    <div id="loginPopup" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: #d4edda; color: #155724; padding: 20px 30px; border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.3); font-size: 16px; font-weight: bold; z-index: 9999; text-align: center;">
      ✅ Login successful! Redirecting...
    </div>
    <script>
      setTimeout(() => {
        <?php if ($_SESSION['user_type'] === 'admin'): ?>
          window.location.href = "DASHBOARD.php";
        <?php elseif ($_SESSION['user_type'] === 'tenant'): ?>
          window.location.href = "./tenantwebview/TENANTHOMEPAGE.php";
        <?php else: ?>
          window.location.href = "./tenantwebview/USERHOMEPAGE.php";
        <?php endif; ?>
      }, 2000);
    </script>
    <?php unset($_SESSION['login_success'], $_SESSION['user_type']); ?>
  <?php endif; ?>
</body>
</html>