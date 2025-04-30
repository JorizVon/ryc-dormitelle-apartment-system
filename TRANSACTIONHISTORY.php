<?php

require_once 'db_connect.php';

// Handle search input
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// SQL query with optional search
$sql = "SELECT payments.transaction_no, tenants.tenant_name, tenant_unit.unit_no, tenant_unit.deposit, 
               payments.amount_paid, payments.payment_date, payments.billing_period, 
               payments.payment_method, payments.payment_status
        FROM payments
        INNER JOIN tenants ON tenants.tenant_ID = payments.tenant_ID
        INNER JOIN tenant_unit ON tenant_unit.tenant_ID = tenants.tenant_ID
        WHERE payments.confirmation_status = 'Confirmed'
        AND (
            tenants.tenant_name LIKE '%$search%' OR
            payments.payment_date LIKE '%$search%' OR
            payments.payment_method LIKE '%$search%' OR
            payments.payment_status LIKE '%$search%'
        )
        ORDER BY payments.payment_date DESC";

$result = $conn->query($sql);
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
        .transactionhistoryHead {
            display: flex;
            justify-content: space-between;
            width: 100%;
            align-items: center;
        }
        .transactionhistoryHead h4 {
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
            font-size: 16px;
            position: relative;
            top: 14px;
        }
        ::placeholder {
            color: #000000;
            opacity: 1;
        }
        .table_container {
            max-width: 90%;
            margin: 0 auto;
            border: 3px solid #A6DDFF;
            border-radius: 8px;
            height: 470px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .table-scroll {
            max-height: 500px;
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
            border-color: #A6DDFF;
            background-color: white;
        }
        th, td {
            padding: 10px 10px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background-color: #e3f2fd;
            font-weight: bold;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .action-btn {
            background-color: #2196f3;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .action-btn:hover {
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
            border-radius: 5px;
        }
        .addtenantbtn {
            height: 36px;
            width: 255px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            bottom: 20px;
            background-color: #004AAD;
            color: #FFFFFF;
            border-radius: 5px;
            text-decoration: none;
        }
        .printTransactionHistory {
            height: 20px;
            width: 20px;
            margin-right: 5px;
        }
        .footbtnContainer button:hover .printTransactionHistory {
            content: url('otherIcons/printIconblue.png');
        }
        .hamburger {
            visibility: hidden;
            width: 0px;
        }
        /* Mobile and Tablet Responsive */
        @media (max-width: 1024px) {
            body {
            display: flex;
            margin: 0;
            background-color: #FFFF;
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
                width: 100%;
                margin: 0 auto;
            }

            .transactionhistoryHead {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .transactionhistoryHead h4 {
                margin: 20px 0 0 15px;
                font-size: 30px;
            }

            .searbar {
                width: 90%;
                margin: 20px auto;
            }

            .table_container {
                width: 100%;
                overflow-x: auto;
            }

            table th, table td {
                font-size: 10px;
                padding: 15px 5px;
            }
            .action-btn {
                font-size: 10px;
                padding: 7px;
            }

            .footbtnContainer {
                flex-direction: column;
                align-items: center;
                gap: 15px;
                top: 10px;
                margin: 0 auto;
            }
            .addtenantbtn {
                font-size: 18px;
                padding: 5px 10px;
            }
            .backbtn {
                visibility: hidden;
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
            .table-scroll {
                width: 600px;
            }
            table th, table td {
                font-size: 10px;
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
                <div class="transactionhistoryHead">
                    <h4>Transaction History</h4>
                    <input type="text" id="searchInput" placeholder="Search" class="searbar" oninput="searchTransactionHistory()">
                </div>
                <div class="table_container">
                    <div class="table-scroll">
                        <table id="transactionTable">
                            <thead>
                                <tr>
                                    <th>Transaction No.</th>
                                    <th>Unit No.</th>
                                    <th>Name</th>
                                    <th>Current Deposit</th>
                                    <th>Amount</th>
                                    <th>Payment Date</th>
                                    <th>Billing Period</th>
                                    <th>Balance</th>
                                    <th>Payment Method</th>
                                    <th>Payment Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): 
                                        $balance = $row['deposit'] - $row['amount_paid'];
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['transaction_no']); ?></td>
                                            <td><?php echo htmlspecialchars($row['unit_no']); ?></td>
                                            <td><?php echo htmlspecialchars($row['tenant_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['deposit']); ?></td>
                                            <td><?php echo htmlspecialchars($row['amount_paid']); ?></td>
                                            <td><?php echo htmlspecialchars($row['payment_date']); ?></td>
                                            <td><?php echo htmlspecialchars($row['billing_period']); ?></td>
                                            <td><?php echo htmlspecialchars($balance); ?></td>
                                            <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                                            <td><?php echo htmlspecialchars($row['payment_status']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="10">No records found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                  <div class="footbtnContainer">
                    <a href="PAYMENTMANAGEMENT.php" class="backbtn">&#10558; Back</a>
                    <button class="addtenantbtn" onclick="generateTransactionPDF()">
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

            for (let i = 0; i < tr.length; i++) {
                const tdName = tr[i].getElementsByTagName("td")[2];  // Name
                const tdDate = tr[i].getElementsByTagName("td")[5];  // Payment Date
                const tdMethod = tr[i].getElementsByTagName("td")[8]; // Payment Method
                const tdStatus = tr[i].getElementsByTagName("td")[9]; // Payment Status

                if (tdName && tdDate && tdMethod && tdStatus) {
                    const nameText = tdName.textContent.toLowerCase();
                    const dateText = tdDate.textContent.toLowerCase();
                    const methodText = tdMethod.textContent.toLowerCase().trim();
                    const statusText = tdStatus.textContent.toLowerCase();

                    // Match payment method only if the whole word matches
                    const isMethodMatch = input === methodText;

                    if (
                        nameText.includes(input) ||
                        dateText.includes(input) ||
                        statusText.includes(input) ||
                        isMethodMatch
                    ) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
    </script>
    <script>
        function generateTransactionPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Header
            doc.setFontSize(16);
            doc.setTextColor(0, 0, 255); 
            doc.text('C A T I I S', 105, 15, { align: 'center' });

            doc.setFontSize(10);
            doc.setTextColor(0, 0, 0); 
            doc.text('APARTMENT MANAGEMENT SYSTEM', 105, 22, { align: 'center' });
            doc.text('Pasig Daet, Camarines Norte', 105, 27, { align: 'center' });
            doc.text('Contact No.: +6398561002586 / 6398561002586', 105, 32, { align: 'center' });

            doc.line(10, 36, 200, 36); 

            doc.setFontSize(14);
            doc.text('TRANSACTION REPORT', 105, 45, { align: 'center' });

            // Collect visible table data
            const table = document.getElementById("transactionTable");
            const rows = [];

            for (let i = 1; i < table.rows.length; i++) {
                const row = table.rows[i];
                if (row.style.display !== "none") {
                    rows.push([
                        row.cells[0].innerText, // Transaction No
                        row.cells[1].innerText, // Unit No
                        row.cells[4].innerText, // Amount
                        row.cells[5].innerText, // Payment Date
                        row.cells[7].innerText, // Balance
                        row.cells[8].innerText, // Payment Method
                        row.cells[9].innerText  // Payment Status
                    ]);
                }
            }

            if (rows.length === 0) {
                alert("No visible data to export.");
                return;
            }

            // Add the table
            doc.autoTable({
                startY: 55,
                head: [[
                    "Transaction No.",
                    "Unit No.",
                    "Amount",
                    "Payment Date",
                    "Balance",
                    "Payment Method",
                    "Payment Status"
                ]],
                body: rows,
                styles: { fontSize: 9, cellPadding: 2 },
                headStyles: { fillColor: [0, 0, 0], textColor: 255 }
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
