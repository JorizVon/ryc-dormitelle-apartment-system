<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_ID'])) {
    header("Location: LOGIN.php");
    exit();
}

require_once 'db_connect.php';

// Initialize error and success messages
$error = "";
$success = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentUsername = trim($_POST['current_username']);
    $newUsername = trim($_POST['new_username']);

    if (empty($currentUsername) || empty($newUsername)) {
        $error = "Please fill in both fields.";
    } else {
        // Get the current username from database
        $stmt = $conn->prepare("SELECT username FROM admin_account WHERE admin_ID = ?");
        $stmt->bind_param("i", $_SESSION['admin_ID']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row && $row['username'] === $currentUsername) {
            // Update to new username
            $updateStmt = $conn->prepare("UPDATE admin_account SET username = ? WHERE admin_ID = ?");
            $updateStmt->bind_param("si", $newUsername, $_SESSION['admin_ID']);
            if ($updateStmt->execute()) {
                $success = "Username successfully updated!";
                $_SESSION['username'] = $newUsername; // Update session too
            } else {
                $error = "Error updating username.";
            }
            $updateStmt->close();
        } else {
            $error = "Current username is incorrect.";
        }
        $stmt->close();
    }
}
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
        .changeUsernameHead {
            display: flex;
            justify-content: center;
            width: 100%;
            margin: 0px;
            height: 20px;
            align-items: center;
        }
        .changeUsernameHead h4 {
            color: #01214B;
            font-size: 36px;
            height: 20px;
            align-items: center;
            position: relative;
            bottom: 25px;
        }
        /* === PASSWORD CHANGE STYLES === */

        .usernameChangecontainer {
            height: 100%;
            padding: 60px 20px;
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
        .UsernameGuidelines {
            font-size: 12px;
            color: #333;
            max-width: 350px;
            margin: 0 auto 20px;
        }

        .UsernameForm {
            max-width: 400px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .UsernameForm input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 20px;
            text-align: center;
            font-size: 14px;
        }

        .UsernameForm input::placeholder {
            color: #bbb;
        }
        .UsernameForm button {
            width: 100%;
            background-color: #004AAD;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 100px;
        }

        .UsernameForm button:hover {
            background-color: #003080;
        }

        /* Adjust Back Button */
        .footbtnContainer {
            width: 90%;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin: 78px auto 20px auto;
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
                transition: 0.3s ease;
            }

            .sideBar.active {
                left: 0;
            }

            .hamburger {
                display: block;
                position: absolute;
                top: 25px;
                left: 20px;
                z-index: 1100;
                font-size: 30px;
                cursor: pointer;
                color: #004AAD;
                visibility: visible;
                width: 10px;
            }

            .mainBody {
                width: 100%;
                margin-left: 0 !important;
            }

            .header {
                justify-content: right;
            }
            .footbtnContainer {
                flex-direction: column;
                align-items: center;
                gap: 15px;
                top: 30px;
                margin: 0 auto;
            }
        }

        @media (max-width: 480px) {
            .headerContent a, .adminLogoutspace {
                font-size: 14px;
            }
            .hamburger {
                font-size: 28px;
            }
            .sideBar{
                width: 53vw;
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
            }
            .card img {
                height: 25px;
            }

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
                    <a href="ADMINPROFILE.php" class="adminTitle">ADMIN</a>
                    <p class="adminLogoutspace">&nbsp;|&nbsp;</p>
                    <a href="LOGIN.php" class="logOutbtn">Log Out</a>
                </div>
            </div>
            <div class="mainContent">
                <div class="tenantInfoContainer usernameChangecontainer">
                    <div class="changeUsernameHead">
                        <h4>Change Username</h4>
                    </div>
                    <p class="adminIdentity">Adrian Abriol • <span class="adminRole">Admin</span></p>
                    <p class="UsernameGuidelines">
                        Your Username must be at least 6 characters and should include a combination of numbers, letters and special characters (!@$%^).
                    </p>
            
                   <!-- Show error or success message -->
                    <?php if (!empty($error)) { echo "<div class='errorMessage'>$error</div>"; } ?>
                    <?php if (!empty($success)) { echo "<div class='successMessage'>$success</div>"; } ?>

                    <form method="POST" class="UsernameForm">
                        <input type="text" name="current_username" placeholder="Current Username" required>
                        <input type="text" name="new_username" placeholder="New Username" required>
                        <button type="submit">Change Username</button>
                    </form>
                </div>
            
                <div class="footbtnContainer">
                    <a href="ADMINPROFILE.php" class="backbtn">&#10558; Back</a>
                </div>
            </div>                          
        </div>
    </div>
    <script>
        // Auto-fade error/success messages after 2 seconds
        setTimeout(() => {
            const errorMessage = document.querySelector('.errorMessage');
            const successMessage = document.querySelector('.successMessage');
            if (errorMessage) errorMessage.style.opacity = '0';
            if (successMessage) successMessage.style.opacity = '0';
        }, 2000);
        function toggleSidebar() {
            const sidebar = document.querySelector('.sideBar');
            sidebar.classList.toggle('active');
        }
    </script>
</body>
</html>
