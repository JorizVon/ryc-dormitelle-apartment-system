<?php
// Add session start at the very beginning of the file
session_start();

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php"); // Assumes LOGIN.php is in the parent directory
    exit();
}

// Connect to the database, assuming db_connect.php is in the parent directory
require_once 'db_connect.php';

// Function to fetch the counts
function getDashboardData($conn) {
    $data = [];
    $default_value = 0; // For counts
    $default_currency = 0.00; // For monetary values

    // Helper function to execute query and fetch single value
    function fetchSingleValue($conn, $query, $column_name, $default = 0) {
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row[$column_name] ?? $default;
        } elseif (!$result) {
            error_log("SQL Error in getDashboardData: " . $conn->error . " | Query: " . $query);
            return $default;
        }
        return $default;
    }

    // Total Units
    $query_total_units = "SELECT COUNT(*) AS total_units_count FROM `units`";
    $data['total_units_count'] = fetchSingleValue($conn, $query_total_units, 'total_units_count', $default_value);

    // Available Units
    $query_available_units = "SELECT COUNT(*) AS available_unit_count FROM `units` WHERE unit_status = 'Available'";
    $data['available_unit_count'] = fetchSingleValue($conn, $query_available_units, 'available_unit_count', $default_value);

    // Occupied Units
    $query_occupied_units = "SELECT COUNT(*) AS occupied_unit_count FROM `units` WHERE unit_status = 'Occupied'";
    $data['occupied_unit_count'] = fetchSingleValue($conn, $query_occupied_units, 'occupied_unit_count', $default_value);

    // Total Tenants
    $query_total_tenants = "SELECT COUNT(*) AS total_tenants_count FROM `tenants`";
    $data['total_tenants_count'] = fetchSingleValue($conn, $query_total_tenants, 'total_tenants_count', $default_value);

    // Monthly Earnings
    $query_monthly_earnings = "SELECT SUM(amount_paid) AS monthly_earnings FROM `payments` 
                               WHERE transaction_type = 'Rent Payment'
                               AND MONTH(payment_date) = MONTH(CURRENT_DATE())
                               AND YEAR(payment_date) = YEAR(CURRENT_DATE())";
    $data['monthly_earnings'] = fetchSingleValue($conn, $query_monthly_earnings, 'monthly_earnings', $default_currency);
    $data['monthly_earnings'] = $data['monthly_earnings'] === null ? $default_currency : (float)$data['monthly_earnings'];

    // Today's Monthly Due
    $query_due_today = 'SELECT COUNT(DISTINCT tu.tenant_id) AS due_today_count 
                        FROM `tenant_unit` tu
                        JOIN `units` u ON tu.unit_no = u.unit_no
                        WHERE DAY(tu.start_date) = DAY(CURRENT_DATE())
                        AND u.unit_status = "Occupied"
                        AND (tu.end_date IS NULL OR tu.end_date >= CURRENT_DATE())';
    $data['due_today_count'] = fetchSingleValue($conn, $query_due_today, 'due_today_count', $default_value);

    return $data;
}

// Fetch the dashboard data
$dashboardData = getDashboardData($conn);

// Get admin's identifier for display
$adminDisplayIdentifier = "ADMIN"; // Default
if (isset($_SESSION['email_account'])) {
    // You might want to extract the username part from the email for display
    // For example: $adminDisplayIdentifier = htmlspecialchars(strtok($_SESSION['email_account'], '@'));
    // Or, if you store a separate name/username in session upon login, use that.
    // For now, we'll just use the email or a default "ADMIN" if you prefer not to show the full email.
    // Let's assume for this dashboard, "ADMIN" is fine, or you fetch a name from a user/admin table based on email.
    // If you have a user table with names associated with email_account:
    /*
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE email = ?"); // Assuming 'users' table and 'full_name', 'email' columns
    if ($stmt) {
        $stmt->bind_param("s", $_SESSION['email_account']);
        $stmt->execute();
        $resultUser = $stmt->get_result();
        if ($resultUser->num_rows > 0) {
            $userRow = $resultUser->fetch_assoc();
            $adminDisplayIdentifier = htmlspecialchars($userRow['full_name']);
        }
        $stmt->close();
    }
    */
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
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
        .headerContent a.logOutbtn:hover {
            color: #004AAD;
        }
        .mainContent {
            height: 100%;
            width: 93%;
            margin: 0px auto;
            background-color: #FFFF;
            padding: 20px;
        }
        .mainContent h4 {
            color: #01214B;
            font-size: 32px;
            margin-left: 20px;
            position: relative;
            bottom: 20px;
            height: 20px;
        }
        .grid-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(2, 1fr);
            gap: 40px;
            padding: 0 20px;
            max-width: 1100px;
        }
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
        }
        .moreInfo a:hover {
            background-color: #003D7A;
        }
        .hamburger {
            visibility: hidden;
            width: 0px;
        }
        @media (max-width: 1024px) {
            body {
            display: flex;
            margin: 0;
            background-color: #FFFF;
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
                position: fixed;
                top: 25px;
                left: 20px;
                z-index: 1100;
                font-size: 30px;
                cursor: pointer;
                color: #004AAD;
                visibility: visible;
                width: auto;
                background-color: white;
                padding: 5px 10px;
                border-radius: 3px;
            }
        }

        @media (max-width: 480px) {
            .headerContent a, .adminLogoutspace {
                font-size: 14px;
            }
            .hamburger {
                font-size: 28px;
                top: 15px;
                left: 15px;
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
            .grid-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .mainContent h4 {
                font-size: 24px;
                margin-left: 10px;
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
                <a href="DASHBOARD.php" class="changeicon" style="background-color: #FFFF; color: #004AAD;">
                    <img src="sidebarIcons/DashboardIcon.png" alt="Dashboard Icon" class="DsidebarIcon" style="margin-right: 8px;">
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
                <!-- Displaying "ADMIN" or a fetched name based on email_account -->
                <a href="ADMINPROFILE.php" class="adminTitle"><?php echo $adminDisplayIdentifier; ?></a>
                <p class="adminLogoutspace"> | </p>
                <!-- Ensure LOGOUT.php is in the parent directory or adjust path -->
                <a href="LOGIN.php" class="logOutbtn">Log Out</a>
            </div>
        </div>
        <div class="mainContent">
            <h4>Dashboard</h4>
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
                        <a href="PAYMENTMANAGEMENT.php?filter=due_today">More Info</a>
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