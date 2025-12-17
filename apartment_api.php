<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once 'db_connect.php'; 
date_default_timezone_set('Asia/Manila');
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'test_connection':
            testConnection($conn);
            break;
        case 'get_last_ids':
            getLastRecordIds($conn);
            break;
        case 'check_contracts':
            checkContractExpirations($conn, $_POST['current_date']);
            break;
        case 'update_expired_contract':
            updateExpiredContract($conn, $_POST['contract_id'], $_POST['email_account']);
            break;
        case 'check_new_visitations':
            checkNewVisitations($conn, $_POST['last_id']);
            break;
        case 'check_new_reservations':
            checkNewReservations($conn, $_POST['last_id']);
            break;
        case 'check_monthly_billing':
            checkMonthlyBilling($conn, $_POST['current_date']);
            break;
        case 'log_notification':
            logNotification($conn, $_POST['email'] ?? null, $_POST['message'], $_POST['type']);
            break;
        case 'check_card_status':
            checkCardStatus($conn, $_POST['card_no']);
            break;
        case 'get_admin_contact':
            getAdminContact($conn);
            break;
        default:
            echo json_encode(["error" => "Invalid action specified."]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "A server error occurred.", "message" => $e->getMessage()]);
}

$conn->close();


// =======================================================================
//                        FUNCTION DEFINITIONS
// =======================================================================

function testConnection($conn) {
    echo json_encode(["status" => "success", "message" => "Database connection is active."]);
}

function getAdminContact($conn) {
    $sql = "SELECT `admin_contact` FROM `admin_profile` LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode(["admin_contact" => $row['admin_contact']]);
    } else {
        echo json_encode(["admin_contact" => ""]);
    }
}

function getLastRecordIds($conn) {
    $response = [];
    $sql_visitation = "SELECT MAX(id) as max_id FROM visitation_dates";
    $result_visitation = $conn->query($sql_visitation);
    $row_visitation = $result_visitation->fetch_assoc();
    $response['last_visitation_id'] = $row_visitation['max_id'] ? (int)$row_visitation['max_id'] : 0;
    
    $sql_reservation = "SELECT MAX(reservation_id) as max_id FROM pending_reservation";
    $result_reservation = $conn->query($sql_reservation);
    $row_reservation = $result_reservation->fetch_assoc();
    $response['last_reservation_id'] = $row_reservation['max_id'] ? (int)$row_reservation['max_id'] : 0;
    
    echo json_encode($response);
}

function checkContractExpirations($conn, $currentDate) {
    $response = ['expiring_soon' => [], 'expired' => []];
    
    $sql_base = "SELECT c.contract_id, c.email_account, c.full_name, t.contact_no, tu.unit_no
                 FROM contract_information c
                 JOIN tenants t ON c.email_account = t.email
                 LEFT JOIN tenant_unit tu ON t.tenant_ID = tu.tenant_ID
                 WHERE (c.contract_status = 'First Contract' OR c.contract_status = 'Contract Renewal') 
                 AND t.role = 'representative' AND c.end_date = ?";
    
    $threeDaysLater = date('Y-m-d', strtotime($currentDate . ' +3 days'));
    $stmt = $conn->prepare($sql_base);
    $stmt->bind_param("s", $threeDaysLater);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response['expiring_soon'][] = $row;
    }
    $stmt->close();
    
    $stmt = $conn->prepare($sql_base);
    $stmt->bind_param("s", $currentDate);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response['expired'][] = $row;
    }
    $stmt->close();
    
    echo json_encode($response);
}

