<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not logged in
if (!isset($_SESSION['email_account'])) {
    header("Location: ../LOGIN.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo isset($page_title) ? $page_title : 'RYC Dormitelle'; ?></title>
  <link rel="icon" type="image/png" href="../otherIcons/pageicon.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <link rel="stylesheet" href="tenantlayout.css">
</head>
<body>
  <div class="header">
    <div class="hanburgerandaccContainer">
      <button class="hamburger" onclick="toggleMenu()">☰</button>
      <div class="adminSection">
        <a href="TENANTACCOUNTPAGE.php"><img src="../staticImages/userIcon.png" alt="userIcon" style="height: 25px; width: 25px; display: flex; justify-content: center;"></a> |
        <a href="../LOGOUT.php">Log Out</a>
      </div>
    </div>
    <div class="containerSystemName" id="containerSystemName">
      <div class="systemName">
        <div class="logo-icon">
          <img src="../tenantviewIcons/roof.png" alt="ryc logo">
        </div>
        <div class="logo-text">
          <h2><b>RYC Dormitelle</b></h2>
          <p1 style="font-size: 0.8rem; opacity: 0.9;">Apartment Management System</p1>
        </div>
      </div>
    </div>
    <div class="navbar" id="navbar">
      <div class="navbarContent">
        <a href="TENANTHOMEPAGE.php">Home</a>
        <a href="TENANTHOMEPAGE.php#aboutRYC">About</a>
        <a href="TENANTHOMEPAGE.php#availUnitsContainer">Available Units</a>
        <a href="TRANSACTIONSPAGE.php">Transactions</a>
        <a href="INBOXPAGE.php">Inbox</a>
        <div class="user-actions">
          <a href="TENANTACCOUNTPAGE.php" class="btn-login">Account</a>
          <a href="../LOGOUT.php" class="btn-login">Log Out</a>
        </div>
      </div>
    </div>
  </div>

  <script>
    function toggleMenu() {
      document.getElementById('containerSystemName').classList.toggle('show');
      document.getElementById('navbar').classList.toggle('show');
    }

    document.querySelectorAll('a.scroll-link').forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href'); 
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            const headerOffset = 92;
            const elementPosition = targetElement.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

            window.scrollTo({
                top: offsetPosition,
                behavior: "smooth"
            });
        }
      });
    });
  </script>