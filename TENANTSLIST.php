<?php
session_start();

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    // Assuming LOGIN.php is in the same directory or adjust path as needed
    header("Location: LOGIN.php");
    exit();
}

// Assuming db_connect.php is in the same directory or adjust path
require_once 'db_connect.php';

// Initialize $result to null or an empty array to avoid issues if query fails
$result = null;
$query_error = ""; // To store any potential query error message

$sql = "SELECT tenants.tenant_ID, tenants.tenant_name, tenants.contact_number, 
               tenant_unit.start_date, tenant_unit.occupant_count,
               tenant_unit.deposit, tenant_unit.balance, tenant_unit.status
        FROM tenants
        INNER JOIN tenant_unit ON tenants.tenant_ID = tenant_unit.tenant_ID";

// Execute the query
$query_result = $conn->query($sql);

// Check if the query was successful
if ($query_result === false) {
    // Query failed, store the error message
    $query_error = "Error executing query: " . $conn->error;
    error_log($query_error); // Log the error for debugging
    $result_data = []; // Ensure $result_data is an empty array so the HTML doesn't break
} else {
    // Query was successful, fetch all results into an array
    // This is generally better than fetching row by row inside the HTML loop for larger datasets
    // but for simplicity and to match your original structure, we'll keep $result as the mysqli_result object.
    $result = $query_result; // Assign the mysqli_result object to $result
}

