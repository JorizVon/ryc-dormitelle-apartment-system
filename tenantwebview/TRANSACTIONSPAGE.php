<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['email_account'])) {
    header("Location: ../LOGIN.php");
    exit();
}

include '../db_connect.php';

// Check if database connection exists
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn->connect_error ?? "Connection not established"));
}

$email = $_SESSION['email_account'];

// Initialize variables with default values
$tenant_ID = $tenant_name = $payment_due = $billing_period = $deposit = $balance = $unit_no = $start_date = $end_date = "";
$monthly_rent = 0;

// Fetch tenant information
if (!empty($email)) {
    try {
        $stmt = $conn->prepare("SELECT tenants.tenant_ID, tenant_name, tenant_unit.unit_no, tenant_unit.payment_due, tenant_unit.billing_period, tenant_unit.security_deposit, tenant_unit.start_date, tenant_unit.end_date
                                FROM tenants 
                                INNER JOIN tenant_unit ON tenants.tenant_ID = tenant_unit.tenant_ID 
                                WHERE tenants.email = ?");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("s", $email);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $tenant_ID = $row['tenant_ID'];
            $tenant_name = $row['tenant_name'];
            $unit_no = $row['unit_no'];
            $payment_due = $row['payment_due'];
            $billing_period = $row['billing_period'];
            $deposit = $row['security_deposit'];
            $start_date = $row['start_date'];
            $end_date = $row['end_date'];
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        die("An error occurred while fetching user data. Please try again later.");
    }
    
    try {
        $rentstmt = $conn->prepare("SELECT units.monthly_rent_amount FROM units INNER JOIN tenant_unit ON units.unit_no = tenant_unit.unit_no INNER JOIN tenants ON tenant_unit.tenant_ID = tenants.tenant_ID
        WHERE tenants.email = ?");
        
        if (!$rentstmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $rentstmt->bind_param("s", $email);
        if (!$rentstmt->execute()) {
            throw new Exception("Execute failed: " . $rentstmt->error);
        }
        $rentresult = $rentstmt->get_result();
        if($row = $rentresult->fetch_assoc()) {
            $monthly_rent = $row['monthly_rent_amount'];
        }
        $rentstmt->close();
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        die("An error occurred while fetching user data. Please try again later.");
    }
}

// --- UPDATED LOGIC: Calculate payment options based on REMAINING balance ---
$payment_options = [];
if (!empty($start_date) && !empty($end_date)) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = $start->diff($end);
    
    // Get unpaid months count
    $unpaid_query = "SELECT COUNT(*) as unpaid_count FROM payment_checklist WHERE email_account = ? AND pay_status = 0";
    $unpaid_stmt = $conn->prepare($unpaid_query);
    $unpaid_stmt->bind_param("s", $email);
    $unpaid_stmt->execute();
    $unpaid_result = $unpaid_stmt->get_result();
    $unpaid_row = $unpaid_result->fetch_assoc();
    $remaining_months = intval($unpaid_row['unpaid_count']);
    $unpaid_stmt->close();
    
    // 1. Current month payment (Show if at least 1 month remains)
    if ($remaining_months >= 1) {
        $payment_options[] = [
            'label' => '1 Month',
            'months' => 1,
            'amount' => $monthly_rent * 1
        ];
    }
    
    // 2. Two months payment (Show ONLY if at least 2 months remain)
    if ($remaining_months >= 2) {
        $payment_options[] = [
            'label' => '2 Months',
            'months' => 2,
            'amount' => $monthly_rent * 2
        ];
    }
    
    // 3. Three months payment (Show ONLY if at least 3 months remain)
    if ($remaining_months >= 3) {
        $payment_options[] = [
            'label' => '3 Months',
            'months' => 3,
            'amount' => $monthly_rent * 3
        ];
    }
    
    // 4. Remaining Balance (Show if user owes more than 3 months)
    // This dynamically replaces "Full Contract" or "Half Contract" with the exact remaining amount
    if ($remaining_months > 3) {
        $payment_options[] = [
            'label' => 'Full Remaining Balance (' . $remaining_months . ' Months)',
            'months' => $remaining_months,
            'amount' => $monthly_rent * $remaining_months
        ];
    }

    // 5. Handle case where remaining is 0 (Fully Paid)
    if ($remaining_months == 0) {
        $payment_options[] = [
            'label' => 'Fully Paid (No Balance Due)',
            'months' => 0,
            'amount' => 0
        ];
    }
}
// --- END UPDATED LOGIC ---

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Set timezone to Philippines
        date_default_timezone_set('Asia/Manila');
        
        // Get payment option
        $payment_option = isset($_POST['payment_option']) ? intval($_POST['payment_option']) : 0;
        
        if ($payment_option <= 0) {
            echo "<script>alert('Please select a valid payment option'); window.history.back();</script>";
            exit();
        }
        
        $amount_paid = $monthly_rent * $payment_option;
        $payment_date_time = date("Y-m-d H:i:s");
        $payment_method = $_POST['payment_method'] ?? '';

        // Validate amount_paid
        if ($amount_paid <= 0) {
            echo "<script>alert('Amount must be greater than 0'); window.history.back();</script>";
            exit();
        }

        // Set transaction type to Rent Payment only
        $transaction_type = "Rent Payment";

        // Validate payment method
        if (!in_array($payment_method, ['Cash', 'Gcash'])) {
            echo "<script>alert('Please select a valid payment method'); window.history.back();</script>";
            exit();
        }

        // Set payment status
        $payment_status = "Paid";

        // Generate unique transaction number
        $datePrefix = date("Ymd");
        $maxAttempts = 9999;
        $sequence = 1;

        // Get count of existing transactions for today
        $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM payments WHERE transaction_no LIKE CONCAT(?, '%')");
        if (!$countStmt) {
            throw new Exception("Prepare failed for count query: " . $conn->error);
        }
        
        $likeParam = $datePrefix . "%";
        $countStmt->bind_param("s", $likeParam);
        
        if (!$countStmt->execute()) {
            throw new Exception("Execute failed for count query: " . $countStmt->error);
        }
        
        $countResult = $countStmt->get_result();
        $countRow = $countResult->fetch_assoc();
        $sequence = $countRow['count'] + 1;
        $countStmt->close();

        // Generate unique transaction number
        $transaction_no = "";
        do {
            $formattedSequence = str_pad($sequence, 4, '0', STR_PAD_LEFT);
            $transaction_no = $datePrefix . $formattedSequence;

            $checkStmt = $conn->prepare("SELECT COUNT(*) as existing FROM payments WHERE transaction_no = ?");
            if (!$checkStmt) {
                throw new Exception("Prepare failed for check query: " . $conn->error);
            }
            
            $checkStmt->bind_param("s", $transaction_no);
            
            if (!$checkStmt->execute()) {
                throw new Exception("Execute failed for check query: " . $checkStmt->error);
            }
            
            $checkResult = $checkStmt->get_result();
            $checkRow = $checkResult->fetch_assoc();
            $existing = $checkRow['existing'];
            $checkStmt->close();

            $sequence++;
        } while ($existing > 0 && $sequence <= $maxAttempts);

        if ($sequence > $maxAttempts + 1) {
            throw new Exception("Daily transaction limit reached.");
        }

        // For Gcash payments, store payment data in session
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
                'tenant_name' => $tenant_name,
                'months_paid' => $payment_option
            ];
            echo "<script>window.location.href='PAYMENTPAGE.php';</script>";
            exit();
        } else {
            // For Cash payments, insert directly with payment_option
            $insertStmt = $conn->prepare("INSERT INTO payments(transaction_no, unit_no, tenant_ID, amount_paid, payment_date_time, payment_status, payment_method, transaction_type, confirmation_status, payment_option) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
            
            if (!$insertStmt) {
                throw new Exception("Prepare failed for insert query: " . $conn->error);
            }
            
            $insertStmt->bind_param("sssissssi", $transaction_no, $unit_no, $tenant_ID, $amount_paid, $payment_date_time, $payment_status, $payment_method, $transaction_type, $payment_option);
            
            if (!$insertStmt->execute()) {
                throw new Exception("Execute failed for insert query: " . $insertStmt->error);
            }
            
            $insertStmt->close();

            echo "<script>alert('Pay to the landlord through Cash on Hand to confirm payment'); window.location.href='TRANSACTIONSPAGE.php';</script>";
            exit();
        }
    } catch (Exception $e) {
        error_log("Payment processing error: " . $e->getMessage());
        echo "<script>alert('An error occurred while processing your payment. Please try again later.'); window.history.back();</script>";
        exit();
    }
}

