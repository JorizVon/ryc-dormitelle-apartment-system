<?php
require_once __DIR__ . '/../db_connect.php';

// Query to get all available units (no limit for the available units page)
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
ORDER BY u.unit_no";

$result = mysqli_query($conn, $sql);

// Check if query executed successfully
if (!$result) {
    error_log("Query failed in Available Units page: " . mysqli_error($conn));
    $error_message = "Something went wrong loading available units. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Available Units - RYC Dormitelle</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html {
      scroll-behavior: smooth;
    }
    
    body {
      margin: 0;
      background-color: #fff;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      line-height: 1.6;
      color: #333;
    }

    /* Header - Modern Design (copied from homepage) */
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

    .logo-text h2 {
      font-size: 1.5rem;
      margin-bottom: -5px;
    }

    .logo-text p {
      font-size: 0.8rem;
      opacity: 0.9;
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
    }

    /* Main Body Styles */
    .mainBody {
      position: relative;
      top: 92px;
      width: 100%;
      min-height: calc(100vh - 92px);
      background: #f8fafc;
      padding: 2rem 0;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 2rem;
    }

    /* Page Title */
    .pageTitle {
      text-align: center;
      margin-bottom: 3rem;
    }

    .pageTitle h1 {
      font-size: 2.5rem;
      color: #1e3c72;
      font-weight: 700;
      margin-bottom: 1rem;
    }

    .pageTitle p {
      font-size: 1.1rem;
      color: #666;
    }

    /* Search Bar Styles */
    .search-container {
      display: flex;
      justify-content: center;
      margin-bottom: 2rem;
    }

    .search-wrapper {
      position: relative;
      width: 100%;
      max-width: 600px;
    }

    .search-bar {
      width: 100%;
      padding: 1rem 1.5rem 1rem 3.5rem;
      border: 2px solid #e2e8f0;
      border-radius: 25px;
      font-size: 1.1rem;
      background: white;
      box-shadow: 0 4px 15px rgba(0,0,0,0.08);
      transition: all 0.3s ease;
      outline: none;
    }

    .search-bar:focus {
      border-color: #79B1FC;
      box-shadow: 0 4px 20px rgba(121, 177, 252, 0.3);
    }

    .search-bar::placeholder {
      color: #94a3b8;
    }

    .search-icon {
      position: absolute;
      left: 1.2rem;
      top: 50%;
      transform: translateY(-50%);
      width: 20px;
      height: 20px;
      color: #94a3b8;
      pointer-events: none;
    }

    .clear-search {
      position: absolute;
      right: 1rem;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #94a3b8;
      cursor: pointer;
      font-size: 1.2rem;
      padding: 0.5rem;
      border-radius: 50%;
      transition: all 0.3s ease;
      display: none;
    }

    .clear-search:hover {
      background: #f1f5f9;
      color: #1e3c72;
    }

    .clear-search.visible {
      display: block;
    }

    /* Filter Options */
    .filter-container {
      display: flex;
      justify-content: center;
      gap: 1rem;
      margin-bottom: 2rem;
      flex-wrap: wrap;
    }

    .filter-btn {
      padding: 0.5rem 1.2rem;
      border: 2px solid #e2e8f0;
      background: white;
      color: #475569;
      border-radius: 20px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 0.9rem;
      font-weight: 500;
    }

    .filter-btn:hover {
      border-color: #79B1FC;
      color: #1e3c72;
    }

    .filter-btn.active {
      background: #79B1FC;
      border-color: #79B1FC;
      color: white;
    }

    /* Units Grid - Updated with homepage design */
    .availUnitsContainer {
      display: grid;
      gap: 2rem;
      grid-template-columns: repeat(3, 1fr);
      margin-bottom: 3rem;
      transition: all 0.3s ease;
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

    .availUnitsBox.hidden {
      display: none;
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

    .unit-badge {
      position: absolute;
      top: 10px;
      left: 10px;
      background: #007bff;
      color: white;
      padding: 4px 12px;
      border-radius: 15px;
      font-size: 12px;
      font-weight: bold;
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
      width: 100%;
      text-align: center;
      border: none;
      cursor: pointer;
    }

    .inquireButton:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(121, 177, 252, 0.4);
      color: white;
      text-decoration: none;
    }

    /* No Results Message */
    .no-results {
      text-align: center;
      padding: 3rem;
      color: #666;
      font-size: 1.1rem;
      display: none;
    }

    .no-results.show {
      display: block;
    }

    /* Error Message */
    .error-message {
      text-align: center;
      padding: 3rem;
      color: #e74c3c;
      font-size: 1.1rem;
      background: #fdf2f2;
      border-radius: 10px;
      margin: 2rem 0;
    }

    /* Responsive for units grid */
    @media screen and (max-width: 992px) {
      .availUnitsContainer {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
      }
    }

    @media screen and (max-width: 768px) {
      .mainBody {
        top: 60px;
        padding: 1.5rem 0;
      }

      .container {
        padding: 0 1rem;
      }

      .pageTitle h1 {
        font-size: 2rem;
      }

      .availUnitsContainer {
        grid-template-columns: 1fr;
        gap: 1.5rem;
      }

      .search-bar {
        font-size: 1rem;
        padding: 0.8rem 1.2rem 0.8rem 3rem;
      }

      .filter-container {
        gap: 0.5rem;
      }

      .filter-btn {
        font-size: 0.8rem;
        padding: 0.4rem 1rem;
      }
    }

    @media screen and (max-width: 480px) {
      .pageTitle h1 {
        font-size: 1.8rem;
      }

      .unitInfo {
        padding: 15px;
      }

      .unitT_type {
        font-size: 20px;
      }

      .monthly_rent_amount {
        font-size: 18px;
      }

      .search-wrapper {
        max-width: 100%;
      }
    }

    /* Footer - Modern Design (copied from homepage) */
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

    /* Responsive adjustments for footer */
    @media screen and (max-width: 768px) {
      .footer {
        top: 0;
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
        <a href="../LOGIN.php">Log Out</a>
      </div>
    </div>
    <div class="containerSystemName" id="containerSystemName">
      <div class="systemName">
        <div class="logo-icon">
          <img src="../tenantviewIcons/roof.png" alt="ryc logo">
        </div>
        <div class="logo-text">
          <h2>RYC Dormitelle</h2>
          <p>Apartment Management System</p>
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
          <a href="../LOGIN.php" class="btn-login">Log Out</a>
        </div>
      </div>
    </div>
  </div>

  <div class="mainBody">
    <div class="container">
      <div class="pageTitle">
        <h1>Available Units</h1>
        <p>Find the perfect space for your needs</p>
      </div>

      <!-- Search Bar Section -->
      <div class="search-container">
        <div class="search-wrapper">
          <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
          <input type="text" class="search-bar" id="searchInput" placeholder="Search by unit number, type, or features...">
          <button class="clear-search" id="clearSearch" onclick="clearSearch()">&times;</button>
        </div>
      </div>

      <!-- Filter Options -->
      <div class="filter-container">
        <button class="filter-btn active" onclick="filterUnits('all')">All Units</button>
        <button class="filter-btn" onclick="filterUnits('2BR')">2BR Units</button>
        <button class="filter-btn" onclick="filterUnits('studio')">Studio Units</button>
        <button class="filter-btn" onclick="filterUnits('study')">Study Units</button>
        <button class="filter-btn" onclick="filterUnits('available')">Available</button>
      </div>

      <!-- Units Container -->
      <?php if (isset($error_message)): ?>
        <div class="error-message">
          <h3>Error Loading Units</h3>
          <p><?php echo htmlspecialchars($error_message); ?></p>
        </div>
      <?php else: ?>
        <div class="availUnitsContainer" id="unitsContainer">
          <?php
          if (mysqli_num_rows($result) > 0) {
              // Loop through available units
              while ($row = mysqli_fetch_assoc($result)) {
                  ?>
                  <div class="availUnitsBox" data-unit="<?php echo htmlspecialchars($row['unit_no']); ?>" data-type="<?php echo htmlspecialchars($row['unit_type']); ?>" data-features="studio study">
                    <div class="unit-image-container">
                      <img src="../unitImages/<?php echo htmlspecialchars($row['unit_image']); ?>" alt="<?php echo htmlspecialchars($row['unit_type']); ?>">
                      <div class="unit_no"><?php echo htmlspecialchars($row['unit_no']); ?></div>
                      <div class="unit-badge">AVAILABLE</div>
                    </div>
                    <div class="unitInfo">
                      <h3 class="unitT_type"><?php echo htmlspecialchars($row['unit_type']); ?></h3>
                      <div class="unit-details">
                        <span>Up to <?php echo htmlspecialchars($row['occupant_capacity']); ?> persons</span>
                        <span>Studio unit</span>
                        <span>Study unit</span>
                      </div>
                      <p class="unit_address"><?php echo htmlspecialchars($row['unit_address']); ?></p>
                      <div class="monthly_rent_amount">₱<?php echo number_format($row['monthly_rent_amount']); ?>/month</div>
                      <button class="inquireButton">Inquire Now</button>
                    </div>
                  </div>
                  <?php
              }
          }
          ?>
        </div>

        <!-- No Results Message -->
        <div class="no-results" id="noResults">
          <h3>No units found</h3>
          <p>Try adjusting your search criteria or filters</p>
        </div>

        <?php if (mysqli_num_rows($result) == 0): ?>
        <div class="no-results show">
          <h3>No Available Units</h3>
          <p>There are currently no available units. Please check back later.</p>
        </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <footer class="footer">
    <div class="footer-content">
      <div class="footer-section">
        <h3>Contact Information</h3>
        <p>Manager: Kyle Angela Catiis</p>
        <p>Email: kyleangelacatiis@gmail.com</p>
        <p>Phone: 0912-345-6789</p>
        <p>Address: Ofelia Pasig, Daet, Camarines Norte</p>
        <div class="social-links">
          <a href="#"><img src="../tenantviewIcons/fb.png" alt="Facebook"></a>
          <a href="#"><img src="../tenantviewIcons/fb.png" alt="Twitter"></a>
          <a href="#"><img src="../tenantviewIcons/fb.png" alt="Instagram"></a>
        </div>
      </div>
      <div class="footer-section">
        <h3>Quick Links</h3>
        <a href="TENANTHOMEPAGE.php">Home</a>
        <a href="TENANTHOMEPAGE.php#aboutRYC">About Us</a>
        <a href="TENANTHOMEPAGE.php#availUnitsContainer">Available Units</a>
        <a href="TRANSACTIONSPAGE.php">Transactions</a>
        <a href="INBOXPAGE.php">Inbox</a>
      </div>
      <div class="footer-section">
        <h3>Services</h3>
        <a href="#">Unit Inquiries</a>
        <a href="#">Online Payments</a>
        <a href="#">Maintenance Requests</a>
        <a href="#">Account Management</a>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2025 RYC Dormitelle Apartment Management System. All rights reserved. | Developed by Joriz Gutierrez</p>
    </div>
  </footer>

  <script>
    function toggleMenu() {
      document.getElementById('containerSystemName').classList.toggle('show');
      document.getElementById('navbar').classList.toggle('show');
    }

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearch');
    const unitsContainer = document.getElementById('unitsContainer');
    const noResults = document.getElementById('noResults');
    const units = document.querySelectorAll('.availUnitsBox');
    let currentFilter = 'all';

    // Search input event listener
    searchInput.addEventListener('input', function() {
      const searchTerm = this.value.toLowerCase().trim();
      
      // Show/hide clear button
      if (searchTerm) {
        clearSearchBtn.classList.add('visible');
      } else {
        clearSearchBtn.classList.remove('visible');
      }
      
      filterAndSearch();
    });

    // Clear search function
    function clearSearch() {
      searchInput.value = '';
      clearSearchBtn.classList.remove('visible');
      filterAndSearch();
      searchInput.focus();
    }

    // Filter functions
    function filterUnits(filterType) {
      // Update active filter button
      document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
      });
      event.target.classList.add('active');
      
      currentFilter = filterType;
      filterAndSearch();
    }

    // Combined filter and search function
    function filterAndSearch() {
      const searchTerm = searchInput.value.toLowerCase().trim();
      let visibleCount = 0;

      units.forEach(unit => {
        const unitNumber = unit.getAttribute('data-unit').toLowerCase();
        const unitType = unit.getAttribute('data-type').toLowerCase();
        const unitFeatures = unit.getAttribute('data-features').toLowerCase();
        const unitText = unit.textContent.toLowerCase();
        
        // Check if unit matches search term
        const matchesSearch = !searchTerm || 
          unitNumber.includes(searchTerm) ||
          unitType.includes(searchTerm) ||
          unitFeatures.includes(searchTerm) ||
          unitText.includes(searchTerm);
        
        // Check if unit matches filter
        let matchesFilter = true;
        if (currentFilter !== 'all') {
          matchesFilter = unitType.includes(currentFilter.toLowerCase()) ||
                          unitFeatures.includes(currentFilter.toLowerCase()) ||
                          (currentFilter === 'available' && unit.querySelector('.unit-badge').textContent === 'AVAILABLE');
        }
        
        // Show/hide unit based on both search and filter
        if (matchesSearch && matchesFilter) {
          unit.classList.remove('hidden');
          visibleCount++;
        } else {
          unit.classList.add('hidden');
        }
      });

      // Show/hide no results message
      if (visibleCount === 0 && units.length > 0) {
        noResults.classList.add('show');
        unitsContainer.style.display = 'none';
      } else if (units.length > 0) {
        noResults.classList.remove('show');
        unitsContainer.style.display = 'grid';
      }
    }

    // Add enter key support for search
    searchInput.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        filterAndSearch();
      }
    });

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
      filterAndSearch();
    });
  </script>
</body>
</html>
<?php
// Close database connection
mysqli_close($conn);
?>