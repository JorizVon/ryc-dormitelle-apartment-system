<?php
// Add session start at the very beginning of the file
session_start();

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php");
    exit();
}

// Connect to the database
require_once 'db_connect.php';

// Function to fetch the dashboard stats
function getDashboardData($conn) {
    $data = [];
    $default_value = 0;
    $default_currency = 0.00;

    function fetchSingleValue($conn, $query, $column_name, $default = 0) {
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row[$column_name] ?? $default;
        }
        return $default;
    }
    
    $query_total_units = "SELECT COUNT(*) AS total_units_count FROM `units`";
    $data['total_units_count'] = fetchSingleValue($conn, $query_total_units, 'total_units_count', $default_value);
    
    $query_available_units = "SELECT COUNT(*) AS available_unit_count FROM `units` WHERE unit_status = 'Available'";
    $data['available_unit_count'] = fetchSingleValue($conn, $query_available_units, 'available_unit_count', $default_value);
    
    $query_occupied_units = "SELECT COUNT(*) AS occupied_unit_count FROM `units` WHERE unit_status = 'Occupied'";
    $data['occupied_unit_count'] = fetchSingleValue($conn, $query_occupied_units, 'occupied_unit_count', $default_value);
    
    $query_total_tenants = "SELECT COUNT(*) AS total_tenants_count FROM `tenants`";
    $data['total_tenants_count'] = fetchSingleValue($conn, $query_total_tenants, 'total_tenants_count', $default_value);
    
    $query_monthly_earnings = "SELECT SUM(amount_paid) AS monthly_earnings FROM `payments` WHERE confirmation_status = 'confirmed' AND MONTH(payment_date_time) = MONTH(CURRENT_DATE()) AND YEAR(payment_date_time) = YEAR(CURRENT_DATE())";
    $data['monthly_earnings'] = fetchSingleValue($conn, $query_monthly_earnings, 'monthly_earnings', $default_currency);
    $data['monthly_earnings'] = $data['monthly_earnings'] === null ? $default_currency : (float)$data['monthly_earnings'];
    
    $query_due_today = 'SELECT COUNT(DISTINCT tu.tenant_id) AS due_today_count FROM `tenant_unit` tu JOIN `units` u ON tu.unit_no = u.unit_no WHERE DAY(tu.start_date) = DAY(CURRENT_DATE()) AND u.unit_status = "Occupied" AND (tu.end_date IS NULL OR tu.end_date >= CURRENT_DATE())';
    $data['due_today_count'] = fetchSingleValue($conn, $query_due_today, 'due_today_count', $default_value);
    
    return $data;
}