// Set default values to prevent undefined variable warnings
$tenant_ID = $tenant_ID ?? '';
$tenant_name = $tenant_name ?? '';
$payment_due = $payment_due ?? 0;
$billing_period = $billing_period ?? '';
$deposit = $deposit ?? 0;
$unit_no = $unit_no ?? '';

// Set timezone for display
date_default_timezone_set('Asia/Manila');

// Set page title for header
$page_title = "Transactions - Rent Payment";

// Include header
include 'tenant_header.php';
?>

<style>
    .mainBody {
      position: relative;
      top: 92px;
      width: 100%;
      min-height: calc(100vh - 92px);
      background: #f8fafc;
    }

    .mainBodyContiner {
      width: 100%;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 2rem;
    }

    .pageTitle {
      height: 100px;
      display: flex;
      align-items: center;
      border-bottom: solid 1px #2262B8;
    }

    .pageTitle h1 {
      margin-left: 0;
      margin-top: 0;
      font-size: 2.5rem;
      color: #1e3c72;
      font-weight: 700;
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
        width: 64%;
        height: auto;
        border: solid 2px #79B1FC;
        border-bottom-left-radius: 45px;
        padding-bottom: 30px;
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
        background: linear-gradient(135deg, #79B1FC, #4A90E2);
        color: #fff;
        border: none;
        border-radius: 25px;
        font-size: 16px;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    
    #submitBtn:hover {
        background-color: #194b91;
    }
    
    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
    }

    .modal.show {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .modal-content {
      background-color: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      max-width: 500px;
      width: 90%;
      text-align: center;
    }

    .modal-content h3 {
      color: #4CAF50;
      margin-bottom: 15px;
    }

    .modal-content p {
      margin-bottom: 10px;
      line-height: 1.5;
    }

    .btn-ok, .btn-confirm, .btn-cancel {
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      margin: 5px;
      font-size: 14px;
    }

    .btn-ok {
      background-color: #1976d2;
      color: white;
    }

    .btn-confirm {
      background-color: #4caf50;
      color: white;
    }

    .btn-cancel {
      background-color: #f44336;
      color: white;
    }

    .btn-ok:hover, .btn-confirm:hover, .btn-cancel:hover {
      opacity: 0.9;
    }
    
    /* Responsive Styles for mainBody */
    @media screen and (max-width: 992px) {
      .transactionform {
        width: 70%;
      }
    }

    @media screen and (max-width: 768px) {
      .mainBody {
        top: 60px;
      }

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

    .payment-option-select {
        width: calc(100% - 212px);
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 16px;
        background-color: white;
        cursor: pointer;
        transition: border-color 0.3s;
        position: relative;
        right: 5px;
        vertical-align: middle;
    }
    
    .payment-option-select:focus {
        outline: none;
        border-color: #2262B8;
    }
    
    .amount-display {
        margin-top: 10px;
        margin-left: 200px;
        padding: 12px;
        background: #f0f4ff;
        border-radius: 6px;
        text-align: center;
        max-width: calc(100% - 230px);
    }
    
    .amount-display .label {
        font-size: 12px;
        color: #666;
        margin-bottom: 5px;
    }
    
    .amount-display .amount {
        font-size: 24px;
        font-weight: 600;
        color: #1e3c72;
    }
    
    @media screen and (max-width: 768px) {
        .payment-option-select {
            width: 103.5%;
            position: relative;
            left: 1px;
        }
    
        .amount-display {
            margin-left: 0;
            max-width: 100%;
        }
    }
  </style>

<div class="mainBody">
  <div class="mainBodyContiner">
    <div class="pageTitle">
      <h1>Transactions</h1>
    </div>
    <div class="transactionchoices">
      <a href="TRANSACTIONSPAGE.php" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: #fff;">Rent Payments</a>
      <a href="TRANSACTIONHISTORYPAGE.php">Transaction History</a>
    </div>
    <div class="transactionformContainer">
      <div class="transactionform">
        <form action="" method="POST" class="form" id="paymentForm">
          <div><label><b>Payment Date</b></label><span name="payment_date_time"><b><?= date('Y-m-d H:i:s') ?></b></span></div>
          <div><label>Tenant ID</label><input type="text" name="tenant_ID" value="<?= htmlspecialchars($tenant_ID) ?>" readonly></div>
          <div><label>Full Name</label><input type="text" name="tenant_name" value="<?= htmlspecialchars($tenant_name) ?>" readonly></div>
          
          <div>
            <label>Payment Option</label>
            <select name="payment_option" id="payment_option" class="payment-option-select" required>
              <option value="">Select Payment Duration</option>
              <?php foreach ($payment_options as $option): ?>
                <option value="<?= $option['months'] ?>" data-amount="<?= $option['amount'] ?>">
                  <?= htmlspecialchars($option['label']) ?> - ₱<?= number_format($option['amount'], 2) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="amount-display" id="amount_display" style="display: none;">
              <div class="label">Total Amount to Pay</div>
              <div class="amount" id="display_amount">₱ 0.00</div>
            </div>
          </div>
          
          <div>
            <label>Payment Method</label>
            <select name="payment_method" id="payment_method" required>
              <option value="">Select Payment Method</option>
              <option value="Cash">Cash</option>
              <option value="Gcash">Gcash</option>
            </select>
          </div>

          <div class="submitbtnContainer">
            <button type="submit" id="submitBtn">Pay Rent</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Confirm Transaction Modal -->
<div id="confirmModal" class="modal">
  <div class="modal-content">
    <h3>Confirm Transaction</h3>
    <p>Are you sure you want to proceed with this rent payment?</p>
    <button onclick="submitForm()" class="btn-confirm">Yes, Proceed</button>
    <button onclick="closeModal('confirmModal')" class="btn-cancel">Cancel</button>
  </div>
</div>

<!-- Success Modal -->
<div id="successModal" class="modal">
  <div class="modal-content">
    <h3>Payment Submitted</h3>
    <p>Your rent payment request was submitted successfully!</p>
    <button onclick="redirectToPage()" class="btn-ok">OK</button>
  </div>
</div>

<script>
  // Update amount display when payment option changes
  document.getElementById('payment_option').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const amount = selectedOption.getAttribute('data-amount');
    const displayDiv = document.getElementById('amount_display');
    const displayAmount = document.getElementById('display_amount');
    
    if (amount) {
      displayAmount.textContent = '₱ ' + parseFloat(amount).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      displayDiv.style.display = 'block';
    } else {
      displayDiv.style.display = 'none';
    }
  });

  // Form validation before submit
  document.getElementById('paymentForm').addEventListener('submit', function(e) {
    const paymentOption = document.getElementById('payment_option').value;
    
    if (!paymentOption || parseInt(paymentOption) === 0) {
      e.preventDefault();
      alert('Please select a valid payment option (you may be fully paid)');
      return false;
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
  let isSubmitting = false;

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    
    if (isSubmitting) return;
    
    document.getElementById("confirmModal").classList.add("show");
  });

  function closeModal(modalId) {
    document.getElementById(modalId).classList.remove("show");
  }

  function submitForm() {
    if (isSubmitting) return;
    isSubmitting = true;
    
    closeModal('confirmModal');
    
    const paymentMethod = document.getElementById('payment_method').value;
    
    fetch("", {
      method: "POST",
      body: new FormData(form)
    })
    .then(response => response.text())
    .then(data => {
      isSubmitting = false;
      
      if (paymentMethod === "Gcash") {
        window.location.href = 'PAYMENTPAGE.php';
      } else {
        document.getElementById("successModal").classList.add("show");
      }
    })
    .catch(error => {
      isSubmitting = false;
      alert("Error submitting form: " + error);
    });
  }

  function redirectToPage() {
    closeModal('successModal');
    window.location.href = 'TRANSACTIONSPAGE.php';
  }
</script>

<?php
// Include footer (which includes the chat component)
include 'footer.php';
?>