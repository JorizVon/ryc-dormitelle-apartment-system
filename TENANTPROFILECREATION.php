<?php

require_once 'db_connect.php';

// Handle Insert operation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenant_name = $_POST['tenant_name'];
    $contact_number = $_POST['contact_number'];
    $unit_no = $_POST['unit_no'];
    $tenant_ID = $_POST['tenant_ID'];
    $emergency_contact_name = $_POST['emergency_contact_name'];
    $emergency_contact_num = $_POST['emergency_contact_num'];
    $lease_start_date = $_POST['lease_start_date'];
    $lease_end_date = $_POST['lease_end_date'];
    $lease_payment_due = $_POST['rent_payment_due'];
    $lease_payment_amount = $_POST['rent_payment_amount'];
    $lease_status = $_POST['lease_status'];

    $tenant_image = '';
    if (isset($_FILES['tenant_image']) && $_FILES['tenant_image']['error'] === UPLOAD_ERR_OK) {
        $imageTmpPath = $_FILES['tenant_image']['tmp_name'];
        $imageName = basename($_FILES['tenant_image']['name']);
        $imageUploadPath = 'tenants_images/' . $imageName;

        if (move_uploaded_file($imageTmpPath, $imageUploadPath)) {
            $tenant_image = $imageName;
        }
    }

    // Insert into tenants
    $stmt = $conn->prepare("INSERT INTO tenants (tenant_ID, tenant_name, contact_number, emergency_contact_name, emergency_contact_num, tenant_image) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $tenant_ID, $tenant_name, $contact_number, $emergency_contact_name, $emergency_contact_num, $tenant_image);
    $stmt->execute();
    $stmt->close();

    // Insert into tenant_unit
    $stmt = $conn->prepare("INSERT INTO tenant_unit (tenant_ID, unit_no, lease_start_date, lease_end_date, lease_payment_amount, lease_payment_due, lease_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $tenant_ID, $unit_no, $lease_start_date, $lease_end_date, $lease_payment_amount, $lease_payment_due, $lease_status);
    $stmt->execute();
    $stmt->close();

    header("Location: TENANTSLIST.php");
    exit();
}
if (!$conn->connect_error) {
    $result = $conn->query("SELECT unit_no FROM units WHERE unit_status = 'Available'");
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
            height: 450px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            position: relative;
            bottom: 15px;
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
            height: 350px;
            width: 95%;
            margin-left: 10px;
            position: relative;
            bottom: 20px;
        }
        .tenantInfoInput1 {
            width: 50%;
            height: 90%;
            margin: auto 0px;
            position: relative;
            bottom: 25px;
        }
        .tenantInfoInput2 {
            width: 50%;
            height: 90%;
            margin: auto 0px;
            margin-left: 10px;
            position: relative;
            bottom: 25px;
        }
        .formContainer {
            width: 100%;
            height: 470px;
            margin-top: 30px;
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
            margin-top: 15px;
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
        .addtenantbtn {
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
                <a href="UNITSINFORMATION.html">
                    <img src="sidebarIcons/UnitsInfoIconWht.png" alt="Units Information Icon" class="UIsidebarIcon" style="margin-right: 10px;">
                    Units Information</a>
            </div>
            <div class="card">
                <a href="TENANTSLIST.html" style="background-color: #FFFF; color: #004AAD;">
                    <img src="sidebarIcons/TenantsInfoIcon.png" alt="Tenants Information Icon" class="THsidebarIcon" style="margin-right: 10px;">
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
                <a href="CARDREGISTRATION.html">
                    <img src="sidebarIcons/CardregisterIconWht.png" alt="Card Registration Icon" class="CGsidebarIcon" style="margin-right: 10px;">
                    Card Registration</a>
            </div>
            
        </div>
    </div>
        <div class="mainBody">
            <div class="header">
                <div class="headerContent">
                    <a href="ADMINPROFILE.html" class="adminTitle">ADMIN</a>
                    <p class="adminLogoutspace">&nbsp;|&nbsp;</p>
                    <a href="LOGIN.html" class="logOutbtn">Log Out</a>
                </div>
            </div>
            <div class="mainContent">
                <div class="tenantHistoryHead">
                    <h4>Create Tenant Profile</h4>
                </div>
                <form method="POST" enctype="multipart/form-data" action="">
                    <div class="tenantInfoContainer">
                        <img src="tenants_images/<?php echo isset($tenant_image) ? htmlspecialchars($tenant_image) : 'default.jpg'; ?>" 
                            alt="Tenant Image" id="tenantImage" class="tenantImageContainer">
                        <div class="uploadBtnContainer">
                            <button type="button" class="customUploadBtn" onclick="document.getElementById('imageInput').click();">Edit Profile</button>
                            <input type="file" accept="image/*" name="tenant_image" id="imageInput" class="inputImage">
                        </div>
                        <div class="tenantsInformation">
                            <div class="tenantInfoInput1">
                                <div class="formContainer">
                                    <div>
                                        <label for="tenant_name">Tenant Name</label>
                                        <input type="text" name="tenant_name" id="tenant_name" required>
                                    </div>
                                    <div>
                                        <label for="contact_number">Contact No.</label>
                                        <input type="text" 
                                            name="contact_number" 
                                            id="contact_number" 
                                            value="+639" 
                                            maxlength="13" 
                                            minlength="13" 
                                            pattern="\+639\d{9}" 
                                            oninput="autoPrefix(this)" 
                                            required>
                                    </div>
                                    <div>
                                        <label for="unit_no">Unit No.</label>
                                        <select name="unit_no" id="unit_no" required onchange="generateTenantID()">
                                            <option value="">-- Select Unit --</option>
                                            <?php foreach ($availableUnits as $unit): ?>
                                                <option value="<?php echo $unit; ?>"><?php echo $unit; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="tenant_ID">Tenant ID</label>
                                        <input type="text" name="tenant_ID" id="tenant_ID" readonly required>
                                    </div>
                                    <div>
                                        <label for="emergency_contact_name">Emergency Contact Person</label>
                                        <input type="text" name="emergency_contact_name" id="emergency_contact_name" required>
                                    </div>
                                    <div>
                                        <label for="emergency_contact_num">Emergency Contact No.</label>
                                        <input type="text" 
                                            name="emergency_contact_num" 
                                            id="emergency_contact_num" 
                                            value="+63" 
                                            maxlength="13" 
                                            minlength="13" 
                                            pattern="\+639\d{9}" 
                                            oninput="autoPrefix(this)" 
                                            required>
                                    </div>
                                </div>
                            </div>
                            <div class="tenantInfoInput2">
                                <div class="formContainer">
                                    <div>
                                        <label for="lease_start_date">Lease Start Date</label>
                                        <input type="date" name="lease_start_date" id="lease_start_date" required>
                                    </div>
                                    <div>
                                        <label for="lease_end_date">Lease End Date</label>
                                        <input type="date" name="lease_end_date" id="lease_end_date" required>
                                    </div>
                                    <div>
                                        <label for="rent_payment_due">Payment Due Date</label>
                                        <input type="text" name="rent_payment_due" id="rent_payment_due" readonly required>
                                    </div>
                                    <div>
                                        <label for="rent_payment_amount">Monthly Rent Amount</label>
                                        <input type="text" name="rent_payment_amount" id="rent_payment_amount" required>
                                    </div>
                                    <div>
                                        <label for="lease_status">Lease Status</label>
                                        <select name="lease_status" id="lease_status" required>
                                            <option value="">-- Lease Status --</option>
                                            <option value="Active">Active</option>
                                            <option value="Expired">Expired</option>
                                            <option value="Pending">Pending</option>
                                        </select>
                                    </div>
                                    <div class="buttonContainer">
                                        <button type="submit">Add Tenant</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="footbtnContainer">
                <a href="TENANTSLIST.php" class="backbtn">&#10558; Back</a>
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
        function generateTenantID() {
            const unitSelect = document.getElementById('unit_no');
            const tenantIDInput = document.getElementById('tenant_ID');
            const selectedUnit = unitSelect.value.replace(/[^a-zA-Z0-9]/g, '');
            
            if (selectedUnit) {
                const today = new Date();
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const day = String(today.getDate()).padStart(2, '0');
                const tenantID = `${year}${month}${day}${selectedUnit}`;
                tenantIDInput.value = tenantID;
            } else {
                tenantIDInput.value = '';
            }
        }
        function autoPrefix(input) {
            if (!input.value.startsWith('+63')) {
                input.value = '+63';
            }
        }
        function getDaySuffix(day) {
        if (day > 3 && day < 21) return 'th';
        switch (day % 10) {
            case 1: return 'st';
            case 2: return 'nd';
            case 3: return 'rd';
            default: return 'th';
        }
        }

        document.getElementById('lease_start_date').addEventListener('change', function () {
            const date = new Date(this.value);
            if (!isNaN(date)) {
                const day = date.getDate();
                const suffix = getDaySuffix(day);
                document.getElementById('rent_payment_due').value = `Every ${day}${suffix} day of the month`;
            } else {
                document.getElementById('rent_payment_due').value = '';
            }
        });
      </script>
</body>
</html>
