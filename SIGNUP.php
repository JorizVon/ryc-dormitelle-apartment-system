<?php
session_start();
require_once 'db_connect.php'; 

// Get Google Client ID from environment
$google_client_id = $_ENV['GOOGLE_CLIENT_ID'] ?? '';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify OTP First
    if (!isset($_POST['otp_code']) || $_POST['otp_code'] != $_SESSION['otp']) {
        $error = "Invalid Verification Code. Please verify your email first.";
    } elseif ($_POST['email'] != $_SESSION['otp_email']) {
        $error = "The verified email does not match the input email.";
    } else {
        // 2. Process Registration
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $passwordInput = trim($_POST['password']);
        $image_path_for_db = null; 

        // Image Handling
        if (isset($_FILES['user_image']) && $_FILES['user_image']['error'] === UPLOAD_ERR_OK) {
            $image = $_FILES['user_image'];
            $upload_dir = 'user_images/';
            $img_ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($img_ext, $allowed_ext)) {
                if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);
                $new_img_name = uniqid('user_', true) . '.' . $img_ext;
                if (move_uploaded_file($image['tmp_name'], $upload_dir . $new_img_name)) {
                    $image_path_for_db = $new_img_name;
                }
            } else {
                $error = "Invalid image format.";
            }
        }

        if (empty($error)) {
            $hashedPassword = password_hash($passwordInput, PASSWORD_DEFAULT);
            $user_type = "user";

            $stmt = $conn->prepare("INSERT INTO accounts (username, email_account, password, user_type, user_image) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sssss", $username, $email, $hashedPassword, $user_type, $image_path_for_db);
                if ($stmt->execute()) {
                    // Success! Clear OTP and redirect
                    unset($_SESSION['otp'], $_SESSION['otp_email']);
                    header('Location: LOGIN.php?signup=success');
                    exit();
                } else {
                    $error = "Database error: " . $conn->error;
                }
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>RYC Dormitelle - Sign Up</title>
  <link rel="icon" type="image/png" href="otherIcons/pageicon.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <!-- Google Script (English forced) -->
  <script src="https://accounts.google.com/gsi/client?hl=en" async defer></script>
  <!-- JQuery for the OTP Button logic -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <style>
    /* --- ORIGINAL DESIGN STYLES --- */
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: url('staticImages/homepagebg.png') no-repeat center center/cover;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    .overlay {
      background: rgba(255, 255, 255, 0.9);
      border-radius: 15px;
      padding: 40px;
      width: 100%;
      max-width: 450px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    .logo { text-align: center; margin-bottom: 15px; }
    .logo img { width: 60%; height: auto; }
    h1 { margin: 0; font-size: 28px; font-weight: bold; color: #000; text-align: center; }
    .subtitle { font-size: 14px; color: #333; text-align: center; margin-bottom: 30px; }
    .form-label { display: block; font-size: 14px; color: #2262B8; margin-bottom: 5px; }
    
    .form-input {
      width: 100%;
      padding: 12px;
      margin-bottom: 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
      box-sizing: border-box;
    }

    .forgot-password { display: block; text-align: right; font-size: 13px; color: #2262B8; text-decoration: none; margin-bottom: 20px; }
    .forgot-password:hover { text-decoration: underline; }

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
    .sign-in-btn:hover { background-color: #1a4d8c; }
    
    /* Disabled state for Sign Up button (until verified) */
    .sign-in-btn:disabled {
      background-color: #ccc;
      cursor: not-allowed;
    }

    .error { background-color: #FFCCCC; color: red; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 15px; }
    
    /* Image Preview */
    #imagePreviewContainer { display: flex; justify-content: center; margin-bottom: 15px; }
    #imagePreview { width: 100px; height: 100px; border: 2px dashed #ccc; border-radius: 50%; object-fit: cover; display: block; background-color: #f8f8f8; cursor: pointer; }
    #user_image { display: none; }

    /* --- NEW STYLES FOR OTP FUNCTIONALITY --- */
    .email-group { display: flex; gap: 10px; align-items: flex-start; }
    
    /* Verify button styled to match your theme */
    .verify-btn {
        padding: 12px 15px;
        background-color: #28a745; /* Green for verify action */
        color: #fff;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 13px;
        font-weight: bold;
        white-space: nowrap;
        height: 42px; /* Matches input height */
    }
    .verify-btn:hover { background-color: #218838; }
    .verify-btn:disabled { background-color: #ccc; cursor: not-allowed; }

    /* Hidden OTP Section */
    #otp_section {
        display: none; /* Hidden by default */
        background-color: #f1f8ff;
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #d0e3f0;
        margin-bottom: 15px;
    }

    /* Google Button Wrapper */
    .google-btn-wrapper {
      width: 100%;
      margin-top: 10px;
      display: flex;
      justify-content: center;
    }
  </style>
</head>
<body>

  <form method="POST" class="overlay" enctype="multipart/form-data">
    <div class="logo">
      <img src="otherIcons/systemLogo.png" alt="RYC Dormitelle">
    </div>
    <h1>Sign Up</h1>
    <p class="subtitle">Register your account here.</p>
    <?php if (!empty($error)) { echo "<div class='error'>$error</div>"; } ?>
    
    <!-- Profile Picture -->
    <label class="form-label">Profile Picture (Optional)</label>
    <div id="imagePreviewContainer">
        <img id="imagePreview" src="otherIcons/adminIcon.png" alt="Image Preview" title="Click to upload a profile picture">
    </div>
    <input type="file" name="user_image" id="user_image" accept="image/jpeg, image/png, image/gif">

    <!-- Username -->
    <label class="form-label" for="username">Username</label>
    <input type="text" name="username" id="username" class="form-input" placeholder="Enter username" required>

    <!-- Email & Verify Button -->
    <label class="form-label" for="email">Email Address</label>
    <div class="email-group">
        <input type="email" name="email" id="email" class="form-input" placeholder="Enter your email address" required>
        <button type="button" id="sendOtpBtn" class="verify-btn">Verify</button>
    </div>

    <!-- Hidden OTP Section -->
    <div id="otp_section">
        <label class="form-label" style="color:#000;">Verification Code sent to email:</label>
        <input type="text" name="otp_code" id="otp_code" class="form-input" placeholder="Enter 6-digit code" style="margin-bottom: 5px;">
        <small id="otpStatus" style="color: #666; font-size: 12px;">Check your inbox or spam folder.</small>
    </div>

    <!-- Password -->
    <label class="form-label" for="password">Password</label>
    <div style="position: relative;">
      <input type="password" name="password" id="password" class="form-input" placeholder="Enter your password" required>
      <button type="button" id="togglePassword" style="position: absolute; right: 12px; top: 12px; background: none; border: none; cursor: pointer;">
        <img id="eyeIcon" src="otherIcons/closedeyeIcon.png" alt="Toggle visibility" style="width: 18px; height: 18px;">
      </button>
    </div>

    <a href="LOGIN.php" class="forgot-password">Already Have an Account?</a>
    
    <!-- Sign Up Button (Disabled until email verified) -->
    <button type="submit" class="sign-in-btn" id="submitBtn" disabled title="Please verify email first">Sign up</button>
    
    <!-- Google Button -->
    <div class="google-btn-wrapper notranslate" translate="no">
        <div id="g_id_onload"
             data-client_id="<?php echo htmlspecialchars($google_client_id); ?>"
             data-context="signup"
             data-ux_mode="popup"
             data-callback="handleCredentialResponse"
             data-auto_prompt="false">
        </div>
        <div class="g_id_signin"
             data-type="standard"
             data-shape="rectangular"
             data-theme="outline"
             data-text="signup_with"
             data-size="large"
             data-logo_alignment="left"
             data-width="400">
        </div>
    </div>
  </form>

  <script>
    // 1. Image Preview Logic
    const imagePreview = document.getElementById('imagePreview');
    const userImageInput = document.getElementById('user_image');
    imagePreview.addEventListener('click', () => userImageInput.click());
    userImageInput.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => { imagePreview.src = e.target.result; };
            reader.readAsDataURL(file);
        }
    });

    // 2. Password Toggle Logic
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    togglePassword.addEventListener('click', () => {
      const isPassword = password.getAttribute('type') === 'password';
      password.setAttribute('type', isPassword ? 'text' : 'password');
      eyeIcon.src = isPassword ? 'otherIcons/openeyeIcon.png' : 'otherIcons/closedeyeIcon.png';
    });

    // 3. OTP Email Verification Logic (AJAX)
    $('#sendOtpBtn').click(function() {
        var email = $('#email').val();
        
        // Simple HTML5 validation check
        if(email === '' || !email.includes('@')) { 
            alert('Please enter a valid email address first.'); 
            return; 
        }

        var btn = $(this);
        btn.text('Sending...').prop('disabled', true);

        // Send request to SEND_OTP.php
        $.post('SEND_OTP.php', { email: email }, function(response) {
            response = response.trim();
            
            if(response === 'sent') {
                alert('✅ Verification code sent! Please check your email (and Spam folder).');
                $('#otp_section').slideDown(); // Show the OTP input
                $('#email').prop('readonly', true); // Lock the email input
                btn.text('Sent').css('background-color', '#ccc'); // Disable button visually
            } else if (response === 'exists') {
                alert('⚠️ This email is already registered. Please log in instead.');
                btn.text('Verify').prop('disabled', false);
            } else {
                alert('❌ Error sending email: ' + response);
                btn.text('Verify').prop('disabled', false);
            }
        }).fail(function() {
            alert('Network error. Please try again.');
            btn.text('Verify').prop('disabled', false);
        });
    });

    // 4. Enable Sign Up Button when OTP is typed
    $('#otp_code').on('keyup', function() {
        var code = $(this).val();
        if(code.length === 6) {
            $('#submitBtn').prop('disabled', false).css('background-color', '#2262B8').attr('title', 'Ready to sign up');
            $('#otpStatus').text('✅ Code entered. Click Sign Up to finish.');
            $('#otpStatus').css('color', 'green');
        } else {
            $('#submitBtn').prop('disabled', true).css('background-color', '#ccc');
            $('#otpStatus').text('Code must be 6 digits.');
            $('#otpStatus').css('color', '#666');
        }
    });

    // 5. Google Sign Up Handler (Context: signup)
    function handleCredentialResponse(response) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'google_auth.php');
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                var resp = xhr.responseText.trim();
                if (resp === 'success') {
                    window.location.href = "LOGIN.php?signup=success";
                } else if (resp === 'error_exists') {
                    alert('⚠️ Account already exists. Please Log In.');
                    window.location.href = "LOGIN.php";
                } else {
                    alert('Google Sign Up Failed: ' + resp);
                }
            }
        };
        xhr.send('credential=' + response.credential + '&context=signup');
    }
  </script>
</body>
</html>