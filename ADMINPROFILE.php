<?php
session_start();

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php"); // Assumes LOGIN.php is in the parent directory
    exit();
}

// Connect to the database, assuming db_connect.php is in the parent directory
require_once 'db_connect.php'; // Adjust path if necessary

$admin_email = $_SESSION['email_account'];
$admin_username = "N/A"; // Default if not found
$admin_display_name = "ADMIN"; // Default for header

// Fetch admin details from the database
$sql = "SELECT `username`, `email_account` FROM `accounts` WHERE `email_account` = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("s", $admin_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $account_data = $result->fetch_assoc();
        $admin_username = htmlspecialchars($account_data['username']);
        // For the header, we can use the username or a part of the email
        $admin_display_name = $admin_username; // Or htmlspecialchars(strtok($admin_email, '@'));
    } else {
        // Handle case where email_account in session doesn't exist in accounts table
        // This shouldn't happen if login is correct, but good to be aware
        error_log("Admin profile: No account found for email: " . $admin_email);
    }
    $stmt->close();
} else {
    // Handle SQL prepare error
    error_log("Admin profile: SQL prepare error: " . $conn->error);
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
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
            /* color: #01214B; */ /* Conflicting with below */
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
            width: 100vw; /* This might cause horizontal scroll if sidebar is also 450px fixed */
            /* Consider width: calc(100vw - 450px); margin-left: 450px; if sidebar is fixed */
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
        .headerContent a.logOutbtn:hover { /* Made selector more specific */
            color: #004AAD;
        }
        .mainContent {
            height: calc(100% - 13vh); /* Adjust based on header height */
            width: 100%;
            margin: 0px auto;
            background-color: #FFFF;
            padding-top: 20px; /* Added padding top */
        }
        .tenantHistoryHead {
            display: flex;
            justify-content: space-between; /* This might not be needed if only h4 is there */
            width: 100%;
            align-items: center;
            margin-bottom: 0px; /* Added margin bottom */
            height: auto;
        }
        .tenantHistoryHead h4 {
            color: #01214B;
            font-size: 32px;
            margin-left: 60px;
            /* height: 20px; */ /* height on h4 might not be ideal */
            /* align-items: center; */ /* Not applicable directly to h4 */
        }
        /* === ADMIN PROFILE VIEW STYLES === */
        .tenantInfoContainer {
            width: 90%;
            max-width: 600px; /* Added max-width for better appearance on large screens */
            margin: 0 auto;
            border: 3px solid #A6DDFF;
            border-radius: 8px;
            height: auto; /* Changed from fixed height to auto */
            /* height: 410px; */
            /* overflow: hidden; */ /* Removed to allow content to define height */
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            text-align: center;
            padding: 30px 20px; /* Adjusted padding */
        }

        .tenantImageContainer { /* This is for the static admin icon */
            width: 150px;
            height: 150px;
            /* border: 1px solid #ccc; */ /* Removed border for cleaner look with icon */
            /* border-radius: 4px; */
            object-fit: cover;
            display: block;
            margin: 0 auto 20px auto; /* Added margin bottom */
        }

        .admintxt { /* This seems redundant if you have .adminName below */
            font-weight: bold;
            margin-top: 10px;
            font-size: 24px;
            color: #000;
        }

        .adminDetails {
            margin-top: 10px; /* Reduced margin */
            line-height: 2;
            font-size: 18px;
            color: #01214B;
        }

        .adminDetails p {
            margin: 8px 0; /* Adjusted margin for paragraphs */
            font-size: 18px;
        }
        .adminDetails p strong { /* For labels like "Email:" */
            color: #333;
            margin-right: 8px;
        }

        .adminDetails .adminLink {
            color: #004AAD;
            cursor: pointer;
            text-decoration: none;
            display: inline-block; /* Keeps it on its own line better */
            margin-top: 5px; /* Spacing for links */
        }

        .adminDetails .adminLink:hover {
            text-decoration: underline;
        }

        /* Adjust Back Button */
        .footbtnContainer {
            width: 90%;
            max-width: 600px; /* Consistent with tenantInfoContainer */
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin: 0px auto; /* Adjusted margin */
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

        .backbtn:hover {
            background-color: #FFFFFF;
            color: #004AAD;
            border: 2px solid #004AAD; /* Make sure border is initially 0 or transparent if not visible */
        }
        .hamburger {
            display: none; /* Kept from original, assuming controlled by JS not shown here */
        }   
        /* Mobile and Tablet Responsive */
        @media (max-width: 1024px) {
            body {
                justify-content: center; /* Might conflict with fixed sidebar idea */
            }
            .sideBar {
                position: fixed;
                left: -100%; /* Start off-screen */
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
                position: fixed; /* Changed from absolute to fixed */
                top: 25px;
                left: 20px;
                z-index: 1100; /* Ensure it's above sidebar */
                font-size: 30px;
                cursor: pointer;
                color: #004AAD; /* Make it visible */
                /* visibility: visible; */ /* Not needed if display:block */
                width: auto; /* Let content define width */
                background-color: white; /* Added for visibility */
                padding: 5px 10px;
                border-radius: 3px;
            }

            .mainBody {
                width: 100%; /* Take full width when sidebar might be overlaying */
                margin-left: 0 !important; /* Ensure no margin when sidebar is inactive/overlay */
            }

            .header {
                justify-content: flex-end; /* Use flex-end for consistency */
            }
            .footbtnContainer {
                /* flex-direction: column; */ /* Stacking might not be ideal for just one button */
                /* align-items: center; */
                /* gap: 15px; */
                /* top: 30px; */ /* position:relative needed for top to work */
                margin: 20px auto; /* Simplified margin */
                justify-content: center; /* Center the back button container */
            }
            /* .tenantInfoContainer {
                height: 350px; 
                margin-bottom: 15px;
            } */ /* Let height be auto */
            .tenantHistoryHead h4 {
                margin-left: 20px; /* Reduce margin for smaller screens */
                font-size: 28px;
            }
        }

        @media (max-width: 480px) {
            .headerContent { margin-right: 20px; } /* Reduce margin */
            .headerContent a, .adminLogoutspace {
                font-size: 14px;
            }
            .hamburger {
                font-size: 28px;
                top: 15px;
                left: 15px;
            }
            .sideBar{
                width: 220px; /* Adjusted width for mobile */
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
            .card img { /* Assuming images are present for other cards */
                height: 18px; /* Adjust icon size in cards */
                width: 18px;
                margin-right: 8px;
            }
            /* .tenantInfoContainer {
                height: auto; 
                margin-bottom: 15px;
            } */ /* Already auto */
            .tenantHistoryHead h4 {
                font-size: 24px;
                margin-left: 15px;
            }
            .adminDetails p { font-size: 16px; }
            .admintxt { font-size: 20px; }
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
                    Tenants List</a>
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
                    <a href="ADMINPROFILE.php" class="adminTitle"><?php echo $admin_display_name; ?></a>
                    <p class="adminLogoutspace"> | </p>
                    <a href="LOGIN.php" class="logOutbtn">Log Out</a> <!-- Assumes LOGOUT.php is in parent dir -->
                </div>
            </div>
            <div class="mainContent">
                <div class="tenantHistoryHead">
                  <h4>Admin Profile</h4>
                </div>
                <div class="tenantInfoContainer"> <!-- Removed inline style -->
                    <img src="otherIcons/adminIcon.png" alt="Admin Profile Picture" class="tenantImageContainer">
                    <!-- <p class="admintxt">Admin</p> --><!-- This seems redundant if showing username/email below -->
                    <div class="adminDetails">
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($admin_email); ?></p>
                        <p><strong>Username:</strong> <?php echo $admin_username; ?></p>
                        <p><a class="adminLink" href="ADMINCHANGEPASSWORD.php">Change password  ›</a></p>
                        <p><a class="adminLink" href="ADMINCHANGEUSERNAME.php">Change username  ›</a></p>
                    </div>
                    <div class="footbtnContainer">
                        <a href="DASHBOARD.php" class="backbtn">⤾ Back</a>
                    </div>
                </div>
              </div>
        </div>
    </div>
    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sideBar');
            sidebar.classList.toggle('active');
        }
    </script>
</body>
</html>