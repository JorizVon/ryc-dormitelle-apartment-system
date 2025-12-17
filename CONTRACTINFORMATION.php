<?php
session_start();

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php");
    exit();
}

require_once 'db_connect.php';

// Initialize variables
$contract_data = [];
$message = "";
$message_type = "";

function generateMonthlyDueDates($start_date, $end_date) {
    $due_dates = [];
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    
    // Get the day from start_date to use as due day
    $due_day = (int)$start->format('j');
    
    // Get starting year and month
    $current_year = (int)$start->format('Y');
    $current_month = (int)$start->format('n');
    
    $end_year = (int)$end->format('Y');
    $end_month = (int)$end->format('n');
    
    // Generate dates for each month by iterating year/month separately
    while (($current_year < $end_year) || ($current_year == $end_year && $current_month <= $end_month)) {
        // Get the last day of the current month
        $last_day_of_month = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);
        
        // Use the due_day or last day of month, whichever is smaller
        $actual_day = min($due_day, $last_day_of_month);
        
        // Create the due date
        $due_date = new DateTime();
        $due_date->setDate($current_year, $current_month, $actual_day);
        
        // Only add if it's within the range
        if ($due_date >= $start && $due_date <= $end) {
            $due_dates[] = $due_date->format('Y-m-d');
        }
        
        // Move to next month
        $current_month++;
        if ($current_month > 12) {
            $current_month = 1;
            $current_year++;
        }
    }
    
    return $due_dates;
}

