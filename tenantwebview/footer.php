<?php
// FETCH ADMIN DATA
// Initialize default variables to prevent errors if the query fails
$manager_name = "Manager";
$manager_email = "email@example.com";
$manager_contact = "0900-000-0000";

// Ensure connection exists before querying
if (isset($conn)) {
    $admin_query = "SELECT `admin_email`, `admin_name`, `admin_contact` FROM `admin_profile` LIMIT 1";
    $admin_result = mysqli_query($conn, $admin_query);

    if ($admin_result && mysqli_num_rows($admin_result) > 0) {
        $admin_row = mysqli_fetch_assoc($admin_result);
        $manager_name = $admin_row['admin_name'];
        $manager_email = $admin_row['admin_email'];
        $manager_contact = $admin_row['admin_contact'];
    }
}
?>

<!-- Footer -->
  <footer class="footer">
    <div class="footer-content">
      <div class="footer-section">
        <h3>Contact Information</h3>
        <!-- Dynamic Data Loaded Here -->
        <p>Manager: <?php echo htmlspecialchars($manager_name); ?></p>
        <p>Email: <?php echo htmlspecialchars($manager_email); ?></p>
        <p>Phone: <?php echo htmlspecialchars($manager_contact); ?></p>
        <p>Address: Ofelia Pasig, Daet, Camarines Norte</p>
        
        <div class="social-links">
          <a href="#"><img src="../otherIcons/fbicon.png" alt="Facebook"></a>
          <a href="#"><img src="../otherIcons/igicon.png" alt="Instagram"></a>
          <a href="#"><img src="../otherIcons/tgicon.png" alt="Telegram"></a>
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

  <?php 
  // Include the reusable chat component if user is logged in
  if (isset($_SESSION['email_account'])) {
      include 'TENANT_CHAT_COMPONENT.php'; 
  }
  ?>
</body>
</html>
<?php 
// Close database connection if it exists
if (isset($conn)) {
    mysqli_close($conn);
}
?>