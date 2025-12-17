<?php
session_start();

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php");
    exit();
}

// Connect to the database
require_once 'db_connect.php';

$admin_email = $_SESSION['email_account'];
$admin_username = "N/A";
$admin_display_name = "ADMIN";

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
        // Assuming the full name is stored in admin_profile, but for display we can use username
        $admin_display_name = htmlspecialchars($account_data['email_account']);
    } else {
        error_log("Admin profile: No account found for email: " . $admin_email);
    }
    $stmt->close();
} else {
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
    <link rel="icon" type="image/png" href="otherIcons/pageicon.png">
    
    <!-- Include the layout CSS -->
    <link rel="stylesheet" href="layout.css">
    
    <!-- Admin Profile specific styles -->
    <style>
        .mainContent {
            padding: 20px;
            overflow-y: auto;
        }

        .tenantHistoryHead {
            display: flex;
            justify-content: space-between;
            width: 100%;
            align-items: center;
            margin-bottom: 20px;
            height: auto;
        }

        .tenantHistoryHead h4 {
            color: #01214B;
            font-size: 32px;
            margin-left: 60px;
        }

        .tenantInfoContainer {
            width: 90%;
            max-width: 600px;
            margin: 0 auto;
            border: 3px solid #A6DDFF;
            border-radius: 8px;
            height: auto;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            text-align: left; /* Changed for better alignment */
            padding: 30px 40px; /* Adjusted padding */
        }

        .tenantImageContainer {
            width: 120px; /* Adjusted size */
            height: 120px;
            object-fit: cover;
            display: block;
            margin: 0 auto 30px auto; /* Centered with more space */
        }

        .adminDetails {
            margin-top: 10px;
            font-size: 18px;
            color: #01214B;
        }

        .adminDetails .detail-item, .adminDetails .link-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .adminDetails .detail-item:last-of-type {
            border-bottom: none;
        }
        
        .adminDetails .link-item:last-of-type {
            border-bottom: none;
        }

        .adminDetails strong {
            color: #333;
        }
        
        .adminDetails .admin-name {
            font-size: 24px;
            font-weight: bold;
            color: #01214B;
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .adminDetails .adminLink {
            color: #004AAD;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
        }

        .adminDetails .adminLink:hover {
            text-decoration: underline;
        }

        .footbtnContainer {
            width: 90%;
            max-width: 600px;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin: 20px auto;
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
            border: 2px solid #004AAD;
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
            <div class="tenantHistoryHead">
                <h4>Admin Profile</h4>
            </div>
            
            <div class="tenantInfoContainer">
                <img src="otherIcons/adminIcon.png" alt="Admin Profile Picture" class="tenantImageContainer">
                <div class="adminDetails">
                    <p class="admin-name"><?php echo $admin_display_name; ?></p>
                    <p class="detail-item"><strong>Username:</strong> <span><?php echo $admin_username; ?></span></p>
                    <p class="link-item"><span>Change password</span> <a class="adminLink" href="ADMINCHANGEPASSWORD.php">›</a></p>
                    <p class="link-item"><span>Change username</span> <a class="adminLink" href="ADMINCHANGEUSERNAME.php">›</a></p>
                    <!-- UPDATED: Added link to the new creation page -->
                    <p class="link-item"><span>Replace current admin account</span> <a class="adminLink" href="ADMINNEWACCOUNTCREATION.php">›</a></p>
                </div>
            </div>
            
            <div class="footbtnContainer">
                <a href="DASHBOARD.php" class="backbtn">⤾ Back</a>
            </div>
        </div>
    </div>
</body>
</html>