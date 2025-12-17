<?php
// chatfunctions/TENANT_FETCH_MESSAGES.php
session_start();
require_once __DIR__ . '/../db_connect.php';

if (!isset($_SESSION['email_account'])) {
    http_response_code(403);
    exit('User not authenticated');
}

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
    exit('Admin not found');
}

// Get the full two-way conversation
// For tenants: they are email_account when sending (sender_type='user') and recipient when receiving from admin
$sql = "SELECT message, sender_type, message_time_date
        FROM chat_box 
        WHERE (email_account = ? AND recipient = ? AND sender_type = 'user') 
           OR (email_account = ? AND recipient = ? AND sender_type = 'admin')
        ORDER BY message_time_date ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $user_email, $admin_email, $admin_email, $user_email);
$stmt->execute();
$result = $stmt->get_result();

$messages_html = '';
while ($row = $result->fetch_assoc()) {
    $message_text = htmlspecialchars($row['message']);
    $bubble_class = ($row['sender_type'] === 'user') ? 'user-bubble' : 'assistant-bubble';
    $messages_html .= "<div class='message-bubble {$bubble_class}'>{$message_text}</div>";
}

$stmt->close();
$conn->close();
echo $messages_html;
?>