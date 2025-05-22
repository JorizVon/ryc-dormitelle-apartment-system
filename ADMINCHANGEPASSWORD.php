<?php
session_start();

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    // Assuming LOGIN.php is in the same directory or adjust path as needed
    header("Location: LOGIN.php");
    exit();
}

// Assuming db_connect.php is in the same directory or adjust path
require_once 'db_connect.php';

// Initialize messages
$success = "";
$error = "";
$admin_email_display = $_SESSION['email_account']; // Email for display
$admin_username_display = "Admin"; // Default, will be fetched

// Fetch current username for display (optional, but good for context)
$stmt_get_user = $conn->prepare("SELECT username FROM accounts WHERE email_account = ?");
if ($stmt_get_user) {
    $stmt_get_user->bind_param("s", $_SESSION['email_account']);
    $stmt_get_user->execute();
    $result_get_user = $stmt_get_user->get_result();
    if ($result_get_user->num_rows > 0) {
        $user_row = $result_get_user->fetch_assoc();
        $admin_username_display = $user_row['username'] ? htmlspecialchars($user_row['username']) : "Admin";
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
    } elseif (strlen($newPassword) < 6) { // Basic password strength check
        $error = "New password must be at least 6 characters long.";
    }
    // You can add more password complexity rules here (e.g., regex for numbers, special chars)
    else {
        // Use email_account from session to identify the user
        $emailAccount = $_SESSION['email_account'];

        // Fetch current password from the 'accounts' table based on email_account
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

                    // Update password in 'accounts' table
                    $updateStmt = $conn->prepare("UPDATE accounts SET password = ? WHERE email_account = ?");
                    if (!$updateStmt) {
                        $error = "Error preparing update statement: " . $conn->error;
                    } else {
                        $updateStmt->bind_param("ss", $newHashedPassword, $emailAccount);

                        if ($updateStmt->execute()) {
                            if ($updateStmt->affected_rows > 0) {
                                $success = "Password changed successfully.";
                            } else {
                                // This could happen if the new hashed password is the same as the old one,
                                // or if the email_account was not found (though previous check should prevent this).
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
                // This case should ideally not happen if the session 'email_account' is valid
                // and corresponds to an entry in the 'accounts' table.
                $error = "User account not found. Please contact support.";
            }
            $stmt->close();
        }
    }
}
// $conn->close(); // Close connection at the end of script execution
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Change Password</title>
    <style>
        body {
            display: flex;
            margin: 0;
            background-color: #FFFF;
        }
        .sideBar {
            width: 450px;
            height: 100%;
            background-color: #01214B;
        }
        .systemTitle {
            text-align: center;
            height: 10vh;
            padding-bottom: 5.3px;
        }
        .systemTitle h1 {
            font-size: 25px;
            font-family: Inria Serif;
            align-items: center;
            position: relative;
            top: 5px;
            color: #FFFF;
        }
        .systemTitle p {
            font-size: 14px;
            font-family: Inria Serif;
            position: relative;
            bottom: 10px;
            color: #FFFF;
        }
        .sidebarContent {
            padding-top: 20px;
            height: 84vh;
            background-color: #004AAD;
            display: block;
        }
        .card {
            width: 100%;
            height: 50px;
            display: flex;
            margin: 10px 0px 10px;
            align-items: center;
            justify-content: center;
        }
        .card a {
            margin: auto 0px auto 0px;
            font-size: 20px;
            padding-left: 20px;
            font-weight: 500;
            display: flex;
            text-decoration: none;
            align-items: center;
            /* color: #01214B; */ /* Covered by color: white */
            height: 100%;
            width: 100%;
            background-color: #004AAD;
            color: white;
        }
        .card a:hover {
            /* background-color: 004AAD; */ /* Invalid */
            color: #FFFF;
            background-color: #FFFF;
            color: #004AAD;
        }
        .card a:hover .DsidebarIcon {
            content: url('sidebarIcons/DashboardIcon.png');
        }
        .card a:hover .UIsidebarIcon {
            content: url('sidebarIcons/UnitsInfoIcon.png');
        }
        .card a:hover .THsidebarIcon {
            content: url('sidebarIcons/TenantsInfoIcon.png');
        }
        .card a:hover .PMsidebarIcon {
            content: url('sidebarIcons/PaymentManagementIcon.png');
        }
        .card a:hover .APLsidebarIcon {
            content: url('sidebarIcons/AccesspointIcon.png');
        }
        .card a:hover .CGsidebarIcon {
            content: url('sidebarIcons/CardregisterIcon.png');
        }
        .card a:hover .PIsidebarIcon {
            content: url('sidebarIcons/PendingInquiryIcon.png');
        }
        .mainBody {
            width: 100vw;
            height: 100%;
            background-color: white;
        }
        .header {
            height: 13vh;
            width: 100%;
            background-color: #79B1FC;
            display: flex;
            justify-content: end;
            align-items: center;
        }
        .headerContent {
            margin-right: 40px;
            display: flex;
            justify-content: center;
            align-items: center;

        }
        .adminTitle {
            font-size: 16px;
            color: #01214B;
            position: relative;
            text-decoration: none;
        }
        .headerContent .adminTitle:hover {
            color: #FFFF;
        }
        .adminLogoutspace {
            font-size: 16px;
            color: #01214B;
            position: relative;
            text-decoration: none;
        }
        .logOutbtn {
            font-size: 16px;
            color: #FFFF;
            position: relative;
            margin-left: 2px;
            text-decoration: none;
        }
        .headerContent a.logOutbtn:hover { /* Specific selector */
            color: #004AAD;
        }
        .mainContent {
            height: calc(100% - 13vh); /* Adjust for header */
            width: 100%;
            margin: 0px auto;
            background-color: #FFFF;
            padding-top: 20px;
        }
        .tenantInfoContainer { /* Main container for the form */
            width: 86%;
            max-width: 700px; /* Added max-width */
            margin: 0 auto;
            border: 3px solid #A6DDFF;
            border-radius: 8px;
            /* height: 450px; */ /* Auto height */
            /* overflow: hidden; */ /* Removed */
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            text-align: center;
            padding: 20px; /* Adjusted padding */
            position: relative;
            top: 30px; /* Adjusted */
        }
        .tenantHistoryHead { /* Title container */
            display: flex;
            justify-content: center;
            width: 100%;
            margin-bottom: 15px; /* Added margin */
            align-items: center;
        }
        .tenantHistoryHead h4 {
            color: #01214B;
            font-size: 32px; /* Adjusted */
            margin: 0; /* Remove default margin */
        }
        .passwordChangeContainer { /* Inner container for form elements */
            /* height: 100%; */ /* Not needed if tenantInfoContainer wraps */
            padding: 20px 0; /* Adjusted padding */
            text-align: center;
            /* max-width: 90%; */ /* Controlled by tenantInfoContainer */
            /* margin: 0 auto; */
        }

        .adminIdentity {
            font-size: 16px; /* Adjusted */
            font-weight: 500;
            margin-bottom: 8px; /* Adjusted */
            color: #000;
        }

        .adminRole { /* Used for "Admin" or username part */
            font-weight: bold;
        }

        .passwordGuideline {
            font-size: 12px;
            color: #555; /* Darker for readability */
            max-width: 380px; /* Adjusted */
            margin: 0 auto 25px; /* Adjusted */
            line-height: 1.5;
        }

        .passwordForm {
            max-width: 350px; /* Adjusted */
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .passwordForm input {
            width: 100%;
            padding: 10px 15px; /* Adjusted */
            margin: 8px 0; /* Adjusted */
            border: 1px solid #ccc;
            border-radius: 20px;
            text-align: center;
            font-size: 14px;
            box-sizing: border-box; /* Important */
        }

        .passwordForm input::placeholder {
            color: #bbb;
        }

        .forgotPassword {
            font-size: 12px;
            font-weight: bold;
            color: #000;
            align-self: flex-start; /* Align to left within form */
            margin: 5px 0 15px; /* Adjusted */
            text-decoration: none;
        }
        .forgotPassword:hover {
            color: #003080;
        }
        .passwordForm button {
            width: 100%;
            background-color: #004AAD;
            color: white;
            padding: 10px; /* Adjusted */
            border: none;
            border-radius: 5px;
            font-size: 15px; /* Adjusted */
            cursor: pointer;
            margin-top: 10px; /* Added margin */
        }

        .passwordForm button:hover {
            background-color: #FFFFFF;
            color: #004AAD;
            padding: 8px; /* Adjust padding for border */
            border: 2px solid #004AAD;
        }

        /* Styles for error and success messages */
        .message {
            padding: 10px;
            margin: 10px auto;
            border-radius: 4px;
            font-size: 14px;
            max-width: 350px; /* Match form width */
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
            max-width: 700px; /* Match tenantInfoContainer */
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin: 25px auto; /* Adjusted */
            position: relative; /* If top is used */
            top: 30px; /* Adjusted */
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
        .hamburger {
            display: none; /* Original: visibility: hidden; width: 0px; */
        }
        /* Mobile and Tablet Responsive */
        @media (max-width: 1024px) {
            body {
            justify-content: center;
            }
            .sideBar {
                position: fixed;
                left: -100%;
                top: 0;
                height: 100vh;
                z-index: 1000;
                transition: left 0.3s ease;
            }

            .sideBar.active {
                left: 0;
            }

            .hamburger {
                display: block;
                position: fixed; /* Changed */
                top: 25px;
                left: 20px;
                z-index: 1100;
                font-size: 30px;
                cursor: pointer;
                color: #004AAD;
                width: auto; /* Changed */
                background-color: white;
                padding: 5px 10px;
                border-radius: 3px;
            }

            .mainBody {
                width: 100%;
                margin-left: 0 !important;
            }

            .header {
                justify-content: flex-end; /* Changed */
            }
            .footbtnContainer {
                margin: 25px auto; /* simplified */
                justify-content: center;
            }
             .tenantInfoContainer { top: 20px; }
            .footbtnContainer { top: 20px; }
            .tenantHistoryHead h4 { font-size: 28px; }
        }

        @media (max-width: 480px) {
            .headerContent { margin-right: 20px; }
            .headerContent a, .adminLogoutspace {
                font-size: 14px;
            }
            .hamburger {
                font-size: 28px;
                top: 15px;
                left: 15px;
            }
            .sideBar{
                width: 220px; /* Adjusted */
            }
            .systemTitle {
                position: relative;
                top: 15px;
                padding: 11px;
            }
            .systemTitle h1 {
                font-size: 14px;
                position: relative;
                margin-bottom: 18px;

            }
            .systemTitle p {
                font-size: 10px;
            }
            .card a {
                font-size: 14px;
                padding-left: 15px;
            }
            .card img {
                height: 18px;
                width: 18px;
                margin-right: 8px;
            }
            .tenantInfoContainer { padding: 15px; top: 15px; }
            .footbtnContainer { margin: 20px auto; top: 15px; }
            .tenantHistoryHead h4 { font-size: 24px; }
            .adminIdentity { font-size: 14px; }
            .passwordForm { max-width: 90%; }
            .passwordForm input { padding: 10px; }
            .passwordForm button { font-size: 14px; padding: 10px; }

        }

    </style>
</head>
<body>
    <div class="hamburger" onclick="toggleSidebar()">☰</div>
    <div class="sideBar">
        <div class="systemTitle">
            <h1>RYC Dormitelle</h1>
            <p>APARTMENT MANAGEMENT SYSTEM</p>
        </div>
        <div class="sidebarContent">
            <div class="card">
                <a href="DASHBOARD.php" class="changeicon">
                    <img src="sidebarIcons/DashboardIconWht.png" alt="Dashboard Icon" class="DsidebarIcon" style="margin-right: 8px;">
                    Dashboard
                </a>
            </div>
            <div class="card">
                <a href="UNITSINFORMATION.php">
                    <img src="sidebarIcons/UnitsInfoIconWht.png" alt="Units Information Icon" class="UIsidebarIcon" style="margin-right: 5px;">
                    Units Information</a>
            </div>
            <div class="card">
                <a href="TENANTSLIST.php">
                    <img src="sidebarIcons/TenantsInfoIconWht.png" alt="Tenants Information Icon" class="THsidebarIcon" style="margin-right: 3px;">
                    Tenants List</a> <!-- Corrected s -->
            </div>
            <div class="card">
                <a href="PAYMENTMANAGEMENT.php">
                    <img src="sidebarIcons/PaymentManagementIconWht.png" alt="Payment Management Icon" class="PMsidebarIcon" style="margin-right: 10px;">
                    Payment Management</a>
            </div>
            <div class="card">
                <a href="ACCESSPOINTLOGS.php">
                    <img src="sidebarIcons/AccesspointIconWht.png" alt="Access Point Logs Icon" class="APLsidebarIcon" style="margin-right: 10px;">
                    Access Point Logs</a>
            </div>
            <div class="card">
                <a href="CARDREGISTRATION.php">
                    <img src="sidebarIcons/CardregisterIconWht.png" alt="Card Registration Icon" class="CGsidebarIcon" style="margin-right: 10px;">
                    Card Registration</a>
            </div>
            <div class="card">
                <a href="PENDINGINQUIRY.php">
                    <img src="sidebarIcons/PendingInquiryIconWht.png" alt="Pending Inquiry Icon" class="PIsidebarIcon" style="margin-right: 10px;">
                    Pending Inquiry</a>
            </div>
        </div>
    </div>
        <div class="mainBody">
            <div class="header">
                <div class="headerContent">
                     <!-- Display fetched username or default -->
                    <a href="ADMINPROFILE.php" class="adminTitle"><?php echo htmlspecialchars($admin_username_display); ?></a>
                    <p class="adminLogoutspace"> | </p>
                    <a href="LOGIN.php" class="logOutbtn">Log Out</a> <!-- Or LOGOUT.php -->
                </div>
            </div>
            <div class="mainContent">
                <div class="tenantInfoContainer passwordChangeContainer"> <!-- Merged classes -->
                    <div class="tenantHistoryHead">
                        <h4>Change password</h4>
                    </div>
                    
                    <!-- Displaying admin identity -->
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
    </div>
    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sideBar');
            sidebar.classList.toggle('active');
        }

        // Auto-fade error/success messages after 3 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.message'); // Select all messages
            messages.forEach(message => {
                if (message) {
                    message.style.opacity = '0';
                    setTimeout(() => { message.style.display = 'none'; }, 500); // Remove after fade
                }
            });
        }, 3000);
    </script>
</body>
</html>
<?php
if(isset($conn)) { $conn->close(); } // Close DB connection if it was opened
?>