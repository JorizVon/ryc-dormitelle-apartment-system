<?php
session_start();

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    // Assuming LOGIN.php is in the same directory or adjust path as needed
    header("Location: LOGIN.php");
    exit();
}

require_once 'db_connect.php';

$unit_no = $_GET['unit_no'] ?? '';

$unit_size = '';
$floor_level = '';
$unit_type = '';
$occupant_capacity = '';
$monthly_rent_amount = '';

if ($unit_no !== '') {
    $stmt = $conn->prepare("SELECT `unit_no`, `unit_size`, `floor_level`, `unit_type`, `occupant_capacity`, `monthly_rent_amount` FROM `units` WHERE `unit_no` = ?");
    $stmt->bind_param("s", $unit_no);
    $stmt->execute();
    $stmt->bind_result($fetched_unit_no, $unit_size, $floor_level, $unit_type, $occupant_capacity, $monthly_rent_amount);
    if ($stmt->fetch()) {
        // Data fetched successfully
    }
    $stmt->close();
}
$unit_images = [];

if ($unit_no !== '') {
    // Fetch unit images from database
    $stmt = $conn->prepare("SELECT `unit_image` FROM `unit_images` WHERE `unit_no` = ?");
    $stmt->bind_param("s", $unit_no);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $unit_images[] = 'unitImages/' . $row['unit_image']; // add folder path
    }
    
    $stmt->close();
}

