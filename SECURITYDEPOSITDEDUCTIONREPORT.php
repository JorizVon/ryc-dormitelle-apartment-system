<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php");
    exit();
}

require_once 'db_connect.php';
require_once __DIR__ . "/dompdf/autoload.inc.php";
use Dompdf\Dompdf;

if (!isset($_GET['contract_id']) || !is_numeric($_GET['contract_id'])) {
    die("Error: A valid contract ID is required.");
}
$contract_id = (int)$_GET['contract_id'];

// --- DEBUG: Check what contract status we have ---
$debug_sql = "SELECT contract_id, email_account, contract_status, full_name 
              FROM contract_information 
              WHERE contract_id = ?";
$debug_stmt = $conn->prepare($debug_sql);
$debug_stmt->bind_param("i", $contract_id);
$debug_stmt->execute();
$debug_result = $debug_stmt->get_result();
$contract_info = $debug_result->fetch_assoc();
$debug_stmt->close();

if (!$contract_info) {
    die("Error: Contract ID $contract_id does not exist in the database.");
}

// Check if this is view-only mode based on URL parameter OR contract status
$view_only_param = isset($_GET['view_only']) && $_GET['view_only'] == '1';
$contract_status = $contract_info['contract_status'];
$view_only_status = ($contract_status != 'First Contract' && $contract_status != 'Contract Renewal' && $contract_status != 'pending');
$view_only = $view_only_param || $view_only_status;

// --- 1. FETCH PRIMARY TENANT AND CONTRACT DATA ---
$tenant_name = "N/A";
$unit_no = "N/A";
$security_deposit = 0.00;
$email_account = "";

// Try to get data from active tenant_unit first
$info_sql = "SELECT t.tenant_name, tu.unit_no, tu.security_deposit, ci.email_account
             FROM contract_information ci
             INNER JOIN tenant_unit tu ON ci.contract_id = tu.tu_ID
             INNER JOIN tenants t ON tu.tenant_ID = t.tenant_ID
             WHERE ci.contract_id = ? AND t.role = 'representative'
             LIMIT 1";

$stmt_info = $conn->prepare($info_sql);
if ($stmt_info) {
    $stmt_info->bind_param("i", $contract_id);
    $stmt_info->execute();
    $info_result = $stmt_info->get_result();
    
    if ($info_row = $info_result->fetch_assoc()) {
        // Found active tenant_unit record
        $tenant_name = $info_row['tenant_name'];
        $unit_no = $info_row['unit_no'];
        $security_deposit = (float)$info_row['security_deposit'];
        $email_account = $info_row['email_account'];
        $stmt_info->close();
    } else {
        // If no active tenant_unit, try to get from tenant_history (for ended contracts)
        $stmt_info->close();
        
        $history_sql = "SELECT th.name, th.unit_no, ci.security_deposit, ci.email_account, ci.full_name
                       FROM contract_information ci
                       LEFT JOIN tenant_history th ON ci.full_name = th.name
                       WHERE ci.contract_id = ?
                       LIMIT 1";
        
        $stmt_history = $conn->prepare($history_sql);
        $stmt_history->bind_param("i", $contract_id);
        $stmt_history->execute();
        $history_result = $stmt_history->get_result();
        
        if ($history_row = $history_result->fetch_assoc()) {
            $tenant_name = $history_row['name'] ?: $history_row['full_name'] ?: $contract_info['full_name'];
            $unit_no = $history_row['unit_no'] ?: "N/A";
            $security_deposit = (float)$history_row['security_deposit'];
            $email_account = $history_row['email_account'] ?: $contract_info['email_account'];
        } else {
            // Last resort: use contract_information data directly
            $tenant_name = $contract_info['full_name'];
            $email_account = $contract_info['email_account'];
            $security_deposit = 0.00;
            
            // Try to find unit_no from deduction_list if it exists
            $unit_check_sql = "SELECT unit_no FROM deduction_list WHERE unit_no IN (
                              SELECT unit_no FROM tenant_history WHERE name = ?) LIMIT 1";
            $unit_check_stmt = $conn->prepare($unit_check_sql);
            $unit_check_stmt->bind_param("s", $tenant_name);
            $unit_check_stmt->execute();
            $unit_check_result = $unit_check_stmt->get_result();
            if ($unit_check_row = $unit_check_result->fetch_assoc()) {
                $unit_no = $unit_check_row['unit_no'];
            }
            $unit_check_stmt->close();
        }
        $stmt_history->close();
    }
} else {
    die("Database error fetching tenant information: " . $conn->error);
}

