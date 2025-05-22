<?php
session_start();

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php"); // Adjust path if LOGIN.php is not in the same directory
    exit();
}

require_once 'db_connect.php'; // Adjust path if db_connect.php is not in the same directory
date_default_timezone_set('Asia/Manila');

$card_data = null;
$page_error = "";
$form_message = ""; // For success or error messages from form submission
$unit_no_get = null;
$tenant_id_get = null; // Optional: if you pass tenant_id for more specific card identification

// --- Retrieve card_id or unit_no/tenant_id from GET parameter ---
// BEST OPTION: Use a unique card_id if your table has it
if (isset($_GET['card_id'])) { // Change this if you use a different parameter name like 'card_registration_id'
    $identifier_value = trim($_GET['card_id']);
    $identifier_column = "cr.card_id"; // Assuming card_registration aliased as cr
    $identifier_type = "s"; // Or "i" if it's an integer
} elseif (isset($_GET['unit_no']) && isset($_GET['tenant_id'])) { // Fallback if using unit_no and tenant_id
    $unit_no_get = trim($_GET['unit_no']);
    $tenant_id_get = trim($_GET['tenant_id']);
    // In this case, the WHERE clause will be more complex or you'll fetch by unit_no and then filter/verify by tenant_id
    // For simplicity in initial fetch, let's assume unit_no is primary for now, but update WHERE clause needs tenant_id.
    $identifier_value = $unit_no_get;
    $identifier_column = "tu.unit_no"; // Assuming tenant_unit aliased as tu, and this is where unit_no is most relevant for tenant
    $identifier_type = "s";
} else {
    $page_error = "No valid card identifier provided.";
}


// --- Data Retrieval Logic ---
if (empty($page_error)) {
    // SQL to fetch card details
    // Added aliases for clarity and specific tenant_ID from card_registration
    $sql = "SELECT cr.card_no, tu.unit_no, t.tenant_name, t.contact_number, cr.tenant_ID AS card_tenant_id, 
                   cr.registration_date, cr.card_expiry, cr.card_status 
            FROM card_registration cr
            INNER JOIN tenants t ON cr.tenant_ID = t.tenant_ID
            LEFT JOIN tenant_unit tu ON cr.tenant_ID = tu.tenant_ID "; // LEFT JOIN in case tenant has no current unit assignment but card exists

    // Adjust WHERE clause based on identifier
    if (isset($_GET['card_id'])) {
        $sql .= " WHERE cr.card_id = ?"; // Using card_id if available
    } elseif ($unit_no_get && $tenant_id_get) {
        // If using unit_no and tenant_id, the WHERE clause should target card_registration directly
        // and ensure the correct tenant is associated if unit_no isn't unique per card.
        // The simplest for now is to assume tenant_id from card_registration is what matters.
        $sql .= " WHERE tu.unit_no = ? AND cr.tenant_ID = ?";
    } else {
        $page_error = "Insufficient identifiers to fetch card data."; // Should not happen if initial check is good
    }


    if (empty($page_error)) {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            if (isset($_GET['card_id'])) {
                $stmt->bind_param($identifier_type, $identifier_value);
            } elseif ($unit_no_get && $tenant_id_get) {
                $stmt->bind_param("ss", $unit_no_get, $tenant_id_get); // unit_no, tenant_id
            }
            
            $stmt->execute();
            $result_card = $stmt->get_result();
            if ($result_card && $result_card->num_rows > 0) {
                $card_data = $result_card->fetch_assoc();
            } else {
                $page_error = "Card registration details not found.";
            }
            $stmt->close();
        } else {
            $page_error = "Error preparing card details query: " . $conn->error;
        }
    }
}


