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
        justify-content: space-around;
        align-items: center;
        box-shadow:  0 4px 2px -1px rgb(0, 0, 0, 0.2);
    }
    .transactionTypecontainer input {
        font-size: 12px;
    }
    .transactionTypecontainer label {
        font-size: 17px;
        margin: 0 8px;
    }
    .form {
        margin-top: 20px;
        margin-left: 50px;
    }
    .form label {
        width: 200px;
        font-size: 16px;
        margin-right: 50px;
        display: inline-block;
        margin-bottom: 15px;
        color: #2262B8;
    }
    .form input {
        width: 360px;
        font: 16px;
    }
    .form span {
        color: #2262B8;
    }
    .submitbtnContainer {
        width: 91%;
        justify-content: right;
        align-items: center;
        display: flex;
        margin-top: 20px;
    }
    .submitbtnContainer a {
        text-decoration: none;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 200px;
        height: 40px;
        color: #fff;
        background-color: #2262B8;
    }
    .mainBody {
      position: relative;
      top: 75px;
    }
    /* Responsive Styles for mainBody */
    @media screen and (max-width: 992px) {
      .transactionformContainer {
        height: auto;
      }
      
      .transactionform {
        width: 70%;
      }
      
      .form input {
        width: 280px;
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
        height: auto;
        min-height: 500px;
      }
      
      .transactionTypecontainer {
        flex-direction: column;
        height: auto;
        padding: 10px 0;
      }
      
      .transactionTypecontainer div {
        margin: 5px 0;
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
        margin-bottom: 15px;
      }
      
      .submitbtnContainer {
        width: 100%;
        justify-content: center;
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
        border-bottom-left-radius: 30px;
      }
      
      .transactionTypecontainer label {
        font-size: 14px;
      }
      
      .form {
        margin-left: 15px;
        margin-right: 15px;
      }
      
      .form label {
        font-size: 14px;
      }
      
      .submitbtnContainer a {
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
            <a href="TRANSACTIONPAGE.php" style="background-color: #2262B8; color: #fff;">Rent Payments</a>
            <a href="TRANSACTIONHISTORYPAGE.php">Transaction History</a>
        </div>
        <div class="transactionformContainer">
            <div class="transactionform">
                <div class="transactionTypecontainer">
                    <div>
                        <input type="checkbox" name="deposit">
                        <label for="deposit">Add to deposit</label>
                    </div>
                    <div>
                        <input type="checkbox" name="payrent">
                        <label for="payrent">Pay rent</label>
                    </div>
                    <div>
                        <input type="checkbox" name="depositToPay">
                        <label for="depositToPay">Use deposit to pay</label>
                    </div>
                </div>
               <form action="" class="form">
                    <div class="transactionNo">
                        <label for="transaction_no"><b>Transaction No.</b></label>
                        <span name="transaction_no"><b>20250421A001</b></span>
                    </div>
                    <div>
                        <label for="tenant_ID">Tenant ID</label>
                        <input type="text" name="tenant_ID">
                    </div>
                    <div>
                        <label for="tenant_name">Full Name</label>
                        <input type="text" name="tenant_name">
                    </div>
                    <div>
                        <label for="lease_payment_due">Payment Due</label>
                        <input type="text" name="lease_payment_due">
                    </div>
                    <div>
                        <label for="billing_period">Billing Period</label>
                        <input type="text" name="billing_period">
                    </div>
                    <div>
                        <label for="deposit">Current Deposit</label>
                        <input type="text" name="deposit">
                    </div>
                    <div>
                        <label for="balance">Remaining Balance</label>
                        <input type="text" name="balance">
                    </div>
                    <div>
                        <label for="amount_paid">Amount</label>
                        <input type="text" name="amount_paid">
                    </div>
                    <div>
                        <label for="payment_date">Payment Date</label>
                        <input type="text" name="payment_date">
                    </div>
                    <div class="submitbtnContainer">
                        <a href="#" class="transactionchoice">Add to deposit</a>
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

  <script>
    function toggleMenu() {
      document.getElementById('containerSystemName').classList.toggle('show');
      document.getElementById('navbar').classList.toggle('show');
    }
  </script>
</body>
</html>