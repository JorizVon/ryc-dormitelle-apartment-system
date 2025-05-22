<?php
session_start();

if (!isset($_SESSION['email_account'])) {
    header("Location: ../LOGIN.php");
    exit();
}

include '../db_connect.php';

$email = $_SESSION['email_account'];

$tenant_ID = $tenant_name = $payment_due = $billing_period = $deposit = $balance = $unit_no = "";

if (!empty($email)) {
    $stmt = $conn->prepare("SELECT tenants.tenant_ID, tenant_name, tenant_unit.unit_no, tenant_unit.payment_due, tenant_unit.billing_period, tenant_unit.deposit, tenant_unit.balance 
                            FROM tenants 
                            INNER JOIN tenant_unit ON tenants.tenant_ID = tenant_unit.tenant_ID 
                            WHERE tenants.email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($tenant_ID, $tenant_name, $unit_no, $payment_due, $billing_period, $deposit, $balance);
    $stmt->fetch();
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and validate amount_paid
    $amount_paid = isset($_POST['amount_paid']) ? floatval($_POST['amount_paid']) : 0;
    $payment_date_time = date("Y-m-d H:i:s");
    $payment_method = $_POST['payment_method'] ?? '';

    // Validate amount_paid
    if ($amount_paid <= 0) {
        echo "<script>alert('Amount must be greater than 0'); window.history.back();</script>";
        exit();
    }

    // Determine transaction type
    $transaction_type = '';
    if (isset($_POST['deposit']) && $_POST['deposit'] == 'on') {
        $transaction_type = "Add to Deposit";
    } elseif (isset($_POST['payrent']) && $_POST['payrent'] == 'on') {
        $transaction_type = "Rent Payment";
    } elseif (isset($_POST['depositToPay']) && $_POST['depositToPay'] == 'on') {
        $transaction_type = "Use Deposit";
        $payment_method = "settle with deposit"; // Override
    } else {
        echo "<script>alert('Please select a transaction type'); window.history.back();</script>";
        exit();
    }

    // Validate payment method if not using deposit
    if ($transaction_type !== "Use Deposit" && !in_array($payment_method, ['Cash', 'Gcash'])) {
        echo "<script>alert('Please select a valid payment method'); window.history.back();</script>";
        exit();
    }

    // Determine payment status
    $payment_status = "Partially Paid";
    if ($transaction_type === "Add to Deposit") {
        $payment_status = "Added Deposit";
    } elseif ($amount_paid >= $payment_due) {
        $payment_status = "Fully Paid";
    } elseif ($amount_paid < $payment_due && strtotime($payment_date_time) > strtotime($billing_period)) {
        $payment_status = "Paid Overdue";
    }

    // Generate unique transaction number
    $datePrefix = date("Ymd");

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

    // For Gcash payments, only store the payment data in session
    if ($payment_method === "Gcash") {
        $_SESSION['payment_data'] = [
            'transaction_no' => $transaction_no,
            'unit_no' => $unit_no,
            'tenant_ID' => $tenant_ID,
            'amount_paid' => $amount_paid,
            'payment_date_time' => $payment_date_time,
            'payment_status' => $payment_status,
            'payment_method' => $payment_method,
            'transaction_type' => $transaction_type,
            'tenant_name' => $tenant_name
        ];
        echo "<script>window.location.href='PAYMENTPAGE.php';</script>";
        exit();
    } else {
        // For Cash and Deposit payments, insert directly
        $insert = $conn->prepare("INSERT INTO payments(transaction_no, unit_no, tenant_ID, amount_paid, payment_date_time, payment_status, payment_method, transaction_type, confirmation_status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $insert->bind_param("sssissss", $transaction_no, $unit_no, $tenant_ID, $amount_paid, $payment_date_time, $payment_status, $payment_method, $transaction_type);
        
        if (!$insert->execute()) {
            echo "<script>alert('Error saving payment: " . $conn->error . "'); window.history.back();</script>";
            exit();
        }
        $insert->close();

        if ($payment_method === "Cash") {
            echo "<script>alert('Pay to the landlord through Cash on Hand to confirm payment'); window.location.href='TRANSACTIONSPAGE.php';</script>";
        } else {
            echo "<script>alert('Payment recorded successfully'); window.location.href='TRANSACTIONSPAGE.php';</script>";
        }
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Transactions - Rent Payment</title>

</head>
  <style>
    body {
      margin: 0;
      background-color: #fff;
      padding: 0;
      font-family: Arial, sans-serif;
    }

    .header {
      display: flex;
      position:fixed;
      z-index: 1;
      justify-content: space-between;
      width: 100%;
      height: 80px;
    }

    .hanburgerandaccContainer {
      background-color: #01214B;
      width: 22%;
      height: 100%;
      display: none;
      justify-content: center;
      align-items: center;
    }

    .containerSystemName {
      display: flex;
      align-items: center;
      height: 100%;
      width: 25%;
      background-color: #01214B;
    }

    .systemName {
      width: 100%;
      text-align: center;
      color: #fff;
    }

    .systemName h2 {
      margin: 0;
      font-size: 22px;
      font-weight: 500;
    }

    .systemName h4 {
      margin: 0;
      font-size: 14px;
      font-weight: 500;
    }

    .navbar {
      background-color: #79B1FC;
      width: 80%;
      height: 100%;
      display: flex;
      align-items: center;
      position: relative;
    }

    .navbarContent {
      display: flex;
      align-items: center;
      width: 100%;
      margin-left: 300px;
      margin-right: 90px;
    }

    .navbarContent a {
      text-decoration: none;
      margin: 0 10px;
      color: white;
      font-size: 20px;
    }

    .navbarContent a:hover {
      color: #01214B;
    }

    .loginLogOut {
      display: flex;
      align-items: center;
      justify-content: right;
      margin-left: 50px;
    }

    .hamburger {
      display: none;
      font-size: 28px;
      color: white;
      background: none;
      border: none;
      cursor: pointer;
      margin-left: 12px;
      margin-bottom: 5px;
    }
 /* Modal Styles */
  .modal {
    display: none; /* Initially hidden */
    position: fixed; 
    z-index: 9999; 
    left: 0;
    top: 0;
    width: 100%; 
    height: 100%; 
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
    justify-content: center;
    align-items: center;
  }

  /* When modal is shown, apply flex display */
  .modal.show {
    display: flex;
  }

  .modal-content {
    background-color: #fff;
    padding: 30px 20px;
    border-radius: 8px;
    text-align: center;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
  }

  .modal-content button {
    margin: 10px 5px;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
  }

  .btn-confirm {
    background-color: #28a745;
    color: white;
  }

  .btn-cancel {
    background-color: #dc3545;
    color: white;
  }

  .btn-ok {
    background-color: #007bff;
    color: white;
  }
    /* Tablet view */
    @media screen and (max-width: 768px) {
      .header {
        height: 60px;
        position: relative;
      }

      .hanburgerandaccContainer {
        width: 100%;
        height: 60px;
        display: flex;
        justify-content: space-between;
        background-color: #79B1FC;
      }

      .containerSystemName {
        display: none;
        position: absolute;
        top: 60px;
        left: 0;
        background-color: #01214B;
        width: 100%;
        padding: 10px 0;
        z-index: 10;
        height: 40px;
        flex-direction: column;
      }

      .containerSystemName.show {
        display: flex;
        width: 50vw;
      }

      .systemName h2 {
        font-size: 18px;
      }

      .systemName h4 {
        font-size: 14px;
      }

      .navbar {
        display: none;
        position: absolute;
        top: 122px;
        left: 0;
        background-color: #01214B;
      }

      .navbar.show {
        display: block;
        width: 50vw;
        height: 85vh;
      }

      .navbarContent {
        flex-direction: column;
        align-items: flex-start;
        padding: 10px 20px;
        margin: 0;
      }

      .navbarContent a {
        margin: 8px 0;
        font-size: 18px;
        color: white;
      }

      .loginLogOut {
        display: none;
      }

      .adminSection {
        position: absolute;
        right: 15px;
        top: 20px;
        color: white;
        font-size: 16px;
        display: flex;
        width: 120px;
        align-items: center;
      }

      .adminSection a {
        color: white;
        text-decoration: none;
        margin-left: 5px;
        margin-right: 5px;
      }

      .hamburger {
        display: block;
        font-size: 35px;
      }
    }

    @media screen and (max-width: 480px) {
      .systemName h2 {
        font-size: 14px;
      }

      .systemName h4 {
        font-size: 9px;
      }

      .navbarContent a {
        font-size: 16px;
      }
    }

    .pageTitle {
        height: 100px;
        display: flex;
        align-items: center;
        border-bottom: solid 1px #2262B8;
    }
    .pageTitle h1 {
        margin-left: 60px;
        margin-top: 40px;
        font-size: 33px;
        color: #2262B8;
    }
    .transactionchoices {
        width: 100%;
        height: 100px;
        align-items: center;
        display: flex;
        justify-content: center;
    }
    .transactionchoices a {
        text-decoration: none;
        font-size: 22px;
        margin: 0 10px;
        margin-top: 70px;
        border: solid 2px #2262B8;
        color: #2262B8;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 30px;
        width: 350px;
        height: 50px;
    }
    .transactionformContainer {
        width: 100%;
        height: auto;
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 30px;
        padding-bottom: 40px;
    }
    .transactionform {
        width: 48%;
        height: auto;
        border: solid 2px #79B1FC;
        border-bottom-left-radius: 45px;
        padding-bottom: 30px;
    }
    
    /* Improved checkbox container styles */
    .transactionTypecontainer {
        width: 90%;
        margin: 0 auto 20px auto;
        padding: 15px 0;
        display: flex;
        justify-content: space-around;
        align-items: center;
        flex-wrap: wrap;
        box-shadow: 0 4px 2px -1px rgba(0, 0, 0, 0.2);
        border-radius: 8px;
    }
    
    .transactionTypecontainer div {
        display: flex;
        align-items: center;
        margin: 8px 5px;
        padding: 5px;
    }
    
    /* Improved checkbox styling */
    .checkinput {
        width: 20px !important;
        height: 20px;
        margin-right: 8px !important;
        cursor: pointer;
        accent-color: #2262B8;
    }
    
    .checklabel {
        font-size: 17px !important;
        color: #2262B8 !important;
        margin: 0 !important;
        cursor: pointer;
        display: inline !important;
        width: auto !important;
    }
    
    .form {
        margin-top: 20px;
        margin-left: 50px;
        margin-right: 50px;
    }
    
    .form > div {
        margin-bottom: 15px;
    }
    
    .form label {
        width: 180px;
        font-size: 16px;
        margin-right: 20px;
        display: inline-block;
        color: #2262B8;
        vertical-align: middle;
    }
    
    .form input {
        width: calc(100% - 230px);
        font-size: 16px;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        vertical-align: middle;
    }
    .form select {
        width: calc(100% - 212px);
        font-size: 16px;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        position: relative;
        right: 5px;
        vertical-align: middle;
    }
    
    .form span {
        color: #2262B8;
        font-size: 16px;
    }
    
    .submitbtnContainer {
        width: 100%;
        display: flex;
        justify-content: flex-end;
        margin-top: 30px;
        padding-right: 30px;
    }
    
    #submitBtn {
        width: 200px;
        height: 40px;
        background-color: #2262B8;
        color: #fff;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    
    #submitBtn:hover {
        background-color: #194b91;
    }
    
    .mainBody {
      position: relative;
      top: 75px;
    }
    
    /* Responsive Styles for mainBody */
    @media screen and (max-width: 992px) {
      .transactionform {
        width: 70%;
      }
    }

    @media screen and (max-width: 768px) {
      .pageTitle {
        height: 70px;
      }
      
      .pageTitle h1 {
        margin-left: 30px;
        margin-top: 20px;
        font-size: 28px;
      }
      
      .transactionchoices {
        height: auto;
        flex-direction: column;
        padding: 20px 0;
      }
      
      .transactionchoices a {
        width: 80%;
        margin: 10px auto;
        font-size: 18px;
        height: 45px;
      }
      
      .transactionform {
        width: 85%;
        border-bottom-left-radius: 30px;
      }
      
      .transactionTypecontainer {
        justify-content: center;
      }
      
      .transactionTypecontainer div {
        margin: 5px 10px;
      }
      
      .form {
        margin-left: 20px;
        margin-right: 20px;
      }
      
      .form label {
        width: 100%;
        display: block;
        margin-right: 0;
        margin-bottom: 5px;
      }
      
      .form input {
        width: 100%;
        display: block;
      }
      .form select {
        width: 103.5%;
        display: block;
        position: relative;
        left: 1px;
      }
      
      .submitbtnContainer {
        justify-content: center;
        padding-right: 0;
      }
    }

    @media screen and (max-width: 480px) {
      .pageTitle h1 {
        margin-left: 20px;
        font-size: 24px;
      }
      
      .transactionchoices a {
        width: 85%;
        font-size: 16px;
        height: 40px;
      }
      
      .transactionform {
        width: 90%;
      }
      
      .checkinput {
        width: 18px !important;
        height: 18px;
      }
      
      .checklabel {
        font-size: 15px !important;
      }
      
      .form {
        margin-left: 15px;
        margin-right: 15px;
      }
      
      .form label {
        font-size: 14px;
      }
      
      .form input, .form select {
        font-size: 14px;
        padding: 6px;
      }
      
      #submitBtn {
        width: 160px;
        height: 35px;
        font-size: 14px;
      }
    }

    /*FOOTER*/
    .footer {
      margin-top: 100px;
      display: flex;
      justify-content: space-between;
      width: 100%;
      height: 140px;
    }
    
    .footerContainer {
      background-color: #2262B8;
      width: 100%;
      height: 100%;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .contactleftside {
      position: relative;
      bottom: 13px;
      margin-left: 30px;
    }
    
    .contactleftside h6 {
      font-size: 15px;
      margin-bottom: 0;
      color: #fff;
    }
    
    .contactleftside p {
      font-size: 13px;
      margin-top: 0;
      color: #fff;
    }
    
    .contactleftside img {
      margin-right: 5px;    
      height: 8px;
      width: 8px;
    }
    
    .contactrightside {
      margin-right: 30px;
      text-align: center;
    }
    
    .contactrightside p {
      font-size: 14px;
      color: #fff;
    }

    /* Responsive styles for footer */
    @media screen and (max-width: 992px) {
      .footer {
        height: auto;
      }
      
      .footerContainer {
        padding: 20px 0;
      }
      
      .contactleftside {
        margin-left: 20px;
        bottom: 0;
      }
      
      .contactrightside {
        margin-right: 20px;
      }
    }

    @media screen and (max-width: 768px) {
      .footerContainer {
        flex-direction: column;
        padding: 15px 0;
      }
      
      .contactleftside {
        margin: 0 0 15px 0;
        text-align: center;
        width: 90%;
      }
      
      .contactleftside h6 {
        font-size: 14px;
      }
      
      .contactleftside p {
        font-size: 12px;
      }
      
      .contactrightside {
        margin: 0;
        width: 90%;
      }
      
      .contactrightside p {
        font-size: 12px;
      }
    }

    @media screen and (max-width: 480px) {
      .footerContainer {
        padding: 10px 0;
      }
      
      .contactleftside h6 {
        font-size: 12px;
      }
      
      .contactleftside p {
        font-size: 10px;
      }
      
      .contactrightside p {
        font-size: 10px;
      }
      
      .contactleftside img {
        width: 12px;
        height: auto;
      }
    }
  </style>
<body>
  <div class="header">
    <div class="hanburgerandaccContainer">
      <button class="hamburger" onclick="toggleMenu()">☰</button>
      <div class="adminSection">
        <a href="TENANTACCOUNTPAGE.php"><img src="../staticImages/userIcon.png" alt="userIcon" style="height: 25px; width: 25px; display: flex; justify-content: center;"></a> |
        <a href="../LOGIN.php">Log Out</a>
      </div>
    </div>
    <div class="containerSystemName" id="containerSystemName">
      <div class="systemName">
        <h2>RYC Dormitelle</h2>
        <h4>APARTMENT MANAGEMENT SYSTEM</h4>
      </div>
    </div>
    <div class="navbar" id="navbar">
      <div class="navbarContent">
        <a href="TENANTHOMEPAGE.php">Home</a>
        <a href="TENANTHOMEPAGE.php#aboutRYC" class="scroll-link">About</a>
        <a href="TENANTHOMEPAGE.php#availUnitsContainer" class="scroll-link">Available Units</a>
        <a href="TRANSACTIONSPAGE.php">Transactions</a>
        <a href="INBOXPAGE.php">Inbox</a>
        <div class="loginLogOut">
          <a href="TENANTACCOUNTPAGE.php"><img src="../staticImages/userIcon.png" alt="userIcon" style="height: 45px; width: 45px; display: flex; justify-content: center;"></a>
          <p style="font-size: 20px; color: white; margin: 0 5px;">|</p>
          <a href="../LOGIN.php">Log Out</a>
        </div>
      </div>
    </div>
  </div>

  <div class="mainBody">
    <div class="mainBodyContiner">
        <div class="pageTitle">
            <h1>Transactions</h1>
        </div>
        <div class="transactionchoices">
            <a href="TRANSACTIONSPAGE.php" style="background-color: #2262B8; color: #fff;">Rent Payments</a>
            <a href="TRANSACTIONHISTORYPAGE.php">Transaction History</a>
        </div>
        <div class="transactionformContainer">
        <div class="transactionform">
          <form action="" method="POST" class="form" id="paymentForm">
            <div class="transactionTypecontainer">
              <div>
                <input type="checkbox" name="deposit" id="deposit" class="checkinput" onclick="setTransactionType(this)" checked>
                <label for="deposit" class="checklabel">Add to deposit</label>
              </div>
              <div>
                <input type="checkbox" name="payrent" id="payrent" class="checkinput" onclick="setTransactionType(this)">
                <label for="payrent" class="checklabel">Pay rent</label>
              </div>
              <div>
                <input type="checkbox" name="depositToPay" id="depositToPay" class="checkinput" onclick="setTransactionType(this)">
                <label for="depositToPay" class="checklabel">Use deposit to pay</label>
              </div>
            </div>

            <div><label><b>Payment Date</b></label><span name="payment_date_time"><b><?= date('Y-m-d H:i:s') ?></b></span></div>
            <div><label>Tenant ID</label><input type="text" name="tenant_ID" value="<?= htmlspecialchars($tenant_ID) ?>" readonly></div>
            <div><label>Full Name</label><input type="text" name="tenant_name" value="<?= htmlspecialchars($tenant_name) ?>" readonly></div>
            <div><label>Payment Due</label><input type="text" name="lease_payment_due" value="<?= htmlspecialchars($payment_due) ?>" readonly></div>
            <div><label>Billing Period</label><input type="text" name="billing_period" value="<?= htmlspecialchars($billing_period) ?>" readonly></div>
            <div><label>Current Deposit</label><input type="text" name="current_deposit" value="₱ <?= htmlspecialchars(number_format($deposit, 2)) ?>" readonly></div>
            <div><label>Remaining Balance</label><input type="text" name="current_balance" value="₱ <?= htmlspecialchars(number_format($balance, 2)) ?>" readonly></div>
            <div><label>Amount</label><input type="number" name="amount_paid" id="amount_paid" required min="1" step="0.01"></div>
            
            <div>
              <label>Payment Method</label>
              <select name="payment_method" id="payment_method" required>
                <option value="">Select Payment Method</option>
                <option value="Cash">Cash</option>
                <option value="Gcash">Gcash</option>
                <option value="settle with deposit" disabled>Settle with Deposit</option>
              </select>
            </div>

            <div class="submitbtnContainer">
              <button type="submit" id="submitBtn">Add to deposit</button>
            </div>
          </form>
        </div>
        </div>
    </div>
  </div>

  <div class="footer">
    <div class="footerContainer">
      <div class="contactleftside">
        <h6>Contact Information & Inquiry Form</h6>
        <p><img src="../tenantviewIcons/profileIcon.png" alt="Profile Icon">Manager: Kyle Angela Catiis<br><img src="../tenantviewIcons/addressIcon.png" alt="Address Icon">Address: Ofelia Pasig, Daet, Camarines Norte<br>
          <img src="../tenantviewIcons/IconMail.png" alt="Mail Icon">Email: kyleangelacatiis@gmail.com<br><img src="../tenantviewIcons/phoneIcon.png" alt="Phone Icon">Phone: 0912-345-6789</p>
      </div>
      <div class="contactrightside">
        <p>Apartment Management System @ 2025.<br>All Rights Reserved.<br>Developed by Joriz Gutierrez</p>
      </div>
    </div>
  </div>
  <!-- Confirm Transaction Modal -->
  <div id="confirmModal" class="modal">
    <div class="modal-content">
      <h3>Confirm Transaction</h3>
      <p>Are you sure you want to proceed with this transaction?</p>
      <button onclick="submitForm()" class="btn-confirm">Yes, Proceed</button>
      <button onclick="closeModal('confirmModal')" class="btn-cancel">Cancel</button>
    </div>
  </div>

  <!-- Success Modal -->
  <div id="successModal" class="modal">
    <div class="modal-content">
      <h3>Transaction Successful</h3>
      <p>Your transaction request was submitted successfully!</p>
      <button onclick="redirectToPage()" class="btn-ok">OK</button>
    </div>
  </div>


  <script>
    // Initial setup
    document.addEventListener('DOMContentLoaded', function() {
      // Initial setup - ensure deposit is checked and set appropriate payment method options
      setTransactionType(document.getElementById('deposit'));
    });

    function toggleMenu() {
      document.getElementById('containerSystemName').classList.toggle('show');
      document.getElementById('navbar').classList.toggle('show');
    }

    function setTransactionType(selected) {
      // Uncheck all first
      document.querySelectorAll('.checkinput').forEach(cb => cb.checked = false);
      selected.checked = true;

      const paymentMethod = document.getElementById('payment_method');
      const settleOption = paymentMethod.querySelector('option[value="settle with deposit"]');
      const amountField = document.getElementById('amount_paid');

      // Update submit button label
      const labelMap = {
        deposit: "Add to deposit",
        payrent: "Pay rent",
        depositToPay: "Use deposit to pay"
      };
      document.getElementById('submitBtn').textContent = labelMap[selected.id] || "Submit";
      
      // Handle payment method based on selected option
      if (selected.id === 'depositToPay') {
        // When using deposit to pay
        paymentMethod.value = "settle with deposit";
        settleOption.disabled = false;
        
        // Disable other options
        Array.from(paymentMethod.options).forEach(opt => {
          if (opt.value !== "settle with deposit") {
            opt.disabled = true;
          }
        });
        
        // Set max amount to deposit value
        const depositValue = parseFloat('<?= $deposit ?>');
        if (depositValue > 0) {
          amountField.max = depositValue;
        }
      } else {
        // Remove max limit for other transaction types
        amountField.removeAttribute('max');
        
        // For other payment types (Add to deposit or Pay rent)
        if (paymentMethod.value === "settle with deposit") {
          paymentMethod.value = ""; // Reset if previously set to settle with deposit
        }
        
        settleOption.disabled = true;
        
        // Enable Cash and Gcash options
        Array.from(paymentMethod.options).forEach(opt => {
          if (opt.value !== "settle with deposit") {
            opt.disabled = false;
          }
        });
      }
    }

    // Form validation before submit
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
      const amount = parseFloat(document.getElementById('amount_paid').value);
      const depositToPay = document.getElementById('depositToPay').checked;
      
      if (depositToPay) {
        const depositValue = parseFloat('<?= $deposit ?>');
        if (amount > depositValue) {
          e.preventDefault();
          alert('The amount cannot exceed your current deposit of ₱' + depositValue.toFixed(2));
          return false;
        }
      }
      
      const paymentMethod = document.getElementById('payment_method').value;
      if (!paymentMethod) {
        e.preventDefault();
        alert('Please select a payment method');
        return false;
      }
      
      return true;
    });

    const form = document.getElementById("paymentForm");
    let isSubmitting = false; // Flag to prevent multiple submissions

    form.addEventListener("submit", function (e) {
      e.preventDefault(); // Prevent direct form submission
      
      // Prevent multiple submissions
      if (isSubmitting) return;
      
      // Show confirmation modal only once
      document.getElementById("confirmModal").classList.add("show");
    });

    function closeModal(modalId) {
      document.getElementById(modalId).classList.remove("show");
    }

    function submitForm() {
      if (isSubmitting) return; // Guard against multiple submissions
      isSubmitting = true;
      
      closeModal('confirmModal');
      
      // Get the selected payment method
      const paymentMethod = document.getElementById('payment_method').value;
      
      // Submit via fetch
      fetch("", {
        method: "POST",
        body: new FormData(form)
      })
      .then(response => response.text())
      .then(data => {
        isSubmitting = false; // Reset flag
        
        // Redirect based on payment method
        if (paymentMethod === "Gcash") {
          window.location.href = 'PAYMENTPAGE.php';
        } else {
          // For Cash and settle with deposit, show success modal
          document.getElementById("successModal").classList.add("show");
        }
      })
      .catch(error => {
        isSubmitting = false; // Reset flag on error
        alert("Error submitting form: " + error);
      });
    }

    function redirectToPage() {
      closeModal('successModal');
      // Redirect to transactions page after successful payment
      window.location.href = 'TRANSACTIONSPAGE.php';
    }
  </script>
</body>
</html>