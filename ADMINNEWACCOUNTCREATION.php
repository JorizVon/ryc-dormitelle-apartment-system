<?php
session_start();
// Redirect to login if not logged in
if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php");
    exit();
}

require_once 'db_connect.php';
$message = "";
$message_type = "error";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $old_admin_email = $_SESSION['email_account'];
    
    // --- 1. NEW: Verify OTP Logic ---
    if (!isset($_POST['otp_code']) || $_POST['otp_code'] != $_SESSION['otp']) {
        $message = "Invalid or missing Verification Code. Please verify the email.";
    } elseif ($_POST['email'] != $_SESSION['otp_email']) {
        $message = "The verified email does not match the input email.";
    } else {
        // OTP is valid, proceed with existing logic...
        
        $full_name = trim($_POST['full_name']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $contact_number = trim($_POST['contact_number']);
        $civil_status = trim($_POST['civil_status']);
        $nationality = trim($_POST['admin_nationality']);
        
        // Basic validation
        if (empty($full_name) || empty($username) || empty($email) || empty($password) || empty($contact_number) || empty($civil_status) || empty($nationality)) {
            $message = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Invalid email address format.";
        } else {
            // Hash the new password for security
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // --- Use a Transaction for safety ---
            $conn->begin_transaction();
            
            try {
                // 1. Update the admin in the 'accounts' table
                $stmt1 = $conn->prepare("UPDATE `accounts` SET `username` = ?, `email_account` = ?, `password` = ? WHERE `email_account` = ? AND `user_type` = 'admin'");
                $stmt1->bind_param("ssss", $username, $email, $hashed_password, $old_admin_email);
                $stmt1->execute();
                $stmt1->close();
                
                // 2. Update the admin in the 'admin_profile' table
                $stmt2 = $conn->prepare("UPDATE `admin_profile` SET `admin_email` = ?, `admin_username` = ?, `admin_pass` = ?, `admin_name` = ?, `admin_contact` = ?, `civil_status` = ?, `admin_nationality` = ? WHERE `admin_email` = ?");
                $stmt2->bind_param("ssssssss", $email, $username, $hashed_password, $full_name, $contact_number, $civil_status, $nationality, $old_admin_email);
                $stmt2->execute();
                $stmt2->close();
                
                // If all queries were successful, commit the transaction
                $conn->commit();
                
                // Clear OTP Session
                unset($_SESSION['otp'], $_SESSION['otp_email']);

                // Update session with new email if it changed
                $_SESSION['email_account'] = $email;
                
                // Destroy the old session and redirect to login with a success message
                session_destroy();
                header("Location: LOGIN.php?status=adminupdated");
                exit();
            } catch (mysqli_sql_exception $exception) {
                // If any query failed, roll back the transaction
                $conn->rollback();
                $message = "Error: Could not update admin account. Operation failed.";
                error_log("Admin update failed: " . $exception->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Account Creation - RYC Dormitelle</title>
    <link rel="icon" type="image/png" href="otherIcons/pageicon.png">
    
    <link rel="stylesheet" href="layout.css">
    <!-- Added jQuery for the OTP button functionality -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        .mainContent {
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .form-container {
            width: 100%;
            max-width: 600px;
            padding: 40px;
            border: 3px solid #A6DDFF;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            background-color: #fff;
            text-align: center;
        }
        
        .form-container h2 {
            color: #01214B;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .form-container p {
            color: #666;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box; 
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-color: #fff; 
        }
        
        .form-group select:invalid {
            color: #6c757d;
        }

        .btn-confirm {
            width: 100%;
            padding: 15px;
            background-color: #004AAD;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-confirm:hover {
            background-color: #003080;
        }

        /* Disabled state for Confirm button */
        .btn-confirm:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        
        .footbtnContainer {
            margin-top: 20px;
            width: 100%;
            max-width: 600px;
        }
        
        .backbtn {
            height: 36px;
            width: 110px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #004AAD;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* --- NEW STYLES FOR OTP --- */
        .input-group {
            display: flex;
            gap: 10px;
        }
        .verify-btn {
            padding: 12px 15px;
            background-color: #28a745; /* Green */
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            white-space: nowrap;
            height: 45px; /* Adjust to match input height */
        }
        .verify-btn:hover { background-color: #218838; }
        .verify-btn:disabled { background-color: #ccc; cursor: not-allowed; }
        
        #otp_section {
            display: none;
            background: #f1f8ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #cce5ff;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.html'; ?>
    
    <div class="mainBody">
        <?php include 'header.php'; ?>
        
        <div class="mainContent">
            <div class="form-container">
                <h2>Admin account creation</h2>
                <p>Register new admin account</p>
                
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form action="ADMINNEWACCOUNTCREATION.php" method="POST">
                    <div class="form-group">
                        <input type="text" name="full_name" placeholder="Full Name" required>
                    </div>
                    <div class="form-group">
                        <input type="text" name="username" placeholder="Username" required>
                    </div>

                    <!-- UPDATED EMAIL SECTION WITH OTP -->
                    <div class="form-group input-group">
                        <input type="email" name="email" id="email" placeholder="Email Address" required>
                        <button type="button" id="sendOtpBtn" class="verify-btn">Verify</button>
                    </div>

                    <!-- HIDDEN OTP INPUT -->
                    <div id="otp_section">
                        <p style="margin: 0 0 10px 0; font-size: 14px; color: #004AAD;">Enter the 6-digit code sent to your email:</p>
                        <input type="text" name="otp_code" id="otp_code" class="form-input" placeholder="Enter Verification Code">
                        <small id="otpStatus" style="color: #666; font-size: 12px; display:block; margin-top:5px;"></small>
                    </div>

                    <div class="form-group">
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <div class="form-group">
                        <input type="text" name="contact_number" placeholder="Contact Number" required>
                    </div>

                    <div class="form-group">
                        <select name="admin_nationality" required>
                            <option value="" disabled selected>-- Select Nationality --</option>
                            <option value="Filipino">Filipino</option>
                            <option value="American">American</option>
                            <option value="Australian">Australian</option>
                            <option value="British">British</option>
                            <option value="Canadian">Canadian</option>
                            <option value="Chinese">Chinese</option>
                            <option value="Indian">Indian</option>
                            <option value="Indonesian">Indonesian</option>
                            <option value="Japanese">Japanese</option>
                            <option value="Malaysian">Malaysian</option>
                            <option value="Singaporean">Singaporean</option>
                            <option value="South Korean">South Korean</option>
                            <option value="Thai">Thai</option>
                            <option value="Vietnamese">Vietnamese</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <select name="civil_status" required>
                            <option value="" disabled selected>-- Select Civil Status --</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Separated">Separated</option>
                            <option value="Divorced">Divorced</option>
                            <option value="Widowed">Widowed</option>
                        </select>
                    </div>
                    
                    <!-- Confirm button disabled until OTP is entered -->
                    <button type="submit" class="btn-confirm" id="submitBtn" disabled>Confirm</button>
                </form>
            </div>
            
            <div class="footbtnContainer">
                <a href="ADMINPROFILE.php" class="backbtn">‹ Back</a>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT FOR OTP LOGIC -->
    <script>
    $(document).ready(function() {
        // 1. Send OTP Button Click
        $('#sendOtpBtn').click(function() {
            var email = $('#email').val();
            
            // Basic validation
            if(email === '' || !email.includes('@')) { 
                alert('Please enter a valid email address first.'); 
                return; 
            }

            var btn = $(this);
            btn.text('Sending...').prop('disabled', true);

            // Post to your existing SEND_OTP.php file
            $.post('SEND_OTP.php', { email: email }, function(response) {
                response = response.trim();
                
                if(response === 'sent') {
                    alert('✅ Code sent! Check your inbox.');
                    $('#otp_section').slideDown();
                    $('#email').prop('readonly', true); // Lock email so they can't change it after sending OTP
                    btn.text('Sent').css('background-color', '#ccc');
                } else if (response === 'exists') {
                    // Note: Depending on logic, you might want to allow this if updating own profile, 
                    // but usually SEND_OTP blocks existing. 
                    alert('⚠️ This email is already registered.');
                    btn.text('Verify').prop('disabled', false);
                } else {
                    alert('❌ Error: ' + response);
                    btn.text('Verify').prop('disabled', false);
                }
            }).fail(function() {
                alert('Network error. Please try again.');
                btn.text('Verify').prop('disabled', false);
            });
        });

        // 2. Enable Confirm Button when 6 digits are typed
        $('#otp_code').on('keyup', function() {
            var code = $(this).val();
            if(code.length === 6) {
                $('#submitBtn').prop('disabled', false).css('background-color', '#004AAD');
                $('#otpStatus').text('✅ Ready to confirm.');
                $('#otpStatus').css('color', 'green');
            } else {
                $('#submitBtn').prop('disabled', true).css('background-color', '#ccc');
                $('#otpStatus').text('Code must be 6 digits.');
                $('#otpStatus').css('color', '#666');
            }
        });
    });
    </script>
</body>
</html>