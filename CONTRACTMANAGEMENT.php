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
$contracts_data = [];

// Handle end contract action - UPDATED to use contract_id and tu_ID
if (isset($_POST['end_contract']) && isset($_POST['contract_id'])) {
    $contract_id = $_POST['contract_id'];
    
    // Start a transaction for safety
    $conn->begin_transaction();

    try {
        // Step 1: Get email_account, tenant_ID, unit_no and tu_ID from contract_id
        $sql_find_info = "SELECT ci.email_account, t.tenant_ID, tu.unit_no, tu.tu_ID
                          FROM contract_information ci
                          INNER JOIN tenant_unit tu ON ci.contract_id = tu.tu_ID
                          INNER JOIN tenants t ON tu.tenant_ID = t.tenant_ID AND t.role = 'representative'
                          WHERE ci.contract_id = ? 
                          AND (ci.contract_status = 'First Contract' OR ci.contract_status = 'Contract Renewal')";
        $stmt_find = $conn->prepare($sql_find_info);
        $stmt_find->bind_param("i", $contract_id);
        $stmt_find->execute();
        $result = $stmt_find->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("No active contract found for contract_id: " . $contract_id);
        }
        
        $data = $result->fetch_assoc();
        $email_account = $data['email_account'];
        $unitNo = $data['unit_no'];
        $representativeTenantId = $data['tenant_ID'];
        $tu_ID = $data['tu_ID'];
        $stmt_find->close();

        // Step 2: Archive ALL tenants (representative + companions) who share the same email.
        // Get tenant_unit data first (from representative)
        $sql_get_tu_data = "SELECT unit_no, start_date, end_date FROM tenant_unit WHERE tu_ID = ?";
        $stmt_get_tu = $conn->prepare($sql_get_tu_data);
        $stmt_get_tu->bind_param("i", $tu_ID);
        $stmt_get_tu->execute();
        $tu_data_result = $stmt_get_tu->get_result();
        $tu_data = $tu_data_result->fetch_assoc();
        $stmt_get_tu->close();
        
        if (!$tu_data) {
            throw new Exception("Could not find tenant_unit data for tu_ID: " . $tu_ID);
        }
        
        // Archive ALL tenants with the same email (representative + companions)
        $sql_archive = "INSERT INTO tenant_history (unit_no, name, role, contact_no, permanent_address, emergency_person, emergency_contact, start_date, end_date)
                        SELECT
                            ?,
                            t.tenant_name,
                            t.role,
                            t.contact_no,
                            t.permanent_address,
                            t.ec_person,
                            t.ec_no,
                            ?,
                            ?
                        FROM
                            tenants AS t
                        WHERE
                            t.email = ?";
        
        $stmt_archive = $conn->prepare($sql_archive);
        $stmt_archive->bind_param("ssss", $tu_data['unit_no'], $tu_data['start_date'], $tu_data['end_date'], $email_account);
        
        if (!$stmt_archive->execute()) {
            throw new Exception("Failed to archive tenants with email: " . $email_account);
        }
        $archived_count = $stmt_archive->affected_rows;
        $stmt_archive->close();
        
        error_log("Archived $archived_count tenants with email $email_account to tenant_history");

        // Step 2.5: Update accounts table
        $sql_update_accounts = "UPDATE `accounts` SET `user_type` = 'user' WHERE `email_account` = ?";
        $stmt_update_accounts = $conn->prepare($sql_update_accounts);
        $stmt_update_accounts->bind_param("s", $email_account);
        
        if (!$stmt_update_accounts->execute()) {
            throw new Exception("Failed to update accounts for email: " . $email_account);
        }
        $stmt_update_accounts->close();

        // Step 3: Delete all tenants (representative + companions) who share the same email.
        $sql_delete_tenants = "DELETE FROM tenants WHERE email = ?";
        $stmt_delete_tenants = $conn->prepare($sql_delete_tenants);
        $stmt_delete_tenants->bind_param("s", $email_account);
        
        if (!$stmt_delete_tenants->execute()) {
            throw new Exception("Failed to delete tenants with email: " . $email_account);
        }
        $deleted_count = $stmt_delete_tenants->affected_rows;
        $stmt_delete_tenants->close();
        
        error_log("Deleted $deleted_count tenants with email $email_account");

        // Step 4: Delete the tenant_unit record using tu_ID
        $sql_delete_link = "DELETE FROM tenant_unit WHERE tu_ID = ?";
        $stmt_delete_link = $conn->prepare($sql_delete_link);
        $stmt_delete_link->bind_param("i", $tu_ID);
        $stmt_delete_link->execute();
        $stmt_delete_link->close();
        
        // Step 5: Update the unit's status to 'pending' instead of 'pending'
        $sql_update_unit = "UPDATE units SET unit_status = 'pending' WHERE unit_no = ?";
        $stmt_update_unit = $conn->prepare($sql_update_unit);
        $stmt_update_unit->bind_param("s", $unitNo);
        $stmt_update_unit->execute();
        $stmt_update_unit->close();

        // Step 6: Update the contract's status to 'Contract Ended' using contract_id
        $sql_update_contract = "UPDATE contract_information SET contract_status = 'Contract Ended' WHERE contract_id = ?";
        $stmt_update_contract = $conn->prepare($sql_update_contract);
        $stmt_update_contract->bind_param("i", $contract_id);
        $stmt_update_contract->execute();
        $stmt_update_contract->close();
        
        // Step 7: Deactivate RFID cards for that unit.
        $sql_deactivate_rfid = "UPDATE card_registration SET card_status = 'Deactivated' WHERE unit_no = ?";
        $stmt_deactivate_rfid = $conn->prepare($sql_deactivate_rfid);
        $stmt_deactivate_rfid->bind_param("s", $unitNo);
        $stmt_deactivate_rfid->execute();
        $stmt_deactivate_rfid->close();
        
        // Step 8: Delete payment checklist entries for the email account
        $sql_delete_checklist = "DELETE FROM payment_checklist WHERE email_account = ?";
        $stmt_delete_checklist = $conn->prepare($sql_delete_checklist);
        $stmt_delete_checklist->bind_param("s", $email_account);
        
        if (!$stmt_delete_checklist->execute()) {
            throw new Exception("Failed to delete payment checklist for email: " . $email_account);
        }
        $checklist_deleted = $stmt_delete_checklist->affected_rows;
        $stmt_delete_checklist->close();
        
        error_log("Deleted $checklist_deleted payment checklist entries for email $email_account");
        
        // If all steps were successful, commit the transaction
        $conn->commit();
        echo "<script>alert('Contract ended successfully! Unit " . $unitNo . " has been set to On Hold.'); window.location.href = 'CONTRACTMANAGEMENT.php';</script>";

    } catch (Exception $e) {
        // If any step failed, roll back all changes
        $conn->rollback();
        error_log("Error ending contract: " . $e->getMessage());
        echo "<script>alert('Error ending contract: " . htmlspecialchars($e->getMessage()) . "'); window.location.href = 'CONTRACTMANAGEMENT.php';</script>";
    }
    exit();
}

