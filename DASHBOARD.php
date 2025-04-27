<?php
// DATABASE CONNECTION (update with your own DB credentials)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ryc_dormitelle";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to fetch the counts
function getDashboardData($conn) {
    $data = [];

    // Total Units
    $query = "SELECT COUNT(*) AS total_units_count FROM `units`";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $data['total_units_count'] = $row['total_units_count'];

    // Available Units
    $query = "SELECT COUNT(*) AS available_unit_count FROM `units` WHERE unit_status = 'Available'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $data['available_unit_count'] = $row['available_unit_count'];

    // Occupied Units
    $query = "SELECT COUNT(*) AS occupied_unit_count FROM `units` WHERE unit_status = 'Occupied'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $data['occupied_unit_count'] = $row['occupied_unit_count'];

    // Total Tenants
    $query = "SELECT COUNT(*) AS total_tenants_count FROM `tenants`";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $data['total_tenants_count'] = $row['total_tenants_count'];

    // Monthly Earnings
    $query = "SELECT SUM(amount_paid) AS monthly_earnings FROM `payments` 
              WHERE transaction_type = 'Rent Payment'
              AND MONTH(payment_date) = MONTH(CURRENT_DATE())
              AND YEAR(payment_date) = YEAR(CURRENT_DATE())";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $data['monthly_earnings'] = $row['monthly_earnings'] ?? 0;

    // Today's Monthly Due
    $query = 'SELECT COUNT(*) AS due_today_count FROM `tenant_unit`
              WHERE DAY(tenant_unit.lease_start_date) = DAY(CURRENT_DATE());';
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $data['due_today_count'] = $row['due_today_count'] ?? 0;

    return $data;
}

// Fetch the dashboard data
$dashboardData = getDashboardData($conn);
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
        /* Updated mainContent styles */
        .mainContent {
            height: 100%;
            width: 90%;
            margin: 0px auto;
            background-color: #FFFF;
            padding: 20px;
        }
        .mainContent h4 {
            color: #01214B;
            font-size: 32px;
            margin-left: 20px;
            height: 20px;
            align-items: center;
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
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
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
                <a href="DASHBOARD.php" class="changeicon" style="background-color: #FFFF; color: #004AAD;">
                    <img src="sidebarIcons/DashboardIcon.png" alt="Dashboard Icon" class="DsidebarIcon" style="margin-right: 10px;">
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
            <h4>Dashboard</h4>
            <div class="grid-container">
                <!-- All Units -->
                <div class="statcards">
                    <div class="statsInfo">
                        <div class="infoandIcon">
                            <div class="info">
                                <h1 class="total_units_count"><?php echo $dashboardData['total_units_count']; ?></h1>
                                <h2 class="contentTitle">All Units</h2>
                            </div>
                            <img src="sidebarIcons/UnitsNumIcon.png" alt="Units Number Icon" class="dashboardcontentIcons">
                        </div>
                    </div>
                    <div class="moreInfo">
                        <a href="#">More Info</a>
                    </div>
                </div>

                <!-- Rented Units -->
                <div class="statcards">
                    <div class="statsInfo">
                        <div class="infoandIcon">
                            <div class="info">
                                <h1 class="occupied_unit_count"><?php echo $dashboardData['occupied_unit_count']; ?></h1>
                                <h2 class="contentTitle">Rented Units</h2>
                            </div>
                            <img src="sidebarIcons/RentedunitIcon.png" alt="Rented Unit Icon" class="dashboardcontentIcons">
                        </div>
                    </div>
                    <div class="moreInfo">
                        <a href="#">More Info</a>
                    </div>
                </div>

                <!-- Available Units -->
                <div class="statcards">
                    <div class="statsInfo">
                        <div class="infoandIcon">
                            <div class="info">
                                <h1 class="available_unit_count"><?php echo $dashboardData['available_unit_count']; ?></h1>
                                <h2 class="contentTitle">Available Units</h2>
                            </div>
                            <img src="sidebarIcons/AvailableunitIcon.png" alt="Units Available Icon" class="dashboardcontentIcons">
                        </div>
                    </div>
                    <div class="moreInfo">
                        <a href="#">More Info</a>
                    </div>
                </div>

                <!-- All Tenants -->
                <div class="statcards">
                    <div class="statsInfo">
                        <div class="infoandIcon">
                            <div class="info">
                                <h1 class="total_tenants_count"><?php echo $dashboardData['total_tenants_count']; ?></h1>
                                <h2 class="contentTitle">All Tenants</h2>
                            </div>
                            <img src="sidebarIcons/TenantnumIcon.png" alt="Tenant Number Icon" class="dashboardcontentIcons">
                        </div>
                    </div>
                    <div class="moreInfo">
                        <a href="#">More Info</a>
                    </div>
                </div>

                <!-- Monthly Earnings -->
                <div class="statcards">
                    <div class="statsInfo">
                        <div class="infoandIcon">
                            <div class="info">
                                <h1 class="monthly_earnings">&#8369;<?php echo number_format($dashboardData['monthly_earnings'], 2); ?></h1>
                                <h2 class="contentTitle">Monthly Earnings</h2>
                            </div>
                            <img src="sidebarIcons/EarningsIcon.png" alt="Monthly Earnings Icon" class="dashboardcontentIcons">
                        </div>
                    </div>
                    <div class="moreInfo">
                        <a href="#">More Info</a>
                    </div>
                </div>

                <!-- Total Due Dates (placeholder for now) -->
                <div class="statcards">
                    <div class="statsInfo">
                        <div class="infoandIcon">
                            <div class="info">
                                <h1 class="numberofOverdues"><?php echo $dashboardData['due_today_count']; ?></h1> <!-- Will update later -->
                                <h2 class="contentTitle">Total Due Dates</h2>
                            </div>
                            <img src="sidebarIcons/TotalduesIcon.png" alt="Due dates Icon" class="dashboardcontentIcons">
                        </div>
                    </div>
                    <div class="moreInfo">
                        <a href="#">More Info</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>