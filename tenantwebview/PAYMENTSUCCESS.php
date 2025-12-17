<?php
// Set timezone at the very beginning - this is crucial
date_default_timezone_set('Asia/Manila');

session_start();

// Check if user is logged in
if (!isset($_SESSION['email_account'])) {
    header("Location: ../LOGIN.php");
    exit();
}

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

include '../db_connect.php';

// Set MySQL timezone to Philippine time as well
$conn->query("SET time_zone = '+08:00'");

// Get source ID from URL parameters or session
$source_id = $_GET['source']['id'] ?? $_SESSION['gcash_source_id'] ?? $_SESSION['source_id'] ?? null;
$transaction_no = $_SESSION['transaction_no'] ?? null;

// Check if we have the necessary data
if (!$source_id) {
    // Try to get from transaction_details as fallback
    if (isset($_SESSION['transaction_details']) && isset($_SESSION['source_id'])) {
        $source_id = $_SESSION['source_id'];
        $transaction_details = $_SESSION['transaction_details'];
        $transaction_no = $transaction_details['transaction_no'] ?? '';
    } else {
        header("Location: TRANSACTIONSPAGE.php");
        exit();
    }
}

// If transaction_no is empty or invalid, generate a proper one
if (empty($transaction_no) || strlen($transaction_no) != 12) {
    // Generate proper transaction number using Philippine time
    $datePrefix = date("Ymd");
    
    // Count total transactions for today to get the next sequence
    $result = $conn->prepare("SELECT COUNT(*) FROM payments WHERE transaction_no LIKE CONCAT(?, '%')");
    $likeParam = $datePrefix . "%";
    $result->bind_param("s", $likeParam);
    $result->execute();
    $result->bind_result($count);
    $result->fetch();
    $result->close();
    
    $sequence = $count + 1;
    $maxAttempts = 9999;
    
    do {
        $formattedSequence = str_pad($sequence, 4, '0', STR_PAD_LEFT);
        $transaction_no = $datePrefix . $formattedSequence;
        
        $check = $conn->prepare("SELECT COUNT(*) FROM payments WHERE transaction_no = ?");
        $check->bind_param("s", $transaction_no);
        $check->execute();
        $check->bind_result($existing);
        $check->fetch();
        $check->close();
        
        $sequence++;
    } while ($existing > 0 && $sequence <= $maxAttempts);
    
    if ($sequence > $maxAttempts + 1) {
        die("Error: Daily transaction limit reached.");
    }
}

// Create Philippine DateTime object for consistent time handling
$philippine_datetime = new DateTime('now', new DateTimeZone('Asia/Manila'));
$current_philippine_time = $philippine_datetime->format('Y-m-d H:i:s');

// Paymongo API credentials
$api_secret_key = $_ENV['PAYMONGO_SECRET_KEY'];