// Get admin's display name (optional, for header)
$adminDisplayIdentifier = "ADMIN"; // Default
if (isset($_SESSION['email_account'])) {
    // You could fetch a name from 'accounts' table based on 'email_account'
    // For now, let's assume a default or you handle it as in other pages
    // Example: $adminDisplayIdentifier = htmlspecialchars(strtok($_SESSION['email_account'], '@'));
}


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
            /* color: #01214B; */ /* Covered by color: white */
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
        .table-container {
            max-width: 90%;
            margin: 0 auto;
            border: 3px solid #A6DDFF;
            border-radius: 8px;
            height: 415px;
            max-height: 470px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-scroll {
            /* max-height: 500px; */ /* This was redundant with table-container max-height */
            height: 100%; /* Fill the container */
            overflow-y: auto;
            overflow-x: auto; /* Keep for wide tables */
            scrollbar-width: none; /* For Firefox */
        }

        .table-scroll::-webkit-scrollbar {
            display: none; /* For Chrome, Safari, Opera */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            /* border-color: #A6DDFF; */ /* Border on container is enough */
            background-color: white;
            
        }
        th, td {
            padding: 10px 12px; /* Adjusted padding */
            text-align: left;
            font-size: 13px; /* Adjusted font size */
            border-bottom: 1px solid #e0e0e0;
            white-space: nowrap; /* Prevent text wrapping that might make rows too tall */
        }
        th {
            background-color: #e3f2fd;
            font-weight: bold;
            position: sticky;
            top: 0;
            z-index: 1;
            font-size: 12px;
        }
        .action-btn {
            background-color: #2196f3;
            color: white;
            border: none;
            padding: 7px 12px; /* Adjusted padding */
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
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
        .footbtnContainer a.backbtn:hover, .footbtnContainer a.addtenantbtn:hover { /* Target specific links */
            background-color: #FFFFFF;
            color: #004AAD;
            border: 2px solid #004AAD;
        }
        .addtenantbtn {
            height: 36px;
            width: auto; /* Auto width based on content */
            padding: 0 15px; /* Padding for text */
            /* position: relative; */ /* Removed */
            display: flex;
            align-items: center;
            justify-content: center;
            /* bottom: 20px; */ /* Removed */
            background-color: #004AAD;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px; /* Added for consistency */
        }
        .addtenantbtnIcon {
            height: 18px; /* Adjusted size */
            width: 18px; /* Adjusted size */
            margin-right: 8px; /* Increased margin */
        }
        .footbtnContainer a:hover .addtenantbtnIcon {
            content: url('UnitsInfoIcons/plusblue.png');
        }
        .hamburger {
            display: none; /* Original: visibility: hidden; width: 0px; */
        }
        /* Mobile and Tablet Responsive */
        @media (max-width: 1024px) {
            body {
            /* display: flex; */ /* Removed, default is block */
            /* margin: 0; */
            /* background-color: #FFFF; */
            /* justify-content: center; */ /* Removed, not ideal for this layout */
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
                position: fixed; /* Changed */
                top: 25px;
                left: 20px;
                z-index: 1100;
                font-size: 30px;
                cursor: pointer;
                color: #004AAD;
                /* visibility: visible; */
                width: auto; /* Changed */
                background-color: white;
                padding: 5px 10px;
                border-radius: 3px;
            }

            .mainBody {
                width: 100%;
                margin-left: 0 !important; /* Ensure full width when sidebar is hidden/overlay */
            }

            .header {
                justify-content: flex-end; /* Changed */
            }

            .mainContent {
                width: 100%;
                margin: 0 auto;
                padding: 15px; /* Added padding */
                box-sizing: border-box; /* Include padding in width */
            }

            .tenantHistoryHead {
                flex-direction: column;
                align-items: stretch; /* Stretch items */
                text-align: center;
            }

            .tenantHistoryHead h4 {
                margin: 15px 0 10px 0; /* Adjusted margin */
                font-size: 28px; /* Adjusted */
                margin-left: 0; /* Center title */
            }

            .searbar {
                width: 90%;
                margin: 10px auto; /* Centered search bar */
            }

            .table-container {
                width: 100%; /* Full width on mobile */
                max-width: 100%; /* Override desktop max-width */
                border-left: none; /* Remove side borders for full bleed */
                border-right: none;
                border-radius: 0; /* No radius for full bleed */
                max-height: calc(100vh - 250px); /* Example dynamic height */
            }

            table th, table td {
                font-size: 11px; /* Adjusted */
                padding: 8px 5px; /* Adjusted */
            }
            .action-btn {
                font-size: 10px;
                padding: 6px 8px; /* Adjusted */
            }

            .footbtnContainer {
                flex-direction: column;
                align-items: center;
                gap: 15px;
                /* top: 10px; */ /* Position with margin */
                margin: 20px auto; /* Adjusted */
                width: 100%;
            }
            .addtenantbtn {
                font-size: 14px; /* Adjusted */
                width: 80%;
                max-width: 280px;
                /* padding: 5px 10px; */ /* Already has padding */
            }
            .backbtn {
                visibility: hidden;
            }
        }

        @media (max-width: 480px) {
            .headerContent { margin-right: 20px; }
            .headerContent a, .adminLogoutspace {
                font-size: 14px;
            }
            .hamburger {
                font-size: 28px;
                top: 15px;
                left: 15px;
            }
            .sideBar{
                width: 220px; /* Adjusted */
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
                height: 18px;
                width: 18px;
                margin-right: 8px;
            }
            /* .table-scroll { */
                /* width: 600px; */ /* This will force horizontal scroll if screen is smaller */
            /* } */
            table th, table td {
                font-size: 10px; /* Further adjust for very small screens */
            }
            .tenantHistoryHead h4 { font-size: 24px; }
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
                <a href="TENANTSLIST.php" style="background-color: #FFFF; color: #004AAD;">
                    <img src="sidebarIcons/TenantsInfoIcon.png" alt="Tenants Information Icon" class="THsidebarIcon" style="margin-right: 3px;">
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
                    <a href="ADMINPROFILE.php" class="adminTitle"><?php echo $adminDisplayIdentifier; ?></a>
                    <p class="adminLogoutspace"> | </p>
                    <a href="LOGIN.php" class="logOutbtn">Log Out</a> <!-- Or to LOGOUT.php -->
                </div>
            </div>
            <div class="mainContent">
        <div class="tenantHistoryHead">
            <h4>Tenants List</h4>
            <input type="text" placeholder="Search by Name, ID, Contact..." class="searbar"> <!-- Updated placeholder -->
        </div>
        <div class="table-container">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Tenant ID</th>
                            <th>Name</th>
                            <th>Contact Number</th>
                            <th>Start Date</th>
                            <th>Occupant Count</th>
                            <th>Current Deposit (₱)</th> <!-- Added Currency -->
                            <th>Current Balance (₱)</th> <!-- Added Currency -->
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Check if there was a query error before trying to use $result
                        if (!empty($query_error)) {
                            echo "<tr><td colspan='9' style='color: red; text-align: center;'>Error loading tenants: " . htmlspecialchars($query_error) . "</td></tr>";
                        } elseif ($result && $result->num_rows > 0) { // Check if $result is a valid mysqli_result object
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row["tenant_ID"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["tenant_name"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["contact_number"]) . "</td>";
                                // Format date if needed: echo "<td>" . htmlspecialchars(date("M d, Y", strtotime($row["start_date"]))) . "</td>";
                                echo "<td>" . htmlspecialchars($row["start_date"]) . "</td>";
                                echo "<td>". htmlspecialchars($row["occupant_count"]) . "</td>";
                                echo "<td>". number_format((float)$row["deposit"], 2) . "</td>"; // Format as currency
                                echo "<td>". number_format((float)$row["balance"], 2) . "</td>"; // Format as currency
                                echo "<td>" . htmlspecialchars($row["status"]) . "</td>";
                                echo '<td><a href="TENANTINFORMATION.php?tenant_ID=' . urlencode($row["tenant_ID"]) . '" class="action-btn">View Details</a></td>';
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='9' style='text-align: center;'>No tenants found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
            <div class="footbtnContainer">
                <a href="DASHBOARD.php" class="backbtn">⤾ Back</a>
            </div>
        </div>
    </div>
    <script>
    document.querySelector('.searbar').addEventListener('keyup', function () {
        const filter = this.value.toLowerCase().trim(); // Added trim
        const rows = document.querySelectorAll('tbody tr');

        rows.forEach(row => {
            // Check if the row is a 'no results' or 'error' row before hiding
            if (row.querySelector('td[colspan="9"]')) {
                // For the "No tenants found" or error message row, always show if filter is empty,
                // or hide if filter is not empty (as it's not a data row to be filtered)
                // This part might need more complex logic depending on exact behavior desired for these rows.
                // For now, we'll assume these rows are not part of the active filtering data.
                return;
            }

            let rowVisible = false;
            // Loop through all cells (td) in the current row
            row.querySelectorAll('td').forEach(cell => {
                // Exclude the action button cell from search text
                if (cell.querySelector('.action-btn')) return;

                if (cell.textContent.toLowerCase().includes(filter)) {
                    rowVisible = true;
                }
            });
            row.style.display = rowVisible ? '' : 'none';
        });
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