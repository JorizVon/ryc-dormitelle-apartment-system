<?php
// chatfunctions/GET_CHAT_LIST.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['email_account'])) {
    echo '<p class="text-center p-4 text-muted">Not authenticated.</p>';
    exit();
}

// Connect to database
require_once __DIR__ . '/../db_connect.php';

function getChatListData($conn) {
    $chats = [];
    if (!isset($_SESSION['email_account'])) {
        return $chats;
    }
    $admin_email = $_SESSION['email_account'];
    
    // Get all unique users that have chatted with admin, with their latest message and unread count
    $sql = "SELECT 
                user_conversations.user_email as email_account,
                latest_messages.message,
                latest_messages.sender_type,
                latest_messages.message_time_date,
                COALESCE(unread_counts.unread_count, 0) as unread_count
            FROM (
                SELECT DISTINCT 
                    CASE 
                        WHEN sender_type = 'user' AND recipient = ? THEN email_account
                        WHEN sender_type = 'admin' AND email_account = ? THEN recipient
                    END as user_email
                FROM chat_box 
                WHERE ((sender_type = 'user' AND recipient = ?) OR (sender_type = 'admin' AND email_account = ?))
                AND CASE 
                    WHEN sender_type = 'user' AND recipient = ? THEN email_account
                    WHEN sender_type = 'admin' AND email_account = ? THEN recipient
                END IS NOT NULL
            ) as user_conversations
            INNER JOIN (
                SELECT 
                    CASE 
                        WHEN sender_type = 'user' AND recipient = ? THEN email_account
                        WHEN sender_type = 'admin' AND email_account = ? THEN recipient
                    END as user_email,
                    message,
                    sender_type,
                    message_time_date,
                    ROW_NUMBER() OVER (
                        PARTITION BY CASE 
                            WHEN sender_type = 'user' AND recipient = ? THEN email_account
                            WHEN sender_type = 'admin' AND email_account = ? THEN recipient
                        END 
                        ORDER BY message_time_date DESC
                    ) as rn
                FROM chat_box 
                WHERE ((sender_type = 'user' AND recipient = ?) OR (sender_type = 'admin' AND email_account = ?))
            ) as latest_messages ON user_conversations.user_email = latest_messages.user_email AND latest_messages.rn = 1
            LEFT JOIN (
                SELECT 
                    email_account as user_email,
                    COUNT(*) as unread_count
                FROM chat_box 
                WHERE recipient = ? AND read_status = 0 AND sender_type = 'user'
                GROUP BY email_account
            ) as unread_counts ON user_conversations.user_email = unread_counts.user_email
            ORDER BY latest_messages.message_time_date DESC";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sssssssssssss", 
            $admin_email, $admin_email, $admin_email, $admin_email,
            $admin_email, $admin_email, $admin_email, $admin_email,
            $admin_email, $admin_email, $admin_email, $admin_email,
            $admin_email
        );
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $chats[] = $row;
        }
        $stmt->close();
    } else {
        error_log("Chat list query failed: " . $conn->error);
    }
    return $chats;
}

$chatListData = getChatListData($conn);

// Generate HTML for chat list
if (!empty($chatListData)): ?>
    <?php foreach ($chatListData as $chat): ?>
        <div class="chat-item <?php echo $chat['unread_count'] > 0 ? 'unread' : ''; ?>" 
             data-email="<?php echo htmlspecialchars($chat['email_account']); ?>"
             data-unread="<?php echo $chat['unread_count']; ?>">
            <div class="chat-avatar">
                <span><?php echo strtoupper(substr(htmlspecialchars($chat['email_account']), 0, 1)); ?></span>
            </div>
            <div class="chat-info">
                <p class="chat-name <?php echo $chat['unread_count'] > 0 ? 'unread' : ''; ?>">
                    <?php echo htmlspecialchars($chat['email_account']); ?>
                </p>
                <p class="chat-message <?php echo $chat['unread_count'] > 0 ? 'unread' : ''; ?>">
                    <?php echo htmlspecialchars($chat['message']); ?>
                </p>
            </div>
            <?php if ($chat['unread_count'] > 0): ?>
                <div class="unread-indicator"></div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p class="text-center p-4 text-muted">No messages found.</p>
<?php endif;

$conn->close();
?>