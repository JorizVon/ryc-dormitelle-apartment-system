<?php
date_default_timezone_set('Asia/Manila');
$host = 'localhost';
$db = 'ryc_dormitelle';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch tenant info
$tenant_sql = "SELECT tenants.tenant_ID, tenant_name FROM tenants";
$tenant_result = $conn->query($tenant_sql);

// Fetch occupied units
$unit_sql = "SELECT unit_no FROM units WHERE unit_status = 'Occupied'";
$unit_result = $conn->query($unit_sql);

$tenant_unit_sql = "SELECT tenants.tenant_ID, tenant_unit.unit_no, tenant_name, tenant_unit.lease_payment_due, tenant_unit.balance 
                    FROM tenants  
                    INNER JOIN tenant_unit ON tenants.tenant_ID = tenant_unit.tenant_ID";
$tenant_unit_result = $conn->query($tenant_unit_sql);

$data = [];
while ($row = $tenant_unit_result->fetch_assoc()) {
    $data[] = $row;
}

// Get today's date in YYYYMMDD format
$today = date('Ymd');

// Query to count today's transactions (only date part, ignoring the time)
$count_sql = "SELECT COUNT(*) as count FROM payments WHERE DATE(payment_date) = CURDATE()";
$count_result = $conn->query($count_sql);
$count_row = $count_result->fetch_assoc();
$count_today = $count_row['count'] + 1;

// Format the counter (4-digit, padded with 0s)
$counter = str_pad($count_today, 4, '0', STR_PAD_LEFT);

