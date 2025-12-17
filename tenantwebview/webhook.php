<?php
// webhook.php - PayMongo Webhook Handler (FINAL VERSION)

// Set up logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/webhook-errors.log');

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$api_secret_key = $_ENV['PAYMONGO_SECRET_KEY'];
$webhook_secret_key = $_ENV['PAYMONGO_WEBHOOK_SECRET_KEY'];

// --- Webhook Signature Verification ---
$payload = file_get_contents('php://input');
$sig_header = null;
if (isset($_SERVER['HTTP_PAYMONGO_SIGNATURE'])) {
    $sig_header = $_SERVER['HTTP_PAYMONGO_SIGNATURE'];
} else {
    $headers = getallheaders();
    $headers = array_change_key_case($headers, CASE_LOWER);
    if (isset($headers['paymongo-signature'])) {
        $sig_header = $headers['paymongo-signature'];
    }
}

header('Content-Type: application/json');

if (!$sig_header || !$webhook_secret_key) {
    http_response_code(400);
    error_log("Webhook signature or secret key missing.");
    echo json_encode(['error' => 'Signature or secret key missing.']);
    exit;
}

try {
    $sig_parts = [];
    parse_str(str_replace(',', '&', $sig_header), $sig_parts);
    if (!isset($sig_parts['t']) || !isset($sig_parts['v1'])) { throw new Exception("Invalid signature header format."); }
    $timestamp = $sig_parts['t'];
    $signature = $sig_parts['v1'];
    if (abs(time() - (int)$timestamp) > 300) { throw new Exception("Webhook timestamp is too old."); }
    $string_to_sign = $timestamp . '.' . $payload;
    $expected_signature = hash_hmac('sha256', $string_to_sign, $webhook_secret_key);
    if (!hash_equals($expected_signature, $signature)) { throw new Exception("Webhook signature verification failed."); }
    
    $event = json_decode($payload, true);
    if (!$event || !isset($event['data']['type'])) { throw new Exception("Invalid JSON payload."); }

    $event_type = $event['data']['type'];
    error_log("Processing valid webhook event: " . $event_type);

    if ($event_type === 'source.chargeable') {
        $source = $event['data']['attributes'];
        $source_id = $event['data']['id'];
        $amount = $source['amount'];
        $currency = $source['currency'];
        $description = $source['description'];

        error_log("Creating payment for chargeable source: " . $source_id);

        $payment_data = [ 'data' => [ 'attributes' => [
            'amount' => $amount, 'currency' => $currency, 'description' => $description,
            'statement_descriptor' => 'RYC Dormitelle',
            'source' => [ 'id' => $source_id, 'type' => 'source' ]
        ]]];

        $ch = curl_init('https://api.paymongo.com/v1/payments');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $api_secret_key . ":");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);

        $payment_response = curl_exec($ch);
        $payment_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $payment_result = json_decode($payment_response, true);
        error_log("PayMongo Create Payment API response (Code: $payment_http_code): " . $payment_response);

        if ($payment_http_code === 200 && isset($payment_result['data']['attributes']['status'])) {
            $payment_status = $payment_result['data']['attributes']['status'];
            include __DIR__ . '/../db_connect.php';
            if ($payment_status === 'paid') {
                $stmt = $conn->prepare("UPDATE payments SET confirmation_status = 'confirmed', payment_status = 'Fully Paid' WHERE source_id = ? AND confirmation_status = 'pending'");
                $stmt->bind_param("s", $source_id);
                if ($stmt->execute() && $stmt->affected_rows > 0) { error_log("SUCCESS: DB updated for source_id: " . $source_id); } 
                else { error_log("WARNING: Payment successful but DB update failed for source_id: " . $source_id); }
                $stmt->close();
            } else {
                $stmt = $conn->prepare("UPDATE payments SET confirmation_status = 'failed', payment_status = 'Failed' WHERE source_id = ?");
                $stmt->bind_param("s", $source_id);
                $stmt->execute();
                $stmt->close();
                error_log("FAILED: Payment status was '$payment_status'. Source ID: " . $source_id);
            }
            $conn->close();
        } else {
            throw new Exception("Failed to create payment via PayMongo API.");
        }
    }

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Webhook processed.']);

} catch (Exception $e) {
    error_log('Webhook processing error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => 'Webhook processing failed']);
    exit;
}
?>