// Step 1: Verify source status
$ch = curl_init("https://api.paymongo.com/v1/sources/{$source_id}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $api_secret_key . ":");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

$payment_verified = false;
$payment_status = "Failed";
$payment_data = null;
$reference_number = '';

if (!$err && $http_code == 200) {
    $source_result = json_decode($response, true);
    
    // Check if source is chargeable
    if (isset($source_result['data']['attributes']['status']) && $source_result['data']['attributes']['status'] === 'chargeable') {
        
        // Step 2: Create payment
        $payment_request_data = [
            'data' => [
                'attributes' => [
                    'amount' => $source_result['data']['attributes']['amount'],
                    'source' => [
                        'id' => $source_id,
                        'type' => 'source'
                    ],
                    'currency' => 'PHP'
                ]
            ]
        ];
        
        $ch = curl_init('https://api.paymongo.com/v1/payments');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $api_secret_key . ":");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_request_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        
        $payment_response = curl_exec($ch);
        $payment_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $payment_err = curl_error($ch);
        curl_close($ch);
        
        if (!$payment_err && $payment_http_code == 200) {
            $payment_result = json_decode($payment_response, true);
            
            if (isset($payment_result['data']['id'])) {
                $reference_number = $payment_result['data']['attributes']['reference_number'] ?? strtoupper(uniqid());
                
                // Get payment data from session
                if (isset($_SESSION['payment_data'])) {
                    $payment_data = $_SESSION['payment_data'];
                    // Update with current Philippine time
                    $payment_data['payment_date_time'] = $current_philippine_time;
                    $payment_option = $payment_data['months_paid'] ?? 1;
                } else {
                    // Fallback: create minimal payment data
                    $payment_data = [
                        'transaction_no' => $transaction_no,
                        'unit_no' => '',
                        'tenant_ID' => '',
                        'amount_paid' => $source_result['data']['attributes']['amount'] / 100,
                        'payment_date_time' => $current_philippine_time,
                        'payment_status' => 'Paid',
                        'payment_method' => 'Gcash',
                        'transaction_type' => 'Rent Payment',
                    ];
                    $payment_option = 1;
                    
                    // Get tenant_ID and unit_no from session email
                    if (isset($_SESSION['email_account'])) {
                        $email = $_SESSION['email_account'];
                        $tenant_stmt = $conn->prepare("SELECT tenants.tenant_ID, tenant_unit.unit_no 
                                                       FROM tenants 
                                                       INNER JOIN tenant_unit ON tenants.tenant_ID = tenant_unit.tenant_ID 
                                                       WHERE tenants.email = ?");
                        $tenant_stmt->bind_param("s", $email);
                        $tenant_stmt->execute();
                        $tenant_result = $tenant_stmt->get_result();
                        if ($tenant_row = $tenant_result->fetch_assoc()) {
                            $payment_data['tenant_ID'] = $tenant_row['tenant_ID'];
                            $payment_data['unit_no'] = $tenant_row['unit_no'];
                        }
                        $tenant_stmt->close();
                    }
                }
                
                // Check if record already exists
                $check_stmt = $conn->prepare("SELECT COUNT(*) FROM payments WHERE transaction_no = ?");
                $check_stmt->bind_param("s", $transaction_no);
                $check_stmt->execute();
                $check_stmt->bind_result($record_count);
                $check_stmt->fetch();
                $check_stmt->close();
                
                if ($record_count == 0) {
                    // Insert new record with payment_option
                    $confirmation_status = "pending";
                    
                    $insert = $conn->prepare("INSERT INTO payments(transaction_no, unit_no, tenant_ID, amount_paid, payment_date_time, payment_status, payment_method, transaction_type, confirmation_status, source_id, reference_number, payment_option) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    if (!$insert) {
                        die("Prepare statement failed: " . $conn->error);
                    }
                    
                    $insert->bind_param("sssdsssssssi", 
                                     $payment_data['transaction_no'],
                                     $payment_data['unit_no'],
                                     $payment_data['tenant_ID'],
                                     $payment_data['amount_paid'],
                                     $payment_data['payment_date_time'],
                                     $payment_data['payment_status'],
                                     $payment_data['payment_method'],
                                     $payment_data['transaction_type'],
                                     $confirmation_status,
                                     $source_id,
                                     $reference_number,
                                     $payment_option);
                    
                    if ($insert->execute()) {
                        $payment_verified = true;
                        $payment_status = "Success";
                        error_log("Rent payment successfully inserted with reference: GCASH-" . $reference_number . " at Philippine time: " . $current_philippine_time);
                    } else {
                        error_log("Insert failed with error: " . $insert->error);
                        $payment_status = "Database Error";
                    }
                    $insert->close();
                } else {
                    // Update existing record with current Philippine time
                    $update = $conn->prepare("UPDATE payments SET payment_status = 'Paid', confirmation_status = 'pending', reference_number = ?, payment_date_time = ?, payment_option = ? WHERE transaction_no = ?");
                    $update->bind_param("ssis", $reference_number, $current_philippine_time, $payment_option, $transaction_no);
                    if ($update->execute()) {
                        $payment_verified = true;
                        $payment_status = "Success";
                        error_log("Rent payment record updated with reference: GCASH-" . $reference_number . " at Philippine time: " . $current_philippine_time);
                    }
                    $update->close();
                }
            }
        } else {
            $payment_status = "Payment Creation Failed";
            error_log("Payment creation failed: " . $payment_err);
        }
    } else {
        $payment_status = $source_result['data']['attributes']['status'] ?? 'Unknown Status';
    }
} else {
    error_log("Source verification failed: " . $err);
}

// Get tenant name for display - simplified query
$tenant_name = '';
if (!empty($payment_data['tenant_ID'])) {
    $tenant_stmt = $conn->prepare("SELECT tenant_name FROM tenants WHERE tenant_ID = ?");
    $tenant_stmt->bind_param("s", $payment_data['tenant_ID']);
    $tenant_stmt->execute();
    $tenant_result = $tenant_stmt->get_result();
    if ($tenant_row = $tenant_result->fetch_assoc()) {
        $tenant_name = $tenant_row['tenant_name'];
    }
    $tenant_stmt->close();
}

// Clear payment session data after processing
unset($_SESSION['gcash_source_id']);
unset($_SESSION['source_id']);
unset($_SESSION['transaction_details']);
unset($_SESSION['payment_data']);
unset($_SESSION['transaction_no']);
unset($_SESSION['amount_in_cents']);

// Debug timezone and current timestamp
error_log("Current timezone: " . date_default_timezone_get());
error_log("Current Philippine time: " . $current_philippine_time);
error_log("Timezone object check: " . $philippine_datetime->getTimezone()->getName());
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rent Payment Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f5f5f5;
        }
        .container {
            width: 100%;
            max-width: 600px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .success-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .success {
            color: #28a745;
        }
        .pending {
            color: #ffc107;
        }
        .failed {
            color: #dc3545;
        }
        h1 {
            margin-bottom: 20px;
        }
        .payment-details {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            text-align: left;
        }
        .payment-details p {
            margin: 10px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #2262B8;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #1b4b8f;
        }
        .btn-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .note {
            background-color: #e7f3ff;
            border-left: 4px solid #2262B8;
            padding: 10px;
            margin-top: 15px;
            font-style: italic;
        }
        .reference-highlight {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 8px;
            border-radius: 4px;
            font-weight: bold;
            color: #856404;
        }
        .print-btn {
            background-color: #28a745;
            margin-left: 10px;
        }
        .print-btn:hover {
            background-color: #218838;
        }
        @media print {
            body {
                background-color: white;
            }
            .btn-container {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($payment_verified): ?>
            <div class="success-icon success">✓</div>
            <h1 class="success">Rent Payment Successfully Processed!</h1>
            <p>Your GCash rent payment has been successfully processed and is pending confirmation.</p>
            
            <?php if (!empty($reference_number)): ?>
            <div class="reference-highlight">
                <p><strong>GCash Reference:</strong> GCASH-<?php echo htmlspecialchars($reference_number); ?></p>
            </div>
            <?php endif; ?>
            
            <div class="payment-details">
                <h3 style="text-align: center; margin-bottom: 15px;">Payment Receipt</h3>
                <p><strong>Transaction Number:</strong> <?php echo htmlspecialchars($payment_data['transaction_no']); ?></p>
                <p><strong>Transaction Type:</strong> <?php echo htmlspecialchars($payment_data['transaction_type']); ?></p>
                <?php if(!empty($tenant_name)): ?>
                <p><strong>Tenant:</strong> <?php echo htmlspecialchars($tenant_name); ?></p>
                <?php endif; ?>
                <p><strong>Tenant ID:</strong> <?php echo htmlspecialchars($payment_data['tenant_ID']); ?></p>
                <p><strong>Unit Number:</strong> <?php echo htmlspecialchars($payment_data['unit_no']); ?></p>
                <p><strong>Months Paid:</strong> <?php echo htmlspecialchars($payment_option ?? 1); ?> month(s)</p>
                <p><strong>Amount Paid:</strong> ₱<?php echo number_format($payment_data['amount_paid'], 2); ?></p>
                <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($payment_data['payment_method']); ?></p>
                <p><strong>Payment Date:</strong> <?php 
                    // Create DateTime object from the stored time and format it in Philippine timezone
                    $stored_datetime = new DateTime($payment_data['payment_date_time'], new DateTimeZone('Asia/Manila'));
                    echo $stored_datetime->format('F j, Y, g:i a T'); 
                ?></p>
                <p><strong>Status:</strong> <span class="pending">Pending Confirmation</span></p>
                
                <div class="note">
                    <p><strong>⏳ Payment Submitted!</strong> Your rent payment has been successfully processed and submitted for confirmation. The landlord will verify and confirm your payment shortly. Your payment checklist will be updated once confirmed.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="success-icon failed">✗</div>
            <h1 class="failed">Payment Processing Issue</h1>
            <p>There was a problem processing your rent payment. Status: <?php echo htmlspecialchars($payment_status); ?></p>
            
            <?php if ($payment_data): ?>
            <div class="payment-details">
                <p><strong>Transaction Number:</strong> <?php echo htmlspecialchars($payment_data['transaction_no'] ?? 'N/A'); ?></p>
                <p><strong>Transaction Type:</strong> <?php echo htmlspecialchars($payment_data['transaction_type'] ?? 'Rent Payment'); ?></p>
                <?php if(!empty($tenant_name)): ?>
                <p><strong>Tenant:</strong> <?php echo htmlspecialchars($tenant_name); ?></p>
                <?php endif; ?>
                <p><strong>Tenant ID:</strong> <?php echo htmlspecialchars($payment_data['tenant_ID'] ?? 'N/A'); ?></p>
                <p><strong>Unit Number:</strong> <?php echo htmlspecialchars($payment_data['unit_no'] ?? 'N/A'); ?></p>
                <p><strong>Amount:</strong> ₱<?php echo number_format($payment_data['amount_paid'] ?? 0, 2); ?></p>
                <p><strong>Status:</strong> <span class="failed">Processing Failed</span>
                
                <div class="note">
                    <p><strong>⚠ Processing Issue:</strong> Your rent payment might still be processing on GCash's end. Please check your GCash transaction history or contact support if the issue persists.</p>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="btn-container">
            <a href="TRANSACTIONSPAGE.php" class="btn">Back to Payments</a>
            <a href="TRANSACTIONHISTORYPAGE.php" class="btn">View Transaction History</a>
            <?php if ($payment_verified): ?>
            <button onclick="window.print()" class="btn print-btn">Print Receipt</button>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>