// Updated function to get chat list with latest message and unread status
// Fixed function to get chat list with latest message and unread status
function getChatListData($conn) {
    $chats = [];
    if (!isset($_SESSION['email_account'])) {
        return $chats;
    }
    $admin_email = $_SESSION['email_account'];
    
    // Simplified approach - get all unique users first
    $users_sql = "SELECT DISTINCT 
                    CASE 
                        WHEN sender_type = 'user' AND recipient = ? THEN email_account
                        WHEN sender_type = 'admin' AND email_account = ? THEN recipient
                    END as user_email
                  FROM chat_box 
                  WHERE ((sender_type = 'user' AND recipient = ?) OR (sender_type = 'admin' AND email_account = ?))
                  HAVING user_email IS NOT NULL";
    
    $stmt = $conn->prepare($users_sql);
    if (!$stmt) {
        error_log("Chat users query preparation failed: " . $conn->error);
        return $chats;
    }
    
    $stmt->bind_param("ssss", $admin_email, $admin_email, $admin_email, $admin_email);
    $stmt->execute();
    $users_result = $stmt->get_result();
    
    while ($user_row = $users_result->fetch_assoc()) {
        $user_email = $user_row['user_email'];
        
        // Get latest message for this user
        $latest_msg_sql = "SELECT message, sender_type, message_time_date
                          FROM chat_box 
                          WHERE ((sender_type = 'user' AND email_account = ? AND recipient = ?) OR 
                                 (sender_type = 'admin' AND email_account = ? AND recipient = ?))
                          ORDER BY message_time_date DESC 
                          LIMIT 1";
        
        $msg_stmt = $conn->prepare($latest_msg_sql);
        if ($msg_stmt) {
            $msg_stmt->bind_param("ssss", $user_email, $admin_email, $admin_email, $user_email);
            $msg_stmt->execute();
            $msg_result = $msg_stmt->get_result();
            $latest_msg = $msg_result->fetch_assoc();
            $msg_stmt->close();
        } else {
            $latest_msg = ['message' => '', 'sender_type' => '', 'message_time_date' => ''];
        }
        
        // Get unread count for this user
        $unread_sql = "SELECT COUNT(*) as unread_count
                      FROM chat_box 
                      WHERE email_account = ? AND recipient = ? AND read_status = 0 AND sender_type = 'user'";
        
        $unread_stmt = $conn->prepare($unread_sql);
        $unread_count = 0;
        if ($unread_stmt) {
            $unread_stmt->bind_param("ss", $user_email, $admin_email);
            $unread_stmt->execute();
            $unread_result = $unread_stmt->get_result();
            $unread_row = $unread_result->fetch_assoc();
            $unread_count = $unread_row['unread_count'] ?? 0;
            $unread_stmt->close();
        }
        
        // Add to chats array
        $chats[] = [
            'email_account' => $user_email,
            'message' => $latest_msg['message'] ?? '',
            'sender_type' => $latest_msg['sender_type'] ?? '',
            'message_time_date' => $latest_msg['message_time_date'] ?? '',
            'unread_count' => $unread_count
        ];
    }
    
    $stmt->close();
    
    // Sort by latest message time
    usort($chats, function($a, $b) {
        return strtotime($b['message_time_date']) - strtotime($a['message_time_date']);
    });
    
    return $chats;
}
$dashboardData = getDashboardData($conn);
$chatListData = getChatListData($conn); // Fetch chat data for the component

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - RYC Dormitelle</title>
    <link rel="icon" type="image/png" href="otherIcons/pageicon.png">
    
    <!-- Include the layout CSS -->
    <link rel="stylesheet" href="layout.css">
    
    <!-- Dashboard specific styles -->
    <style>
        /* Dashboard Grid Layout */
        .grid-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(2, 1fr);
            gap: 40px;
            padding: 0 20px;
            max-width: 1100px;
            margin: 20px 0;
        }

        /* Dashboard Cards */
        .statcards {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .statsInfo {
            display: flex;
            background-color: #BBE1FF;
            padding: 20px;
            height: 120px;
            position: relative;
        }

        .infoandIcon {
            display: flex;
            width: 100%;
            align-items: center;
        }

        .info {
            flex-grow: 1;
        }

        .info h1 {
            font-size: 30px;
            color: #333;
            margin: 0;
            margin-bottom: 5px;
        }

        .info h2 {
            font-size: 16px;
            color: #666;
            margin: 0;
            font-weight: normal;
        }

        .dashboardcontentIcons {
            width: 90px;
            height: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
        }

        .dashboardcontentIcons img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .moreInfo {
            background-color: #0056B3;
            height: 40px;
        }

        .moreInfo a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .moreInfo a:hover {
            background-color: #003D7A;
        }

        /* Responsive Dashboard */
        @media (max-width: 480px) {
            .grid-container {
                grid-template-columns: 1fr;
                gap: 20px;
                padding: 0 10px;
            }

            .info h1 {
                font-size: 24px;
            }

            .info h2 {
                font-size: 14px;
            }

            .dashboardcontentIcons {
                width: 70px;
                height: 70px;
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
            <h4>Dashboard</h4>
            
            <!-- Dashboard Grid -->
            <div class="grid-container">
                <!-- All Units -->
                <div class="statcards">
                    <div class="statsInfo">
                        <div class="infoandIcon">
                            <div class="info">
                                <h1 class="total_units_count"><?php echo htmlspecialchars($dashboardData['total_units_count']); ?></h1>
                                <h2 class="contentTitle">All Units</h2>
                            </div>
                            <div class="dashboardcontentIcons">
                                <img src="sidebarIcons/UnitsNumIcon.png" alt="Units Number Icon">
                            </div>
                        </div>
                    </div>
                    <div class="moreInfo">
                        <a href="UNITSINFORMATION.php">More Info</a>
                    </div>
                </div>

                <!-- Rented Units -->
                <div class="statcards">
                    <div class="statsInfo">
                        <div class="infoandIcon">
                            <div class="info">
                                <h1 class="occupied_unit_count"><?php echo htmlspecialchars($dashboardData['occupied_unit_count']); ?></h1>
                                <h2 class="contentTitle">Rented Units</h2>
                            </div>
                            <div class="dashboardcontentIcons">
                                <img src="sidebarIcons/RentedunitIcon.png" alt="Rented Unit Icon">
                            </div>
                        </div>
                    </div>
                    <div class="moreInfo">
                        <a href="UNITSINFORMATION.php?status=Occupied">More Info</a>
                    </div>
                </div>

                <!-- Available Units -->
                <div class="statcards">
                    <div class="statsInfo">
                        <div class="infoandIcon">
                            <div class="info">
                                <h1 class="available_unit_count"><?php echo htmlspecialchars($dashboardData['available_unit_count']); ?></h1>
                                <h2 class="contentTitle">Available Units</h2>
                            </div>
                            <div class="dashboardcontentIcons">
                                <img src="sidebarIcons/AvailableunitIcon.png" alt="Units Available Icon">
                            </div>
                        </div>
                    </div>
                    <div class="moreInfo">
                        <a href="UNITSINFORMATION.php?status=Available">More Info</a>
                    </div>
                </div>

                <!-- All Tenants -->
                <div class="statcards">
                    <div class="statsInfo">
                        <div class="infoandIcon">
                            <div class="info">
                                <h1 class="total_tenants_count"><?php echo htmlspecialchars($dashboardData['total_tenants_count']); ?></h1>
                                <h2 class="contentTitle">All Tenants</h2>
                            </div>
                            <div class="dashboardcontentIcons">
                                <img src="sidebarIcons/TenantnumIcon.png" alt="Tenant Number Icon">
                            </div>
                        </div>
                    </div>
                    <div class="moreInfo">
                        <a href="TENANTSLIST.php">More Info</a>
                    </div>
                </div>

                <!-- Monthly Earnings -->
                <div class="statcards">
                    <div class="statsInfo">
                        <div class="infoandIcon">
                            <div class="info">
                                <h1 class="monthly_earnings">₱<?php echo number_format($dashboardData['monthly_earnings'], 2); ?></h1>
                                <h2 class="contentTitle">Monthly Earnings</h2>
                            </div>
                            <div class="dashboardcontentIcons">
                                <img src="sidebarIcons/EarningsIcon.png" alt="Monthly Earnings Icon">
                            </div>
                        </div>
                    </div>
                    <div class="moreInfo">
                        <a href="PAYMENTMANAGEMENT.php">More Info</a>
                    </div>
                </div>

                <!-- Rent Due Today -->
                <div class="statcards">
                    <div class="statsInfo">
                        <div class="infoandIcon">
                            <div class="info">
                                <h1 class="due_today_count"><?php echo htmlspecialchars($dashboardData['due_today_count']); ?></h1>
                                <h2 class="contentTitle">Rent Due Today</h2>
                            </div>
                            <div class="dashboardcontentIcons">
                                <img src="sidebarIcons/TotalduesIcon.png" alt="Due dates Icon">
                            </div>
                        </div>
                    </div>
                    <div class="moreInfo">
                        <a href="TENANTSLIST.php?filter=due_today">More Info</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include the reusable chat component from chatfunctions folder -->
    <?php include 'chatfunctions/CHAT_COMPONENT.php'; ?>

</body>
</html>