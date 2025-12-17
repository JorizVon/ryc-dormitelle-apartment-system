<?php
session_start();

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php");
    exit();
}

require_once 'db_connect.php';

// Initialize messages
$success = "";
$error = "";
$admin_email_display = $_SESSION['email_account'];
$admin_username_display = "Admin";
$admin_display_name = "Admin"; // For header

// Fetch current username for display
$stmt_get_user = $conn->prepare("SELECT username FROM accounts WHERE email_account = ?");
if ($stmt_get_user) {
    $stmt_get_user->bind_param("s", $_SESSION['email_account']);
    $stmt_get_user->execute();
    $result_get_user = $stmt_get_user->get_result();
    if ($result_get_user->num_rows > 0) {
        $user_row = $result_get_user->fetch_assoc();
        $admin_username_display = $user_row['username'] ? htmlspecialchars($user_row['username']) : "Admin";
        $admin_display_name = $admin_username_display; // For header
    }
    $stmt_get_user->close();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = trim($_POST['current_password']);
    $newPassword = trim($_POST['new_password']);
    $confirmPassword = trim($_POST['confirm_password']);

    // Validate inputs
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = "Please fill in all fields.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "New passwords do not match.";
    } elseif (strlen($newPassword) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else {
        $emailAccount = $_SESSION['email_account'];

        // Fetch current password from the 'accounts' table
        $stmt = $conn->prepare("SELECT password FROM accounts WHERE email_account = ?");
        if (!$stmt) {
            $error = "Error preparing statement: " . $conn->error;
        } else {
            $stmt->bind_param("s", $emailAccount);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                $storedHashedPassword = $row['password'];

                // Verify current password
                if (password_verify($currentPassword, $storedHashedPassword)) {
                    // Hash new password
                    $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                    // Update password
                    $updateStmt = $conn->prepare("UPDATE accounts SET password = ? WHERE email_account = ?");
                    if (!$updateStmt) {
                        $error = "Error preparing update statement: " . $conn->error;
                    } else {
                        $updateStmt->bind_param("ss", $newHashedPassword, $emailAccount);

                        if ($updateStmt->execute()) {
                            if ($updateStmt->affected_rows > 0) {
                                $success = "Password changed successfully.";
                            } else {
                                $error = "No changes made. Password might be the same or an issue occurred.";
                            }
                        } else {
                            $error = "Error updating password: " . $updateStmt->error;
                        }
                        $updateStmt->close();
                    }
                } else {
                    $error = "The 'Current password' you entered is incorrect.";
                }
            } else {
                $error = "User account not found. Please contact support.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Change Password</title>
    <link rel="icon" type="image/png" href="otherIcons/pageicon.png">
    
    <!-- Include the layout CSS -->
    <link rel="stylesheet" href="layout.css">
    
    <!-- Change Password specific styles -->
    <style>
        .mainContent {
            padding: 20px;
            overflow-y: auto;
        }

        .tenantInfoContainer {
            width: 86%;
            max-width: 700px;
            margin: 0 auto;
            border: 3px solid #A6DDFF;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            text-align: center;
            padding: 20px;
            position: relative;
            top: 30px;
        }

        .tenantHistoryHead {
            display: flex;
            justify-content: center;
            width: 100%;
            margin-bottom: 15px;
            align-items: center;
        }

        .tenantHistoryHead h4 {
            color: #01214B;
            font-size: 32px;
            margin: 0;
        }

        .passwordChangeContainer {
            padding: 20px 0;
            text-align: center;
        }

        .adminIdentity {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 8px;
            color: #000;
        }

        .adminRole {
            font-weight: bold;
        }

        .passwordGuideline {
            font-size: 12px;
            color: #555;
            max-width: 380px;
            margin: 0 auto 25px;
            line-height: 1.5;
        }

        .passwordForm {
            max-width: 350px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .passwordForm input {
            width: 100%;
            padding: 10px 15px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 20px;
            text-align: center;
            font-size: 14px;
            box-sizing: border-box;
        }

        .passwordForm input::placeholder {
            color: #bbb;
        }

        .forgotPassword {
            font-size: 12px;
            font-weight: bold;
            color: #000;
            align-self: flex-start;
            margin: 5px 0 15px;
            text-decoration: none;
        }

        .forgotPassword:hover {
            color: #003080;
        }

        .passwordForm button {
            width: 100%;
            background-color: #004AAD;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            font-size: 15px;
            cursor: pointer;
            margin-top: 10px;
        }

        .passwordForm button:hover {
            background-color: #FFFFFF;
            color: #004AAD;
            padding: 8px;
            border: 2px solid #004AAD;
        }

        .message {
            padding: 10px;
            margin: 10px auto;
            border-radius: 4px;
            font-size: 14px;
            max-width: 350px;
            opacity: 1;
            transition: opacity 0.5s ease-out;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .footbtnContainer {
            width: 86%;
            max-width: 700px;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin: 25px auto;
            position: relative;
            top: 30px;
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
            font-size: 16px;
        }

        .backbtn:hover {
            background-color: #FFFFFF;
            color: #004AAD;
            border: 2px solid #004AAD;
        }

        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .footbtnContainer {
                margin: 25px auto;
                justify-content: center;
            }
            
            .tenantInfoContainer {
                top: 20px;
            }
            
            .footbtnContainer {
                top: 20px;
            }
            
            .tenantHistoryHead h4 {
                font-size: 28px;
            }
        }

        @media (max-width: 480px) {
            .tenantInfoContainer {
                padding: 15px;
                top: 15px;
            }
            
            .footbtnContainer {
                margin: 20px auto;
                top: 15px;
            }
            
            .tenantHistoryHead h4 {
                font-size: 24px;
            }
            
            .adminIdentity {
                font-size: 14px;
            }
            
            .passwordForm {
                max-width: 90%;
            }
            
            .passwordForm input {
                padding: 10px;
            }
            
            .passwordForm button {
                font-size: 14px;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'sidebar.html'; ?>
    
    <div class="mainBody">
        <!-- Include Header -->
        <?php include 'header.php'; ?>
        
        <div class="mainContent">
            <div class="tenantInfoContainer passwordChangeContainer">
                <div class="tenantHistoryHead">
                    <h4>Change password</h4>
                </div>
                
                <p class="adminIdentity">
                    <?php echo htmlspecialchars($admin_email_display); ?> • 
                    <span class="adminRole"><?php echo htmlspecialchars($admin_username_display); ?></span>
                </p>
                
                <p class="passwordGuideline">
                    Your password must be at least 6 characters and should include a combination of numbers, letters and special characters (!@$%^).
                </p>

                <!-- Show success or error messages -->
                <?php if (!empty($success)): ?>
                    <div class="message success"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="message error"><?php echo $error; ?></div>
                <?php endif; ?>

                <form class="passwordForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <input type="password" name="current_password" placeholder="Current password" required>
                    <input type="password" name="new_password" placeholder="New password" required>
                    <input type="password" name="confirm_password" placeholder="Re-type new password" required>
                    <a href="#" class="forgotPassword">Forgot your password?</a>
                    <button type="submit">Change password</button>
                </form>
            </div>
        
            <div class="footbtnContainer">
                <a href="ADMINPROFILE.php" class="backbtn">⤾ Back</a>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-fade error/success messages after 3 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                if (message) {
                    message.style.opacity = '0';
                    setTimeout(() => { message.style.display = 'none'; }, 500);
                }
            });
        }, 3000);
    </script>
</body>
</html>
<?php
if(isset($conn)) { $conn->close(); }
?>