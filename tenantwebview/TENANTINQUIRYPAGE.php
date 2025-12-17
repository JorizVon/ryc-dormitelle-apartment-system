<?php
session_start();

if (!isset($_SESSION['email_account'])) {
    header("Location: ../LOGIN.php"); 
    exit();
}

require_once '../db_connect.php';

// Handle flash messages from session
if (isset($_SESSION['form_success'])) {
    $form_success_message = $_SESSION['form_success'];
    unset($_SESSION['form_success']);
} else {
    $form_success_message = '';
}

// --- Dynamically create the full URL for the check_slots.php script ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['PHP_SELF']) . '/CHECK_SLOTS.php'; 
$checkSlotsUrl = $protocol . $host . $path;

$unit_no_from_url = null;
$unit_data = null;
$unit_images = [];
$page_error_message = '';
$form_error_message = '';

if (isset($_GET['unit_no'])) {
    $unit_no_from_url = $_GET['unit_no'];
    $sql_fetch_unit = "SELECT `unit_no`, `apartment_no`, `unit_address`, `unit_size`, `occupant_capacity`, `floor_level`, `unit_type`, `monthly_rent_amount`, `unit_status` FROM `units` WHERE `units`.`unit_no` = ?";
    $stmt_fetch_unit = $conn->prepare($sql_fetch_unit);
    if ($stmt_fetch_unit) {
        $stmt_fetch_unit->bind_param("s", $unit_no_from_url);
        $stmt_fetch_unit->execute();
        $result_unit = $stmt_fetch_unit->get_result();
        if ($result_unit->num_rows > 0) {
            $unit_data = $result_unit->fetch_assoc();
            if ($unit_data['unit_status'] !== 'Available') {
                $page_error_message = "This unit (" . htmlspecialchars($unit_data['unit_no']) . ") is currently not available for inquiry or reservation.";
                $unit_data = null;
            } else {
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
                }
            }
        } else {
            $page_error_message = "Unit details not found.";
        }
        $stmt_fetch_unit->close();
    }
} else {
    $page_error_message = "No unit selected.";
}

