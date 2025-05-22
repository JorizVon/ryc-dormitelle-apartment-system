<?php
/* ================= NOTIFICATION.php ================= */
// Session is already started in the main file
// session_start(); // Start session ONCE, at the very beginning

require_once '../db_connect.php'; // adjust path if needed

// Redirect to login if not logged in
if (!isset($_SESSION['email_account'])) {
    header("Location: ../login.php"); // Change path as needed
    exit();
}

$email = $_SESSION['email_account'];
$today = new DateTime();
$todayStr = $today->format('Y-m-d');

// Reset shown flag once per day
if (!isset($_SESSION['notif_last_shown']) || $_SESSION['notif_last_shown'] !== $todayStr) {
    $_SESSION['notif_shown'] = false;
    $_SESSION['notif_last_shown'] = $todayStr;
}

// First, get the tenant_ID from the email - This is safer than assuming the query will work
$tenant_query = $conn->prepare("SELECT tenant_ID FROM tenants WHERE email = ?");
if (!$tenant_query) {
    // Handle the SQL preparation error - log it for debugging
    error_log("SQL Prepare Error in NOTIFICATION.php: " . $conn->error);
    return; // Exit gracefully without breaking the page
}

$tenant_query->bind_param("s", $email);
$tenant_query->execute();
$tenant_result = $tenant_query->get_result();

// Check if we found a tenant
if ($tenant_result->num_rows == 0) {
    // No tenant found - log for debugging
    error_log("No tenant found with email: $email");
    return; // Exit gracefully
}

$tenant_row = $tenant_result->fetch_assoc();
$tenant_ID = $tenant_row['tenant_ID'];

// Now get the tenant unit info
$stmt = $conn->prepare("SELECT tenant_unit.unit_no, tenant_unit.start_date, tenant_unit.balance, tenant_unit.end_date 
                        FROM tenant_unit 
                        WHERE tenant_unit.tenant_ID = ?");

if (!$stmt) {
    // Handle the SQL preparation error - log it for debugging
    error_log("SQL Prepare Error in NOTIFICATION.php for tenant_unit: " . $conn->error);
    return; // Exit gracefully without breaking the page
}

$stmt->bind_param("s", $tenant_ID); // FIXED: Changed "i" to "s" since tenant_ID is VARCHAR
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];

