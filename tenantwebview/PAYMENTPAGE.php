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

// Get payment details from session
$payment_data = $_SESSION['payment_data'];
$transaction_no = $payment_data['transaction_no'];
$amount_paid = $payment_data['amount_paid'];
$tenant_name = $payment_data['tenant_name'];
$transaction_type = $payment_data['transaction_type'];

// Convert to cents for Paymongo (they use the smallest currency unit)
$amount_in_cents = $amount_paid * 100;

// Paymongo API credentials - Replace with your actual credentials
$api_secret_key = $_ENV['PAYMONGO_SECRET_KEY'];

// Set up the source request data for GCash
$source_data = array(
    'data' => array(
        'attributes' => array(
            'amount' => $amount_in_cents,
            'currency' => 'PHP',
            'type' => 'gcash',
            'redirect' => array(
                'success' => 'http://127.0.0.1/RYC-DORMITELLE-APT-SYSTEM/tenantwebview/PAYMENTSUCCESS.php',
                'failed' => 'http://127.0.0.1/RYC-DORMITELLE-APT-SYSTEM/tenantwebview/PAYMENTFAILED.php'
            ),
            'billing' => array(
                'name' => $tenant_name,
                'email' => $_SESSION['email_account']
            ),
            'description' => 'RYC Dormitelle - ' . $transaction_type . ' - ' . $transaction_no
        )
    )
);

// Initiate payment source
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.paymongo.com/v1/sources');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $api_secret_key . ":");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($source_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json'
));

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    $_SESSION['payment_error'] = "cURL Error: " . $err;
    header("Location: http://127.0.0.1/RYC-DORMITELLE-APT-SYSTEM/tenantwebview/PAYMENTFAILED.php");
    exit();
}

// Process the response
$result = json_decode($response, true);

if (isset($result['data']) && isset($result['data']['id']) && isset($result['data']['attributes']['redirect']['checkout_url'])) {
    // Save source ID to session for verification later
    $_SESSION['source_id'] = $result['data']['id'];
    
    // Store transaction details in session for verification
    $_SESSION['transaction_details'] = [
        'transaction_no' => $transaction_no,
        'amount' => $amount_paid
    ];
    
    // Get the checkout URL
    $checkout_url = $result['data']['attributes']['redirect']['checkout_url'];
    
    // Redirect to GCash checkout page
    header("Location: " . $checkout_url);
    exit();
} else {
    // Handle API error
    $_SESSION['payment_error'] = "Failed to create payment source. Please try again.";
    if (isset($result['errors'])) {
        $_SESSION['payment_error'] .= " Error: " . json_encode($result['errors']);
    }
    header("Location: http://127.0.0.1/RYC-DORMITELLE-APT-SYSTEM/tenantwebview/PAYMENTFAILED.php");
    exit();
}
?>