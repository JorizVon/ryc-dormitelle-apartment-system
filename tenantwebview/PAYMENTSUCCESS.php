<?php
// Set timezone to Philippine time
date_default_timezone_set('Asia/Manila');
// Verify the timezone setting took effect
$current_timezone = date_default_timezone_get();
if ($current_timezone != 'Asia/Manila') {
    // Try alternate method if the first one fails
    ini_set('date.timezone', 'Asia/Manila');
}

session_start();

// Check if user is logged in
if (!isset($_SESSION['email_account'])) {
    header("Location: ../LOGIN.php");
    exit();
}
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Check if we have source ID and transaction details
if (!isset($_SESSION['source_id']) || !isset($_SESSION['transaction_details'])) {
    header("Location: TRANSACTIONSPAGE.php");
    exit();
}

include '../db_connect.php';

$source_id = $_SESSION['source_id'];
$transaction_details = $_SESSION['transaction_details'];
$transaction_no = isset($transaction_details['transaction_no']) ? $transaction_details['transaction_no'] : '';

// If transaction_no is empty or invalid, generate a proper one
if (empty($transaction_no) || strlen($transaction_no) != 12) {
    // Generate proper transaction number
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
    
    // Update the transaction_no in the session
    $transaction_details['transaction_no'] = $transaction_no;
    $_SESSION['transaction_details'] = $transaction_details;
}

// Retrieve the payment data from session if it exists
$payment_data = null;
if (isset($_SESSION['payment_data'])) {
    $payment_data = $_SESSION['payment_data'];
} else {
    // Check if a record already exists in the database
    $stmt = $conn->prepare("SELECT transaction_no, unit_no, tenant_ID, amount_paid, payment_date_time, payment_status, payment_method, transaction_type, confirmation_status FROM payments WHERE transaction_no = ? LIMIT 1");
    $stmt->bind_param("s", $transaction_no);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $payment_data = $result->fetch_assoc();
    } else {
        // If no existing record, use transaction details as fallback
        $payment_data = [
            'transaction_no' => $transaction_no,
            'unit_no' => $transaction_details['unit_no'] ?? '',
            'tenant_ID' => $transaction_details['tenant_ID'] ?? '',
            'amount_paid' => $transaction_details['amount'] ?? 0,
            'payment_date_time' => date('Y-m-d H:i:s'), // Ensure this uses the Philippine timezone
            'payment_status' => $transaction_details['payment_status'] ?? 'pending',
            'payment_method' => 'Gcash',
            'transaction_type' => $transaction_details['transaction_type'] ?? 'Rent Payment',
        ];
        
        // If no tenant_ID in transaction_details, try to get it from the email session
        if (empty($payment_data['tenant_ID']) && isset($_SESSION['email_account'])) {
            $email = $_SESSION['email_account'];
            $tenant_stmt = $conn->prepare("SELECT tenant_ID FROM tenants WHERE email = ?");
            $tenant_stmt->bind_param("s", $email);
            $tenant_stmt->execute();
            $tenant_result = $tenant_stmt->get_result();
            if ($tenant_row = $tenant_result->fetch_assoc()) {
                $payment_data['tenant_ID'] = $tenant_row['tenant_ID'];
            }
            $tenant_stmt->close();
        }
    }
    $stmt->close();
}

// Debug timezone and current timestamp
error_log("Current timezone: " . date_default_timezone_get());
error_log("Current Philippine time: " . date('Y-m-d H:i:s'));

// Paymongo API credentials
$api_secret_key = $_ENV['PAYMONGO_SECRET_KEY'];

// Call Paymongo API to check payment status
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.paymongo.com/v1/sources/' . $source_id);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $api_secret_key . ":");
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

$payment_verified = false;
$payment_status = "Failed";

if (!$err) {
    $result = json_decode($response, true);
    
    if (isset($result['data']['attributes']['status'])) {
        if ($result['data']['attributes']['status'] == 'chargeable') {
            // Check if record already exists
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM payments WHERE transaction_no = ?");
            $check_stmt->bind_param("s", $transaction_no);
            $check_stmt->execute();
            $check_stmt->bind_result($record_count);
            $check_stmt->fetch();
            $check_stmt->close();
            
            if ($record_count == 0) {
                // Store the full tenant_ID string
                $tenant_id_value = $payment_data['tenant_ID'];
                
                // Ensure we're using Philippine time for the current timestamp
                date_default_timezone_set('Asia/Manila');
                $current_timestamp = date('Y-m-d H:i:s');
                $payment_data['payment_date_time'] = $current_timestamp;
                
                // Set confirmation status to pending
                $confirmation_status = "pending";
                
                // Insert new record using the payment data
                $insert = $conn->prepare("INSERT INTO payments(transaction_no, unit_no, tenant_ID, amount_paid, payment_date_time, payment_status, payment_method, transaction_type, confirmation_status, source_id) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                if (!$insert) {
                    die("Prepare statement failed: " . $conn->error);
                }
                
                $insert->bind_param("sssdssssss", 
                                 $payment_data['transaction_no'],
                                 $payment_data['unit_no'],
                                 $tenant_id_value,
                                 $payment_data['amount_paid'],
                                 $payment_data['payment_date_time'],
                                 $payment_data['payment_status'],
                                 $payment_data['payment_method'],
                                 $payment_data['transaction_type'],
                                 $confirmation_status,
                                 $source_id);
                
                if (!$insert->execute()) {
                    error_log("Insert failed with error: " . $insert->error);
                    error_log("Tenant ID being inserted: " . $tenant_id_value);
                    error_log("Current timestamp being used: " . $current_timestamp);
                    die("Insert failed: " . $insert->error . " - Tenant ID: " . $tenant_id_value);
                }
                $insert->close();
                
                // Debug - Log after insertion
                error_log("Payment inserted with tenant ID: " . $tenant_id_value);
            } else {
                // Update existing record if needed
                error_log("Record already exists for transaction: " . $transaction_no);
            }
            
            $payment_verified = true;
            $payment_status = "Success";
        } else {
            $payment_status = $result['data']['attributes']['status'];
        }
    }
}