// --- FORM SUBMISSION HANDLING ---
// Handle Visit Inquiry Submission ONLY
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_visit_inquiry']) && $_POST['submit_visit_inquiry'] === 'confirmed') {
    if (!$unit_data) { 
        $form_error_message = "Cannot submit inquiry. The unit may have become unavailable."; 
    } else {
        $posted_unit_no = $_POST['unit_no_form'] ?? '';
        $full_name = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact_no = trim($_POST['contact_no'] ?? '');
        $visit_date = $_POST['visit_date'] ?? ''; 
        $visit_time = $_POST['visit_time_slot'] ?? '';
        $message = trim($_POST['message'] ?? ''); 

        $errors = [];
        if (empty($full_name)) $errors[] = "Full name is required.";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email address is required.";
        if (empty($contact_no)) $errors[] = "Contact number is required.";
        if (!empty($visit_date) && empty($visit_time)) $errors[] = "If you select a date, you must also select an available time slot.";

        if (empty($errors)) {
            $conn->begin_transaction();
            try {
                if (!empty($visit_date) && !empty($visit_time)) {
                    $sql_visit = "INSERT INTO `visitation_dates`(`email_account`, `visitation_date`, `visitation_time`, `unit_no`, `contact_no`, `request_type`) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt_visit = $conn->prepare($sql_visit);
                    $request_type = "Inquiry";
                    $stmt_visit->bind_param("ssssss", $email, $visit_date, $visit_time, $posted_unit_no, $contact_no, $request_type);
                    if (!$stmt_visit->execute()) { 
                        throw new Exception("The selected time slot was just booked. Please choose another one."); 
                    }
                    $stmt_visit->close();
                }

                $time_display = !empty($visit_time) ? ucfirst($visit_time) . ' Slot' : 'Not specified';
                $formatted_message = "--- NEW UNIT INQUIRY ---\n\n" . 
                                     "Unit No: " . $posted_unit_no . "\n" . 
                                     "From (Tenant): " . $full_name . "\n" . 
                                     "Email: " . $email . "\n" . 
                                     "Contact Number: " . $contact_no . "\n" . 
                                     "Unit Type: " . $unit_data['unit_type'] . "\n" . 
                                     "Requested Visit Date: " . (!empty($visit_date) ? $visit_date : 'Not specified') . "\n" . 
                                     "Requested Visit Time: " . $time_display . "\n\n" . 
                                     "Additional Questions:\n" . (!empty($message) ? $message : 'None');

                // Fetch admin email dynamically
                $sql_admin = "SELECT email_account FROM accounts WHERE user_type = 'admin' LIMIT 1";
                $result_admin = $conn->query($sql_admin);
                if ($result_admin && $result_admin->num_rows > 0) {
                    $admin_row = $result_admin->fetch_assoc();
                    $admin_recipient = $admin_row['email_account'];
                } else {
                    throw new Exception("Admin account not found.");
                }

                $sql_chat = "INSERT INTO `chat_box`(`email_account`, `message`, `recipient`, `sender_type`, `message_time_date`) VALUES (?, ?, ?, 'user', NOW())";
                $stmt_chat = $conn->prepare($sql_chat);
                $stmt_chat->bind_param("sss", $_SESSION['email_account'], $formatted_message, $admin_recipient);
                if (!$stmt_chat->execute()) throw new Exception("Failed to send message to admin.");
                $stmt_chat->close();
                
                $conn->commit();
                $_SESSION['form_success'] = "Your inquiry has been sent successfully! The landlord will get back to you shortly.";
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();

            } catch (Exception $e) {
                $conn->rollback();
                $form_error_message = $e->getMessage();
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

// Set page title for header
$page_title = "Unit Inquiry - RYC Dormitelle";

// Include header
include 'tenant_header.php';
?>

<style>
  /* ===========================
     INQUIRY PAGE-SPECIFIC STYLES
     =========================== */

  :root { 
    --primary-blue: #004AAD; 
    --text-dark: #333; 
    --text-light: #555; 
    --border-color: #dee2e6; 
    --available-green: #28a745; 
    --taken-red: #dc3545; 
  }

  .page-wrapper { 
    flex: 1; 
    margin-top: 92px; /* Adjust based on header height */
  }

  .unit-image-slider { 
    width: 100%; 
    max-width: 100vw; 
    margin: 0 auto; 
    position: relative; 
    overflow: hidden; 
    border: 1px solid var(--border-color); 
    height: 90vh; 
  }

  .slider-image-container { 
    display: flex; 
    transition: transform 0.5s ease-in-out; 
    height: 100%; 
  }

  .slider-image-container img { 
    width: 100%; 
    height: 100%; 
    object-fit: fill; 
    flex-shrink: 0; 
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

  .slider-btn.prev { left: 10px; }
  .slider-btn.next { right: 10px; }

  .main-content { 
    padding: 30px 20px; 
    max-width: 1200px; 
    margin: 0 auto; 
  }

  .unit-content { 
    display: flex; 
    flex-wrap: nowrap; 
    gap: 40px; 
    margin-top: 30px; 
  }

  .unit-details-section { 
    flex: 1; 
    min-width: 320px; 
  }

  .inquiry-form-section { 
    flex: 1.2; 
    border: 1px solid var(--border-color); 
    border-radius: 5px; 
    overflow: hidden; 
    max-width: 550px; 
  }

  .unit-id { 
    background-color: var(--primary-blue); 
    color: white; 
    padding: 10px 20px; 
    font-size: 24px; 
    font-weight: bold; 
    display: inline-block; 
    margin-bottom: 15px;
  }

  .unit-title { 
    font-size: 26px; 
    font-weight: 600; 
    color: var(--text-dark); 
    margin: 0; 
  }

  .unit-address { 
    font-size: 18px; 
    color: var(--text-light); 
    margin: 5px 0 25px 0; 
  }

  .section-heading { 
    font-size: 16px; 
    font-weight: bold; 
    color: var(--text-dark); 
    margin-top: 30px; 
    margin-bottom: 15px; 
    letter-spacing: 1px; 
    text-transform: uppercase;
  }

  .details-list { 
    list-style-type: none; 
    padding: 0; 
    margin: 0; 
  }

  .details-list li { 
    margin-bottom: 12px; 
    font-size: 16px; 
    display: flex; 
    align-items: center; 
  }

  .details-list li:before { 
    content: "•"; 
    color: var(--primary-blue); 
    font-weight: bold; 
    margin-right: 10px; 
    font-size: 20px; 
  }

  .form-tabs { display: flex; }

  .tab-link { 
    flex: 1; 
    padding: 15px; 
    background-color: #f8f9fa; 
    border: none; 
    border-bottom: 1px solid var(--border-color); 
    font-size: 16px; 
    cursor: pointer; 
    color: var(--text-light); 
    border-radius: 0; 
  }

  .tab-link.active { 
    background-color: white; 
    color: var(--primary-blue); 
    font-weight: bold; 
  }

  #inquiry-tab-btn { border-top-left-radius: 5px;}
  #reservation-tab-btn { border-top-right-radius: 5px;}

  .tab-content { display: none; padding: 25px; }
  .tab-content.active { display: block; }

  .form-intro { 
    font-size: 15px; 
    color: var(--text-light); 
    margin-bottom: 20px; 
  }

  .form-row { margin-bottom: 15px; }

  .form-row label { 
    display: block; 
    margin-bottom: 5px; 
    font-weight: normal; 
    color: var(--text-light); 
    font-size: 14px; 
  }

  .form-row input, .form-row textarea, .form-row select { 
    width: 100%; 
    padding: 10px; 
    border: 1px solid #ccc; 
    border-radius: 4px; 
    box-sizing: border-box; 
    font-size: 15px; 
  }

  .form-row input[readonly] { 
    background-color: #e9ecef; 
    cursor: not-allowed; 
  }

  .form-row textarea { resize: vertical; }

  #time-slot-container { display: none; }

  .calendar-container { 
    margin-top: 10px; 
    border: 1px solid var(--border-color); 
    border-radius: 4px; 
    padding: 10px; 
  }

  .calendar-header { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    padding: 5px 0 15px 0; 
  }

  .calendar-header span { 
    font-weight: bold; 
    font-size: 16px; 
  }

  .calendar-header .nav-btn { 
    background: none; 
    border: none; 
    font-size: 20px; 
    cursor: pointer; 
    padding: 0 10px; 
  }

  .calendar-weekdays, .calendar-days { 
    display: grid; 
    grid-template-columns: repeat(7, 1fr); 
    text-align: center; 
  }

  .calendar-weekdays div { 
    font-size: 12px; 
    color: var(--text-light); 
    font-weight: bold; 
    padding: 8px 0; 
  }

  .calendar-days .day { 
    padding: 8px 0; 
    cursor: pointer; 
    border-radius: 50%; 
    margin: 2px; 
    border: 2px solid transparent; 
  }

  .calendar-days .day.past { 
    color: #ccc; 
    cursor: not-allowed; 
    text-decoration: line-through; 
  }

  .calendar-days .day.selected { 
    background-color: var(--primary-blue); 
    color: white; 
    border-color: var(--primary-blue); 
  }

  .calendar-days .day:hover:not(.past):not(.selected) { 
    background-color: #e9ecef; 
  }

  .calendar-days .day.available-day { 
    border-color: var(--available-green); 
    font-weight: bold;
  }

  .calendar-days .day.full-day { 
    border-color: var(--taken-red); 
    color: var(--taken-red); 
    text-decoration: line-through; 
    cursor: not-allowed;
  }

  select option.slot-available { 
    color: var(--available-green); 
    font-weight: bold; 
  }

  select option.slot-taken { 
    color: var(--taken-red); 
    text-decoration: line-through; 
  }

  .important-reminder { 
    background-color: #fff3cd; 
    border: 1px solid #ffeeba; 
    border-radius: 4px; 
    padding: 15px; 
    margin-top: 20px; 
    font-size: 13px; 
  }

  .important-reminder strong { color: #856404; }

  .submit-btn { 
    background-color: var(--primary-blue); 
    color: white; 
    padding: 12px 20px; 
    font-size: 16px; 
    border: none; 
    border-radius: 4px; 
    cursor: pointer; 
    width: 100%; 
    margin-top: 20px; 
    transition: background-color 0.3s; 
    font-weight: bold; 
  }

  .submit-btn:hover { background-color: #01214B; }

  .alert-success { 
    color: #155724; 
    background-color: #d4edda; 
    border-color: #c3e6cb; 
  }

  .alert-danger { 
    color: #721c24; 
    background-color: #f8d7da; 
    border-color: #f5c6cb; 
  }

  .error-input { border-color: #d9534f !important; }

  .reservation-notice {
    text-align: center;
    padding: 40px 20px;
  }

  .reservation-notice h2 {
    font-size: 22px;
    color: var(--text-dark);
    margin-bottom: 15px;
  }

  .reservation-notice p {
    font-size: 15px;
    color: var(--text-light);
    margin-bottom: 25px;
    line-height: 1.6;
  }

  .reservation-notice .info-box {
    background-color: #f8f9fa;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
  }

  .reservation-notice .info-box strong {
    color: var(--primary-blue);
  }

  /* Mobile Responsive */
  @media screen and (max-width: 992px) { 
    .unit-content { flex-direction: column; } 
    .inquiry-form-section { max-width: 100%; } 
    .unit-image-slider { height: 40vh; } 
  }

  @media screen and (max-width: 768px) { 
    .page-wrapper { margin-top: 60px; }
    .unit-image-slider { height: 35vh; } 
  }

  @media screen and (max-width: 480px) {
    .unit-image-slider { height: 30vh; }
  }
</style>

<?php if (!empty($form_success_message)): ?>
<div class="alert alert-success alert-dismissible fade show" style="position: fixed; top: 100px; left: 50%; transform: translateX(-50%); z-index: 1050; max-width: 500px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);" role="alert">
  <strong>Success!</strong><br>
  <?php echo $form_success_message; ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<script>
  setTimeout(function() {
    const alert = document.querySelector('.alert-success');
    if (alert) {
      const bsAlert = new bootstrap.Alert(alert);
      bsAlert.close();
    }
  }, 8000);
</script>
<?php endif; ?>

<div class="page-wrapper">
  <?php if ($unit_data): ?>
    <div class="unit-image-slider" id="unitImageSlider">
      <?php if (!empty($unit_images)): ?>
        <div class="slider-image-container" id="sliderImageContainer">
          <?php foreach ($unit_images as $image_filename): ?>
            <img src="../unitImages/<?php echo htmlspecialchars($image_filename); ?>" alt="Image for unit <?php echo htmlspecialchars($unit_data['unit_no']); ?>">
          <?php endforeach; ?>
        </div>
        <?php if (count($unit_images) > 1): ?>
          <button class="slider-btn prev" onclick="changeSlide(-1)">❮</button>
          <button class="slider-btn next" onclick="changeSlide(1)">❯</button>
        <?php endif; ?>
      <?php else: ?>
        <div style="display:flex; align-items:center; justify-content:center; height:100%; background-color:#f0f0f0;"><p>No images available for this unit.</p></div>
      <?php endif; ?>
    </div>

    <div class="main-content">
      <div class="unit-id" data-unit-no="<?php echo htmlspecialchars($unit_data['unit_no']); ?>"><?php echo htmlspecialchars($unit_data['unit_no']); ?></div>
      <div class="unit-content">
        <div class="unit-details-section">
          <h1 class="unit-title"><?php echo htmlspecialchars($unit_data['unit_type']); ?> Unit accommodating up to <?php echo htmlspecialchars($unit_data['occupant_capacity']); ?> persons</h1>
          <p class="unit-address"><?php echo htmlspecialchars($unit_data['unit_address']); ?></p>
          <h3 class="section-heading">UNIT DETAILS</h3>
          <ul class="details-list">
            <li>Unit Size: <?php echo htmlspecialchars($unit_data['unit_size']); ?> Sqm.</li>
            <li>Floor Level Type: <?php echo htmlspecialchars($unit_data['floor_level']); ?></li>
            <li>Capacity: <?php echo htmlspecialchars($unit_data['occupant_capacity']); ?> Persons</li>
            <li>Type: <?php echo htmlspecialchars($unit_data['unit_type']); ?></li>
            <li>Monthly Rent Amount: ₱<?php echo number_format($unit_data['monthly_rent_amount'], 2); ?></li>
          </ul>
          <h3 class="section-heading">PAYMENT TERMS</h3>
          <ul class="details-list">
            <li>Advance Payments: ₱<?php echo number_format($unit_data['monthly_rent_amount'], 2); ?> (1 month)</li>
            <li>Security Deposit: ₱<?php echo number_format($unit_data['monthly_rent_amount'] * 2, 2); ?> (2 MONTHS)</li>
          </ul>
        </div>
        
        <div class="inquiry-form-section">
          <div class="form-tabs">
            <button class="tab-link active" id="inquiry-tab-btn">Inquiry Form</button>
            <button class="tab-link" id="reservation-tab-btn">Reservation Form</button>
          </div>

          <div class="tab-content active" id="inquiry-form-content">
            <?php if(!empty($form_success_message)): ?>
              <div class="alert alert-success"><?php echo $form_success_message; ?></div>
            <?php endif; ?>
            <?php if (isset($_POST['submit_visit_inquiry']) && !empty($form_error_message)): ?>
              <div class="alert alert-danger"><?php echo $form_error_message; ?></div>
            <?php endif; ?>
            
            <p class="form-intro">Let us know how we can assist you or if you'd like to see the unit in person.</p>
            <form id="visitInquiryForm" method="POST" action="TENANTINQUIRYPAGE.php?unit_no=<?php echo htmlspecialchars($unit_no_from_url); ?>" novalidate>
              <input type="hidden" name="unit_no_form" value="<?php echo htmlspecialchars($unit_data['unit_no']); ?>">
              <div class="form-row"><label for="fullname">Full Name</label><input type="text" id="fullname" name="fullname" required></div>
              <div class="form-row"><label for="email">Email Address</label><input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email_account']); ?>" readonly></div>
              <div class="form-row"><label for="contact_no">Contact Number</label><input type="tel" id="contact_no" name="contact_no" required placeholder="+639123456789"></div>
              <div class="form-row"><label for="unit_type_display">Unit Type</label><input type="text" id="unit_type_display" value="<?php echo htmlspecialchars($unit_data['unit_type']); ?>" readonly></div>
              <div class="form-row"><label for="visit_date">Preferred Date to Visit (Optional)</label><div class="calendar-container" id="inquiry-calendar-container"><div class="calendar-header"><button type="button" class="nav-btn prev-btn"><</button><span></span><button type="button" class="nav-btn next-btn">></button></div><div class="calendar-weekdays"><div>MON</div><div>TUE</div><div>WED</div><div>THU</div><div>FRI</div><div>SAT</div><div>SUN</div></div><div class="calendar-days"></div></div><input type="hidden" id="visit_date" name="visit_date"></div>
              <div class="form-row" id="time-slot-container"><label for="visit_time_slot">Preferred Time Slot</label><select name="visit_time_slot" id="visit_time_slot" required><option value="">-- Select an available time --</option><option value="morning">Morning (8:00 AM - 11:00 AM)</option><option value="afternoon">Afternoon (1:00 PM - 5:00 PM)</option></select></div>
              <div class="form-row"><label for="message">Questions or Message</label><textarea id="message" name="message" rows="4" placeholder="Is Unit <?php echo htmlspecialchars($unit_data['unit_no']); ?> still available?"></textarea></div>
              <button type="button" class="submit-btn" data-form-id="visitInquiryForm">Send Inquiry</button>
            </form>
          </div>

          <div class="tab-content" id="reservation-form-content">
            <div class="reservation-notice">
              <h2>Ready to Reserve This Unit?</h2>
              <p>To proceed with a unit reservation, you'll need to create a new account dedicated to this reservation process.</p>
              
              <div class="info-box">
                <p><strong>Why a new account?</strong></p>
                <p>Each tenant account is linked to a single unit reservation to ensure proper tracking and management of your lease agreement.</p>
              </div>

              <button type="button" class="submit-btn" id="showReservationModalBtn">Continue to Reservation</button>
              
              <p style="margin-top: 20px; font-size: 13px; color: #6c757d;">
                Already have a reservation account? <a href="../LOGIN.php" style="color: var(--primary-blue);">Log in here</a>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Inquiry Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmModalLabel"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="confirmModalBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="confirmSubmitBtn">Confirm & Submit</button>
      </div>
    </div>
  </div>
</div>

<!-- Reservation Account Modal -->
<div class="modal fade" id="reservationAccountModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background-color: var(--primary-blue); color: white;">
        <h5 class="modal-title">Create New Account to Continue</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding: 30px;">
        <div style="text-align: center; margin-bottom: 20px;">
          <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--primary-blue)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        </div>
        <h6 style="font-size: 18px; font-weight: bold; color: var(--text-dark); margin-bottom: 15px; text-align: center;">
          Create a New Account for Your Reservation
        </h6>
        <p style="font-size: 15px; color: var(--text-light); line-height: 1.6; margin-bottom: 20px;">
          To submit a reservation application, please log out and create a new account. This ensures your reservation details are properly managed and tracked throughout the entire leasing process.
        </p>
        <div style="background-color: #f8f9fa; border-left: 4px solid var(--primary-blue); padding: 15px; margin-bottom: 20px; border-radius: 4px;">
          <p style="margin: 0; font-size: 14px; color: var(--text-dark);">
            <strong>Note:</strong> Your current account cannot be used for multiple unit reservations. Each reservation requires a dedicated account.
          </p>
        </div>
        <p style="font-size: 14px; color: var(--text-light); margin-bottom: 10px;">
          <strong>Steps to continue:</strong>
        </p>
        <ol style="font-size: 14px; color: var(--text-light); line-height: 1.8; padding-left: 20px;">
          <li>Log out from your current account</li>
          <li>Click on "Sign Up" to create a new account</li>
          <li>Return to this unit page</li>
          <li>Complete the reservation form</li>
        </ol>
      </div>
      <div class="modal-footer" style="justify-content: center; gap: 10px;">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="padding: 10px 25px;">Cancel</button>
        <a href="../LOGOUT.php" class="btn btn-primary" style="background-color: var(--primary-blue); border: none; padding: 10px 25px;">Log Out & Create New Account</a>
      </div>
    </div>
  </div>
</div>
    
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const CHECK_SLOTS_URL = '<?php echo $checkSlotsUrl; ?>';

    function toggleMenu() {
        document.getElementById('navbar').classList.toggle('show');
    }

    let currentSlideIndex = 0;
    const slidesContainer = document.getElementById('sliderImageContainer');
    const totalSlides = slidesContainer ? slidesContainer.children.length : 0;
    function showSlide(index) {
        if (!slidesContainer || totalSlides === 0) return;
        const slideWidth = slidesContainer.children[0].clientWidth;
        slidesContainer.style.transform = `translateX(-${index * slideWidth}px)`;
    }
    function changeSlide(n) {
        currentSlideIndex = (currentSlideIndex + n + totalSlides) % totalSlides;
        showSlide(currentSlideIndex);
    }
    if (totalSlides > 0) { showSlide(currentSlideIndex); }
    
    document.addEventListener('DOMContentLoaded', function() {
        const unitNo = document.querySelector('.unit-id')?.dataset.unitNo;
        new Calendar('inquiry-calendar-container', unitNo, true);

        // Tab switching functionality
        const inquiryTab = document.getElementById('inquiry-tab-btn');
        const reservationTab = document.getElementById('reservation-tab-btn');
        const inquiryContent = document.getElementById('inquiry-form-content');
        const reservationContent = document.getElementById('reservation-form-content');
        
        inquiryTab.addEventListener('click', () => {
            inquiryTab.classList.add('active'); 
            reservationTab.classList.remove('active');
            inquiryContent.classList.add('active'); 
            reservationContent.classList.remove('active');
        });
        
        reservationTab.addEventListener('click', () => {
            reservationTab.classList.add('active'); 
            inquiryTab.classList.remove('active');
            reservationContent.classList.add('active'); 
            inquiryContent.classList.remove('active');
        });

        // Show reservation account modal
        const showReservationModalBtn = document.getElementById('showReservationModalBtn');
        const reservationAccountModal = new bootstrap.Modal(document.getElementById('reservationAccountModal'));
        
        if (showReservationModalBtn) {
            showReservationModalBtn.addEventListener('click', () => {
                reservationAccountModal.show();
            });
        }

        // Inquiry form confirmation modal
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        const confirmModalLabel = document.getElementById('confirmModalLabel');
        const confirmModalBody = document.getElementById('confirmModalBody');
        const confirmSubmitBtn = document.getElementById('confirmSubmitBtn');
        let formToSubmit = null;
        
        document.querySelectorAll('.submit-btn[data-form-id]').forEach(button => {
            button.addEventListener('click', (e) => {
                const formId = e.target.dataset.formId;
                formToSubmit = document.getElementById(formId);
                let isValid = false, modalTitle = '', modalBody = '';
                
                if (formId === 'visitInquiryForm') {
                    isValid = validateInquiryForm();
                    modalTitle = 'Confirm Inquiry';
                    modalBody = 'Are you sure you want to submit this visit inquiry?';
                }
                
                if (isValid) {
                    confirmModalLabel.textContent = modalTitle;
                    confirmModalBody.textContent = modalBody;
                    confirmModal.show();
                } else {
                    alert('Please fill in all required fields and correct any errors.');
                }
            });
        });
        
        confirmSubmitBtn.addEventListener('click', () => {
            if (formToSubmit) {
                formToSubmit.querySelectorAll('input[type="hidden"][name^="submit_"]').forEach(input => input.remove());
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.value = 'confirmed';
                if (formToSubmit.id === 'visitInquiryForm') hiddenInput.name = 'submit_visit_inquiry';
                formToSubmit.appendChild(hiddenInput);
                confirmModal.hide();
                formToSubmit.submit();
            }
        });
    });

    class Calendar {
        constructor(containerId, unitNo, checkAvailability = false) {
            this.container = document.getElementById(containerId);
            if (!this.container) return;
            this.unitNo = unitNo; 
            this.checkAvailability = checkAvailability;
            this.today = new Date(); 
            this.currentYear = this.today.getFullYear(); 
            this.currentMonth = this.today.getMonth() + 1;
            this.availabilityData = {};
            this.dateInput = (containerId.includes('inquiry')) ? document.getElementById('visit_date') : null;
            this.header = this.container.querySelector('.calendar-header span');
            this.daysGrid = this.container.querySelector('.calendar-days');
            this.container.querySelector('.prev-btn').addEventListener('click', () => this.changeMonth(-1));
            this.container.querySelector('.next-btn').addEventListener('click', () => this.changeMonth(1));
            this.loadDataAndRender();
        }
        
        changeMonth(direction) {
            if (direction === -1) {
                const now = new Date();
                if (this.currentYear === now.getFullYear() && this.currentMonth === now.getMonth() + 1) return;
                this.currentMonth--; 
                if (this.currentMonth < 1) { 
                    this.currentMonth = 12; 
                    this.currentYear--; 
                }
            } else { 
                this.currentMonth++; 
                if (this.currentMonth > 12) { 
                    this.currentMonth = 1; 
                    this.currentYear++; 
                } 
            }
            this.loadDataAndRender();
        }
        
        async loadDataAndRender() {
            this.daysGrid.innerHTML = '<p>Loading schedule...</p>';
            if (this.checkAvailability && this.unitNo) {
                try {
                    const url = new URL(CHECK_SLOTS_URL);
                    url.searchParams.append('year', this.currentYear); 
                    url.searchParams.append('month', this.currentMonth); 
                    url.searchParams.append('unit_no', this.unitNo);
                    const response = await fetch(url.toString());
                    if (!response.ok) throw new Error(`HTTP error ${response.status}`);
                    this.availabilityData = await response.json();
                    if (this.availabilityData.error) throw new Error(this.availabilityData.error);
                } catch (error) { 
                    this.daysGrid.innerHTML = '<p style="color:red;">Could not load schedule.</p>'; 
                    return; 
                }
            }
            this.render();
        }
        
        render() {
            this.daysGrid.innerHTML = '';
            const date = new Date(this.currentYear, this.currentMonth - 1, 1);
            this.header.textContent = `${date.toLocaleString('default', { month: 'long' })} ${this.currentYear}`;
            const daysInMonth = new Date(this.currentYear, this.currentMonth, 0).getDate();
            let firstDayOfWeek = (date.getDay() === 0) ? 6 : date.getDay() - 1;
            
            for (let i = 0; i < firstDayOfWeek; i++) { 
                this.daysGrid.appendChild(document.createElement('div')); 
            }
            
            const todayForComparison = new Date(); 
            todayForComparison.setHours(0, 0, 0, 0);
            
            for (let i = 1; i <= daysInMonth; i++) {
                const dayDiv = document.createElement('div'); 
                dayDiv.textContent = i; 
                dayDiv.classList.add('day');
                const dayDate = new Date(this.currentYear, this.currentMonth - 1, i);
                const fullDateString = `${this.currentYear}-${String(this.currentMonth).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                
                if (this.dateInput && this.dateInput.value === fullDateString) dayDiv.classList.add('selected');
                
                if (dayDate < todayForComparison) { 
                    dayDiv.classList.add('past');
                } else {
                    const dayData = this.availabilityData[fullDateString]; 
                    let isFull = this.checkAvailability && dayData && dayData.morning && dayData.afternoon;
                    
                    if(isFull) {
                        dayDiv.classList.add('full-day');
                    } else if (this.checkAvailability && dayData) {
                        dayDiv.classList.add('available-day');
                    }
                    
                    if (!isFull) {
                        dayDiv.addEventListener('click', () => {
                            this.container.querySelector('.day.selected')?.classList.remove('selected'); 
                            dayDiv.classList.add('selected');
                            if (this.dateInput) {
                                this.dateInput.value = fullDateString;
                                if (this.checkAvailability) updateAvailableTimeSlots(this.availabilityData[fullDateString]);
                            }
                        });
                    }
                }
                this.daysGrid.appendChild(dayDiv);
            }
        }
    }
    
    function updateAvailableTimeSlots(dayData) {
        const timeSlotContainer = document.getElementById('time-slot-container');
        const timeSlotSelect = document.getElementById('visit_time_slot');
        const morningOption = timeSlotSelect.querySelector('option[value="morning"]');
        const afternoonOption = timeSlotSelect.querySelector('option[value="afternoon"]');
        
        timeSlotContainer.style.display = 'block'; 
        timeSlotSelect.value = '';
        [morningOption, afternoonOption].forEach(opt => { 
            opt.disabled = false; 
            opt.classList.remove('slot-available', 'slot-taken'); 
        });
        
        if (dayData && dayData.morning) { 
            morningOption.disabled = true; 
            morningOption.classList.add('slot-taken'); 
        } else { 
            morningOption.classList.add('slot-available'); 
        }
        
        if (dayData && dayData.afternoon) { 
            afternoonOption.disabled = true; 
            afternoonOption.classList.add('slot-taken'); 
        } else { 
            afternoonOption.classList.add('slot-available'); 
        }
    }
    
    function validateInquiryForm() {
        let isValid = true;
        ['fullname', 'email', 'contact_no'].forEach(id => {
            const input = document.getElementById(id); 
            input.classList.remove('error-input');
            if (!input.value.trim()) { 
                input.classList.add('error-input'); 
                isValid = false; 
            }
        });
        
        const dateInput = document.getElementById('visit_date');
        const timeInput = document.getElementById('visit_time_slot');
        timeInput.classList.remove('error-input');
        
        if(dateInput.value && !timeInput.value){ 
            timeInput.classList.add('error-input'); 
            isValid = false; 
        }
        
        return isValid;
    }
</script>

<?php
// Include footer
include 'footer.php';
?>