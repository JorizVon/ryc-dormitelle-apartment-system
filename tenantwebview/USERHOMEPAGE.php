<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['email_account'])) {
    header("Location: ../login.php");
    exit();
}

// Connect to the database
require_once '../db_connect.php';

// Query to get available units
$sql = "SELECT 
    u.unit_no, 
    u.unit_address, 
    ui.unit_image, 
    u.occupant_capacity, 
    u.monthly_rent_amount,
    u.unit_type
FROM 
    units u
INNER JOIN (
    SELECT unit_no, MIN(unit_image) AS unit_image
    FROM unit_images
    GROUP BY unit_no
) ui ON u.unit_no = ui.unit_no
WHERE 
    u.unit_status = 'Available'
LIMIT 0, 25";

$result = mysqli_query($conn, $sql);

// Check if query executed successfully
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Homepage</title>
  <style>
    html {
      scroll-behavior: smooth; /* enables smooth scrolling */
    }
    body {
      margin: 0;
      background-color: #fff;
      padding: 0;
      font-family: Arial, sans-serif;
    }

    .header {
      display: flex;
      position: fixed;
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
      margin-left: 500px;
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
      background-image: url("../staticImages/userhomepagebg.png");
      background-color: #cccccc;
      width: 100%;
      height: 89vh;
      background-size: cover; 
      background-repeat: no-repeat;
      background-position: top;
      margin-top: 0;
      position: relative;
      top: 80px;
    }

    .mainBodyName {
      position: relative;
      top: 150px;
    }

    .mainBody h1 {
      margin-top: 0;
      margin-left: 120px;
      margin-bottom: 0;
      font-size: 60px;
    }

    .mainBody h2 {
      margin-top: 2px;
      margin-left: 120px;
      margin-bottom: 0;
      font-size: 40px;
    }

    .mainBody h3 {
      margin-top: 2px;
      margin-left: 120px;
      margin-bottom: 0;
      font-size: 20px;
    }

    /* Responsive styles for mainBody */
    @media screen and (max-width: 992px) {
      .mainBodyName {
        top: 120px;
      }
      
      .mainBody h1 {
        font-size: 50px;
        margin-left: 80px;
      }
      
      .mainBody h2 {
        font-size: 32px;
        margin-left: 80px;
      }
      
      .mainBody h3 {
        font-size: 18px;
        margin-left: 80px;
      }
    }

    @media screen and (max-width: 768px) {
      .mainBody {
        height: 70vh;
      }
      
      .mainBodyName {
        top: 80px;
      }
      
      .mainBody h1 {
        font-size: 40px;
        margin-left: 40px;
      }
      
      .mainBody h2 {
        font-size: 24px;
        margin-left: 40px;
      }
      
      .mainBody h3 {
        font-size: 16px;
        margin-left: 40px;
      }
    }

    @media screen and (max-width: 480px) {
      .mainBody {
        height: 50vh;
        background-position: center;
      }
      
      .mainBodyName {
        top: 50px;
      }
      
      .mainBody h1 {
        font-size: 28px;
        margin-left: 20px;
      }
      
      .mainBody h2 {
        font-size: 18px;
        margin-left: 20px;
      }
      
      .mainBody h3 {
        font-size: 14px;
        margin-left: 20px;
      }
    }

    .aboutRYC {
      position: relative;
      top: 50px;
      width: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      margin-top: 70px;
    }

    .aboutRYCcontent img {
      margin: 0 auto;
      text-align: center;
      height: 25vh;
    }

    .aboutRYCcontent h1 {
      text-align: center;
      font-size: 50px;
    }

    .aboutRYCcontent p {
      text-align: center;
      font-size: 20px;
    }

    /* Responsive styles for aboutRYC */
    @media screen and (max-width: 992px) {
      .aboutRYCcontent img {
        height: 20vh;
        width: 100%;
      }
      
      .aboutRYCcontent h1 {
        font-size: 40px;
      }
      
      .aboutRYCcontent p {
        font-size: 18px;
        padding: 0 20px;
      }
      .mainBody {
        margin-top: 0;
      }
    }

    @media screen and (max-width: 768px) {
      .aboutRYCcontent img {
        height: 15vh;
        width: 100%;
      }
      
      .aboutRYCcontent h1 {
        font-size: 32px;
      }
      
      .aboutRYCcontent p {
        font-size: 16px;
        padding: 0 30px;
        br {
          display: none;
        }
      }
      .mainBody {
         top: 0;
      }
    }

    @media screen and (max-width: 480px) {
      .aboutRYCcontent img {
        height: 12vh;
        width: 100%;
      }
      
      .aboutRYCcontent h1 {
        font-size: 26px;
      }
      
      .aboutRYCcontent p {
        font-size: 14px;
        padding: 0 15px;
      }
      .mainBody {
        top: 0;
      }
    }

    /* Available Units Section - Fixed */
    .availUnitsContent {
      max-width: 1200px;
      margin: 80px auto 20px;
      padding: 0 20px;
    }

    .availUnitsContent h1 {
      font-size: 26px;
      color: #01214B;
      margin-bottom: 20px;
      font-weight: bold;
    }

    .availUnits {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }

    .availUnitsContainer {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
    }

    .availUnitsBox {
      position: relative;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      height: 300px;
    }

    .availUnitsBox img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .unit_no {
      position: absolute;
      top: 10px;
      right: 10px;
      background-color: #0066cc;
      color: white;
      padding: 5px 10px;
      border-radius: 4px;
      font-weight: bold;
      font-size: 14px;
    }

    .unitInfo {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      background-color: rgba(255, 255, 255, 0.9);
      padding: 8px;
    }

    .unitT_type, .unit_type {
      font-size: 16px;
      font-weight: bold;
      margin: 0 0 3px 0;
    }

    .unitDetails, .occupant_capacity, .unit_address {
      font-size: 14px;
      color: #555;
      margin: 0 0 5px 0;
    }

    .monthly_rent_amount {
      font-weight: bold;
      color: #01214B;
      margin: 0 0 5px 0;
      font-size: 16px;
    }

    .inquireButton {
      display: inline-block;
      background-color: #0066cc;
      color: white;
      padding: 5px 10px;
      text-decoration: none;
      border-radius: 4px;
      font-size: 14px;
    }

    .inquireButton:hover {
      background-color: #01214B;
    }

    @media screen and (max-width: 992px) {
      .availUnitsContainer {
        grid-template-columns: repeat(3, 1fr);
      }
    }

    @media screen and (max-width: 768px) {
      .availUnitsContainer {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media screen and (max-width: 480px) {
      .availUnitsContainer {
        grid-template-columns: 1fr;
      }
    }
    
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
        <a href="USERACCOUNTPAGE.php"><img src="../staticImages/userIcon.png" alt="userIcon" style="height: 25px; width: 25px; display: flex; justify-content: center;"></a> |
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
        <a href="#aboutRYC" class="scroll-link">About</a>
        <a href="#availUnitsContainer" class="scroll-link">Available Units</a>
        <div class="loginLogOut">
          <a href="USERACCOUNTPAGE.php"><img src="../staticImages/userIcon.png" alt="userIcon" style="height: 45px; width: 45px; display: flex; justify-content: center;"></a>
          <p style="font-size: 20px; color: white; margin: 0 5px;">|</p>
          <a href="LOGIN.php">Log Out</a>
        </div>
      </div>
    </div>
  </div>

  <div class="mainBody">
    <div class="mainBodyName">
      <h1>RYC Dormitelle</h1>
      <h2>APARTMENT MANAGEMENT SYSTEM</h2>
      <h3>Ofelia Pasig, Daet, Camarines Norte 4600</h3>
    </div>
  </div>

  <div class="aboutRYC" id="aboutRYC">
    <div class="aboutRYCcontent">
      <img src="../otherIcons/systemLogo.png" alt="systemLogo">
      <h1>About RYC Dormitelle</h1>
      <p>RYC Dormitelle is a modern apartment-style residence designed for<br>students and working professionals seeking comfort, convenience, and<br>security. Located in a prime area near schools, offices, and transportation<br>hubs, RYC Dormitelle offers easy access to everything you need. <br><br>
        Each unit is fully furnished and equipped with essential amenities such as<br>air-conditioning, high-speed Wi-Fi, study and sleeping areas, and private<br>bathrooms. The building features 24/7 security, CCTV monitoring, and a<br>clean, well-maintained environment to ensure a safe and peaceful stay. <br><br>
        At RYC Dormitelle, we are committed to providing affordable yet quality<br>living spaces that feel like home.<br><br>
      </p>
    </div>
  </div>

  <div class="availUnitsContent">
    <h1>Available Units</h1>
  </div>
  
  <div class="availUnits">
    <div class="availUnitsContainer" id="availUnitsContainer">
      <?php
      // Check if there are any available units
      if (mysqli_num_rows($result) > 0) {
          // Loop through all available units
          while ($row = mysqli_fetch_assoc($result)) {
              ?>
              <div class="availUnitsBox">
              <img src="../unitImages/<?php echo htmlspecialchars($row['unit_image']); ?>" alt="<?php echo htmlspecialchars($row['unit_type']); ?>" class="unit_image">
                <div class="unit_no"><?php echo htmlspecialchars($row['unit_no']); ?></div>
                <div class="unitInfo">
                  <p class="unitT_type"><?php echo htmlspecialchars($row['unit_type']); ?></p>
                  <p class="occupant_capacity">Studio unit accommodating up to <?php echo htmlspecialchars($row['occupant_capacity']); ?> persons</p>
                  <p class="unitDetails"><?php echo htmlspecialchars($row['unit_address']); ?></p>
                  <p class="monthly_rent_amount">₱<?php echo number_format($row['monthly_rent_amount']); ?> monthly</p>
                  <a href="inquire.php?unit_no=<?php echo htmlspecialchars($row['unit_no']); ?>" class="inquireButton">Inquire Now</a>
                </div>
              </div>
              <?php
          }
      } else {
          echo "<p>No available units at the moment. Please check back later.</p>";
      }
      ?>
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
  <script>
    document.querySelectorAll('a.scroll-link').forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        const targetClass = this.getAttribute('href'); // e.g., '.section2'
        const targetElement = document.querySelector(targetClass);
        if (targetElement) {
          targetElement.scrollIntoView({ behavior: 'smooth' });
        }
      });
    });
  </script>
</body>
</html>
<?php 
// Close database connection
mysqli_close($conn);
?>