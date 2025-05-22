<?php
session_start(); // Start session at the very beginning

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    // Assuming LOGIN.php is in the same directory or adjust path if it's in parent (../LOGIN.php)
    header("Location: LOGIN.php");
    exit();
}

require_once 'db_connect.php';

// Handle the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $unit_no = $_POST['unit_no'];
    $apartment_no = $_POST['apartment_no'];
    $unit_address = $_POST['unit_address'];
    $unit_size = $_POST['unit_size'];
    $floor_level = $_POST['floor_level'];
    $unit_type = $_POST['unit_type'];
    $occupant_capacity = $_POST['occupant_capacity'];
    $monthly_rent_amount = $_POST['monthly_rent_amount'];
    $unit_status = 'Available'; // default unit status

    // Insert into units table
    $stmt = $conn->prepare("INSERT INTO `units`(`unit_no`, `apartment_no`, `unit_address`, `unit_size`, `floor_level`, `unit_type`, `occupant_capacity`, `monthly_rent_amount`, `unit_status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $unit_no, $apartment_no, $unit_address, $unit_size, $floor_level, $unit_type, $occupant_capacity, $monthly_rent_amount, $unit_status);
    $stmt->execute();
    $stmt->close();

    // Handle unit images
    if (isset($_FILES['unit_images'])) {
        $files = $_FILES['unit_images'];

        for ($i = 0; $i < count($files['name']); $i++) {
            $fileName = basename($files['name'][$i]);
            $targetDir = "unitImages/"; // Make sure this folder exists!
            $targetFilePath = $targetDir . $fileName;

            // Upload file to server
            if (move_uploaded_file($files['tmp_name'][$i], $targetFilePath)) {
                // Insert image record into database
                $stmt = $conn->prepare("INSERT INTO `unit_images`(`unit_no`, `unit_image`) VALUES (?, ?)");
                $stmt->bind_param("ss", $unit_no, $fileName);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    echo "<script>alert('Unit and images inserted successfully!'); window.location.href='UNITSINFORMATION.php';</script>";
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
            height: 530px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            position: relative;
            padding-top: 1px;
            bottom: 20px;
        }
        .unitImagesContainer {
            display: grid;
            grid-template-columns: repeat(4, 1fr); /* 4 equal columns */
            gap: 2px; /* same gap horizontally and vertically */
            width: max-content;
            margin: 0 auto;
            position: relative;
            bottom: 5px;
        }
        .unitImageCard {
            display: block;
            margin-left: auto;
            margin-right: auto;
            width: 30px;
            height: 30px;
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
            top: 10px;
        }
        .unitInfoInputs {
            width: 50%;
            height: 90%;
            margin: auto 0px;

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
        input[type="date"],
        input[type="number"]
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
            bottom: 30px;
            background-color: #004AAD;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 5px;
        }
        
        
        .footbtnContainer a:hover .printTenantInfo {
            content: url('otherIcons/printIconblue.png');
        }

        .unitphotoContainer {
            width: 100%;
            display: flex;
            justify-content: center;
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
                margin-top: 50px;
            }
            .unitInfoInputs {
                width: 90%;
                height: 70%;
                margin: auto 0px;
            }
            .overviewContainer {
                height: 480px;
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
                gap: 1px;
                padding-bottom: 15px;
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
                    <img src="sidebarIcons/DashboardIconWht.png" alt="Dashboard Icon" class="DsidebarIcon" style="margin-right: 10px;">
                    Dashboard
                </a>
            </div>
            <div class="card">
                <a href="UNITSINFORMATION.php" style="background-color: #FFFF; color: #004AAD;">
                    <img src="sidebarIcons/UnitsInfoIcon.png" alt="Units Information Icon" class="UIsidebarIcon" style="margin-right: 8px;">
                    Units Information</a>
            </div>
            <div class="card">
                <a href="TENANTSLIST.php">
                    <img src="sidebarIcons/TenantsInfoIconWht.png" alt="Tenants Information Icon" class="THsidebarIcon" style="margin-right: 5px;">
                    Tenants Lists</a>
            </div>
            <div class="card">
                <a href="PAYMENTMANAGEMENT.php">
                    <img src="sidebarIcons/PaymentManagementIconWht.png" alt="Payment Management Icon" class="PMsidebarIcon" style="margin-right: 3px;">
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
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="header">
                <div class="headerContent">
                    <a href="ADMINPROFILE.php" class="adminTitle">ADMIN</a>
                    <p class="adminLogoutspace">&nbsp;|&nbsp;</p>
                    <a href="LOGIN.php" class="logOutbtn">Log Out</a>
                </div>
            </div>

            <div class="mainContent">
            <div class="cardreg"></div>

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
                                    <input placeholder="eg. A-00#" type="text" name="unit_no" id="unit_no" required>
                                </div>

                                <div>
                                    <label for="apartment_no">Apartment No.</label>
                                    <input placeholder="eg. APT-00#" type="text" name="apartment_no" id="apartment_no" required>
                                </div>

                                <div>
                                    <label for="unit_address">Unit Address</label>
                                    <input type="text" name="unit_address" id="unit_address" required>
                                </div>

                                <div>
                                    <label for="unit_size">Unit Size</label>
                                    <input type="number" min="0" oninput="this.value = Math.abs(this.value)" name="unit_size" id="unit_size" required>
                                </div>

                                <div>
                                    <label for="floor_level">Floor Level</label>
                                    <input type="number" min="0" oninput="this.value = Math.abs(this.value)" name="floor_level" id="floor_level" required>
                                </div>

                                <div>
                                    <label for="unit_type">Unit Type</label>
                                    <input type="text" name="unit_type" id="unit_type" required>
                                </div>

                                <div>
                                    <label for="occupant_capacity">Occupant Capacity</label>
                                    <input type="number" min="0" oninput="this.value = Math.abs(this.value)" name="occupant_capacity" id="occupant_capacity" required>
                                </div>

                                <div>
                                    <label for="monthly_rent_amount">Monthly Rent Amount</label>
                                    <input type="text" name="monthly_rent_amount" id="monthly_rent_amount" required>
                                </div>

                                <div>
                                    <label for="unit_images">Upload (8) Unit Images</label>
                                    <input type="file" name="unit_images[]" id="unit_images" accept="image/*" multiple required onchange="previewImages()">
                                </div>
                                <div class="unitphotoContainer">
                                    <div class="unitImagesContainer" id="unitImagesContainer" ></div>
                                </div>


                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footbtnContainer">
                <a href="UNITSINFORMATION.php" class="backbtn">&#10558; Back</a>
                <button type="submit" class="confirmbtn">Confirm</button>
            </div>
        </form>
    </div>
    </div>
    <script>
        function previewImages() {
            const preview = document.getElementById('unitImagesContainer');
            const files = document.getElementById('unit_images').files;

            preview.innerHTML = ''; // Clear previous previews

            if (files.length > 8) {
                alert("You can only upload up to 6 images.");
                document.getElementById('unit_images').value = '';
                return;
            }

            Array.from(files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = 'Uploaded Unit Image';
                    img.className = 'unitImageCard'; // Keep your style
                    img.style.width = '80px';
                    img.style.height = '80px';
                    img.style.objectFit = 'cover';
                    img.style.margin = '5px';
                    img.style.borderRadius = '10px';
                    img.style.boxShadow = '0 0 5px rgba(0,0,0,0.3)';
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        }
        function toggleSidebar() {
        const sidebar = document.querySelector('.sideBar');
        sidebar.classList.toggle('active');
        }
    </script>
</body>
</html>