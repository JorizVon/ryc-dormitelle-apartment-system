<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['email_account'])) {
    header("Location: ../LOGIN.php");
    exit();
}

// Get error message if any
$error_message = isset($_SESSION['payment_error']) ? $_SESSION['payment_error'] : "An unknown error occurred during payment processing.";

// Clear payment session data
unset($_SESSION['source_id']);
unset($_SESSION['transaction_details']);
unset($_SESSION['payment_data']);
unset($_SESSION['payment_error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed</title>
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
        .error-icon {
            color: #dc3545;
            font-size: 80px;
            margin-bottom: 20px;
        }
        h1 {
            color: #dc3545;
            margin-bottom: 20px;
        }
        .error-details {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            text-align: left;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #2262B8;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #1b4b8f;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-icon">✗</div>
        <h1>Payment Failed</h1>
        <p>We couldn't process your GCash payment at this time.</p>
        
        <div class="error-details">
            <p><strong>Reason:</strong> <?php echo htmlspecialchars($error_message); ?></p>
            <p>Please try again or choose a different payment method.</p>
        </div>
        
        <a href="TRANSACTIONPAGE.php" class="btn">Try Again</a>
        <a href="TRANSACTIONSPAGE.php" class="btn btn-secondary">Back to Transactions</a>
    </div>
</body>
</html>