// Handle contract renewal submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['renew_contract'])) {
    $original_contract_id = $_POST['original_contract_id'];
    $email_account = $_POST['email_account'];
    $contract_date = $_POST['contract_date'];
    $full_name = $_POST['full_name'];
    $citizenship = $_POST['citizenship'];
    $postal_address = $_POST['postal_address'];
    $contract_term = $_POST['contract_term'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $monthly_rate = $_POST['monthly_rate'];
    $security_deposit = $_POST['security_deposit'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update the original contract status to "Contract Ended"
        $update_sql = "UPDATE contract_information SET contract_status = 'Contract Ended' WHERE contract_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $original_contract_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        // Format contract_term properly before inserting
        $contract_term_formatted = $contract_term;
        if ($contract_term == 12) {
            $contract_term_formatted = "1 YEAR";
        } elseif ($contract_term == 24) {
            $contract_term_formatted = "2 YEARS";
        } elseif ($contract_term == 36) {
            $contract_term_formatted = "3 YEARS";
        } elseif ($contract_term == 48) {
            $contract_term_formatted = "4 YEARS";
        } elseif ($contract_term == 60) {
            $contract_term_formatted = "5 YEARS";
        } else {
            $contract_term_formatted = $contract_term . " MONTHS";
        }
        
        // Insert new contract with "Contract Renewal" status
        $insert_sql = "INSERT INTO contract_information (email_account, contract_date, full_name, citizenship, postal_address, contract_term, start_date, end_date, monthly_rate, security_deposit, contract_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Contract Renewal')";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ssssssssdd", $email_account, $contract_date, $full_name, $citizenship, $postal_address, $contract_term_formatted, $start_date, $end_date, $monthly_rate, $security_deposit);
        $insert_stmt->execute();
        $new_contract_id = $conn->insert_id; // Get the new contract_id
        $insert_stmt->close();
        
        // Get existing tenant_unit data linked to the original contract
        $get_tu_sql = "SELECT tu.`tenant_ID`, tu.`unit_no`, tu.`occupant_count`, tu.`security_deposit`, 
                              tu.`balance`, tu.`payment_due`, tu.`billing_period`, tu.`total_rent_paid`, 
                              tu.`last_billing_date`, tu.`status`
                       FROM tenant_unit tu
                       INNER JOIN contract_information ci ON tu.tu_ID = ci.contract_id
                       WHERE ci.contract_id = ?";
        $get_tu_stmt = $conn->prepare($get_tu_sql);
        $get_tu_stmt->bind_param("i", $original_contract_id);
        $get_tu_stmt->execute();
        $tu_result = $get_tu_stmt->get_result();
        
        if ($tu_result->num_rows > 0) {
            $tu_data = $tu_result->fetch_assoc();
            $unit_no = $tu_data['unit_no']; // Store unit_no for later use
            $get_tu_stmt->close();
            
            // CRITICAL FIX: Get the CURRENT representative tenant_ID from tenants table
            // This ensures we use the existing tenant_ID, not create a new one
            $get_current_tenant_sql = "SELECT tenant_ID FROM tenants WHERE email = ? AND role = 'representative' LIMIT 1";
            $get_current_tenant_stmt = $conn->prepare($get_current_tenant_sql);
            $get_current_tenant_stmt->bind_param("s", $email_account);
            $get_current_tenant_stmt->execute();
            $current_tenant_result = $get_current_tenant_stmt->get_result();
            
            if ($current_tenant_result->num_rows === 0) {
                $get_current_tenant_stmt->close();
                throw new Exception("No representative tenant found for email: " . $email_account);
            }
            
            $current_tenant_data = $current_tenant_result->fetch_assoc();
            $current_representative_tenant_id = $current_tenant_data['tenant_ID'];
            $get_current_tenant_stmt->close();
            
            // Insert new tenant_unit record with the new contract_id as tu_ID
            // BUT use the EXISTING tenant_ID from the tenants table
            $insert_tu_sql = "INSERT INTO tenant_unit (tu_ID, tenant_ID, unit_no, start_date, end_date, 
                                                       occupant_count, security_deposit, balance, payment_due, 
                                                       billing_period, total_rent_paid, last_billing_date, status) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insert_tu_stmt = $conn->prepare($insert_tu_sql);
            $insert_tu_stmt->bind_param("issssiddssdss", 
                $new_contract_id, // Use new contract_id as tu_ID
                $current_representative_tenant_id, // Use CURRENT tenant_ID, not old one
                $tu_data['unit_no'],
                $start_date, // New start date
                $end_date,   // New end date
                $tu_data['occupant_count'],
                $tu_data['security_deposit'],
                $tu_data['balance'],
                $tu_data['payment_due'],
                $tu_data['billing_period'],
                $tu_data['total_rent_paid'],
                $tu_data['last_billing_date'],
                $tu_data['status']
            );
            $insert_tu_stmt->execute();
            $insert_tu_stmt->close();
            
            // Delete the old tenant_unit record
            $delete_tu_sql = "DELETE FROM tenant_unit WHERE tu_ID = ?";
            $delete_tu_stmt = $conn->prepare($delete_tu_sql);
            $delete_tu_stmt->bind_param("i", $original_contract_id);
            $delete_tu_stmt->execute();
            $delete_tu_stmt->close();
        } else {
            $get_tu_stmt->close();
            throw new Exception("No tenant_unit record found for the original contract.");
        }
        
        // Delete old payment_checklist entries for this email_account
        $delete_checklist_sql = "DELETE FROM payment_checklist WHERE email_account = ?";
        $delete_checklist_stmt = $conn->prepare($delete_checklist_sql);
        $delete_checklist_stmt->bind_param("s", $email_account);
        $delete_checklist_stmt->execute();
        $delete_checklist_stmt->close();
        
        // Generate new payment checklist entries with unit_no
        $monthly_due_dates = generateMonthlyDueDates($start_date, $end_date);
        
        foreach ($monthly_due_dates as $due_date) {
            $insert_checklist_sql = "INSERT INTO payment_checklist (email_account, unit_no, monthly_due_dates, pay_status) VALUES (?, ?, ?, 0)";
            $insert_checklist_stmt = $conn->prepare($insert_checklist_sql);
            $insert_checklist_stmt->bind_param("sss", $email_account, $unit_no, $due_date);
            $insert_checklist_stmt->execute();
            $insert_checklist_stmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        
        $message = "Contract renewed successfully!";
        $message_type = "success";
        
        // Redirect after success
        echo "<script>
                alert('Contract renewed successfully!');
                setTimeout(function() {
                    window.location.href = 'CONTRACTMANAGEMENT.php';
                }, 500);
              </script>";
        exit(); // Stop execution after redirect
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $message = "Error renewing contract: " . $e->getMessage();
        $message_type = "error";
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// Get contract data using contract_id (only fetch if not processing a renewal)
if (isset($_GET['contract_id']) && !isset($_POST['renew_contract'])) {
    $contract_id = $_GET['contract_id'];
    
    // Fetch contract data from database using contract_id
    $sql = "SELECT contract_id, email_account, contract_date, full_name, citizenship, postal_address, 
            contract_term, start_date, end_date, monthly_rate, security_deposit, contract_status
            FROM contract_information 
            WHERE contract_id = ? 
            AND (contract_status = 'First Contract' OR contract_status = 'Contract Renewal')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $contract_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $contract_data = $result->fetch_assoc();
        
        // Check for unpaid dues in payment_checklist
        $check_unpaid_sql = "SELECT COUNT(*) as unpaid_count 
                            FROM payment_checklist 
                            WHERE email_account = ? AND pay_status = 0";
        $check_stmt = $conn->prepare($check_unpaid_sql);
        $check_stmt->bind_param("s", $contract_data['email_account']);
        $check_stmt->execute();
        $unpaid_result = $check_stmt->get_result();
        $unpaid_data = $unpaid_result->fetch_assoc();
        $check_stmt->close();
        
        $contract_data['has_unpaid_dues'] = ($unpaid_data['unpaid_count'] > 0);
        $contract_data['unpaid_count'] = $unpaid_data['unpaid_count'];
        
        // If there are unpaid dues, show alert and redirect
        if ($contract_data['has_unpaid_dues']) {
            echo "<script>
                    alert('Contract renewal is not permitted. There are " . $unpaid_data['unpaid_count'] . " unpaid due(s) remaining. Please settle all outstanding payments before renewing the contract.');
                    window.location.href = 'CONTRACTMANAGEMENT.php';
                  </script>";
            exit();
        }
        
        // Calculate the new start date (day after current end_date)
        $current_end_date = new DateTime($contract_data['end_date']);
        $new_start_date = clone $current_end_date;
        $new_start_date->modify('+1 day');
        $contract_data['new_start_date'] = $new_start_date->format('Y-m-d');
        
        // Calculate initial end date based on contract term
        $contract_term_months = 12; // Default to 1 year
        if (strpos($contract_data['contract_term'], 'YEAR') !== false) {
            $years = (int)filter_var($contract_data['contract_term'], FILTER_SANITIZE_NUMBER_INT);
            $contract_term_months = $years * 12;
        } else {
            $contract_term_months = (int)filter_var($contract_data['contract_term'], FILTER_SANITIZE_NUMBER_INT);
        }
        
        $new_end_date = clone $new_start_date;
        $new_end_date->modify("+{$contract_term_months} months");
        $contract_data['new_end_date'] = $new_end_date->format('Y-m-d');
        
    } else {
        // Contract not found, redirect back
        echo "<script>alert('Contract not found or already ended.'); window.location.href = 'CONTRACTMANAGEMENT.php';</script>";
        exit();
    }
    $stmt->close();
} elseif (!isset($_GET['contract_id']) && !isset($_POST['renew_contract'])) {
    // Redirect back if no contract_id and not processing renewal
    header("Location: CONTRACTMANAGEMENT.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract Information - Renewal</title>
    <link rel="icon" type="image/png" href="otherIcons/pageicon.png">
    
    <!-- Include the layout CSS -->
    <link rel="stylesheet" href="layout.css">
    
    <!-- Contract Renewal specific styles -->
    <style>
        .mainContent {
            padding: 20px;
            overflow-y: auto;
        }

        .contract-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .contract-form-container h2 {
            color: #004AAD;
            font-size: 25px;
            margin: 0;
            padding: 15px;
            text-align: center;
        }

        .contract-form-container {
            max-width: 800px;
            margin: 0 auto;
            border: 3px solid #A6DDFF;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .photo-placeholder {
            width: 150px;
            height: 150px;
            border: 2px solid #ccc;
            margin: 0 auto 30px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 14px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            align-items: center;
        }

        .form-group {
            flex: 1;
            display: flex;
            align-items: center;
        }

        .form-group label {
            min-width: 120px;
            font-weight: 10000;
            color: #004AAD;
            margin-right: 10px;
        }

        .form-group input, .form-group select {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group input[readonly] {
            background-color: #f8f9fa;
            color: #666;
        }

        .editable-field {
            background-color: #fff !important;
            border-color: #004AAD !important;
        }

        .button-container {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            min-width: 120px;
        }

        .btn-back {
            background-color: #6c757d;
            color: white;
        }

        .btn-back:hover {
            background-color: #545b62;
        }

        .btn-renew {
            background-color: #28a745;
            color: white;
        }

        .btn-renew:hover {
            background-color: #218838;
        }

        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .mainContent {
                padding: 15px;
            }

            .form-row {
                flex-direction: column;
                gap: 15px;
            }

            .form-group {
                flex-direction: column;
                align-items: stretch;
            }

            .form-group label {
                min-width: auto;
                margin-bottom: 5px;
            }

            .button-container {
                flex-direction: column;
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            .contract-form-container {
                padding: 20px;
                margin: 0 10px;
            }

            .contract-form-container h2 {
                font-size: 24px;
                padding: 10px;
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
            <div class="contract-header">
                
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <div class="contract-form-container">
                <h2>Renew Contract</h2>
                
                <form method="POST" onsubmit="return confirm('Are you sure you want to renew this contract? This will:\n\n- End the current contract\n- Create a new contract with Contract Renewal status\n- Update tenant unit dates\n\nThis action cannot be undone!')">
                    <input type="hidden" name="original_contract_id" value="<?php echo htmlspecialchars($contract_data['contract_id']); ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contract_id">Contract ID:</label>
                            <input type="text" name="display_contract_id" id="contract_id" value="<?php echo htmlspecialchars($contract_data['contract_id']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="email_account">Email Account:</label>
                            <input type="email" name="email_account" id="email_account" value="<?php echo htmlspecialchars($contract_data['email_account']); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contract_date">Contract Date:</label>
                            <input type="date" name="contract_date" id="contract_date" value="<?php echo htmlspecialchars($contract_data['contract_date']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="full_name">Full Name:</label>
                            <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($contract_data['full_name']); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="citizenship">Citizenship:</label>
                            <input type="text" name="citizenship" id="citizenship" value="<?php echo htmlspecialchars($contract_data['citizenship']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="postal_address">Postal Address:</label>
                            <input type="text" name="postal_address" id="postal_address" value="<?php echo htmlspecialchars($contract_data['postal_address']); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="security_deposit">Security Deposit (₱):</label>
                            <input type="number" step="0.01" name="security_deposit" id="security_deposit" value="<?php echo htmlspecialchars($contract_data['security_deposit']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="monthly_rate">Monthly Rate (₱):</label>
                            <input type="number" step="0.01" name="monthly_rate" id="monthly_rate" value="<?php echo htmlspecialchars($contract_data['monthly_rate']); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contract_term">Contract Term:</label>
                            <select name="contract_term" id="contract_term" class="editable-field" required>
                                <option value="">Select Contract Term</option>
                                <option value="6">6 MONTHS</option>
                                <option value="7">7 MONTHS</option>
                                <option value="8">8 MONTHS</option>
                                <option value="9">9 MONTHS</option>
                                <option value="10">10 MONTHS</option>
                                <option value="11">11 MONTHS</option>
                                <option value="12">1 YEAR</option>
                                <option value="24">2 YEARS</option>
                                <option value="36">3 YEARS</option>
                                <option value="48">4 YEARS</option>
                                <option value="60">5 YEARS</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <!-- Empty space for layout balance -->
                        </div>
                    </div>
                    
                    <!-- Non-editable date fields -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date">New Start Date:</label>
                            <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($contract_data['new_start_date']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="end_date">New End Date:</label>
                            <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($contract_data['new_end_date']); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="button-container">
                        <a href="CONTRACTMANAGEMENT.php" class="btn btn-back">← Back</a>
                        <button type="submit" name="renew_contract" class="btn btn-renew">Renew Contract</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-calculate end date based on start date and contract term
        function calculateEndDate() {
            const startDateStr = document.getElementById('start_date').value;
            const contractTerm = parseInt(document.getElementById('contract_term').value);
            
            if (startDateStr && contractTerm) {
                const startDate = new Date(startDateStr);
                const endDate = new Date(startDate);
                endDate.setMonth(endDate.getMonth() + contractTerm);
                
                document.getElementById('end_date').value = endDate.toISOString().split('T')[0];
            }
        }
        
        // Add event listener only for contract term changes
        document.getElementById('contract_term').addEventListener('change', calculateEndDate);
        
        // Initialize end date on page load if contract term is already selected
        window.addEventListener('load', function() {
            if (document.getElementById('contract_term').value) {
                calculateEndDate();
            }
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