function checkMonthlyBilling($conn, $currentDate) {
    $response = [
        'payment_due_today' => [], 
        'payment_due_3days' => [],
        'billing_period_today' => [],
        'billing_period_3days' => [],
        'overdue_reminders' => [],
        'card_deactivations' => []
    ];
    
    // Set timezone to Philippines
    $timezone = new DateTimeZone('Asia/Manila');
    
    // Use the current date passed from ESP32, but ensure it's in Philippine timezone
    $today = new DateTime($currentDate, $timezone);
    $threeDaysLater = (clone $today)->modify('+3 days');
    
    $todayStr = $today->format('Y-m-d');
    $threeDaysLaterStr = $threeDaysLater->format('Y-m-d');
    
    // Get all active tenants with their units
    $sql = "SELECT t.tenant_ID, t.email, t.contact_no, tu.unit_no, t.tenant_name, u.monthly_rent_amount
            FROM tenants t
            INNER JOIN tenant_unit tu ON t.tenant_ID = tu.tenant_ID
            INNER JOIN units u ON tu.unit_no = u.unit_no
            WHERE tu.status = 'Active' AND t.role = 'representative'";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $unit_no = $row['unit_no'];
            $email = $row['email'];
            $contact_no = $row['contact_no'];
            $tenant_name = $row['tenant_name'];
            $monthly_rent = floatval($row['monthly_rent_amount'] ?? 0);
            
            // Get all checklist entries for this tenant ordered by date
            $checklist_sql = "SELECT checklist_ID, monthly_due_dates, pay_status 
                            FROM payment_checklist 
                            WHERE unit_no = ? AND email_account = ? 
                            ORDER BY monthly_due_dates ASC";
            $stmt = $conn->prepare($checklist_sql);
            $stmt->bind_param("ss", $unit_no, $email);
            $stmt->execute();
            $checklist_result = $stmt->get_result();
            
            $all_entries = [];
            $unpaid_months = [];
            
            while ($checklist = $checklist_result->fetch_assoc()) {
                $due_date_str = $checklist['monthly_due_dates'];
                $due_date = new DateTime($due_date_str, $timezone);
                $pay_status = intval($checklist['pay_status']);
                
                $entry_info = [
                    'due_date' => $due_date,
                    'formatted_date' => $due_date->format('Y-m-d'),
                    'month_year' => $due_date->format('F Y'),
                    'pay_status' => $pay_status
                ];
                
                $all_entries[] = $entry_info;
                
                // Track unpaid entries for overdue/deactivation checks later
                if ($pay_status < 1) {
                    $unpaid_months[] = $entry_info;
                }
            }
            $stmt->close();
            
            // ===== 1. PAYMENT DUE CHECKS =====
            // MODIFIED: Iterating all entries and explicitly checking pay_status
            foreach ($all_entries as $entry) {
                $pay_status = $entry['pay_status'];

                // STRICT CHECK: If pay_status is 1 (Paid), SKIP this entry completely.
                // It will not send SMS and will not add to balance.
                if ($pay_status == 1) {
                    continue; 
                }

                $due_date = $entry['due_date'];
                $dueStr = $due_date->format('Y-m-d');
                
                // Check if payment due is TODAY (and not paid)
                if ($dueStr === $todayStr) {
                    $response['payment_due_today'][] = [
                        'email' => $email,
                        'contact_no' => $contact_no,
                        'unit_no' => $unit_no,
                        'tenant_name' => $tenant_name,
                        'due_date' => $dueStr,
                        'monthly_rent' => number_format($monthly_rent, 2)
                    ];
                    
                    // Update balance in tenant_unit
                    $update_balance_sql = "UPDATE tenant_unit SET balance = balance + ? WHERE unit_no = ? AND tenant_ID = ?";
                    $update_stmt = $conn->prepare($update_balance_sql);
                    $update_stmt->bind_param("dsi", $monthly_rent, $unit_no, $row['tenant_ID']);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
                
                // Check if payment due is in 3 DAYS (and not paid)
                if ($dueStr === $threeDaysLaterStr) {
                    $response['payment_due_3days'][] = [
                        'email' => $email,
                        'contact_no' => $contact_no,
                        'unit_no' => $unit_no,
                        'tenant_name' => $tenant_name,
                        'due_date' => $dueStr,
                        'monthly_rent' => number_format($monthly_rent, 2)
                    ];
                }
            }
            
            // ===== 2. BILLING PERIOD CHECKS (NEXT MONTH NOTIFICATION) =====
            for ($i = 0; $i < count($all_entries); $i++) {
                $current_entry = $all_entries[$i];
                $current_due = $current_entry['due_date'];
                $currentDueStr = $current_entry['formatted_date'];
                
                // Find the next entry after this one (if it exists)
                $next_entry = null;
                $next_due = null;
                $nextDueStr = null;
                $next_pay_status = 0;
                if ($i < count($all_entries) - 1) {
                    $next_entry = $all_entries[$i + 1];
                    $next_due = $next_entry['due_date'];
                    $nextDueStr = $next_entry['formatted_date'];
                    $next_pay_status = intval($next_entry['pay_status']);
                }
                
                // Check if this billing period due date is TODAY
                if ($currentDueStr === $todayStr) {
                    // Only send notification if there's a next billing period AND it's NOT already paid
                    if ($next_due && $next_pay_status < 1) {
                        $response['billing_period_today'][] = [
                            'email' => $email,
                            'contact_no' => $contact_no,
                            'unit_no' => $unit_no,
                            'tenant_name' => $tenant_name,
                            'current_due_date' => $currentDueStr,
                            'next_due_date' => $nextDueStr,
                            'next_month_year' => $next_due->format('F Y')
                        ];
                    }
                }
                
                // Check if this billing period due date is 3 DAYS from today
                if ($currentDueStr === $threeDaysLaterStr) {
                    // Only send notification if there's a next billing period AND it's NOT already paid
                    if ($next_due && $next_pay_status < 1) {
                        $response['billing_period_3days'][] = [
                            'email' => $email,
                            'contact_no' => $contact_no,
                            'unit_no' => $unit_no,
                            'tenant_name' => $tenant_name,
                            'current_due_date' => $currentDueStr,
                            'next_due_date' => $nextDueStr,
                            'next_month_year' => $next_due->format('F Y')
                        ];
                    }
                }
            }
            
            // ===== 3. OVERDUE REMINDERS (Every 5 days for past unpaid) =====
            foreach ($unpaid_months as $unpaid) {
                $due_date = $unpaid['due_date'];
                
                // Only check past due dates (not today or future)
                if ($due_date < $today) {
                    $days_overdue = $today->diff($due_date)->days;
                    
                    // Send reminder every 5 days (5, 10, 15, 20, etc.)
                    if ($days_overdue > 0 && $days_overdue % 5 === 0) {
                        $response['overdue_reminders'][] = [
                            'email' => $email,
                            'contact_no' => $contact_no,
                            'unit_no' => $unit_no,
                            'tenant_name' => $tenant_name,
                            'overdue_month' => $unpaid['month_year'],
                            'days_overdue' => $days_overdue,
                            'due_date' => $unpaid['formatted_date']
                        ];
                    }
                }
            }
            
            // ===== 4. CARD DEACTIVATION (3+ months unpaid) =====
            if (count($unpaid_months) >= 3) {
                // Check if today matches the third unpaid month's due date
                $third_unpaid = $unpaid_months[2];
                if ($third_unpaid['formatted_date'] === $todayStr) {
                    $months_list = implode(', ', array_column(array_slice($unpaid_months, 0, 3), 'month_year'));
                    
                    $response['card_deactivations'][] = [
                        'email' => $email,
                        'contact_no' => $contact_no,
                        'unit_no' => $unit_no,
                        'tenant_name' => $tenant_name,
                        'unpaid_months' => $months_list,
                        'total_unpaid' => count($unpaid_months)
                    ];
                    
                    // Deactivate RFID cards for this unit
                    $deactivate_sql = "UPDATE card_registration SET card_status = 'Deactivated' WHERE unit_no = ?";
                    $deactivate_stmt = $conn->prepare($deactivate_sql);
                    $deactivate_stmt->bind_param("s", $unit_no);
                    $deactivate_stmt->execute();
                    $deactivate_stmt->close();
                }
            }
        }
    }
    
    echo json_encode($response);
}

