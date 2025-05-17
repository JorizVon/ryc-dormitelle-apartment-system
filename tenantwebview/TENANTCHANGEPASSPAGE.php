<?php
session_start();
require_once '../db_connect.php';

$email_account = 'none';
$username = 'none';
$message = '';  // To hold success or error messages

if (isset($_SESSION['email_account'])) {
    $email_account = $_SESSION['email_account'];

    $stmt = $conn->prepare("SELECT username, password FROM accounts WHERE email_account = ?");
    $stmt->bind_param("s", $email_account);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $username = $row['username'];
        $hashed_password = $row['password'];
    }
    $stmt->close();
} 

// Handle Change Password form submission
if (isset($_POST['change_password_submit'])) {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    // Verify old password
    if (!password_verify($old_password, $hashed_password)) {
        $message = "Old password is incorrect.";
    } elseif ($new_password !== $confirm_new_password) {
        $message = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $message = "New password must be at least 6 characters.";
    } else {
        // Hash the new password and update DB
        $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $update_stmt = $conn->prepare("UPDATE accounts SET password = ? WHERE email_account = ?");
        $update_stmt->bind_param("ss", $new_hashed_password, $email_account);

        if ($update_stmt->execute()) {
            $message = "Password changed successfully!";
        } else {
            $message = "Error updating password. Please try again.";
        }
        $update_stmt->close();
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

    /* Main Body Styles */
    .mainBody {
      position: relative;
      top: 75px;
      width: 100%;
    }

    .mainBodyContiner {
      width: 100%;
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

    .accInfo {
      display: inline-block;
      margin-left: 20px;
    }

    .accInfo h5 {
      font-size: 16px;
      margin: 0;
    }

    .accInfo p {
      font-size: 14px;
      margin: 2px 0;
    }

    .transactionformContainer {
      width: 100%;
      height: auto;
      min-height: 400px;
      display: flex;
      justify-content: center;
      align-items: center;
      margin-top: 30px;
    }

    .transactionform {
      width: 100%;
      height: 100%;
    }

    .instruct p {
      font-size: 11px;
      color: #2262B8;
      width: 100%;
      display: flex;
      justify-content: center;
      margin-bottom: 50px;
      text-align: center;
      padding: 0 20px;
      box-sizing: border-box;
    }

    .changepassForm {
      width: 48%;
      height: auto;
      margin: 0 auto;
    }

    .changepassForm input {
      padding: 12px; 
      font-size: 16px;
      border-radius: 30px;
      width: 300px;
      max-width: 100%;
      margin: 0 auto;
      border: 1px solid #ccc;
      box-sizing: border-box;
      display: block;
    }

    .changepassForm button {
      padding: 12px;
      background-color: #2262B8;
      color: white;
      font-size: 16px;
      border: none;
      border-radius: 25px;
      cursor: pointer;
      width: 150px;
      margin: 0 auto;
      margin-top: 50px;
      display: block;
    }

    /* Responsive styles for mainBody */
    @media screen and (max-width: 992px) {
      .pageTitle h1 {
        margin-left: 40px;
        font-size: 30px;
      }

      .profileHeader {
        width: 60%;
      }

      .changepassForm {
        width: 60%;
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
      }

      .profileHeader {
        width: 80%;
        height: 90px;
        margin-top: 30px;
      }

      .accInfo h5 {
        font-size: 15px;
      }

      .accInfo p {
        font-size: 13px;
      }

      .transactionformContainer {
        margin-top: 20px;
      }

      .instruct p {
        font-size: 10px;
        margin-bottom: 30px;
      }

      .changepassForm {
        width: 70%;
      }

      .changepassForm input {
        width: 100%;
        padding: 10px;
        font-size: 14px;
      }

      .changepassForm button {
        width: 130px;
        padding: 10px;
        font-size: 14px;
        margin-top: 30px;
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

      .profileHeader {
        width: 90%;
        height: 80px;
        margin-top: 20px;
      }

      .accInfo {
        margin-left: 15px;
      }

      .accInfo h5 {
        font-size: 14px;
      }

      .accInfo p {
        font-size: 12px;
      }

      .transactionformContainer {
        margin-top: 15px;
      }

      .instruct p {
        font-size: 9px;
        margin-bottom: 20px;
      }

      .changepassForm {
        width: 90%;
      }

      .changepassForm input {
        padding: 8px;
        font-size: 13px;
      }

      .changepassForm button {
        width: 120px;
        padding: 8px;
        font-size: 13px;
        margin-top: 25px;
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
          <a href="TENANTACCOUNTPAGE.php"><img src="../staticImages/userIcon.png" alt="userIcon" style="height: 45px; width: 45px; display: flex; justify-content: center;"></a>
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
        <div class="transactionchoices">
           <div class="profileHeader">
            <div class="accInfo">
                <h5 class="email_account">Change Password</h5>
                <h5 class="username">Email Account: <?php echo $email_account; ?></h5>
            </div>
           </div>
        </div>
        <div class="transactionformContainer">
            <div class="transactionform">
                <div class="instruct">
                    <p><b>Your password must be at least 6 characters and should include a combination of numbers, letters and special characters (!$@%).</b></p>
                 </div>
                 <?php if ($message): ?>
                    <p style="display:flex; justify-content: center; height:auto; color: <?php echo (strpos($message, 'successfully') !== false) ? 'green' : 'red'; ?>">
                      <?php echo htmlspecialchars($message); ?>
                    </p>
                  <?php endif; ?>
                 <form action="" class="changepassForm" method="post">
                  <div style="display: flex; flex-direction: column; gap: 15px;">
                    <input type="password" name="old_password" placeholder="Old Password" required>
                    <input type="password" name="new_password" placeholder="New Password" required>
                    <input type="password" name="confirm_new_password" placeholder="Confirm New Password" required>
                    <button type="submit" name="change_password_submit">
                      Submit
                    </button>
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