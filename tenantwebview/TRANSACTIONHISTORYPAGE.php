<?php
session_start();

if (!isset($_SESSION['email_account'])) {
    header("Location: ../LOGIN.php");
    exit();
}

include '../db_connect.php';

$email = $_SESSION['email_account'];

// Get payment checklist for the logged-in user
$query = "SELECT pc.`monthly_due_dates`, pc.`pay_status`, ci.`start_date`, ci.`end_date`, ci.monthly_rate
          FROM `payment_checklist` pc
          LEFT JOIN `contract_information` ci ON pc.email_account = ci.email_account
          WHERE pc.email_account = ? AND (ci.contract_status = 'First Contract' OR ci.contract_status = 'Contract Renewal')
          ORDER BY pc.monthly_due_dates ASC";

// Check if the connection is valid
if (!$conn) {
    die("Connection failed: Unable to connect to database");
}

// Prepare statement with error handling
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Preparation failed: " . $conn->error);
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// Group payments by year
$payments_by_year = [];
while ($row = $result->fetch_assoc()) {
    $date = new DateTime($row['monthly_due_dates']);
    $year = $date->format('Y');
    $month = $date->format('F');
    $day = $date->format('j');
    
    if (!isset($payments_by_year[$year])) {
        $payments_by_year[$year] = [];
    }
    
    $payments_by_year[$year][] = [
        'month' => $month,
        'day' => $day,
        'due_date' => $row['monthly_due_dates'],
        'pay_status' => $row['pay_status'],
        'monthly_rate' => $row['monthly_rate'] // Storing the rate
    ];
}

// Format current date for display
$current_date = date('M j, Y');

// Set page title for header
$page_title = "Transaction History - RYC Dormitelle";

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
    transition: all 0.3s;
  }

  .transactionchoices a:hover {
    background-color: #f8fafc;
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

  .payment-checklist-container {
    width: 64%;
    height: auto;
    border: solid 2px #79B1FC;
    border-bottom-left-radius: 45px;
    padding-bottom: 30px;
    background: white;
    margin: 0;
  }
  
  .checklist-header {
    background-color: transparent;
    border-bottom: 1px solid #79B1FC;
    box-shadow: 0 4px 2px -1px rgba(0, 0, 0, 0.2);
    padding: 15px 20px;
    margin: 0;
    text-align: left;
  }
  
  .checklist-header h2 {
    font-size: 17px;
    font-weight: normal;
    margin: 0;
    color: #2262B8;
  }
  
  .checklist-header p {
    display: none;
  }
  
  .year-section {
    margin-bottom: 0;
    padding: 5px;
  }
  
  .year-header {
    font-size: 16px;
    font-weight: bold;
    color: #2262B8;
    margin-bottom: 5px;
    margin-left: 8px;
    padding: 5px 5px;
    border-bottom: none;
  }
  
  .payment-item {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    margin: 10px;
    background: white;
    border: 1px solid #B7B5B5;
    border-radius: 8px;
    transition: background-color 0.3s;
  }
  
  .payment-item:hover {
    background-color: #f8fafc;
  }
  
  .payment-checkbox {
    width: 20px;
    height: 20px;
    min-width: 20px;
    margin-right: 15px;
    cursor: default;
    accent-color: #2262B8;
  }
  
  .payment-info {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 40px;
  }
  
  .payment-month {
    font-weight: 600;
    color: #1e3c72;
    font-size: 14px;
    margin: 0;
  }
  
  .payment-due {
    color: #64748b;
    font-size: 10px;
    margin: 0;
    margin-top: 5px;
  }
  
  .payment-amount {
    font-weight: 600;
    color: #2262B8;
    font-size: 14px;
    width: 20%;
    text-align: right;
  }
  
  .payment-item.paid {
    background: white;
  }
  
  .payment-item.paid:hover {
    background-color: #f8fafc;
  }
  
  .payment-item.paid .payment-month {
    color: #1e3c72;
  }
  
  .no-payments {
    text-align: center;
    padding: 40px 20px;
    color: #666;
    font-size: 16px;
  }

  /* Responsive Styles */
  @media screen and (max-width: 992px) {
    .payment-checklist-container {
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
    
    .payment-checklist-container {
      width: 85%;
      border-bottom-left-radius: 30px;
    }
    
    .checklist-header h2 {
      font-size: 15px;
    }

    .year-header {
      margin-left: 15px;
    }
    
    .payment-item {
      padding: 0 15px;
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
    
    .payment-checklist-container {
      width: 90%;
    }
    
    .checklist-header h2 {
      font-size: 13px;
    }
    
    .year-header {
      font-size: 12px;
      margin-left: 10px;
    }
    
    .payment-due {
      font-size: 9px;
    }
    
    .payment-month {
      font-size: 12px;
    }
    
    .payment-amount {
      font-size: 12px;
    }
  }
</style>

<div class="mainBody">
  <div class="mainBodyContiner">
    <div class="pageTitle">
      <h1>Transactions</h1>
    </div>
    <div class="transactionchoices">
      <a href="TRANSACTIONSPAGE.php">Rent Payments</a>
      <a href="TRANSACTIONHISTORYPAGE.php" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: #fff;">Transaction History</a>
    </div>
    
    <div class="transactionformContainer">
      <div class="payment-checklist-container">
        <div class="checklist-header">
          <h2>Monthly Transaction Checklist</h2>
          <p>Every 24th day of the month</p>
        </div>
        
        <?php if (empty($payments_by_year)): ?>
          <div class="no-payments">
            <p>No payment schedule found.</p>
          </div>
        <?php else: ?>
          <?php foreach ($payments_by_year as $year => $payments): ?>
            <div class="year-section">
              <div class="year-header"><?php echo $year; ?></div>
              
              <?php foreach ($payments as $payment): ?>
                <?php 
                  $isPaid = $payment['pay_status'] == 1;
                  $dueDate = new DateTime($payment['due_date']);
                  $formattedDate = $dueDate->format('M j, Y');
                  $formattedRate = number_format($payment['monthly_rate'], 2); 
                ?>
                <div class="payment-item <?php echo $isPaid ? 'paid' : ''; ?>">
                  <input type="checkbox" class="payment-checkbox" <?php echo $isPaid ? 'checked' : ''; ?> disabled>
                  <div class="payment-info">
                    <div>
                      <div class="payment-month"><?php echo $payment['month']; ?></div>
                      <div class="payment-due">Due Date <?php echo $formattedDate; ?></div>
                    </div>
                    <div class="payment-amount">₱ <?php echo $formattedRate; ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php
// Include footer (which includes the chat component)
include 'footer.php';
?>