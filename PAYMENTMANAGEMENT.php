<?php
session_start();

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect to login if not logged in using 'email_account'
if (!isset($_SESSION['email_account'])) {
    header("Location: LOGIN.php");
    exit();
}

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Use absolute path or check if file exists
$db_connect_path = __DIR__ . '/db_connect.php';
if (!file_exists($db_connect_path)) {
    die("Database connection file not found at: " . $db_connect_path);
}
require_once $db_connect_path;

// Check database connection
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . (isset($conn) ? $conn->connect_error : "Connection object not found"));
}

// Set MySQL timezone to Philippines time
$conn->query("SET time_zone = '+08:00'");

// Initialize variables
$table_result = null;
$query_error_table = ""; 

// Get current date for defaults
$current_month = date('n');
$current_year = date('Y');
$current_month_name = date('F');

// Get filter parameters from URL
$filter_month = isset($_GET['month']) ? (int)$_GET['month'] : $current_month;
$filter_year = isset($_GET['year']) ? (int)$_GET['year'] : $current_year;
$filter_month_name = date('F', mktime(0, 0, 0, $filter_month, 1));

// Main table query - Updated to include payment_option
$table_query = "SELECT 
    payments.transaction_no, 
    tenants.tenant_name,
    tenants.email,
    payments.payment_date_time,
    tenant_unit.security_deposit, 
    payments.amount_paid, 
    tenant_unit.balance, 
    payments.payment_method, 
    payments.confirmation_status,
    payments.transaction_type,
    payments.unit_no,
    payments.payment_option
FROM payments 
INNER JOIN tenants ON payments.tenant_ID = tenants.tenant_ID 
INNER JOIN tenant_unit ON tenants.tenant_ID = tenant_unit.tenant_ID 
WHERE payments.confirmation_status = 'Pending'
ORDER BY payments.payment_date_time DESC";

$query_exec_result = $conn->query($table_query);

if ($query_exec_result === false) {
    $query_error_table = "Error fetching pending payments: " . $conn->error;
    error_log($query_error_table);
} else {
    $table_result = $query_exec_result;
}

// Query to count payment method occurrences for filtered month/year
$method_query = "SELECT payment_method, COUNT(*) as count 
FROM payments 
WHERE confirmation_status = 'Confirmed' 
AND MONTH(CONVERT_TZ(payment_date_time, '+00:00', '+08:00')) = ? 
AND YEAR(CONVERT_TZ(payment_date_time, '+00:00', '+08:00')) = ?
GROUP BY payment_method";

$method_stmt = $conn->prepare($method_query);
$method_stmt->bind_param("ii", $filter_month, $filter_year);
$method_stmt->execute();
$method_result_exec = $method_stmt->get_result();

$method_data = [
    'Gcash' => 0,
    'Cash' => 0
];

if ($method_result_exec === false) {
    error_log("Error fetching payment method counts: " . $conn->error);
} else {
    while ($row = $method_result_exec->fetch_assoc()) {
        if (array_key_exists($row['payment_method'], $method_data)) {
            $method_data[$row['payment_method']] = (int)$row['count'];
        }
    }
}

// Calculate Monthly Revenue for filtered month/year
$monthly_revenue_query = "SELECT SUM(amount_paid) as monthly_total 
FROM payments 
WHERE confirmation_status = 'Confirmed' 
AND MONTH(CONVERT_TZ(payment_date_time, '+00:00', '+08:00')) = ? 
AND YEAR(CONVERT_TZ(payment_date_time, '+00:00', '+08:00')) = ?";

$monthly_stmt = $conn->prepare($monthly_revenue_query);
$monthly_stmt->bind_param("ii", $filter_month, $filter_year);
$monthly_stmt->execute();
$monthly_result = $monthly_stmt->get_result();
$monthly_revenue = 0;

if ($monthly_result->num_rows > 0) {
    $row = $monthly_result->fetch_assoc();
    $monthly_revenue = (float)$row['monthly_total'];
}

