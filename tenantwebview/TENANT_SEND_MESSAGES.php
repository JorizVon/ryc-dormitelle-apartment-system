<?php
// chatfunctions/TENANT_SEND_MESSAGES.php
session_start();
require_once __DIR__ . '/../db_connect.php';

header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['email_account'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated']);
    exit();
}

// Get and validate the message from POST data
$message = trim($_POST['message'] ?? '');
if (empty($message)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Message cannot be empty']);
    exit();
}

// Get admin email dynamically
function getAdminEmail($conn) {
    $sql = "SELECT email_account FROM accounts WHERE user_type = 'admin' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['email_account'];
    }
    return null;
}

$admin_email = getAdminEmail($conn);
if (!$admin_email) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Admin not found']);
    exit();
}

// Assign variables for the database insert
$user_email = $_SESSION['email_account'];
$sender_type = 'user'; // The sender is the user

// Create a timestamp for the Philippine timezone
try {
    $timezone = new DateTimeZone('Asia/Manila');
    $philippine_time = new DateTime('now', $timezone);
    $timestamp_for_db = $philippine_time->format('Y-m-d H:i:s');
} catch (Exception $e) {
    http_response_code(500);
    error_log("Timezone error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Server timezone configuration error.']);
    exit();
}

// Prepare the SQL statement with placeholders
// User messages should have read_status = TRUE by default since user is sending them
$sql = "INSERT INTO chat_box (email_account, message, recipient, sender_type, message_time_date, read_status) 
        VALUES (?, ?, ?, ?, ?, FALSE)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    error_log("Prepare failed: " . $conn->error);
    echo json_encode(['status' => 'error', 'message' => 'Database prepare statement failed.']);
    exit();
}

// Bind parameters
$stmt->bind_param("sssss", $user_email, $message, $admin_email, $sender_type, $timestamp_for_db);

// Execute and provide response
if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Message sent successfully.']);
} else {
    http_response_code(500);
    error_log("Execute failed: " . $stmt->error);
    echo json_encode(['status' => 'error', 'message' => 'Failed to send message.']);
}

$stmt->close();
$conn->close();
?>