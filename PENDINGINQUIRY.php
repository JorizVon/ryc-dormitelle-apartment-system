<?php
session_start(); // Start session at the very beginning

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    // Assuming LOGIN.php is in the same directory or adjust path if it's in parent (../LOGIN.php)
    header("Location: LOGIN.php");
    exit();
}

// Assuming db_connect.php is in the same directory or adjust path if it's in parent (../db_connect.php)
require_once 'db_connect.php';

date_default_timezone_set('Asia/Manila'); // Set timezone for date formatting

// Initialize $result to null and $query_error
$result = null;
$query_error = "";

// SQL query for PENDING INQUIRIES
$sql = "SELECT `inquiry_date_time`, `unit_no`, `full_name`, `contact_no`, 
               `email`, `pref_move_date`, `start_date`, `end_date`, `payment_due_date` 
        FROM `pending_inquiry`
        ORDER BY `inquiry_date_time` DESC"; // Show newest inquiries first

$query_exec_result = $conn->query($sql);

if ($query_exec_result === false) {
    $query_error = "Error fetching pending inquiries: " . $conn->error;
    error_log($query_error); // Log the error for debugging
} else {
    $result = $query_exec_result; // Assign the mysqli_result object
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
    <title>Pending Inquiry</title> <!-- Changed Title -->
    <style>
        /* PASTE THE EXACT CSS FROM YOUR TENANTSLIST.PHP HERE */
        /* I will use the CSS from the previous TENANTSLIST.php example you provided */
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
            height: 100%;
            width: 100%;
            background-color: #004AAD;
            color: white;
        }
        .card a:hover {
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
        .headerContent a.logOutbtn:hover {
            color: #004AAD;
        }
        .mainContent {
            height: 100%;
            width: 100%;
            margin: 0px auto;
            background-color: #FFFF;
        }
        /* Using .pageHeader instead of .pendingInquiryHead or .tenantHistoryHead for general use */
        .pageHeader {
            display: flex;
            justify-content: space-between;
            width: 100%;
            align-items: center;
        }
        .pageHeader h4 {
            color: #01214B;
            font-size: 32px;
            margin-left: 60px;
            height: 20px;
            align-items: center;
        }
        .searbar {
            height: 20px;
            width: 270px;
            margin-right: 55px;
            border-style: solid;
            font-size: 12px;
            position: relative;
            top: 14px;
        }
        ::placeholder {
            color: #B7B5B5;
            opacity: 1;
        }
        .table-container { /* This style is from TENANTSLIST.PHP */
            max-width: 90%;
            margin: 0 auto;
            border: 3px solid #A6DDFF;
            border-radius: 8px;
            height: 470px; /* Fixed height for scrolling */
            overflow: hidden; /* To make inner scroll work */
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .table-scroll {
            height: 100%; /* Fill the container */
            overflow-y: auto;
            overflow-x: auto; 
            scrollbar-width: none; 
        }
        .table-scroll::-webkit-scrollbar {
            display: none; 
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }
        th, td {
            padding: 12px 15px; /* Original from TENANTSLIST */
            text-align: left;
            font-size: 14px; /* Original from TENANTSLIST */
            border-bottom: 1px solid #e0e0e0;
            white-space: nowrap;
        }
        th {
            background-color: #e3f2fd;
            font-weight: bold;
            position: sticky; 
            top: 0;
            z-index: 1;
            font-size: 12px; /* Original from TENANTSLIST */
        }
        .action-btn { /* Style for View Details button */
            background-color: #2196f3;
            color: white;
            border: none;
            padding: 8px 14px; /* Original from TENANTSLIST */
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px; /* Original from TENANTSLIST */
        }
        .action-btn:hover {
            background-color: #1976d2;
        }
        .footbtnContainer {
            display: flex;
            justify-content: space-between;
            width: 70%;
            align-items: center;
            position: fixed;
            top: 685px;
            right: 58px;
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
        .footbtnContainer a.backbtn:hover {
            background-color: #FFFFFF;
            color: #004AAD;
            border: 2px solid #004AAD;
        }
        .hamburger {
            display: none; /* Original: visibility: hidden; width: 0px; */
        }
        /* Mobile and Tablet Responsive - Copied from TENANTSLIST */
        @media (max-width: 1024px) {
            .sideBar {
                position: fixed; left: -100%; top: 0; height: 100vh;
                z-index: 1000; transition: 0.3s ease;
            }
            .sideBar.active { left: 0; }
            .hamburger {
                display: block; position: fixed; top: 25px; left: 20px; z-index: 1100;
                font-size: 30px; cursor: pointer; color: #004AAD; width: auto;
                background-color: white; padding: 5px 10px; border-radius: 3px;
            }
            .mainBody { width: 100%; margin-left: 0 !important; }
            .header { justify-content: flex-end; }
            .mainContent { width: 100%; margin: 0 auto; padding: 15px; box-sizing: border-box; }
            .pageHeader { flex-direction: column; align-items: stretch; text-align: center; width: 100%; }
            .pageHeader h4 { margin: 15px 0 10px 0; font-size: 28px; margin-left: 0; }
            .searbar { width: 100%; margin: 10px auto; }
            .table-container { width: 100%; max-width: 100%; border-left: none; border-right: none; border-radius: 0; max-height: calc(100vh - 250px); } /* Adjusted max-height */
            table th, table td { font-size: 11px; padding: 8px 5px; }
            .action-btn { font-size: 10px; padding: 6px 8px; }
            .footbtnContainer { flex-direction: column; align-items: center; gap: 15px; margin: 20px auto; width: 100%; }
            .backbtn { width: 80%; max-width: 250px; }
        }

        @media (max-width: 480px) {
            .headerContent { margin-right: 20px; }
            .headerContent a, .adminLogoutspace { font-size: 14px; }
            .hamburger { font-size: 28px; }
            .sideBar{ width: 220px; }
            .systemTitle { position: relative; top: 15px; padding: 11px; }
            .systemTitle h1 { font-size: 14px; position: relative; margin-bottom: 18px; }
            .systemTitle p { font-size: 10px; }
            .card a { font-size: 14px; padding-left: 15px; }
            .card img { height: 18px; width: 18px; margin-right: 8px; }
            table th, table td { font-size: 10px; }
            .pageHeader h4 { font-size: 24px; }
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
                <a href="UNITSINFORMATION.php">
                    <img src="sidebarIcons/UnitsInfoIconWht.png" alt="Units Information Icon" class="UIsidebarIcon" style="margin-right: 5px;">
                    Units Information</a>
            </div>
            <div class="card">
                <a href="TENANTSLIST.php"> <!-- Kept original link for Tenants List -->
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
                <a href="CARDREGISTRATION.php">
                    <img src="sidebarIcons/CardregisterIconWht.png" alt="Card Registration Icon" class="CGsidebarIcon" style="margin-right: 10px;">
                    Card Registration</a>
            </div>
            <div class="card">
                <a href="PENDINGINQUIRY.php" style="background-color: #FFFF; color: #004AAD;"> <!-- Active style for this page -->
                    <img src="sidebarIcons/PendingInquiryIcon.png" alt="Pending Inquiry Icon" class="PIsidebarIcon" style="margin-right: 10px;">
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
        <div class="pageHeader"> <!-- Using a more generic class name for the header div -->
            <h4>Pending Inquiry</h4> <!-- Changed Title -->
            <input type="text" id="searchInput" placeholder="Search Name, Unit, Date..." class="searbar" oninput="searchTable()"> <!-- Re-linked client search -->
        </div>
        <div class="table-container">
            <div class="table-scroll">
                <table id="pendingInquiryTable"> <!-- Unique ID for this table -->
                    <thead>
                        <tr>
                            <th>Inquiry Date & Time</th>
                            <th>Unit No</th>
                            <th>Full Name</th>
                            <th>Contact No</th>
                            <th>Email</th>
                            <th>Pref. Move-In</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <!-- <th>Payment Due</th> Omitting to save space, can be re-added -->
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (!empty($query_error)) {
                            echo "<tr><td colspan='9' style='color:red; text-align:center;'>" . htmlspecialchars($query_error) . "</td></tr>"; // Colspan matches headers
                        } elseif ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(date("M d, Y h:i A", strtotime($row['inquiry_date_time']))); ?></td>
                                    <td><?php echo htmlspecialchars($row['unit_no']); ?></td>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['contact_no']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars(date("M d, Y", strtotime($row['pref_move_date']))); ?></td>
                                    <td><?php echo htmlspecialchars(date("M d, Y", strtotime($row['start_date']))); ?></td>
                                    <td><?php echo htmlspecialchars(date("M d, Y", strtotime($row['end_date']))); ?></td>
                                    <!-- <td><?php // echo htmlspecialchars($row['payment_due_date']); ?></td> -->
                                    <td>
                                        <?php
                                            $details_params = "email=" . urlencode($row["email"]) . "&inquiry_datetime=" . urlencode($row["inquiry_date_time"]);
                                            // If you add a unique inquiry_id like 'pending_inquiry_id' to the table, use that:
                                            // if (isset($row['pending_inquiry_id'])) {
                                            //     $details_params = "inquiry_id=" . urlencode($row["pending_inquiry_id"]);
                                            // }
                                        ?>
                                        <a href="TENANTPROFILECREATION.php?<?php echo $details_params; ?>" class="action-btn">View Details</a>
                                    </td>
                                </tr>
                        <?php
                            } // End while
                        } else { // End if $result->num_rows > 0
                            echo "<tr><td colspan='9' style='text-align:center;'>No pending inquiries found.</td></tr>"; // Colspan matches headers
                        } // End else
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div> <!-- This was an extra closing div in TENANTSLIST that is removed here -->
            <div class="footbtnContainer">
                <a href="DASHBOARD.php" class="backbtn">⤾ Back</a>
                <!-- Removed "Add New Tenant" button as it's not for inquiries page -->
            </div>
        </div> <!-- .mainContent closing div -->
    </div> <!-- .mainBody closing div -->

    <script>
    function searchTable() { // Renamed generic search function
        const input = document.getElementById("searchInput").value.toLowerCase().trim();
        const table = document.getElementById("pendingInquiryTable"); // Using specific table ID
        const tr = table.getElementsByTagName("tr");
        let found = false;

        for (let i = 1; i < tr.length; i++) { // Start from 1 to skip header
            const row = tr[i];
            if (row.cells.length > 1 && row.cells[0].colSpan !== 9) { 
                const inquiryDate = row.cells[0].textContent.toLowerCase();
                const unitNo = row.cells[1].textContent.toLowerCase();
                const fullName = row.cells[2].textContent.toLowerCase();
                const contactNo = row.cells[3].textContent.toLowerCase();
                const email = row.cells[4].textContent.toLowerCase();
                const prefMoveIn = row.cells[5].textContent.toLowerCase();
                // Add other cells to search if needed
                let rowVisible = false;

                if (inquiryDate.includes(input)) rowVisible = true;
                if (unitNo.includes(input)) rowVisible = true;
                if (fullName.includes(input)) rowVisible = true;
                if (contactNo.includes(input)) rowVisible = true;
                if (email.includes(input)) rowVisible = true;
                if (prefMoveIn.includes(input)) rowVisible = true;
                
                row.style.display = rowVisible ? "" : "none";
                if (rowVisible) found = true;
            }
        }
        
        const noRecordsRow = table.querySelector('td[colspan="9"]');
        if (noRecordsRow) {
            const dataRowsPresent = Array.from(tr).slice(1).some(r => r.cells.length > 1 && r.cells[0].colSpan !== 9);
            if (!found && input !== "" && dataRowsPresent) {
                noRecordsRow.textContent = "No matching inquiries found for your search.";
                noRecordsRow.parentNode.style.display = ""; 
            } else if (input === "" && !dataRowsPresent) {
                noRecordsRow.textContent = "No pending inquiries found.";
                noRecordsRow.parentNode.style.display = "";
            } else if (input === "" && dataRowsPresent) {
                noRecordsRow.parentNode.style.display = "none";
            } else if (!dataRowsPresent && input === ""){
                noRecordsRow.textContent = "No pending inquiries found.";
                noRecordsRow.parentNode.style.display = "";
            } else if (!found && !dataRowsPresent && input !== ""){
                noRecordsRow.textContent = "No matching inquiries found for your search.";
                noRecordsRow.parentNode.style.display = "";
            } else {
                noRecordsRow.parentNode.style.display = "none";
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        if(document.getElementById("pendingInquiryTable")) { // Check for specific table ID
            searchTable(); // Call with empty input to correctly show/hide "No records"
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
if (isset($conn)) { // Close connection if it was opened
    $conn->close();
}
?>