<?php

require_once 'db_connect.php';

$tenant = null;
$availableUnits = [];

// Handle Update and Delete operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenant_ID = $_POST['tenant_ID'];

    if (isset($_POST['update'])) {
        $tenant_name = $_POST['tenant_name'];
        $contact_number = $_POST['contact_number'];
        $emergency_contact_name = $_POST['emergency_contact_name'];
        $emergency_contact_num = $_POST['emergency_contact_num'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $payment_due = $_POST['rent_payment_due'];
        $payment_amount = $_POST['monthly_rent_amount'];
        $status = $_POST['status'];

        $tenant_image = '';
        if (isset($_FILES['tenant_image']) && $_FILES['tenant_image']['error'] === UPLOAD_ERR_OK) {
            $imageTmpPath = $_FILES['tenant_image']['tmp_name'];
            $imageName = basename($_FILES['tenant_image']['name']);
            $imageUploadPath = 'tenants_images/' . $imageName;

            if (move_uploaded_file($imageTmpPath, $imageUploadPath)) {
                $tenant_image = $imageName;
            }
        }
 
        if ($tenant_image) {
            $stmt = $conn->prepare("UPDATE tenants SET tenant_name=?, contact_number=?, emergency_contact_name=?, emergency_contact_num=?, tenant_image=? WHERE tenant_ID=?");
            $stmt->bind_param("ssssss", $tenant_name, $contact_number, $emergency_contact_name, $emergency_contact_num, $tenant_image, $tenant_ID);
        } else {
            $stmt = $conn->prepare("UPDATE tenants SET tenant_name=?, contact_number=?, emergency_contact_name=?, emergency_contact_num=? WHERE tenant_ID=?");
            $stmt->bind_param("sssss", $tenant_name, $contact_number, $emergency_contact_name, $emergency_contact_num, $tenant_ID);
        }
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE tenant_unit SET start_date=?, end_date=?, payment_amount=?, payment_due=?, status=? WHERE tenant_ID=?");
        $stmt->bind_param("ssssss", $start_date, $end_date, $payment_amount, $payment_due, $status, $tenant_ID);
        $stmt->execute();
        $stmt->close();

        header("Location: TENANTSLIST.php");
        exit();
    }

    if (isset($_POST['delete'])) {
        $stmt = $conn->prepare("DELETE FROM tenant_unit WHERE tenant_ID=?");
        $stmt->bind_param("s", $tenant_ID);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM tenants WHERE tenant_ID=?");
        $stmt->bind_param("s", $tenant_ID);
        $stmt->execute();
        $stmt->close();

        header("Location: TENANTSLIST.php");
        exit();
    }
}

// Load tenant data by ID
if (isset($_GET['tenant_ID']) && !empty($_GET['tenant_ID'])) {
    $tenant_ID = trim($_GET['tenant_ID']);

    $sql = "SELECT 
                tenants.tenant_name,
                tenants.contact_number,
                tenant_unit.unit_no,
                tenants.tenant_ID,
                tenants.emergency_contact_name,
                tenants.emergency_contact_num,
                tenant_unit.occupant_count,
                tenant_unit.start_date,
                tenant_unit.end_date,
                tenant_unit.payment_due,
                units.monthly_rent_amount,
                tenant_unit.status,
                tenants.tenant_image
            FROM tenants
            INNER JOIN tenant_unit ON tenants.tenant_ID = tenant_unit.tenant_ID
            INNER JOIN units ON tenant_unit.unit_no = units.unit_no
            WHERE tenants.tenant_ID = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $tenant_ID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $tenant = $result->fetch_assoc();
        } else {
            echo "Tenant not found.";
        }

        $stmt->close();
    } else {
        echo "SQL error: " . $conn->error;
    }
} else {
    echo "No valid tenant ID provided.";
}

