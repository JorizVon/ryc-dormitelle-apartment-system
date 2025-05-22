<?php
session_start();

if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php"); // Adjust path if needed
    exit();
}

require_once 'db_connect.php'; // Adjust path if needed
date_default_timezone_set('Asia/Manila');

$form_message = "";
$tenant_options = [];
$unit_options = [];
$tenant_unit_data_for_js = []; // To store data for client-side auto-fill

// Fetch all Tenant IDs for the select dropdown
$sql_tenants = "SELECT `tenant_ID`, `tenant_name`, `contact_number` FROM `tenants` ORDER BY `tenant_name`";
$result_tenants = $conn->query($sql_tenants);
if ($result_tenants && $result_tenants->num_rows > 0) {
    while ($row = $result_tenants->fetch_assoc()) {
        $tenant_options[] = $row;
    }
}

// Fetch all Occupied Unit Nos for the select dropdown
$sql_units = "SELECT `unit_no` FROM `units` WHERE `unit_status` = 'Occupied' ORDER BY `unit_no`";
$result_units = $conn->query($sql_units);
if ($result_units && $result_units->num_rows > 0) {
    while ($row = $result_units->fetch_assoc()) {
        $unit_options[] = $row['unit_no'];
    }
}

// Fetch combined tenant and unit data for JavaScript auto-fill
$sql_tenant_unit_info = "SELECT t.tenant_ID, tu.unit_no, t.tenant_name, t.contact_number 
                         FROM tenants t
                         INNER JOIN tenant_unit tu ON t.tenant_ID = tu.tenant_ID
                         WHERE tu.status = 'Active'"; // Fetch only for active leases perhaps
$result_tu_info = $conn->query($sql_tenant_unit_info);
if ($result_tu_info && $result_tu_info->num_rows > 0) {
    while ($row = $result_tu_info->fetch_assoc()) {
        $tenant_unit_data_for_js[] = $row;
    }
}


// Handle Form Submission (INSERT Logic)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_registration'])) {
    $card_no = trim($_POST['card_no']); // RFID Card No. from input
    $selected_unit_no = trim($_POST['unit_no']); // From select
    $selected_tenant_ID = trim($_POST['tenant_ID']); // From select
    $card_expiry = $_POST['card_expiry'];       // From date input
    $card_status = $_POST['card_status'];     // From select

    // Validate inputs
    if (empty($card_no) || empty($selected_unit_no) || empty($selected_tenant_ID) || empty($card_expiry) || empty($card_status)) {
        $form_message = "Error: Please fill in all required fields.";
    } elseif (strlen($card_no) < 5) { // Example validation for card number length
        $form_message = "Error: RFID Card No. seems too short.";
    } else {
        // Check if this card_no is already registered to avoid duplicates
        $stmt_check_card = $conn->prepare("SELECT card_no FROM card_registration WHERE card_no = ?");
        if ($stmt_check_card) {
            $stmt_check_card->bind_param("s", $card_no);
            $stmt_check_card->execute();
            $result_check_card = $stmt_check_card->get_result();
            if ($result_check_card->num_rows > 0) {
                $form_message = "Error: This RFID Card No. ('" . htmlspecialchars($card_no) . "') is already registered.";
            }
            $stmt_check_card->close();
        } else {
            $form_message = "Error: Could not prepare card check statement. " . $conn->error;
        }


        if (empty($form_message)) { // Proceed if no validation errors so far
            // The registration_date will be CURRENT_DATE as per your query
            $insert_sql = "INSERT INTO `card_registration`(`card_no`, `unit_no`, `tenant_ID`, 
                                                        `registration_date`, `card_expiry`, `card_status`) 
                           VALUES (?, ?, ?, CURRENT_DATE, ?, ?)";
            $stmt_insert = $conn->prepare($insert_sql);

            if ($stmt_insert) {
                $stmt_insert->bind_param("sssss", 
                    $card_no, 
                    $selected_unit_no, 
                    $selected_tenant_ID, 
                    $card_expiry, 
                    $card_status
                );

                if ($stmt_insert->execute()) {
                    if ($stmt_insert->affected_rows > 0) {
                        $form_message = "Success: New card registered successfully for Tenant ID: " . htmlspecialchars($selected_tenant_ID) . "!";
                        // Optionally clear form fields here or redirect
                        // For now, just show success message.
                        // To redirect:
                        // header("Location: CARDREGISTRATION.php?message=" . urlencode($form_message));
                        // exit();
                    } else {
                        $form_message = "Error: Could not register the card. No rows affected.";
                    }
                } else {
                    // Check for duplicate entry specifically for card_no if it's unique, or tenant_ID if one tenant can only have one card
                    if ($conn->errno == 1062) { // Error number for duplicate entry
                         $form_message = "Error: This card (No: ".htmlspecialchars($card_no).") or tenant (ID: ".htmlspecialchars($selected_tenant_ID).") might already have an active registration. Please check existing records.";
                    } else {
                        $form_message = "Error: Could not register the card. " . $stmt_insert->error;
                    }
                }
                $stmt_insert->close();
            } else {
                $form_message = "Error preparing insert statement: " . $conn->error;
            }
        }
    }
}

