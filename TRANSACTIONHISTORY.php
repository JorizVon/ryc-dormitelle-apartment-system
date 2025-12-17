<?php
session_start(); // Start session at the very beginning

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php");
    exit();
}

// Assuming db_connect.php is in the same directory
require_once 'db_connect.php';

date_default_timezone_set('Asia/Manila'); // Set timezone for date formatting

// Initialize variables
$result = null;
$query_error = "";

// Check if database connection exists
if (!isset($conn) || $conn->connect_error) {
    $query_error = "Database connection failed: " . (isset($conn) ? $conn->connect_error : "Connection object not found");
} else {
    // SQL query
    $sql = "SELECT 
                p.transaction_no, 
                t.tenant_name, 
                p.unit_no,
                p.amount_paid, 
                p.payment_date_time, 
                p.payment_method, 
                p.payment_status,
                p.reference_number
            FROM payments p
            LEFT JOIN tenants t ON t.tenant_ID = p.tenant_ID
            LEFT JOIN tenant_unit tu ON tu.tenant_ID = t.tenant_ID
            WHERE p.confirmation_status = 'Confirmed'
            ORDER BY p.payment_date_time DESC";

    $query_exec_result = $conn->query($sql);

    if ($query_exec_result === false) {
        $query_error = "Error fetching transaction history: " . $conn->error;
        error_log($query_error);
    } else {
        $result = $query_exec_result;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
    <title>Transaction History - RYC Dormitelle</title>
    
    <!-- Include the layout CSS -->
    <link rel="stylesheet" href="layout.css">
    
    <!-- Transaction History specific styles -->
    <style>
        .mainContent {
            padding: 20px;
            overflow-y: auto;
        }

        .tenantHistoryHead {
            display: flex;
            justify-content: right;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .searbar {
            height: 30px;
            width: 270px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 12px;
            padding: 0 10px;
            box-sizing: border-box;
        }

        ::placeholder {
            color: #B7B5B5;
            opacity: 1;
        }

        .table-container {
            max-width: 100%;
            margin: 0 auto;
            border: 3px solid #A6DDFF;
            border-radius: 8px;
            height: 57vh;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-scroll {
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
            background-color: white;
        }
        
        th, td {
            padding: 10px 12px;
            text-align: left;
            font-size: 13px;
            border-bottom: 1px solid #e0e0e0;
            white-space: nowrap;
            vertical-align: middle;
            line-height: 1.4;
        }
        
        th {
            background-color: #e3f2fd;
            font-weight: bold;
            position: sticky;
            top: 0;
            z-index: 1;
            font-size: 12px;
        }

        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 4px;
            margin: 10px auto;
            width: 90%;
            text-align: center;
        }
        
        .footbtnContainer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .backbtn,
        .addtenantbtn {
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #004AAD;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            padding: 0 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .backbtn {
            min-width: 110px;
        }

        .addtenantbtn {
            min-width: 150px;
        }

        .printTransactionHistory {
            height: 18px;
            width: 18px;
            margin-right: 8px;
        }

        .footbtnContainer a:hover,
        .footbtnContainer button:hover {
            background-color: white;
            color: #004AAD;
            border: 2px solid #004AAD;
        }

        .footbtnContainer button:hover .printTransactionHistory {
            content: url('otherIcons/printIconblue.png');
        }

        @media (max-width: 1024px) {
            .mainContent { padding: 15px; }
            .tenantHistoryHead { flex-direction: column; align-items: stretch; text-align: center; }
            .searbar { width: 100%; }
            .table-container { border-left: none; border-right: none; border-radius: 0; max-height: calc(100vh - 280px); }
            .footbtnContainer { flex-direction: column; align-items: center; }
            .backbtn { order: 2; width: 80%; max-width: 280px; }
            .addtenantbtn { order: 1; width: 80%; max-width: 250px; }
        }

        @media (max-width: 768px) {
            .mainContent { padding: 10px; }
            table th, table td { font-size: 11px; padding: 8px 5px; }
        }

        @media (max-width: 480px) {
            table th, table td { font-size: 10px; padding: 6px 3px; }
            .footbtnContainer { gap: 10px; }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.html'; ?>
    
    <div class="mainBody">
        <?php include 'header.php'; ?>
        
        <div class="mainContent">
            <h4>Transaction History</h4>
            
            <div class="tenantHistoryHead">
                <input type="text" id="searchInput" placeholder="Search Name, Date, Method, Status..." class="searbar" oninput="searchTransactionHistory()">
            </div>
            
            <?php if (!empty($query_error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($query_error); ?>
                    <br><small>Please check your database connection and table structure.</small>
                </div>
            <?php endif; ?>
            
            <div class="table-container">
                <div class="table-scroll">
                    <table id="transactionTable">
                        <thead>
                            <tr>
                                <th>Transaction No.</th>
                                <th>Tenant Name</th>
                                <th>Unit No.</th>
                                <th>Amount Paid (₱)</th>
                                <th>Payment Date & Time</th>
                                <th>Payment Method</th>
                                <th>Reference No.</th>
                                <th>Payment Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($query_error)) {
                                echo "<tr><td colspan='8' style='color:red; text-align:center;'>" . htmlspecialchars($query_error) . "</td></tr>";
                            } elseif ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $transaction_no = $row['transaction_no'] ?? 'N/A';
                                    $tenant_name = $row['tenant_name'] ?? 'N/A';
                                    $unit_no = $row['unit_no'] ?? 'N/A';
                                    $amount_paid = $row['amount_paid'] ?? 0;
                                    $payment_date_time = $row['payment_date_time'] ?? '';
                                    $payment_method = $row['payment_method'] ?? 'N/A';
                                    $reference_no = $row['reference_number'] ?? 'N/A';
                                    $payment_status = $row['payment_status'] ?? 'N/A';
                                    
                                    $formatted_date = 'N/A';
                                    if (!empty($payment_date_time)) {
                                        $date_obj = DateTime::createFromFormat('Y-m-d H:i:s', $payment_date_time);
                                        if ($date_obj !== false) {
                                            $formatted_date = $date_obj->format("M d, Y h:i A");
                                        } else {
                                            $formatted_date = htmlspecialchars($payment_date_time);
                                        }
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($transaction_no); ?></td>
                                        <td><?php echo htmlspecialchars($tenant_name); ?></td>
                                        <td><?php echo htmlspecialchars($unit_no); ?></td>
                                        <td><?php echo number_format((float)$amount_paid, 2); ?></td>
                                        <td><?php echo $formatted_date; ?></td>
                                        <td><?php echo htmlspecialchars($payment_method); ?></td>
                                        <td><?php echo htmlspecialchars($reference_no); ?></td>
                                        <td><?php echo htmlspecialchars($payment_status); ?></td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo "<tr><td colspan='8' style='text-align:center;'>No transaction records found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="footbtnContainer">
                <a href="PAYMENTMANAGEMENT.php" class="backbtn">⤾ Back</a>
                <button class="addtenantbtn" onclick="generateTransactionPDF()">
                    <img src="otherIcons/printIcon.png" alt="Print Icon" class="printTransactionHistory">
                    Print Report
                </button>
            </div>
        </div>
    </div>

    <script>
        function searchTransactionHistory() {
            const input = document.getElementById("searchInput").value.toLowerCase().trim();
            const table = document.getElementById("transactionTable");
            const tr = table.getElementsByTagName("tr");
            let found = false;

            for (let i = 1; i < tr.length; i++) {
                const row = tr[i];
                if (row.cells.length > 1) {
                    const tdTxNo = row.cells[0];
                    const tdName = row.cells[1];
                    const tdUnitNo = row.cells[2];
                    const tdDate = row.cells[4];
                    const tdMethod = row.cells[5];
                    const tdStatus = row.cells[7];
                    let rowVisible = false;

                    if (tdTxNo && tdTxNo.textContent.toLowerCase().includes(input)) rowVisible = true;
                    if (tdName && tdName.textContent.toLowerCase().includes(input)) rowVisible = true;
                    if (tdUnitNo && tdUnitNo.textContent.toLowerCase().includes(input)) rowVisible = true;
                    if (tdDate && tdDate.textContent.toLowerCase().includes(input)) rowVisible = true;
                    if (tdMethod && tdMethod.textContent.toLowerCase().includes(input)) rowVisible = true;
                    if (tdStatus && tdStatus.textContent.toLowerCase().includes(input)) rowVisible = true;

                    row.style.display = rowVisible ? "" : "none";
                    if (rowVisible) found = true;
                }
            }

            const noRecordsRow = table.querySelector('td[colspan="8"]');
            if (noRecordsRow) {
                const dataRowsPresent = Array.from(tr).slice(1).some(r => r.cells.length > 1);
                if (!found && input !== "" && dataRowsPresent) {
                    noRecordsRow.textContent = "No matching records found for your search.";
                    noRecordsRow.parentNode.style.display = "";
                } else if (input === "" && !dataRowsPresent) {
                    noRecordsRow.textContent = "No transaction records found.";
                    noRecordsRow.parentNode.style.display = "";
                } else if (input === "" && dataRowsPresent) {
                    noRecordsRow.parentNode.style.display = "none";
                } else if (!found && !dataRowsPresent && input !== "") {
                    noRecordsRow.textContent = "No matching records found for your search.";
                    noRecordsRow.parentNode.style.display = "";
                } else {
                    noRecordsRow.parentNode.style.display = "none";
                }
            }
        }

        function generateTransactionPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({ orientation: 'landscape' });
        
            // ----- PDF Header -----
            doc.setFontSize(18); 
            doc.setTextColor(0, 0, 255); 
            doc.text('RYC Dormitelle', doc.internal.pageSize.getWidth() / 2, 18, { align: 'center' });
        
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
            const pdfHead = [ 
                'Txn No.', 'Tenant Name', 'Unit', 'Amount', 'Payment Date & Time', 'Method', 'Status'
            ];
        
            for (let i = 1; i < table.rows.length; i++) {
                const row = table.rows[i];
                // Changed from 8 to 7 columns
                if (row.style.display !== "none" && row.cells.length >= 7) { 
                    const rowData = [];
                    // Changed loop from 8 to 7
                    for (let j = 0; j < 7; j++) {
                        rowData.push(row.cells[j].innerText.trim());
                    }
                    bodyRows.push(rowData);
                }
            }
        
            if (bodyRows.length === 0) {
                alert("No data to export in the current view.");
                return;
            }
        
            // ----- Calculate Centering -----
            // Updated column widths: 30+40+20+25+40+25+25 = 205
            const totalTableWidth = 205; 
            const pageWidth = doc.internal.pageSize.getWidth();
            const marginX = (pageWidth - totalTableWidth) / 2;
        
            // ----- Add Table to PDF -----
            doc.autoTable({
                startY: startYPosition,
                head: [pdfHead], 
                body: bodyRows,
                theme: 'plain',
                styles: { 
                    fontSize: 7,
                    cellPadding: {top: 2, right: 1.5, bottom: 2, left: 1.5},
                    valign: 'middle',
                    lineColor: [44, 62, 80],
                    lineWidth: 0.1,
                },
                headStyles: { 
                    fillColor: [0, 74, 173],
                    textColor: 255, 
                    fontSize: 7.5,
                    fontStyle: 'bold',
                    halign: 'center',
                    lineColor: [0, 74, 173],
                    lineWidth: 0.1
                },
                alternateRowStyles: {
                    fillColor: [245, 245, 245]
                },
                columnStyles: {
                    0: { cellWidth: 30, halign: 'left' },   // Txn No
                    1: { cellWidth: 40, overflow: 'ellipsize'}, // Tenant Name
                    2: { cellWidth: 20, halign: 'center' }, // Unit
                    3: { cellWidth: 25, halign: 'right' },  // Amount
                    4: { cellWidth: 40, halign: 'center' }, // Date & Time
                    5: { cellWidth: 25, halign: 'center' }, // Method
                    6: { cellWidth: 25, halign: 'center' }  // Status
                },
                margin: { top: 10, left: marginX },
                didDrawPage: function (data) {
                    let pageNumberText = 'Page ' + doc.internal.getNumberOfPages();
                    doc.setFontSize(8);
                    doc.setTextColor(100);
                    doc.text(pageNumberText, data.settings.margin.left, doc.internal.pageSize.height - 7);
                    
                    const generationDate = `Generated: ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}`;
                    doc.text(generationDate, marginX + totalTableWidth, doc.internal.pageSize.height - 7, { align: 'right'});
                }
            });
        
            const blobURL = doc.output('bloburl');
            window.open(blobURL, '_blank');
        }

        function toggleSidebar() {
            const sidebar = document.querySelector('.sideBar');
            const mainBody = document.querySelector('.mainBody');
            sidebar.classList.toggle('active');
            mainBody.classList.toggle('active');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const table = document.getElementById('transactionTable');
            const tbody = table.querySelector('tbody');
            const rowCount = tbody.querySelectorAll('tr').length;
            console.log('Transaction table loaded with ' + rowCount + ' rows');
        });
    </script>
    <?php include 'chatfunctions/CHAT_COMPONENT.php'; ?>
</body>
</html>
<?php
if (isset($conn)) {
    $conn->close();
}
?>