// --- Handle Form Submission (Renew or Delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $card_data) { // Ensure card_data was fetched
    $posted_unit_no = $_POST['unit_no']; // This should come from a hidden field or be the original unit_no
    $posted_card_no = $_POST['card_no']; // This should come from a hidden field
    // Use the originally fetched tenant_id associated with the card for WHERE clauses
    $card_original_tenant_id = $card_data['card_tenant_id']; 


    if (isset($_POST['renew_card'])) {
        $renewal_date = $_POST['renewal_date']; // This will update 'registration_date'
        $new_card_expiry = $_POST['card_expiry'];
        $new_card_status = $_POST['card_status'];

        if (empty($renewal_date) || empty($new_card_expiry) || empty($new_card_status)) {
            $form_message = "Error: Please fill all fields for renewal.";
        } else {
            // Prepare UPDATE statement
            // IMPORTANT: The WHERE clause needs to uniquely identify the card_registration record.
            // Using card_no AND tenant_ID from card_registration is safer if card_no is not globally unique.
            // If you have a unique `card_id` (primary key) in `card_registration`, use that in WHERE.
            $update_sql = "UPDATE card_registration 
                           SET registration_date = ?, card_expiry = ?, card_status = ? 
                           WHERE card_no = ? AND tenant_ID = ?"; // Assuming card_no + tenant_ID is unique enough
            $stmt_update = $conn->prepare($update_sql);

            if ($stmt_update) {
                $stmt_update->bind_param("sssss", $renewal_date, $new_card_expiry, $new_card_status, $posted_card_no, $card_original_tenant_id);
                if ($stmt_update->execute()) {
                    if ($stmt_update->affected_rows > 0) {
                        $form_message = "Success: Card details updated successfully!";
                        // Re-fetch data to show updated values
                        // This is a simplified re-fetch. Ideally, the main fetch logic would run again.
                        $card_data['registration_date'] = $renewal_date;
                        $card_data['card_expiry'] = $new_card_expiry;
                        $card_data['card_status'] = $new_card_status;
                    } else {
                        $form_message = "Notice: No changes were made. Data might be the same or record not found for update criteria.";
                    }
                } else {
                    $form_message = "Error: Could not update card details. " . $stmt_update->error;
                }
                $stmt_update->close();
            } else {
                $form_message = "Error preparing update statement: " . $conn->error;
            }
        }
    } elseif (isset($_POST['delete_card'])) {
        // Prepare DELETE statement
        // IMPORTANT: The WHERE clause needs to uniquely identify the card_registration record.
        $delete_sql = "DELETE FROM card_registration WHERE card_no = ? AND tenant_ID = ?";
        $stmt_delete = $conn->prepare($delete_sql);
        if ($stmt_delete) {
            $stmt_delete->bind_param("ss", $posted_card_no, $card_original_tenant_id);
            if ($stmt_delete->execute()) {
                if ($stmt_delete->affected_rows > 0) {
                    // Redirect to card registration list after successful deletion
                    header("Location: CARDREGISTRATION.php?message=" . urlencode("Card deleted successfully."));
                    exit();
                } else {
                    $form_message = "Error: Card not found or already deleted.";
                }
            } else {
                $form_message = "Error: Could not delete card. " . $stmt_delete->error;
            }
            $stmt_delete->close();
        } else {
            $form_message = "Error preparing delete statement: " . $conn->error;
        }
    }
}

