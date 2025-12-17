<?php
// chatfunctions/MARK_MESSAGES_READ.php
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

if (!isset($input['user_email'])) {
    echo json_encode(['success' => false, 'message' => 'Missing user email']);
    exit();
}

$user_email = $input['user_email'];
$admin_email = $_SESSION['email_account'];

// Update read status for messages from the specific user to admin
$sql = "UPDATE chat_box 
        SET read_status = 1 
        WHERE email_account = ? 
        AND recipient = ? 
        AND sender_type = 'user'
        AND read_status = 0";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ss", $user_email, $admin_email);
    $success = $stmt->execute();
    $stmt->close();
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Messages marked as read']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update read status']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$conn->close();
?>