<?php

date_default_timezone_set('Asia/Manila');

require_once 'db_connect.php';


$table_query = "SELECT payments.transaction_no, tenants.tenant_name, payments.payment_date, 
                tenant_unit.deposit, payments.amount_paid, tenant_unit.balance, payments.confirmation_status 
                FROM payments 
                INNER JOIN tenants ON payments.tenant_ID = tenants.tenant_ID 
                INNER JOIN tenant_unit ON tenants.tenant_ID = tenant_unit.tenant_ID 
                WHERE payments.confirmation_status = 'Pending'";
$table_result = $conn->query($table_query);

// Query to count payment status occurrences
$status_query = "SELECT payment_status, COUNT(*) as count FROM payments GROUP BY payment_status";
$status_result = $conn->query($status_query);
$status_data = [
    'Fully Paid' => 0,
    'Paid Overdue' => 0,
    'Partially Paid' => 0
];
while ($row = $status_result->fetch_assoc()) {
    $status_data[$row['payment_status']] = $row['count'];
}

// Query to count payment method occurrences
$method_query = "SELECT payment_method, COUNT(*) as count FROM payments GROUP BY payment_method";
$method_result = $conn->query($method_query);
$method_data = [
    'Gcash' => 0,
    'Cash' => 0
];
while ($row = $method_result->fetch_assoc()) {
    $method_data[$row['payment_method']] = $row['count'];
}

// Prepare the data for Google Charts
$fully_paid_count = $status_data['Fully Paid'];
$paid_overdue_count = $status_data['Paid Overdue'];
$partially_paid_count = $status_data['Partially Paid'];