// Get tenant name for display
$tenant_name = '';
if (!empty($payment_data['tenant_ID'])) {
    if (isset($payment_data['tenant_name'])) {
        $tenant_name = $payment_data['tenant_name'];
    } else {
        $tenant_stmt = $conn->prepare("SELECT tenant_name FROM tenants WHERE tenant_ID = ?");
        $tenant_stmt->bind_param("s", $payment_data['tenant_ID']);
        $tenant_stmt->execute();
        $tenant_result = $tenant_stmt->get_result();
        if ($tenant_row = $tenant_result->fetch_assoc()) {
            $tenant_name = $tenant_row['tenant_name'];
        }
        $tenant_stmt->close();
    }
}

// Clear payment session data after processing
unset($_SESSION['source_id']);
unset($_SESSION['transaction_details']);
unset($_SESSION['payment_data']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status</title>
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
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($payment_verified): ?>
            <div class="success-icon success">✓</div>
            <h1 class="success">Payment Successfully Processed!</h1>
            <p>Your GCash payment has been successfully processed.</p>
            <div class="payment-details">
                <p><strong>Transaction Number:</strong> <?php echo htmlspecialchars($payment_data['transaction_no']); ?></p>
                <p><strong>Transaction Type:</strong> <?php echo htmlspecialchars($payment_data['transaction_type']); ?></p>
                <?php if(!empty($tenant_name)): ?>
                <p><strong>Tenant:</strong> <?php echo htmlspecialchars($tenant_name); ?></p>
                <?php endif; ?>
                <p><strong>Tenant ID:</strong> <?php echo htmlspecialchars($payment_data['tenant_ID']); ?></p>
                <p><strong>Unit Number:</strong> <?php echo htmlspecialchars($payment_data['unit_no']); ?></p>
                <p><strong>Amount:</strong> ₱<?php echo number_format($payment_data['amount_paid'], 2); ?></p>
                <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($payment_data['payment_method']); ?></p>
                <p><strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($payment_data['payment_date_time'])); ?></p>
                <p><strong>Confirmation Status:</strong> pending</p>
                <p class="note">Please wait for the landlord's approval of your payment. You will be notified once the payment is confirmed.</p>
            </div>
        <?php else: ?>
            <div class="success-icon failed">✗</div>
            <h1 class="failed">Payment Verification Issue</h1>
            <p>There was a problem with your payment. Status: <?php echo htmlspecialchars($payment_status); ?></p>
            <div class="payment-details">
                <p><strong>Transaction Number:</strong> <?php echo htmlspecialchars($payment_data['transaction_no']); ?></p>
                <p><strong>Transaction Type:</strong> <?php echo htmlspecialchars($payment_data['transaction_type']); ?></p>
                <?php if(!empty($tenant_name)): ?>
                <p><strong>Tenant:</strong> <?php echo htmlspecialchars($tenant_name); ?></p>
                <?php endif; ?>
                <p><strong>Tenant ID:</strong> <?php echo htmlspecialchars($payment_data['tenant_ID']); ?></p>
                <p><strong>Unit Number:</strong> <?php echo htmlspecialchars($payment_data['unit_no']); ?></p>
                <p><strong>Amount:</strong> ₱<?php echo number_format($payment_data['amount_paid'], 2); ?></p>
                <p><strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($payment_data['payment_date_time'])); ?></p>
                <p><strong>Status:</strong> Verification Failed</p>
                <p class="note">Your payment might still be processing. Please check your transaction history or contact the landlord if the issue persists.</p>
            </div>
        <?php endif; ?>
        
        <div class="btn-container">
            <a href="TRANSACTIONSPAGE.php" class="btn">Back to Payments</a>
            <a href="TRANSACTIONHISTORYPAGE.php" class="btn">View Transaction History</a>
        </div>
    </div>
</body>
</html>