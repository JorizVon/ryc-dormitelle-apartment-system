<?php
session_start();

if (!isset($_SESSION['email_account'])) {
    header("Location: ../LOGIN.php"); 
    exit();
}

require_once '../db_connect.php';
date_default_timezone_set('Asia/Manila');

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

// --- HELPER FUNCTION ---
// Validate Name: At least 5 chars, Letters, Numbers, Spaces, Max 2 Periods
// MUST CONTAIN AT LEAST ONE LETTER
function validateName($name) {
    // Check length (At least 5)
    if (strlen($name) < 5) return false;
    
    // Regex: Only allow Letters (a-z), Numbers (0-9), Spaces (\s), and Dots (.)
    if (!preg_match('/^[a-zA-Z0-9\s.]+$/', $name)) return false;
    
    // Check dot count: Max 2 periods allowed
    if (substr_count($name, '.') > 2) return false;
    
    // NEW CHECK: Must contain at least one letter. Cannot be numbers only.
    if (!preg_match('/[a-zA-Z]/', $name)) return false;
    
    return true;
}

// --- FORM SUBMISSION HANDLING ---
// 1. Handle Visit Inquiry Submission
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
        // Name Validation
        if (empty($full_name) || !validateName($full_name)) {
            $errors[] = "Name must be at least 5 characters, contain at least one letter, and avoid special characters other than periods (max 2).";
        }
        // Phone Validation
        if (empty($contact_no) || strlen($contact_no) !== 13) {
            $errors[] = "Contact number must be exactly 10 digits.";
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email address is required.";
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

// 2. Handle Reservation Application Submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['request_type']) && $_POST['request_type'] === 'reservation' && isset($_POST['submit_reservation']) && $_POST['submit_reservation'] === 'confirmed') {
    if (!$unit_data) { 
        $form_error_message = "Cannot submit reservation. The unit may have become unavailable."; 
    } else {
        $request_type       = $_POST['request_type'];
        $role               = $_POST['role'];
        $reservation_date   = date("Y-m-d"); 
        $unit_no            = $_POST['unit_no_reserve_form'];
        $full_name          = trim($_POST['fullname_reserve']);
        $contact_no         = trim($_POST['contact_no_reserve']);
        $email_account      = $_SESSION['email_account'];
        $pref_move_date     = $_POST['move_in_date'];
        $permanent_address  = trim($_POST['permanent_address']);
        $ec_person          = trim($_POST['ec_person']);
        $ec_no              = trim($_POST['ec_no']);
        $confirmation_status = "pending"; 

        $errors = [];
        
        // Validations
        if (empty($full_name) || !validateName($full_name)) $errors[] = "Full name must be at least 5 characters, contain at least one letter, and avoid special characters other than periods (max 2).";
        if (empty($contact_no) || strlen($contact_no) !== 13) $errors[] = "Contact number must be exactly 10 digits.";
        
        if (empty($pref_move_date)) $errors[] = "A preferred move-in date is required.";
        if (empty($permanent_address)) $errors[] = "Permanent address is required.";
        
        if (empty($ec_person) || !validateName($ec_person)) $errors[] = "Emergency contact name must be at least 5 characters, contain at least one letter, and avoid special characters other than periods (max 2).";
        if (empty($ec_no) || strlen($ec_no) !== 13) $errors[] = "Emergency contact number must be exactly 10 digits.";
        
        $sql_check_status = "SELECT unit_status FROM units WHERE unit_no = ?";
        $stmt_check = $conn->prepare($sql_check_status);
        $stmt_check->bind_param("s", $unit_no);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            $current_status = $result_check->fetch_assoc()['unit_status'];
            if ($current_status !== 'Available') { 
                $errors[] = "We're sorry, but this unit was just reserved by someone else."; 
            }
        } else {
            $errors[] = "Unit not found.";
        }
        $stmt_check->close();

        if (empty($errors)) {
            $conn->begin_transaction();
            try {
                $current_date = date('Ymd');
                $clean_unit_no = preg_replace('/[^a-zA-Z0-9]/', '', $unit_no);
                $representative_tenant_id = $current_date . $clean_unit_no . '01';

                $sql = "INSERT INTO `pending_reservation`
                    (`request_type`, `role`, `reservation_date`, `unit_no`, `full_name`, `contact_no`, `email_account`, `pref_move_date`, `permanent_address`, `ec_person`, `ec_no`, `confirmation_status`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "ssssssssssss",
                    $request_type, $role, $reservation_date, $unit_no, $full_name,
                    $contact_no, $email_account, $pref_move_date, $permanent_address,
                    $ec_person, $ec_no, $confirmation_status
                );

                if (!$stmt->execute()) {
                    throw new Exception("Failed to insert main applicant: " . $stmt->error);
                }
                $stmt->close();

                $occupant_count = 1; 
                if (isset($_POST['companion_fullname']) && is_array($_POST['companion_fullname'])) {
                    foreach ($_POST['companion_fullname'] as $comp_name) {
                        if (!empty($comp_name)) {
                            $occupant_count++;
                        }
                    }
                }

                $monthly_rate = $unit_data['monthly_rent_amount'];
                $security_deposit = $unit_data['monthly_rent_amount'] * 2;
                $contract_status = 'pending';
                
                $contract_full_name = $full_name;
                $contract_postal_address = !empty($_POST['postal_address']) ? trim($_POST['postal_address']) : $permanent_address;
                $contract_citizenship = !empty($_POST['citizenship']) ? $_POST['citizenship'] : 'Filipino';
                $contract_term = !empty($_POST['contract_term']) ? $_POST['contract_term'] : '6 MONTHS';
                $contract_start_date = $pref_move_date;
                $contract_end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : date("Y-m-d", strtotime($pref_move_date . " +6 MONTHS"));

                $contract_sql = "INSERT INTO `contract_information`
                    (`contract_date`, `email_account`, `full_name`, `citizenship`, `postal_address`, `contract_term`, `start_date`, `end_date`, `monthly_rate`, `security_deposit`, `contract_status`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $contract_stmt = $conn->prepare($contract_sql);
                $contract_stmt->bind_param(
                    "sssssssssss",
                    $reservation_date, $email_account, $contract_full_name, $contract_citizenship, $contract_postal_address,
                    $contract_term, $contract_start_date, $contract_end_date, $monthly_rate,
                    $security_deposit, $contract_status
                );

                if (!$contract_stmt->execute()) {
                    throw new Exception("Failed to insert contract information: " . $contract_stmt->error);
                }
                $contract_stmt->close();

                $start_date_obj = new DateTime($pref_move_date);
                $actual_start_date = $start_date_obj->format('Y-m-d');
                $end_date = $contract_end_date;

                $start_day = $start_date_obj->format('j');
                $payment_due = "Every {$start_day}" . ($start_day == 1 ? "st" : ($start_day == 2 ? "nd" : ($start_day == 3 ? "rd" : "th"))) . " Day of the Month";

                $billing_end_day_obj = clone $start_date_obj;
                $billing_end_day_obj->modify('+3 days');
                $billing_end_day = $billing_end_day_obj->format('j');
                $billing_period = "Until {$billing_end_day}" . ($billing_end_day == 1 ? "st" : ($billing_end_day == 2 ? "nd" : ($billing_end_day == 3 ? "rd" : "th"))) . " day of the month";

                $insert_tenant_unit_sql = "INSERT INTO `tenant_unit`(`tenant_ID`, `unit_no`, `start_date`, `end_date`, `occupant_count`, `security_deposit`, `balance`, `payment_due`, `billing_period`, `status`)
                                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
                $insert_tenant_unit_stmt = $conn->prepare($insert_tenant_unit_sql);

                if ($insert_tenant_unit_stmt) {
                    $balance_amount = 0.0;
                    $security_deposit_amount = (float)$security_deposit;

                    $insert_tenant_unit_stmt->bind_param("ssssidsss",
                        $representative_tenant_id,
                        $unit_no,
                        $actual_start_date,
                        $end_date,
                        $occupant_count,
                        $security_deposit_amount,
                        $balance_amount,
                        $payment_due,
                        $billing_period
                    );

                    if (!$insert_tenant_unit_stmt->execute()) {
                        throw new Exception("Failed to insert tenant_unit: " . $insert_tenant_unit_stmt->error);
                    }
                    $insert_tenant_unit_stmt->close();
                } else {
                    throw new Exception("Failed to prepare tenant_unit insert: " . $conn->error);
                }

                if (isset($_POST['companion_fullname']) && is_array($_POST['companion_fullname'])) {
                    $companion_fullnames = $_POST['companion_fullname'];
                    $companion_contacts = $_POST['companion_contact'] ?? [];
                    $companion_addresses = $_POST['companion_address'] ?? [];
                    $companion_ec_persons = $_POST['companion_ec_person'] ?? [];
                    $companion_ec_nos = $_POST['companion_ec_no'] ?? [];
                    $companion_roles = $_POST['companion_role'] ?? [];

                    $companion_sql = "INSERT INTO `pending_reservation`
                        (`request_type`, `role`, `reservation_date`, `unit_no`, `full_name`, `contact_no`, `email_account`, `pref_move_date`, `permanent_address`, `ec_person`, `ec_no`, `confirmation_status`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                    $companion_stmt = $conn->prepare($companion_sql);

                    for ($i = 0; $i < count($companion_fullnames); $i++) {
                        if (!empty($companion_fullnames[$i])) {
                            $comp_role = $companion_roles[$i] ?? 'companion';
                            $comp_contact = $companion_contacts[$i] ?? '';
                            $comp_address = $companion_addresses[$i] ?? '';
                            $comp_ec_person = $companion_ec_persons[$i] ?? '';
                            $comp_ec_no = $companion_ec_nos[$i] ?? '';
                            
                            $companion_stmt->bind_param(
                                "ssssssssssss",
                                $request_type, $comp_role, $reservation_date, $unit_no,
                                $companion_fullnames[$i], $comp_contact, $email_account,
                                $pref_move_date, $comp_address, $comp_ec_person,
                                $comp_ec_no, $confirmation_status
                            );

                            if (!$companion_stmt->execute()) {
                                throw new Exception("Failed to insert companion: " . $companion_stmt->error);
                            }
                        }
                    }
                    $companion_stmt->close();
                }

                $update_unit_sql = "UPDATE `units` SET `unit_status` = 'pending' WHERE `unit_no` = ?";
                $update_unit_stmt = $conn->prepare($update_unit_sql);
                $update_unit_stmt->bind_param("s", $unit_no);
                if (!$update_unit_stmt->execute()) {
                    throw new Exception("Failed to update unit status: " . $update_unit_stmt->error);
                }
                $update_unit_stmt->close();

                $reservation_message = "--- NEW UNIT RESERVATION ---\n\n" .
                                       "Unit No: " . $unit_no . "\n" .
                                       "From (Tenant): " . $full_name . "\n" .
                                       "Email: " . $email_account . "\n" .
                                       "Contact Number: " . $contact_no . "\n" .
                                       "Unit Type: " . $unit_data['unit_type'] . "\n" .
                                       "Preferred Move-in Date: " . $pref_move_date . "\n" .
                                       "Permanent Address: " . $permanent_address . "\n" .
                                       "Citizenship: " . $contract_citizenship . "\n" .
                                       "Contract Term: " . $contract_term . "\n" .
                                       "Monthly Rate: ₱" . number_format($monthly_rate, 2) . "\n" .
                                       "Security Deposit: ₱" . number_format($security_deposit, 2) . "\n" .
                                       "Emergency Contact: " . $ec_person . " (" . $ec_no . ")\n\n" .
                                       "Additional Information:\n" . (!empty($_POST['message_reserve']) ? $_POST['message_reserve'] : 'None');

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
                $stmt_chat->bind_param("sss", $_SESSION['email_account'], $reservation_message, $admin_recipient);
                if (!$stmt_chat->execute()) throw new Exception("Failed to send reservation notification to admin.");
                $stmt_chat->close();

                $conn->commit();
                
                $_SESSION['reservation_success'] = "Reservation submitted successfully! We will contact you shortly. Unit " . htmlspecialchars($unit_no) . " is now marked as Pending.";
                header("Location: USERHOMEPAGE.php");
                exit();

            } catch (Exception $e) {
                $conn->rollback();
                $form_error_message = "Database error: " . $e->getMessage();
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

$page_title = "Unit Inquiry - RYC Dormitelle";
include 'user_header.php';
?>

<style>
  :root { 
    --primary-blue: #004AAD; 
    --dark-blue: #01214B; 
    --light-blue: #79B1FC; 
    --text-dark: #333; 
    --text-light: #555; 
    --border-color: #dee2e6; 
    --available-green: #28a745; 
    --taken-red: #dc3545; 
  }

  .capitalize-text { text-transform: capitalize; }
  .page-wrapper { flex: 1; margin-top: 80px; }
  .unit-image-slider { width: 100%; max-width: 100vw; margin: 0 auto; position: relative; overflow: hidden; border: 1px solid var(--border-color); height:100vh; }
  .slider-image-container { display: flex; transition: transform 0.5s ease-in-out; height: 100%; }
  .slider-image-container img { width: 100%; height: 100%; object-fit: cover; flex-shrink: 0; }
  .slider-btn { position: absolute; top: 50%; transform: translateY(-50%); background-color: rgba(0, 0, 0, 0.5); color: white; border: none; padding: 10px 15px; font-size: 20px; cursor: pointer; z-index: 10; }
  .slider-btn.prev { left: 10px; }
  .slider-btn.next { right: 10px; }
  .main-content { padding: 30px 20px; max-width: 1200px; margin: 0 auto; }
  .unit-content { display: flex; flex-wrap: nowrap; gap: 40px; margin-top: 30px; }
  .unit-details-section { flex: 1; min-width: 320px; }
  .inquiry-form-section { flex: 1.2; border: 1px solid var(--border-color); border-radius: 5px; overflow: hidden; max-width: 550px; }
  .unit-id { background-color: var(--primary-blue); color: white; padding: 10px 20px; font-size: 24px; font-weight: bold; display: inline-block; margin-bottom: 15px; }
  .unit-title { font-size: 26px; font-weight: 600; color: var(--text-dark); margin: 0; }
  .unit-address { font-size: 18px; color: var(--text-light); margin: 5px 0 25px 0; }
  .section-heading { font-size: 16px; font-weight: bold; color: var(--text-dark); margin-top: 30px; margin-bottom: 15px; letter-spacing: 1px; text-transform: uppercase; }
  .details-list { list-style-type: none; padding: 0; margin: 0; }
  .details-list li { margin-bottom: 12px; font-size: 16px; display: flex; align-items: center; }
  .details-list li:before { content: "•"; color: var(--primary-blue); font-weight: bold; margin-right: 10px; font-size: 20px; }
  .form-tabs { display: flex; }
  .tab-link { flex: 1; padding: 15px; background-color: #f8f9fa; border: none; border-bottom: 1px solid var(--border-color); font-size: 16px; cursor: pointer; color: var(--text-light); border-radius: 0; }
  .tab-link.active { background-color: white; color: var(--primary-blue); font-weight: bold; }
  #inquiry-tab-btn { border-top-left-radius: 5px;}
  #reservation-tab-btn { border-top-right-radius: 5px;}
  .tab-content { display: none; padding: 25px; }
  .tab-content.active { display: block; }
  .form-intro { font-size: 15px; color: var(--text-light); margin-bottom: 20px; }
  .form-row { margin-bottom: 15px; }
  .form-row label { display: block; margin-bottom: 5px; font-weight: normal; color: var(--text-light); font-size: 14px; }
  .form-row input, .form-row textarea, .form-row select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 15px; }
  .form-row input[readonly] { background-color: #e9ecef; cursor: not-allowed; }
  .form-row textarea { resize: vertical; }
  #time-slot-container { display: none; }
  .calendar-container { margin-top: 10px; border: 1px solid var(--border-color); border-radius: 4px; padding: 10px; }
  .calendar-header { display: flex; justify-content: space-between; align-items: center; padding: 5px 0 15px 0; }
  .calendar-header span { font-weight: bold; font-size: 16px; }
  .calendar-header .nav-btn { background: none; border: none; font-size: 20px; cursor: pointer; padding: 0 10px; }
  .calendar-weekdays, .calendar-days { display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; }
  .calendar-weekdays div { font-size: 12px; color: var(--text-light); font-weight: bold; padding: 8px 0; }
  .calendar-days .day { padding: 8px 0; cursor: pointer; border-radius: 50%; margin: 2px; border: 2px solid transparent; }
  .calendar-days .day:hover:not(.past):not(.selected) { background-color: #e9ecef; }
  .calendar-days .day.past { color: #ccc; cursor: not-allowed; text-decoration: line-through; }
  .calendar-days .day.selected { background-color: var(--primary-blue); color: white; border-color: var(--primary-blue); }
  .calendar-days .day.available-day { border-color: var(--available-green); font-weight: bold;}
  .calendar-days .day.full-day { border-color: var(--taken-red); color: var(--taken-red); text-decoration: line-through; cursor: not-allowed;}
  select option.slot-available { color: var(--available-green); font-weight: bold; }
  select option.slot-taken { color: var(--taken-red); text-decoration: line-through; }
  .important-reminder { background-color: #fff3cd; border: 1px solid #ffeeba; border-radius: 4px; padding: 15px; margin-top: 20px; font-size: 13px; }
  .important-reminder strong { color: #856404; }
  .submit-btn { background-color: var(--primary-blue); color: white; padding: 12px 20px; font-size: 16px; border: none; border-radius: 4px; cursor: pointer; width: 100%; margin-top: 20px; transition: background-color 0.3s; font-weight: bold; }
  .submit-btn:hover { background-color: var(--dark-blue); }
  .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
  .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
  .error-input { border-color: #d9534f !important; }
  .companion-form { border: 2px solid #e9ecef; border-radius: 8px; padding: 20px; margin-bottom: 20px; background-color: #f8f9fa; }
  .companion-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
  .companion-title { font-size: 16px; font-weight: bold; color: var(--text-dark); margin: 0; }
  .remove-companion-btn { background-color: var(--taken-red); color: white; border: none; padding: 5px 10px; border-radius: 4px; font-size: 12px; cursor: pointer; }
  .remove-companion-btn:hover { background-color: #c82333; }
  @media screen and (max-width: 992px) { .unit-content { flex-direction: column; } .inquiry-form-section { max-width: 100%; } .unit-image-slider { height: 40vh; } }
  @media screen and (max-width: 768px) { .page-wrapper { margin-top: 0; } .unit-image-slider { height: 35vh; } }
</style>

<?php if (!empty($page_error_message)): ?>
  <div class="main-content">
    <div class="alert <?php echo strpos($page_error_message, 'success') !== false ? 'alert-success' : 'alert-danger'; ?>">
      <?php echo $page_error_message; ?>
    </div>
  </div>
<?php endif; ?>

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
      <div style="display:flex; align-items:center; justify-content:center; height:100%; background-color:#f0f0f0;">
        <p>No images available for this unit.</p>
      </div>
    <?php endif; ?>
  </div>

  <div class="main-content">
    <div class="unit-id" data-unit-no="<?php echo htmlspecialchars($unit_data['unit_no']); ?>">
      <?php echo htmlspecialchars($unit_data['unit_no']); ?>
    </div>
    
    <div class="unit-content">
      <div class="unit-details-section">
        <h1 class="unit-title">
          <?php echo htmlspecialchars($unit_data['unit_type']); ?> Unit accommodating up to <?php echo htmlspecialchars($unit_data['occupant_capacity']); ?> persons
        </h1>
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

        <!-- INQUIRY FORM TAB -->
        <div class="tab-content active" id="inquiry-form-content">
          <?php if(!empty($form_success_message)): ?>
            <div class="alert alert-success"><?php echo $form_success_message; ?></div>
          <?php endif; ?>
          <?php if (isset($_POST['submit_visit_inquiry']) && !empty($form_error_message)): ?>
            <div class="alert alert-danger"><?php echo $form_error_message; ?></div>
          <?php endif; ?>
          
          <p class="form-intro">Let us know how we can assist you or if you'd like to see the unit in person.</p>
          
          <form id="visitInquiryForm" method="POST" action="USERINQUIRYPAGE.php?unit_no=<?php echo htmlspecialchars($unit_no_from_url); ?>" novalidate>
            <input type="hidden" name="unit_no_form" value="<?php echo htmlspecialchars($unit_data['unit_no']); ?>">
            
            <div class="form-row">
              <label for="fullname">Full Name</label>
              <!-- MODIFIED PATTERN: Positive lookahead (?=.*[a-zA-Z]) ensures at least one letter -->
              <input type="text" id="fullname" name="fullname" class="capitalize-text" required minlength="5" pattern="(?=.*[a-zA-Z])[a-zA-Z0-9\s.]+$" title="At least 5 characters. Must contain at least one letter. Numbers, spaces, and up to 2 dots allowed.">
            </div>
            
            <div class="form-row">
              <label for="email">Email Address</label>
              <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email_account']); ?>" readonly>
            </div>
            
            <div class="form-row">
              <label for="contact_no">Contact Number</label>
              <input type="tel" id="contact_no" name="contact_no" class="ph-phone-input" required value="+63" maxlength="13">
            </div>
            
            <div class="form-row">
              <label for="unit_type_display">Unit Type</label>
              <input type="text" id="unit_type_display" value="<?php echo htmlspecialchars($unit_data['unit_type']); ?>" readonly>
            </div>
            
            <div class="form-row">
              <label for="visit_date">Preferred Date to Visit (Optional)</label>
              <div class="calendar-container" id="inquiry-calendar-container">
                <div class="calendar-header">
                  <button type="button" class="nav-btn prev-btn"><</button>
                  <span></span>
                  <button type="button" class="nav-btn next-btn">></button>
                </div>
                <div class="calendar-weekdays">
                  <div>MON</div><div>TUE</div><div>WED</div><div>THU</div><div>FRI</div><div>SAT</div><div>SUN</div>
                </div>
                <div class="calendar-days"></div>
              </div>
              <input type="hidden" id="visit_date" name="visit_date">
            </div>
            
            <div class="form-row" id="time-slot-container">
              <label for="visit_time_slot">Preferred Time Slot</label>
              <select name="visit_time_slot" id="visit_time_slot" required>
                <option value="">-- Select an available time --</option>
                <option value="morning">Morning (8:00 AM - 11:00 AM)</option>
                <option value="afternoon">Afternoon (1:00 PM - 5:00 PM)</option>
              </select>
            </div>
            
            <div class="form-row">
              <label for="message">Questions or Message</label>
              <textarea id="message" name="message" rows="4" placeholder="Is Unit <?php echo htmlspecialchars($unit_data['unit_no']); ?> still available?"></textarea>
            </div>
            
            <button type="button" class="submit-btn" data-form-id="visitInquiryForm">Send Inquiry</button>
          </form>
        </div>

        <!-- RESERVATION FORM TAB -->
        <div class="tab-content" id="reservation-form-content">
          <?php if(!empty($form_success_message)): ?>
            <div class="alert alert-success"><?php echo $form_success_message; ?></div>
          <?php endif; ?>
          <?php if (isset($_POST['request_type']) && $_POST['request_type'] === 'reservation' && !empty($form_error_message)): ?>
            <div class="alert alert-danger"><?php echo $form_error_message; ?></div>
          <?php endif; ?>

          <h2 style="font-size: 18px; margin-bottom: 10px; color: var(--text-dark);">Apartment Unit Reservation Application</h2>
          <p class="form-intro">Please complete the form to reserve your preferred unit. We will contact you for the next steps.</p>
          
          <form id="reservationForm" method="POST" action="USERINQUIRYPAGE.php?unit_no=<?php echo htmlspecialchars($unit_no_from_url); ?>" novalidate>
            <input type="hidden" name="unit_no_reserve_form" value="<?php echo htmlspecialchars($unit_data['unit_no']); ?>">
            <input type="hidden" name="request_type" value="reservation">
            
            <div class="form-row">
              <label for="role">Role</label>
              <select id="role" name="role" required readonly style="background-color: #e9ecef; cursor: not-allowed;">
                <option value="representative" selected>Representative</option>
              </select>
            </div>
            
            <div class="form-row">
              <label for="fullname_reserve">Full Name</label>
              <!-- MODIFIED PATTERN -->
              <input type="text" id="fullname_reserve" name="fullname_reserve" class="capitalize-text" required minlength="5" pattern="(?=.*[a-zA-Z])[a-zA-Z0-9\s.]+$" title="At least 5 characters. Must contain at least one letter. Numbers, spaces, and up to 2 dots allowed.">
            </div>
            
            <div class="form-row">
              <label for="email_reserve">Email Address</label>
              <input type="email" id="email_reserve" name="email_reserve" value="<?php echo htmlspecialchars($_SESSION['email_account']); ?>" readonly>
            </div>
            
            <div class="form-row">
              <label for="contact_no_reserve">Contact Number</label>
              <input type="tel" id="contact_no_reserve" name="contact_no_reserve" class="ph-phone-input" required value="+63" maxlength="13">
            </div>
            
            <div class="form-row">
              <label for="unit_no_display_reserve">Preferred Unit to Reserve</label>
              <input type="text" id="unit_no_display_reserve" value="<?php echo htmlspecialchars($unit_data['unit_type']); ?> (<?php echo htmlspecialchars($unit_data['unit_no']); ?>)" readonly>
            </div>
            
            <div class="form-row">
              <label for="move_in_date">Move-in Date</label>
              <div class="calendar-container" id="reservation-calendar-container">
                <div class="calendar-header">
                  <button type="button" class="nav-btn prev-btn"><</button>
                  <span></span>
                  <button type="button" class="nav-btn next-btn">></button>
                </div>
                <div class="calendar-weekdays">
                  <div>MON</div><div>TUE</div><div>WED</div><div>THU</div><div>FRI</div><div>SAT</div><div>SUN</div>
                </div>
                <div class="calendar-days"></div>
              </div>
              <input type="hidden" id="move_in_date" name="move_in_date" required>
            </div>
            
            <div class="form-row">
              <label for="permanent_address">Permanent Address</label>
              <input type="text" id="permanent_address" name="permanent_address" required>
            </div>

            <div class="form-row">
              <label for="postal_address">Postal Address (Optional)</label>
              <input type="text" id="postal_address" name="postal_address">
            </div>

            <div class="form-row">
              <label for="citizenship">Citizenship</label>
              <select id="citizenship" name="citizenship" required>
                <option value="Filipino" selected>Filipino</option>
                <option value="American">American</option>
                <option value="British">British</option>
                <option value="Canadian">Canadian</option>
                <option value="Australian">Australian</option>
                <option value="Chinese">Chinese</option>
                <option value="Japanese">Japanese</option>
                <option value="Korean">Korean</option>
                <option value="Indian">Indian</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <div class="form-row">
              <label for="contract_term">Contract Term</label>
              <select id="contract_term" name="contract_term" required>
                <option value="6 MONTHS" selected>6 MONTHS</option>
                <option value="7 MONTHS">7 MONTHS</option>
                <option value="8 MONTHS">8 MONTHS</option>
                <option value="9 MONTHS">9 MONTHS</option>
                <option value="10 MONTHS">10 MONTHS</option>
                <option value="11 MONTHS">11 MONTHS</option>
                <option value="1 YEAR">1 YEAR</option>
                <option value="2 YEARS">2 YEARS</option>
                <option value="3 YEARS">3 YEARS</option>
                <option value="4 YEARS">4 YEARS</option>
                <option value="5 YEARS">5 YEARS</option>
              </select>
            </div>

            <div class="form-row">
              <label for="end_date">Contract End Date</label>
              <input type="text" id="end_date" name="end_date" readonly style="background-color: #e9ecef;">
            </div>

            <div class="form-row">
              <label for="monthly_rate_display">Monthly Rate</label>
              <input type="text" id="monthly_rate_display" value="₱<?php echo number_format($unit_data['monthly_rent_amount'], 2); ?>" readonly>
            </div>

            <div class="form-row">
              <label for="security_deposit_display">Security Deposit (2 MONTHS)</label>
              <input type="text" id="security_deposit_display" value="₱<?php echo number_format($unit_data['monthly_rent_amount'] * 2, 2); ?>" readonly>
            </div>

            <div class="form-row">
              <label for="ec_person">Emergency Contact Person</label>
              <!-- MODIFIED PATTERN -->
              <input type="text" id="ec_person" name="ec_person" class="capitalize-text" required minlength="5" pattern="(?=.*[a-zA-Z])[a-zA-Z0-9\s.]+$" title="At least 5 characters. Must contain at least one letter. Numbers, spaces, and up to 2 dots allowed.">
            </div>
            
            <div class="form-row">
              <label for="ec_no">Emergency Contact Number</label>
              <input type="tel" id="ec_no" name="ec_no" class="ph-phone-input" required value="+63" maxlength="13">
            </div>
            
            <div class="form-row">
              <label for="message_reserve">Questions or Message (Optional)</label>
              <textarea id="message_reserve" name="message_reserve" rows="4" placeholder="Any additional information..."></textarea>
            </div>
            
            <div id="companionFormsContainer"></div>
            
            <div class="form-row">
              <button type="button" id="addCompanionBtn" style="background-color: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; width: 100%; margin-bottom: 15px;">
                + Add Companion (Optional)
              </button>
            </div>
            
            <div class="important-reminder">
              <p><strong>Important:</strong><br>
              • Submitting this reservation will mark the unit as 'Pending' and remove it from public listing while your application is reviewed.<br>
              • Maximum of <?php echo htmlspecialchars($unit_data['occupant_capacity']); ?> persons per unit (including representative).<br>
              • Your move-in date will be the start date of your contract.<br>
              • A staff member will contact you shortly to finalize your application.</p>
            </div>
            
            <button type="button" class="submit-btn" data-form-id="reservationForm">Submit Reservation</button>
          </form>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Confirmation Modal -->
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const CHECK_SLOTS_URL = '<?php echo $checkSlotsUrl; ?>';
  let companionCount = 0;
  const maxCompanions = <?php echo $unit_data ? $unit_data['occupant_capacity'] - 1 : 0; ?>;

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
  
  if (totalSlides > 0) { 
    showSlide(currentSlideIndex); 
  }

  // --- COMPANION FORM MANAGEMENT ---
  function addCompanionForm() {
    if (companionCount >= maxCompanions) {
      alert(`Maximum of ${maxCompanions} companions allowed.`);
      return;
    }
    
    companionCount++;
    const container = document.getElementById('companionFormsContainer');
    const companionForm = document.createElement('div');
    companionForm.className = 'companion-form';
    companionForm.id = `companion-${companionCount}`;
    
    // UPDATED PATTERN: (?=.*[a-zA-Z]) ensures at least one letter
    companionForm.innerHTML = `
      <div class="companion-header">
        <h3 class="companion-title">Companion ${companionCount}</h3>
        <button type="button" class="remove-companion-btn" onclick="removeCompanionForm(${companionCount})">Remove</button>
      </div>
      <div class="form-row">
        <label for="companion_fullname_${companionCount}">Full Name</label>
        <input type="text" id="companion_fullname_${companionCount}" name="companion_fullname[]" class="capitalize-text" required minlength="5" pattern="(?=.*[a-zA-Z])[a-zA-Z0-9\\s.]+$" title="At least 5 characters. Must contain at least one letter. Numbers, spaces, and up to 2 dots allowed.">
      </div>
      <div class="form-row">
        <label for="companion_contact_${companionCount}">Contact Number</label>
        <input type="tel" id="companion_contact_${companionCount}" name="companion_contact[]" class="ph-phone-input" value="+63" maxlength="13">
      </div>
      <div class="form-row">
        <label for="companion_address_${companionCount}">Address</label>
        <input type="text" id="companion_address_${companionCount}" name="companion_address[]">
      </div>
      <div class="form-row">
        <label for="companion_ec_person_${companionCount}">Emergency Contact Person</label>
        <input type="text" id="companion_ec_person_${companionCount}" name="companion_ec_person[]" class="capitalize-text" minlength="5" pattern="(?=.*[a-zA-Z])[a-zA-Z0-9\\s.]+$" title="At least 5 characters. Must contain at least one letter. Numbers, spaces, and up to 2 dots allowed.">
      </div>
      <div class="form-row">
        <label for="companion_ec_no_${companionCount}">Emergency Contact Number</label>
        <input type="tel" id="companion_ec_no_${companionCount}" name="companion_ec_no[]" class="ph-phone-input" value="+63" maxlength="13">
      </div>
      <input type="hidden" name="companion_role[]" value="companion">
    `;
    
    container.appendChild(companionForm);
    updateAddCompanionButton();
  }

  function removeCompanionForm(companionId) {
    const companionForm = document.getElementById(`companion-${companionId}`);
    if (companionForm) {
      companionForm.remove();
      companionCount--;
      updateAddCompanionButton();
      renumberCompanions();
    }
  }

  function renumberCompanions() {
    const companionForms = document.querySelectorAll('.companion-form');
    companionForms.forEach((form, index) => {
      const newNumber = index + 1;
      const title = form.querySelector('.companion-title');
      if (title) {
        title.textContent = `Companion ${newNumber}`;
      }
    });
  }

  function updateAddCompanionButton() {
    const addBtn = document.getElementById('addCompanionBtn');
    if (companionCount >= maxCompanions) {
      addBtn.disabled = true;
      addBtn.textContent = `Maximum companions reached (${maxCompanions})`;
      addBtn.style.backgroundColor = '#6c757d';
    } else {
      addBtn.disabled = false;
      addBtn.textContent = `+ Add Companion (${companionCount}/${maxCompanions})`;
      addBtn.style.backgroundColor = '#6c757d';
    }
  }

  // --- UNIVERSAL CALENDAR & FORM LOGIC ---
  document.addEventListener('DOMContentLoaded', function() {
    const unitNo = document.querySelector('.unit-id')?.dataset.unitNo;
    const inquiryCalendar = new Calendar('inquiry-calendar-container', unitNo, true);
    const reservationCalendar = new Calendar('reservation-calendar-container', unitNo, false);

    document.getElementById('addCompanionBtn').addEventListener('click', addCompanionForm);
    updateAddCompanionButton();

    const contractTermSelect = document.getElementById('contract_term');
    if (contractTermSelect) {
      contractTermSelect.addEventListener('change', () => {
        const moveInDateInput = document.getElementById('move_in_date');
        if (moveInDateInput && moveInDateInput.value) {
          calculateEndDate(moveInDateInput.value);
        }
      });
    }

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
    
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
    const confirmModalLabel = document.getElementById('confirmModalLabel');
    const confirmModalBody = document.getElementById('confirmModalBody');
    const confirmSubmitBtn = document.getElementById('confirmSubmitBtn');
    let formToSubmit = null;

    document.querySelectorAll('.submit-btn').forEach(button => {
      button.addEventListener('click', (e) => {
        const formId = e.target.dataset.formId;
        formToSubmit = document.getElementById(formId);
        let isValid = false;
        let modalTitle = '', modalBody = '';

        if (formId === 'visitInquiryForm') {
          isValid = validateInquiryForm();
          modalTitle = 'Confirm Inquiry';
          modalBody = 'Are you sure you want to submit this visit inquiry?';
        } else if (formId === 'reservationForm') {
          isValid = validateReservationForm();
          modalTitle = 'Confirm Reservation';
          modalBody = 'Are you sure you want to submit this reservation application? This will mark the unit as Pending.';
        }

        if (isValid) {
          confirmModalLabel.textContent = modalTitle;
          confirmModalBody.textContent = modalBody;
          confirmModal.show();
        } else {
          // Specific alert guidance
          alert('Please correct the highlighted errors. Ensure names are valid (5+ chars, must contain a letter) and phone numbers are exactly 10 digits.');
        }
      });
    });

    confirmSubmitBtn.addEventListener('click', () => {
      if (formToSubmit) {
        const existingSubmitInputs = formToSubmit.querySelectorAll('input[type="hidden"][name^="submit_"]');
        existingSubmitInputs.forEach(input => input.remove());
        
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.value = 'confirmed'; 
        
        if (formToSubmit.id === 'visitInquiryForm') {
          hiddenInput.name = 'submit_visit_inquiry';
        } else if (formToSubmit.id === 'reservationForm') {
          hiddenInput.name = 'submit_reservation';
        }
        
        formToSubmit.appendChild(hiddenInput);
        confirmModal.hide();
        formToSubmit.submit();
      }
    });
  });

  // --- REUSABLE CALENDAR CLASS ---
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
      
      if (containerId.includes('inquiry')) {
        this.dateInput = document.getElementById('visit_date');
      } else if (containerId.includes('reservation')) {
        this.dateInput = document.getElementById('move_in_date');
      }
      
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
        if (this.currentMonth < 1) { this.currentMonth = 12; this.currentYear--; }
      } else {
        this.currentMonth++;
        if (this.currentMonth > 12) { this.currentMonth = 1; this.currentYear++; }
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
          if (!response.ok) {
            throw new Error(`HTTP error ${response.status}`);
          }
          this.availabilityData = await response.json();
          if (this.availabilityData.error) throw new Error(this.availabilityData.error);
        } catch (error) {
          console.error("Failed to load schedule:", error);
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
      for (let i = 0; i < firstDayOfWeek; i++) { this.daysGrid.appendChild(document.createElement('div')); }
      const todayForComparison = new Date();
      todayForComparison.setHours(0, 0, 0, 0);

      for (let i = 1; i <= daysInMonth; i++) {
        const dayDiv = document.createElement('div');
        dayDiv.textContent = i;
        dayDiv.classList.add('day');
        const dayDate = new Date(this.currentYear, this.currentMonth - 1, i);
        const fullDateString = `${this.currentYear}-${String(this.currentMonth).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
        
        if (this.dateInput.value === fullDateString) dayDiv.classList.add('selected');

        if (dayDate < todayForComparison) {
          dayDiv.classList.add('past');
        } else {
          const dayData = this.availabilityData[fullDateString];
          let isFull = this.checkAvailability && dayData && dayData.morning && dayData.afternoon;
          if(isFull) dayDiv.classList.add('full-day');
          else if (this.checkAvailability && dayData) dayDiv.classList.add('available-day');
          
          if (!isFull) {
            dayDiv.addEventListener('click', () => {
              this.container.querySelector('.day.selected')?.classList.remove('selected');
              dayDiv.classList.add('selected');
              this.dateInput.value = fullDateString;
              if (this.checkAvailability) {
                updateAvailableTimeSlots(dayData);
              }
              if (this.container.id === 'reservation-calendar-container') {
                calculateEndDate(fullDateString);
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

  function parseContractTerm(termString) {
    if (!termString) return 6;
    const match = termString.match(/^(\d+)\s+(month|year)s?$/i);
    if (!match) return 6;
    const value = parseInt(match[1]);
    const unit = match[2].toLowerCase();
    return (unit === 'year') ? value * 12 : value;
  }

  function calculateEndDate(startDateString) {
    const endDateInput = document.getElementById('end_date');
    if (!endDateInput) return;
    const startDate = new Date(startDateString);
    const contractTermSelect = document.getElementById('contract_term');
    const contractTermString = contractTermSelect ? contractTermSelect.value : '6 MONTHS';
    const contractTermMONTHS = parseContractTerm(contractTermString);
    const endDate = new Date(startDate);
    endDate.setMonth(endDate.getMonth() + contractTermMONTHS);
    endDateInput.value = endDate.toISOString().split('T')[0];
  }

  // --- VALIDATION FUNCTIONS ---
  
  // Validates: At least 5 chars, Letters, Numbers, Spaces, Max 2 dots
  // MUST CONTAIN AT LEAST ONE LETTER
  function validateNameFormat(val) {
      if (!val) return false;
      if (val.length < 5) return false;
      
      // Regex: Letters, Numbers, Spaces, Dots
      const validChars = /^[a-zA-Z0-9\s.]+$/.test(val);
      
      // Count dots (Max 2)
      const dotCount = (val.match(/\./g) || []).length;

      // Check for at least one letter
      const hasLetter = /[a-zA-Z]/.test(val);
      
      return validChars && dotCount <= 2 && hasLetter;
  }

  function validateInquiryForm() {
    let isValid = true;
    
    // Check Fullname
    const nameInput = document.getElementById('fullname');
    nameInput.classList.remove('error-input');
    if (!nameInput.value.trim() || !validateNameFormat(nameInput.value)) {
      nameInput.classList.add('error-input');
      isValid = false;
    }

    // Check Phone (Must be 13 chars including +63)
    const phoneInput = document.getElementById('contact_no');
    phoneInput.classList.remove('error-input');
    if (phoneInput.value.length !== 13) {
       phoneInput.classList.add('error-input');
       isValid = false;
    }

    // Check other fields
    ['email'].forEach(id => {
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

  function validateReservationForm() {
    let isValid = true;

    // Validate Name Fields (Length >= 5, allow numbers, max 2 dots, must have letter)
    ['fullname_reserve', 'ec_person'].forEach(id => {
       const input = document.getElementById(id);
       if (input) {
          input.classList.remove('error-input');
          if (!validateNameFormat(input.value)) {
             input.classList.add('error-input');
             isValid = false;
          }
       }
    });

    // Validate Phone Fields (Must be exactly 13 chars)
    ['contact_no_reserve', 'ec_no'].forEach(id => {
       const input = document.getElementById(id);
       if(input) {
           input.classList.remove('error-input');
           if(input.value.length !== 13) {
               input.classList.add('error-input');
               isValid = false;
           }
       }
    });

    // Validate other required fields
    ['move_in_date', 'permanent_address'].forEach(id => {
      const input = document.getElementById(id); 
      if (input) {
        input.classList.remove('error-input');
        if (!input.value.trim()) { 
          input.classList.add('error-input'); 
          isValid = false; 
        }
      }
    });
    
    // Validate Companion Forms
    document.querySelectorAll('.companion-form').forEach(form => {
      // Check Name
      const nameInput = form.querySelector('input[name="companion_fullname[]"]');
      if (nameInput) {
        nameInput.classList.remove('error-input');
        if (!validateNameFormat(nameInput.value)) {
          nameInput.classList.add('error-input');
          isValid = false;
        }
      }
      // Check Phone
      const phoneInput = form.querySelector('input[name="companion_contact[]"]');
      if(phoneInput && phoneInput.value !== '+63' && phoneInput.value.length !== 13) {
          phoneInput.classList.add('error-input');
          isValid = false;
      }
    });
    
    return isValid;
  }

  // --- INPUT FORMATTING SCRIPTS ---

  document.addEventListener('DOMContentLoaded', function() {
    // 1. CAPITALIZATION LOGIC
    document.body.addEventListener('focusout', function(e) {
      if (e.target.classList.contains('capitalize-text')) {
        let words = e.target.value.toLowerCase().split(' ');
        for (let i = 0; i < words.length; i++) {
          if (words[i].length > 0) {
              words[i] = words[i][0].toUpperCase() + words[i].substring(1);
          }
        }
        e.target.value = words.join(' ');
      }
    });

    // 2. PHONE NUMBER LOGIC (Strictly 10 digits after +63)
    document.body.addEventListener('input', function(e) {
      if (e.target.classList.contains('ph-phone-input')) {
        let input = e.target;
        let val = input.value;
        
        // Ensure it always starts with +63
        if (!val.startsWith('+63')) {
          let raw = val.replace(/\D/g, ''); // Strip non-digits
          if (raw.startsWith('63')) raw = raw.substring(2);
          if (raw.startsWith('0')) raw = raw.substring(1);
          val = '+63' + raw;
        }

        let numericPart = val.substring(3).replace(/\D/g, '');
        // Force strict 10 digit max
        if (numericPart.length > 10) {
          numericPart = numericPart.substring(0, 10);
        }
        input.value = '+63' + numericPart;
      }
    });

    // 3. Initialize Phone Inputs
    const phoneInputs = document.querySelectorAll('.ph-phone-input');
    phoneInputs.forEach(input => {
      if (input.value === '' || input.value === '+63') {
        input.value = '+63';
      }
    });
  });
</script>

<?php
include 'footer.php';
?>