$gcash_count = $method_data['Gcash'];
$cash_count = $method_data['Cash'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_transaction'])) {
        $transaction_no = $_POST['confirm_transaction'];
        $stmt = $conn->prepare("UPDATE payments SET confirmation_status = 'Confirmed' WHERE transaction_no = ?");
        $stmt->bind_param("s", $transaction_no);
        $stmt->execute();
        $stmt->close();
    }

    if (isset($_POST['delete_transaction'])) {
        $transaction_no = $_POST['delete_transaction'];
        $stmt = $conn->prepare("DELETE FROM payments WHERE transaction_no = ?");
        $stmt->bind_param("s", $transaction_no);
        $stmt->execute();
        $stmt->close();
    }

    // Redirect to avoid resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
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
            font-size: 24px;
            padding-left: 20px;
            font-weight: 200;
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
            padding: 20px 0;
        }
        .tenantHistoryHead {
            display: flex;
            width: 100%;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
            height: 10px;
            position: relative;
            bottom: 10px;
        }
        .tenantHistoryHead h4 {
            color: #01214B;
            font-size: 32px;
            margin-left: 60px;
            height: 20px;
            align-items: center;
            margin-right: 20px;
        }
        .searbar {
            height: 20px;
            width: 270px;
            margin-left: 20px;
            border-style: solid;
            font-size: 16px;
            position: relative;
            top: 14px;
            padding: 5px;
        }
        ::placeholder {
            color: #000000;
            opacity: 1;
        }
        .tenantInfoandGraphs {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            width: 94%;
            margin: 0 auto;
            position: relative;
            height: 400px;
            top: 70px;
        }
        .table_container {
            width: 65%;
            border: 3px solid #A6DDFF;
            border-radius: 8px;
            height: auto;
            max-height: 400px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .table_scroll {
            max-height: 470px;
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
            border-color: #A6DDFF;
            background-color: white;
            table-layout: fixed;
        }
        th, td {
            padding: 12px 8px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        th {
            background-color: #e3f2fd;
            font-weight: bold;
            font-size: 14px;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .action_btn {
            background-color: #2196f3;
            color: white;
            border: none;
            padding: 7px 10px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            white-space: nowrap;
        }
        .action_btn:hover {
            background-color: #1976d2;
        }
        .footbtnContainer {
            width: 90%;
            margin: 30px auto 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 100px;
            position: relative;
            top: 40px;
        }

        button {
            background-color: #004AAD;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 12px;
            cursor: pointer;
        }
        button:hover {
            background-color: #FFFFFF;
            color: #004AAD;
            border: 2px solid #004AAD;
            font-size: 12px;
        }
        .graphsplacement {
            width: 33%;
            height: 400px;
            display: flex;
            flex-direction: column;
            position: relative;
            bottom: 50px;
        }
        .monthlyRevenueTitle {
            display: flex;
            align-items: center;
            width: 100%;
            justify-content: center;
            flex-wrap: wrap;
        }
        .monthlyRevenueTitle h3 {
            margin: 5px;
            font-size: 16px;
        }
        .graphsplacement h4 {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 10px 0;
            font-size: 16px;
            text-align: center;
        }
        .bargraphContainer {
            width: 100%;
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .bargraph {
            width: 100%;
            height: 200px;
        }
        .pieChartTitle {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 10px 0;
            font-size: 16px;
            text-align: center;
        }
        .piechartContainer {
            width: 100%;
            display: flex;
            justify-content: center;
        }
        .pieChart {
            width: 100%;
            height: 200px;
        }
        .footbtnContainer a:hover {
            background-color: #FFFFFF;
            color: #004AAD;
            border: 2px solid #004AAD;
            height: 33px;
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
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #004AAD;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }
        .viewtransactionHistory {
            height: 20px;
            width: 20px;
            margin-right: 5px;
        }
        .hamburger {
            visibility: hidden;
            width: 0px;
        }
        
        /* Mobile and Tablet Responsive */
        @media (max-width: 1199px) {
            .tenantInfoandGraphs {
                flex-direction: block;
                top:60px;
                height: 500px;
            }
            .tenantHistoryHead {
                display: block;
            }
            .table_container {
                bottom: 30px;
            }
            .table_container, .graphsplacement {
                width: 100%;
                margin-bottom: 40px;
                bottom: 20px;
            }
            .graphsplacement {
                order: 1; /* Show graphs first on smaller screens */
            }
            .bargraph, .pieChart {
                height: 250px;
            }
            .footbtnContainer {
                top: 220px;
            }
        }
        
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
                justify-content: flex-end;
                padding-right: 15px;
            }
            .headerContent {
                margin-right: 10px;
            }
            .tenantHistoryHead {
                flex-direction: column;
                align-items: flex-start;
                margin-top: 10px;
            }
            .tenantHistoryHead h4 {
                margin-bottom: 15px;
                margin-left: 20px;
            }
            .searbar {
                margin-left: 20px;
                width: calc(100% - 50px);
                margin-bottom: 20px;
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
            
            /* Make table responsive */
            table {
                table-layout: auto;
            }
            
            /* Adjust columns for smaller screens */
            th:nth-child(3), td:nth-child(3),  /* Payment Date */
            th:nth-child(4), td:nth-child(4),  /* Deposit */
            th:nth-child(6), td:nth-child(6) { /* Balance */
                display: none;
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
            .tenantHistoryHead h4 {
                font-size: 24px;
            }
            
            /* Further simplify table on mobile */
            th:nth-child(5), td:nth-child(5) { /* Amount */
                display: none;
            }
            
            th, td {
                padding: 8px 5px;
                font-size: 12px;
            }
            
            .action_btn {
                padding: 5px;
                font-size: 12px;
            }
            
            /* Improve graphs for mobile */
            .monthlyRevenueTitle h3 {
                font-size: 16px;
            }
            .graphsplacement h4 {
                font-size: 14px;
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
                <a href="TENANTSLIST.php">
                    <img src="sidebarIcons/TenantsInfoIconWht.png" alt="Tenants Information Icon" class="THsidebarIcon" style="margin-right: 10px;">
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
                <h4>Payment Management</h4>
                <input type="text" placeholder="Search by name or date..." class="searbar" id="searchInput" onkeyup="searchTable()">
            </div>
            <div class="tenantInfoandGraphs">
                <div class="table_container">
                    <div class="table_scroll">
                      <table id="paymentTable">
                        <thead>
                          <tr>
                            <th>Tx no.</th>
                            <th>Tenant</th>
                            <th>Date</th>
                            <th>Deposit</th>
                            <th>Amount</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Confirm</th>
                            <th>Cancel</th>
                          </tr>
                        </thead>
                        <tbody>
                            <?php if ($table_result->num_rows > 0): ?>
                                <?php while ($row = $table_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['transaction_no']); ?></td>
                                        <td><?php echo htmlspecialchars($row['tenant_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['payment_date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['deposit']); ?></td>
                                        <td><?php echo htmlspecialchars($row['amount_paid']); ?></td>
                                        <td><?php echo htmlspecialchars($row['balance']); ?></td>
                                        <td><?php echo htmlspecialchars($row['confirmation_status']); ?></td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="confirm_transaction" value="<?php echo htmlspecialchars($row['transaction_no']); ?>">
                                                <button type="submit" class="action_btn">Confirm</button>
                                            </form>
                                        </td>
                                        <td>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to cancel this transaction?');">
                                                <input type="hidden" name="delete_transaction" value="<?php echo htmlspecialchars($row['transaction_no']); ?>">
                                                <button type="submit" class="action_btn">Cancel</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9">No payment records found.</td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                      </table>
                    </div>
                </div>
                <div class="graphsplacement">
                    <div class="monthlyRevenueTitle">
                        <h3>Monthly Revenue Summary</h3>
                        <h3>(</h3>
                        <h3 id="RevenuecurrentMonth">March 2025</h3>
                        <h3>)</h3>
                    </div>
                    <h4>Units Paid vs. Overdue Payments vs. Pending</h4>
                    <div class="bargraphContainer">
                        <div id="chart_div" class="bargraph"></div>
                    </div>
                    <h3 class="pieChartTitle">Payment Methods Breakdown</h3>
                    <div class="piechartContainer">
                        <div id="piechart" class="pieChart"></div>
                    </div>
                </div>
            </div>
            <div class="footbtnContainer">
                <a href="DASHBOARD.php" class="backbtn">&#10558; Back</a>
                <a href="TRANSACTIONHISTORY.php" class="viewtransactionhistory">
                    Transaction History</a>
            </div>
        </div>
    </div>
    <script>
        google.charts.load('current', {packages: ['corechart', 'bar']});
        google.charts.setOnLoadCallback(drawCharts);
        
        function drawCharts() {
            drawBarChart();
            drawPieChart();
            
            // Redraw charts when window is resized
            window.addEventListener('resize', function() {
                drawBarChart();
                drawPieChart();
            });
        }

        function drawBarChart() {
            var data = google.visualization.arrayToDataTable([
                ['Payment Status', 'Number of Tenants', { role: 'style' }],
                ['Fully Paid', <?php echo $fully_paid_count ?? 8; ?>, 'color: green'],
                ['Paid Overdue', <?php echo $paid_overdue_count ?? 3; ?>, 'color: red'],
                ['Partially Paid', <?php echo $partially_paid_count ?? 5; ?>, 'color: orange']
            ]);

            var options = {
                title: '',
                hAxis: {
                    title: 'Payment Status',
                    textStyle: {
                        fontSize: getResponsiveFontSize()
                    }
                },
                vAxis: {
                    title: 'Number of Tenants',
                    textStyle: {
                        fontSize: getResponsiveFontSize()
                    }
                },
                legend: { position: 'none' },
                chartArea: {
                    width: '80%',
                    height: '70%'
                },
                animation: {
                    startup: true,
                    duration: 1000,
                    easing: 'out'
                }
            };

            var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
            chart.draw(data, options);
        }
        
        function getResponsiveFontSize() {
            return window.innerWidth < 600 ? 8 : 
                   window.innerWidth < 900 ? 10 : 12;
        }

        function drawPieChart() {
            var data = google.visualization.arrayToDataTable([
                ['Mode of Payment', 'Percentage'],
                ['Gcash', <?php echo $gcash_count ?? 12; ?>],
                ['Cash', <?php echo $cash_count ?? 4; ?>]
            ]);

            var options = {
                title: '',
                pieHole: 0,
                pieSliceText: 'percentage',
                slices: {
                    0: { color: '#2196f3' },  // GCash - Blue
                    1: { color: '#ffa500', offset: 0.1 }  // Cash - Orange and pulled out
                },
                legend: {
                    position: 'labeled',
                    textStyle: {
                        fontSize: getResponsiveFontSize()
                    }
                },
                chartArea: {
                    width: '90%',
                    height: '80%'
                },
                animation: {
                    startup: true,
                    duration: 1000,
                    easing: 'out'
                }
            };

            var chart = new google.visualization.PieChart(document.getElementById('piechart'));
            chart.draw(data, options);
        }
        
        function searchTable() {
            const input = document.getElementById("searchInput").value.toLowerCase();
            const table = document.getElementById("paymentTable");
            const tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                const tdName = tr[i].getElementsByTagName("td")[1]; // Tenant Name
                const tdDate = tr[i].getElementsByTagName("td")[2]; // Payment Date

                if (tdName && tdDate) {
                    const nameText = tdName.textContent.toLowerCase();
                    const dateText = tdDate.textContent.toLowerCase();

                    if (nameText.includes(input) || dateText.includes(input)) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
        
        function toggleSidebar() {
            const sidebar = document.querySelector('.sideBar');
            sidebar.classList.toggle('active');
        }
        
        // Set current month name
        window.onload = function() {
            const months = [
                'January', 'February', 'March', 'April', 'May', 'June', 
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            const currentDate = new Date();
            const monthName = months[currentDate.getMonth()];
            const year = currentDate.getFullYear();
            document.getElementById('RevenuecurrentMonth').textContent = `${monthName} ${year}`;
        };
    </script>
</body>
</html>