// Combine date and counter to form transaction number
$transaction_no = $today . $counter;

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
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
        }
        .tenantPaymentmanagement {
            display: flex;
            justify-content: space-between;
            width: 100%;
            align-items: center;
        }
        .tenantPaymentmanagement h4 {
            color: #01214B;
            font-size: 32px;
            margin-left: 60px;
            height: 20px;
            align-items: center;
        }
        .tenantInfoandGraphs {
            display: flex;
            justify-content: space-between;
            height: 500px;
            width: 90%;
            margin-left: 60px;
        }
        .tenantInfoInput {
            width: 100%;
            height: 98%;
            margin: auto 0px;
            position: relative;
            bottom: 20px;
            border: 2px solid #A6DDFF;
            background-color: #FFFF;
        }
        .tenantInfoInput h3 {
            width: 100%;
            display: flex;
            height: 10px;
            padding-bottom: 12px;
            justify-content: center;
            align-items: center;
            position: relative;
            bottom: 5px;
            border-bottom: 2px solid #A6DDFF;
        }
        .formContainer {
            width: 100%;
            height: 480px;
            margin-top: 5px;
            padding: 10px 0px;
            margin-left: 20px;
            position: relative;
            bottom: 25px;
        }
        label {
            display: inline-block;
            width: 150px;
            margin-bottom: 10px;
            padding: 2px;
            vertical-align: top;
            color: #004AAD;
        }
        input[type="text"],
        input[type="date"]
        {
            width: 310px;
            padding: 2px;
            margin-bottom: 10px;
        }
        select {
            width: 317px;
            padding: 2px;
            margin-bottom: 15px;
        }
        .buttonContainer {
            width: 100%;
            display: flex;
            align-items: center;
            margin-left: 185px;
            margin-top: 7px;
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
        .buttonContainer button:hover .printReceiptIcon {
            content: url('otherIcons/printIconblue.png');
        }
        .graphsplacement {
            width: 100%;
            height: 400px;
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
        .footbtnContainer {
            width: 90%;
            height: 20px;
            margin-left: 60px;
            display: flex;
            position: relative;
            top: 12px;
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
        .transactionHistorybtn {
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
                    <a href="DASHBOARD.html" class="changeicon">
                        <img src="sidebarIcons/DashboardIconWht.png" alt="Dashboard Icon" class="DsidebarIcon" style="margin-right: 10px;">
                        Dashboard
                    </a>
                </div>
                <div class="card">
                    <a href="UNITSINFORMATION.html">
                        <img src="sidebarIcons/UnitsInfoIconWht.png" alt="Units Information Icon" class="UIsidebarIcon" style="margin-right: 10px;">
                        Units Information</a>
                </div>
                <div class="card">
                    <a href="TENANTSLIST.html">
                        <img src="sidebarIcons/TenantsInfoIconWht.png" alt="Tenants Information Icon" class="THsidebarIcon" style="margin-right: 10px;">
                        Tenants List</a>
                </div>
                <div class="card">
                    <a href="PAYMENTMANAGEMENT.html" style="background-color: #FFFF; color: #004AAD;">
                        <img src="sidebarIcons/PaymentManagementIcon.png" alt="Payment Management Icon" class="PMsidebarIcon" style="margin-right: 10px;">
                        Payment Management</a>
                </div>
                <div class="card">
                    <a href="ACCESSPOINTLOGS.html">
                        <img src="sidebarIcons/AccesspointIconWht.png" alt="Access Point Logs Icon" class="APLsidebarIcon" style="margin-right: 10px;">
                        Access Point Logs</a>
                </div>
                <div class="card">
                    <a href="CARDREGISTRATION.html">
                        <img src="sidebarIcons/CardregisterIconWht.png" alt="Card Registration Icon" class="CGsidebarIcon" style="margin-right: 10px;">
                        Card Registration</a>
                </div>
                
            </div>
        </div>
            <div class="mainBody">
                <div class="header">
                    <div class="headerContent">
                        <a href="ADMINPROFILE.html" class="adminTitle">ADMIN</a>
                        <p class="adminLogoutspace">&nbsp;|&nbsp;</p>
                        <a href="ADMINLOGIN.html" class="logOutbtn">Log Out</a>
                    </div>
                </div>
                <div class="mainContent">
                    <div class="tenantPaymentmanagement">
                        <h4>Payment Management</h4>
                    </div>
                    <div class="tenantInfoandGraphs">
                    <form method="POST" action="">
                        <div class="tenantInfoInput">
                            <h3>Tenants Information</h3>
                            <div class="formContainer">
                                <div>
                                    <label for="transactionNum">Transaction No.</label>
                                    <input style="border: none; font-weight: bold;" type="text" name="transactionNum" id="transaction_no" value="<?= $transaction_no ?>" readonly>
                                </div>
                                <div>
                                    <label for="tenantID">Tenant ID</label>
                                    <select name="tenantID" id="tenant_ID" onchange="updateFields('tenant_ID', this.value)">
                                        <option value="">-- Select Tenant ID --</option>
                                        <?php foreach ($data as $row): ?>
                                            <option value="<?= htmlspecialchars($row['tenant_ID']) ?>"><?= htmlspecialchars($row['tenant_ID']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label for="tenantName">Tenant Name</label>
                                    <select name="tenantName" id="tenant_name" onchange="updateFields('tenant_name', this.value)">
                                        <option value="">-- Select Tenant Name --</option>
                                        <?php foreach ($data as $row): ?>
                                            <option value="<?= htmlspecialchars($row['tenant_name']) ?>"><?= htmlspecialchars($row['tenant_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label for="unitNum">Unit No.</label>
                                    <select name="unitNum" id="unit_no" onchange="updateFields('unit_no', this.value)">
                                        <option value="">-- Select Unit No --</option>
                                        <?php foreach ($data as $row): ?>
                                            <option value="<?= htmlspecialchars($row['unit_no']) ?>"><?= htmlspecialchars($row['unit_no']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label for="payment_due">Payment Due</label>
                                    <input type="text" name="payment_due" id="payment_due" readonly>
                                </div>

                                <div>
                                    <label for="billing_period">Billing Period</label>
                                    <input type="text" name="billing_period" id="billing_period">
                                </div>

                                <div>
                                    <label for="balance">Balance</label>
                                    <input type="text" name="balance" id="balance" readonly>
                                </div>

                                <div>
                                    <label for="amount">Amount</label>
                                    <input type="text" name="amount" id="amount_paid">
                                </div>

                                <div>
                                    <label for="paymentDate">Payment Date</label>
                                    <input type="date" name="paymentDate" id="payment_date">
                                </div>

                                <div>
                                    <label for="payment_method">Payment Method</label>
                                    <select name="payment_method" id="payment_method">
                                        <option value="">-- Payment Method --</option>
                                        <option>Cash</option>
                                        <option>GCash</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="payment_status">Payment Status</label>
                                    <select name="payment_status" id="payment_status">
                                        <option value="">-- Payment Status --</option>
                                        <option>Fully Paid</option>
                                        <option>Paid Overdue</option>
                                        <option>Partially Paid</option>
                                    </select>
                                </div>

                                <div class="buttonContainer">
                                    <button type="button" id="printReceiptBtn">
                                        <img src="otherIcons/printIcon.png" alt="Print Icon" class="printReceiptIcon"> Print Receipt
                                    </button>
                                    <button type="submit">Confirm</button>
                                </div>
                            </div>
                        </div>
                    </form>
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
                    <div class="footbtnContainer">
                        <a href="DASHBOARD.html" class="backbtn">&#10558; Back</a>
                        <a href="TRANSACTIONHISTORY.html" class="transactionHistorybtn">View Transaction History</a>
                    </div>
                </div>
            </div>
        </div>
        <script>
            document.getElementById("printReceiptBtn").addEventListener("click", function () {
                const { jsPDF } = window.jspdf;
        
                // Get values from the form
                const transactionNum = document.querySelector("span[name='transactionNum']").textContent.trim();
                const tenantName = document.querySelector("input[name='tenantName']").value.trim();
                const tenantID = document.querySelector("input[name='tenantID']").value.trim();
                const unitNum = document.querySelector("input[name='unitNum']").value.trim();
                const amount = document.querySelector("input[name='amount']").value.trim();
                const paymentDate = document.querySelector("input[name='paymentDate']").value;
                const paymentDue = document.querySelector("input[name='payment_due']").value.trim();
                const billingPeriod = document.querySelector("input[name='billing_period']").value.trim();
                const balance = document.querySelector("input[name='balance']").value.trim();
                const paymentStatus = document.getElementById("payment_status").value;
        
                // Validation
                if (!tenantName || !tenantID || !unitNum || !amount || isNaN(amount) || !paymentDate) {
                    alert("Please fill in all required fields correctly.");
                    return;
                }
        
                const formattedDate = new Date(paymentDate).toISOString().split("T")[0].replace(/-/g, "/");
                const doc = new jsPDF();
        
                // Header
                doc.setFont("helvetica", "normal");
                doc.setFontSize(16);
                doc.setTextColor(0, 0, 255);
                doc.text("RYC Dormitelle", 105, 20, null, null, "center");
        
                doc.setFontSize(10);
                doc.setTextColor(0, 0, 0);
                doc.text("APARTMENT MANAGEMENT SYSTEM", 105, 26, null, null, "center");
                doc.text("Pasig Daet, Camarines Norte", 105, 32, null, null, "center");
                doc.text("Contact No: +6398561002586 / 6398561002586", 105, 38, null, null, "center");
        
                doc.setFontSize(14);
                doc.setFont("helvetica", "bold");
                doc.text("RECEIPT", 105, 50, null, null, "center");
        
                // Tenant Name Highlight
                doc.setFont("helvetica", "bold");
                doc.setFillColor(220, 230, 241);
                doc.rect(15, 60, 180, 10, 'F');
                doc.setTextColor(0, 0, 0);
                doc.text(`Tenant Name: ${tenantName}`, 20, 67);
        
                const transactionText = `Transaction No: ${transactionNum}`;
                const textWidth = doc.getTextWidth(transactionText);
                const adjustedX = 195 - textWidth - 5;
                doc.text(transactionText, adjustedX, 67);
        
                // Info Box
                doc.setFont("helvetica", "normal");
                doc.setFontSize(12);
                doc.setLineWidth(0.2);
                doc.rect(15, 75, 180, 90);
        
                let y = 85;
                const gap = 10;
        
                const infoPairs = [
                    ["Tenant ID:", tenantID],
                    ["Unit No:", unitNum],
                    ["Payment Due:", paymentDue],
                    ["Billing Period:", billingPeriod],
                    ["Balance:", `₱ ${parseFloat(balance || 0).toLocaleString()}`],
                    ["Amount Paid:", `₱ ${parseFloat(amount).toLocaleString()}`],
                    ["Payment Date:", formattedDate],
                    ["Payment Status:", paymentStatus || "N/A"]
                ];
        
                infoPairs.forEach(([label, value]) => {
                    doc.setFont("helvetica", "normal");
                    doc.text(label, 20, y);
                    doc.setFont("helvetica", "bold");
                    doc.text(value, 60, y);
                    y += gap;
                });
        
                // Open PDF in a new tab
                const pdfBlobUrl = doc.output("bloburl");
                window.open(pdfBlobUrl, "_blank");
            });
        </script>             
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
        </script>
        <script>
            const tenantData = <?php echo json_encode($data); ?>;

            function updateFields(source, value) {
                const selected = tenantData.find(row => row[source] === value);
                if (!selected) return;

                document.getElementById('tenant_ID').value = selected.tenant_ID;
                document.getElementById('tenant_name').value = selected.tenant_name;
                document.getElementById('unit_no').value = selected.unit_no;
                document.getElementById('payment_due').value = selected.lease_payment_due;
                document.getElementById('balance').value = selected.balance;
            }
        </script>

    </body>
</html>