// Modified SQL query to get contracts that are not confirmed
$contracts_sql = "SELECT `contract_id`, `email_account`, `contract_date`, `full_name`, `citizenship`, 
                  `postal_address`, `contract_term`, `start_date`, `end_date`, `monthly_rate`, 
                  `security_deposit`, `contract_status` 
                  FROM `contract_information` 
                  WHERE contract_status = 'First Contract' OR contract_status = 'Contract Renewal'
                  ORDER BY contract_date DESC";

// Execute the query for contracts
$contracts_result = $conn->query($contracts_sql);

// Check if the query was successful
if ($contracts_result === false) {
    $query_error = "Error executing query: " . $conn->error;
    error_log($query_error);
} else {
    // Process contracts
    while ($contract_row = $contracts_result->fetch_assoc()) {
        $contracts_data[] = $contract_row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract Management - RYC Dormitelle</title>
    <link rel="icon" type="image/png" href="otherIcons/pageicon.png">
    
    <!-- Include the layout CSS -->
    <link rel="stylesheet" href="layout.css">
    
    <!-- Contract Management specific styles -->
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
            min-width: 100%;
        }
        
        th, td {
            padding: 8px 6px;
            text-align: left;
            font-size: 12px;
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
            font-size: 11px;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        
        .action-btn {
            padding: 4px 8px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 11px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            min-width: 60px;
        }
        
        .renewal-btn {
            background-color: #28a745;
            color: white;
        }
        
        .renewal-btn:hover {
            background-color: #218838;
        }
        
        .end-btn {
            background-color: #dc3545;
            color: white;
        }
        
        .end-btn:hover {
            background-color: #c82333;
        }
        
        .footbtnContainer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .viewcontracthistory,
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

        .viewcontracthistory {
            min-width: 200px;
        }

        .footbtnContainer a:hover {
            background-color: white;
            color: #004AAD;
            border: 2px solid #004AAD;
        }
        
        /* Status styling */
        .status-active {
            background-color: #d4edda;
            color: #155724;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
        }
        
        .status-renewal {
            background-color: #fff3cd;
            color: #856404;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
        }
        
        .status-ended {
            background-color: #f8d7da;
            color: #721c24;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
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

            table th, table td {
                font-size: 10px;
                padding: 6px 4px;
            }

            .action-btn {
                font-size: 10px;
                padding: 3px 6px;
                min-width: 50px;
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

            .viewcontracthistory {
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
                font-size: 9px;
                padding: 4px 3px;
            }

            .action-buttons {
                flex-direction: column;
                gap: 3px;
            }

            .action-btn {
                font-size: 9px;
                padding: 2px 4px;
            }
        }

        @media (max-width: 480px) {
            .footbtnContainer {
                gap: 10px;
            }
        }
        .deduction-btn {
            background-color: #ffc107; /* A yellow/orange color */
            color: black;
        }
        
        .deduction-btn:hover {
            background-color: #e0a800;
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
            <h4>Contract Management</h4>
            
            <div class="tenantHistoryHead">
                <input type="text" id="searchInput" placeholder="Search" class="searbar">
            </div>
            
            <div class="table-container">
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Ctr. ID</th>
                                <th>Contract Date</th>
                                <th>Email Account</th>
                                <th>Full Name</th>
                                <th>Citizenship</th>
                                <th>Postal Address</th>
                                <th>Contract Term</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Monthly Rate</th>
                                <th>Security Deposit</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Check if there was a query error before trying to use data
                            if (!empty($query_error)) {
                                echo "<tr><td colspan='13' style='color: red; text-align: center;'>Error loading contracts: " . htmlspecialchars($query_error) . "</td></tr>";
                            } elseif (!empty($contracts_data)) {
                                foreach ($contracts_data as $contract) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($contract["contract_id"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($contract["contract_date"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($contract["email_account"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($contract["full_name"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($contract["citizenship"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($contract["postal_address"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($contract["contract_term"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($contract["start_date"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($contract["end_date"]) . "</td>";
                                    echo "<td>₱" . number_format((float)$contract["monthly_rate"], 2) . "</td>";
                                    echo "<td>₱" . number_format((float)$contract["security_deposit"], 2) . "</td>";
                                    
                                    // Status with styling
                                    $status_class = '';
                                    switch(strtolower($contract["contract_status"])) {
                                        case 'confirmed':
                                            $status_class = 'status-active';
                                            break;
                                        case 'contract renewal':
                                            $status_class = 'status-renewal';
                                            break;
                                        case 'contract ended':
                                            $status_class = 'status-ended';
                                            break;
                                    }
                                    echo "<td><span class='" . $status_class . "'>" . htmlspecialchars($contract["contract_status"]) . "</span></td>";
                                    
                                    // Action buttons - UPDATED to use contract_id
                                    echo "<td>";
                                    echo "<div class='action-buttons'>";
                                    
                                    // Renewal button
                                    echo "<a href='CONTRACTINFORMATION.php?contract_id=" . $contract['contract_id'] . "' class='action-btn renewal-btn'>Renew</a>";
                                    // Deductions button
                                    echo "<a href='SECURITYDEPOSITDEDUCTIONREPORT.php?contract_id=" . $contract['contract_id'] . "' class='action-btn deduction-btn'>Deductions</a>";
                                    // End contract button
                                    echo "<form method='POST' style='display: inline;' onsubmit='return confirm(\"Are you sure you want to end this contract? This will:\\n\\n- Archive all tenants\\n- Delete tenant records\\n- Set unit to pending\\n- Deactivate RFID cards\\n\\nThis action cannot be undone!\")'>";
                                    echo "<input type='hidden' name='contract_id' value='" . $contract['contract_id'] . "'>";
                                    echo "<button type='submit' name='end_contract' class='action-btn end-btn'>End</button>";
                                    echo "</form>";
                                    
                                    echo "</div>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='13' style='text-align: center;'>No contracts found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="footbtnContainer">
                <a href="DASHBOARD.php" class="backbtn">⤾ Back</a>
                <a href="CONTRACTHISTORY.php" class="viewcontracthistory">
                    View All Contract History</a>
            </div>
        </div>
    </div>
    
    <script>
        // Enhanced search functionality
        document.getElementById('searchInput').addEventListener('keyup', function () {
            const filter = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('table tbody tr');

            rows.forEach(row => {
                let rowVisible = false;
                
                // Check if any cell in the row matches the search filter
                row.querySelectorAll('td').forEach(cell => {
                    if (cell.textContent.toLowerCase().includes(filter)) {
                        rowVisible = true;
                    }
                });
                
                // Show/hide row based on match
                row.style.display = rowVisible ? '' : 'none';
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