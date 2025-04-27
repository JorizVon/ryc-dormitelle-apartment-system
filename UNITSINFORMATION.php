<?php
// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "ryc_dormitelle";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch units ordered correctly
$sql = "
    SELECT unit_no, unit_status
    FROM units
    ORDER BY 
        LEFT(unit_no, 1),
        LPAD(SUBSTRING(unit_no, 3), 3, '0')
";
$result = $conn->query($sql);

$units = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $units[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Units Information</title>
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
        .mainContent h4 {
            color: #01214B;
            font-size: 32px;
            margin-left: 60px;
            height: 20px;
        }
        .Unitslegend {
            display: flex;
            justify-content: left;
            align-items: center;
            position: relative;
            bottom: 35px;
            margin-left: 55px;
        }
        .legendsIcon {
            height: 20px;
            width: 20px;
            margin-right: 3px;
            margin-left: 8px;
        }
        .grid-wrapper {
            height: 59vh;
            overflow-y: auto;
            margin-left: 60px;
            margin-bottom: 50px;
            width: 1080px;
            position: relative;
            bottom: 30px;
            overflow-y: scroll;
            scrollbar-width: none;     /* Firefox */
            -ms-overflow-style: none;  /* IE and Edge */
        }
        .grid-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 40px;
        }
        .grid-container::-webkit-scrollbar {
            display: none;
        }

        .statcardOccupiedUnit {
            align-items: center;
            background-color: #FFFF;
            font-size: 20px;
            border-color: #A6DDFF;
            border-style: solid;
            border-width: 4px;
        }
        .statcardAvailableUnit {
            align-items: center;
            background-color: #A6DDFF;
            font-size: 20px;
            border-color: #A6DDFF;
            border-style: solid;
            border-width: 4px;
        }
        .statsInfoOccupiedUnit {
            align-items: center;
            height: 80%;
            width: 100%;
            margin: 0px auto;
            background-color: #FFFF;
        }
        .statsInfoAvailableUnit {
            align-items: center;
            height: 80%;
            width: 100%;
            background-color: #A6DDFF;
        }
        .UnitInfocontentIcons {
            height: 80px;
            width: 80px;
            left: 33%;
            top: 15px;
            position: relative;
        }
        .unit_no {
            position: relative;
            left: 35%;
            top: 15px;
            font-size: 26px;
            margin: 5px auto;
        }
        .viewandInfo {
            height: 28px;
            width: 140px;
            background-color: #004AAD;
            color: #FFFF;
            display: flex;
            margin: 15px auto;
            position: relative;
            bottom: 12px;
            justify-content: center;
            align-items: center;
            font-size: 16px;
            text-decoration: none;
        }
        .viewandInfo:hover {
            background-color: #FFFFFF;
            color: #004AAD;
            border: 1px solid #004AAD;
            height: 26px;
        }
        .addUnitbtnContainer {
            display: flex;
            justify-content: space-between;
            width: 70%;
            align-items: center;
            position: fixed;
            top: 685px;
            right: 58px;
        }
        .addUnitbtn {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 36px;
            width: 255px;
            border-radius: 5px;
        }
        .PlusSign {
            color: #FFFF;
            font-weight: 1000;
            font-size: 22px;
            margin-right: 5px;
        }
        .addUnitbtn a {
            color: #FFFF;
            font-size: 16px;
            text-decoration: none;
            height: 100%;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #004AAD;
            border-radius: 5px;
        }
        .addUnitbtnIcon {
            height: 20px;
            width: 20px;
            margin-right: 5px;
        }
        .addUnitbtn a:hover .addUnitbtnIcon {
            content: url('UnitsInfoIcons/plusblue.png');
        }
        .addUnitbtn a:hover {
            background-color: #FFFF;
            color: #004AAD;
            border-style: solid;
            border-color: #004AAD;
        }
        .backbtn {
            height: 36px;
            width: 110px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            bottom: 4px;
            margin-left: 5px;
            background-color: #004AAD;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 5px;
        }
        .backbtn a {
            color: #FFFF;
            font-size: 16px;
            text-decoration: none;
            height: 100%;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #004AAD;
            border-radius: 5px;
        }
        .backbtn a:hover {
            background-color: #FFFF;
            color: #004AAD;
            border-style: solid;
            border-color: #004AAD;
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
                <a href="UNITSINFORMATION.php" style="background-color: #FFFF; color: #004AAD;">
                    <img src="sidebarIcons/UnitsInfoIcon.png" alt="Units Information Icon" class="UIsidebarIcon" style="margin-right: 10px;">
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
            <h4>Units Information</h4>
            <div class="Unitslegend">
                <img src="UnitsInfoIcons/OccupiedUnitIcon.png" alt="Occupied Unit Icon" class="legendsIcon">
                <p>Occupied</p>
                <img src="UnitsInfoIcons/UnoccupiedUnitIcon.png" alt="Occupied Unit Icon" class="legendsIcon">
                <p>Available Unit</p>
            </div>
           <div class="grid-wrapper">
            <div class="grid-container">
            <?php
                foreach ($units as $unit) {
                    if ($unit['unit_status'] === 'Occupied') {
                        echo "
                        <div class='statcardOccupiedUnit'>
                            <div class='statsInfoOccupiedUnit'>
                                <img src='UnitsInfoIcons/OccupiedUnitIcon.png' alt='Occupied Unit Icon' class='UnitInfocontentIcons'>
                                <h1 class='unit_no'>{$unit['unit_no']}</h1>
                            </div>
                            <a href='OCCUPIEDUNITOVERVIEW.php?unit_no=" . urlencode($unit['unit_no']) . "' class='viewandInfo'>View</a>
                        </div>";
                    } else if ($unit['unit_status'] === 'Available') {
                        echo "
                        <div class='statcardAvailableUnit'>
                            <div class='statsInfoAvailableUnit'>
                                <img src='UnitsInfoIcons/UnoccupiedUnitIcon.png' alt='Available Unit Icon' class='UnitInfocontentIcons'>
                                <h1 class='unit_no'>{$unit['unit_no']}</h1>
                            </div>
                            <a href='AVAILABLEUNITOVERVIEW.php?unit_no=" . urlencode($unit['unit_no']) . "' class='viewandInfo'>Info</a>
                        </div>";
                    }
                }
            ?>
            </div>
           </div>
           <div class="addUnitbtnContainer">
                <div class="backbtn">
                    <a href="DASHBOARD.php">&#10558; Back</a>
                </div>
                <div class="addUnitbtn">
                    <a href="ADDNEWUNIT.php" type="action-btn">
                        <img src="UnitsInfoIcons/pluswht.png" alt="Units Information Icon" class="addUnitbtnIcon">
                        Add New Unit</a>
                </div>
           </div>
        </div>
    </div>
</body>
</html>