function checkNewVisitations($conn, $lastId) {
    $response = ['new_visitations' => []];
    $sql = "SELECT id, request_type, email_account, contact_no, visitation_date, unit_no
            FROM visitation_dates WHERE id > ? ORDER BY id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $lastId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response['new_visitations'][] = $row;
    }
    $stmt->close();
    echo json_encode($response);
}

function checkNewReservations($conn, $lastId) {
    $response = ['new_reservations' => []];
    $sql = "SELECT reservation_id, full_name, unit_no, contact_no, pref_move_date, email_account
            FROM pending_reservation WHERE role = 'representative' AND reservation_id > ? ORDER BY reservation_id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $lastId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $response['new_reservations'][] = $row;
    }
    $stmt->close();
    echo json_encode($response);
}

function logNotification($conn, $email, $message, $type) {
    $philippines_time = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $formatted_datetime = $philippines_time->format('Y-m-d H:i:s');
    
    $sql_insert = "INSERT INTO notification_inbox (email_account, notif_title, notif_description, notif_date_time) VALUES (?, ?, ?, ?)";
    $insertStmt = $conn->prepare($sql_insert);
    $insertStmt->bind_param("ssss", $email, $type, $message, $formatted_datetime);
    if ($insertStmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $insertStmt->error]);
    }
    $insertStmt->close();
}