// Fetch available units
$result = $conn->query("SELECT unit_no FROM units WHERE unit_status = 'Available'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $availableUnits[] = $row['unit_no'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Information</title>
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
        .tenantHistoryHead {
            display: flex;
            justify-content: space-between;
            width: 100%;
            align-items: center;
        }
        .tenantHistoryHead h4 {
            color: #01214B;
            font-size: 32px;
            margin-left: 60px;
            height: 20px;
            align-items: center;
        }
        .tenantInfoContainer {
            max-width: 90%;
            margin: 0 auto;
            border: 3px solid #A6DDFF;
            border-radius: 8px;
            height: 470px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            position: relative;
            bottom: 15px;
            overflow-y: auto;
            scrollbar-width: none;
        }
        .tenantInfoContainer::-webkit-scrollbar {
            display: none;
        }
        .tenantImageContainer {
            display: block;
            margin-left: auto;
            margin-right: auto;
            width: 203px;
            height: 170px;
            margin-top: 15px;
            border: 2px solid #000000;
            border-radius: 0.5rem;
            object-fit: cover; /* Keeps aspect ratio and fills container */
        }
        .uploadBtnContainer {
            text-align: center;
            margin-top: 5px;
            width: 100%;
            height: 10px;

        }
        .customUploadBtn {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0px auto;
            height: 25px;
            width: 130px;
            background-color: #79B1FC;
        }
        .inputImage {
            visibility: hidden;
        }
        .tenantsInformation {
            display: flex;
            justify-content: space-between;
            height: auto;
            width: 95%;
            margin-left: 10px;
            position: relative;
            top: 35px;
            align-items: center;
        }
        .tenantInfoInput1 {
            width: 50%;
            height: 90%;
            margin: auto 0px;
            position: relative;
        }
        .tenantInfoInput2 {
            width: 50%;
            height: 90%;
            margin: auto 0px;
            margin-left: 10px;
            position: relative;
        }
        .formContainer {
            width: 100%;
            height: 200px;
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
        .buttonContainer {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: right;
            margin-top: 5px;
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
        .printReportbtn {
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
        .printTenantInfo {
            height: 20px;
            width: 20px;
            margin-right: 5px;
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
                margin-top: 20px;
            }
            .tenantHistoryHead {
                height: 20px;
                width: 100%;
                margin-bottom: 80px;
            }
            .unitInfoInputs {
                width: 90%;
                height: 70%;
                margin: auto 0px;
            }
            .tenantInfoContainer {
                height: 480px;
                bottom: 50px;
                overflow-y: auto;
                scrollbar-width: none;
            }
            .tenantImageContainer {
                height: 150px;
                width: 150px;
            }
            .tenantInfoContainer::-webkit-scrollbar {
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
                width: 35vw;
                padding: 2px;
                margin-bottom:5px;
            }
            select {
                width: 36vw;
                padding: 2px;
                margin-bottom:5px;
            }
            .footbtnContainer {
                flex-direction: column;
                align-items: center;
                margin: 0 auto;
                top: 10px;
            }
            .formContainer {
                margin-left: 0px;
                padding-bottom: 15px;
                height: 300px;
            } 
            .cardreg {
                display: flex;
                justify-content: center;
                width: 100%;
                height: 50px;
                align-items: center;
                margin-bottom: 20px;
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
            .rfidImageContainer {
                height: 150px;
                width: 150px;
            }
            .backbtn {
                visibility: hidden;
            }
            .printReportbtn {
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
            .rfidInput1 {
                width: 50%;
            }
            .rfidInput2 {
                width: 50%;
            }
            .tenantInfoContainer {
                height: 600px;
                bottom: 50px;
                overflow-y: auto;
                scrollbar-width: none;
            }
            .tenantInfoContainer::-webkit-scrollbar {
                display: none;
            }
            .rfidImageContainer {
                height: 80px;
                width: 80px;
            }
            .cardregistration {
                height: 80px;
            }
            .buttonContainer {
                justify-content: center;
            }
            button {
                padding: 5px 10px;
                font: 14px;
                width: 30vw;
            }
            .formContainer {
                margin-left: 0;
                height: 300px;
                padding-bottom: 15px;
            }
            .cardreg {
                width: 100%;
            }
            .cardreg h4 {
                font-size: 24px;
                bottom: 15px;
                margin-left: 0px;
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
                width: 35vw;
                padding: 2px;
                margin-bottom:5px;
            }
            select {
                width: 36vw;
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
                <a href="UNITSINFORMATION.php">
                    <img src="sidebarIcons/UnitsInfoIconWht.png" alt="Units Information Icon" class="UIsidebarIcon" style="margin-right: 10px;">
                    Units Information</a>
            </div>
            <div class="card">
                <a href="TENANTSLIST.php" style="background-color: #FFFF; color: #004AAD;">
                    <img src="sidebarIcons/TenantsInfoIcon.png" alt="Tenants Information Icon" class="THsidebarIcon" style="margin-right: 10px;">
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
            <div class="tenantHistoryHead">
                <h4>Tenants Information</h4>
            </div>
            <form method="POST" enctype="multipart/form-data" action="">
                <div class="tenantInfoContainer">
                    <!-- Tenant Image -->
                    <img src="tenants_images/<?php echo htmlspecialchars($tenant['tenant_image']); ?>" 
                        alt="Tenant Image" 
                        id="tenantImage" 
                        class="tenantImageContainer">

                    <!-- Image Upload Button -->
                    <div class="uploadBtnContainer">
                        <button type="button" class="customUploadBtn" onclick="document.getElementById('imageInput').click();">Edit Profile</button>
                        <input type="file" accept="image/*" name="tenant_image" id="imageInput" class="inputImage">
                    </div>

                    <!-- Tenant Information Form -->
                    <div class="tenantsInformation"> 
                        <!-- Left Column -->
                        <div class="tenantInfoInput1">
                            <div class="formContainer">
                                <div>
                                    <label for="tenant_name">Tenant Name</label>
                                    <input type="text" maxlength="20" name="tenant_name" id="tenant_name" value="<?php echo $tenant['tenant_name']; ?>">
                                </div>
                                <div>
                                    <label for="contact_number">Contact No.</label>
                                    <input type="text" name="contact_number" id="contact_number" value="<?php echo $tenant['contact_number']; ?>"
                                            maxlength="13" 
                                            minlength="13" 
                                            pattern="\+639\d{9}" 
                                            oninput="autoPrefix(this)" 
                                            required>
                                </div>
                                <div>
                                    <label for="unit_no">Unit No.</label>
                                    <input type="text" name="unit_no" id="unit_no" value="<?php echo $tenant['unit_no']; ?>" readonly>
                                </div>
                                <div>
                                    <label for="tenant_ID">Tenant ID</label>
                                    <input type="text" name="tenant_ID" id="tenant_ID" value="<?php echo $tenant['tenant_ID']; ?>" readonly>
                                </div>
                                <div>
                                    <label for="emergency_contact_name">Emergency Contact Person</label>
                                    <input type="text" name="emergency_contact_name" id="emergency_contact_name" value="<?php echo $tenant['emergency_contact_name']; ?>">
                                </div>
                                <div>
                                    <label for="emergency_contact_num">Emergency Contact No.</label>
                                    <input type="text" name="emergency_contact_num" id="emergency_contact_num" value="<?php echo $tenant['emergency_contact_num']; ?>"
                                            maxlength="13" 
                                            minlength="13" 
                                            pattern="\+639\d{9}" 
                                            oninput="autoPrefix(this)" 
                                            required>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="tenantInfoInput2">
                            <div class="formContainer">
                                <div>
                                    <label for="occupant_count">Occupant Count</label>
                                    <input type="text" name="occupant_count" id="occupant_count" value="<?php echo $tenant['occupant_count']; ?> Occupant" readonly>
                                </div>
                                <div>
                                    <label for="start_date">Lease Start Date</label>
                                    <input type="date" name="start_date" id="start_date" value="<?php echo $tenant['start_date']; ?>">
                                </div>
                                <div>
                                    <label for="end_date">Lease End Date</label>
                                    <input type="date" name="end_date" id="end_date" value="<?php echo $tenant['end_date']; ?>">
                                </div>
                                <div>
                                    <label for="rent_payment_due">Payment Due Date</label>
                                    <input type="text" readonly name="rent_payment_due" id="rent_payment_due" value="<?php echo $tenant['payment_due']; ?>">
                                </div>
                                <div>
                                    <label for="monthly_rent_amount">Monthly Rent Amount</label>
                                    <input readonly  type="text" name="monthly_rent_amount" id="monthly_rent_amount" value="<?php echo $tenant['monthly_rent_amount']; ?>">
                                </div>
                                <div>
                                    <label for="status">Lease Status</label>
                                    <select name="status" id="status">
                                        <option value="Active" <?php echo ($tenant['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="Expired" <?php echo ($tenant['status'] == 'Expired') ? 'selected' : ''; ?>>Expired</option>
                                        <option value="Pending" <?php echo ($tenant['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                    </select>
                                </div>

                                <!-- Action Buttons -->
                                <div class="buttonContainer">
                                    <button type="submit" name="update">Update</button>
                                    <button type="submit" name="delete" onclick="return confirm('Are you sure you want to delete this tenant?')">Delete</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <div class="footbtnContainer">
                <a href="TENANTSLIST.php" class="backbtn">&#10558; Back</a>
                <a href="#" class="printReportbtn">
                    <img src="otherIcons/printIcon.png" alt="Plus Sign" class="printTenantInfo">
                    Print Report</a>
            </div>
        </div>
    </div>
    <script>
        const imageInput = document.getElementById('imageInput');
        const tenantImage = document.getElementById('tenantImage');
    
        imageInput.addEventListener('change', function(event) {
          const file = event.target.files[0];
          if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
              tenantImage.src = e.target.result;
            }
            reader.readAsDataURL(file);
          }
        });
      </script>
    <script>
        function getDaySuffix(day) {
        if (day > 3 && day < 21) return 'th';
        switch (day % 10) {
            case 1: return 'st';
            case 2: return 'nd';
            case 3: return 'rd';
            default: return 'th';
        }
        }

        document.getElementById('start_date').addEventListener('change', function () {
            const date = new Date(this.value);
            if (!isNaN(date)) {
                const day = date.getDate();
                const suffix = getDaySuffix(day);
                document.getElementById('rent_payment_due').value = `Every ${day}${suffix} day of the month`;
            } else {
                document.getElementById('rent_payment_due').value = '';
            }
        });
        function toggleSidebar() {
            const sidebar = document.querySelector('.sideBar');
            sidebar.classList.toggle('active');
        }
    </script>
</body>
</html>