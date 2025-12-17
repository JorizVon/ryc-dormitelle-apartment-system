<?php
// chatfunctions/TENANT_MARK_MESSAGES_READ.php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['email_account'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Connect to database
require_once __DIR__ . '/../db_connect.php';

$user_email = $_SESSION['email_account'];

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
    echo json_encode(['success' => false, 'message' => 'Admin not found']);
    exit();
}

// Update read status for messages from admin to user (where tenant is recipient)
$sql = "UPDATE chat_box 
        SET read_status = TRUE 
        WHERE email_account = ? 
        AND recipient = ? 
        AND sender_type = 'admin'
        AND read_status = FALSE";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ss", $admin_email, $user_email);
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