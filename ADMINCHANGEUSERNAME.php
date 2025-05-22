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

// Initialize error and success messages
$error = "";
$success = "";
$current_db_username = ""; // To display the admin's current username
$admin_email_display = $_SESSION['email_account']; // Email for display

// Fetch current username for display and for verification later
$stmt_get_user = $conn->prepare("SELECT username FROM accounts WHERE email_account = ?");
if ($stmt_get_user) {
    $stmt_get_user->bind_param("s", $_SESSION['email_account']);
    $stmt_get_user->execute();
    $result_get_user = $stmt_get_user->get_result();
    if ($result_get_user->num_rows > 0) {
        $user_row = $result_get_user->fetch_assoc();
        $current_db_username = $user_row['username'];
    } else {
        // This case should ideally not happen if the session is valid
        $error = "Could not retrieve current user details.";
        // Optionally, log this issue or redirect
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
    } elseif (strlen($new_username) < 6) { // Example: Basic validation for new username length
        $error = "New username must be at least 6 characters long.";
    }
    // Add more validation for new_username (e.g., special characters) if needed based on your guidelines
    else {
        // Verify the submitted current username against the one fetched from DB for the logged-in user
        if ($current_db_username === $submitted_current_username) {
            // Check if the new username is different from the current one
            if ($new_username === $current_db_username) {
                $error = "New username cannot be the same as the current username.";
            } else {
                // Check if the new username already exists for another account (optional but good practice)
                $stmt_check_new_username = $conn->prepare("SELECT email_account FROM accounts WHERE username = ? AND email_account != ?");
                if ($stmt_check_new_username) {
                    $stmt_check_new_username->bind_param("ss", $new_username, $_SESSION['email_account']);
                    $stmt_check_new_username->execute();
                    $result_check_new_username = $stmt_check_new_username->get_result();

                    if ($result_check_new_username->num_rows > 0) {
                        $error = "The new username '" . htmlspecialchars($new_username) . "' is already taken. Please choose a different one.";
                    } else {
                        // Proceed to update the username
                        // Using email_account from session for WHERE clause
                        $updateStmt = $conn->prepare("UPDATE accounts SET username = ? WHERE email_account = ?");
                        if ($updateStmt) {
                            $updateStmt->bind_param("ss", $new_username, $_SESSION['email_account']);
                            if ($updateStmt->execute()) {
                                if ($updateStmt->affected_rows > 0) {
                                    $success = "Username successfully updated!";
                                    // Update session if you store username there and it's used for display elsewhere
                                    // For example, if your header displays $_SESSION['username']
                                    $_SESSION['username'] = $new_username; // Example session variable
                                    $current_db_username = $new_username; // Update for immediate display on this page
                                } else {
                                    // This might happen if the new username was the same as old, but we already checked that.
                                    // Or if email_account somehow didn't match.
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
// $conn->close(); // Close connection at the end of the script if no more DB operations
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Username</title>
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
            /* color: #01214B; */ /* Already white due to below */
            height: 100%;
            width: 100%;
            background-color: #004AAD;
            color: white;
        }
        .card a:hover {
            /* background-color: 004AAD; */ /* Invalid CSS */
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
        .headerContent a.logOutbtn:hover { /* More specific selector */
            color: #004AAD;
        }
        .mainContent {
            height: calc(100% - 13vh); /* Adjust based on header height */
            width: 100%;
            margin: 0px auto;
            background-color: #FFFF;
            padding-top: 20px;
        }
        .tenantInfoContainer { /* Main container for the form area */
            width: 86%;
            max-width: 700px; /* Added max-width */
            margin: 0 auto;
            border: 3px solid #A6DDFF;
            border-radius: 8px;
            /* height: 470px; */ /* Let height be auto */
            /* overflow: hidden; */ /* Removed */
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            text-align: center;
            padding: 20px; /* Adjusted padding */
            position: relative;
            top: 30px; /* Adjusted top */
        }
        .changeUsernameHead {
            display: flex;
            justify-content: center;
            width: 100%;
            margin-bottom: 15px; /* Added margin */
            /* height: 20px; */ /* Removed fixed height */
            align-items: center;
        }
        .changeUsernameHead h4 {
            color: #01214B;
            font-size: 32px; /* Adjusted font size */
            /* height: 20px; */
            /* align-items: center; */
            /* position: relative; */ /* Removed */
            /* bottom: 25px; */ /* Removed */
            margin: 0; /* Remove default h4 margin */
        }
        .usernameChangecontainer { /* This class seems to wrap the content inside tenantInfoContainer */
            /* height: 100%; */ /* Not needed if tenantInfoContainer wraps */
            padding: 20px 0; /* Adjusted padding */
            text-align: center;
            /* max-width: 90%; */ /* Already controlled by tenantInfoContainer */
            /* margin: 0 auto; */
        }

        .adminIdentity {
            font-size: 16px; /* Adjusted font size */
            font-weight: 500;
            margin-bottom: 8px; /* Adjusted margin */
            color: #000;
        }

        .adminRole {
            font-weight: bold;
        }
        .UsernameGuidelines {
            font-size: 12px;
            color: #555; /* Slightly darker for better readability */
            max-width: 380px; /* Adjusted width */
            margin: 0 auto 25px; /* Adjusted margin */
            line-height: 1.5;
        }

        .UsernameForm {
            max-width: 350px; /* Adjusted width */
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .UsernameForm input {
            width: 100%;
            padding: 10px 15px; /* Adjusted padding */
            margin: 8px 0; /* Adjusted margin */
            border: 1px solid #ccc;
            border-radius: 20px;
            text-align: center;
            font-size: 14px;
            box-sizing: border-box; /* Important for width and padding */
        }

        .UsernameForm input::placeholder {
            color: #bbb;
        }
        .UsernameForm button {
            width: 100%;
            background-color: #004AAD;
            color: white;
            padding: 10px; /* Adjusted padding */
            border: none;
            border-radius: 5px;
            font-size: 15px; /* Adjusted font size */
            cursor: pointer;
            margin-top: 15px; /* Reduced margin */
        }

        .UsernameForm button:hover {
            background-color: #003080;
        }

        /* Styles for error and success messages */
        .errorMessage, .successMessage {
            padding: 10px;
            margin: 10px auto;
            border-radius: 4px;
            font-size: 14px;
            max-width: 350px; /* Match form width */
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


        /* Adjust Back Button */
        .footbtnContainer {
            width: 86%;
            max-width: 700px; /* Match tenantInfoContainer */
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin: 25px auto; /* Adjusted margin */
            position: relative; /* Needed if top was used, but margin is better */
            top: 30px; /* Adjusted top */
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
            display: none;
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
                /* visibility: visible; */ /* Not needed if display:block */
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
                /* flex-direction: column; */ /* Not ideal for single button */
                /* align-items: center; */
                /* gap: 15px; */
                /* top: 30px; */ /* position:relative needed */
                margin: 25px auto; /* simplified */
                justify-content: center;
            }
            .tenantInfoContainer { top: 20px; }
            .footbtnContainer { top: 20px; }
            .changeUsernameHead h4 { font-size: 28px; }

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
            .changeUsernameHead h4 { font-size: 24px; }
            .adminIdentity { font-size: 14px; }
            .UsernameForm { max-width: 90%; }
            .UsernameForm input { padding: 10px; }
            .UsernameForm button { font-size: 14px; padding: 10px; }

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
                    <!-- Assuming $admin_display_name is set from session or default -->
                    <a href="ADMINPROFILE.php" class="adminTitle"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : (isset($current_db_username) && $current_db_username ? htmlspecialchars($current_db_username) : 'ADMIN'); ?></a>
                    <p class="adminLogoutspace"> | </p>
                    <a href="LOGIN.php" class="logOutbtn">Log Out</a> <!-- Or LOGOUT.php -->
                </div>
            </div>
            <div class="mainContent">
                <div class="tenantInfoContainer usernameChangecontainer"> <!-- Merged classes for simplicity -->
                    <div class="changeUsernameHead">
                        <h4>Change Username</h4>
                    </div>
                    <!-- Displaying current username and email -->
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
    </div>
    <script>
        // Auto-fade error/success messages after 3 seconds
        setTimeout(() => {
            const errorMessage = document.querySelector('.errorMessage');
            const successMessage = document.querySelector('.successMessage');
            
            if (errorMessage) {
                errorMessage.style.opacity = '0';
                setTimeout(() => { errorMessage.style.display = 'none'; }, 500); // Remove after fade
            }
            if (successMessage) {
                successMessage.style.opacity = '0';
                setTimeout(() => { successMessage.style.display = 'none'; }, 500); // Remove after fade
            }
        }, 3000); // Increased to 3 seconds

        function toggleSidebar() {
            const sidebar = document.querySelector('.sideBar');
            sidebar.classList.toggle('active');
        }
    </script>
</body>
</html>
<?php
if(isset($conn)) { $conn->close(); } // Close DB connection if it was opened
?>