// Use contract info as fallback
if (empty($tenant_name) || $tenant_name === "N/A") {
    $tenant_name = $contract_info['full_name'];
}
if (empty($email_account)) {
    $email_account = $contract_info['email_account'];
}

// --- 2. HANDLE FORM SUBMISSION (PRINT & SAVE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['print_deductions'])) {
    // A. Save new deductions to the database (only if not in view-only mode)
    if (!$view_only && isset($_POST['item_name']) && is_array($_POST['item_name'])) {
        $new_item_names = $_POST['item_name'];
        $new_item_amounts = $_POST['item_amount'];

        $insert_sql = "INSERT INTO `deduction_list` (`unit_no`, `item_name`, `item_amount`) VALUES (?, ?, ?)";
        $stmt_insert = $conn->prepare($insert_sql);
        
        if($stmt_insert) {
            foreach ($new_item_names as $index => $name) {
                $name = trim($name);
                $amount = isset($new_item_amounts[$index]) ? (float)preg_replace('/[^0-9.]/', '', $new_item_amounts[$index]) : 0;
                
                // UPDATED: Check for at least 1 letter (removed number requirement)
                $has_letter = preg_match('/[a-zA-Z]/', $name);

                if (!empty($name) && $amount > 0 && $has_letter) {
                    $stmt_insert->bind_param("ssd", $unit_no, $name, $amount);
                    $stmt_insert->execute();
                }
            }
            $stmt_insert->close();
        }
    }

    // B. Fetch all deductions for PDF generation
    $all_deductions_sql = "SELECT `item_name`, `item_amount` FROM `deduction_list` WHERE `unit_no` = ?";
    $stmt_pdf = $conn->prepare($all_deductions_sql);
    $stmt_pdf->bind_param("s", $unit_no);
    $stmt_pdf->execute();
    $all_deductions_result = $stmt_pdf->get_result();
    
    $total_deductions = 0;
    $deductions_html = "";
    while ($row = $all_deductions_result->fetch_assoc()) {
        $amount = (float)$row['item_amount'];
        $deductions_html .= "<tr><td>" . htmlspecialchars($row['item_name']) . "</td><td>Php " . number_format($amount, 2) . "</td></tr>";
        $total_deductions += $amount;
    }
    $stmt_pdf->close();
    $remaining_deposit = $security_deposit - $total_deductions;

    // C. Generate PDF
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; }
            h1 { text-align: center; color: #01214B; }
            .info { margin-bottom: 30px; }
            .info p { margin: 5px 0; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
            th { background-color: #e3f2fd; }
            .summary { margin-top: 30px; text-align: right; font-size: 16px; font-weight: bold; }
            .summary p { margin: 10px 0; }
        </style>
    </head>
    <body>
        <h1>Security Deposit Deduction Report</h1>
        <div class="info">
            <p><strong>Tenant Name:</strong> <?php echo htmlspecialchars($tenant_name); ?></p>
            <p><strong>Unit No:</strong> <?php echo htmlspecialchars($unit_no); ?></p>
            <p><strong>Security Deposit:</strong> Php <?php echo number_format($security_deposit, 2); ?></p>
        </div>
        
        <h2>Deductions</h2>
        <table>
            <thead>
                <tr>
                    <th>Item/Damage</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php echo $deductions_html; ?>
            </tbody>
        </table>
        
        <div class="summary">
            <p>Total Deductions: Php <?php echo number_format($total_deductions, 2); ?></p>
            <p>Remaining Security Deposit: Php <?php echo number_format($remaining_deposit, 2); ?></p>
        </div>
    </body>
    </html>
    <?php
    $html = ob_get_clean();

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("Deduction_Summary_" . str_replace(' ', '_', $tenant_name) . ".pdf", ["Attachment" => 1]);
    exit();
}

// --- 3. FETCH EXISTING DEDUCTIONS FOR DISPLAY ---
$existing_deductions = [];
if ($unit_no !== "N/A") {
    $deductions_sql = "SELECT `item_name`, `item_amount` FROM `deduction_list` WHERE `unit_no` = ?";
    $stmt_deductions = $conn->prepare($deductions_sql);
    if ($stmt_deductions) {
        $stmt_deductions->bind_param("s", $unit_no);
        $stmt_deductions->execute();
        $deductions_result = $stmt_deductions->get_result();
        while ($row = $deductions_result->fetch_assoc()) {
            $existing_deductions[] = $row;
        }
        $stmt_deductions->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $view_only ? 'View' : ''; ?> Deduction Summary - RYC Dormitelle</title>
    <link rel="stylesheet" href="layout.css">
    <style>
        .mainContent { padding: 20px; overflow-y: auto; }
        .form-wrapper { max-width: 900px; margin: 0 auto; border: 3px solid #A6DDFF; padding: 20px 40px 40px 40px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); background: #fff;}
        .tenant-info { margin-bottom: 20px; font-size: 16px; line-height: 1.8; color: #01214B; border-bottom: 1px solid #ccc; padding-bottom: 20px; }
        .tenant-info strong { color: #333; }
        h2 { text-align: center; color: #01214B; margin-bottom: 25px; }
        .deductions-container .deduction-row { display: flex; gap: 15px; margin-bottom: 15px; align-items: center; }
        .deductions-container input { flex-grow: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
        .deductions-container .item-name-input { width: 70%; }
        .deductions-container .amount-input { width: 25%; }
        .remove-btn { background-color: #dc3545; color: white; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-weight: bold; flex-shrink: 0; font-size: 18px; }
        .remove-btn:hover { background-color: #c82333; }
        .add-btn { background-color: #004AAD; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; margin-top: 10px; }
        .add-btn:hover { background-color: #003580; }
        .summary { margin-top: 30px; text-align: right; font-size: 18px; color: #01214B; }
        .summary p { margin: 10px 0; display:flex; justify-content: flex-end; gap: 40px; }
        .footbtnContainer { display: flex; justify-content: space-between; align-items: center; margin-top: 30px; }
        .print-btn, .backbtn { height: 40px; min-width: 110px; display: flex; align-items: center; justify-content: center; background-color: #004AAD; color: white; text-decoration: none; border-radius: 5px; font-size: 14px; padding: 0 15px; transition: all 0.3s ease; border:none; cursor:pointer;}
        .footbtnContainer a:hover, .footbtnContainer button:hover { background-color: white; color: #004AAD; border: 2px solid #004AAD; }
        
        /* View-only mode styling */
        .view-only-badge {
            display: inline-block;
            background-color: #6c757d;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            margin-left: 10px;
        }
        
        .no-deductions-message {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .form-wrapper { padding: 15px; }
            .deduction-row { flex-direction: column; }
            .deductions-container .item-name-input,
            .deductions-container .amount-input { width: 100%; }
            .summary { font-size: 14px; }
            .footbtnContainer { flex-direction: column; gap: 10px; }
            .backbtn, .print-btn { width: 100%; }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.html'; ?>
    <div class="mainBody">
        <?php include 'header.php'; ?>
        <div class="mainContent">
            <h4>
                Security Deposit Deductions
                <?php if ($view_only): ?>
                    <span class="view-only-badge">View Only</span>
                <?php endif; ?>
            </h4>
            <form method="POST" action="SECURITYDEPOSITDEDUCTIONREPORT.php?contract_id=<?php echo $contract_id; ?><?php echo $view_only_param ? '&view_only=1' : ''; ?>">
                <div class="form-wrapper">
                    <div class="tenant-info">
                        <p><strong>Name:</strong> <span id="tenantName"><?php echo htmlspecialchars($tenant_name); ?></span></p>
                        <p><strong>Unit No.:</strong> <span id="unitNo"><?php echo htmlspecialchars($unit_no); ?></span></p>
                        <p><strong>Security Deposit:</strong> ₱<span id="securityDeposit"><?php echo number_format($security_deposit, 2); ?></span></p>
                    </div>

                    <h2>Damage/Deduction Summary</h2>

                    <div id="deductions-container" class="deductions-container">
                        <?php if (!empty($existing_deductions)): ?>
                            <?php foreach ($existing_deductions as $deduction): ?>
                                <div class="deduction-row">
                                    <input type="text" class="item-name-input" value="<?php echo htmlspecialchars($deduction['item_name']); ?>" readonly>
                                    <input type="text" class="amount-input" value="<?php echo number_format((float)$deduction['item_amount'], 2); ?>" readonly>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-deductions-message">
                                No deductions have been recorded for this contract.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!$view_only): ?>
                        <button type="button" class="add-btn" id="addDeductionBtn">+ Add Deduction</button>
                    <?php endif; ?>

                    <div class="summary">
                        <p><span>Total Deductions:</span> <span id="totalDeductions">₱0.00</span></p>
                        <p><span>Remaining Security Deposit:</span> <span id="remainingDeposit">₱0.00</span></p>
                    </div>

                    <div class="footbtnContainer">
                        <?php if ($view_only_param): ?>
                            <a href="CONTRACTHISTORY.php" class="backbtn">&#10558; Back</a>
                        <?php else: ?>
                            <a href="CONTRACTMANAGEMENT.php" class="backbtn">&#10558; Back</a>
                        <?php endif; ?>
                        <button type="submit" name="print_deductions" class="print-btn">Print Summary</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deductionsContainer = document.getElementById('deductions-container');
            const addDeductionBtn = document.getElementById('addDeductionBtn');
            const securityDeposit = parseFloat(document.getElementById('securityDeposit').textContent.replace(/,/g, ''));
            const viewOnly = <?php echo $view_only ? 'true' : 'false'; ?>;

            function calculateTotals() {
                let total = 0;
                const amountInputs = deductionsContainer.querySelectorAll('.amount-input');
                
                amountInputs.forEach(input => {
                    const cleanValue = input.value.replace(/[^0-9.]/g, '');
                    const value = parseFloat(cleanValue);
                    if (!isNaN(value)) {
                        total += value;
                    }
                });

                const remaining = securityDeposit - total;
                document.getElementById('totalDeductions').textContent = `₱${total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                document.getElementById('remainingDeposit').textContent = `₱${remaining.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            }

            // Only enable add/remove functionality if not in view-only mode
            if (!viewOnly && addDeductionBtn) {
                addDeductionBtn.addEventListener('click', function() {
                    const newRow = document.createElement('div');
                    newRow.className = 'deduction-row';
                    // UPDATED HERE: Regex now only checks for at least one letter
                    newRow.innerHTML = `
                        <input type="text" name="item_name[]" class="item-name-input" 
                               placeholder="Item/Damage Description" 
                               pattern=".*[a-zA-Z].*"
                               title="Description must contain at least one letter"
                               required>
                        <input type="number" name="item_amount[]" class="amount-input" placeholder="0.00" step="0.01" min="0" required>
                        <button type="button" class="remove-btn">×</button>
                    `;
                    deductionsContainer.appendChild(newRow);
                });

                deductionsContainer.addEventListener('click', function(e) {
                    if (e.target.classList.contains('remove-btn')) {
                        e.target.parentElement.remove();
                        calculateTotals();
                    }
                });

                deductionsContainer.addEventListener('input', function(e) {
                    if (e.target.classList.contains('amount-input')) {
                        calculateTotals();
                    }
                });
            }

            // Initial calculation on page load to include existing deductions
            calculateTotals();
        });
    </script>
</body>
</html>