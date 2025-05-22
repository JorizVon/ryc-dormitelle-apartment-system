<?php
session_start();

if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php"); // Adjust path if needed
    exit();
}

require_once 'db_connect.php'; // Adjust path if needed
date_default_timezone_set('Asia/Manila');

$inquiry_data = null;
$unit_details = null;
$page_error = "";
$form_submission_error = "";
$form_submission_success = "";

// --- Data Retrieval Logic ---
// We need a unique way to identify the inquiry. Using email and inquiry_datetime for now.
// A unique inquiry_id would be better.
if (isset($_GET['email']) && isset($_GET['inquiry_datetime'])) {
    $email_param = trim($_GET['email']);
    $inquiry_datetime_param = trim($_GET['inquiry_datetime']);

    // 1. Fetch from pending_inquiry
    $stmt_inquiry = $conn->prepare("SELECT `inquiry_date_time`, `unit_no`, `full_name`, `contact_no`, `email`, 
                                          `pref_move_date`, `start_date`, `end_date`, `payment_due_date` 
                                   FROM `pending_inquiry` 
                                   WHERE `email` = ? AND `inquiry_date_time` = ?");
    if ($stmt_inquiry) {
        $stmt_inquiry->bind_param("ss", $email_param, $inquiry_datetime_param);
        $stmt_inquiry->execute();
        $result_inquiry = $stmt_inquiry->get_result();
        if ($result_inquiry && $result_inquiry->num_rows > 0) {
            $inquiry_data = $result_inquiry->fetch_assoc();

            // 2. Fetch unit details using unit_no from inquiry_data
            $unit_no_from_inquiry = $inquiry_data['unit_no'];
            $stmt_unit = $conn->prepare("SELECT `unit_no`, `apartment_no`, `unit_address`, `unit_size`, 
                                               `occupant_capacity`, `floor_level`, `unit_type`, 
                                               `monthly_rent_amount`, `unit_status` 
                                        FROM `units` 
                                        WHERE `unit_no` = ?");
            if ($stmt_unit) {
                $stmt_unit->bind_param("s", $unit_no_from_inquiry);
                $stmt_unit->execute();
                $result_unit = $stmt_unit->get_result();
                if ($result_unit && $result_unit->num_rows > 0) {
                    $unit_details = $result_unit->fetch_assoc();
                    // Check if unit is still available or pending for this inquiry
                    if ($unit_details['unit_status'] !== 'Available' && $unit_details['unit_status'] !== 'Pending') {
                        $page_error = "This unit (" . htmlspecialchars($unit_details['unit_no']) . ") is no longer available or pending. Current status: " . $unit_details['unit_status'];
                        $inquiry_data = null; // Prevent form display
                        $unit_details = null;
                    }
                } else {
                    $page_error = "Unit details not found for unit number: " . htmlspecialchars($unit_no_from_inquiry);
                }
                $stmt_unit->close();
            } else {
                $page_error = "Error preparing unit details query: " . $conn->error;
            }
        } else {
            $page_error = "Pending inquiry not found for the provided details.";
        }
        $stmt_inquiry->close();
    } else {
        $page_error = "Error preparing inquiry query: " . $conn->error;
    }
} else {
    $page_error = "No valid inquiry identifier provided.";
}


// --- Handle Form Submission (INSERT Logic) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tenant']) && $inquiry_data && $unit_details) {
    // Retrieve all necessary data from form and from fetched data
    $tenant_name = trim($_POST['tenant_name']); // Should be from $inquiry_data, but allow override
    $contact_number = trim($_POST['contact_number']); // From $inquiry_data, allow override
    $email = $inquiry_data['email']; // From inquiry, should not change
    $unit_no = $inquiry_data['unit_no']; // From inquiry, should not change
    
    $emergency_contact_name = trim($_POST['emergency_contact_name']);
    $emergency_contact_num = trim($_POST['emergency_contact_num']);
    
    $start_date = $_POST['start_date']; // From $inquiry_data, allow override
    $end_date = $_POST['end_date'];     // From $inquiry_data, allow override
    $payment_due_text = $_POST['rent_payment_due']; // Text like "Every Xth day..."
    $monthly_rent_amount = $unit_details['monthly_rent_amount']; // From unit_details
    
    $occupant_count_input = (int)trim($_POST['occupant_count']); // Admin inputs this
    $deposit_input = (float)trim($_POST['deposit_paid']);   // Admin inputs this
    $billing_period = $_POST['billing_period']; // Admin might set this or derive
    $status_lease = 'Active'; // Lease status for tenant_unit

    // Validate required fields that admin inputs
    if (empty($tenant_name) || empty($contact_number) || empty($start_date) || empty($end_date) || empty($billing_period) || $occupant_count_input <= 0) {
        $form_submission_error = "Please fill in all required tenant and lease details.";
    } else {
        // Generate Tenant ID: YYYYMMDD + UnitNo
        $tenant_ID = date('Ymd') . preg_replace("/[^A-Za-z0-9]/", '', $unit_no); // Sanitize unit_no for ID
        if (strlen($tenant_ID) > 12) { // Ensure it fits VARCHAR(12)
            $tenant_ID = substr($tenant_ID, 0, 12);
        }

        // Handle tenant image upload
        $tenant_image_filename = null; // Default to no image or existing if logic changes
        if (isset($_FILES['tenant_image']) && $_FILES['tenant_image']['error'] === UPLOAD_ERR_OK) {
            $imageTmpPath = $_FILES['tenant_image']['tmp_name'];
            // Sanitize filename
            $imageName = preg_replace("/[^A-Z0-9._-]/i", "_", basename($_FILES['tenant_image']['name']));
            // Ensure unique filename
            $imageUploadPath = 'tenants_images/' . time() . '_' . $imageName;

            if (move_uploaded_file($imageTmpPath, $imageUploadPath)) {
                $tenant_image_filename = time() . '_' . $imageName;
            } else {
                $form_submission_error = "Failed to upload tenant image.";
            }
        }

        if (empty($form_submission_error)) { // Proceed if no upload error (or no image uploaded)
            $conn->begin_transaction();
            try {
                // 1. INSERT into tenants
                $stmt_insert_tenant = $conn->prepare(
                    "INSERT INTO `tenants`(`tenant_ID`, `tenant_name`, `contact_number`, `email`, 
                                       `emergency_contact_name`, `emergency_contact_num`, `tenant_image`) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                if (!$stmt_insert_tenant) throw new Exception("Prepare tenants insert failed: " . $conn->error);
                $stmt_insert_tenant->bind_param("sssssss", 
                    $tenant_ID, $tenant_name, $contact_number, $email, 
                    $emergency_contact_name, $emergency_contact_num, $tenant_image_filename
                );
                if (!$stmt_insert_tenant->execute()) throw new Exception("Execute tenants insert failed: " . $stmt_insert_tenant->error);
                $stmt_insert_tenant->close();

                // 2. INSERT into tenant_unit
                $stmt_insert_tenant_unit = $conn->prepare(
                    "INSERT INTO `tenant_unit`(`tenant_ID`, `unit_no`, `start_date`, `end_date`, 
                                             `occupant_count`, `deposit`, `balance`, `payment_due`, 
                                             `billing_period`, `status`) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                if (!$stmt_insert_tenant_unit) throw new Exception("Prepare tenant_unit insert failed: " . $conn->error);
                // Balance is initialized to monthly_rent_amount
                $stmt_insert_tenant_unit->bind_param("ssssidssss",
                    $tenant_ID, $unit_no, $start_date, $end_date, 
                    $occupant_count_input, $deposit_input, $monthly_rent_amount, $payment_due_text,
                    $billing_period, $status_lease
                );
                if (!$stmt_insert_tenant_unit->execute()) throw new Exception("Execute tenant_unit insert failed: " . $stmt_insert_tenant_unit->error);
                $stmt_insert_tenant_unit->close();

                // 3. UPDATE units status to 'Occupied'
                $stmt_update_unit = $conn->prepare("UPDATE `units` SET `unit_status`='Occupied' WHERE `unit_no`= ?");
                if (!$stmt_update_unit) throw new Exception("Prepare unit update failed: " . $conn->error);
                $stmt_update_unit->bind_param("s", $unit_no);
                if (!$stmt_update_unit->execute()) throw new Exception("Execute unit update failed: " . $stmt_update_unit->error);
                if ($stmt_update_unit->affected_rows === 0) throw new Exception("Unit status not updated or unit not found.");
                $stmt_update_unit->close();
                
                // 4. (Optional but Recommended) Delete from pending_inquiry
                $stmt_delete_inquiry = $conn->prepare("DELETE FROM pending_inquiry WHERE email = ? AND inquiry_date_time = ?");
                if ($stmt_delete_inquiry) {
                    $stmt_delete_inquiry->bind_param("ss", $email_param, $inquiry_datetime_param); // Use original params for deletion
                    $stmt_delete_inquiry->execute();
                    $stmt_delete_inquiry->close();
                } else {
                    error_log("Could not prepare delete inquiry statement: " . $conn->error); // Log this, but don't fail transaction
                }


                $conn->commit();
                $form_submission_success = "Tenant successfully added and unit status updated to Occupied! Tenant ID: " . $tenant_ID;
                // Redirect to tenants list or tenant details page after success
                // header("Location: TENANTSLIST.php?success_message=" . urlencode($form_submission_success));
                // For now, just show message on this page. To see the message after redirect, use GET param.
                 echo "<script>alert('" . addslashes($form_submission_success) . "'); window.location.href='TENANTSLIST.php';</script>";
                exit();

            } catch (Exception $e) {
                $conn->rollback();
                $form_submission_error = "Transaction failed: " . $e->getMessage();
            }
        }
    }
}


// Fetch available units for a dropdown (if needed for unit reassignment, not primary for this flow but kept from original)
// This is not directly used for pre-filling the current unit_no for this specific inquiry-to-tenant flow.
$availableUnits = [];
$result_avail_units = $conn->query("SELECT unit_no FROM units WHERE unit_status = 'Available'");
if ($result_avail_units) {
    while ($row_avail = $result_avail_units->fetch_assoc()) {
        $availableUnits[] = $row_avail['unit_no'];
    }
}

// $conn->close(); // Close connection at the very end of the script (after HTML)
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Tenant from Inquiry</title> <!-- Changed Title -->
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
            width: 180px;
            height: 160px;
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
        input[type="date"],
        input[type="email"],
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
        <!-- Sidebar content from your original TENANTSLIST.PHP -->
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
                <a href="TENANTSLIST.php" style="background-color: #FFFF; color: #004AAD;"> <!-- Assuming this page is part of tenant flow -->
                    <img src="sidebarIcons/TenantsInfoIcon.png" alt="Tenants Information Icon" class="THsidebarIcon" style="margin-right: 10px;">
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
                <a href="ADMINPROFILE.php" class="adminTitle">Admin</a>
                <p class="adminLogoutspace"> | </p>
                <a href="LOGIN.php" class="logOutbtn">Log Out</a> <!-- Or your LOGOUT.php -->
            </div>
        </div>
        <div class="mainContent">
            <div class="tenantHistoryHead"> <!-- Used generic .pageHeader -->
                <h4>Add New Tenant from Inquiry</h4>
            </div>

            <?php if ($page_error): ?>
                <p class="message error"><?php echo $page_error; ?></p>
            <?php elseif ($inquiry_data && $unit_details): ?>
                <form method="POST" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?email=' . urlencode($inquiry_data['email']) . '&inquiry_datetime=' . urlencode($inquiry_data['inquiry_date_time']); ?>">
                    <div class="tenantInfoContainer">
                        <?php if ($form_submission_error): ?><p class="message error"><?php echo $form_submission_error; ?></p><?php endif; ?>
                        <?php if ($form_submission_success): ?><p class="message success"><?php echo $form_submission_success; ?></p><?php endif; ?>

                        <img src="tenants_images/default_avatar.png"  alt="Tenant Image" id="tenantImagePreview" class="tenantImageContainer">
                        <div class="uploadBtnContainer">
                            <button type="button" class="customUploadBtn" onclick="document.getElementById('imageInput').click();">Upload Photo</button>
                            <input type="file" accept="image/*" name="tenant_image" id="imageInput" class="inputImage" onchange="previewImage(event)">
                        </div>

                        <div class="tenantsInformation"> 
                            <div class="tenantInfoInput1">
                                <div class="formContainer">
                                    <div class="form-group">
                                        <label for="tenant_name">Tenant Name:</label>
                                        <input type="text" maxlength="50" name="tenant_name" id="tenant_name" value="<?php echo htmlspecialchars($inquiry_data['full_name']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="contact_number">Contact No.:</label>
                                        <input type="text" name="contact_number" id="contact_number" value="<?php echo htmlspecialchars($inquiry_data['contact_no']); ?>"
                                                maxlength="13" pattern="\+639\d{9}" title="Format: +639xxxxxxxxx" oninput="autoPrefix(this)" required>
                                    </div>
                                     <div class="form-group">
                                        <label for="email">Email:</label>
                                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($inquiry_data['email']); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="unit_no">Unit No.:</label>
                                        <input type="text" name="unit_no_display" id="unit_no_display" value="<?php echo htmlspecialchars($inquiry_data['unit_no']); ?>" readonly>
                                        <!-- Hidden input to pass actual unit_no for submission -->
                                        <input type="hidden" name="unit_no" value="<?php echo htmlspecialchars($inquiry_data['unit_no']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="emergency_contact_name">Emergency Contact:</label>
                                        <input type="text" name="emergency_contact_name" id="emergency_contact_name" placeholder="Name of contact person" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="emergency_contact_num">Emergency Contact No.:</label>
                                        <input type="text" name="emergency_contact_num" id="emergency_contact_num" placeholder="+639xxxxxxxxx"
                                                maxlength="13" pattern="\+639\d{9}" title="Format: +639xxxxxxxxx" oninput="autoPrefix(this)" required>
                                    </div>
                                </div>
                            </div>

                            <div class="tenantInfoInput2">
                                <div class="formContainer">
                                    <div class="form-group">
                                        <label for="occupant_count">Actual Occupant Count:</label>
                                        <input type="number" name="occupant_count" id="occupant_count" min="1" value="1" max="<?php echo htmlspecialchars($unit_details['occupant_capacity']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="start_date">Start Date:</label>
                                        <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($inquiry_data['start_date']); ?>" required onchange="updatePaymentDueDate()">
                                    </div>
                                    <div class="form-group">
                                        <label for="end_date">End Date:</label>
                                        <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($inquiry_data['end_date']); ?>" required>
                                    </div>
                                     <div class="form-group">
                                        <label for="rent_payment_due">Payment Due Text:</label>
                                        <input type="text" name="rent_payment_due" id="rent_payment_due" value="<?php echo htmlspecialchars($inquiry_data['payment_due_date']); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="monthly_rent_amount">Monthly Rent (₱):</label>
                                        <input type="text" name="monthly_rent_amount_display" id="monthly_rent_amount_display" value="<?php echo number_format($unit_details['monthly_rent_amount'], 2); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="deposit_paid">Deposit Paid (₱):</label>
                                        <input type="number" name="deposit_paid" id="deposit_paid" step="0.01" min="0" placeholder="Amount of deposit collected" required>
                                    </div>
                                     <div class="form-group">
                                        <label for="billing_period">Billing Period:</label>
                                        <input type="text" name="billing_period" id="billing_period" value="Monthly" required>
                                    </div>
                                    
                                    <div class="buttonContainer">
                                        <button type="submit" name="add_tenant" class="form-action-btn">Add Tenant</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                 <!-- Show page error if $inquiry_data or $unit_details is not set, but only if not already handled by $page_error above -->
                <?php if (empty($page_error)): ?>
                    <p class="message error">Could not load inquiry or unit details to proceed.</p>
                <?php endif; ?>
            <?php endif; ?>

            <div class="footbtnContainer">
                <a href="PENDINGINQUIRY.php" class="backbtn">⤾ Back</a>
            </div>
        </div>
    </div>
    <script>
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function(){
                const output = document.getElementById('tenantImagePreview');
                output.src = reader.result;
            };
            if (event.target.files[0]) {
                reader.readAsDataURL(event.target.files[0]);
            } else {
                // If no file is selected (e.g., user cancels), reset to default or keep current
                document.getElementById('tenantImagePreview').src = 'tenants_images/default_avatar.png';
            }
        }

        function autoPrefix(input) {
            if (!input.value.startsWith('+639') && input.value.length > 0) {
                if (input.value.startsWith('09')) {
                    input.value = '+639' + input.value.substring(2);
                } else if (input.value.startsWith('9')) {
                     input.value = '+639' + input.value.substring(1);
                } else {
                    input.value = '+639';
                }
            }
            // Limit length after prefixing
            if (input.value.length > 13) {
                input.value = input.value.substring(0, 13);
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

        function updatePaymentDueDate() {
            const startDateInput = document.getElementById('start_date');
            const paymentDueInput = document.getElementById('rent_payment_due');
            if (startDateInput.value) {
                const date = new Date(startDateInput.value + "T00:00:00"); // Ensure local time interpretation
                if (!isNaN(date)) {
                    const day = date.getDate();
                    const suffix = getDaySuffix(day);
                    paymentDueInput.value = `Every ${day}${suffix} day of the month`;
                } else {
                    paymentDueInput.value = '';
                }
            } else {
                paymentDueInput.value = '';
            }
        }
        // Initialize payment due date if start_date is pre-filled
        document.addEventListener('DOMContentLoaded', function() {
            updatePaymentDueDate();
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