// Calculate Overall Revenue (all time)
$overall_revenue_query = "SELECT SUM(amount_paid) as total_revenue 
FROM payments 
WHERE confirmation_status = 'Confirmed'";

$overall_result = $conn->query($overall_revenue_query);
$overall_revenue = 0;

if ($overall_result->num_rows > 0) {
    $row = $overall_result->fetch_assoc();
    $overall_revenue = (float)$row['total_revenue'];
}

// Prepare the data for Google Charts
$gcash_count = $method_data['Gcash'];
$cash_count = $method_data['Cash'];

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirect_needed = false;

    if (isset($_POST['confirm_transaction']) && !empty($_POST['confirm_transaction'])) {
        $transaction_no = trim($_POST['confirm_transaction']);
        
        // Start transaction for data consistency
        $conn->begin_transaction();
        
        try {
            // First, get payment details including amount_paid, unit_no, payment_option, and tenant email
            $get_payment_stmt = $conn->prepare("SELECT p.amount_paid, p.unit_no, p.payment_option, t.email 
                                                FROM payments p
                                                INNER JOIN tenants t ON p.tenant_ID = t.tenant_ID
                                                WHERE p.transaction_no = ? AND p.confirmation_status = 'Pending'");
            $get_payment_stmt->bind_param("s", $transaction_no);
            $get_payment_stmt->execute();
            $payment_result = $get_payment_stmt->get_result();
            
            if ($payment_result->num_rows > 0) {
                $payment_data = $payment_result->fetch_assoc();
                $amount_paid = (float)$payment_data['amount_paid'];
                $unit_no = $payment_data['unit_no'];
                $payment_option = (int)$payment_data['payment_option'];
                $tenant_email = $payment_data['email'];
                
                // Update tenant_unit: decrease balance and increase total_rent_paid
                $update_stmt = $conn->prepare("UPDATE tenant_unit SET balance = balance - ?, total_rent_paid = total_rent_paid + ? WHERE unit_no = ?");
                $update_stmt->bind_param("dds", $amount_paid, $amount_paid, $unit_no);
                
                if (!$update_stmt->execute()) {
                    throw new Exception("Error updating tenant unit: " . $update_stmt->error);
                }
                $update_stmt->close();
                
                // Update payment_checklist: mark the next unpaid months as paid based on payment_option
                if ($payment_option > 0) {
                    // Get unpaid months in order
                    $checklist_stmt = $conn->prepare("SELECT monthly_due_dates 
                                                      FROM payment_checklist 
                                                      WHERE email_account = ? AND pay_status = 0 
                                                      ORDER BY monthly_due_dates ASC 
                                                      LIMIT ?");
                    $checklist_stmt->bind_param("si", $tenant_email, $payment_option);
                    $checklist_stmt->execute();
                    $checklist_result = $checklist_stmt->get_result();
                    
                    $months_to_update = [];
                    while ($checklist_row = $checklist_result->fetch_assoc()) {
                        $months_to_update[] = $checklist_row['monthly_due_dates'];
                    }
                    $checklist_stmt->close();
                    
                    // Update the pay_status for the selected months
                    if (!empty($months_to_update)) {
                        $placeholders = implode(',', array_fill(0, count($months_to_update), '?'));
                        $update_checklist_query = "UPDATE payment_checklist 
                                                   SET pay_status = 1 
                                                   WHERE email_account = ? 
                                                   AND monthly_due_dates IN ($placeholders)";
                        
                        $update_checklist_stmt = $conn->prepare($update_checklist_query);
                        
                        // Bind parameters dynamically
                        $types = str_repeat('s', count($months_to_update) + 1);
                        $params = array_merge([$tenant_email], $months_to_update);
                        $update_checklist_stmt->bind_param($types, ...$params);
                        
                        if (!$update_checklist_stmt->execute()) {
                            throw new Exception("Error updating payment checklist: " . $update_checklist_stmt->error);
                        }
                        $update_checklist_stmt->close();
                    }
                }
                
                // Update payment confirmation status
                $confirm_stmt = $conn->prepare("UPDATE payments SET confirmation_status = 'Confirmed' WHERE transaction_no = ? AND confirmation_status = 'Pending'");
                $confirm_stmt->bind_param("s", $transaction_no);
                
                if (!$confirm_stmt->execute()) {
                    throw new Exception("Error confirming transaction: " . $confirm_stmt->error);
                }
                $confirm_stmt->close();
                
                // Commit transaction
                $conn->commit();
                $redirect_needed = true;
                
            } else {
                throw new Exception("Transaction not found or already processed");
            }
            
            $get_payment_stmt->close();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            error_log("Error processing transaction confirmation: " . $e->getMessage());
        }
    }

    if (isset($_POST['delete_transaction']) && !empty($_POST['delete_transaction'])) {
        $transaction_no = trim($_POST['delete_transaction']);
        $stmt = $conn->prepare("DELETE FROM payments WHERE transaction_no = ? AND confirmation_status = 'Pending'");
        if ($stmt) {
            $stmt->bind_param("s", $transaction_no);
            if ($stmt->execute()) {
                $redirect_needed = true;
            } else {
                error_log("Error executing delete transaction: " . $stmt->error);
            }
            $stmt->close();
        } else {
            error_log("Error preparing delete transaction statement: " . $conn->error);
        }
    }

    if ($redirect_needed) {
        // Use full URL or current page name
        $redirect_url = $_SERVER['PHP_SELF'];
        if (empty($redirect_url)) {
            $redirect_url = 'PAYMENTMANAGEMENT.php';
        }
        header("Location: " . $redirect_url);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <title>Payment Management - RYC Dormitelle</title>
    <link rel="icon" type="image/png" href="otherIcons/pageicon.png">
    
    <!-- Include the layout CSS -->
    <link rel="stylesheet" href="layout.css">
    
    <!-- Payment Management specific styles -->
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

        .tenantInfoandGraphs {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            width: 100%;
        }

        .table_container {
            flex: 2;
            max-width: 100%;
            border: 3px solid #A6DDFF;
            border-radius: 8px;
            height: 57vh;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .table_scroll {
            height: 100%;
            overflow-y: auto;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .table_scroll::-webkit-scrollbar {
            display: none;
        }

        .table_scroll::-webkit-scrollbar-thumb {
            background-color: #A6DDFF;
            border-radius: 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            table-layout: auto;
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

        .chartContainer {
            flex: 1;
            max-width: 350px;
            min-width: 300px;
            height: 55vh;
            display: flex;
            flex-direction: column;
            border: 2px solid #A6DDFF;
            border-radius: 8px;
            padding: 8px 5px;
            background: linear-gradient(135deg, #f8fdff 0%, #e8f4f8 100%);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }

        .revenueSection {
            width: 100%;
            margin-bottom: 10px;
            text-align: center;
        }

        .dateFilter {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 7px;
            margin-bottom: 12px;
            flex-wrap: wrap;
            padding: 5px 0;
            background: linear-gradient(135deg, #ffffff 0%, #f0f8ff 100%);
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            border: 1px solid #e0f0ff;
        }

        .dateFilter select {
            padding: 6px 10px;
            border: 2px solid #A6DDFF;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            color: #01214B;
            background: white;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            min-width: 70px;
        }

        .dateFilter select:focus {
            outline: none;
            border-color: #004AAD;
            box-shadow: 0 0 0 2px rgba(0,74,173,0.1);
        }

        .dateFilter select:hover {
            border-color: #79B1FC;
            transform: translateY(-1px);
        }

        .dateFilter button {
            padding: 6px 12px;
            background: linear-gradient(135deg, #004AAD 0%, #0056d3 100%);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(0,74,173,0.3);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .dateFilter button:hover {
            background: linear-gradient(135deg, #0056d3 0%, #0066ff 100%);
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(0,74,173,0.4);
        }

        .dateFilter button:active {
            transform: translateY(0);
        }

        .revenueInfo {
            background: linear-gradient(135deg, #e8f4f8 0%, #d1ecf1 100%);
            border-radius: 8px;
            padding: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #b3e5fc;
        }

        .monthlyRevenue {
            font-size: 13px;
            font-weight: bold;
            color: #01214B;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .monthlyAmount {
            font-size: 20px;
            font-weight: bold;
            color: #004AAD;
            margin-bottom: 8px;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .overallRevenue {
            font-size: 11px;
            color: #666;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .overallAmount {
            font-size: 16px;
            font-weight: bold;
            color: #28a745;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .chartTitle {
            width: 100%;
            display: flex;
            height: auto;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: bold;
            color: #01214B;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .piechartContainer {
            width: 100%;
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #A6DDFF;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }

        .pieChart {
            width: 100%;
            height: 100%;
        }

        .footbtnContainer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .viewtransactionhistory,
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

        .viewtransactionhistory {
            min-width: 200px;
        }

        .footbtnContainer a:hover {
            background-color: white;
            color: #004AAD;
            border: 2px solid #004AAD;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin: 10px;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
        }

        /* Responsive Design */
        @media (max-width: 1199px) {
            .tenantInfoandGraphs {
                flex-direction: column;
            }
            .table_container {
                width: 100%;
                height: 50vh;
                margin: 0 3px;
            }
            .chartContainer {
                max-width: 100%;
                width: 99%;
                height: 60vh;
            }
        }
        
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

            .table_container {
                border-left: none;
                border-right: none;
                border-radius: 0;
                height: 45vh;
                margin: 0 3px;
            }

            .chartContainer {
                height: 55vh;
                padding: 5px;
            }

            .dateFilter {
                flex-direction: column;
                gap: 10px;
            }

            .dateFilter select, .dateFilter button {
                width: 100%;
                max-width: 200px;
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

            .viewtransactionhistory {
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

            .action_btn {
                padding: 5px 7px;
                font-size: 11px;
            }

            .chartTitle {
                font-size: 14px;
            }

            .chartContainer {
                height: 50vh;
                padding: 5px;
                width: 95%;
            }

            .table_container {
                height: 40vh;
            }

            .monthlyRevenue {
                font-size: 13px;
            }

            .monthlyAmount {
                font-size: 20px;
            }

            .overallAmount {
                font-size: 16px;
            }

            .dateFilter select, .dateFilter button {
                font-size: 12px;
                padding: 6px 10px;
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

            .table_container {
                height: 35vh;
            }

            .monthlyAmount {
                font-size: 18px;
            }

            .overallAmount {
                font-size: 14px;
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
            <h4>Payment Management</h4>
            
            <div class="tenantHistoryHead">
                <input type="text" id="searchInput" placeholder="Search" class="searbar">
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
                                    <th>Payment Type</th>
                                    <th>Security Deposit (₱)</th>
                                    <th>Amount(₱)</th>
                                    <th>Months Paid</th>
                                    <th>Payment Method</th>
                                    <th>Status</th>
                                    <th>Confirm</th>
                                    <th>Cancel</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (!empty($query_error_table)) {
                                    echo "<tr><td colspan='11' class='error-message'>" . htmlspecialchars($query_error_table) . "</td></tr>";
                                } elseif ($table_result && $table_result->num_rows > 0) {
                                    while ($row = $table_result->fetch_assoc()) { 
                                        // Create DateTime object and ensure it's in Philippines timezone
                                        $datetime = new DateTime($row['payment_date_time']);
                                        $datetime->setTimezone(new DateTimeZone('Asia/Manila'));
                                        $formatted_datetime = $datetime->format("M d, Y h:i A");
                                        
                                        // Format months paid
                                        $payment_option = (int)($row['payment_option'] ?? 0);
                                        $months_text = $payment_option == 1 ? '1 Month' : $payment_option . ' Months';
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['transaction_no']); ?></td>
                                            <td><?php echo htmlspecialchars($row['tenant_name']); ?></td>
                                            <td><?php echo htmlspecialchars($formatted_datetime); ?></td>
                                            <td><?php echo htmlspecialchars($row['transaction_type'] ?? 'N/A'); ?></td>
                                            <td><?php echo number_format((float)$row['security_deposit'], 2); ?></td>
                                            <td><?php echo number_format((float)$row['amount_paid'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($months_text); ?></td>
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
                                    <?php }
                                } else {
                                    echo "<tr><td colspan='11' style='text-align:center;'>No pending payment records found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="chartContainer">
                    <!-- Revenue Section -->
                    <div class="revenueSection">
                        <!-- Date Filter -->
                        <form method="GET" class="dateFilter">
                            <select name="month">
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($i == $filter_month) ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <select name="year">
                                <?php for ($year = 2020; $year <= date('Y') + 2; $year++): ?>
                                    <option value="<?php echo $year; ?>" <?php echo ($year == $filter_year) ? 'selected' : ''; ?>>
                                        <?php echo $year; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <button type="submit">Update</button>
                        </form>
                        
                        <!-- Revenue Info -->
                        <div class="revenueInfo">
                            <div class="monthlyRevenue">
                                Monthly Revenue (<?php echo $filter_month_name . ' ' . $filter_year; ?>)
                            </div>
                            <div class="monthlyAmount">
                                ₱<?php echo number_format($overall_revenue, 2); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chart Section -->
                    <h3 class="chartTitle">Payment Methods Distribution</h3>
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
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawChart);
        
        function drawChart() {
            var data = google.visualization.arrayToDataTable([
                ['Payment Method', 'Count'],
                ['Gcash', <?php echo $gcash_count; ?>],
                ['Cash', <?php echo $cash_count; ?>]
            ]);

            var options = {
                title: '',
                pieHole: 0.3,
                pieSliceText: 'percentage',
                slices: {
                    0: { color: '#2196f3' },
                    1: { color: '#FFC107' }
                },
                legend: { 
                    position: 'bottom', 
                    alignment: 'center', 
                    textStyle: { fontSize: 11 } 
                },
                chartArea: { 
                    width: '95%', 
                    height: '75%'
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
        
        // Redraw chart on window resize
        window.addEventListener('resize', function() {
            setTimeout(drawChart, 250);
        });
        
        function searchTable() {
            const input = document.getElementById("searchInput").value.toLowerCase().trim();
            const table = document.getElementById("paymentTable");
            const tr = table.getElementsByTagName("tr");
            let found = false;

            for (let i = 1; i < tr.length; i++) {
                const row = tr[i];
                if (row.getElementsByTagName("td").length === 1 && row.getElementsByTagName("td")[0].colSpan === 11) {
                    continue;
                }

                const tdTxNo = row.getElementsByTagName("td")[0];
                const tdName = row.getElementsByTagName("td")[1];
                const tdDate = row.getElementsByTagName("td")[2];
                const tdPaymentType = row.getElementsByTagName("td")[3];
                let rowVisible = false;

                if (tdTxNo && tdTxNo.textContent.toLowerCase().includes(input)) rowVisible = true;
                if (tdName && tdName.textContent.toLowerCase().includes(input)) rowVisible = true;
                if (tdDate && tdDate.textContent.toLowerCase().includes(input)) rowVisible = true;
                if (tdPaymentType && tdPaymentType.textContent.toLowerCase().includes(input)) rowVisible = true;
                
                row.style.display = rowVisible ? "" : "none";
                if (rowVisible) found = true;
            }
            
            const noRecordsRow = table.querySelector('td[colspan="11"]');
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
        
        // Add event listener for search
        document.getElementById('searchInput').addEventListener('keyup', searchTable);
        
        window.onload = function() {
            if(document.getElementById("paymentTable")){
                 searchTable();
            }
        };
    </script>
    <?php include 'chatfunctions/CHAT_COMPONENT.php'; ?>
</body>
</html>
<?php
if (isset($conn)) { 
    $conn->close();
}
?>