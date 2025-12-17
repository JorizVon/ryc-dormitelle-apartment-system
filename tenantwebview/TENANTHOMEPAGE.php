<?php
session_start(); // Start session ONCE, at the very beginning

// Redirect to login if not logged in
if (!isset($_SESSION['email_account'])) {
    header("Location: ../LOGIN.php"); // Change path as needed
    exit();
}

// Handle reservation success message
$reservation_success_message = '';
if (isset($_SESSION['reservation_success'])) {
    $reservation_success_message = $_SESSION['reservation_success'];
    unset($_SESSION['reservation_success']);
}

// Connect to the database
require_once '../db_connect.php';

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
LIMIT 6"; // Changed from LIMIT 0, 25 to LIMIT 6

$result = mysqli_query($conn, $sql);

// Check if query executed successfully
if (!$result) {
    error_log("Query failed in TENANTHOMEPAGE.php: " . mysqli_error($conn));
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

// Set page title for header
$page_title = "Homepage - RYC Dormitelle";

// Include header
include 'tenant_header.php';
?>

<style>
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

    .features {
      top: 0;
    }
    
    .aboutRYC {
      top: 0;
    }
    
    .availUnitsSection {
      top: 0;
    }
  }
</style>

<?php if (!empty($reservation_success_message)): ?>
<div class="alert alert-success alert-dismissible fade show" style="position: fixed; top: 100px; left: 50%; transform: translateX(-50%); z-index: 1050; max-width: 500px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);" role="alert">
  <strong>Reservation Submitted Successfully!</strong><br>
  <?php echo htmlspecialchars($reservation_success_message); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<script>
  // Auto-hide the success message after 8 seconds
  setTimeout(function() {
    const alert = document.querySelector('.alert-success');
    if (alert) {
      const bsAlert = new bootstrap.Alert(alert);
      bsAlert.close();
    }
  }, 8000);
</script>
<?php endif; ?>

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
                    <a href="TENANTINQUIRYPAGE.php?unit_no=<?php echo htmlspecialchars($row['unit_no']); ?>" class="inquireButton">Inquire Now</a>
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
        <a href="ALLUNITSPAGE.php" class="btn-see-more">
          See More Units (<?php echo $total_units - 6; ?> more available)
        </a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
// Include footer
include 'footer.php';
?>