<?php
session_start();
// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php");
    exit();
}
require_once 'db_connect.php';

// Initialize variables
$result = null;
$query_error = "";
$tenants_data = [];

// Modified SQL query to get representatives with their unit information
$representative_sql = "SELECT tenants.tenant_ID, tenants.tenant_name, tenants.contact_no, 
                      tenants.permanent_address, tenants.ec_person, tenants.email,
                      tenants.ec_no, tenant_unit.start_date, tenant_unit.occupant_count,
                      tenant_unit.security_deposit, tenant_unit.balance, tenant_unit.status,
                      tenant_unit.unit_no, tenant_unit.payment_due, tenants.role, tenant_unit.total_rent_paid
               FROM tenants
               INNER JOIN tenant_unit ON tenants.tenant_ID = tenant_unit.tenant_ID
               WHERE tenants.role = 'representative'
               ORDER BY tenant_unit.unit_no";

// Execute the query for representatives
$representative_result = $conn->query($representative_sql);

// Check if the query was successful
if ($representative_result === false) {
    $query_error = "Error executing query: " . $conn->error;
    error_log($query_error);
} else {
    
    // ---------------------------------------------------------
    // PREPARE STATEMENT FOR PAYMENT CHECKLIST
    // ---------------------------------------------------------
    // We select the checklist items for the specific email
    // We ORDER BY monthly_due_dates ASC to ensure we check from oldest to newest
    $checklist_sql = "SELECT `checklist_ID`, `unit_no`, `email_account`, `monthly_due_dates`, `pay_status` 
                      FROM `payment_checklist`
                      INNER JOIN tenants ON payment_checklist.email_account = tenants.email
                      WHERE email_account = ?
                      ORDER BY monthly_due_dates ASC"; 
    
    $checklist_stmt = $conn->prepare($checklist_sql);
    
    // Prepare companion statement
    $companion_sql = "SELECT tenant_ID, tenant_name, contact_no, permanent_address, 
                     ec_person, ec_no, email, role
                     FROM tenants 
                     WHERE tenant_ID LIKE ? 
                     AND role = 'companion'
                     AND tenant_ID != ?";
    $companion_stmt = $conn->prepare($companion_sql);

    // Process representatives
    while ($rep_row = $representative_result->fetch_assoc()) {
        $unit_no = $rep_row['unit_no'];
        $rep_tenant_id = $rep_row['tenant_ID'];
        $rep_email = $rep_row['email'];
        
        // ---------------------------------------------------------
        // LOGIC TO GET 1ST UNPAID DUE DATE
        // ---------------------------------------------------------
        if ($checklist_stmt) {
            $checklist_stmt->bind_param("s", $rep_email);
            $checklist_stmt->execute();
            $checklist_result = $checklist_stmt->get_result();
            
            $target_date = null;
            
            // Loop through the checklist
            while ($check_row = $checklist_result->fetch_assoc()) {
                // Always update target_date to the current row's date.
                // If we finish the loop without finding an 'Unpaid', 
                // this will hold the LATEST date (even if it's paid).
                $target_date = $check_row['monthly_due_dates'];
                
                // If we find an 'Unpaid' status, this is the one we want.
                // Since we ordered ASC, this is the "1st unpaid".
                if (strcasecmp($check_row['pay_status'], 'Unpaid') == 0) {
                    // We found the first unpaid bill.
                    // $target_date is already set to this row's date above.
                    break; // EXIT the loop immediately.
                }
            }
            
            // If we found any checklist data, update the payment_due field
            if ($target_date !== null) {
                $rep_row['payment_due'] = $target_date;
            }
        }
        // ---------------------------------------------------------

        if (!isset($tenants_data[$unit_no])) {
            $tenants_data[$unit_no] = [
                'representative' => null,
                'companions' => []
            ];
        }
        
        // Store representative data
        $tenants_data[$unit_no]['representative'] = $rep_row;
        
        // Extract the tenant_ID prefix pattern
        $tenant_id_prefix = substr($rep_tenant_id, 0, -2);
        
        // Get companions matching the tenant_ID pattern
        $pattern = $tenant_id_prefix . '%';
        $companion_stmt->bind_param("ss", $pattern, $rep_tenant_id);
        $companion_stmt->execute();
        $companion_result = $companion_stmt->get_result();
        
        while ($companion_row = $companion_result->fetch_assoc()) {
            $tenants_data[$unit_no]['companions'][] = $companion_row;
        }
    }
    
    // Close statements
    if(isset($checklist_stmt)) $checklist_stmt->close();
    if(isset($companion_stmt)) $companion_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenants List - RYC Dormitelle</title>
    
    <!-- Include the layout CSS -->
    <link rel="stylesheet" href="layout.css">
    
    <!-- Tenants List specific styles -->
    <style>
        /* Tenants List Specific Styles */
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
        
        /* Dropdown styles */
        .dropdown-toggle {
            cursor: pointer;
            color: #004AAD;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
            width: 16px;
            text-align: center;
            user-select: none;
            transition: transform 0.3s ease;
            margin-right: 8px;
        }
        
        .dropdown-toggle.expanded {
            transform: rotate(90deg);
        }
        
        .dropdown-toggle:hover {
            color: #0066FF;
        }
        
        .dropdown-placeholder {
            display: inline-block;
            width: 16px;
            margin-right: 8px;
        }
        
        /* Companion rows styling */
        .companion-row {
            display: none;
            background-color: #f8f9fa;
        }
        
        .companion-row.show {
            display: table-row;
        }
        
        .companion-row td {
            color: #666;
            border-bottom: 1px solid #e9ecef;
        }
        
        .tenant-id-cell {
            position: relative;
        }
        
        .companion-row .tenant-id-cell {
            padding-left: 35px;
        }
        
        th:first-child {
            padding-left: 36px;
        }
        
        .footbtnContainer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .viewtenanthistory,
        .backbtn {
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #004AAD;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            padding: 0 15px;
            transition: all 0.3s ease;
        }

        .backbtn {
            min-width: 110px;
        }

        .viewtenanthistory {
            min-width: 200px;
        }

        .footbtnContainer a:hover {
            background-color: white;
            color: #004AAD;
            border: 2px solid #004AAD;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .mainContent {
                padding: 15px;
            }

            .tenantHistoryHead {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }

            .searbar {
                width: 100%;
            }

            .table-container {
                border-left: none;
                border-right: none;
                border-radius: 0;
                max-height: calc(100vh - 280px);
            }

            .footbtnContainer {
                flex-direction: column;
                align-items: center;
            }

            .backbtn {
                order: 2;
                width: 80%;
                max-width: 280px;
            }

            .viewtenanthistory {
                order: 1;
                width: 80%;
                max-width: 250px;
            }
        }

        @media (max-width: 768px) {
            .mainContent {
                padding: 10px;
            }

            table th, table td {
                font-size: 11px;
                padding: 8px 5px;
            }
        }

        @media (max-width: 480px) {
            table th, table td {
                font-size: 10px;
                padding: 6px 3px;
            }

            .footbtnContainer {
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'sidebar.html'; ?>
    
    <div class="mainBody">
        <!-- Include Header -->
        <?php include 'header.php'; ?>
        
        <div class="mainContent">
            <h4>Tenants List</h4>
            
            <div class="tenantHistoryHead">
                <input type="text" id="searchInput" placeholder="Search" class="searbar">
            </div>
            
            <div class="table-container">
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Tenant ID</th>
                                <th>Name</th>
                                <th>Contact Number</th>
                                <th>Permanent Address</th>
                                <th>Emergency Person</th>
                                <th>Emergency Contact</th>
                                <th>Start Date</th>
                                <th>Occupant Count</th>
                                <th>Security Deposit (₱)</th>
                                <th>Total Rent Paid (₱)</th>
                                <th>Next Payment Due</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Check if there was a query error before trying to use data
                            if (!empty($query_error)) {
                                
                            } elseif (!empty($tenants_data)) {
                                foreach ($tenants_data as $unit_no => $unit_data) {
                                    $representative = $unit_data['representative'];
                                    $companions = $unit_data['companions'];
                                    
                                    if ($representative) {
                                        // Display representative row
                                        echo "<tr class='representative-row'>";
                                        
                                        // Tenant ID column with dropdown toggle if there are companions
                                        echo "<td class='tenant-id-cell'>";
                                        if (count($companions) > 0) {
                                            echo "<span class='dropdown-toggle' onclick='toggleCompanions(\"unit_" . $unit_no . "\")'>▶</span>";
                                        } else {
                                            echo "<span class='dropdown-placeholder'></span>";
                                        }
                                        echo htmlspecialchars($representative["tenant_ID"]) . "</td>";
                                        
                                        echo "<td>" . htmlspecialchars($representative["tenant_name"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($representative["contact_no"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($representative["permanent_address"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($representative["ec_person"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($representative["ec_no"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($representative["start_date"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($representative["occupant_count"]) . "</td>";
                                        echo "<td>" . number_format((float)$representative["security_deposit"], 2) . "</td>";
                                        echo "<td>" . htmlspecialchars($representative["total_rent_paid"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($representative["payment_due"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($representative["status"]) . "</td>";
                                        echo "</tr>";
                                        
                                        // Display companion rows (initially hidden)
                                        foreach ($companions as $companion) {
                                            echo "<tr class='companion-row unit_" . $unit_no . "'>";
                                            echo "<td class='tenant-id-cell'>" . htmlspecialchars($companion["tenant_ID"]) . "</td>";
                                            echo "<td>" . htmlspecialchars($companion["tenant_name"]) . "</td>";
                                            echo "<td>" . htmlspecialchars($companion["contact_no"]) . "</td>";
                                            echo "<td>" . htmlspecialchars($companion["permanent_address"]) . "</td>";
                                            echo "<td>" . htmlspecialchars($companion["ec_person"]) . "</td>";
                                            echo "<td>" . htmlspecialchars($companion["ec_no"]) . "</td>";
                                            echo "<td>---</td>"; 
                                            echo "<td>---</td>"; 
                                            echo "<td>---</td>"; 
                                            echo "<td>---</td>"; 
                                            echo "<td>---</td>";
                                            echo "</tr>";
                                        }
                                    }
                                }
                            } else {
                                // In the no tenants message:
                                echo "<tr><td colspan='12' style='text-align: center;'>No tenants found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="footbtnContainer">
                <a href="DASHBOARD.php" class="backbtn">⤾ Back</a>
                <a href="TENANTHISTORY.php" class="viewtenanthistory">View All Tenant History</a>
            </div>
        </div>
    </div>
    
    <script>
        // Function to toggle companion rows
        function toggleCompanions(unitClass) {
            const companionRows = document.querySelectorAll('.' + unitClass);
            const dropdownToggle = document.querySelector('[onclick="toggleCompanions(\'' + unitClass + '\')"]');
            
            companionRows.forEach(row => {
                row.classList.toggle('show');
            });
            
            // Rotate the dropdown arrow
            dropdownToggle.classList.toggle('expanded');
        }
        
        // Enhanced search functionality
        document.getElementById('searchInput').addEventListener('keyup', function () {
            const filter = this.value.toLowerCase().trim();
            const representativeRows = document.querySelectorAll('.representative-row');
            const companionRows = document.querySelectorAll('.companion-row');
            
            // Hide all companion rows first when searching
            companionRows.forEach(row => {
                row.classList.remove('show');
            });
            
            // Reset all dropdown toggles
            document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
                toggle.classList.remove('expanded');
            });

            representativeRows.forEach(row => {
                let rowVisible = false;
                let unitVisible = false;
                
                // Check if representative matches
                row.querySelectorAll('td').forEach(cell => {
                    if (cell.textContent.toLowerCase().includes(filter)) {
                        rowVisible = true;
                        unitVisible = true;
                    }
                });
                
                // Get the unit class for this representative
                const dropdownToggle = row.querySelector('.dropdown-toggle');
                if (dropdownToggle) {
                    const unitClass = dropdownToggle.getAttribute('onclick').match(/'([^']+)'/)[1];
                    const unitCompanions = document.querySelectorAll('.' + unitClass);
                    
                    // Check if any companion matches
                    unitCompanions.forEach(companionRow => {
                        let companionMatches = false;
                        companionRow.querySelectorAll('td').forEach(cell => {
                            if (cell.textContent.toLowerCase().includes(filter)) {
                                companionMatches = true;
                                unitVisible = true;
                            }
                        });
                        
                        // Show/hide individual companion based on match
                        if (filter === '' || companionMatches) {
                            companionRow.style.display = unitVisible ? '' : 'none';
                            if (companionMatches && filter !== '') {
                                companionRow.classList.add('show');
                                dropdownToggle.classList.add('expanded');
                            }
                        } else {
                            companionRow.style.display = 'none';
                        }
                    });
                }
                
                // Show/hide representative row
                row.style.display = unitVisible ? '' : 'none';
            });
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