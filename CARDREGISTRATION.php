<?php
session_start();

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    // Assuming LOGIN.php is in the same directory or adjust path as needed
    header("Location: LOGIN.php");
    exit();
}

require_once 'db_connect.php'; // Assuming db_connect.php is in the same directory

// Initialize $result and $query_error
$result = null;
$query_error = "";

// Fetch the card registration data
// Added card_registration.tenant_ID to be able to pass it if needed, or a unique card_id if available
$sql = "SELECT cr.registration_date, cr.card_expiry, cr.card_status, 
               tu.unit_no, t.tenant_name, cr.tenant_ID AS card_tenant_ID 
               -- If card_registration has its own primary key like 'card_id', select it:
               -- , cr.card_id 
        FROM card_registration cr
        LEFT JOIN tenant_unit tu ON cr.tenant_ID = tu.tenant_ID
        INNER JOIN tenants t ON tu.tenant_ID = t.tenant_ID
        ORDER BY cr.registration_date DESC"; // Optional: Order by registration date

$query_exec_result = $conn->query($sql);

if ($query_exec_result === false) {
    $query_error = "Error fetching card registration data: " . $conn->error;
    error_log($query_error);
} else {
    $result = $query_exec_result;
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
    <title>Card Registration</title>
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
        .headerContent a.logOutbtn:hover { /* Specific selector */
            color: #004AAD;
        }
        .mainContent {
            height: calc(100% - 13vh); /* Adjusted for header */
            width: 100%;
            margin: 0px auto;
            background-color: #FFFF;
            padding-top: 20px;
        }
        .tenantHistoryHead { /* Reusing class, ensure CSS targets it or use a new one */
            display: flex;
            justify-content: space-between;
            width: calc(90% - 40px); /* Align with table */
            align-items: center;
            margin: 0 auto 15px auto; /* Center and margin */
        }
        .tenantHistoryHead h4 {
            color: #01214B;
            font-size: 32px;
            /* margin-left: 60px; */ /* Handled by container margin */
            /* height: 20px; */
            /* align-items: center; */
            margin-top:0; margin-bottom:0;
        }
        .searbar {
            height: 20px; /* Original */
            width: 270px; /* Original */
            border-style: solid; /* Original */
            font-size: 12px; /* Original */
            padding: 5px 8px;
            box-sizing: border-box;
            /* position: relative; */
            /* top: 14px; */
        }
        ::placeholder {
            color: #B7B5B5;
            opacity: 1;
        }
        .table-container {
            width: 90%; /* Original */
            margin: 0 auto; /* Original */
            border: 3px solid #A6DDFF; /* Original */
            border-radius: 8px; /* Original */
            height: 470px; /* Original */
            overflow: hidden; /* Original */
            box-shadow: 0 4px 8px rgba(0,0,0,0.1); /* Original */
        }
        .table-scroll {
            /* max-height: 500px; */ /* Redundant with container height */
            height: 100%;
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
            /* border-color: #A6DDFF; */ /* Border is on container */
            background-color: white;
        }
        th, td {
            padding: 12px 15px; /* Original */
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px; /* Added based on previous tenant list */
            white-space: nowrap;
        }
        th {
            background-color: #e3f2fd;
            font-weight: bold;
            position: sticky;
            top: 0;
            z-index: 1;
            font-size: 12px; /* Added based on previous tenant list */
        }
        .action-btn {
            background-color: #2196f3;
            color: white;
            border: none;
            padding: 8px 14px; /* Original */
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px; /* Original */
        }

        .action-btn:hover {
            background-color: #1976d2;
        }
        .footbtnContainer {
            width: 90%; /* Original */
            /* height: 20px; */ /* Let content define */
            /* margin-left: 60px; */ /* Use auto margin for centering */
            margin: 20px auto 0 auto; /* Consistent margin */
            display: flex;
            /* position: relative; */
            /* top: 38px; */
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
            font-size: 14px; /* Added */
        }
        /* button { } */ /* General button style, target specific if needed */
        /* button:hover { } */
        .footbtnContainer a.backbtn:hover, .footbtnContainer a.addtenantbtn:hover { /* Specificity for links */
            background-color: #FFFFFF;
            color: #004AAD;
            border: 2px solid #004AAD;
        }
        .addtenantbtn {
            height: 36px;
            width: auto; /* Auto width */
            padding: 0 15px; /* Padding for text */
            /* position: relative; */
            display: flex;
            align-items: center;
            justify-content: center;
            /* bottom: 20px; */
            background-color: #004AAD;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px; /* Added */
        }
        .addtenantbtnIcon {
            height: 20px; /* Original */
            width: 20px; /* Original */
            margin-right: 5px; /* Original */
        }
        .footbtnContainer a.addtenantbtn:hover .addtenantbtnIcon { /* Specificity */
            content: url('UnitsInfoIcons/plusblue.png');
        }
        .hamburger {
            display: none; /* Original: visibility: hidden; width: 0px; */
        }
        /* Mobile and Tablet Responsive - Copied from original */
        @media (max-width: 1024px) {
            .sideBar {
                position: fixed; left: -100%; top: 0; height: 100vh;
                z-index: 1000; transition: 0.3s ease;
            }
            .sideBar.active { left: 0; }
            .hamburger {
                display: block; position: fixed; /* Changed */ top: 25px; left: 20px; z-index: 1100;
                font-size: 30px; cursor: pointer; color: #004AAD; width: auto; /* Changed */
                background-color: white; padding: 5px 10px; border-radius: 3px;
            }
            .mainBody { width: 100%; margin-left: 0 !important; }
            .header { justify-content: flex-end; /* Changed */ }
            .mainContent { width: 95%; margin: 0 auto; padding: 15px; box-sizing:border-box; }
            .tenantHistoryHead { flex-direction: column; align-items: stretch; /* Changed */ text-align: center; width:100%;}
            .tenantHistoryHead h4 { margin: 15px 0 10px 0; font-size: 30px; margin-left:0; }
            .searbar { width: 100%; margin: 10px auto; }
            .table-container { width: 100%; max-width:100%; overflow-x: auto; border-left:none; border-right:none; border-radius:0; max-height: calc(100vh - 280px); }
            table th, table td { font-size: 11px; padding: 10px 5px; } /* Adjusted padding */
            .action-btn { font-size: 10px; padding: 6px 8px; } /* Adjusted */
            .footbtnContainer { flex-direction: column; align-items: center; gap: 15px; /* top: 30px; */ margin: 20px auto; width:100%;}
            .addtenantbtn { font-size: 16px; /* Adjusted */ /* bottom: 45px; */ padding: 8px 15px; width:80%; max-width:280px;} /* Adjusted */
            .backbtn { /* visibility: hidden; */ width:80%; max-width:280px;}
        }
        @media (max-width: 480px) {
            .headerContent { margin-right: 20px; }
            .headerContent a, .adminLogoutspace { font-size: 14px; }
            .hamburger { font-size: 28px; }
            .sideBar{ width: 220px; } /* Adjusted */
            .systemTitle { position: relative; top: 15px; padding: 11px; }
            .systemTitle h1 { font-size: 14px; position: relative; margin-bottom: 18px; }
            .systemTitle p { font-size: 10px; }
            .card a { font-size: 14px; padding-left:15px;}
            .card img { height: 18px; width:18px; margin-right:8px;}
            /* .table-scroll { width: 600px; } */ /* Better to let it be responsive */
            table th, table td { font-size: 10px; padding: 8px 3px;} /* Further adjust */
            .tenantHistoryHead h4 { font-size: 24px; }
            .action-btn { font-size:9px; padding: 5px 7px;}
            .addtenantbtn {font-size:14px;}

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
                <a href="CARDREGISTRATION.php" style="background-color: #FFFF; color: #004AAD;">
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
                <div class="tenantHistoryHead"> <!-- Reusing class for consistency, you can rename if desired -->
                    <h4>Card Registration</h4>
                    <input type="text" id="searchInput" placeholder="Search Name, Unit, Status..." class="searbar"> <!-- Re-linked client search -->
                </div>
                <div class="table-container">
                    <div class="table-scroll">
                      <table id="cardTable"> <!-- Added ID for search script -->
                        <thead>
                          <tr>
                            <th>No.</th>
                            <th>Unit No.</th>
                            <th>Tenant Name</th>
                            <th>Registration Date</th>
                            <th>Expiration Date</th>
                            <th>Card Status</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($query_error)) {
                                echo "<tr><td colspan='7' style='color:red; text-align:center;'>" . htmlspecialchars($query_error) . "</td></tr>";
                            } elseif ($result && $result->num_rows > 0) {
                                $count = 1;
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $count++ . "</td>";
                                    echo "<td>" . htmlspecialchars($row['unit_no'] ?? 'N/A') . "</td>"; // Added null coalescing for unit_no
                                    echo "<td>" . htmlspecialchars($row['tenant_name'] ?? 'N/A') . "</td>"; // Added null coalescing for tenant_name
                                    echo "<td>" . htmlspecialchars(date("M d, Y", strtotime($row['registration_date']))) . "</td>"; // Formatted date
                                    echo "<td>" . htmlspecialchars(date("M d, Y", strtotime($row['card_expiry']))) . "</td>";     // Formatted date
                                    echo "<td>" . htmlspecialchars($row['card_status']) . "</td>";
                                    // Construct the link for View Details
                                    // IF card_registration has its own unique ID (e.g., card_id), use that.
                                    // Otherwise, unit_no and tenant_ID (from cr.tenant_ID) might be needed.
                                    // For now, using unit_no and the tenant_ID aliased as card_tenant_ID from the query.
                                    $view_details_params = "unit_no=" . urlencode($row['unit_no'] ?? '') . "&tenant_id=" . urlencode($row['card_tenant_ID'] ?? '');
                                    // If you selected cr.card_id in SQL:
                                    // if (isset($row['card_id'])) {
                                    //     $view_details_params = "card_id=" . urlencode($row['card_id']);
                                    // }
                                    echo "<td><a href='CARDRENEWORDELETE.php?" . $view_details_params . "' class='action-btn'>View Details</a></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7' style='text-align:center;'>No card registration records found</td></tr>";
                            }
                            ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
            </div> <!-- This was missing a closing div for mainContent in original tenantlist.php -->
            <div class="footbtnContainer">
                <a href="DASHBOARD.php" class="backbtn">⤾ Back</a>
                <a href="CARDREGISTER.php" class="addtenantbtn"> <!-- Assuming this is the correct "add new" link for cards -->
                    <img src="UnitsInfoIcons/pluswht.png" alt="Plus Sign" class="addtenantbtnIcon">
                    New Card to Register</a>
            </div>
        </div> <!-- mainBody -->
    </div> <!-- This was an extra closing div in your original tenantlist, likely meant for .sideBar container or body -->

    <script>
        // Client-Side Search Function (kept from your original structure)
        document.getElementById('searchInput').addEventListener('keyup', function () {
            var input, filter, table, tr, td, i, j, txtValue;
            input = document.getElementById("searchInput");
            filter = input.value.toLowerCase().trim();
            table = document.getElementById("cardTable"); // Target specific table
            tr = table.getElementsByTagName("tr");
            let foundDataRow = false;

            for (i = 1; i < tr.length; i++) { // Start at 1 to skip header row
                let displayRow = false;
                // Check if it's the "No records found" row
                if (tr[i].cells.length === 1 && tr[i].cells[0].colSpan === 7) {
                    continue; // Skip the "No records" row from filtering logic
                }

                td = tr[i].getElementsByTagName("td");
                for (j = 1; j < td.length -1; j++) { // Iterate through searchable cells (skip No. and Action)
                    if (td[j]) {
                        txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toLowerCase().indexOf(filter) > -1) {
                            displayRow = true;
                            break; 
                        }
                    }
                }
                if (displayRow) {
                    tr[i].style.display = "";
                    foundDataRow = true;
                } else {
                    tr[i].style.display = "none";
                }
            }

            // Handle "No records found" / "No matching records" message
            const noRecordsRow = table.querySelector('td[colspan="7"]');
            if (noRecordsRow) {
                const originalDataRowsExist = Array.from(tr).slice(1).some(row => row.cells.length > 1 && row.cells[0].colSpan !== 7);

                if (filter !== "" && !foundDataRow && originalDataRowsExist) {
                    noRecordsRow.textContent = "No matching records found for your search.";
                    noRecordsRow.parentNode.style.display = "";
                } else if (!originalDataRowsExist) { // If table was empty to begin with
                    noRecordsRow.textContent = "No card registration records found";
                    noRecordsRow.parentNode.style.display = "";
                } else if (filter === "" && originalDataRowsExist) { // If search cleared and there's data
                     noRecordsRow.parentNode.style.display = "none";
                } else if (!foundDataRow && filter === "" && originalDataRowsExist){ // No data and no search term
                     noRecordsRow.parentNode.style.display = "none"; // Should not happen if originalDataRowsExist is true
                }
            }
        });

        // Initial call to handle "No records" message visibility on page load
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById("cardTable")) {
                // Temporarily set input value to trigger the correct "No records" message logic
                const searchInput = document.getElementById("searchInput");
                const initialSearchValue = searchInput.value;
                searchInput.value = ""; // Simulate empty search
                searchInput.dispatchEvent(new Event('keyup')); // Trigger search
                searchInput.value = initialSearchValue; // Restore if needed
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