$conn->close();
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
        .cardreg {
            display: flex;
            justify-content: center;
            width: 100%;
            align-items: center;
            position: relative;
            bottom: 15px;
            height: 45px;
        }
        .cardreg h4 {
            color: #01214B;
            font-size: 32px;
            margin-left: 20px;
            height: 20px;
            align-items: center;
            margin-bottom: 10px;
            position: relative;
            bottom: 25px;
        }
        .cardreg img {
            height: 50px;
            width: 50px;
        }
        .overviewContainer {
            max-width: 90%;
            margin: 0 auto;
            border: 3px solid #A6DDFF;
            border-radius: 8px;
            height: 500px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            position: relative;
            bottom: 15px;
            overflow-y: auto;
            scrollbar-width: none;
        }
        .overviewContainer::-webkit-scrollbar {
            display: none;
            }
        .unitImagesContainer {
            display: grid;
            grid-template-columns: repeat(4, 1fr); /* 4 equal columns */
            gap: 10px; /* same gap horizontally and vertically */
            width: max-content;
            margin: 0 auto;x
        }

        .rfidImageContainer {
            display: block;
            margin-left: auto;
            margin-right: auto;
            width: 80px;
            height: 80px;
            border: 2px solid #000000;
            border-radius: 0.5rem;
            object-fit: cover; /* Keeps aspect ratio and fills container */
        }

        .cardregistration {
            display: flex;
            justify-content: center;
            height: 470px;
            width: 95%;
            margin-left: 10px;
            position: relative;
            top: 25px;
        }
        .unitInfoInputs {
            width: 50%;
            height: 90%;
            margin: auto 0px;
            position: relative;
            bottom: 25px;
        }
        .formContainer {
            width: 100%;
            height: 470px;
            margin-top: 5px;
            margin-left: 15px;
        }
        label {
            display: inline-block;
            width: 180px;
            margin-bottom: 5px;
            padding: 2px;
            vertical-align: top;
            color: #004AAD;
        }
        input[type="text"],
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
        .hamburger {
            visibility: hidden;
            width: 0px;
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

            .mainContent {
                width: 95%;
                margin: 0 auto;
                margin-top: 40px;
            }
            .unitInfoInputs {
                width: 90%;
                height: 70%;
                margin: auto 0px;
            }
            .overviewContainer {
                height: 500px;
                bottom: 50px;
                overflow-y: auto;
                scrollbar-width: none;
            }
            .overviewContainer::-webkit-scrollbar {
            display: none;
            }
            .cardregistration {
                height: 80px;
                margin-top: 30px;
            }
            label {
                width: 30vw;
                font-size: 16px;
            }
            input[type="text"],
            input[type="date"],
            input[type="number"],
            input[type="file"]
            {
                width: 40vw;
                padding: 2px;
                margin-bottom:5px;
            }
            .footbtnContainer {
                flex-direction: column;
                align-items: center;
                gap: 15px;
                top: 30px;
                margin: 0 auto;
            }
            .formContainer {
                margin-left: 0px;
                padding-bottom: 15px;
            } 
            .cardreg {
                width: 100%;
            }
            .unitphotoContainer {
                width: 100%;
                display: flex;
                justify-content: center;
            }
            .unitImagesContainer {
                height: 15vw;
                width: 35vw;
                margin-top: 15px;
                grid-template-columns: repeat(4, 1fr);
                gap: 2px;
                padding-bottom: 15px;
            }
            .unitImagesContainer img {
                height: 80px;
                width: 80px;
                margin: 5px;
            }
            .backbtn {
                visibility: hidden;
            }
            .confirmbtn {
                position: relative;
                bottom: 50px;
                padding: 5px 15px;
                font-size: 18px;
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
            .mainContent {
                width: 100%;
            }
            .unitInfoInputs {
                width: 100%;
            }
            .overviewContainer {
                height: 600px;
                bottom: 50px;
                overflow-y: auto;
                scrollbar-width: none;
            }
            .overviewContainer::-webkit-scrollbar {
            display: none;
            }
            .cardregistration {
                height: 80px;
            }
            .formContainer {
                margin-left: 0;
                height: 600px;
                padding-bottom: 15px;
            }
            .cardreg {
                width: 100%;
            }
            .cardreg h4 {
                font-size: 24px;
                bottom: 15px;
            }
            .cardreg img {
                height: 40px;
                width: 40px;
            }
            label {
                width: 35vw;
                font-size: 12px;
            }
            input[type="text"],
            input[type="date"],
            input[type="number"],
            input[type="file"]
            {
                width: 40vw;
                padding: 2px;
                margin-bottom:5px;
            }
            .unitphotoContainer {
                width: 100%;
                display: flex;
                justify-content: center;
            }
            .unitImagesContainer {
                height: 35vw;
                width: 100vw;
                margin-top: 15px;
                grid-template-columns: repeat(3, 1fr);
                gap: 1px;
                padding-bottom: 15px;
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
                <a href="UNITSINFORMATION.php" style="background-color: #FFFF; color: #004AAD;">
                    <img src="sidebarIcons/UnitsInfoIcon.png" alt="Units Information Icon" class="UIsidebarIcon" style="margin-right: 5px;">
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
                <a href="#">
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
                <div class="cardreg">
                </div>
                <div class="overviewContainer">
           
                    <div class="cardregistration">
                        <div class="unitInfoInputs">
                            <div class="formContainer">
                                <div class="cardreg">
                                    <img src="UnitsInfoIcons/UnoccupiedUnitIcon.png" alt="rent icon">
                                    <h4>Unit Overview</h4>
                                </div>
                                <div>
                                    <label for="unit_no">Unit No.</label>
                                    <input type="text" name="unit_no" id="unit_no" value="<?php echo htmlspecialchars($unit_no); ?>" readonly>
                                </div>

                                <div>
                                    <label for="unit_details">Unit Details</label>
                                </div>

                                <div>
                                    <label for="unit_size">Unit Size</label>
                                    <input type="text" name="unit_size" id="unit_size" value="<?php echo htmlspecialchars($unit_size); ?>" readonly>
                                </div>

                                <div>
                                    <label for="floor_level">Floor Level</label>
                                    <input type="text" name="floor_level" id="floor_level" value="<?php echo htmlspecialchars($floor_level); ?>" readonly>
                                </div>

                                <div>
                                    <label for="unit_type">Unit Type</label>
                                    <input type="text" name="unit_type" id="unit_type" value="<?php echo htmlspecialchars($unit_type); ?>" readonly>
                                </div>

                                <div>
                                    <label for="occupant_capacity">Occupant Capacity</label>
                                    <input type="text" name="occupant_capacity" id="occupant_capacity" value="Maximum of <?php echo htmlspecialchars($occupant_capacity); ?> occupant" readonly>
                                </div>

                                <div>
                                    <label for="monthly_rent_amount">Monthly Rent Amount</label>
                                    <input type="text" name="monthly_rent_amount" id="monthly_rent_amount" value="<?php echo htmlspecialchars($monthly_rent_amount); ?>" readonly>
                                </div>
                                <div>
                                    <label for="unit_images">Unit Photos </label>
                                </div>
                                <div class="unitphotoContainer">
                                    <div class="unitImagesContainer">
                                        <?php if (!empty($unit_images)): ?>
                                            <?php foreach ($unit_images as $image): ?>
                                                <img src="<?php echo htmlspecialchars($image); ?>" alt="Unit Photo" class="rfidImageContainer">
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <!-- If no images found, show 8 placeholders -->
                                            <?php for ($i = 0; $i < 8; $i++): ?>
                                                <img src="UnitsInfoIcons/UnoccupiedUnitIcon.png" alt="Placeholder" class="rfidImageContainer">
                                            <?php endfor; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                  </div>
            </div>
            <div class="footbtnContainer">
                <a href="UNITSINFORMATION.php" class="backbtn">&#10558; Back</a>
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