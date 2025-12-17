<?php
// chatfunctions/SEND_MESSAGE.php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['email_account'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Connect to database
require_once __DIR__ . '/../db_connect.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['recipient']) || !isset($input['message'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$recipient_email = trim($input['recipient']);
$message = trim($input['message']);
$sender_email = $_SESSION['email_account'];

if (empty($recipient_email) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Recipient and message cannot be empty']);
    exit();
}

// Create a timestamp for the Philippine timezone
try {
    $timezone = new DateTimeZone('Asia/Manila');
    $philippine_time = new DateTime('now', $timezone);
    $timestamp_for_db = $philippine_time->format('Y-m-d H:i:s');
} catch (Exception $e) {
    http_response_code(500);
    error_log("Timezone error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server timezone configuration error.']);
    exit();
}

// Insert message into database
// Admin messages should have read_status = 1 by default since admin is sending them
$sql = "INSERT INTO chat_box (email_account, recipient, message, sender_type, message_time_date, read_status) 
        VALUES (?, ?, ?, 'admin', ?, 0)";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ssss", $sender_email, $recipient_email, $message, $timestamp_for_db);
    $success = $stmt->execute();
    $stmt->close();
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send message']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$conn->close();
?>