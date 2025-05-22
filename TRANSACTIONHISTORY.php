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

// Original SQL query structure for fetching all confirmed transactions
// The client-side search will handle filtering the displayed data.
// Changed payment_date to payment_date_time
$sql = "SELECT payments.transaction_no, tenants.tenant_name, tenant_unit.unit_no, tenant_unit.deposit, 
               payments.amount_paid, payments.payment_date_time, tenant_unit.billing_period, 
               tenant_unit.balance AS current_tenant_unit_balance, /* Fetched actual balance */
               payments.payment_method, payments.payment_status
        FROM payments
        INNER JOIN tenants ON tenants.tenant_ID = payments.tenant_ID
        INNER JOIN tenant_unit ON tenant_unit.tenant_ID = tenants.tenant_ID
        WHERE payments.confirmation_status = 'confirmed'
        ORDER BY payments.payment_date_time DESC";

$query_exec_result = $conn->query($sql);

if ($query_exec_result === false) {
    $query_error = "Error fetching transaction history: " . $conn->error;
    error_log($query_error); // Log the error for debugging
} else {
    $result = $query_exec_result; // Assign the mysqli_result object
}

// Get admin's display name (optional, for header)
$adminDisplayIdentifier = "ADMIN"; // Default
if (isset($_SESSION['email_account'])) {
    // Example: $adminDisplayIdentifier = htmlspecialchars(strtok($_SESSION['email_account'], '@'));
    // You might want to fetch the admin's actual name/username from an 'accounts' table
    // based on $_SESSION['email_account'] for a more personalized header.
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
    <title>Transaction History</title>
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
            /* color: #01214B; */ /* Covered by color: white below */
            height: 100%;
            width: 100%;
            background-color: #004AAD;
            color: white;
        }
        .card a:hover {
            /* background-color: 004AAD; */ /* Invalid CSS value */
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
            width: 100vw; /* This can cause horizontal scroll if sidebar is fixed and takes width */
            /* Consider: width: calc(100vw - 450px); margin-left: 450px; if sidebar has fixed width */
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
        .headerContent a.logOutbtn:hover { /* Made selector more specific */
            color: #004AAD;
        }
        .mainContent {
            height: calc(100% - 13vh); /* Adjust based on header height */
            width: 100%;
            margin: 0px auto;
            background-color: #FFFF;
            padding-top: 20px; /* Added padding */
            overflow-y: auto; /* Allow content to scroll if it exceeds viewport height */
        }
        .transactionhistoryHead {
            display: flex;
            justify-content: space-between;
            width: calc(90% - 40px); /* Align with table container, adjust if needed */
            align-items: center;
            margin: 0 auto 15px auto; /* Centered and margin bottom */
        }
        .transactionhistoryHead h4 {
            color: #01214B;
            font-size: 32px;
            /* margin-left: 60px; */ /* Removed, use auto margin of container */
            /* height: 20px; */ /* Not needed */
            /* align-items: center; */ /* Not applicable */
            margin-top: 0;
            margin-bottom: 0;
        }
        .searbar { /* Original styling for client-side search */
            height: 20px;
            width: 270px;
            border-style: solid; /* Consider '1px solid #ccc;' for a more standard look */
            font-size: 12px;
            padding: 5px 8px; /* Added padding for text inside */
            box-sizing: border-box; /* Include padding and border in element's total width and height */
        }
        ::placeholder {
            color: #B7B5B5;
            opacity: 1;
        }
        .table_container {
            max-width: 90%;
            margin: 0 auto;
            border: 3px solid #A6DDFF;
            border-radius: 8px;
            height: 470px; */ /* Let content define height or use max-height for scrolling */
            max-height: 470px; /* If you want the table to scroll within this height */
            overflow: hidden; /* Important for the inner .table-scroll to work */
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .table-scroll {
            /* max-height: 500px; */ /* Redundant if .table_container has max-height */
            height: 100%; /* Fill the .table_container */
            overflow-y: auto;
            overflow-x: auto; /* Keep for potentially wide tables */
            scrollbar-width: none; /* For Firefox */
        }
        .table-scroll::-webkit-scrollbar {
            display: none; /* For Chrome, Safari, Opera */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            /* border-color: #A6DDFF; */ /* Border is on .table_container */
            background-color: white;
        }
        th, td {
            padding: 10px 12px; /* Adjusted padding */
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px; /* Consistent font size */
            white-space: nowrap; /* Prevent text from wrapping, makes horizontal scroll work */
        }
        th {
            background-color: #e3f2fd;
            font-weight: bold;
            position: sticky; /* Makes header sticky during vertical scroll */
            top: 0;
            z-index: 1;
            font-size: 12px; /* Slightly smaller for table headers */
        }
        /* .action-btn is not used on this page according to original HTML */

        .footbtnContainer {
            width: 90%;
            /* height: 20px; */ /* Let content define height */
            margin: 20px auto 0 auto; /* Consistent margin */
            display: flex;
            /* position: relative; */ /* Removed */
            /* top: 38px; */ /* Use margin for spacing */
            justify-content: space-between;
            align-items: center;
        }
        .backbtn {
            height: 36px;
            width: 110px;
            /* position: relative; */
            display: flex;
            align-items: center;
            justify-content: center;
            /* bottom: 22px; */
            background-color: #004AAD;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px; /* Added for consistency */
        }
        /* Original had 'button' selector, making it specific to the 'Print Report' button */
        button.addtenantbtn {
            background-color: #004AAD;
            color: white;
            border: none;
            border-radius: 5px;
            height: 36px; /* Match back button */
            padding: 0 15px; /* Padding for text and icon */
            display: flex; /* For icon and text alignment */
            align-items: center;
            justify-content: center;
            font-size: 14px; /* Match back button */
            cursor: pointer;
            /* width: 255px; */ /* Let content define width or set min-width */
            min-width: 150px; /* Example min-width */
        }
        button.addtenantbtn:hover {
            background-color: #FFFFFF;
            color: #004AAD;
            border: 2px solid #004AAD;
        }
        .footbtnContainer a.backbtn:hover { /* Target specific link */
            background-color: #FFFFFF;
            color: #004AAD;
            border: 2px solid #004AAD;
            /* border-radius: 5px; */ /* Already defined */
        }

        .printTransactionHistory {
            height: 18px; /* Adjusted icon size */
            width: 18px;  /* Adjusted icon size */
            margin-right: 8px; /* Space between icon and text */
        }
        .footbtnContainer button.addtenantbtn:hover .printTransactionHistory {
            content: url('otherIcons/printIconblue.png');
        }
        .hamburger {
            display: none; /* Original: visibility: hidden; width: 0px; */
        }
        /* Mobile and Tablet Responsive */
        @media (max-width: 1024px) {
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
                position: fixed; /* Changed from absolute */
                top: 25px;
                left: 20px;
                z-index: 1100; /* Ensure it's above sidebar */
                font-size: 30px;
                cursor: pointer;
                color: #004AAD; /* Make it visible */
                /* visibility: visible; */ /* Not needed if display:block */
                width: auto; /* Let content define width */
                background-color: white; /* Added for visibility */
                padding: 5px 10px;
                border-radius: 3px;
            }

            .mainBody {
                width: 100%;
                margin-left: 0 !important; /* Full width when sidebar hidden/overlay */
            }

            .header {
                justify-content: flex-end; /* Original: justify-content: right; */
            }

            .mainContent {
                width: 100%;
                margin: 0 auto;
                padding: 15px; /* Add some padding for mobile */
                box-sizing: border-box; /* Include padding in width calculation */
            }

            .transactionhistoryHead {
                flex-direction: column;
                align-items: stretch; /* Stretch items to full width */
                text-align: center;
                width: 100%; /* Full width for mobile */
            }

            .transactionhistoryHead h4 {
                margin: 15px 0 10px 0; /* Adjusted margins */
                font-size: 28px; /* Slightly smaller */
                margin-left: 0; /* Remove left margin */
            }

            .searbar {
                width: 100%; /* Full width search bar */
                margin: 10px auto; /* Centered */
            }

            .table_container {
                width: 100%; /* Full width table container */
                max-width: 100%; /* Override desktop max-width */
                border-left: none; /* Remove side borders for full bleed */
                border-right: none;
                border-radius: 0; /* No radius for full bleed */
                max-height: calc(100vh - 280px); /* Example dynamic height, adjust as needed */
            }

            table th, table td {
                font-size: 11px; /* Smaller font for mobile */
                padding: 8px 5px; /* Reduced padding */
            }

            .footbtnContainer {
                flex-direction: column;
                align-items: center;
                gap: 15px;
                /* top: 10px; */ /* Position with margin */
                margin: 20px auto; /* Adjusted */
                width: 100%;
            }
            button.addtenantbtn { /* Print button */
                font-size: 14px; /* Adjusted */
                width: 80%;
                max-width: 250px;
            }
            .backbtn {
                /* visibility: hidden; */ /* Or display: none; */
                width: 80%;
                max-width: 250px;
            }
        }

        @media (max-width: 480px) {
            .headerContent { margin-right: 20px; }
            .headerContent a, .adminLogoutspace {
                font-size: 14px;
            }
            .hamburger {
                font-size: 28px;
            }
            .sideBar{
                width: 220px; /* Adjusted from 53vw */
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
                padding-left: 15px;
            }
            .card img {
                height: 18px; /* Adjust icon size in cards */
                width: 18px;
                margin-right: 8px;
            }
            /* .table-scroll {
                width: 600px; 
            } */ /* This forces horizontal scroll, better to let table be responsive if possible */
            table th, table td {
                font-size: 10px; /* Further adjust for very small screens */
            }
            .transactionhistoryHead h4 { font-size: 24px; }
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
                <a href="TENANTSLIST.php">
                    <img src="sidebarIcons/TenantsInfoIconWht.png" alt="Tenants Information Icon" class="THsidebarIcon" style="margin-right: 3px;">
                    Tenants List</a>
            </div>
            <div class="card">
                <a href="PAYMENTMANAGEMENT.php" style="background-color: #FFFF; color: #004AAD;">
                    <img src="sidebarIcons/PaymentManagementIcon.png" alt="Payment Management Icon" class="PMsidebarIcon" style="margin-right: 10px;">
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
                    <a href="ADMINPROFILE.php" class="adminTitle"><?php echo htmlspecialchars($adminDisplayIdentifier); ?></a>
                    <p class="adminLogoutspace"> | </p>
                    <a href="LOGIN.php" class="logOutbtn">Log Out</a> <!-- Or your LOGOUT.php -->
                </div>
            </div>
            <div class="mainContent">
                <div class="transactionhistoryHead">
                    <h4>Transaction History</h4>
                    <input type="text" id="searchInput" placeholder="Search Name, Date, Method, Status..." class="searbar" oninput="searchTransactionHistory()">
                </div>
                <div class="table_container">
                    <div class="table-scroll">
                        <table id="transactionTable">
                            <thead>
                                <tr>
                                    <th>Transaction No.</th>
                                    <th>Unit No.</th>
                                    <th>Name</th>
                                    <th>Current Deposit (₱)</th>
                                    <th>Amount (₱)</th>
                                    <th>Payment Date & Time</th> <!-- Changed -->
                                    <th>Billing Period</th>
                                    <th>Balance (₱)</th>
                                    <th>Payment Method</th>
                                    <th>Payment Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (!empty($query_error)) {
                                    echo "<tr><td colspan='10' style='color:red; text-align:center;'>" . htmlspecialchars($query_error) . "</td></tr>";
                                } elseif ($result && $result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                        // Use the fetched balance from tenant_unit table
                                        $balance_display = $row['current_tenant_unit_balance'];
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['transaction_no']); ?></td>
                                            <td><?php echo htmlspecialchars($row['unit_no']); ?></td>
                                            <td><?php echo htmlspecialchars($row['tenant_name']); ?></td>
                                            <td><?php echo number_format((float)$row['deposit'], 2); ?></td>
                                            <td><?php echo number_format((float)$row['amount_paid'], 2); ?></td>
                                            <td><?php echo htmlspecialchars(date("M d, Y h:i A", strtotime($row['payment_date_time']))); ?></td> <!-- Formatted -->
                                            <td><?php echo htmlspecialchars($row['billing_period']); ?></td>
                                            <td><?php echo number_format((float)$balance_display, 2); ?></td>
                                            <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                                            <td><?php echo htmlspecialchars($row['payment_status']); ?></td>
                                        </tr>
                                <?php
                                    } // End while
                                } else { // End if $result->num_rows > 0
                                    echo "<tr><td colspan='10' style='text-align:center;'>No transaction records found.</td></tr>";
                                } // End else
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                  <div class="footbtnContainer">
                    <a href="PAYMENTMANAGEMENT.php" class="backbtn">⤾ Back</a>
                    <button class="addtenantbtn" onclick="generateTransactionPDF()"> <!-- Changed <a> to <button> for consistency -->
                        <img src="otherIcons/printIcon.png" alt="Print Icon" class="printTransactionHistory">
                        Print Report
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
        function searchTransactionHistory() {
            const input = document.getElementById("searchInput").value.toLowerCase().trim();
            const table = document.getElementById("transactionTable");
            const tr = table.getElementsByTagName("tr");
            let found = false;

            // Start loop from 1 to skip table header (thead > tr)
            for (let i = 1; i < tr.length; i++) {
                const row = tr[i];
                // Ensure it's a data row, not the "no records" row
                if (row.cells.length > 1 && row.cells[0].colSpan !== 10) {
                    const tdTxNo = row.cells[0];       // Transaction No.
                    const tdUnitNo = row.cells[1];     // Unit No.
                    const tdName = row.cells[2];       // Name
                    const tdDate = row.cells[5];       // Payment Date & Time
                    const tdMethod = row.cells[8];     // Payment Method
                    const tdStatus = row.cells[9];     // Payment Status
                    let rowVisible = false;

                    if (tdTxNo && tdTxNo.textContent.toLowerCase().includes(input)) rowVisible = true;
                    if (tdUnitNo && tdUnitNo.textContent.toLowerCase().includes(input)) rowVisible = true;
                    if (tdName && tdName.textContent.toLowerCase().includes(input)) rowVisible = true;
                    if (tdDate && tdDate.textContent.toLowerCase().includes(input)) rowVisible = true;
                    if (tdMethod && tdMethod.textContent.toLowerCase().includes(input)) rowVisible = true;
                    if (tdStatus && tdStatus.textContent.toLowerCase().includes(input)) rowVisible = true;
                    
                    row.style.display = rowVisible ? "" : "none";
                    if (rowVisible) found = true;
                }
            }
             // Handle "No records found" message visibility
            const noRecordsRow = table.querySelector('td[colspan="10"]');
            if (noRecordsRow) { // Check if the "no records" row exists
                const dataRowsPresent = Array.from(tr).slice(1).some(r => r.cells.length > 1 && r.cells[0].colSpan !== 10);

                if (!found && input !== "" && dataRowsPresent) { // If search term and no results from actual data
                    noRecordsRow.textContent = "No matching records found for your search.";
                    noRecordsRow.parentNode.style.display = ""; 
                } else if (input === "" && !dataRowsPresent) { // If search empty and no data originally
                    noRecordsRow.textContent = "No transaction records found.";
                    noRecordsRow.parentNode.style.display = "";
                } else if (input === "" && dataRowsPresent) { // If search empty and there was data
                     noRecordsRow.parentNode.style.display = "none";
                } else if (!dataRowsPresent && input === ""){ // If no data and no search term
                     noRecordsRow.textContent = "No transaction records found.";
                     noRecordsRow.parentNode.style.display = "";
                } else if (!found && !dataRowsPresent && input !== ""){ // no data and search term
                    noRecordsRow.textContent = "No matching records found for your search.";
                    noRecordsRow.parentNode.style.display = "";
                }
                 else { // If found
                    noRecordsRow.parentNode.style.display = "none";
                }
            }
        }

        // Initial call to set up "No records found" message correctly if table is empty on load
        document.addEventListener('DOMContentLoaded', function() {
            if(document.getElementById("transactionTable")) {
                searchTransactionHistory(); // Call with empty input to correctly show/hide "No records" initially
            }
        });
    </script>
    <script> // PDF generation and toggleSidebar
        function generateTransactionPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({ orientation: 'landscape' });

            // ----- PDF Header -----
            doc.setFontSize(18); 
            doc.setTextColor(0, 0, 255); 
            doc.text('C A T I I S', doc.internal.pageSize.getWidth() / 2, 18, { align: 'center' });

            doc.setFontSize(10);
            doc.setTextColor(0, 0, 0); 
            doc.text('APARTMENT MANAGEMENT SYSTEM', doc.internal.pageSize.getWidth() / 2, 25, { align: 'center' });
            doc.text('Pasig Daet, Camarines Norte', doc.internal.pageSize.getWidth() / 2, 30, { align: 'center' });
            doc.text('Contact No.: +6398561002586 / 6398561002586', doc.internal.pageSize.getWidth() / 2, 35, { align: 'center' });

            doc.setLineWidth(0.3); 
            doc.line(10, 40, doc.internal.pageSize.getWidth() - 10, 40); 

            doc.setFontSize(14);
            const clientSearchInput = document.getElementById("searchInput");
            const clientSearchTerm = clientSearchInput ? clientSearchInput.value.trim() : "";
            let reportTitle = 'TRANSACTION REPORT';
            let startYPosition = 50; 

            if (clientSearchTerm !== "") {
                reportTitle = 'Filtered TRANSACTION REPORT';
                doc.setFontSize(9); 
                doc.text("Search Term: " + clientSearchTerm, doc.internal.pageSize.getWidth() / 2, 50, {align: 'center'});
                startYPosition = 55; 
            }
            doc.setFontSize(14); 
            doc.text(reportTitle, doc.internal.pageSize.getWidth() / 2, startYPosition - (clientSearchTerm !== "" ? 0 : 5) , { align: 'center' });
            
            startYPosition += 7; 


            // ----- Collect Table Data -----
            const table = document.getElementById("transactionTable");
            const bodyRows = [];
            const pdfHead = [ // 8 Columns for PDF
                'Txn No.', 'Unit', 'Tenant Name', 'Amount', 'Payment Date & Time', 'Balance', 'Method', 'Status'
            ];
            const htmlCellIndicesForPdf = [0, 1, 2, 4, 5, 7, 8, 9]; 

            for (let i = 1; i < table.rows.length; i++) {
                const row = table.rows[i];
                if (row.style.display !== "none" && row.cells.length >= Math.max(...htmlCellIndicesForPdf) + 1) { 
                    const rowData = [];
                    htmlCellIndicesForPdf.forEach(cellIndex => {
                        rowData.push(row.cells[cellIndex].innerText.trim());
                    });
                    bodyRows.push(rowData);
                }
            }

            if (bodyRows.length === 0) {
                alert("No data to export in the current view.");
                return;
            }

            // ----- Add Table to PDF -----
            doc.autoTable({
                startY: startYPosition,
                head: [pdfHead], 
                body: bodyRows,
                theme: 'plain', // CHANGED THEME TO 'plain' - removes most lines
                styles: { 
                    fontSize: 8, // Slightly increased font size for content
                    cellPadding: {top: 2.5, right: 2, bottom: 2.5, left: 2}, // Adjusted padding
                    valign: 'middle',
                    lineColor: [44, 62, 80], // Dark gray for any lines if theme adds them (plain usually doesn't)
                    lineWidth: 0.1,          // Thin lines if any
                },
                headStyles: { 
                    fillColor: [0, 74, 173], // Kept dark blue header background
                    textColor: 255, 
                    fontSize: 8.5,        // Slightly increased header font
                    fontStyle: 'bold',
                    halign: 'center',
                    lineColor: [0, 74, 173], // Match header fill for border
                    lineWidth: 0.1
                },
                // Alternating row styles for better readability without grid lines
                alternateRowStyles: {
                    fillColor: [245, 245, 245] // Light gray for alternate rows
                },
                columnStyles: { // Adjusted for 8 columns - TUNE THESE CAREFULLY
                    0: { cellWidth: 25, halign: 'left' },    // Txn No. (more space)
                    1: { cellWidth: 18, halign: 'center' },   // Unit
                    2: { cellWidth: 50, overflow: 'ellipsize', cellWidth: 'auto'}, // Tenant Name (auto with ellipsize)
                    3: { cellWidth: 25, halign: 'center' },  // Amount (₱)
                    4: { cellWidth: 38 },  // Payment Date & Time (more space)
                    5: { cellWidth: 25, halign: 'center' },  // Balance (₱)
                    6: { cellWidth: 30, halign: 'center' },  // Method
                    7: { cellWidth: 30, halign: 'center' }  // Status
                },
                margin: { top: 10, right: 7, bottom: 15, left: 7 },
                didDrawPage: function (data) {
                    let pageNumberText = 'Page ' + doc.internal.getNumberOfPages();
                    doc.setFontSize(8);
                    doc.setTextColor(100);
                    doc.text(pageNumberText, data.settings.margin.left, doc.internal.pageSize.height - 7);
                    
                    const generationDate = `Generated: ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}`;
                    doc.text(generationDate, doc.internal.pageSize.getWidth() - data.settings.margin.right, doc.internal.pageSize.height - 7, { align: 'right'});
                }
            });

            const blobURL = doc.output('bloburl');
            window.open(blobURL, '_blank');
        }

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