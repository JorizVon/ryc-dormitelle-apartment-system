<?php
// esp32_handler.php

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php'; // Ensure this path is correct
date_default_timezone_set('Asia/Manila');

// Helper function for logging access (remains the same)
function logAccess($conn, $tenant_ID, $card_no, $unit_no, $status) {
    $current_time = date("Y-m-d H:i:s");
    $log_sql = "INSERT INTO access_logs (tenant_ID, card_no, unit_no, date_and_time, access_status)
                VALUES (?, ?, ?, ?, ?)";
    
    $log_stmt = $conn->prepare($log_sql);
    if ($log_stmt) {
        $log_stmt->bind_param("sssss", $tenant_ID, $card_no, $unit_no, $current_time, $status);
        if (!$log_stmt->execute()) {
            error_log("Access log insert failed: " . $log_stmt->error);
        }
        $log_stmt->close();
    } else {
        error_log("Failed to prepare access log statement: " . $conn->error);
    }
}

// ========================================================
// == API ENDPOINTS FOR WEB UI (CARDREGISTRATION.php)
// ========================================================

if (isset($_GET['check_rfid_scan'])) {
    header('Content-Type: application/json');
    
    // Select the globally most recent scan within the last 30 seconds
    $scan_sql = "SELECT rfid_tag, scan_time 
                 FROM temp_rfid_scans 
                 WHERE scan_time > DATE_SUB(NOW(), INTERVAL 30 SECOND)
                 ORDER BY scan_time DESC 
                 LIMIT 1";
    
    $scan_stmt = $conn->prepare($scan_sql);
    if ($scan_stmt) {
        $scan_stmt->execute();
        $scan_result = $scan_stmt->get_result();
        
        if ($scan_result->num_rows > 0) {
            $scan_row = $scan_result->fetch_assoc();
            
            // IMPORTANT: Clear the temporary scan record after retrieving it
            // This prevents the same tag from being fetched repeatedly.
            $clear_sql = "DELETE FROM temp_rfid_scans WHERE rfid_tag = ?";
            $clear_stmt = $conn->prepare($clear_sql);
            if ($clear_stmt) {
                $clear_stmt->bind_param("s", $scan_row['rfid_tag']);
                $clear_stmt->execute();
                $clear_stmt->close();
            }
            
            echo json_encode([
                'status' => 'new_scan',
                'rfid_tag' => $scan_row['rfid_tag']
            ]);
        } else {
            echo json_encode(['status' => 'no_new_scans']);
        }
        $scan_stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    $conn->close();
    exit();
}

// NEW: Handle tenant selection and get unit_no from web interface
if (isset($_GET['get_unit_no']) && isset($_GET['tenant_id'])) {
    header('Content-Type: application/json');
    $tenant_id = trim($_GET['tenant_id']);
    
    // Get the first 12 characters to match with tenant_unit (assuming tenant_ID structure)
    $tenant_prefix = substr($tenant_id, 0, 12); // Adjust if your tenant_ID linking logic is different
    
    $unit_sql = "SELECT tenant_unit.unit_no 
                 FROM tenant_unit 
                 WHERE LEFT(tenant_unit.tenant_ID, 12) = ?
                 LIMIT 1";
    
    $unit_stmt = $conn->prepare($unit_sql);
    if ($unit_stmt) {
        $unit_stmt->bind_param("s", $tenant_prefix);
        $unit_stmt->execute();
        $unit_result = $unit_stmt->get_result();
        
        if ($unit_result->num_rows > 0) {
            $unit_row = $unit_result->fetch_assoc();
            echo json_encode([
                'status' => 'success',
                'unit_no' => $unit_row['unit_no']
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No unit found for this tenant ID: ' . htmlspecialchars($tenant_id)
            ]);
        }
        $unit_stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error preparing unit query: ' . $conn->error]);
    }
    $conn->close(); // Close connection and exit for AJAX request
    exit();
}

// ========================================================
// == API ENDPOINTS FOR ESP32 DEVICE (UNCHANGED)
// ========================================================

// Handle RFID card checking from ESP32 (door access logic)
if (isset($_GET['tag']) && isset($_GET['unit'])) {
    $tag = trim($_GET['tag']);
    $unit_no = trim($_GET['unit']);
    
    if (empty($tag) || empty($unit_no)) { exit("MISSING_DATA"); }
    if ($conn->connect_error) { exit("DB_ERROR"); }
    
    $isAuthorized = false;
    $isAdminCard = false;
    $tenant_ID = null; // Initialize tenant_ID
    
    // STEP 1: Check if card matches the specific unit (now includes tenant_ID)
    $sql = "SELECT card_no, tenant_ID, unit_no 
            FROM card_registration 
            WHERE card_no = ? AND unit_no = ? AND card_status = 'Activated'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $tag, $unit_no);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $isAuthorized = true;
        $card_row = $result->fetch_assoc();
        $tenant_ID = $card_row['tenant_ID'];
    } else {
        // STEP 2: Check if it's an admin card (unit_no = 'admin')
        $stmt->close(); // Close previous statement
        $admin_sql = "SELECT card_no, tenant_ID 
                      FROM card_registration 
                      WHERE card_no = ? AND unit_no = 'admin' AND card_status = 'Activated'";
        
        $stmt = $conn->prepare($admin_sql);
        $stmt->bind_param("s", $tag);
        $stmt->execute();
        $admin_result = $stmt->get_result();
        
        if ($admin_result->num_rows > 0) {
            $isAuthorized = true;
            $isAdminCard = true;
            $admin_row = $admin_result->fetch_assoc();
            $tenant_ID = $admin_row['tenant_ID'];
        }
    }
    
    if ($isAuthorized) {
        echo "FOUND"; // Respond to ESP32 immediately
        // Optional: fastcgi_finish_request() if you need to do background logging
        if (function_exists('fastcgi_finish_request')) {
             fastcgi_finish_request();
        }
        $log_unit = $unit_no; 
        $status = $isAdminCard ? 'Success (Admin)' : 'Success';
        logAccess($conn, $tenant_ID, $tag, $log_unit, $status);
    } else {
        // First, attempt to get tenant_ID for failed access logging
        if (!$tenant_ID) { // Only try if tenant_ID wasn't set by a valid card
            $tenant_sql = "SELECT tenant_ID FROM tenant_unit WHERE unit_no = ? LIMIT 1";
            $tenant_stmt = $conn->prepare($tenant_sql);
            if ($tenant_stmt) {
                $tenant_stmt->bind_param("s", $unit_no);
                $tenant_stmt->execute();
                if ($tenant_row = $tenant_stmt->get_result()->fetch_assoc()) {
                    $tenant_ID = $tenant_row['tenant_ID'];
                }
                $tenant_stmt->close();
            }
        }
        logAccess($conn, $tenant_ID, $tag, $unit_no, 'Failed');
        
        // Then, tell the ESP32 the specific reason for the failure.
        $check_unit_sql = "SELECT 1 FROM card_registration WHERE unit_no = ? AND card_status = 'Activated' LIMIT 1";
        $check_stmt = $conn->prepare($check_unit_sql);
        $check_stmt->bind_param("s", $unit_no);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows == 0) {
            echo "NO_CARDS_REGISTERED";
        } else {
            echo "NOT_AUTHORIZED";
        }
        $check_stmt->close();
    }
    
    $stmt->close();
    $conn->close();
    exit();
}