// Get admin's display name (optional, for header)
$adminDisplayIdentifier = "ADMIN"; // Default
if (isset($_SESSION['email_account'])) {
    // Example: $adminDisplayIdentifier = htmlspecialchars(strtok($_SESSION['email_account'], '@'));
}

// $conn->close(); // Close connection at the very end of the script
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Register</title>
    <style>
        /* YOUR EXISTING CSS FROM CARDRENEWORDELETE.PHP - REMAINS UNCHANGED */
        /* For brevity, I'm not pasting all CSS again, assuming it's identical */
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
            /* background-color: 004AAD; */ /* Invalid */
            color: #FFFF;
            background-color: #FFFF;
            color: #004AAD;
        }
        .card a:hover .DsidebarIcon { content: url('sidebarIcons/DashboardIcon.png');}
        .card a:hover .UIsidebarIcon { content: url('sidebarIcons/UnitsInfoIcon.png');}
        .card a:hover .THsidebarIcon { content: url('sidebarIcons/TenantsInfoIcon.png');}
        .card a:hover .PMsidebarIcon { content: url('sidebarIcons/PaymentManagementIcon.png');}
        .card a:hover .APLsidebarIcon { content: url('sidebarIcons/AccesspointIcon.png');}
        .card a:hover .CGsidebarIcon { content: url('sidebarIcons/CardregisterIcon.png');}
        .card a:hover .PIsidebarIcon { content: url('sidebarIcons/PendingInquiryIcon.png');}
        .mainBody { width: 100vw; height: 100%; background-color: white;}
        .header { height: 13vh; width: 100%; background-color: #79B1FC; display: flex; justify-content: end; align-items: center;}
        .headerContent { margin-right: 40px; display: flex; justify-content: center; align-items: center;}
        .adminTitle { font-size: 16px; color: #01214B; position: relative; text-decoration: none;}
        .headerContent .adminTitle:hover { color: #FFFF;}
        .adminLogoutspace { font-size: 16px; color: #01214B; position: relative; text-decoration: none;}
        .logOutbtn { font-size: 16px; color: #FFFF; position: relative; margin-left: 2px; text-decoration: none;}
        .headerContent a.logOutbtn:hover { color: #004AAD;} /* Specificity */
        .mainContent { height: calc(100% - 13vh); width: 100%; margin: 0px auto; background-color: #FFFF; padding-top: 20px; overflow-y:auto;}
        .cardreg { display: flex; justify-content: center; width: 100%; align-items: center; margin-bottom: 15px;}
        .cardreg h4 { color: #01214B; font-size: 32px; align-items: center; margin:0;}
        .rfidContainer {
            max-width: 90%; margin: 0 auto; border: 3px solid #A6DDFF; border-radius: 8px;
            min-height: 420px; 
            overflow-y: auto; box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            scrollbar-width: none; padding-bottom: 20px; 
        }
        .rfidContainer::-webkit-scrollbar { display: none; }
        .rfidImageContainer {
            display: block; margin-left: auto; margin-right: auto; width: 180px; height: 150px;
            margin-top: 15px; margin-bottom: 15px;  border: 2px solid #000000;
            border-radius: 0.5rem; object-fit: cover;
        }
        .cardregistration { 
            display: flex; justify-content: space-around; 
            width: 95%; margin: 0 auto; 
            gap: 20px; 
            align-items: flex-start; 
        }
        .rfidInput1, .rfidInput2 {
            width: calc(50% - 10px); 
        }
        .formContainer { 
            width: 100%;
        }
        .form-group { display: flex; align-items: center; margin-bottom: 8px;} 
        .form-group label {
            display: inline-block; width: 170px; 
            min-width: 170px;
            margin-bottom: 0; 
            padding: 2px 0; 
            margin-right: 10px; 
            vertical-align: middle; color: #004AAD; font-weight: bold; font-size: 14px;
        }
        .form-group input[type="text"], .form-group input[type="date"], .form-group select {
            flex-grow: 1; 
            padding: 6px 8px; 
            margin-bottom:0; 
            border: 1px solid #ccc; border-radius: 3px; font-size: 14px;
            box-sizing:border-box;
        }
        .form-group input[readonly] { background-color: #f0f0f0; cursor: not-allowed; }

        .buttonContainer { /* Changed from cardrenewordelete, used only for confirm button here */
            width: 100%; display: flex; align-items: center; justify-content: flex-end;
            margin-top: 25px; padding-right: 0;
        }
        /* This confirmbtn style was from your provided HTML, now applied to the button */
        button.confirmbtn-action {
            background-color: #004AAD; border: none; /*width: 130px;*/  /* Width from original confirmbtn */
            min-width: 130px; /* Use min-width for flexibility */
            height: 36px; /* Consistent height */
            justify-content: center; align-items: center; display: flex; color: #FFFFFF;
            font-weight: bold; /* Was 10000 */
            /* margin: auto 10px; */ /* Not needed if only one button */
            border-radius: 5px; cursor:pointer; font-size: 14px; padding: 0 15px;
        }
        button.confirmbtn-action:hover { background-color: #003080; border: none; color: #FFFFFF; }


        .footbtnContainer {
            width: 90%; margin: 20px auto 0 auto;
            display: flex; 
            justify-content: flex-start;  align-items: center;
        }
        .backbtn {
            height: 36px; width: 110px; display: flex;
            align-items: center; justify-content: center; 
            background-color: #004AAD; color: #FFFFFF; text-decoration: none;
            border-radius: 5px; font-size: 14px;
        }
        .footbtnContainer a.backbtn:hover { background-color: #FFFFFF; color: #004AAD; border: 2px solid #004AAD;}
        .hamburger { display: none;}

        /* Message styling */
        .form-message {
            text-align: center; padding: 10px; margin: 10px auto 15px auto; border-radius: 4px;
            font-weight: bold; max-width: calc(100% - 40px);
        }
        .form-message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;}
        .form-message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;}

        @media (max-width: 1024px) {
            .sideBar { position: fixed; left: -100%; top: 0; height: 100vh; z-index: 1000; transition: 0.3s ease; }
            .sideBar.active { left: 0; }
            .hamburger { display: block; position: fixed; top: 25px; left: 20px; z-index: 1100; font-size: 30px; cursor: pointer; color: #004AAD; width: auto; background-color: white; padding: 5px 10px; border-radius: 3px; }
            .mainBody { width: 100%; margin-left: 0 !important; }
            .mainContent { width: 95%; margin: 0 auto; margin-top: 20px; }
            .cardreg { flex-direction: column; text-align:center; margin-bottom: 20px;}
            .cardreg h4 { margin-left:0; font-size:28px; }
            .rfidContainer { height: auto; min-height:0; margin-top:0px; }
            .cardregistration { flex-direction: column; width:100%; margin-left:0; gap:0; }
            .rfidInput1, .rfidInput2 { width: 100%; margin-left:0; }
            .formContainer { margin-left:0; margin-top:10px;}
            .form-group { flex-direction: column; align-items: flex-start; }
            .form-group label { width:100%; text-align:left; margin-bottom:3px; font-size:14px; }
            .form-group input, .form-group select { width:100%; margin-bottom:10px; font-size:14px; }
            .buttonContainer { justify-content: center; flex-direction:column; gap:10px; margin-top:20px; }
            button.confirmbtn-action { width:100%; max-width:200px; }
            .footbtnContainer { flex-direction: column; align-items: center; gap: 15px; margin: 20px auto; width:100%; }
            .backbtn { width:100%; max-width:200px; }
        }
        @media (max-width: 480px) {
            .sideBar{ width: 220px; }
            .cardreg h4 { font-size: 24px; }
            .rfidImageContainer { width:120px; height:100px; margin-bottom:10px;}
            .form-group label { font-size:13px; }
            .form-group input, .form-group select { font-size:13px; }
            button.confirmbtn-action { font-size:13px; }
        }
    </style>
</head>
<body>
    <div class="hamburger" onclick="toggleSidebar()">☰</div>
    <div class="sideBar">
        <!-- Sidebar content from CARDREGISTRATION.PHP -->
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
                <a href="UNITSINFORMATION.php">
                    <img src="sidebarIcons/UnitsInfoIconWht.png" alt="Units Information Icon" class="UIsidebarIcon" style="margin-right: 5px;">
                    Units Information</a>
            </div>
            <div class="card">
                <a href="TENANTSLIST.php">
                    <img src="sidebarIcons/TenantsInfoIconWht.png" alt="Tenants Information Icon" class="THsidebarIcon" style="margin-right: 3px;">
                    Tenants List</a> <!-- Corrected -->
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
                <a href="CARDREGISTRATION.php"  style="background-color: #FFFF; color: #004AAD;">
                    <img src="sidebarIcons/CardregisterIcon.png" alt="Card Registration Icon" class="CGsidebarIcon" style="margin-right: 10px;">
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
                <a href="ADMINPROFILE.php" class="adminTitle"><?php echo htmlspecialchars($adminDisplayIdentifier); ?></a>
                <p class="adminLogoutspace"> | </p>
                <a href="LOGIN.php" class="logOutbtn">Log Out</a> <!-- Or your LOGOUT.php -->
            </div>
        </div>
        <div class="mainContent">
            <div class="cardreg">
                <h4>New Card to Register</h4>
            </div>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="rfidContainer">
                    <?php if (!empty($form_message)): ?>
                        <p class="form-message <?php echo strpos(strtolower($form_message), 'error') !== false ? 'error' : 'success'; ?>">
                            <?php echo $form_message; ?>
                        </p>
                    <?php endif; ?>

                    <img src="otherIcons/cardreaderIcon.png" alt="Card Reader Icon" id="rfidImage" class="rfidImageContainer">
                                
                    <div class="cardregistration">
                        <div class="rfidInput1">
                            <div class="formContainer">
                                <div class="form-group">
                                    <label for="tenant_ID_select">Tenant ID:</label>
                                    <select name="tenant_ID" id="tenant_ID_select" onchange="updateFieldsBasedOnSelection('tenant_ID')" required>
                                        <option value="">-- Select Tenant ID --</option>
                                        <?php foreach ($tenant_options as $tenant): ?>
                                            <option value="<?php echo htmlspecialchars($tenant['tenant_ID']); ?>">
                                                <?php echo htmlspecialchars($tenant['tenant_ID']) . " (" . htmlspecialchars($tenant['tenant_name']) . ")"; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="unit_no_select">Unit No.:</label>
                                     <select name="unit_no" id="unit_no_select" onchange="updateFieldsBasedOnSelection('unit_no')" required>
                                        <option value="">-- Select Unit No --</option>
                                        <?php foreach ($unit_options as $unit): ?>
                                            <option value="<?php echo htmlspecialchars($unit); ?>">
                                                <?php echo htmlspecialchars($unit); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="tenantNameDisplay">Tenant Name:</label>
                                    <input type="text" name="tenantNameDisplay" id="tenantNameDisplay" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="contactNumDisplay">Contact No.:</label>
                                    <input type="text" name="contactNumDisplay" id="contactNumDisplay" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="rfidInput2">
                            <div class="formContainer">
                                <div class="form-group">
                                    <label for="card_no">RFID Card No.:</label>
                                    <input type="text" name="card_no" id="card_no" placeholder="Enter RFID card number" required>
                                </div>
                                <div class="form-group">
                                    <label for="registration_date_display">Reg. Date:</label>
                                    <input type="date" name="registration_date_display" id="registration_date_display" value="<?php echo date('Y-m-d'); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="card_expiry">Card Expiration:</label>
                                    <input type="date" name="card_expiry" id="card_expiry" required>
                                </div>
                                <div class="form-group">
                                    <label for="card_status">Card Status:</label>
                                    <select id="card_status" name="card_status" required>
                                        <option value="">-- Select Card Status --</option>
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                        <option value="Expired">Expired</option>
                                    </select>
                                </div>
                                 <div class="buttonContainer"> <!-- Moved Confirm button here -->
                                    <button type="submit" name="confirm_registration" class="confirmbtn-action">Confirm Registration</button>
                                </div>
                            </div>
                        </div>
                    </div>
                  </div>
            </form>
            <div class="footbtnContainer">
                <a href="CARDREGISTRATION.php" class="backbtn">⤾ Back</a>
                <!-- Removed original confirm button from here as it's now in the form -->
            </div>
        </div>
    </div>
    <script>
        const tenantUnitData = <?php echo json_encode($tenant_unit_data_for_js); ?>;

        function updateFieldsBasedOnSelection(sourceField) {
            const tenantIdSelect = document.getElementById('tenant_ID_select');
            const unitNoSelect = document.getElementById('unit_no_select');
            const tenantNameDisplay = document.getElementById('tenantNameDisplay');
            const contactNumDisplay = document.getElementById('contactNumDisplay');

            let selectedTenantId = tenantIdSelect.value;
            let selectedUnitNo = unitNoSelect.value;
            let foundMatch = false;

            if (sourceField === 'tenant_ID' && selectedTenantId) {
                // Find the unit associated with this tenant
                const match = tenantUnitData.find(item => item.tenant_ID === selectedTenantId);
                if (match) {
                    unitNoSelect.value = match.unit_no;
                    tenantNameDisplay.value = match.tenant_name;
                    contactNumDisplay.value = match.contact_number;
                    foundMatch = true;
                }
            } else if (sourceField === 'unit_no' && selectedUnitNo) {
                // Find the tenant associated with this unit
                // Note: This assumes one primary tenant per unit for this auto-fill.
                // If multiple tenants can be in one unit, this might pick the first one.
                const match = tenantUnitData.find(item => item.unit_no === selectedUnitNo);
                if (match) {
                    tenantIdSelect.value = match.tenant_ID;
                    tenantNameDisplay.value = match.tenant_name;
                    contactNumDisplay.value = match.contact_number;
                    foundMatch = true;
                }
            }

            if (!foundMatch) {
                // If selection is cleared or no match, clear dependent fields
                if (sourceField === 'tenant_ID' && !selectedTenantId) {
                    unitNoSelect.value = "";
                    tenantNameDisplay.value = "";
                    contactNumDisplay.value = "";
                } else if (sourceField === 'unit_no' && !selectedUnitNo) {
                    tenantIdSelect.value = "";
                    tenantNameDisplay.value = "";
                    contactNumDisplay.value = "";
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const cardExpiryInput = document.getElementById('card_expiry');
            
            // Set min date for card_expiry to today
            if (cardExpiryInput) {
                cardExpiryInput.min = today.toISOString().split('T')[0];
            }
        });

        function toggleSidebar() {
            const sidebar = document.querySelector('.sideBar');
            sidebar.classList.toggle('active');
        }
    </script>
</body>
</html>
<?php
if (isset($conn)) {
    $conn->close();
}
?>