while ($row = $result->fetch_assoc()) {
    $unit_no = $row['unit_no'];
    $start_date = new DateTime($row['start_date']);
    $balance = number_format($row['balance'], 2);
    $end_date = new DateTime($row['end_date']);

    $billing_start_day = (int)$start_date->format('d');

    // Construct billing period start date for THIS month
    $billing_this_month_str = $today->format('Y-m') . '-' . str_pad($billing_start_day, 2, '0', STR_PAD_LEFT);
    $billing_this_month = DateTime::createFromFormat('Y-m-d', $billing_this_month_str);

    // If billing day is > days in this month (e.g. 31 on Feb), adjust to last day of month
    if (!$billing_this_month) {
        $billing_this_month = new DateTime($today->format('Y-m-t')); // last day of month
    }

    $billing_end = clone $billing_this_month;
    $billing_end->modify('+5 days');
    $statement_posted_date = clone $billing_end;
    $statement_posted_date->modify('+1 day');
    $payment_due_date = clone $statement_posted_date;
    $payment_due_date->modify('+9 days');

    $unit_display = strtoupper($unit_no);
    $formatDate = fn($d) => $d->format('F j, Y');

    // Calculate intervals for notifications safely
    $diff_billing_this_month = (int)$today->diff($billing_this_month)->format('%r%a'); // negative if today < billing_this_month

    // BILLING PERIOD NOTIFICATIONS
    if ($diff_billing_this_month === -1) { // today is day before billing start
        $notifications[] = [
            'title' => 'Upcoming Billing Period Starts Tomorrow',
            'message' => "A reminder that your next billing period for $unit_display at RYC Dormitelle begins tomorrow, " . $formatDate($billing_this_month) . ", and runs through " . $formatDate($billing_end) . ".<br>Prepare to review your rent statement once it's posted on " . $formatDate($statement_posted_date) . ".<br><br>Thank you!"
        ];
    } elseif ($todayStr === $billing_this_month->format('Y-m-d')) {
        $notifications[] = [
            'title' => 'Billing Period Begins Today',
            'message' => "Your billing period for $unit_display starts today, " . $formatDate($billing_this_month) . ", and ends " . $formatDate($billing_end) . ".<br>You can view your rent amount anytime in the resident portal.<br><br>Thanks for staying on top of it!"
        ];
    } elseif ($todayStr === $billing_end->format('Y-m-d')) {
        $notifications[] = [
            'title' => 'Billing Period Closed – Statement Ready',
            'message' => "Your billing period of " . $formatDate($billing_this_month) . " – " . $formatDate($billing_end) . " has ended.<br>Your statement is now available in the portal. Please review and prepare for payment by " . $formatDate($payment_due_date) . ".<br><br>Let us know if you have any questions.<br>RYC Dormitelle Apartment System"
        ];
    }

    // PAYMENT DUE NOTIFICATIONS
    // We must not modify $payment_due_date object when checking
    $payment_due_minus_one = (clone $payment_due_date)->modify('-1 day');
    if ($todayStr === $payment_due_minus_one->format('Y-m-d')) {
        $notifications[] = [
            'title' => 'Payment Due Tomorrow',
            'message' => "This is a friendly reminder that your rent and utilities for $unit_display at RYC Dormitelle are due tomorrow, " . $formatDate($payment_due_date) . ".<br>Amount Due: ₱$balance<br><br>You may settle your payment through the following methods:<br>• GCash Transfer:<br>   - GCash Number: 0917-123-4567<br>   - Account Name: Kyle Catiis<br>• In-person payment at the leasing office (9 AM – 5 PM, Mon–Fri)<br>• Settle with Deposit: Let us know if you'd like to use your deposit for this payment.<br><br>Thank you."
        ];
    } elseif ($todayStr === $payment_due_date->format('Y-m-d')) {
        $notifications[] = [
            'title' => 'Payment Due',
            'message' => "This is a friendly reminder that your ₱$balance payment for $unit_display is due today, " . $formatDate($payment_due_date) . ".<br>Please settle it by 11:59 PM to avoid late fees.<br><br>Thanks for your prompt attention."
        ];
    } elseif ($today > $payment_due_date) {
        $notifications[] = [
            'title' => 'Payment Overdue Notice',
            'message' => "We noticed we haven't received your " . $formatDate($payment_due_date) . " payment for $unit_display.<br>Amount Outstanding: ₱$balance<br><br>Kindly settle as soon as possible to avoid a late fee. If you've already paid, please send us the confirmation.<br><br>We appreciate your cooperation."
        ];
    }

    // END DATE NOTIFICATIONS
    $end_minus_one = (clone $end_date)->modify('-1 day');
    if ($end_minus_one->format('Y-m-d') === $todayStr) {
        $notifications[] = [
            'title' => 'Stay Ends Tomorrow – Card Access Will Expire',
            'message' => "We hope you've enjoyed your stay at RYC Dormitelle. This is a reminder that your apartment stay is set to end tomorrow, " . $formatDate((clone $end_date)) . ".<br><br>Your access card will automatically expire on the end date. Contact us if you need to renew or move out.<br><br>Thank you for being part of our community."
        ];
    } elseif ($todayStr === $end_date->format('Y-m-d')) {
        $notifications[] = [
            'title' => 'Today Is Your End Date – Access Card Expires Today',
            'message' => "Today, " . $formatDate($end_date) . ", is the final day of your stay at RYC Dormitelle.<br><br>Your access card has now expired. For renewal or move-out assistance, contact us immediately.<br><br>Thank you for staying with us!"
        ];
    } elseif ($today > $end_date) {
        $notifications[] = [
            'title' => 'Your Stay Has Ended – Card Access Disabled',
            'message' => "Your apartment stay at RYC Dormitelle officially ended on " . $formatDate($end_date) . ".<br><br>Your access card has been deactivated. If you need help with final procedures, please reach out.<br><br>Thank you for staying with us!"
        ];
    }

    // Save notifications to inbox
    foreach ($notifications as $notif) {
        $notif_title = $notif['title'];
        $notif_desc = $notif['message'];
        $now = date('Y-m-d H:i:s');

        $check = $conn->prepare("SELECT * FROM notification_inbox WHERE tenant_ID = ? AND notif_title = ? AND DATE(notif_date_time) = CURDATE()");
        if (!$check) {
            error_log("SQL Prepare Error in NOTIFICATION.php for notification_inbox check: " . $conn->error);
            continue; // Skip this notification
        }
        
        $check->bind_param("ss", $tenant_ID, $notif_title); // FIXED: Changed "is" to "ss" since tenant_ID is VARCHAR
        $check->execute();
        $exists = $check->get_result();

        if ($exists->num_rows == 0) {
            $insert = $conn->prepare("INSERT INTO notification_inbox (notif_date_time, tenant_ID, notif_title, notif_description) VALUES (?, ?, ?, ?)");
            if (!$insert) {
                error_log("SQL Prepare Error in NOTIFICATION.php for notification_inbox insert: " . $conn->error);
                continue; // Skip this notification
            }
            
            $insert->bind_param("ssss", $now, $tenant_ID, $notif_title, $notif_desc); // FIXED: Changed "siss" to "ssss"
            $insert->execute();
        }
    }
}

if (!empty($notifications) && !$_SESSION['notif_shown']) {
    $_SESSION['current_notification'] = $notifications[0]; // Show the first generated notification
    $_SESSION['notif_shown'] = true;
}
?>