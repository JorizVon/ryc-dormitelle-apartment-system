<?php
// Webhook handler for Paymongo payment notifications
session_start();
require_once _DIR_ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(_DIR_ . '/../');
$dotenv->load();

// Database connection
include '../db_connect.php';

// Get JSON payload
$payload = file_get_contents('php://input');
$signature = isset($_SERVER['HTTP_PAYMONGO_SIGNATURE']) ? $_SERVER['HTTP_PAYMONGO_SIGNATURE'] : '';

// Your webhook secret (from Paymongo dashboard)
$webhook_secret = $_ENV['PAYMONGO_WEB_HOOK_SECRET_KEY'];
$api_secret_key = $_ENV['PAYMONGO_SECRET_KEY'];

// Verify webhook signature
$computed_signature = hash_hmac('sha256', $payload, $webhook_secret);
if (!hash_equals($computed_signature, $signature)) {
    http_response_code(401);
    exit('Signature verification failed');
}

// Parse payload
$event = json_decode($payload, true);

// Check if this is a source.chargeable event
if (isset($event['data']['attributes']['type']) && $event['data']['attributes']['type'] === 'source.chargeable') {
    // Extract data from the event
    $source_id = $event['data']['attributes']['data']['id'];
    $amount = $event['data']['attributes']['data']['attributes']['amount'] / 100; // Convert from cents to peso
    $status = $event['data']['attributes']['data']['attributes']['status'];

    // Log the webhook event for debugging
    error_log("PayMongo webhook received: " . $payload);

    // Call Paymongo API to get payment details
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.paymongo.com/v1/sources/' . $source_id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $api_secret_key . ":");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

    $source_response = curl_exec($ch);
    curl_close($ch);

    $source_data = json_decode($source_response, true);

    if (isset($source_data['data']['attributes']['metadata']['transaction_no'])) {
        $transaction_no = $source_data['data']['attributes']['metadata']['transaction_no'];
        
        // Extract all metadata for easier access
        $metadata = $source_data['data']['attributes']['metadata'];
        $tenant_ID = isset($metadata['tenant_ID']) ? $metadata['tenant_ID'] : null;
        $unit_no = isset($metadata['unit_no']) ? $metadata['unit_no'] : null;
        $payment_status = isset($metadata['payment_status']) ? $metadata['payment_status'] : 'Partially Paid';
        $transaction_type = isset($metadata['transaction_type']) ? $metadata['transaction_type'] : 'Rent Payment';

        if ($status === 'chargeable') {
            // Update payment status in database
            $stmt = $conn->prepare("UPDATE payments SET confirmation_status = 'pending' WHERE transaction_no = ?");
            $stmt->bind_param("s", $transaction_no);
            $stmt->execute();
            $stmt->close();
            
            // Retrieve payment data from session or database
            $payment_data = null;
            
            // First, check if we can find a session with this transaction number
            $session_files = glob(session_save_path() . '/*');
            foreach ($session_files as $file) {
                if (is_file($file)) {
                    $session_content = file_get_contents($file);
                    if (strpos($session_content, $transaction_no) !== false) {
                        $old_session_id = basename($file);
                        session_write_close(); // Close current session
                        session_id($old_session_id);
                        session_start();
                        if (isset($_SESSION['payment_data']) && $_SESSION['payment_data']['transaction_no'] === $transaction_no) {
                            $payment_data = $_SESSION['payment_data'];
                            break;
                        }
                        session_write_close();
                        session_id(''); // Reset session ID
                        session_start(); // Start a new session
                    }
                }
            }
            
            // If session data is found, previously had INSERT query which is now removed
            if ($payment_data) {
                // Payment data found in session, but INSERT query is removed
                error_log("Payment data found for transaction: " . $transaction_no . " but INSERT operation was removed");
            } else {
                // Check if a record with this transaction number exists
                $check_stmt = $conn->prepare("SELECT * FROM payments WHERE transaction_no = ? LIMIT 1");
                $check_stmt->bind_param("s", $transaction_no);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                
                if ($result->num_rows === 0) {
                    // No existing record, look for transaction data in session storage
                    $tenant_ID = null;
                    $unit_no = null;
                    $payment_status = 'Partially Paid';
                    $transaction_type = 'Rent Payment';
                    $payment_date_time = date("Y-m-d H:i:s");
                    
                    // Try to find the session with the payment data for this transaction
                    $session_dir = session_save_path();
                    $found = false;
                    
                    // If session_save_path() returns an empty string or N;, use default tmp directory
                    if (empty($session_dir) || $session_dir === 'N;') {
                        $session_dir = '/tmp';
                    }
                    
                    if (is_dir($session_dir)) {
                        $session_files = glob($session_dir . '/sess_*');
                        foreach ($session_files as $file) {
                            if (is_file($file)) {
                                $session_content = file_get_contents($file);
                                // Check if this session file contains our transaction number
                                if (strpos($session_content, $transaction_no) !== false) {
                                    // Save current session
                                    $old_session_id = session_id();
                                    $old_session_data = $_SESSION;
                                    session_write_close();
                                    
                                    // Load the session with payment data
                                    session_id(basename($file, 'sess_'));
                                    session_start();
                                    
                                    if (isset($_SESSION['payment_data']) && 
                                        $_SESSION['payment_data']['transaction_no'] === $transaction_no) {
                                        $tenant_ID = $_SESSION['payment_data']['tenant_ID'];
                                        $unit_no = $_SESSION['payment_data']['unit_no'];
                                        $payment_status = $_SESSION['payment_data']['payment_status'];
                                        $transaction_type = $_SESSION['payment_data']['transaction_type'];
                                        $payment_date_time = $_SESSION['payment_data']['payment_date_time'];
                                        $found = true;
                                    }
                                    
                                    // Restore original session
                                    session_write_close();
                                    session_id($old_session_id);
                                    session_start();
                                    $_SESSION = $old_session_data;
                                    
                                    if ($found) {
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    
                    // If we still don't have the data, try to get it from other sources
                    if (!$found) {
                        // Try to extract from PayMongo metadata
                        if (isset($source_data['data']['attributes']['metadata']['tenant_ID'])) {
                            $tenant_ID = $source_data['data']['attributes']['metadata']['tenant_ID'];
                        }
                        
                        if (isset($source_data['data']['attributes']['metadata']['unit_no'])) {
                            $unit_no = $source_data['data']['attributes']['metadata']['unit_no'];
                        }
                        
                        // If still not available, query from the database based on transaction number format
                        if (empty($tenant_ID) || empty($unit_no)) {
                            // Try to extract email from transaction number and find user
                            $email = isset($source_data['data']['attributes']['billing']['email']) ? 
                                $source_data['data']['attributes']['billing']['email'] : null;
                                
                            if ($email) {
                                $tenant_stmt = $conn->prepare("SELECT tenants.tenant_ID, tenant_unit.unit_no 
                                                FROM tenants 
                                                INNER JOIN tenant_unit ON tenants.tenant_ID = tenant_unit.tenant_ID 
                                                WHERE tenants.email = ? LIMIT 1");
                                $tenant_stmt->bind_param("s", $email);
                                $tenant_stmt->execute();
                                $tenant_result = $tenant_stmt->get_result();
                                
                                if ($tenant_result->num_rows > 0) {
                                    $tenant_data = $tenant_result->fetch_assoc();
                                    $tenant_ID = $tenant_data['tenant_ID'];
                                    $unit_no = $tenant_data['unit_no'];
                                }
                                $tenant_stmt->close();
                            }
                        }
                    }
                    
                    // Make sure we have valid values
                    if (empty($tenant_ID) || empty($unit_no)) {
                        error_log("Could not find tenant_ID or unit_no for transaction: " . $transaction_no);
                        http_response_code(400);
                        echo json_encode(['status' => 'error', 'message' => 'Cannot process payment - missing tenant information']);
                        exit;
                    }
                    
                    // Previously had INSERT query here which is now removed
                    error_log("Found transaction data for: " . $transaction_no . " but INSERT operation was removed");
                } else {
                    // Update existing record
                    $update_stmt = $conn->prepare("UPDATE payments SET source_id = ?, confirmation_status = 'complete' WHERE transaction_no = ?");
                    $update_stmt->bind_param("ss", $source_id, $transaction_no);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
                $check_stmt->close();
            }

            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Payment processed successfully']);
        } else {
            // Payment failed
            $stmt = $conn->prepare("UPDATE payments SET confirmation_status = 'failed' WHERE transaction_no = ?");
            $stmt->bind_param("s", $transaction_no);
            $stmt->execute();
            $stmt->close();

            http_response_code(200);
            echo json_encode(['status' => 'failed', 'message' => 'Payment failed']);
        }
    } else {
        // Could not find transaction number
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Transaction number not found']);
    }
} else {
    // Not a payment event we're interested in
    http_response_code(200);
    echo json_encode(['status' => 'ignored', 'message' => 'Event type not handled']);
}
?>