// Handle staging a new RFID card for registration from ESP32
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_card'])) {
    // We only need the tag. The 'unit' parameter from the ESP32 will be ignored.
    $tag = trim($_POST['tag']);
    
    if (empty($tag)) { 
        echo json_encode(['status' => 'error', 'message' => 'MISSING_TAG_DATA']);
        exit(); 
    }
    if ($conn->connect_error) { 
        echo json_encode(['status' => 'error', 'message' => 'DB_ERROR']);
        exit(); 
    }
    
    // Check if card is already fully registered and activated
    $card_check_sql = "SELECT 1 FROM card_registration WHERE card_no = ? AND card_status = 'Activated' LIMIT 1";
    $card_stmt = $conn->prepare($card_check_sql);
    $card_stmt->bind_param("s", $tag);
    $card_stmt->execute();
    if ($card_stmt->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'CARD_ALREADY_EXISTS']);
        $card_stmt->close();
        $conn->close();
        exit();
    }
    $card_stmt->close();
    
    // Store the scanned tag temporarily for the web interface.
    // This will overwrite any existing entry for the same tag with a new timestamp.
    $temp_sql = "INSERT INTO temp_rfid_scans (rfid_tag, scan_time) VALUES (?, NOW()) 
                 ON DUPLICATE KEY UPDATE scan_time = VALUES(scan_time)";
                 
    $temp_stmt = $conn->prepare($temp_sql);
    $temp_stmt->bind_param("s", $tag);
    
    if ($temp_stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'RFID tag staged successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'DB_INSERT_ERROR: ' . $conn->error]);
    }
    
    $temp_stmt->close();
    $conn->close();
    exit();
}

// Default response for any other type of request
// This ensures that if no specific action is matched, the script exits cleanly.
echo "INVALID_REQUEST";
if (isset($conn)) {
    $conn->close();
}
exit();
?>