<?php
session_start();
require_once '../db_connect.php';

$email = isset($_SESSION['email']) ? $_SESSION['email'] : null;

// Default values
$tenant_image = "../staticImages/userIcon.png";
$tenant_name = "none";
$contact_number = "none";
$tenant_ID = "none";
$payment_due = "none";
$billing_period = "none";
$deposit = "₱ 0.00";
$balance = "₱ 0.00";
$monthly_rent_amount = "₱ 0.00";
$payment_status = "none";
$card_status = "none";
$total_rent_paid = "₱ 0.00";

// Fetch tenant details if email is present
if ($email) {
    $sql = "SELECT tenants.email, tenants.tenant_image, tenants.tenant_name, tenants.contact_number, 
                   tenants.tenant_ID, tenant_unit.payment_due, tenant_unit.billing_period, 
                   tenant_unit.deposit, tenant_unit.balance, units.monthly_rent_amount, 
                   payments.payment_status, card_registration.card_status
            FROM tenants
            INNER JOIN tenant_unit ON tenants.tenant_ID = tenant_unit.tenant_ID
            INNER JOIN payments ON tenant_unit.tenant_ID = payments.tenant_ID
            INNER JOIN units ON payments.unit_no = units.unit_no
            INNER JOIN card_registration ON units.unit_no = card_registration.unit_no
            WHERE tenants.email = '$email'
            LIMIT 1";

    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        $tenant_image = $row['tenant_image'];
        $tenant_name = $row['tenant_name'];
        $contact_number = $row['contact_number'];
        $tenant_ID = $row['tenant_ID'];
        $payment_due = $row['payment_due'];
        $billing_period = $row['billing_period'];
        $deposit = '₱ ' . number_format($row['deposit'], 2);
        $balance = '₱ ' . number_format($row['balance'], 2);
        $monthly_rent_amount = '₱ ' . number_format($row['monthly_rent_amount'], 2);
        $payment_status = $row['payment_status'];
        $card_status = $row['card_status'];

        // Fetch total rent paid
        $tid = $row['tenant_ID'];
        $sumQuery = "SELECT SUM(amount_paid) AS total FROM payments 
                     WHERE transaction_type = 'Rent Payment' 
                     AND confirmation_status = 'confirmed' 
                     AND tenant_ID = '$tid'";
        $sumResult = mysqli_query($conn, $sumQuery);
        if ($sumResult && mysqli_num_rows($sumResult) > 0) {
            $sumRow = mysqli_fetch_assoc($sumResult);
            $total_rent_paid = '₱ ' . number_format($sumRow['total'], 2);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Homepage</title>
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
      position: fixed;
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
      }

      .adminSection a {
        color: white;
        text-decoration: none;
        margin-left: 5px;
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
    .mainBody {
      position: relative;
      top: 75px;
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
    .profileContent {
        width: 100%;
        height: 100px;
        align-items: center;
        display: flex;
        justify-content: center;
    }
    .profileHeader {
        margin: 0 10px;
        margin-top: 50px;
        color: #FFFF;
        background-color: #2262B8;
        display: flex;
        align-items: center;
        justify-content: left;
        width: 47.3%;
        height: 110px;
    }
    .profile {
        height: 80px;
        width: 80px;
        margin-left: 30px;
        margin-right: 10px;
    }
    .profile img {
        height: 100%;
        width: 100%;
    }
    .accInfo {
        display: inline-block;
    }
    .accInfo h5 {
        font-size: 16px;
        margin: 0;
    }
    .accInfo h6 {
        font-size: 15px;
        margin: 0;
    }
    .accInfo p {
        font-size: 14px;
        margin: 2px 0;
    }
    .profileFormContainer {
        width: 100%;
        height: 600px;
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 30px;
    }
    .profileForm {
        width: 48%;
        height: 100%;
        border-bottom-left-radius: 45px;
    }
    .boxContainer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 1px solid #B7B5B5;
        margin: 0 5px;
        padding: 0 22px;
    }
    .box {
        width: 100%;
        height: 60px;
    }
    .box a {
      text-decoration: none;
    }
    .notif {
        font-size: 14px;
        margin: 0;
        position: relative;
        top: 5px;
        margin-top: 10px;
        color: #2262B8;
    }
    .notiftext {
        font-size: 12px;
        margin: 0;
        margin-top: 5px;
        color: #B7B5B5;
    }
    .amountpaid {
        font-size: 14px;
        position: relative;
        top: 5px;
    }

    /* RESPONSIVE STYLES FOR MAIN BODY */
    @media screen and (max-width: 992px) {
        .mainBody {
          top: 0;
        }
        .pageTitle {
            height: 80px;
        }
        .pageTitle h1 {
            margin-left: 40px;
            margin-top: 30px;
            font-size: 28px;
        }
        .profileHeader {
            width: 60%;
            height: 100px;
        }
        .profileForm {
            width: 60%;
        }
    }

    @media screen and (max-width: 768px) {
        .mainBody {
          top: 0;
        }
        .pageTitle {
            height: 70px;
            justify-content: center;
        }
        .pageTitle h1 {
            margin-left: 0;
            margin-top: 20px;
            font-size: 24px;
            text-align: center;
        }
        .profileContent {
            height: auto;
        }
        .profileHeader {
            width: 80%;
            margin-top: 30px;
            height: 90px;
        }
        .profile {
            height: 40px;
            width: 40px;
            margin-left: 20px;
        }
        .accInfo h5 {
            font-size: 14px;
        }
        .accInfo h6 {
            font-size: 13px;
        }
        .accInfo p {
            font-size: 12px;
        }
        .profileFormContainer {
            height: auto;
            margin-top: 20px;
        }
        .profileForm {
            width: 80%;
            height: auto;
        }
        .boxContainer {
            padding: 0 15px;
        }
        .box {
            height: 50px;
        }
        .notif {
            font-size: 13px;
            margin-top: 8px;
        }
        .notiftext {
            font-size: 11px;
        }
    }

    @media screen and (max-width: 480px) {
       .mainBody {
          top: 0;
        }
        .pageTitle {
            height: 60px;
        }
        .pageTitle h1 {
            font-size: 20px;
            margin-top: 15px;
        }
        .profileHeader {
            width: 90%;
            height: 80px;
            margin-top: 20px;
        }
        .profile {
            height: 35px;
            width: 35px;
            margin-left: 15px;
            margin-right: 10px;
        }
        .accInfo h5 {
            font-size: 12px;
        }
        .accInfo h6 {
            font-size: 11px;
        }
        .accInfo p {
            font-size: 10px;
        }
        .profileForm {
            width: 90%;
        }
        .boxContainer {
            padding: 0 10px;
        }
        .box {
            height: 45px;
        }
        .notif {
            font-size: 12px;
            margin-top: 7px;
        }
        .notiftext {
            font-size: 10px;
        }
    }

    /*FOOTER*/
    .footer {
      margin-top: 120px;
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
        <a href="TENANTACCOUNTPAGE.php"><img src="../staticImages/userIcon.png" alt="userIcon" style="height: 20px; width: 20px;"></a> |
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
          <a href="ACCOUNTPAGE.php">Profile</a>
          <p style="font-size: 20px; color: white; margin: 0 5px;">|</p>
          <a href="LOGIN.php">Log Out</a>
        </div>
      </div>
    </div>
  </div>

  <div class="mainBody">
    <div class="mainBodyContiner">
        <div class="pageTitle">
            <h1>Account</h1>
        </div>
        <div class="profileContent">
           <div class="profileHeader">
            <div class="profile">
                <img src="<?php echo $tenant_image; ?>" alt="profile" id="tenant_image">
            </div>
            <div class="accInfo">
                <h5 class="tenant_name"><?php echo $tenant_name; ?></h5>
                <p class="contact_number"><?php echo $contact_number; ?></p>
                <h6 class="teanant_ID">Tenant ID: <?php echo $tenant_ID; ?></h6>
            </div>
           </div>
        </div>
        <div class="profileFormContainer">
            <div class="profileForm">
                <div class="profilebox">
                    <div class="boxContainer">
                        <div class="box">
                            <p class="notif"><b>Payment Due</b></p>
                            <p class="notiftext" id="lease_payment_due"><?php echo $payment_due; ?></p>
                        </div>
                    </div>
                    <div class="boxContainer">
                        <div class="box">
                            <p class="notif"><b>Billing Period</b></p>
                            <p class="notiftext" id="billing_period"><?php echo $billing_period; ?></p>
                        </div>
                    </div>
                    <div class="boxContainer">
                        <div class="box">
                            <p class="notif"><b>Total Rent Paid</b></p>
                           <p class="notiftext" id="total_rent_paid"><?php echo $total_rent_paid; ?></p>
                        </div>
                    </div>
                    <div class="boxContainer">
                        <div class="box">
                            <p class="notif"><b>Current Deposit</b></p>
                            <p class="notiftext" id="deposit"><?php echo $deposit; ?></p>
                        </div>
                    </div>
                    <div class="boxContainer">
                        <div class="box">
                            <p class="notif"><b>Remaining Balance</b></p>
                            <p class="notiftext" id="balance"><?php echo $balance; ?></p>
                        </div>
                    </div>
                    <div class="boxContainer">
                        <div class="box">
                            <p class="notif"><b>Monthly Rent Payment</b></p>
                            <p class="notiftext" id="lease_payment_amount"><?php echo $monthly_rent_amount; ?></p>
                        </div>
                    </div>
                    <div class="boxContainer">
                        <div class="box">
                            <p class="notif"><b>Payment Status</b></p>
                            <p class="notiftext" id="payment_status"><?php echo $payment_status; ?></p>
                        </div>
                    </div>
                    <div class="boxContainer">
                        <div class="box">
                            <p class="notif"><b>Card Status</b></p>
                            <p class="notiftext" id="card_status"><?php echo $card_status; ?></p>
                        </div>
                    </div>
                    <div class="boxContainer">
                        <div class="box">
                            <a href="CHANGEPASSPAGE.php"><p class="notif"><b>Change unit code</b></p></a>
                        </div>
                    </div>
                    <div class="boxContainer">
                        <div class="box">
                            <a href="../LOGIN.php"><p class="notif"><b>Log Out</b></p></a>
                        </div>
                    </div>
                </div>

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
  </script>
</body>
</html>