// Get admin's display name (optional, for header)
$adminDisplayIdentifier = "ADMIN"; // Default
if (isset($_SESSION['email_account'])) {
    // Example: $adminDisplayIdentifier = htmlspecialchars(strtok($_SESSION['email_account'], '@'));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Information</title>
    <style>
        /* YOUR EXISTING CSS - REMAINS UNCHANGED */
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
        .cardreg { display: flex; /*justify-content: space-between;*/ justify-content: center; width: 100%; align-items: center; margin-bottom: 15px;}
        .cardreg h4 { color: #01214B; font-size: 32px; /*margin-left: 60px;*/ /*height: 20px;*/ align-items: center; margin:0;}
        .rfidContainer {
            max-width: 90%; margin: 0 auto; border: 3px solid #A6DDFF; border-radius: 8px;
            /*height: 470px;*/ /* Auto height */
            min-height: 420px; /* Min height to keep structure */
            overflow-y: auto; box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            scrollbar-width: none; padding-bottom: 20px; /* Padding at bottom */
        }
        .rfidContainer::-webkit-scrollbar { display: none; }
        .rfidImageContainer {
            display: block; margin-left: auto; margin-right: auto; width: 180px; height: 150px;
            margin-top: 15px; margin-bottom: 15px; /* Reduced bottom margin */ border: 2px solid #000000;
            border-radius: 0.5rem; object-fit: cover;
        }
        /* .uploadBtnContainer { text-align: center; margin-top: 5px; width: 100%;} */ /* Not used */
        /* .customUploadBtn { } */ /* Not used */
        /* .inputImage { visibility: hidden;} */ /* Not used */
        .cardregistration { /* Main form area */
            display: flex; justify-content: space-around; /* Was space-between */
            /* height: 50%; */ /* Auto height */
            width: 95%; margin: 0 auto; /* Centered */
            /* position: relative; */ /* bottom: 20px; */
            gap: 20px; /* Gap between columns */
            align-items: flex-start; /* Align columns to top */
        }
        .rfidInput1, .rfidInput2 {
            width: calc(50% - 10px); /* Each column takes half minus gap */
            /* height: 90%; */ /* Auto height */
            /* margin: auto 0px; */
            /* position: relative; */
            /* bottom: 25px; */
        }
        .formContainer { /* Wraps inputs in each column */
            width: 100%;
            /* height: 80%; */ /* Auto height */
            /* margin-top: 52px; */ /* Removed large top margin */
            /* margin-left: 15px; */ /* Handled by column gap */
        }
        .form-group { display: flex; align-items: center; margin-bottom: 8px;} /* For label-input alignment */
        .form-group label {
            display: inline-block; width: 170px; /* Slightly reduced fixed width for labels */
            min-width: 170px;
            margin-bottom: 0; /* Removed margin as form-group has it */
            padding: 2px 0; /* vertical padding */
            margin-right: 10px; /* Space between label and input */
            vertical-align: middle; color: #004AAD; font-weight: bold; font-size: 14px;
        }
        .form-group input[type="text"], .form-group input[type="date"], .form-group select {
            flex-grow: 1; /* Input takes remaining space */
            padding: 6px 8px; /* Standard padding */
            margin-bottom:0; /* Removed margin as form-group has it */
            border: 1px solid #ccc; border-radius: 3px; font-size: 14px;
            box-sizing:border-box;
        }
        .form-group input[readonly] { background-color: #f0f0f0; cursor: not-allowed; }

        .buttonContainer {
            width: 100%; display: flex; align-items: center; justify-content: flex-end;
            margin-top: 25px; /* More space above buttons */ padding-right: 0; /* No extra padding */
        }
        button.form-action-btn { /* Specific class for these buttons */
            background-color: #004AAD; border: none; width: 110px; /* Adjusted width */ height: 35px;
            justify-content: center; align-items: center; display: flex; color: #FFFFFF;
            font-weight: bold; margin-left: 10px; /* Spacing between buttons */
            border-radius: 4px; cursor:pointer; font-size: 13px;
        }
        /* button img { width: 15px; height: 15px; margin-right: 5px;} */ /* Not used for these buttons */
        button.form-action-btn:hover { background-color: #003080; /* Darker blue */ border: none; color: #FFFFFF; }
        button.form-action-btn.delete:hover { background-color: #c82333; } /* Specific hover for delete */


        .footbtnContainer {
            width: 90%; /* margin-left: 60px; */ margin: 20px auto 0 auto;
            display: flex; /* position: relative; */ /* top: 38px; */
            justify-content: flex-start; /* Only back button */ align-items: center;
        }
        .backbtn {
            height: 36px; width: 110px; /*position: relative;*/ display: flex;
            align-items: center; justify-content: center; /*bottom: 22px;*/
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
            button.form-action-btn { width:100%; max-width:200px; }
            .footbtnContainer { flex-direction: column; align-items: center; gap: 15px; margin: 20px auto; width:100%; }
            .backbtn { width:100%; max-width:200px; }
        }
        @media (max-width: 480px) {
            .sideBar{ width: 220px; }
            .cardreg h4 { font-size: 24px; }
            .rfidImageContainer { width:120px; height:100px; margin-bottom:10px;}
            .form-group label { font-size:13px; }
            .form-group input, .form-group select { font-size:13px; }
            button.form-action-btn { font-size:13px; }

        }
    </style>
</head>
<body>
    <div class="hamburger" onclick="toggleSidebar()">☰</div>
    <div class="sideBar">
        <!-- Sidebar content from your CARDREGISTRATION.PHP -->
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
                <h4>Renew or Delete Card</h4>
            </div>

            <?php if ($page_error): ?>
                <p class="form-message error"><?php echo $page_error; ?></p>
            <?php elseif ($card_data): ?>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?' . http_build_query($_GET); ?>">
                    <div class="rfidContainer">
                        <?php if (!empty($form_message)): ?>
                            <p class="form-message <?php echo strpos(strtolower($form_message), 'error') !== false ? 'error' : 'success'; ?>">
                                <?php echo $form_message; ?>
                            </p>
                        <?php endif; ?>

                        <img src="./otherIcons/cardreaderIcon.png" alt="RFID Card Icon" id="rfidImage" class="rfidImageContainer">
                                    
                        <div class="cardregistration">
                            <div class="rfidInput1">
                                <div class="formContainer">
                                    <div class="form-group">
                                        <label for="tenant_name">Tenant Name:</label>
                                        <input type="text" name="tenant_name" id="tenant_name" value="<?php echo htmlspecialchars($card_data['tenant_name']); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="contact_number">Contact No.:</label>
                                        <input type="text" name="contact_number" id="contact_number" value="<?php echo htmlspecialchars($card_data['contact_number']); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="unit_no">Unit No.:</label>
                                        <input type="text" name="unit_no" id="unit_no" value="<?php echo htmlspecialchars($card_data['unit_no']); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="tenant_ID">Tenant ID:</label>
                                        <input type="text" name="tenant_ID_display" id="tenant_ID_display" value="<?php echo htmlspecialchars($card_data['card_tenant_id']); ?>" readonly>
                                        <input type="hidden" name="tenant_ID" value="<?php echo htmlspecialchars($card_data['card_tenant_id']); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="rfidInput2">
                                <div class="formContainer">
                                    <div class="form-group">
                                        <label for="card_no">RFID Card No.:</label>
                                        <input type="text" name="card_no" id="card_no" value="<?php echo htmlspecialchars($card_data['card_no']); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="registration_date_display">Original Reg. Date:</label>
                                        <input type="date" name="registration_date_display" id="registration_date_display" value="<?php echo htmlspecialchars($card_data['registration_date']); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="renewal_date">Date of Renewal:</label>
                                        <input type="date" name="renewal_date" id="renewal_date" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="card_expiry">New Card Expiry:</label>
                                        <input type="date" name="card_expiry" id="card_expiry" value="<?php echo htmlspecialchars($card_data['card_expiry']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="card_status">New Card Status:</label>
                                        <select id="card_status" name="card_status" required>
                                            <option value="Active" <?php echo (strtolower($card_data['card_status']) === 'active') ? 'selected' : ''; ?>>Active</option>
                                            <option value="Expired" <?php echo (strtolower($card_data['card_status']) === 'expired') ? 'selected' : ''; ?>>Expired</option>
                                            <option value="Inactive" <?php echo (strtolower($card_data['card_status']) === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                            <!-- <option value="Pending">Pending</option> -->
                                        </select>
                                    </div>
                                    <div class="buttonContainer">
                                        <button type="submit" name="renew_card" class="form-action-btn">Renew</button>
                                        <button type="submit" name="delete_card" class="form-action-btn delete" onclick="return confirm('Are you sure you want to delete this card registration? This action cannot be undone.')" style="background-color: #dc3545;">Delete</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                      </div>
                </form>
            <?php else: ?>
                <!-- Fallback if $card_data is null and no $page_error was set earlier -->
                <?php if(empty($page_error)): ?>
                    <p class="form-message error">Could not load card details.</p>
                <?php endif; ?>
            <?php endif; ?>

            <div class="footbtnContainer">
                <a href="CARDREGISTRATION.php" class="backbtn">⤾ Back</a>
            </div>
        </div>
    </div>
    <script>

        function toggleSidebar() {
            const sidebar = document.querySelector('.sideBar');
            sidebar.classList.toggle('active');
        }

        // Set min date for renewal_date and card_expiry to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const renewalDateInput = document.getElementById('renewal_date');
            const cardExpiryInput = document.getElementById('card_expiry');

            if (renewalDateInput) {
                // renewalDateInput.min = today; // Renewal date can be today or future
                // If you want to set default renewal date to current date, it's already done by PHP value attribute
            }
            if (cardExpiryInput) {
                cardExpiryInput.min = today; // Expiry date should be today or in the future
            }
        });
    </script>
</body>
</html>
<?php
if (isset($conn)) {
    $conn->close();
}
?>