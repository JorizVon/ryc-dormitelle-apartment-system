<?php
session_start();

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php");
    exit();
}

require_once 'db_connect.php';

// Initialize error and success messages
$error = "";
$success = "";
$current_db_username = "";
$admin_email_display = $_SESSION['email_account'];
$admin_display_name = "Admin"; // For header

// Fetch current username for display and for verification later
$stmt_get_user = $conn->prepare("SELECT username FROM accounts WHERE email_account = ?");
if ($stmt_get_user) {
    $stmt_get_user->bind_param("s", $_SESSION['email_account']);
    $stmt_get_user->execute();
    $result_get_user = $stmt_get_user->get_result();
    if ($result_get_user->num_rows > 0) {
        $user_row = $result_get_user->fetch_assoc();
        $current_db_username = $user_row['username'];
        $admin_display_name = $current_db_username ? htmlspecialchars($current_db_username) : "Admin";
    } else {
        $error = "Could not retrieve current user details.";
    }
    $stmt_get_user->close();
} else {
    $error = "Error preparing to fetch user details: " . $conn->error;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_current_username = trim($_POST['current_username']);
    $new_username = trim($_POST['new_username']);

    if (empty($submitted_current_username) || empty($new_username)) {
        $error = "Please fill in both current and new username fields.";
    } elseif (strlen($new_username) < 6) {
        $error = "New username must be at least 6 characters long.";
    } else {
        // Verify the submitted current username
        if ($current_db_username === $submitted_current_username) {
            // Check if the new username is different
            if ($new_username === $current_db_username) {
                $error = "New username cannot be the same as the current username.";
            } else {
                // Check if the new username already exists
                $stmt_check_new_username = $conn->prepare("SELECT email_account FROM accounts WHERE username = ? AND email_account != ?");
                if ($stmt_check_new_username) {
                    $stmt_check_new_username->bind_param("ss", $new_username, $_SESSION['email_account']);
                    $stmt_check_new_username->execute();
                    $result_check_new_username = $stmt_check_new_username->get_result();

                    if ($result_check_new_username->num_rows > 0) {
                        $error = "The new username '" . htmlspecialchars($new_username) . "' is already taken. Please choose a different one.";
                    } else {
                        // Update the username
                        $updateStmt = $conn->prepare("UPDATE accounts SET username = ? WHERE email_account = ?");
                        if ($updateStmt) {
                            $updateStmt->bind_param("ss", $new_username, $_SESSION['email_account']);
                            if ($updateStmt->execute()) {
                                if ($updateStmt->affected_rows > 0) {
                                    $success = "Username successfully updated!";
                                    $_SESSION['username'] = $new_username;
                                    $current_db_username = $new_username;
                                    $admin_display_name = htmlspecialchars($new_username);
                                } else {
                                    $error = "No changes made. Username might be the same or an issue occurred.";
                                }
                            } else {
                                $error = "Error updating username: " . $updateStmt->error;
                            }
                            $updateStmt->close();
                        } else {
                            $error = "Error preparing username update: " . $conn->error;
                        }
                    }
                    $stmt_check_new_username->close();
                } else {
                    $error = "Error preparing to check new username: " . $conn->error;
                }
            }
        } else {
            $error = "The 'Current Username' you entered is incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Username</title>
    <link rel="icon" type="image/png" href="otherIcons/pageicon.png">
    
    <!-- Include the layout CSS -->
    <link rel="stylesheet" href="layout.css">
    
    <!-- Change Username specific styles -->
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

        .changeUsernameHead {
            display: flex;
            justify-content: center;
            width: 100%;
            margin-bottom: 15px;
            align-items: center;
        }

        .changeUsernameHead h4 {
            color: #01214B;
            font-size: 32px;
            margin: 0;
        }

        .usernameChangecontainer {
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

        .UsernameGuidelines {
            font-size: 12px;
            color: #555;
            max-width: 380px;
            margin: 0 auto 25px;
            line-height: 1.5;
        }

        .UsernameForm {
            max-width: 350px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .UsernameForm input {
            width: 100%;
            padding: 10px 15px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 20px;
            text-align: center;
            font-size: 14px;
            box-sizing: border-box;
        }

        .UsernameForm input::placeholder {
            color: #bbb;
        }

        .UsernameForm button {
            width: 100%;
            background-color: #004AAD;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            font-size: 15px;
            cursor: pointer;
            margin-top: 15px;
        }

        .UsernameForm button:hover {
            background-color: #003080;
        }

        .errorMessage, .successMessage {
            padding: 10px;
            margin: 10px auto;
            border-radius: 4px;
            font-size: 14px;
            max-width: 350px;
            opacity: 1;
            transition: opacity 0.5s ease-out;
        }

        .errorMessage {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .successMessage {
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
            
            .changeUsernameHead h4 {
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
            
            .changeUsernameHead h4 {
                font-size: 24px;
            }
            
            .adminIdentity {
                font-size: 14px;
            }
            
            .UsernameForm {
                max-width: 90%;
            }
            
            .UsernameForm input {
                padding: 10px;
            }
            
            .UsernameForm button {
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
            <div class="tenantInfoContainer usernameChangecontainer">
                <div class="changeUsernameHead">
                    <h4>Change Username</h4>
                </div>
                
                <p class="adminIdentity">
                    <?php echo htmlspecialchars($admin_email_display); ?> • 
                    <span class="adminRole">Current Username: <?php echo htmlspecialchars($current_db_username ? $current_db_username : "Not Set"); ?></span>
                </p>
                
                <p class="UsernameGuidelines">
                    Your Username must be at least 6 characters. Combination of numbers, letters, and special characters (!@$%^) is recommended.
                </p>
        
                <!-- Show error or success message -->
                <?php if (!empty($error)): ?>
                    <div class='errorMessage'><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div class='successMessage'><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="UsernameForm">
                    <input type="text" name="current_username" placeholder="Current Username" required>
                    <input type="text" name="new_username" placeholder="New Username" required>
                    <button type="submit">Change Username</button>
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
            const errorMessage = document.querySelector('.errorMessage');
            const successMessage = document.querySelector('.successMessage');
            
            if (errorMessage) {
                errorMessage.style.opacity = '0';
                setTimeout(() => { errorMessage.style.display = 'none'; }, 500);
            }
            if (successMessage) {
                successMessage.style.opacity = '0';
                setTimeout(() => { successMessage.style.display = 'none'; }, 500);
            }
        }, 3000);
    </script>
</body>
</html>
<?php
if(isset($conn)) { $conn->close(); }
?>