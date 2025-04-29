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
            content: url('sidebarIcons/PaymentManagementIconWht.png');
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
            margin-left: 200px;
            border-style: solid;
            font-size: 16px;
            position: relative;
            top: 14px;
        }
        ::placeholder {
            color: #000000;
            opacity: 1;
        }
        .tenantInfoandGraphs {
            display: flex;
            justify-content: space-between;
            height: 450px;
            width: 94%;
            margin-left: 60px;
            position: relative;
            bottom: 10px;
        }
        .table_container {
            width: 65%;
            border: 3px solid #A6DDFF;
            border-radius: 8px;
            height: 470px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .table_scroll {
            max-height: 500px;
            overflow-y: auto;
            scrollbar-width: none;
        }
        .table_scroll::-webkit-scrollbar {
            display: none;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border-color: #A6DDFF;
            background-color: white;
        }
        th, td {
            padding: 15px 8px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
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
        }

        .action_btn:hover {
            background-color: #1976d2;
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
        .graphsplacement {
            width: 35%;
            height: 300px;
        }
        .monthlyRevenueTitle {
            display: flex;
            align-items: center;
            width: 100%;
            justify-content: center;
        }
        .monthlyRevenueTitle h3 {
            display: flex;
            height: 10px;
            padding-bottom: 12px;
            justify-content: center;
            align-items: center;
            position: relative;
            bottom: 2px;
        }
        .graphsplacement h4 {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            bottom: 45px;
        }
        .bargraphContainer {
            height: 150px;
            width: 100%;
            justify-content: center;
            display: flex;
        }
        .bargraph {
            width: 320px;
            height: 200px;
            position: relative;
            bottom: 65px;
        }
        .pieChartTitle {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            bottom: 30px;
            font-size: 16px;
        }
        .piechartContainer {
            height: 100px;
            width: 100%;
            justify-content: center;
            display: flex;
        }
        .pieChart {
            width: 360px;
            height: 220px;
            position: relative;
            bottom: 45px;
        }
        .footbtnContainer a:hover {
            background-color: #FFFFFF;
            color: #004AAD;
            border: 2px solid #004AAD;
        }
        .viewtransactionhistory {
            height: 36px;
            width: 255px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #004AAD;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 5px;
        }
        .viewtransactionHistory {
            height: 20px;
            width: 20px;
            margin-right: 5px;
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
                    <a href="ADMINLOGIN.php" class="logOutbtn">Log Out</a>
                </div>
            </div>
            <div class="mainContent">
                <div class="tenantHistoryHead">
                    <h4>Card Registration</h4>
                    <input type="text" placeholder="Search by name or date..." class="searbar" id="searchInput" onkeyup="searchTable()">
                </div>
                <div class="tenantInfoandGraphs">
                    <div class="table_container">
                        <div class="table_scroll">
                          <table id="paymentTable">
                            <thead>
                              <tr>
                                <th>Transaction<br>no.</th>
                                <th>Tenant<br>Name</th>
                                <th>Payment<br>Date</th>
                                <th>Current<br>Deposit</th>
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
                            <h3>Monthly Revenue Summary&#xa0;-&#xa0;</h3>
                            <h3>&#40;&#xa0;</h3>
                            <h3 id="RevenuecurrentMonth">March 2025</h3>
                            <h3>&#xa0;&#41;</h3>
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
            </div>
            <div class="footbtnContainer">
                <a href="DASHBOARD.php" class="backbtn">&#10558; Back</a>
                <a href="TRANSACTIONHISTORY.php" class="viewtransactionhistory" class="viewtransactionHistory">
                    Transaction History</a>
            </div>
        </div>
    </div>
    <script>
        google.charts.load('current', {packages: ['corechart', 'bar']});
        google.charts.setOnLoadCallback(drawBarChart);

        function drawBarChart() {
            var data = google.visualization.arrayToDataTable([
                ['Payment Status', 'Number of Tenants', { role: 'style' }],
                ['Fully Paid', <?php echo $fully_paid_count; ?>, 'color: green'],
                ['Paid Overdue', <?php echo $paid_overdue_count; ?>, 'color: red'],
                ['Partially Paid', <?php echo $partially_paid_count; ?>, 'color: orange']
            ]);

            var options = {
                title: '',
                hAxis: {
                    title: 'Payment Status',
                },
                vAxis: {
                    title: 'Number of Tenants'
                },
                legend: { position: 'none' }
            };

            var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
            chart.draw(data, options);
        }
    </script>
    <script type="text/javascript">
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawPieChart);

        function drawPieChart() {
            var data = google.visualization.arrayToDataTable([
                ['Mode of Payment', 'Percentage'],
                ['Gcash', <?php echo $gcash_count; ?>],
                ['Cash', <?php echo $cash_count; ?>]
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
                    position: 'labeled'
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
    </script>
</body>
</html>
<?php
$conn->close();
?>