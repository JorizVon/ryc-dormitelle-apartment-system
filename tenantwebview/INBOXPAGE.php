<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../db_connect.php'; // adjust path if needed

// Check if user is logged in
if (!isset($_SESSION['email_account'])) {
    // Redirect to login page
    header("Location: LOGIN.php");
    exit();
}

$email = $_SESSION['email_account'];

// Get the tenant_ID from the email
$tenant_query = $conn->prepare("SELECT tenant_ID FROM tenants WHERE email = ?");
if (!$tenant_query) {
    // Handle the SQL preparation error
    $error_message = "System error. Please try again later.";
} else {
    $tenant_query->bind_param("s", $email);
    $tenant_query->execute();
    $tenant_result = $tenant_query->get_result();

    if ($tenant_result->num_rows == 0) {
        $error_message = "User account not found.";
    } else {
        $tenant_row = $tenant_result->fetch_assoc();
        $tenant_ID = $tenant_row['tenant_ID'];
        
        // Get notifications for this tenant
        $notif_query = $conn->prepare("SELECT tenant_ID, notif_date_time, notif_title, notif_description FROM notification_inbox 
                                      WHERE tenant_ID = ? ORDER BY notif_date_time DESC");
        
        if (!$notif_query) {
            $error_message = "Could not retrieve notifications.";
        } else {
            $notif_query->bind_param("i", $tenant_ID);
            $notif_query->execute();
            $notifications = $notif_query->get_result();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Inbox Page</title>
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
      z-index: 100;
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
      top: 80px;
      margin-bottom: 100px;
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
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 10px;
    }
    
.transactionform {
        width: 48%;
        max-height: 500px;
        border-bottom-left-radius: 45px;
        overflow-y: auto;
        scrollbar-width: none; /* Firefox */
        padding-bottom: 10px; /* Add padding to avoid border cutoff */
    }
    
    .transactionform::-webkit-scrollbar {
        display: none; /* Chrome, Safari, Opera */
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
        margin: 0 5px 10px 5px;
        padding: 10px 22px;
    }
    
    .todaystransactbox .boxContainer:last-child {
        border-bottom-left-radius: 43px; /* Apply radius to the last notification box */
        margin-bottom: 0; /* Remove bottom margin from last box */
    }
    
    .box {
        width: 100%;
        min-height: 60px;
    }
    
    .box a {
      text-decoration: none;
    }
    
    .notif {
        font-size: 14px;
        margin: 0;
        position: relative;
        margin-top: 5px;
        color: #2262B8;
        font-weight: bold;
    }
    
    .notiftext {
        font-size: 12px;
        margin: 0;
        margin-top: 5px;
        color: #B7B5B5;
    }
    
    .notifdate {
        font-size: 11px;
        color: #919191;
        margin-top: 8px;
        text-align: right;
    }
    
    .notifcontent {
        max-height: 80px;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
    }
    
    .noNotifications {
        text-align: center;
        padding: 40px 20px;
        color: #919191;
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
      .mainBody {
        top: 0;
      }
      
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
        padding: 8px 15px;
        margin: 0 5px 10px;
      }
      
      .box {
        min-height: 50px;
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
        padding: 6px 10px;
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
      margin-top: 32vh;
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
    
    /* Modal styles */
    .notification-modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.4);
    }
    
    .modal-content {
      background-color: #fefefe;
      margin: 15% auto;
      padding: 20px;
      border: 1px solid #888;
      width: 70%;
      max-width: 600px;
      border-radius: 10px;
    }
    
    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }
    
    .close:hover,
    .close:focus {
      color: black;
      text-decoration: none;
    }
    
    .modal-title {
      color: #2262B8;
      margin-top: 10px;
    }
    
    .modal-date {
      color: #919191;
      font-size: 14px;
      margin-bottom: 20px;
    }
    
    .modal-body {
      color: #333;
      line-height: 1.5;
    }
  </style>
</head>
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
          <a href="LOGIN.php">Log Out</a>
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
            <h1>Notifications</h1>
        </div>
        <div class="transactionformContainer">
            <div class="transactionform">
                <div class="todaystransactbox">
                    <div class="transacdate">
                        <p><b>Your Notifications</b></p>
                    </div>
                    
                    <?php
                    if (isset($error_message)) {
                        echo '<div class="noNotifications">' . $error_message . '</div>';
                    } elseif (isset($notifications) && $notifications->num_rows > 0) {
                        while ($notif = $notifications->fetch_assoc()) {
                            $date = new DateTime($notif['notif_date_time']);
                            $formatted_date = $date->format('F j, Y - g:i A');
                            
                            echo '<div class="boxContainer" data-id="' . $notif['tenant_ID'] . '" onclick="showNotification(this)">
                                    <div class="box">
                                        <p class="notif">' . htmlspecialchars($notif['notif_title']) . '</p>
                                        <div class="notifcontent">
                                            <p class="notiftext">' . substr(strip_tags($notif['notif_description']), 0, 150) . (strlen($notif['notif_description']) > 150 ? '...' : '') . '</p>
                                        </div>
                                        <p class="notifdate">' . $formatted_date . '</p>
                                        
                                        <div class="hidden-content" style="display:none;">
                                            <div class="full-title">' . htmlspecialchars($notif['notif_title']) . '</div>
                                            <div class="full-date">' . $formatted_date . '</div>
                                            <div class="full-description">' . $notif['notif_description'] . '</div>
                                        </div>
                                    </div>
                                </div>';
                        }
                    } else {
                        echo '<div class="noNotifications">No notifications found</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
  </div>

  <!-- Notification Modal -->
  <div id="notificationModal" class="notification-modal">
    <div class="modal-content">
      <span class="close">&times;</span>
      <h2 id="modalTitle" class="modal-title"></h2>
      <p id="modalDate" class="modal-date"></p>
      <div id="modalBody" class="modal-body"></div>
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
    
    // Modal functions
    var modal = document.getElementById("notificationModal");
    var span = document.getElementsByClassName("close")[0];
    
    function showNotification(element) {
      var title = element.querySelector(".full-title").innerHTML;
      var date = element.querySelector(".full-date").innerHTML;
      var description = element.querySelector(".full-description").innerHTML;
      
      document.getElementById("modalTitle").innerHTML = title;
      document.getElementById("modalDate").innerHTML = date;
      document.getElementById("modalBody").innerHTML = description;
      
      modal.style.display = "block";
    }
    
    // When the user clicks on <span> (x), close the modal
    span.onclick = function() {
      modal.style.display = "none";
    }
    
    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
      if (event.target == modal) {
        modal.style.display = "none";
      }
    }
  </script>
</body>
</html>