<?php
session_start();

if (!isset($_SESSION['email_account'])) {
    header("Location: ../LOGIN.php");
    exit();
}

include '../db_connect.php';

$email = $_SESSION['email_account'];

// Get transaction history for the logged-in user
$query = "SELECT `amount_paid`, `payment_date_time`, `transaction_type` FROM `payments` 
          INNER JOIN `tenants`
          ON payments.tenant_ID = tenants.tenant_ID
          WHERE tenants.email = ?
          ORDER BY payment_date_time DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// Group transactions by date
$transactions = [];
while ($row = $result->fetch_assoc()) {
    $date = date('F j, Y', strtotime($row['payment_date_time']));
    if (!isset($transactions[$date])) {
        $transactions[$date] = [];
    }
    $transactions[$date][] = $row;
}

// Format current date for display
$current_date = date('M j, Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Transaction History</title>
  <style>
    body {
      margin: 0;
      background-color: #fff;
      padding: 0;
      font-family: Arial, sans-serif;
    }

    .header {
      display: flex;
      justify-content: space-between;
      width: 100%;
      height: 80px;
      z-index: 1;
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

    /* MAIN BODY STYLES */
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
        height: 500px;
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 30px;
    }
    .transactionform {
        width: 48%;
        height: 100%;
        border: solid 2px #79B1FC;
        border-bottom-left-radius: 45px;
    }
    .transactionTypecontainer {
        width: 100%;
        height: 50px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow:  0 4px 2px -1px rgb(0, 0, 0, 0.2);
    }
    .transactionTypecontainer p {
        font-size: 17px;
        margin-left: 25px;
    }
    .transacdate p {
        font-size: 13px;
        margin-left: 25px;
    }
    .boxContainer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 1px solid #B7B5B5;
        margin: 10px 5px;
        padding: 0 22px;
    }
    .box {
        width: 100%;
        height: 40px;
    }
    .payment_date_time {
        font-size: 10px;
        margin: 0;
        margin-top: 5px;
    }
    .transaction_type {
        font-size: 14px;
        margin: 0;
    }
    .amount_paid {
        font-size: 14px;
        position: relative;
        top: 5px;
        width: 20%;
    }

    /* MAIN BODY RESPONSIVE STYLES */
    @media screen and (max-width: 992px) {
        .transactionform {
            width: 65%;
        }
        
        .pageTitle h1 {
            margin-left: 40px;
            font-size: 30px;
        }
        
        .transactionchoices a {
            width: 300px;
            font-size: 20px;
        }
    }

    @media screen and (max-width: 768px) {
        .pageTitle {
            height: 80px;
        }
        
        .pageTitle h1 {
            margin-left: 30px;
            margin-top: 30px;
            font-size: 26px;
        }
        
        .transactionchoices {
            height: auto;
            flex-direction: column;
            align-items: center;
        }
        
        .transactionchoices a {
            width: 80%;
            margin: 10px auto;
            font-size: 18px;
            height: 45px;
        }
        
        .transactionformContainer {
            height: auto;
            margin-top: 20px;
        }
        
        .transactionform {
            width: 85%;
            margin-bottom: 30px;
        }
        
        .transactionTypecontainer p {
            font-size: 15px;
            margin-left: 15px;
        }
    }

    @media screen and (max-width: 480px) {
        .pageTitle {
            height: 70px;
        }
        
        .pageTitle h1 {
            margin-left: 20px;
            margin-top: 25px;
            font-size: 22px;
        }
        
        .transactionchoices a {
            width: 90%;
            font-size: 16px;
            height: 40px;
        }
        
        .transactionform {
            width: 95%;
            border-bottom-left-radius: 30px;
        }
        
        .transactionTypecontainer {
            height: 40px;
        }
        
        .transactionTypecontainer p {
            font-size: 13px;
            margin-left: 10px;
        }
        
        .transacdate p {
            font-size: 12px;
            margin-left: 15px;
        }
        
        .boxContainer {
            padding: 0 15px;
        }
        
        .payment_date_time {
            font-size: 9px;
        }
        
        .transaction_type {
            font-size: 12px;
        }
        
        .amount_paid {
            font-size: 12px;
        }
    }

    /*FOOTER*/
    .footer {
      margin-top: 50px;
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
</head>
<body>
  <div class="header">
    <div class="hanburgerandaccContainer">
      <button class="hamburger" onclick="toggleMenu()">☰</button>
      <div class="adminSection">
        <a href="TENANTACCOUNTPAGE.php"><img src="../staticImages/userIcon.png" alt="userIcon" style="height: 25px; width: 25px; display: flex; justify-content: center;"></a> |
        <a href="LOGIN.php">Log Out</a>
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
        <a href="USERHOMEPAGE.php">Home</a>
        <a href="USERHOMEPAGE.php#aboutRYC" class="scroll-link">About</a>
        <a href="USERHOMEPAGE.php#availUnitsContainer" class="scroll-link">Available Units</a>
        <a href="TRANSACTIONSPAGE.php">Transactions</a>
        <a href="INBOXPAGE.php">Inbox</a>
        <div class="loginLogOut">
          <a href="ACCOUNTPAGE.php"><img src="../staticImages/userIcon.png" alt="userIcon" style="height: 45px; width: 45px; display: flex; justify-content: center;"></a>
          <p style="font-size: 20px; color: white; margin: 0 5px;">|</p>
          <a href="LOGIN.php">Login</a>
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
            <a href="TRANSACTIONSPAGE.php">Rent Payments</a>
            <a href="TRANSACTIONHISTORYPAGE.php" style="background-color: #2262B8; color: #fff;">Transaction History</a>
        </div>
        <div class="transactionformContainer">
            <div class="transactionform">
                <div class="transactionTypecontainer">
                    <p id="current_date">As of <?php echo $current_date; ?></p>
                </div>
                
                <?php if (empty($transactions)): ?>
                <div class="no-transactions">
                    <p>No transaction history found.</p>
                </div>
                <?php else: ?>
                    <?php foreach ($transactions as $date => $dayTransactions): ?>
                        <div class="todaystransactbox">
                            <div class="transacdate">
                                <p>
                                    <b><?php echo ($date == date('F j, Y')) ? 'Today' : $date; ?></b>
                                </p>
                            </div>
                            
                            <?php foreach ($dayTransactions as $transaction): ?>
                                <div class="boxContainer <?php echo ($date == date('F j, Y')) ? 'today' : ''; ?>">
                                    <div class="box">
                                        <p class="payment_date_time">
                                            <?php echo date('g:i A', strtotime($transaction['payment_date_time'])); ?>
                                        </p>
                                        <p class="transaction_type">
                                            <b><?php echo htmlspecialchars($transaction['transaction_type']); ?></b>
                                        </p>
                                    </div>
                                    <p class="amount_paid">
                                        <b><?php 
                                        // Format amount with a plus or minus sign
                                        $amount = $transaction['amount_paid'];
                                        echo ($amount >= 0 ? '&#8369; ' : '') . number_format($amount, 2); 
                                        ?></b>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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

  <script>
    function toggleMenu() {
      document.getElementById('containerSystemName').classList.toggle('show');
      document.getElementById('navbar').classList.toggle('show');
    }
    
    // Format current date and time
    document.addEventListener('DOMContentLoaded', function() {
      const today = new Date();
      const options = { month: 'short', day: 'numeric', year: 'numeric' };
      document.getElementById('current_date').innerText = 'As of ' + today.toLocaleDateString('en-US', options);
    });
  </script>
</body>
</html>