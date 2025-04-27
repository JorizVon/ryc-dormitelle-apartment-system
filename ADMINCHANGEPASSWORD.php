<?php
session_start();

// Connect to database
$servername = "localhost";
$dbUsername = "root";
$dbPassword = "";
$dbName = "ryc_dormitelle";

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize messages
$success = "";
$error = "";

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
        $error = "Password must be at least 6 characters long.";
    } else {
        // Get the admin_ID from session
        if (!isset($_SESSION['admin_ID'])) {
            $error = "Session expired. Please log in again.";
        } else {
            $adminID = $_SESSION['admin_ID'];

            // Fetch current password from database
            $stmt = $conn->prepare("SELECT password FROM admin_account WHERE admin_ID = ?");
            $stmt->bind_param("i", $adminID);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                $storedHashedPassword = $row['password'];

                // Verify current password
                if (password_verify($currentPassword, $storedHashedPassword)) {
                    // Hash new password
                    $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                    // Update password in database
                    $updateStmt = $conn->prepare("UPDATE admin_account SET password = ? WHERE admin_ID = ?");
                    $updateStmt->bind_param("si", $newHashedPassword, $adminID);

                    if ($updateStmt->execute()) {
                        $success = "Password changed successfully.";
                    } else {
                        $error = "Error updating password.";
                    }
                    $updateStmt->close();
                } else {
                    $error = "Current password is incorrect.";
                }
            } else {
                $error = "Admin not found.";
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
            font-size: 24px;
            padding-left: 20px;
            font-weight: 500;
            display: flex;
            text-decoration: none;
            align-items: center;
            color: #01214B;
            height: 100%;
            width: 100%;
            background-color: #004AAD;
            color: white;
        }
        .card a:hover {
            background-color: 004AAD;
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
        .headerContent a:hover {
            color: #004AAD;
        }
        .mainContent {
            height: 100%;
            width: 100%;
            margin: 0px auto;
            background-color: #FFFF;
        }
        /* === ADMIN PROFILE VIEW STYLES === */
        .tenantInfoContainer {
            width: 86%;
            margin: 0 auto;
            border: 3px solid #A6DDFF;
            border-radius: 8px;
            height: 470px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            text-align: center;
            padding: 10px 0;
            position: relative;
            top: 50px;
        }
        .tenantHistoryHead {
            display: flex;
            justify-content: center;
            width: 100%;
            margin: 0px;
            height: 20px;
            align-items: center;
        }
        .tenantHistoryHead h4 {
            color: #01214B;
            font-size: 36px;
            height: 20px;
            align-items: center;
            position: relative;
            bottom: 25px;
        }
        /* === PASSWORD CHANGE STYLES === */

        .passwordChangeContainer {
            height: 100%;
            padding: 63px 20px;
            text-align: center;
            max-width: 90%;
            margin: 0 auto;
        }

        .adminIdentity {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 10px;
            color: #000;
        }

        .adminRole {
            font-weight: bold;
        }

        .passwordGuideline {
            font-size: 12px;
            color: #333;
            max-width: 350px;
            margin: 0 auto 20px;
        }

        .passwordForm {
            max-width: 400px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .passwordForm input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 20px;
            text-align: center;
            font-size: 14px;
        }

        .passwordForm input::placeholder {
            color: #bbb;
        }

        .forgotPassword {
            font-size: 12px;
            font-weight: bold;
            color: #000;
            align-self: flex-start;
            margin: 5px 0 20px;
            text-decoration: none;
        }
        .forgotPassword:hover {
            color: #003080;
        }
        .passwordForm button {
            width: 100%;
            background-color: #004AAD;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        .passwordForm button:hover {
            background-color: #003080;
        }

        /* Adjust Back Button */
        .footbtnContainer {
            width: 90%;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin: 70px auto 20px auto;
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

    </style>
</head>
<body>
    <div class="sideBar">
        <div class="systemTitle">
            <h1>RYC Dormitelle</h1>
            <p>APARTMENT MANAGEMENT SYSTEM</p>
        </div>
        <div class="sidebarContent">
            <div class="card">
                <a href="DASHBOARD.php" class="changeicon">
                    <img src="sidebarIcons/DashboardIconWht.png" alt="Dashboard Icon" class="DsidebarIcon" style="margin-right: 10px;">
                    Dashboard
                </a>
            </div>
            <div class="card">
                <a href="UNITSINFORMATION.php">
                    <img src="sidebarIcons/UnitsInfoIconWht.png" alt="Units Information Icon" class="UIsidebarIcon" style="margin-right: 10px;">
                    Units Information</a>
            </div>
            <div class="card">
                <a href="TENANTSLIST.php">
                    <img src="sidebarIcons/TenantsInfoIconWht.png" alt="Tenants Information Icon" class="THsidebarIcon" style="margin-right: 10px;">
                    Tenants Lists</a>
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
            
        </div>
    </div>
        <div class="mainBody">
            <div class="header">
                <div class="headerContent">
                    <a href="ADMINPROFILE.php" class="adminTitle">ADMIN</a>
                    <p class="adminLogoutspace">&nbsp;|&nbsp;</p>
                    <a href="ADMINLOGIN.php" class="logOutbtn">Log Out</a>
                </div>
            </div>
            <div class="mainContent">
                <div class="tenantInfoContainer passwordChangeContainer">
                    <div class="tenantHistoryHead">
                        <h4>Change password</h4>
                    </div>
                    <!-- Show success or error messages -->
                    <?php if (!empty($success)) { echo "<div style='color: green;'>$success</div>"; } ?>
                    <?php if (!empty($error)) { echo "<div style='color: red;'>$error</div>"; } ?>

                    <p class="adminIdentity">Adrian Abriol • <span class="adminRole">Admin</span></p>
                    <p class="passwordGuideline">
                        Your password must be at least 6 characters and should include a combination of numbers, letters and special characters (!@$%^).
                    </p>

                    <form class="passwordForm" method="POST" action="">
                        <input type="password" name="current_password" placeholder="Current password" required>
                        <input type="password" name="new_password" placeholder="New password" required>
                        <input type="password" name="confirm_password" placeholder="Re-type new password" required>
                        <a href="#" class="forgotPassword">Forgot your password?</a>
                        <button type="submit">Change password</button>
                    </form>
                </div>
            
                <div class="footbtnContainer">
                    <a href="ADMINPROFILE.php" class="backbtn">&#10558; Back</a>
                </div>
            </div>                          
        </div>
    </div>
</body>
</html>
