<?php
// chatfunctions/GET_CHAT_HISTORY.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['email_account'])) {
    echo '<p class="text-center text-danger">Not authenticated</p>';
    exit();
}

// Connect to database
require_once __DIR__ . '/../db_connect.php';

if (!isset($_GET['user_email'])) {
    echo '<p class="text-center text-danger">User email not provided</p>';
    exit();
}

$user_email = $_GET['user_email'];
$admin_email = $_SESSION['email_account'];

// Fetch chat history between admin and user
$sql = "SELECT email_account, recipient, message, sender_type, message_time_date, read_status
        FROM chat_box 
        WHERE (email_account = ? AND recipient = ?) 
           OR (email_account = ? AND recipient = ?)
        ORDER BY message_time_date ASC";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ssss", $user_email, $admin_email, $admin_email, $user_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $isAdmin = ($row['sender_type'] === 'admin');
            $messageClass = $isAdmin ? 'message-admin' : 'message-user';
            
            echo '<div class="chat-bubble ' . $messageClass . '">';
            echo htmlspecialchars($row['message']);
            echo '</div>';
        }
    } else {
        echo '<p class="text-center text-muted">No messages yet. Start the conversation!</p>';
    }
    
    $stmt->close();
} else {
    echo '<p class="text-center text-danger">Database error occurred</p>';
}

$conn->close();
?>