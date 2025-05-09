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
    .backbtn a {
      color: #FFFFFF;
      text-decoration: none;
      font-size: 30px;
      position: relative;
      left: 60px;
      top: 33px;
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
        display: flex;
        justify-content: center;
        border-bottom-left-radius: 45px;
    }
    .transacdate p {
        font-size: 17px;
        margin-left: 25px;
        color: #2262B8;
    }
    .boxContainer {
        display: flex;
        justify-content: center;
        align-items: center;
        border: 1px solid #B7B5B5;
        margin: 0 5px;
        margin-top: 50px;
        padding: 0 22px;
        width: 75%;
    }
    .box {
        height: 200px;
        
    }
    .iconContainer {
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .maillogo {
        width: 60px;
        height: 40px;
    }
    .text {
        font-size: 12px;
        margin: 0;
        width: 100%;
        margin-top: 25px;
        color: #B7B5B5;
        display: flex;
        justify-content: left;
        align-items: center;
        border-top: solid 1.5px #B7B5B5;
        padding-top: 15px;
  
    }
    .notif {
        font-size: 12px;
        margin: 0;
        width: 100%;
        margin-top: 10px;
        color: #B7B5B5;
        display: flex;
        justify-content: center;
        align-items: center;

    }
    .notiftext {
        font-size: 14px;
        margin: 0;
        position: relative;
        top: 5px;
        margin-top: 5px;
        color: #2262B8;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .amountpaid {
        font-size: 14px;
        position: relative;
        top: 5px;
    }

    /* Added responsive styles for mainBody section */
    @media screen and (max-width: 992px) {
      .pageTitle h1 {
        margin-left: 40px;
        font-size: 28px;
      }
      
      .transactionchoices h1 {
        font-size: 20px;
        width: 60%;
      }
      
      .transactionform {
        width: 65%;
      }
    }

    @media screen and (max-width: 768px) {
      .pageTitle {
        height: 80px;
      }
      
      .pageTitle h1 {
        margin-left: 25px;
        margin-top: 25px;
        font-size: 24px;
      }
      
      .transactionchoices {
        height: 80px;
      }
      
      .transactionchoices h1 {
        font-size: 18px;
        width: 75%;
        height: 45px;
        margin-top: 50px;
      }
      
      .transactionformContainer {
        height: auto;
        margin-bottom: 40px;
      }
      
      .transactionform {
        width: 80%;
      }
      
      .boxContainer {
        width: 90%;
        padding: 0 15px;
      }
      
      .transacdate p {
        font-size: 15px;
        margin-left: 15px;
      }
    }

    @media screen and (max-width: 480px) {
      .pageTitle {
        height: 60px;
      }
      
      .pageTitle h1 {
        margin-left: 15px;
        margin-top: 20px;
        font-size: 20px;
      }
      
      .transactionchoices {
        height: 60px;
      }
      
      .transactionchoices h1 {
        font-size: 16px;
        width: 90%;
        height: 40px;
        margin-top: 40px;
        border-top-right-radius: 30px;
      }
      
      .transactionform {
        width: 95%;
        border-bottom-left-radius: 30px;
      }
      
      .boxContainer {
        width: 100%;
        padding: 0 10px;
        margin-top: 30px;
      }
      
      .box {
        height: auto;
        padding-bottom: 15px;
      }
      
      .maillogo {
        width: 50px;
        height: 30px;
      }
      
      .notif {
        font-size: 10px;
      }
      
      .notiftext {
        font-size: 12px;
      }
      
      .text {
        font-size: 10px;
        padding-top: 10px;
        margin-top: 15px;
      }
      
      .transacdate p {
        font-size: 13px;
        margin-left: 10px;
      }
    }

    /*FOOTER*/
    .footer {
      margin-top: 140px;
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
        <a href="ACCOUNTPAGE.php">Profile</a> |
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
          <div class="backbtn">
            <a href="INBOXPAGE.php">&larr;</a>
          </div>
            <h1>Inbox</h1>
        </div>
        <div class="transactionformContainer">
            <div class="transactionform">
                     <div class="boxContainer">
                        <div class="box">
                            <div class="iconContainer">
                                <img src="emailone.png" alt="mail" class="maillogo"></div>
                            <p class="notif">Apr 24, 2025</p>
                            <p class="notiftext"><b>Rent Payment Reminder</b></p>
                            <p class="text">This is a friendly reminder that your rent for April 2025 is now due. 
                                Please settle the amount of ₱5,000 on or before April 30, 2025, to avoid any 
                                late fees. Thank you for your prompt attention!</p>
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