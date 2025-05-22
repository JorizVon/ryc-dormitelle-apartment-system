<?php
session_start();

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php"); // Adjust path if LOGIN.php is not in the same directory
    exit();
}

date_default_timezone_set('Asia/Manila');

require_once 'db_connect.php'; // Adjust path if db_connect.php is not in the same directory

// Initialize $table_result to null and $query_error
$table_result = null;
$query_error_table = ""; 

// Main table query - Changed payment_date to payment_date_time
$table_query = "SELECT payments.transaction_no, tenants.tenant_name, payments.payment_date_time, 
                tenant_unit.deposit, payments.amount_paid, tenant_unit.balance, payments.payment_method, payments.confirmation_status 
                FROM payments 
                INNER JOIN tenants ON payments.tenant_ID = tenants.tenant_ID 
                INNER JOIN tenant_unit ON tenants.tenant_ID = tenant_unit.tenant_ID 
                WHERE payments.confirmation_status = 'Pending'";

$query_exec_result = $conn->query($table_query);

if ($query_exec_result === false) {
    $query_error_table = "Error fetching pending payments: " . $conn->error;
    error_log($query_error_table);
} else {
    $table_result = $query_exec_result;
}

// Query to count payment status occurrences
$status_query = "SELECT payment_status, COUNT(*) as count FROM payments GROUP BY payment_status";
$status_result_exec = $conn->query($status_query);
$status_data = [
    'Fully Paid' => 0,
    'Paid Overdue' => 0,
    'Partially Paid' => 0
];
if ($status_result_exec === false) {
    error_log("Error fetching payment status counts: " . $conn->error);
} elseif ($status_result_exec) {
    while ($row = $status_result_exec->fetch_assoc()) {
        if (array_key_exists($row['payment_status'], $status_data)) {
            $status_data[$row['payment_status']] = (int)$row['count'];
        }
    }
}

// Query to count payment method occurrences
$method_query = "SELECT payment_method, COUNT(*) as count FROM payments GROUP BY payment_method";
$method_result_exec = $conn->query($method_query);
$method_data = [
    'Gcash' => 0,
    'Cash' => 0,
    'settle with deposit' => 0
];
if ($method_result_exec === false) {
    error_log("Error fetching payment method counts: " . $conn->error);
} elseif ($method_result_exec) {
    while ($row = $method_result_exec->fetch_assoc()) {
        if (array_key_exists($row['payment_method'], $method_data)) {
            $method_data[$row['payment_method']] = (int)$row['count'];
        }
    }
}

// Prepare the data for Google Charts
$fully_paid_count = $status_data['Fully Paid'];
$paid_overdue_count = $status_data['Paid Overdue'];
$partially_paid_count = $status_data['Partially Paid'];

