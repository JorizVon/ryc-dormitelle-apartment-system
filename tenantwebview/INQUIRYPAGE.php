<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['email_account'])) {
    // Assuming login.php is in the parent directory of ryc-dormitelle-apartment-system
    // If login.php is inside ryc-dormitelle-apartment-system, it would be '../login.php'
    // Adjust this path carefully based on your actual login.php location relative to INQUIRYPAGE.php
    header("Location: ../LOGIN.php"); // Example if login.php is two levels up from tenantwebview
    exit();
}

// Assuming db_connect.php is in the parent directory of ryc-dormitelle-apartment-system
// If db_connect.php is inside ryc-dormitelle-apartment-system, it would be '../db_connect.php'
// Adjust this path carefully
require_once '../db_connect.php';

$unit_no_from_url = null;
$unit_data = null;
$unit_images = []; // Array to hold all images for the unit
$page_error_message = '';
$form_success_message = '';
$form_error_message = '';

if (isset($_GET['unit_no'])) {
    $unit_no_from_url = $_GET['unit_no'];

    // Fetch main unit data
    $sql_fetch_unit = "SELECT `unit_no`, `apartment_no`, `unit_address`, `unit_size`, 
                              `occupant_capacity`, `floor_level`, `unit_type`, 
                              `monthly_rent_amount`, `unit_status` 
                       FROM `units` 
                       WHERE `units`.`unit_no` = ?";
    $stmt_fetch_unit = $conn->prepare($sql_fetch_unit);
    if ($stmt_fetch_unit) {
        $stmt_fetch_unit->bind_param("s", $unit_no_from_url);
        $stmt_fetch_unit->execute();
        $result_unit = $stmt_fetch_unit->get_result();
        if ($result_unit->num_rows > 0) {
            $unit_data = $result_unit->fetch_assoc();
            if ($unit_data['unit_status'] !== 'Available') {
                $page_error_message = "This unit (" . htmlspecialchars($unit_data['unit_no']) . ") is currently not available for inquiry as its status is: " . htmlspecialchars($unit_data['unit_status']) . ".";
                $unit_data = null;
            } else {
                // If unit is available, fetch its images
                $sql_fetch_images = "SELECT `unit_image` FROM `unit_images` WHERE `unit_no` = ?";
                $stmt_fetch_images = $conn->prepare($sql_fetch_images);
                if ($stmt_fetch_images) {
                    $stmt_fetch_images->bind_param("s", $unit_no_from_url);
                    $stmt_fetch_images->execute();
                    $result_images = $stmt_fetch_images->get_result();
                    while ($img_row = $result_images->fetch_assoc()) {
                        $unit_images[] = $img_row['unit_image'];
                    }
                    $stmt_fetch_images->close();
                    if (empty($unit_images)) {
                        // Optional: set a default placeholder if no images found
                        // $unit_images[] = 'default_placeholder.jpg'; 
                    }
                } else {
                    $page_error_message .= " Error fetching unit images: " . $conn->error;
                }
            }
        } else {
            $page_error_message = "Unit details not found for the selected unit.";
        }
        $stmt_fetch_unit->close();
    } else {
        $page_error_message = "Database error: Could not prepare statement to fetch unit details. " . $conn->error;
    }
} else {
    $page_error_message = "No unit selected. Please go back to the homepage and select a unit to inquire.";
}

