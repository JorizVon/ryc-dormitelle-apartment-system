<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>RYC Dormitelle - Unit Details</title>
  <style>
    html, body {
      height: 100%;
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      background-color: #fff;
    }

    body {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    .content {
      flex: 1 0 auto;
      display: flex;
      flex-direction: column;
    }

    .page-wrapper {
      flex: 1;
    }

    /* Header styles */
    .header {
      display: flex;
      justify-content: space-between;
      width: 100%;
      height: 80px;
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

    /* Mobile menu styles */
    .hanburgerandaccContainer {
      background-color: #01214B;
      width: 22%;
      height: 100%;
      display: none;
      justify-content: center;
      align-items: center;
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

    /* Unit image styles */
    .unit-image {
      width: 100%;
      height: 50vh; /* Half of viewport height */
      overflow: hidden;
    }

    .unit-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    /* Unit information section */
    .main-content {
      padding: 20px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .unit-header {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      margin-bottom: 20px;
      gap: 15px;
    }

    .unit-id {
      background-color: #004AAD;
      color: white;
      padding: 12px 25px;
      font-size: 18px;
      font-weight: bold;
      border: none;
    }

    .inquire-button {
      background-color: #004AAD;
      color: white;
      padding: 12px 25px;
      font-size: 16px;
      font-weight: bold;
      border: none;
      border-radius: 25px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .inquire-button:hover {
      background-color: #003080;
    }

    .unit-title {
      font-size: 20px;
      font-weight: 600;
      margin: 0;
      color: #333;
      padding: 5px 0;
    }

    .unit-address {
      font-size: 16px;
      color: #555;
      margin: 0;
      padding-bottom: 10px;
    }

    /* Unit info and form layout */
    .unit-content {
      display: flex;
      flex-wrap: wrap;
      gap: 30px;
    }

    .unit-details-section {
      flex: 1;
      min-width: 300px;
    }

    .section-heading {
      font-size: 18px;
      font-weight: bold;
      color: #004AAD;
      margin-bottom: 15px;
      padding-bottom: 5px;
      border-bottom: 2px solid #79B1FC;
    }

    .details-list {
      list-style-type: none;
      padding: 0;
      margin: 0;
    }

    .details-list li {
      display: flex;
      margin-bottom: 10px;
      font-size: 15px;
    }

    .details-list li:before {
      content: "•";
      color: #004AAD;
      font-weight: bold;
      margin-right: 10px;
    }

    /* Inquiry form styles */
    .inquiry-form-container {
      flex: 1;
      min-width: 300px;
      max-width: 500px;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      background-color: #f9f9f9;
      display: none;
    }

    .form-heading {
      text-align: center;
      color: #004AAD;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 1px solid #ddd;
    }

    .form-row {
      margin-bottom: 15px;
    }

    .form-row label {
      display: block;
      margin-bottom: 5px;
      font-weight: 600;
      color: #333;
      font-size: 14px;
    }

    .form-row input {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 4px;
      box-sizing: border-box;
      font-size: 14px;
    }

    .form-row input[readonly] {
      background-color: #f0f0f0;
    }

    .submit-btn {
      background-color: #004AAD;
      color: white;
      padding: 12px 20px;
      font-size: 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      width: 100%;
      margin-top: 10px;
      transition: background-color 0.3s;
    }

    .submit-btn:hover {
      background-color: #003080;
    }

    .form-notification {
      text-align: center;
      font-size: 14px;
      color: #555;
      margin-top: 15px;
    }

    .error-message {
      color: #d9534f;
      font-size: 12px;
      margin-top: 5px;
      display: none;
    }

    .error-input {
      border: 1px solid #d9534f !important;
    }

    /* Footer styles */
    .footer {
      background-color: #2262B8;
      color: white;
      padding: 20px;
      margin-top: 30px;
      flex-shrink: 0;
    }

    .footer-content {
      display: flex;
      justify-content: space-between;
      max-width: 1200px;
      margin: 0 auto;
    }

    .footer-left h4 {
      font-size: 16px;
      margin-bottom: 10px;
    }

    .footer-left p {
      font-size: 14px;
      margin: 5px 0;
    }

    .footer-right {
      text-align: right;
    }

    .footer-right p {
      font-size: 14px;
    }

    /* Responsive styles */
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
        z-index: 10;
        height: auto;
        padding: 10px 0;
      }

      .containerSystemName.show {
        display: flex;
        width: 50vw;
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
        z-index: 10;
      }

      .navbarContent {
        flex-direction: column;
        align-items: flex-start;
        margin: 0;
        padding: 20px;
      }

      .navbarContent a {
        margin: 10px 0;
      }

      .loginLogOut {
        display: none;
      }

      .hamburger {
        display: block;
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

      .unit-header {
        flex-direction: column;
        align-items: flex-start;
      }

      .footer-content {
        flex-direction: column;
      }

      .footer-left, .footer-right {
        text-align: center;
        margin-bottom: 15px;
      }

      .unit-image {
        height: 35vh;
      }
    }
  </style>
</head>
<body>
  <div class="content">
    <div class="page-wrapper">
      <!-- Header Section -->
      <div class="header">
        <div class="hanburgerandaccContainer">
          <button class="hamburger" onclick="toggleMenu()">☰</button>
          <div class="adminSection">
        <a href="SIGNIN.php">Sign Up</a> |
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

      <!-- Unit Image -->
      <div class="unit-image">
        <img src="/api/placeholder/1200/600" alt="Unit A-001">
      </div>

      <!-- Main Content -->
      <div class="main-content">
        <!-- Unit Header -->
        <div class="unit-header">
          <div class="unit-id">A-001</div>
          <button class="inquire-button" onclick="toggleForm()">Inquire Now</button>
        </div>
        
        <h2 class="unit-title">1BR Unit accommodating up to 5 persons</h2>
        <p class="unit-address">Ofelia Pasig, Daet, Camarines Norte</p>
        
        <!-- Unit Information and Form Container -->
        <div class="unit-content">
          <!-- Details Section -->
          <div class="unit-details-section">
            <h3 class="section-heading">UNIT DETAILS</h3>
            <ul class="details-list">
              <li>Unit Size: 28 Sqm</li>
              <li>Floor Level Type: 2</li>
              <li>Capacity: 4-5 Persons</li>
              <li>Type: Studio</li>
              <li>Monthly Rent Amount: ₱10,000</li>
            </ul>
            
            <h3 class="section-heading">PAYMENT TERMS</h3>
            <ul class="details-list">
              <li>Advance Payments: ₱10,000 (1 month)</li>
              <li>Security Deposit: ₱20,000 (2 months)</li>
            </ul>
          </div>
          
          <!-- Inquiry Form -->
          <div id="inquiry-form" class="inquiry-form-container">
            <h3 class="form-heading">Unit Inquiry Form</h3>
            <form id="rentalForm">
              <div class="form-row">
                <label for="fullname">Full Name:</label>
                <input type="text" id="fullname" name="fullname" required placeholder="Juan Dela Cruz">
                <span id="fullname-error" class="error-message">Please enter a valid full name (at least first and last name)</span>
              </div>
              
              <div class="form-row">
                <label for="contact">Contact Number:</label>
                <input type="tel" id="contact" name="contact" required placeholder="+639123456789">
                <span id="contact-error" class="error-message">Please enter a valid Philippine phone number (+639XXXXXXXXX)</span>
              </div>
              
              <div class="form-row">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" required placeholder="example@gmail.com">
                <span id="email-error" class="error-message">Please enter a valid email address (example@domain.com)</span>
              </div>
              
              <div class="form-row">
                <label for="movein">Preferred Move-in Date:</label>
                <input type="date" id="movein" name="movein" required onchange="updateDates()">
              </div>
              
              <div class="form-row">
                <label for="leaseStart">Lease Start Date:</label>
                <input type="date" id="leaseStart" name="leaseStart" readonly onchange="updateDueDate()">
              </div>
              
              <div class="form-row">
                <label for="leaseEnd">Lease End Date:</label>
                <input type="date" id="leaseEnd" name="leaseEnd" required>
              </div>
              
              <div class="form-row">
                <label for="dueDate">Payment Due Date:</label>
                <input type="text" id="dueDate" name="dueDate" readonly placeholder="Every Nth day of the month">
              </div>
              
              <div class="form-row">
                <label for="rent">Monthly Rent Amount (₱):</label>
                <input type="number" id="rent" name="rent" value="10000" readonly>
              </div>
              
              <button type="submit" class="submit-btn">Submit Inquiry</button>
              
              <p class="form-notification">
                Thank you for your inquiry. Once the landlord confirms your request, 
                you will receive a text message notification.
              </p>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <div class="footer">
    <div class="footer-content">
      <div class="footer-left">
        <h4>Contact Information</h4>
        <p>Manager: Kyle Angela Catiis</p>
        <p>Address: Ofelia Pasig, Daet, Camarines Norte</p>
        <p>Email: kyleangelacatiis@gmail.com</p>
        <p>Phone: 0912-345-6789</p>
      </div>
      <div class="footer-right">
        <p>Apartment Management System @ 2025.</p>
        <p>All Rights Reserved.</p>
        <p>Developed by Joriz Gutierrez</p>
      </div>
    </div>
  </div>

  <script>
    // Set today's date as the minimum date for move-in
    document.addEventListener('DOMContentLoaded', function() {
      const today = new Date();
      const formattedDate = today.toISOString().split('T')[0];
      document.getElementById('movein').min = formattedDate;
      
      // Set up form validation
      setupFormValidation();
    });
    
    function toggleMenu() {
      document.getElementById('navbar').classList.toggle('show');
      document.getElementById('containerSystemName').classList.toggle('show');
    }
  
    function toggleForm() {
      const form = document.getElementById('inquiry-form');
      const inquireButton = document.querySelector('.inquire-button');
      
      if (form.style.display === 'block') {
        form.style.display = 'none';
        inquireButton.textContent = 'Inquire Now';
      } else {
        form.style.display = 'block';
        inquireButton.textContent = 'Hide Form';
      }
    }
    
    function updateDates() {
      // Get the move-in date value
      const moveInValue = document.getElementById('movein').value;
      
      // Clear all dependent fields if no date is selected
      if (!moveInValue) {
        document.getElementById('leaseStart').value = '';
        document.getElementById('dueDate').value = '';
        return;
      }
      
      // Proceed with date calculations if a date is selected
      const moveInDate = new Date(moveInValue);
      
      // Set lease start date to the same as move-in date
      document.getElementById('leaseStart').value = moveInDate.toISOString().split('T')[0];
      
      // Update the payment due date text
      updateDueDate();
      

    }
    
    function updateDueDate() {
      // Get the lease start date
      const leaseStartValue = document.getElementById('leaseStart').value;
      
      if (!leaseStartValue) {
        document.getElementById('dueDate').value = '';
        return;
      }
      
      const leaseStartDate = new Date(leaseStartValue);
      const day = leaseStartDate.getDate();
      
      // Format the due date text
      let dueDateText;
      let dayNumber = day;
      
      // Ensure we don't go beyond the 28th (since some months don't have 29, 30, 31)
      if (dayNumber > 28) {
        dayNumber = 28;
      }
      
      // Add suffix to the day number
      let suffix;
      if (dayNumber === 1 || dayNumber === 21) {
        suffix = 'st';
      } else if (dayNumber === 2 || dayNumber === 22) {
        suffix = 'nd';
      } else if (dayNumber === 3 || dayNumber === 23) {
        suffix = 'rd';
      } else {
        suffix = 'th';
      }
      
      dueDateText = `Every ${dayNumber}${suffix} day of the month`;
      document.getElementById('dueDate').value = dueDateText;
    }
    
    function setupFormValidation() {
      // Full name validation
      const fullnameInput = document.getElementById('fullname');
      const fullnameError = document.getElementById('fullname-error');
      
      fullnameInput.addEventListener('input', function() {
        validateFullName(this, fullnameError);
      });
      
      fullnameInput.addEventListener('blur', function() {
        validateFullName(this, fullnameError);
      });
      
      // Contact number validation
      const contactInput = document.getElementById('contact');
      const contactError = document.getElementById('contact-error');
      
      contactInput.addEventListener('input', function() {
        validatePhoneNumber(this, contactError);
      });
      
      contactInput.addEventListener('blur', function() {
        validatePhoneNumber(this, contactError);
      });
      
      // Email validation
      const emailInput = document.getElementById('email');
      const emailError = document.getElementById('email-error');
      
      emailInput.addEventListener('input', function() {
        validateEmail(this, emailError);
      });
      
      emailInput.addEventListener('blur', function() {
        validateEmail(this, emailError);
      });
    }
    
    function validateFullName(input, errorElement) {
      // Check if name has at least two words and each word is at least 2 characters
      const value = input.value.trim();
      const words = value.split(/\s+/).filter(word => word.length > 0);
      const isValid = words.length >= 2 && words.every(word => word.length >= 2) && 
                    /^[A-Za-z\s\-']+$/.test(value); // Only allow letters, spaces, hyphens, and apostrophes
      
      if (isValid) {
        input.classList.remove('error-input');
        errorElement.style.display = 'none';
        return true;
      } else {
        input.classList.add('error-input');
        errorElement.style.display = 'block';
        return false;
      }
    }
    
    function validatePhoneNumber(input, errorElement) {
      // Philippine phone number format: +639XXXXXXXXX
      const phoneRegex = /^\+639\d{9}$/;
      const isValid = phoneRegex.test(input.value.trim());
      
      if (isValid) {
        input.classList.remove('error-input');
        errorElement.style.display = 'none';
        return true;
      } else {
        input.classList.add('error-input');
        errorElement.style.display = 'block';
        return false;
      }
    }
    
    function validateEmail(input, errorElement) {
      // Basic email validation
      const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
      const isValid = emailRegex.test(input.value.trim());
      
      if (isValid) {
        input.classList.remove('error-input');
        errorElement.style.display = 'none';
        return true;
      } else {
        input.classList.add('error-input');
        errorElement.style.display = 'block';
        return false;
      }
    }
    
    // Form submission handler
    document.getElementById('rentalForm').addEventListener('submit', function(event) {
      event.preventDefault();
      
      // Validate all fields before submission
      const fullnameInput = document.getElementById('fullname');
      const fullnameError = document.getElementById('fullname-error');
      const fullnameValid = validateFullName(fullnameInput, fullnameError);
      
      const contactInput = document.getElementById('contact');
      const contactError = document.getElementById('contact-error');
      const contactValid = validatePhoneNumber(contactInput, contactError);
      
      const emailInput = document.getElementById('email');
      const emailError = document.getElementById('email-error');
      const emailValid = validateEmail(emailInput, emailError);
      
      // Check if all validations passed
      if (fullnameValid && contactValid && emailValid) {
        // You can add form submission logic here
        alert('Form submitted successfully!');
        
        // Hide the form after submission
        document.getElementById('inquiry-form').style.display = 'none';
        document.querySelector('.inquire-button').textContent = 'Inquire Now';
      } else {
        alert('Please correct the errors in the form before submitting.');
      }
    });
  </script>
</body>
</html>