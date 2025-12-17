<?php
require_once __DIR__ . '/../db_connect.php';

// Query to get available units - LIMIT to 6 for homepage
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
LIMIT 6";

$result = mysqli_query($conn, $sql);

// Check if query executed successfully
if (!$result) {
    error_log("Query failed in HOMEPAGE.php: " . mysqli_error($conn));
    $error_message = "Something went wrong loading available units. Please try again later.";
}

// Count total available units for "See More" button logic
$count_sql = "SELECT COUNT(*) as total_units FROM units WHERE unit_status = 'Available'";
$count_result = mysqli_query($conn, $count_sql);
$total_units = 0;
if ($count_result) {
    $count_row = mysqli_fetch_assoc($count_result);
    $total_units = $count_row['total_units'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>RYC Dormitelle - Homepage</title>
  <link rel="icon" type="image/png" href="../otherIcons/pageicon.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html {
      scroll-behavior: smooth; /* enables smooth scrolling */
    }

    body {
      margin: 0;
      background-color: #fff;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      line-height: 1.6;
      color: #333;
    }

    /* Header - Modern Design */
    .header {
      background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
      color: white;
      padding: 1rem 0;
      position: fixed;
      width: 100%;
      height: 92px;
      top: 0;
      z-index: 1000;
      box-shadow: 0 2px 20px rgba(0,0,0,0.1);
      display: flex;
      align-items: center;
    }

    .hanburgerandaccContainer {
      background-color: transparent;
      width: 22%;
      height: 100%;
      display: none;
      justify-content: space-between;
      align-items: center;
      padding: 0 1rem;
    }

    .containerSystemName {
      display: flex;
      align-items: center;
      height: 100%;
      width: 25%;
      background-color: transparent;
    }

    .systemName {
      width: 100%;
      display: flex;
      align-items: center;
      gap: 1rem;
      padding-left: 2rem;
    }

    .logo-icon {
      width: 85px;
      height: 60px;
      background: #ffffff;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 1.2rem;
    }

    .logo-icon img {
      display: flex;
      align-items: center;
      width: 90%;
      height: 100%;
    }

    .logo-text {
      margin-top: 5px;
    }
    .logo-text h2 {
      font-size: 1.5rem;
      margin-bottom: -5px;
    }

    .navbar {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 2rem;
    }

    .navbarContent {
      display: flex;
      list-style: none;
      align-items: center;
      width: 100%;
      justify-content: center;
      gap: 2rem;
    }

    .navbarContent a {
      text-decoration: none;
      color: white;
      font-size: 16px;
      font-weight: 500;
      transition: color 0.3s;
    }

    .navbarContent a:hover {
      color: #79B1FC;
    }

    .user-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .user-actions a:hover {
      color: #ffffff;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s;
    }

    .btn-login {
      background: rgba(121, 177, 252, 0.3);
      border: 2px solid #79B1FC;
      color: white;
      padding: 0.5rem 1.5rem;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
      font-size: 16px;
    }

    .btn-login:hover {
        background: #79B1FC;
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
        height: 70px;
      }

      .systemName {
        justify-content: center;
        padding-left: 0;
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
        gap: 0.5rem;
      }

      .navbarContent a {
        margin: 8px 0;
        font-size: 18px;
        color: white;
      }

      .auth-section {
        position: absolute;
        right: 15px;
        top: 20px;
        color: white;
        font-size: 16px;
        display: flex;
        width: 120px;
        align-items: center;
      }

      .auth-section a {
        color: white;
        text-decoration: none;
        margin-left: 5px;
        margin-right: 5px;
      }

      .hamburger {
        display: block;
        font-size: 35px;
      }

      .user-actions {
        display: none;
      }
    }

    @media screen and (max-width: 480px) {
      .logo-text h2 {
        font-size: 14px;
      }

      .logo-text h4 {
        font-size: 9px;
      }

      .navbarContent a {
        font-size: 16px;
      }
      .auth-section a {
        font-size: 14px;
      }
    }

    /* Hero Section - Modern Design */
    .mainBody {
      height: 100vh;
      background: linear-gradient(135deg, rgba(30, 60, 114, 0.8), rgba(42, 82, 152, 0.8)), url("../staticImages/homepagebg.png");
      background-size: cover;
      background-repeat: no-repeat;
      background-position: center;
      margin-top: 0;
      position: relative;
      top: 80px;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: white;
    }

    .mainBodyName {
      max-width: 800px;
      padding: 0 2rem;
      animation: fadeInUp 1s ease-out;
    }

    .mainBody h1 {
      font-size: 3.5rem;
      margin-bottom: 1rem;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
      font-weight: 700;
    }

    .mainBody h2 {
      font-size: 1.3rem;
      margin-bottom: 2rem;
      opacity: 0.95;
      font-weight: 400;
    }

    .mainBody h3 {
      font-size: 1rem;
      margin-bottom: 2rem;
      opacity: 0.9;
      font-weight: 300;
    }

    .cta-buttons {
      display: flex;
      gap: 1rem;
      justify-content: center;
      flex-wrap: wrap;
      margin-top: 2rem;
    }

    .btn-primary-hero {
      background: linear-gradient(135deg, #79B1FC, #4A90E2);
      color: white;
      padding: 1rem 2rem;
      border-radius: 50px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s;
      box-shadow: 0 4px 15px rgba(121, 177, 252, 0.4);
    }

    .btn-primary-hero:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(121, 177, 252, 0.6);
      color: white;
      text-decoration: none;
    }

    .btn-secondary-hero {
      background: transparent;
      color: white;
      border: 2px solid white;
      padding: 1rem 2rem;
      border-radius: 50px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s;
    }

    .btn-secondary-hero:hover {
      background: white;
      color: #1e3c72;
      text-decoration: none;
    }

    /* Responsive styles for mainBody */
    @media screen and (max-width: 992px) {
      .mainBody h1 {
        font-size: 3rem;
      }

      .mainBody h2 {
        font-size: 1.2rem;
      }

      .mainBody h3 {
        font-size: 0.9rem;
      }
    }

    @media screen and (max-width: 768px) {
      .nav-menu {
        display: none;
      }

      .mainBody {
        height: 70vh;
        top: 0;
      }

      .mainBody h1 {
        font-size: 2.5rem;
      }

      .mainBody h2 {
        font-size: 1.1rem;
      }

      .mainBody h3 {
        font-size: 0.8rem;
      }
    }

    @media screen and (max-width: 480px) {
      .mainBody {
        height: 50vh;
        background-position: center;
        top: 0;
      }

      .mainBody h1 {
        font-size: 2rem;
      }

      .mainBody h2 {
        font-size: 1rem;
      }

      .mainBody h3 {
        font-size: 0.7rem;
      }

      .cta-buttons {
        flex-direction: column;
        align-items: center;
      }
    }

    /* Features Section - Why Choose RYC */
    .features {
      padding: 5rem 0;
      background: #f8fafc;
      position: relative;
      top: 80px;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 2rem;
    }

    .section-title {
      text-align: center;
      margin-bottom: 3rem;
    }

    .section-title h2 {
      font-size: 2.5rem;
      color: #1e3c72;
      margin-bottom: 1rem;
      font-weight: 700;
    }

    .section-title p {
      font-size: 1.1rem;
      color: #666;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 2rem;
    }

    .feature-card {
      background: white;
      padding: 2rem;
      border-radius: 15px;
      text-align: center;
      box-shadow: 0 5px 25px rgba(0,0,0,0.08);
      transition: all 0.3s;
    }

    .feature-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 35px rgba(0,0,0,0.15);
    }

    .feature-icon {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #79B1FC, #4A90E2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
      font-size: 2rem;
      color: white;
    }

    .feature-card h3 {
      color: #1e3c72;
      margin-bottom: 1rem;
      font-size: 1.3rem;
      font-weight: 600;
    }

    .feature-card p {
      color: #666;
      line-height: 1.6;
    }

    /* About Section - Modern Design */
    .aboutRYC {
      padding: 5rem 0;
      background: white;
      position: relative;
      top: 80px;
    }

    .about-content {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 4rem;
      align-items: center;
    }

    .about-text h1 {
      color: #1e3c72;
      font-size: 2.5rem;
      margin-bottom: 1.5rem;
      font-weight: 700;
    }

    .about-text p {
      margin-bottom: 1.5rem;
      color: #666;
      font-size: 1.1rem;
      line-height: 1.7;
    }

    .about-image {
      position: relative;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      height: 400px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .about-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .about-image-placeholder {
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, #e2e8f0, #cbd5e0);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #64748b;
      font-size: 1.2rem;
    }

    @media screen and (max-width: 768px) {
      .about-content {
        grid-template-columns: 1fr;
        text-align: center;
      }
    }

    /* Available Units Section - Modern Design */
    .availUnitsSection {
      padding: 5rem 0;
      background: #f8fafc;
      position: relative;
      top: 80px;
    }

    .availUnitsContent h1 {
      font-size: 2.5rem;
      color: #1e3c72;
      margin-bottom: 1rem;
      font-weight: 700;
      text-align: center;
    }

    .availUnitsContent p {
      font-size: 1.1rem;
      color: #666;
      text-align: center;
      margin-bottom: 3rem;
    }

    .availUnits {
      max-width: 100%;
      margin: 0 auto;
      padding: 0 2rem;
    }

    .availUnitsContainer {
      display: grid;
      gap: 1rem;
      grid-template-columns: repeat(3, 1fr);
    }

    .availUnitsBox {
      background: white;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 5px 25px rgba(0,0,0,0.08);
      transition: all 0.3s;
    }

    .availUnitsBox:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 35px rgba(0,0,0,0.15);
    }

    .unit-image-container {
      position: relative;
      height: 230px;
      overflow: hidden;
    }

    .availUnitsBox img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .unit_no {
      position: absolute;
      top: 1rem;
      right: 1rem;
      background: #1e3c72;
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-weight: 600;
      font-size: 0.9rem;
    }

    .unitInfo {
      padding: 1.5rem;
      line-height: 22px;
    }

    .unitT_type, .unit_type {
      color: #1e3c72;
      font-size: 1.3rem;
      font-weight: 600;
      margin: 0 0 0.5rem 0;
    }

    .unit-details {
      margin: 1rem 0;
    }

    .unit-details span {
      display: inline-block;
      background: #f1f5f9;
      color: #475569;
      padding: 0.3rem 0.8rem;
      border-radius: 15px;
      font-size: 0.9rem;
      margin: 0.2rem 0.3rem 0.2rem 0;
    }

    .occupant_capacity, .unit_address {
      font-size: 0.9rem;
      color: #666;
      margin: 0.5rem 0;
    }

    .monthly_rent_amount {
      font-size: 1.5rem;
      font-weight: bold;
      color: #1e3c72;
      margin: 1rem 0;
    }

    .inquireButton {
      background: linear-gradient(135deg, #79B1FC, #4A90E2);
      color: white;
      padding: 0.8rem 2rem;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s;
      display: inline-block;
    }

    .inquireButton:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(121, 177, 252, 0.4);
      color: white;
      text-decoration: none;
    }

    /* See More Button Styles */
    .see-more-container {
      text-align: center;
      margin-top: 3rem;
    }

    .btn-see-more {
      background: linear-gradient(135deg, #1e3c72, #2a5298);
      color: white;
      padding: 1rem 3rem;
      border-radius: 50px;
      text-decoration: none;
      font-weight: 600;
      font-size: 1.1rem;
      transition: all 0.3s;
      display: inline-block;
      box-shadow: 0 4px 15px rgba(30, 60, 114, 0.3);
    }

    .btn-see-more:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(30, 60, 114, 0.4);
      color: white;
      text-decoration: none;
    }

    @media screen and (max-width: 992px) {
      .availUnitsContainer {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media screen and (max-width: 768px) {
      .availUnitsContainer {
        grid-template-columns: 1fr;
      }

      .systemName {
        background: #01214B;
      }
    }

    /* Footer - Modern Design */
    .footer {
      background: #1e3c72;
      color: white;
      padding: 3rem 0 1rem;
      position: relative;
      top: 80px;
    }

    .footer-content {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 2rem;
      margin-bottom: 2rem;
      max-width: 1200px;
      margin-left: auto;
      margin-right: auto;
      padding: 0 2rem;
    }

    .footer-section h3 {
      margin-bottom: 1rem;
      color: #79B1FC;
      font-size: 1.17em;
      font-weight: bold;
    }

    .footer-section p,
    .footer-section a {
      color: #cbd5e0;
      text-decoration: none;
      margin-bottom: 0.5rem;
      display: block;
      line-height: 1.6;
    }

    .footer-section a:hover {
      color: #79B1FC;
    }

    .footer-bottom {
      border-top: 1px solid #334155;
      padding-top: 1rem;
      text-align: center;
      color: #94a3b8;
      max-width: 1200px;
      margin: 0 auto;
      padding-left: 2rem;
      padding-right: 2rem;
    }

    .social-links {
      display: flex;
      gap: 1rem;
      margin-top: 1rem;
    }

    .social-links img {
      width: 30px;
      height: 30px;
      transition: transform 0.3s;
    }

    .social-links img:hover {
      transform: scale(1.1);
    }

    /* Animations */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Responsive adjustments */
    @media screen and (max-width: 768px) {
      .features {
        top: 0;
      }

      .aboutRYC {
        top: 0;
      }

      .availUnitsSection {
        top: 0;
      }

      .user-actions {
        display: none;
      }

      .footer {
        top: 0;
      }
    }
  </style>
<body>
  <div class="header">
    <div class="hanburgerandaccContainer">
      <button class="hamburger" onclick="toggleMenu()">☰</button>
      <div class="auth-section">
        <a href="../SIGNUP.php">Sign Up</a> |
        <a href="../LOGIN.php">Log In</a>
      </div>
    </div>
    <div class="containerSystemName" id="containerSystemName">
      <div class="systemName">
        <div class="logo-icon">
          <img src="../tenantviewIcons/roof.png" alt="ryc logo">
        </div>
        <div class="logo-text">
          <h2> <b>RYC Dormitelle</b></h2>
          <p1 style="font-size: 0.8rem; opacity: 0.9;">Apartment Management System</p1>
        </div>
      </div>
    </div>
    <div class="navbar" id="navbar">
      <div class="navbarContent">
        <a href="HOMEPAGE.php">Home</a>
        <a href="HOMEPAGE.php#aboutRYC">About</a>
        <a href="HOMEPAGE.php#availUnitsContainer">Available Units</a>
        <div class="user-actions">
          <a href="../SIGNUP.php" class="btn-login">Sign Up</a>
          <a href="../LOGIN.php" class="btn-login">Log In</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Hero Section -->
  <div class="mainBody">
    <div class="mainBodyName">
      <h1>RYC Dormitelle</h1>
      <h2>Modern apartment-style living designed for students and professionals in Daet, Camarines Norte</h2>
      <h3>Ofelia Pasig, Daet, Camarines Norte 4600</h3>
      <div class="cta-buttons">
        <a href="#availUnitsContainer" class="btn-primary-hero scroll-link">View Available Units</a>
        <a href="#aboutRYC" class="btn-secondary-hero scroll-link">Learn More</a>
      </div>
    </div>
  </div>

  <!-- Features Section -->
  <section class="features">
    <div class="container">
      <div class="section-title">
        <h2>Why Choose RYC Dormitelle?</h2>
        <p>Experience comfort, convenience, and security in our modern living spaces</p>
      </div>
      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon">🏠</div>
          <h3>Semi Furnished</h3>
          <p>Each unit comes with essential furniture and appliances for your comfort</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">🔒</div>
          <h3>24/7 Security</h3>
          <p>Round-the-clock security and CCTV monitoring for your peace of mind</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">📶</div>
          <h3>High-Speed WiFi</h3>
          <p>Reliable internet connection perfect for studying and working</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">🌍</div>
          <h3>Prime Location</h3>
          <p>Conveniently located near schools, offices, and transportation hubs</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">❄️</div>
          <h3>Air-Conditioned</h3>
          <p>Stay comfortable year-round with modern air conditioning systems</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">💰</div>
          <h3>Affordable Rates</h3>
          <p>Quality living spaces at competitive prices for students and professionals</p>
        </div>
      </div>
    </div>
  </section>

  <!-- About Section -->
  <div class="aboutRYC" id="aboutRYC">
    <div class="container">
      <div class="about-content">
        <div class="about-text">
          <h1>About RYC Dormitelle</h1>
          <p>RYC Dormitelle is a modern apartment-style residence designed for students and working professionals seeking comfort, convenience, and security.</p>
          <p>Located in Ofelia Pasig, Daet, Camarines Norte, we offer easy access to schools, offices, and transportation hubs while providing a safe and peaceful environment.</p>
          <p>Our commitment is to provide affordable yet quality living spaces that truly feel like home.</p>
          <a href="#availUnitsContainer" class="btn-primary-hero scroll-link">Explore Our Units</a>
        </div>
        <div class="about-image">
          <img src="../otherIcons/systemLogo.png" alt="RYC Dormitelle Building" style="width: 100%; height: 100%; object-fit: contain;">
        </div>
      </div>
    </div>
  </div>

  <!-- Available Units Section -->
  <div class="availUnitsSection">
    <div class="container">
      <div class="availUnitsContent">
        <h1>Available Units</h1>
        <p>Find the perfect space for your needs</p>
      </div>

      <div class="availUnits">
        <div class="availUnitsContainer" id="availUnitsContainer">
          <?php
          if (isset($error_message)) {
              echo "<p>$error_message</p>";
          } elseif (mysqli_num_rows($result) > 0) {
              // Loop through available units (max 6)
              while ($row = mysqli_fetch_assoc($result)) {
                  ?>
                  <div class="availUnitsBox">
                    <div class="unit-image-container">
                      <img src="../unitImages/<?php echo htmlspecialchars($row['unit_image']); ?>" alt="<?php echo htmlspecialchars($row['unit_type']); ?>" class="unit_image">
                      <div class="unit_no"><?php echo htmlspecialchars($row['unit_no']); ?></div>
                    </div>
                    <div class="unitInfo">
                      <h3 class="unitT_type"><?php echo htmlspecialchars($row['unit_type']); ?></h3>
                      <div class="unit-details">
                        <span>Up to <?php echo htmlspecialchars($row['occupant_capacity']); ?> persons</span>
                        <span>Studio unit</span>
                      </div>
                      <p class="unit_address"><?php echo htmlspecialchars($row['unit_address']); ?></p>
                      <div class="monthly_rent_amount">₱<?php echo number_format($row['monthly_rent_amount']); ?>/month</div>
                      <a href="../LOGIN.php" class="inquireButton">Inquire Now</a>
                    </div>
                  </div>
                  <?php
              }
          } else {
              echo "<p>No available units at the moment. Please check back later.</p>";
          }
          ?>
        </div>

        <!-- See More Button (only show if there are more than 6 units) -->
        <?php if ($total_units > 6): ?>
        <div class="see-more-container">
          <a href="../LOGIN.php" class="btn-see-more">
            See More Units (<?php echo $total_units - 6; ?> more available)
          </a>
        </div>
        <?php endif; ?>
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
            // Correct for the fixed header height
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
</body>
</html>
<?php
// Include footer
include 'footer.php';
?>
