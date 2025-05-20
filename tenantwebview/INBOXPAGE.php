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
    .transactionchoices {
        width: 100%;
        height: 100px;
        align-items: center;
        display: flex;
        justify-content: center;
    }
    .transactionchoices h1 {
        text-decoration: none;
        font-size: 22px;
        margin: 0 10px;
        margin-top: 70px;
        color: #FFFF;
        background-color: #2262B8;
        display: flex;
        align-items: center;
        justify-content: center;
        border-top-right-radius: 45px;
        width: 47%;
        height: 50px;
    }
    .transactionformContainer {
        width: 100%;
        height: 300px;
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 10px;
    }
    .transactionform {
        width: 48%;
        height: 100%;
        border-bottom-left-radius: 45px;
    }
    .transacdate p {
        font-size: 17px;
        margin-left: 25px;
        color: #2262B8;
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
    
    /* RESPONSIVE STYLES FOR MAINBODY */
    @media screen and (max-width: 992px) {
      .pageTitle {
        height: 80px;
      }
      
      .pageTitle h1 {
        margin-left: 40px;
        margin-top: 30px;
        font-size: 28px;
      }
      
      .transactionchoices {
        height: 80px;
      }
      
      .transactionchoices h1 {
        font-size: 20px;
        margin-top: 50px;
        width: 60%;
      }
      
      .transactionform {
        width: 60%;
      }
    }
    
    @media screen and (max-width: 768px) {
      .pageTitle {
        height: 70px;
      }
      
      .pageTitle h1 {
        margin-left: 30px;
        margin-top: 25px;
        font-size: 24px;
      }
      
      .transactionchoices {
        height: 70px;
      }
      
      .transactionchoices h1 {
        font-size: 18px;
        margin-top: 40px;
        width: 75%;
        height: 45px;
      }
      
      .transactionformContainer {
        height: auto;
      }
      
      .transactionform {
        width: 75%;
        height: 100%;
      }
      
      .transacdate p {
        font-size: 16px;
        margin-left: 20px;
      }
      
      .boxContainer {
        padding: 0 15px;
        margin: 0 5px 10px;
      }
      
      .box {
        height: auto;
        min-height: 60px;
        padding: 5px 0;
      }
    }
    
    @media screen and (max-width: 480px) {
      .pageTitle {
        height: 60px;
      }
      
      .pageTitle h1 {
        margin-left: 20px;
        margin-top: 20px;
        font-size: 22px;
      }
      
      .transactionchoices {
        height: 60px;
      }
      
      .transactionchoices h1 {
        font-size: 16px;
        margin-top: 30px;
        width: 85%;
        height: 40px;
        border-top-right-radius: 30px;
      }
      
      .transactionform {
        width: 85%;
        border-bottom-left-radius: 30px;
      }
      
      .transacdate p {
        font-size: 15px;
        margin-left: 15px;
      }
      
      .boxContainer {
        padding: 0 10px;
        margin: 0 5px 8px;
      }
      
      .notif {
        font-size: 13px;
      }
      
      .notiftext {
        font-size: 11px;
      }
    }

    /*FOOTER*/
    .footer {
      margin-top: 85px;
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
          <a href="TENANTACCOUNTPAGE.php"><img src="../staticImages/userIcon.png" alt="userIcon" style="height: 45px; width: 45px; display: flex; justify-content: center;"></a>
          <p style="font-size: 20px; color: white; margin: 0 5px;">|</p>
          <a href="LOGIN.php">Login</a>
        </div>
      </div>
    </div>
  </div>

  <div class="mainBody">
    <div class="mainBodyContiner">
        <div class="pageTitle">
            <h1>Inbox</h1>
        </div>
        <div class="transactionchoices">
            <h1>Inbox</h1>
        </div>
        <div class="transactionformContainer">
            <div class="transactionform">
                <div class="todaystransactbox">
                    <div class="transacdate">
                        <p><b>Latest</b></p>
                     </div>
                     <div class="boxContainer">
                        <div class="box">
                            <a href="INBOXTEXTPAGE.php"><p class="notif"><b>Rent Payment Reminder</b></p></a>
                            <p class="notiftext">This is a friendly reminder that your rent for April 2025 is now due.</p>
                         </div>
                     </div>
                     <div class="boxContainer">
                        <div class="box">
                            <a href="DEPOSITNOTIF.php"><p class="notif"><b>Deposit Adjustment Notification</b></p></a>
                            <p class="notiftext">You have used ₱3,000 from your security deposit to cover part of this month's rent.</p>
                         </div>
                     </div>
                     <div class="boxContainer">
                        <div class="box">
                            <a href="BALANCENOTIF.php"><p class="notif"><b>Balance Payment Confirmation</b></p></a>
                            <p class="notiftext">We've received your rent balance payment of ₱3,000.</p>
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