function updateExpiredContract($conn, $contractId, $emailAccount) {
    $conn->begin_transaction();

    try {
        // Step 1: Get email_account, tenant_ID, unit_no and tu_ID from contract_id
        $sql_find_info = "SELECT ci.email_account, t.tenant_ID, tu.unit_no, tu.tu_ID
                          FROM contract_information ci
                          INNER JOIN tenant_unit tu ON ci.contract_id = tu.tu_ID
                          INNER JOIN tenants t ON tu.tenant_ID = t.tenant_ID AND t.role = 'representative'
                          WHERE ci.contract_id = ? 
                          AND (ci.contract_status = 'First Contract' OR ci.contract_status = 'Contract Renewal')";
        $stmt_find = $conn->prepare($sql_find_info);
        $stmt_find->bind_param("i", $contractId);
        $stmt_find->execute();
        $result = $stmt_find->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("No active contract found for contract_id: " . $contractId);
        }
        
        $data = $result->fetch_assoc();
        $email_account = $data['email_account'];
        $unitNo = $data['unit_no'];
        $representativeTenantId = $data['tenant_ID'];
        $tu_ID = $data['tu_ID'];
        $stmt_find->close();

        // Step 2: Archive ALL tenants (representative + companions) who share the same email.
        // Get tenant_unit data first (from representative)
        $sql_get_tu_data = "SELECT unit_no, start_date, end_date FROM tenant_unit WHERE tu_ID = ?";
        $stmt_get_tu = $conn->prepare($sql_get_tu_data);
        $stmt_get_tu->bind_param("i", $tu_ID);
        $stmt_get_tu->execute();
        $tu_data_result = $stmt_get_tu->get_result();
        $tu_data = $tu_data_result->fetch_assoc();
        $stmt_get_tu->close();
        
        if (!$tu_data) {
            throw new Exception("Could not find tenant_unit data for tu_ID: " . $tu_ID);
        }
        
        // Archive ALL tenants with the same email (representative + companions)
        $sql_archive = "INSERT INTO tenant_history (unit_no, name, role, contact_no, permanent_address, emergency_person, emergency_contact, start_date, end_date)
                        SELECT
                            ?,
                            t.tenant_name,
                            t.role,
                            t.contact_no,
                            t.permanent_address,
                            t.ec_person,
                            t.ec_no,
                            ?,
                            ?
                        FROM
                            tenants AS t
                        WHERE
                            t.email = ?";
        
        $stmt_archive = $conn->prepare($sql_archive);
        $stmt_archive->bind_param("ssss", $tu_data['unit_no'], $tu_data['start_date'], $tu_data['end_date'], $email_account);
        
        if (!$stmt_archive->execute()) {
            throw new Exception("Failed to archive tenants with email: " . $email_account);
        }
        $archived_count = $stmt_archive->affected_rows;
        $stmt_archive->close();
        
        error_log("Archived $archived_count tenants with email $email_account to tenant_history");

        // Step 2.5: Update accounts table
        $sql_update_accounts = "UPDATE `accounts` SET `user_type` = 'user' WHERE `email_account` = ?";
        $stmt_update_accounts = $conn->prepare($sql_update_accounts);
        $stmt_update_accounts->bind_param("s", $email_account);
        if (!$stmt_update_accounts->execute()) {
            throw new Exception("Failed to update accounts for email: " . $email_account);
        }
        $stmt_update_accounts->close();

        // Step 3: Delete all tenants (representative + companions)
        $sql_delete_tenants = "DELETE FROM tenants WHERE email = ?";
        $stmt_delete_tenants = $conn->prepare($sql_delete_tenants);
        $stmt_delete_tenants->bind_param("s", $email_account);
        if (!$stmt_delete_tenants->execute()) {
            throw new Exception("Failed to delete tenants with email: " . $email_account);
        }
        $deleted_count = $stmt_delete_tenants->affected_rows;
        $stmt_delete_tenants->close();
        
        error_log("Deleted $deleted_count tenants with email $email_account");

        // Step 4: Delete tenant_unit record using tu_ID
        $sql_delete_link = "DELETE FROM tenant_unit WHERE tu_ID = ?";
        $stmt_delete_link = $conn->prepare($sql_delete_link);
        $stmt_delete_link->bind_param("i", $tu_ID);
        $stmt_delete_link->execute();
        $stmt_delete_link->close();
        
        // Step 5: Update unit status to 'pending'
        $sql_update_unit = "UPDATE units SET unit_status = 'pending' WHERE unit_no = ?";
        $stmt_update_unit = $conn->prepare($sql_update_unit);
        $stmt_update_unit->bind_param("s", $unitNo);
        $stmt_update_unit->execute();
        $stmt_update_unit->close();

        // Step 6: Update contract status to 'Contract Ended'
        $sql_update_contract = "UPDATE contract_information SET contract_status = 'Contract Ended' WHERE contract_id = ?";
        $stmt_update_contract = $conn->prepare($sql_update_contract);
        $stmt_update_contract->bind_param("i", $contractId);
        $stmt_update_contract->execute();
        $stmt_update_contract->close();
        
        // Step 7: Deactivate RFID cards
        $sql_deactivate_rfid = "UPDATE card_registration SET card_status = 'Deactivated' WHERE unit_no = ?";
        $stmt_deactivate_rfid = $conn->prepare($sql_deactivate_rfid);
        $stmt_deactivate_rfid->bind_param("s", $unitNo);
        $stmt_deactivate_rfid->execute();
        $stmt_deactivate_rfid->close();
        
        // Step 8: Delete payment checklist entries
        $sql_delete_checklist = "DELETE FROM payment_checklist WHERE email_account = ?";
        $stmt_delete_checklist = $conn->prepare($sql_delete_checklist);
        $stmt_delete_checklist->bind_param("s", $email_account);
        
        if (!$stmt_delete_checklist->execute()) {
            throw new Exception("Failed to delete payment checklist for email: " . $email_account);
        }
        $checklist_deleted = $stmt_delete_checklist->affected_rows;
        $stmt_delete_checklist->close();
        
        error_log("Deleted $checklist_deleted payment checklist entries for email $email_account");
        
        $conn->commit();
        echo json_encode(["success" => true, "message" => "Contract " . $contractId . " ended successfully. Unit " . $unitNo . " has been set to pending status."]);

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Failed to end expired contract: " . $e->getMessage()]);
    }
}

function checkCardStatus($conn, $cardNo) {
    $response = ['status' => 'unknown'];
    
    $sql = "SELECT card_no, tenant_ID, unit_no FROM card_registration WHERE card_no = ? AND card_status = 'Activated'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $cardNo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $response['status'] = 'already_registered';
        $response['tenant_ID'] = $row['tenant_ID'];
        $response['unit_no'] = $row['unit_no'];
    } else {
        $response['status'] = 'new_card';
    }
    
    $stmt->close();
    echo json_encode($response);
}
?>