// Handle form submission for inquiry
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_inquiry_confirmed'])) {
    // Ensure unit_data was loaded and is available before proceeding
    if (!$unit_data || $unit_data['unit_status'] !== 'Available') {
        $form_error_message = "Cannot submit inquiry. The unit is no longer available or there was an issue fetching unit details.";
    } else {
        $posted_unit_no = $_POST['unit_no_form'] ?? '';
        $full_name = trim($_POST['fullname'] ?? '');
        $contact_no = trim($_POST['contact_no'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pref_move_date_str = $_POST['pref_move_date'] ?? '';
        $start_date_str = $_POST['start_date'] ?? '';
        $end_date_str = $_POST['end_date'] ?? '';
        $payment_due_date_text = $_POST['payment_due_date'] ?? '';

        $errors = [];
        if (empty($posted_unit_no)) $errors[] = "Unit number is missing.";
        if (empty($full_name) || count(explode(' ', $full_name)) < 2) $errors[] = "Full name is required (first and last name).";
        if (empty($contact_no) || !preg_match("/^\+639\d{9}$/", $contact_no)) $errors[] = "Valid Philippine contact number is required (+639xxxxxxxxx).";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email address is required.";
        if (empty($pref_move_date_str)) $errors[] = "Preferred move-in date is required.";
        if (empty($start_date_str)) $errors[] = "Start date is required.";
        if (empty($end_date_str)) $errors[] = "End date is required.";
        
        $pref_move_date = date_create_from_format('Y-m-d', $pref_move_date_str);
        $start_date = date_create_from_format('Y-m-d', $start_date_str);
        $end_date = date_create_from_format('Y-m-d', $end_date_str);

        if (!$pref_move_date) $errors[] = "Invalid preferred move-in date format.";
        if (!$start_date) $errors[] = "Invalid start date format.";
        if (!$end_date) $errors[] = "Invalid end date format.";

        if ($start_date && $end_date && $end_date <= $start_date) {
            $errors[] = "End date must be after the start date.";
        }
        if ($unit_data && $posted_unit_no !== $unit_data['unit_no']) {
            $errors[] = "Unit number mismatch. Inquiry cannot be processed.";
        }

        if (empty($errors)) {
            $inquiry_date_time = date('Y-m-d H:i:s'); 

            $conn->begin_transaction();

            try {
                $sql_insert_inquiry = "INSERT INTO `pending_inquiry`(`inquiry_date_time`, `unit_no`, `full_name`, `contact_no`, `email`, `pref_move_date`, `start_date`, `end_date`, `payment_due_date`) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert_inquiry);
                if (!$stmt_insert) {
                    throw new Exception("Prepare failed for insert inquiry: " . $conn->error);
                }
                $stmt_insert->bind_param(
                    "sssssssss",
                    $inquiry_date_time,
                    $posted_unit_no,
                    $full_name,
                    $contact_no,
                    $email,
                    $pref_move_date_str,
                    $start_date_str,
                    $end_date_str,
                    $payment_due_date_text 
                );

                if (!$stmt_insert->execute()) {
                    throw new Exception("Execute failed for insert inquiry: " . $stmt_insert->error);
                }
                $stmt_insert->close();

                $sql_update_unit_status = "UPDATE `units` SET `unit_status`='Pending' WHERE `unit_no` = ?";
                $stmt_update_status = $conn->prepare($sql_update_unit_status);
                if (!$stmt_update_status) {
                    throw new Exception("Prepare failed for update unit status: " . $conn->error);
                }
                $stmt_update_status->bind_param("s", $posted_unit_no); 

                if (!$stmt_update_status->execute()) {
                    throw new Exception("Execute failed for update unit status: " . $stmt_update_status->error);
                }
                if ($stmt_update_status->affected_rows === 0) {
                    throw new Exception("Failed to update unit status or unit not found for update. Unit No: " . htmlspecialchars($posted_unit_no));
                }
                $stmt_update_status->close();

                $conn->commit();
                $page_error_message = "Your inquiry has been submitted successfully, and the unit status has been updated to Pending! You will be notified once the landlord reviews your request.";
                $unit_data = null; // Hide form and details
                $unit_images = []; // Clear images as well
                $form_success_message = "";

            } catch (Exception $e) {
                $conn->rollback(); 
                $form_error_message = "An error occurred: " . $e->getMessage();
            }

        } else {
            $form_error_message = "Please correct the following errors: <ul>";
            foreach ($errors as $error) {
                $form_error_message .= "<li>" . htmlspecialchars($error) . "</li>";
            }
            $form_error_message .= "</ul>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>RYC Dormitelle - Unit Inquiry</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* ... (keep all your existing CSS from the previous version) ... */
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
    
    /* ---- NEW CSS FOR IMAGE SLIDER ---- */
    .unit-image-slider {
      width: 100%;
      max-width: 700px; /* Adjust as needed */
      margin: 0 auto 20px auto; /* Center the slider */
      position: relative;
      overflow: hidden; /* Important for hiding non-active slides */
      border: 1px solid #ddd;
      height: 50vh; /* Same as .unit-image */
    }

    .slider-image-container {
      display: flex; /* Arrange images side-by-side */
      transition: transform 0.5s ease-in-out; /* Smooth sliding effect */
      height: 100%;
    }

    .slider-image-container img {
      width: 100%; /* Each image takes full width of the container */
      height: 100%;
      object-fit: cover;
      flex-shrink: 0; /* Prevent images from shrinking */
    }

    .slider-btn {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background-color: rgba(0, 0, 0, 0.5);
      color: white;
      border: none;
      padding: 10px 15px;
      font-size: 20px;
      cursor: pointer;
      z-index: 10;
    }

    .slider-btn.prev {
      left: 10px;
    }

    .slider-btn.next {
      right: 10px;
    }
    /* ---- END NEW CSS FOR IMAGE SLIDER ---- */


    /* Unit image styles - This can be removed or repurposed if slider replaces it */
    /* .unit-image {
      width: 100%;
      height: 50vh; 
      overflow: hidden;
      background-color: #e0e0e0;
    }

    .unit-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    } */

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

    .inquire-button-toggle { 
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

    .inquire-button-toggle:hover {
      background-color: #003080;
    }
     .inquire-button-toggle:disabled {
      background-color: #cccccc;
      cursor: not-allowed;
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
    .inquiry-form-container.show-form-on-load {
        display: block !important;
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

    .submit-btn-modal-trigger { 
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

    .submit-btn-modal-trigger:hover {
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
    .alert-success {
        color: #155724;
        background-color: #d4edda;
        border-color: #c3e6cb;
        padding: .75rem 1.25rem;
        margin-bottom: 1rem;
        border: 1px solid transparent;
        border-radius: .25rem;
    }
    .alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
        padding: .75rem 1.25rem;
        margin-bottom: 1rem;
        border: 1px solid transparent;
        border-radius: .25rem;
    }


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

      /* .unit-image,  */
      .unit-image-slider { /* Adjust slider height for mobile */
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
            <a href="#">Sign Up</a> <!-- Placeholder -->
            <a href="#">Log Out</a> <!-- Placeholder -->
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
             <!-- Update these paths if INQUIRYPAGE.php is not in the same dir as these target pages -->
            <a href="TENANTHOMEPAGE.php">Home</a>
            <a href="TENANTHOMEPAGE.php#aboutRYC" class="scroll-link">About</a>
            <a href="TENANTHOMEPAGE.php#availUnitsContainer" class="scroll-link">Available Units</a>
            <a href="TRANSACTIONSPAGE.php">Transactions</a>
            <a href="INBOXPAGE.php">Inbox</a>
            <div class="loginLogOut">
              <a href="TENANTACCOUNTPAGE.php">Profile</a>
              <p style="font-size: 20px; color: white; margin: 0 5px;">|</p>
              <a href="../../LOGIN.php">Log Out</a> <!-- Adjusted path -->
            </div>
          </div>
        </div>
      </div>

    <?php if (!empty($page_error_message)): ?>
        <div class="main-content">
            <div class="alert <?php echo (strpos(strtolower($page_error_message), 'success') !== false || strpos(strtolower($page_error_message), 'submitted') !== false) ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo $page_error_message; ?>
            </div>
            <p><a href="TENANTHOMEPAGE.php" class="btn btn-primary mt-3">Back to Homepage</a></p>
        </div>
    <?php endif; ?>

    <?php if ($unit_data): // Only show if unit_data is available ?>
      <!-- Unit Image Slider -->
      <?php if (!empty($unit_images)): ?>
        <div class="unit-image-slider" id="unitImageSlider">
          <div class="slider-image-container" id="sliderImageContainer">
            <?php foreach ($unit_images as $image_filename): ?>
              <img src="../unitImages/<?php echo htmlspecialchars($image_filename); ?>" alt="Unit Image for <?php echo htmlspecialchars($unit_data['unit_no']); ?>">
            <?php endforeach; ?>
          </div>
          <?php if (count($unit_images) > 1): // Only show buttons if more than one image ?>
            <button class="slider-btn prev" onclick="changeSlide(-1)">❮</button>
            <button class="slider-btn next" onclick="changeSlide(1)">❯</button>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="unit-image-slider" style="display:flex; align-items:center; justify-content:center; background-color:#f0f0f0;">
            <p>No images available for this unit.</p>
        </div>
      <?php endif; ?>


      <!-- Main Content -->
      <div class="main-content">
        <div class="unit-header">
          <div class="unit-id"><?php echo htmlspecialchars($unit_data['unit_no']); ?></div>
          <button class="inquire-button-toggle" 
                  onclick="toggleForm()" 
                  <?php echo ($unit_data['unit_status'] !== 'Available') ? 'disabled' : ''; ?>>
            <?php echo ($unit_data['unit_status'] !== 'Available') ? 'Unit Not Available' : 'Inquire Now'; ?>
          </button>
        </div>
        
        <h2 class="unit-title"><?php echo htmlspecialchars($unit_data['unit_type']); ?> unit accommodating up to <?php echo htmlspecialchars($unit_data['occupant_capacity']); ?> persons</h2>
        <p class="unit-address"><?php echo htmlspecialchars($unit_data['unit_address']); ?></p>
        
        <div class="unit-content">
          <div class="unit-details-section">
            <h3 class="section-heading">UNIT DETAILS</h3>
            <ul class="details-list">
              <li>Unit Size: <?php echo htmlspecialchars($unit_data['unit_size']); ?> Sqm</li>
              <li>Floor Level: <?php echo htmlspecialchars($unit_data['floor_level']); ?></li>
              <li>Capacity: <?php echo htmlspecialchars($unit_data['occupant_capacity']); ?> Persons</li>
              <li>Type: <?php echo htmlspecialchars($unit_data['unit_type']); ?></li>
              <li>Monthly Rent Amount: ₱<?php echo number_format($unit_data['monthly_rent_amount'], 2); ?></li>
              <li>Current Status: <strong style="color: <?php echo $unit_data['unit_status'] === 'Available' ? 'green' : ($unit_data['unit_status'] === 'Pending' ? 'orange' : 'red'); ?>;"><?php echo htmlspecialchars($unit_data['unit_status']); ?></strong></li>
            </ul>
            
            <h3 class="section-heading">PAYMENT TERMS</h3>
            <ul class="details-list">
              <li>Advance Payments: ₱<?php echo number_format($unit_data['monthly_rent_amount'], 2); ?> (1 month)</li>
              <li>Security Deposit: ₱<?php echo number_format($unit_data['monthly_rent_amount'] * 2, 2); ?> (2 months)</li>
            </ul>
          </div>
          
          <?php if ($unit_data['unit_status'] === 'Available'): ?>
          <div id="inquiry-form" class="inquiry-form-container <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($form_error_message)) echo 'show-form-on-load'; ?>">
            <h3 class="form-heading">Unit Inquiry Form</h3>

            <?php if (!empty($form_error_message)): ?>
                <div class="alert alert-danger"><?php echo $form_error_message; ?></div>
            <?php endif; ?>

            <form id="rentalForm" method="POST" action="INQUIRYPAGE.php?unit_no=<?php echo htmlspecialchars($unit_no_from_url); ?>">
              <input type="hidden" name="unit_no_form" value="<?php echo htmlspecialchars($unit_data['unit_no']); ?>">
              
              <div class="form-row">
                <label for="fullname">Full Name:</label>
                <input type="text" id="fullname" name="fullname" required placeholder="Juan Dela Cruz" value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>">
                <span id="fullname-error" class="error-message">Please enter a valid full name</span>
              </div>
              
              <div class="form-row">
                <label for="contact_no">Contact Number:</label>
                <input type="tel" id="contact_no" name="contact_no" required placeholder="+639123456789" value="<?php echo isset($_POST['contact_no']) ? htmlspecialchars($_POST['contact_no']) : ''; ?>">
                <span id="contact-error" class="error-message">Please enter a valid Philippine phone number</span>
              </div>
              
              <div class="form-row">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" required placeholder="example@gmail.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <span id="email-error" class="error-message">Please enter a valid email address</span>
              </div>
              
              <div class="form-row">
                <label for="pref_move_date">Preferred Move-in Date:</label>
                <input type="date" id="pref_move_date" name="pref_move_date" required onchange="updateDates()" value="<?php echo isset($_POST['pref_move_date']) ? htmlspecialchars($_POST['pref_move_date']) : ''; ?>">
              </div>
              
              <div class="form-row">
                <label for="start_date">Start Date (Lease):</label>
                <input type="date" id="start_date" name="start_date" readonly onchange="updateDueDate()" value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>">
              </div>
              
              <div class="form-row">
                <label for="end_date">End Date (Lease):</label>
                <input type="date" id="end_date" name="end_date" required value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>">
              </div>
              
              <div class="form-row">
                <label for="payment_due_date">Payment Due Date:</label>
                <input type="text" id="payment_due_date" name="payment_due_date" readonly placeholder="Every Nth day of the month" value="<?php echo isset($_POST['payment_due_date']) ? htmlspecialchars($_POST['payment_due_date']) : ''; ?>">
              </div>
              
              <div class="form-row">
                <label for="monthly_rent_amount_display">Monthly Rent Amount (₱):</label>
                <input type="text" id="monthly_rent_amount_display" name="monthly_rent_amount_display" value="<?php echo number_format($unit_data['monthly_rent_amount'], 2); ?>" readonly>
              </div>
              
              <button type="button" class="submit-btn-modal-trigger" id="showConfirmModalBtn">Submit Inquiry</button>
              
              <p class="form-notification">
                Thank you for your inquiry. Once the landlord confirms your request, 
                you will receive a text message notification.
              </p>
            </form>
          </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
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

  <?php if ($unit_data && $unit_data['unit_status'] === 'Available'): ?>
  <div class="modal fade" id="confirmInquiryModal" tabindex="-1" aria-labelledby="confirmInquiryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="confirmInquiryModalLabel">Confirm Inquiry</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to submit this inquiry for Unit <?php echo htmlspecialchars($unit_data['unit_no']); ?>?
          This will mark the unit as 'Pending'.
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="confirmInquirySubmitBtn">Confirm & Submit</button>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // ---- NEW JAVASCRIPT FOR SLIDER ----
    let currentSlideIndex = 0;
    const slidesContainer = document.getElementById('sliderImageContainer');
    const totalSlides = slidesContainer ? slidesContainer.children.length : 0;

    function showSlide(index) {
        if (!slidesContainer || totalSlides === 0) return;
        const slideWidth = slidesContainer.children[0].clientWidth;
        slidesContainer.style.transform = `translateX(-${index * slideWidth}px)`;
    }

    function changeSlide(n) {
        currentSlideIndex += n;
        if (currentSlideIndex >= totalSlides) {
            currentSlideIndex = 0;
        } else if (currentSlideIndex < 0) {
            currentSlideIndex = totalSlides - 1;
        }
        showSlide(currentSlideIndex);
    }
    // Initialize slider
    if (totalSlides > 0) {
        showSlide(currentSlideIndex);
    }
    // ---- END NEW JAVASCRIPT FOR SLIDER ----


    document.addEventListener('DOMContentLoaded', function() {
      const today = new Date().toISOString().split('T')[0];
      const prefMoveDateInput = document.getElementById('pref_move_date');
      if (prefMoveDateInput) {
        prefMoveDateInput.min = today;
      }
      
      setupFormValidation();

      const inquiryFormDiv = document.getElementById('inquiry-form');
      if (inquiryFormDiv && inquiryFormDiv.classList.contains('show-form-on-load')) {
        const inquireButton = document.querySelector('.inquire-button-toggle');
        if (inquireButton && !inquireButton.disabled) inquireButton.textContent = 'Hide Form';
      }

      const showModalButton = document.getElementById('showConfirmModalBtn');
      const confirmSubmitButton = document.getElementById('confirmInquirySubmitBtn');
      const rentalForm = document.getElementById('rentalForm');
      
      if (showModalButton && confirmSubmitButton && rentalForm) {
        var confirmModalInstance = new bootstrap.Modal(document.getElementById('confirmInquiryModal'));

        showModalButton.addEventListener('click', function() {
          let allValid = true;
          if (!validateFullName(document.getElementById('fullname'), document.getElementById('fullname-error'))) allValid = false;
          if (!validatePhoneNumber(document.getElementById('contact_no'), document.getElementById('contact-error'))) allValid = false;
          if (!validateEmail(document.getElementById('email'), document.getElementById('email-error'))) allValid = false;
          
          if (!document.getElementById('pref_move_date').value) {
            document.getElementById('pref_move_date').classList.add('error-input');
            allValid = false;
          } else {
            document.getElementById('pref_move_date').classList.remove('error-input');
          }
          if (!document.getElementById('end_date').value) {
            document.getElementById('end_date').classList.add('error-input');
            allValid = false;
          } else {
            document.getElementById('end_date').classList.remove('error-input');
          }
          if(!document.getElementById('start_date').value) {
            updateDates(); 
            if(!document.getElementById('start_date').value) { 
                document.getElementById('pref_move_date').classList.add('error-input');
                 allValid = false;
            }
          }

          if (allValid) {
            confirmModalInstance.show();
          } else {
            alert('Please fill in all required fields and correct any errors before submitting.');
          }
        });

        confirmSubmitButton.addEventListener('click', function() {
          let confirmedInput = rentalForm.querySelector('input[name="submit_inquiry_confirmed"]');
          if (!confirmedInput) {
              confirmedInput = document.createElement('input');
              confirmedInput.type = 'hidden';
              confirmedInput.name = 'submit_inquiry_confirmed';
              rentalForm.appendChild(confirmedInput);
          }
          confirmedInput.value = '1';
          rentalForm.submit();
        });
      }
    });
    
    function toggleMenu() {
      document.getElementById('navbar').classList.toggle('show');
      document.getElementById('containerSystemName').classList.toggle('show');
    }
  
    function toggleForm() {
      const form = document.getElementById('inquiry-form');
      const inquireButton = document.querySelector('.inquire-button-toggle');
      
      if (!form) return;

      if (form.style.display === 'block') {
        form.style.display = 'none';
        if (inquireButton && !inquireButton.disabled) inquireButton.textContent = 'Inquire Now';
      } else {
        form.style.display = 'block';
        if (inquireButton && !inquireButton.disabled) inquireButton.textContent = 'Hide Form';
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }
    
    function updateDates() {
      const moveInValue = document.getElementById('pref_move_date').value;
      const leaseStartInput = document.getElementById('start_date');
      const endDateInput = document.getElementById('end_date');
      
      if (!moveInValue) {
        leaseStartInput.value = '';
        endDateInput.min = ''; 
        updateDueDate();
        return;
      }
      
      const moveInDate = new Date(moveInValue + "T00:00:00"); 
      leaseStartInput.value = moveInDate.toISOString().split('T')[0];
      
      let nextDay = new Date(moveInDate);
      nextDay.setDate(moveInDate.getDate() + 1);
      endDateInput.min = nextDay.toISOString().split('T')[0];
      if (endDateInput.value && new Date(endDateInput.value + "T00:00:00") <= moveInDate) {
        endDateInput.value = '';
      }
      updateDueDate();
    }
    
    function updateDueDate() {
      const leaseStartValue = document.getElementById('start_date').value;
      const dueDateInput = document.getElementById('payment_due_date');
      
      if (!leaseStartValue) {
        dueDateInput.value = '';
        return;
      }
      
      const leaseStartDate = new Date(leaseStartValue + "T00:00:00"); 
      let dayNumber = leaseStartDate.getDate();
      
      if (dayNumber > 28) {
        dayNumber = 28; 
      }
      
      let suffix;
      if (dayNumber % 10 === 1 && dayNumber % 100 !== 11) suffix = 'st';
      else if (dayNumber % 10 === 2 && dayNumber % 100 !== 12) suffix = 'nd';
      else if (dayNumber % 10 === 3 && dayNumber % 100 !== 13) suffix = 'rd';
      else suffix = 'th';
      
      dueDateInput.value = `Every ${dayNumber}${suffix} day of the month`;
    }
    
    function setupFormValidation() {
      const fullnameInput = document.getElementById('fullname');
      const fullnameError = document.getElementById('fullname-error');
      if (fullnameInput) {
        fullnameInput.addEventListener('input', function() { validateFullName(this, fullnameError); });
        fullnameInput.addEventListener('blur', function() { validateFullName(this, fullnameError); });
      }
      
      const contactInput = document.getElementById('contact_no');
      const contactError = document.getElementById('contact-error');
      if (contactInput) {
        contactInput.addEventListener('input', function() { validatePhoneNumber(this, contactError); });
        contactInput.addEventListener('blur', function() { validatePhoneNumber(this, contactError); });
      }
      
      const emailInput = document.getElementById('email');
      const emailError = document.getElementById('email-error');
      if (emailInput) {
        emailInput.addEventListener('input', function() { validateEmail(this, emailError); });
        emailInput.addEventListener('blur', function() { validateEmail(this, emailError); });
      }
    }
    
    function validateFullName(input, errorElement) {
      const value = input.value.trim();
      const words = value.split(/\s+/).filter(word => word.length > 0);
      const isValid = words.length >= 2 && words.every(word => word.length >= 1) && 
                    /^[A-Za-z\sÑñ.'-]+$/.test(value);
      
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
  </script>
</body>
</html>