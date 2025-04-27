<?php
// Database connection settings
$host = "localhost";
$username = "root";
$password = "";
$database = "ryc_dormitelle";

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if unit_no is provided
    if (isset($_GET['unit_no']) && !empty($_GET['unit_no'])) {
        $unit_no = $_GET['unit_no'];

        // Prepare and execute the query
        $stmt = $pdo->prepare("
            SELECT 
                units.unit_no, 
                tenants.tenant_name, 
                tenant_unit.lease_start_date, 
                tenant_unit.lease_end_date, 
                tenant_unit.lease_payment_due, 
                units.monthly_rent_amount, 
                tenant_unit.deposit, 
                units.unit_size, 
                units.unit_type, 
                units.floor_level, 
                units.unit_status
            FROM units
            INNER JOIN tenant_unit ON tenant_unit.unit_no = units.unit_no
            INNER JOIN tenants ON tenants.tenant_ID = tenant_unit.tenant_ID
            WHERE units.unit_no = ?
        ");
        
        $stmt->execute([$unit_no]);
        $unitData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$unitData) {
            echo "No data found for this unit.";
            exit();
        }
    } else {
        echo "Unit number not provided.";
        exit();
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenants List</title>
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
        }
        .headerContent a {
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
            height: 20px;
            bottom: 35px;
            right: 10px;
        }
        .legendsIcon {
            height: 50px;
            width: 50px;
            margin-right: 3px;
            margin-left: 8px;
        }
        .unitNoandCode {
            height: 16px;
            position: relative;
            bottom: 40px;
            margin-left: 10px;
        }
        .unitNoandCode h5 {
            font-size: 20px;
            position: relative;
            height: 10px;
        }
        .unitNoandCode p {
            font-size: 14px;
            position: relative;
            bottom: 23px;
            margin-left: 2px;
        }
        .cardreg {
            display: flex;
            justify-content: left;
            width: 100%;
            align-items: center;
            
        }
        .cardreg h4 {
            color: #01214B;
            font-size: 32px;
            height: 20px;
            align-items: center;
            margin-bottom: 30px;
        }
        .rfidContainer {
            max-width: 90%;
            margin: 0 auto;
            border: 3px solid #A6DDFF;
            border-radius: 8px;
            height: 470px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .cardregistration {
            display: flex;
            justify-content: left;
            height: 350px;
            width: 95%;
            margin-left: 10px;
            position: relative;
            bottom: 5px;
        }
        .rfidInput1 {
            width: 60%;
            height: 90%;
            margin: auto 0px;
            position: relative;
            top: 50px;
        }

        .formContainer {
            width: 100%;
            height: 470px;
            margin-top: 5px;
            margin-left: 15px;
        }
        .formContainer label {
            display: inline-block;
            width: 180px;
            margin-bottom: 10px;
            padding: 2px;
            vertical-align: top;
            color: #004AAD;
        }
        .formContainer [type="text"],
        input[type="date"]
        {
            width: 300px;
            padding: 2px;
            margin-bottom:5px;
        }
        select {
            width: 308px;
            padding: 2px;
            margin-bottom: 15px;
        }
        .unitDetails {
            margin-left: 40px;
        }
        .unitDetails [type="text"]{
            position: relative;
            right: 40px;
        }
        button {
            background-color: #004AAD;
            border: none;
            width: 130px;
            height: 30px;
            justify-content: center;
            align-items: center;
            display: flex;
            color: #FFFFFF;
            font-weight: 10000;
            margin: auto 10px;
        }
        button img {
            width: 15px;
            height: 15px;
            margin-right: 5px;
        }
        button :hover {
            background-color: #FFFFFF;
            color: #004AAD;
            border: 2px #004AAD solid;
        }
        .footbtnContainer {
            width: 90%;
            height: 20px;
            margin-left: 60px;
            display: flex;
            position: relative;
            top: 38px;
            justify-content: space-between;
            align-items: center;
        }
        .backbtn {
            height: 36px;
            width: 110px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            bottom: 22px;
            background-color: #004AAD;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 5px;
        }
        button {
            background-color: #004AAD;
            color: white;
            border: none;
            border-radius: 5px;
        }
        button:hover {
            background-color: #FFFFFF;
            color: #004AAD;
            border: 2px solid #004AAD;
        }
        .footbtnContainer a:hover {
            background-color: #FFFFFF;
            color: #004AAD;
            border: 2px solid #004AAD;
        }
        .confirmbtn {
            height: 36px;
            width: 255px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            bottom: 20px;
            background-color: #004AAD;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 5px;
        }
        
        
        .footbtnContainer a:hover .printTenantInfo {
            content: url('otherIcons/printIconblue.png');
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
                <a href="DASHBOARD.html" class="changeicon">
                    <img src="sidebarIcons/DashboardIconWht.png" alt="Dashboard Icon" class="DsidebarIcon" style="margin-right: 10px;">
                    Dashboard
                </a>
            </div>
            <div class="card">
                <a href="UNITSINFORMATION.html" style="background-color: #FFFF; color: #004AAD;">
                    <img src="sidebarIcons/UnitsInfoIcon.png" alt="Units Information Icon" class="UIsidebarIcon" style="margin-right: 10px;">
                    Units Information</a>
            </div>
            <div class="card">
                <a href="TENANTSLIST.html">
                    <img src="sidebarIcons/TenantsInfoIconWht.png" alt="Tenants Information Icon" class="THsidebarIcon" style="margin-right: 10px;">
                    Tenants Lists</a>
            </div>
            <div class="card">
                <a href="PAYMENTMANAGEMENT.html">
                    <img src="sidebarIcons/PaymentManagementIconWht.png" alt="Payment Management Icon" class="PMsidebarIcon" style="margin-right: 10px;">
                    Payment Management</a>
            </div>
            <div class="card">
                <a href="ACCESSPOINTLOGS.html">
                    <img src="sidebarIcons/AccesspointIconWht.png" alt="Access Point Logs Icon" class="APLsidebarIcon" style="margin-right: 10px;">
                    Access Point Logs</a>
            </div>
            <div class="card">
                <a href="#">
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
                <div class="rfidContainer">
                    <div class="cardregistration">
                        <div class="rfidInput1">
                            <div class="formContainer">
                                <div class="cardreg">
                                    <div class="Unitslegend">
                                        <img src="UnitsInfoIcons/OccupiedUnitIcon.png" alt="Occupied Unit Icon" class="legendsIcon">
                                        <div class="unitNoandCode">
                                            <h5 id="unit_no">
                                                Unit No. <?php echo isset($unitData['unit_no']) ? htmlspecialchars($unitData['unit_no']) : 'N/A'; ?>
                                            </h5>
                                            <p id="unit_code">
                                                Status: <?php echo isset($unitData['unit_status']) ? htmlspecialchars($unitData['unit_status']) : 'N/A'; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Form fields -->
                                <div>
                                    <label for="tenant_name">Tenant Name</label>
                                    <input type="text" name="tenant_name" id="tenant_name" value="<?php echo isset($unitData['tenant_name']) ? htmlspecialchars($unitData['tenant_name']) : ''; ?>" readonly>
                                </div>

                                <div>
                                    <label for="lease_start_date">Lease Start Date</label>
                                    <input type="text" name="lease_start_date" id="lease_start_date" value="<?php echo isset($unitData['lease_start_date']) ? htmlspecialchars($unitData['lease_start_date']) : ''; ?>" readonly>
                                </div>

                                <div>
                                    <label for="lease_end_date">Lease End Date</label>
                                    <input type="text" name="lease_end_date" id="lease_end_date" value="<?php echo isset($unitData['lease_end_date']) ? htmlspecialchars($unitData['lease_end_date']) : ''; ?>" readonly>
                                </div>

                                <div>
                                    <label for="lease_payment_due">Payment Due Date</label>
                                    <input type="text" name="lease_payment_due" id="lease_payment_due" value="<?php echo isset($unitData['lease_payment_due']) ? htmlspecialchars($unitData['lease_payment_due']) : ''; ?>" readonly>
                                </div>

                                <div>
                                    <label for="lease_payment_amount">Monthly Rent Amount</label>
                                    <input type="text" name="lease_payment_amount" id="lease_payment_amount" value="<?php echo isset($unitData['monthly_rent_amount']) ? htmlspecialchars($unitData['monthly_rent_amount']) : ''; ?>" readonly>
                                </div>

                                <div>
                                    <label for="deposit">Current Deposit</label>
                                    <input type="text" name="deposit" id="deposit" value="<?php echo isset($unitData['deposit']) ? htmlspecialchars($unitData['deposit']) : ''; ?>" readonly>
                                </div>

                                <div>
                                    <label style="font-size: 20px;">Unit Details:</label>
                                </div>

                                <div class="unitDetails">
                                    <label for="unit_size">Unit Size</label>
                                    <input type="text" name="unit_size" id="unit_size" value="<?php echo isset($unitData['unit_size']) ? htmlspecialchars($unitData['unit_size']) : ''; ?>" readonly>
                                </div>

                                <div class="unitDetails">
                                    <label for="floor_level">Floor Level</label>
                                    <input type="text" name="floor_level" id="floor_level" value="<?php echo isset($unitData['floor_level']) ? htmlspecialchars($unitData['floor_level']) : ''; ?>" readonly>
                                </div>

                                <div class="unitDetails">
                                    <label for="unit_type">Unit Type</label>
                                    <input type="text" name="unit_type" id="unit_type" value="<?php echo isset($unitData['unit_type']) ? htmlspecialchars($unitData['unit_type']) : ''; ?>" readonly>
                                </div>

                                <div>
                                    <label for="card_status">Card Status</label>
                                    <input type="text" name="card_status" id="card_status" value="Active" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <div class="footbtnContainer">
                <a href="UNITSINFORMATION.php" class="backbtn">&#10558; Back</a>
                <a href="#" class="confirmbtn">
                    Confirm</a>
            </div>
        </div>
    </div>
</body>
</html>