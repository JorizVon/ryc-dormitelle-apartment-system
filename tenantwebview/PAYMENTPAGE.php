<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['email_account'])) {
    header("Location: ../LOGIN.php");
    exit();
}

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Check if payment data exists in session
if (!isset($_SESSION['payment_data'])) {
    header("Location: TRANSACTIONSPAGE.php");
    exit();
}

include '../db_connect.php';

// Get payment details from session
$payment_data = $_SESSION['payment_data'];
$transaction_no = $payment_data['transaction_no'];
$amount_paid = $payment_data['amount_paid'];
$tenant_name = $payment_data['tenant_name'];
$tenant_ID = $payment_data['tenant_ID'];
$unit_no = $payment_data['unit_no'];
$payment_date_time = $payment_data['payment_date_time'];
$payment_status = $payment_data['payment_status'];
$payment_method = $payment_data['payment_method'];
$transaction_type = $payment_data['transaction_type'];
$months_paid = $payment_data['months_paid'] ?? 1;

// Validate minimum amount (GCash restriction)
if ($amount_paid < 20) {
    $_SESSION['payment_error'] = "Minimum payment amount is ₱20.00 due to GCash restrictions.";
    header("Location: PAYMENTFAILED.php");
    exit();
}

// Convert to cents for Paymongo
$amount_in_cents = round($amount_paid * 100);

// Paymongo API credentials
$api_secret_key = $_ENV['PAYMONGO_SECRET_KEY'];

// Set up the source request data for GCash
$source_data = [
    'data' => [
        'attributes' => [
            'amount' => $amount_in_cents,
            'redirect' => [
                'success' => 'https://ryc-dormitelle.com/tenantwebview/PAYMENTSUCCESS.php',
                'failed' => 'https://ryc-dormitelle.com/tenantwebview/PAYMENTFAILED.php'
            ],
            'type' => 'gcash',
            'currency' => 'PHP'
        ]
    ]
];

// Create source
$ch = curl_init('https://api.paymongo.com/v1/sources');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $api_secret_key . ":");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($source_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    $_SESSION['payment_error'] = "cURL Error creating source: " . $err;
    header("Location: PAYMENTFAILED.php");
    exit();
}

$result = json_decode($response, true);

if ($http_code == 200 && isset($result['data']['id'])) {
    // Save source ID and other data to session for use in success page
    $source_id = $result['data']['id'];
    
    $_SESSION['gcash_source_id'] = $source_id;
    $_SESSION['transaction_no'] = $transaction_no;
    $_SESSION['amount_in_cents'] = $amount_in_cents;
    
    $checkout_url = $result['data']['attributes']['redirect']['checkout_url'];
    
    // Keep payment_data in session - we'll need it for database insertion in PAYMENTSUCCESS.php
    // Make sure months_paid is preserved in the session data
    $_SESSION['payment_data']['months_paid'] = $months_paid;
    
    // Redirect user to GCash
    header("Location: " . $checkout_url);
    exit();
} else {
    // Handle API error
    $_SESSION['payment_error'] = "Failed to create PayMongo payment source.";
    if (isset($result['errors'])) {
        $error_messages = [];
        foreach ($result['errors'] as $error) {
            $error_messages[] = $error['detail'];
        }
        $_SESSION['payment_error'] .= " Details: " . implode(", ", $error_messages);
    }
    
    header("Location: PAYMENTFAILED.php");
    exit();
}
?>