$gcash_count = $method_data['Gcash'];
$cash_count = $method_data['Cash'];
$settle_deposit_count = $method_data['settle with deposit'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirect_needed = false;

    if (isset($_POST['confirm_transaction'])) {
        $transaction_no = $_POST['confirm_transaction'];
        $stmt = $conn->prepare("UPDATE payments SET confirmation_status = 'Confirmed' WHERE transaction_no = ? AND confirmation_status = 'Pending'");
        if ($stmt) {
            $stmt->bind_param("s", $transaction_no);
            $stmt->execute();
            $stmt->close();
            $redirect_needed = true;
        } else {
            error_log("Error preparing confirm transaction statement: " . $conn->error);
        }
    }

    if (isset($_POST['delete_transaction'])) {
        $transaction_no = $_POST['delete_transaction'];
        $stmt = $conn->prepare("DELETE FROM payments WHERE transaction_no = ? AND confirmation_status = 'Pending'");
        if ($stmt) {
            $stmt->bind_param("s", $transaction_no);
            $stmt->execute();
            $stmt->close();
            $redirect_needed = true;
        } else {
            error_log("Error preparing delete transaction statement: " . $conn->error);
        }
    }

    if ($redirect_needed) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

$adminDisplayIdentifier = "ADMIN";
if (isset($_SESSION['email_account'])) {
    // Example: $adminDisplayIdentifier = htmlspecialchars(strtok($_SESSION['email_account'], '@'));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <title>Payment Management</title>
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
            font-weight: 200; 
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
        .tenantHistoryHead {
            display: flex;
            justify-content: space-between;
            width: 67%;
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
        .tenantInfoandGraphs {
            display: flex;
            flex-wrap: wrap; 
            justify-content: space-between;
            width: 94%;
            margin: 0 auto; 
            gap: 20px; 
        }
        .table_container {
            flex-basis: 63%; 
            min-width: 300px; 
            flex-grow: 1;
            border: 3px solid #A6DDFF;
            border-radius: 8px;
            max-height: 400px; 
            overflow: hidden; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .table_scroll {
            height: 100%; 
            overflow-y: auto;
            scrollbar-width: thin;
        }
        .table_scroll::-webkit-scrollbar {
            width: 6px;
        }
        .table_scroll::-webkit-scrollbar-thumb {
            background-color: #A6DDFF;
            border-radius: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            table-layout: fixed; 
        }
        th, td {
            padding: 10px 8px; 
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px; 
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        th {
            background-color: #e3f2fd;
            font-weight: bold;
            font-size: 12px; 
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .action_btn {
            background-color: #2196f3;
            color: white;
            border: none;
            padding: 6px 9px; 
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px; 
            white-space: nowrap;
        }
        .action_btn:hover {
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
        .graphsplacement {
            flex-basis: 34%; 
            min-width: 280px; 
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .monthlyRevenueTitle {
            display: flex;
            align-items: center;
            width: 100%;
            justify-content: center;
            flex-wrap: wrap;
        }
        .monthlyRevenueTitle h3 {
            margin: 5px 3px; 
            font-size: 15px; 
        }
        .graphsplacement h4 { 
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 8px 0; 
            font-size: 15px; 
            text-align: center;
        }
        .bargraphContainer {
            width: 100%;
            display: flex;
            justify-content: center;
            margin-bottom: 15px; 
            flex-grow: 1; 
            min-height: 180px; 
        }
        .bargraph {
            width: 100%;
        }
        .pieChartTitle {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 8px 0; 
            font-size: 15px; 
            text-align: center;
        }
        .piechartContainer {
            width: 100%;
            display: flex;
            justify-content: center;
            flex-grow: 1; 
            min-height: 180px; 
        }
        .pieChart {
            width: 100%;
        }
        .viewtransactionhistory {
            height: 36px;
            min-width: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #004AAD;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            padding: 0 15px;
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
        .footbtnContainer a.backbtn:hover, .footbtnContainer a.viewtransactionhistory:hover { 
            background-color: #FFFFFF;
            color: #004AAD;
            border: 2px solid #004AAD;
        }
        .hamburger {
            display: none; 
        }
        
        /* Mobile and Tablet Responsive */
        @media (max-width: 1199px) { 
            .tenantInfoandGraphs {
                flex-direction: column; 
            }
            .table_container {
                flex-basis: auto; 
                width: 100%; 
            }
            .graphsplacement {
                flex-basis: auto; 
                width: 100%; 
            }
            .bargraphContainer, .piechartContainer {
                min-height: 220px; 
            }
            .footbtnContainer {
                margin-top: 20px;
            }
        }
        
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
                position: fixed; 
                top: 25px;
                left: 20px;
                z-index: 1100;
                font-size: 30px;
                cursor: pointer;
                color: #004AAD;
                width: auto; 
                background-color: white;
                padding: 5px 10px;
                border-radius: 3px;
            }
            .mainBody {
                width: 100%;
                margin-left: 0 !important; 
            }
            .header {
                justify-content: flex-end; 
                padding-right: 15px;
            }
            .headerContent {
                margin-right: 10px;
            }
            .tenantHistoryHead {
                flex-direction: column;
                align-items: stretch; 
                width: calc(100% - 30px); 
            }
            .tenantHistoryHead h4 {
                margin-bottom: 10px; 
                margin-left: 0; 
                text-align: center;
            }
            .searbar {
                margin-left: 0; 
                width: 100%; 
                margin-bottom: 15px; 
            }
            .footbtnContainer {
                flex-direction: column;
                gap: 15px;
                align-items: center;
            }
            .backbtn {
                order: 2;
                width: 80%;
                max-width: 250px;
                visibility: visible;
            }
            .viewtransactionhistory {
                order: 1;
                width: 80%;
                max-width: 250px;
            }
            .table_container {
                max-width: 100%;
                border-left: none;
                border-right: none;
                border-radius: 0;
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
                width: 220px; 
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
            .tenantHistoryHead h4 {
                font-size: 24px;
            }
            th, td {
                padding: 8px 5px;
                font-size: 11px; 
            }
            .action_btn {
                padding: 5px 7px; 
                font-size: 11px; 
            }
            .monthlyRevenueTitle h3 {
                font-size: 14px; 
            }
            .graphsplacement h4 { 
                font-size: 13px; 
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
                <a href="PAYMENTMANAGEMENT.php"  style="background-color: #FFFF; color: #004AAD;">
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
                <a href="LOGIN.php" class="logOutbtn">Log Out</a>
            </div>
        </div>
        <div class="mainContent">
            <div class="tenantHistoryHead">
                <h4>Payment Management</h4>
                <input type="text" placeholder="Search by Tx No, Name, Date..." class="searbar" id="searchInput" onkeyup="searchTable()">
            </div>
            <div class="tenantInfoandGraphs">
                <div class="table_container">
                    <div class="table_scroll">
                      <table id="paymentTable">
                        <thead>
                          <tr>
                            <th>Tx no.</th>
                            <th>Tenant</th>
                            <th>Date & Time</th>
                            <th>Deposit (₱)</th>
                            <th>Amount (₱)</th>
                            <th>Balance (₱)</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>Confirm</th>
                            <th>Cancel</th>
                          </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($query_error_table)) {
                                echo "<tr><td colspan='9' style='color:red; text-align:center;'>" . htmlspecialchars($query_error_table) . "</td></tr>";
                            } elseif ($table_result && $table_result->num_rows > 0) {
                                while ($row = $table_result->fetch_assoc()) { ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['transaction_no']); ?></td>
                                        <td><?php echo htmlspecialchars($row['tenant_name']); ?></td>
                                        <td><?php echo htmlspecialchars(date("M d, Y h:i A", strtotime($row['payment_date_time']))); ?></td> <!-- Changed and Formatted -->
                                        <td><?php echo number_format((float)$row['deposit'], 2); ?></td>
                                        <td><?php echo number_format((float)$row['amount_paid'], 2); ?></td>
                                        <td><?php echo number_format((float)$row['balance'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                                        <td><?php echo htmlspecialchars($row['confirmation_status']); ?></td>
                                        <td>
                                            <?php if (strtolower($row['confirmation_status']) === 'pending'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="confirm_transaction" value="<?php echo htmlspecialchars($row['transaction_no']); ?>">
                                                <button type="submit" class="action_btn">Confirm</button>
                                            </form>
                                            <?php else: echo htmlspecialchars($row['confirmation_status']); endif; ?>
                                        </td>
                                        <td>
                                            <?php if (strtolower($row['confirmation_status']) === 'pending'): ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to cancel this transaction?');">
                                                <input type="hidden" name="delete_transaction" value="<?php echo htmlspecialchars($row['transaction_no']); ?>">
                                                <button type="submit" class="action_btn" style="background-color: #f44336;">Cancel</button>
                                            </form>
                                            <?php else: echo "N/A"; endif; ?>
                                        </td>
                                    </tr>
                                <?php } // End while loop
                            } else { // End if $table_result->num_rows > 0
                                echo "<tr><td colspan='9' style='text-align:center;'>No pending payment records found.</td></tr>";
                            } // End else
                            ?>
                            </tbody>
                      </table>
                    </div>
                </div>
                <div class="graphsplacement">
                    <div class="monthlyRevenueTitle">
                        <h3>Payment Summary</h3>
                        <h3 id="RevenuecurrentMonth"></h3>
                    </div>
                    <h4>Payment Status Distribution</h4>
                    <div class="bargraphContainer">
                        <div id="chart_div" class="bargraph"></div>
                    </div>
                    <h4 class="pieChartTitle">Payment Methods Breakdown</h4>
                    <div class="piechartContainer">
                        <div id="piechart" class="pieChart"></div>
                    </div>
                </div>
            </div>
            <div class="footbtnContainer">
                <a href="DASHBOARD.php" class="backbtn">⤾ Back</a>
                <a href="TRANSACTIONHISTORY.php" class="viewtransactionhistory">
                    View All Transaction History</a>
            </div>
        </div>
    </div>
    <script>
        google.charts.load('current', {packages: ['corechart', 'bar']});
        google.charts.setOnLoadCallback(drawCharts);
        
        function drawCharts() {
            drawBarChart();
            drawPieChart();
            
            window.addEventListener('resize', function() {
                setTimeout(function() {
                    drawBarChart();
                    drawPieChart();
                }, 250);
            });
        }

        function getResponsiveFontSize() {
            if (window.innerWidth < 480) return 8;
            if (window.innerWidth < 768) return 9;
            if (window.innerWidth < 1024) return 10;
            return 11;
        }

        function drawBarChart() {
            var data = google.visualization.arrayToDataTable([
                ['Payment Status', 'Count', { role: 'style' }],
                ['Fully Paid', <?php echo $fully_paid_count; ?>, 'color: #4CAF50'],
                ['Paid Overdue', <?php echo $paid_overdue_count; ?>, 'color: #F44336'],
                ['Partially Paid', <?php echo $partially_paid_count; ?>, 'color: #FF9800']
            ]);

            var options = {
                title: '',
                hAxis: { title: 'Payment Status', textStyle: { fontSize: getResponsiveFontSize() } },
                vAxis: { title: 'Number of Payments', minValue: 0, textStyle: { fontSize: getResponsiveFontSize() } },
                legend: { position: 'none' },
                chartArea: { width: '80%', height: '70%' },
                animation: { startup: true, duration: 1000, easing: 'out' },
                bar: { groupWidth: "60%" }
            };

            var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
            chart.draw(data, options);
        }
        
        function drawPieChart() {
            var data = google.visualization.arrayToDataTable([
                ['Mode of Payment', 'Count'],
                ['Gcash', <?php echo $gcash_count; ?>],
                ['Cash', <?php echo $cash_count; ?>],
                ['settle with deposit', <?php echo $settle_deposit_count; ?>]
            ]);

            var options = {
                title: '',
                pieHole: 0.3,
                pieSliceText: 'percentage',
                slices: {
                    0: { color: '#2196f3' },
                    1: { color: '#FFC107' },
                    2: { color: '#9C27B0' } 
                },
                legend: { position: 'bottom', alignment: 'center', textStyle: { fontSize: getResponsiveFontSize() } },
                chartArea: { width: '90%', height: '75%' },
                animation: { startup: true, duration: 1000, easing: 'out' }
            };

            var chart = new google.visualization.PieChart(document.getElementById('piechart'));
            chart.draw(data, options);
        }
        
        function searchTable() {
            const input = document.getElementById("searchInput").value.toLowerCase().trim();
            const table = document.getElementById("paymentTable");
            const tr = table.getElementsByTagName("tr");
            let found = false; 

            for (let i = 1; i < tr.length; i++) { 
                const row = tr[i];
                if (row.getElementsByTagName("td").length === 1 && row.getElementsByTagName("td")[0].colSpan === 9) {
                    continue; 
                }

                const tdTxNo = row.getElementsByTagName("td")[0];
                const tdName = row.getElementsByTagName("td")[1];
                const tdDate = row.getElementsByTagName("td")[2]; // This now contains date and time
                let rowVisible = false;

                if (tdTxNo && tdTxNo.textContent.toLowerCase().includes(input)) rowVisible = true;
                if (tdName && tdName.textContent.toLowerCase().includes(input)) rowVisible = true;
                if (tdDate && tdDate.textContent.toLowerCase().includes(input)) rowVisible = true; 
                
                row.style.display = rowVisible ? "" : "none";
                if (rowVisible) found = true;
            }
            const noRecordsRow = table.querySelector('td[colspan="9"]');
            if (noRecordsRow) {
                if (!found && input !== "" && tr.length > 1 && !(tr.length === 2 && tr[1].style.display === 'none' && noRecordsRow.parentNode === tr[1])) { 
                    noRecordsRow.textContent = "No matching records found for your search.";
                    noRecordsRow.parentNode.style.display = ""; 
                } else if (input === "" && tr.length > 1 && noRecordsRow.textContent.includes("No pending payment records found.")) { 
                     noRecordsRow.parentNode.style.display = "";
                } else if (tr.length <=1) { 
                     noRecordsRow.parentNode.style.display = "";
                }
                else { 
                    noRecordsRow.parentNode.style.display = "none";
                }
            }
        }
        
        function toggleSidebar() {
            const sidebar = document.querySelector('.sideBar');
            sidebar.classList.toggle('active');
        }
        
        window.onload = function() {
            const months = [
                'January', 'February', 'March', 'April', 'May', 'June', 
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            const currentDate = new Date();
            const monthName = months[currentDate.getMonth()];
            const year = currentDate.getFullYear();
            const revenueMonthElement = document.getElementById('RevenuecurrentMonth');
            if(revenueMonthElement) { 
                 revenueMonthElement.textContent = `(${monthName} ${year})`;
            }
            if(document.getElementById("paymentTable")){
                 searchTable();
            }
        };
    </script>
</body>
</html>
<?php
if (isset($conn)) { 
    $conn->close();
}
?>