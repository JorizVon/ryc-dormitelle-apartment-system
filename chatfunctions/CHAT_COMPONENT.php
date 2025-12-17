<?php
// chatfunctions/CHAT_COMPONENT.php - Reusable Chat Component
// Make sure this file is included after the main PHP logic that sets $chatListData

// If $chatListData is not set, fetch it here
if (!isset($chatListData)) {
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
}
?>

<!-- Chat functionality CSS dependency -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
/* ===== CHAT FUNCTIONALITY CSS ===== */
.chat-toggle-button {
    position: fixed;
    bottom: 25px;
    right: 25px;
    width: 60px;
    height: 60px;
    background-color: #01214B;
    color: white;
    border-radius: 50%;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    cursor: pointer;
    z-index: 1040;
    transition: background-color 0.3s;
}

.chat-toggle-button:hover {
    background-color: #004AAD;
}

.chat-notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: #ff4444;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.chat-popup {
    display: none;
    position: fixed;
    bottom: 100px;
    right: 25px;
    width: 370px;
    height: 500px;
    background-color: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    z-index: 1050;
    flex-direction: column;
    border: 1px solid #ddd;
    overflow: hidden;
}

.chat-popup.show {
    display: flex;
}

.chat-list-view .chat-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-list-view .chat-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #01214B;
}

.chat-close-btn {
    background: none;
    border: none;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
    color: #555;
    padding: 0 5px;
}

.chat-close-btn:hover {
    color: #000;
}

.chat-body {
    padding: 0 10px 10px 10px;
    overflow-y: auto;
    flex-grow: 1;
}

.chat-item {
    display: flex;
    align-items: center;
    padding: 10px;
    cursor: pointer;
    border-radius: 8px;
    margin-top: 5px;
    position: relative;
}

.chat-item:hover {
    background-color: #f1f1f1;
}

.chat-item.unread {
    background-color: #f8f9ff;
}

.chat-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #004AAD;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    font-weight: bold;
    flex-shrink: 0;
}

.chat-info {
    flex-grow: 1;
    overflow: hidden;
}

.chat-name {
    font-weight: 700;
    font-size: 15px;
    color: #333;
    margin: 0;
}

.chat-name.unread {
    font-weight: 900;
}

.chat-message {
    font-size: 14px;
    color: #666;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin: 0;
}

.chat-message.unread {
    font-weight: bold;
    color: #333;
}

.unread-indicator {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 12px;
    height: 12px;
    background-color: #007bff;
    border-radius: 50%;
}

.chat-conversation-view {
    display: none;
    flex-direction: column;
    height: 100%;
}

.chat-conversation-header {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    border-bottom: 1px solid #eee;
    flex-shrink: 0;
}

.back-button {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    margin-right: 10px;
    color: #555;
}

.chat-conversation-header .chat-avatar {
    width: 35px;
    height: 35px;
    margin-right: 10px;
}

.chat-conversation-header .chat-name {
    font-size: 16px;
    font-weight: 700;
}

.chat-messages-container {
    flex-grow: 1;
    overflow-y: auto;
    padding: 15px;
    display: flex;
    flex-direction: column;
}

.chat-bubble {
    max-width: 80%;
    margin: 8px 12px;
    padding: 10px 14px;
    border-radius: 10px;
    white-space: pre-wrap;
    word-wrap: break-word;
    line-height: 1.4;
    font-family: Arial, sans-serif;
}

.message-admin {
    background-color: #004AAD;
    color: white;
    align-self: flex-end;
    border-bottom-right-radius: 4px;
    margin-left: auto;
}

.message-user {
    background-color: #E9E9EB;
    color: #000;
    align-self: flex-start;
    border-bottom-left-radius: 4px;
}

.chat-input-form {
    display: flex;
    padding: 10px;
    border-top: 1px solid #eee;
    flex-shrink: 0;
}

.chat-input-form input[type="text"] {
    flex-grow: 1;
    border: 1px solid #ccc;
    border-radius: 20px;
    padding: 8px 15px;
    outline: none;
}

.chat-input-form button {
    background-color: #01214B;
    color: white;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    margin-left: 10px;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Responsive adjustments */
@media (max-width: 480px) {
    .chat-popup {
        width: 90vw;
        right: 5vw;
        height: 70vh;
    }
    
    .chat-toggle-button {
        right: 15px;
        bottom: 15px;
        width: 50px;
        height: 50px;
        font-size: 24px;
    }
}
</style>

<!-- ===== CHAT FUNCTIONALITY HTML ===== -->
<?php 
// Calculate total unread messages
$totalUnread = 0;
foreach ($chatListData as $chat) {
    $totalUnread += $chat['unread_count'];
}
?>

<button class="chat-toggle-button" id="chatToggleButton" title="Open Messages">
    <i class="bi bi-envelope-fill"></i>
    <?php if ($totalUnread > 0): ?>
        <span class="chat-notification-badge"><?php echo $totalUnread > 99 ? '99+' : $totalUnread; ?></span>
    <?php endif; ?>
</button>

<div class="chat-popup" id="chatPopup">
    <div class="chat-list-view" id="chatListView">
        <div class="chat-header">
            <h3>Messages</h3>
            <button type="button" class="chat-close-btn" id="closeChatButton" title="Close">×</button>
        </div>
        <div class="chat-body">
            <?php if (!empty($chatListData)): ?>
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
            <?php endif; ?>
        </div>
    </div>
    
    <div class="chat-conversation-view" id="chatConversationView">
        <div class="chat-conversation-header">
            <button class="back-button" id="backToChatList">
                <i class="bi bi-arrow-left"></i>
            </button>
            <div class="chat-avatar" id="conversationAvatar"></div>
            <div class="chat-name" id="conversationName"></div>
        </div>
        <div class="chat-messages-container" id="chatMessagesContainer"></div>
        <form class="chat-input-form" id="chatMessageForm">
            <input type="hidden" id="conversationTargetEmail" name="recipient">
            <input type="text" id="messageInput" name="message" placeholder="Send a message" autocomplete="off" required>
            <button type="submit">
                <i class="bi bi-send-fill"></i>
            </button>
        </form>
    </div>
</div>

<!-- ===== CHAT FUNCTIONALITY JAVASCRIPT ===== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Element Variables ---
    const chatPopup = document.getElementById('chatPopup');
    const chatToggleButton = document.getElementById('chatToggleButton');
    const closeChatButton = document.getElementById('closeChatButton');
    const chatListView = document.getElementById('chatListView');
    const chatConversationView = document.getElementById('chatConversationView');
    const backToChatListButton = document.getElementById('backToChatList');
    const chatItems = document.querySelectorAll('.chat-item');
    const conversationName = document.getElementById('conversationName');
    const conversationAvatar = document.getElementById('conversationAvatar');
    const messagesContainer = document.getElementById('chatMessagesContainer');
    const messageForm = document.getElementById('chatMessageForm');
    const messageInput = document.getElementById('messageInput');
    const targetEmailInput = document.getElementById('conversationTargetEmail');

    // --- Real-Time Polling Variable ---
    let chatInterval = null;

    // --- Helper Function ---
    const scrollToBottom = () => {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    };
    
    // --- Function to mark messages as read ---
    const markMessagesAsRead = (userEmail) => {
        fetch('chatfunctions/MARK_MESSAGES_READ.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_email: userEmail })
        })
        .catch(error => console.error("Failed to mark messages as read:", error));
    };
    
    // --- Function to update chat list item read status ---
    const updateChatItemReadStatus = (userEmail) => {
        const chatItem = document.querySelector(`[data-email="${userEmail}"]`);
        if (chatItem) {
            chatItem.classList.remove('unread');
            chatItem.dataset.unread = '0';
            
            const chatName = chatItem.querySelector('.chat-name');
            const chatMessage = chatItem.querySelector('.chat-message');
            const unreadIndicator = chatItem.querySelector('.unread-indicator');
            
            if (chatName) chatName.classList.remove('unread');
            if (chatMessage) chatMessage.classList.remove('unread');
            if (unreadIndicator) unreadIndicator.remove();
        }
        
        // Update notification badge
        updateNotificationBadge();
    };
    
    // --- Function to update notification badge ---
    const updateNotificationBadge = () => {
        let totalUnread = 0;
        document.querySelectorAll('.chat-item[data-unread]').forEach(item => {
            totalUnread += parseInt(item.dataset.unread || '0');
        });
        
        let badge = chatToggleButton.querySelector('.chat-notification-badge');
        if (totalUnread > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'chat-notification-badge';
                chatToggleButton.appendChild(badge);
            }
            badge.textContent = totalUnread > 99 ? '99+' : totalUnread;
        } else if (badge) {
            badge.remove();
        }
    };
    
    // --- Function to fetch and update chat content ---
    const refreshChat = (userEmail) => {
        fetch(`chatfunctions/GET_CHAT_HISTORY.php?user_email=${encodeURIComponent(userEmail)}`)
            .then(response => response.text())
            .then(html => {
                const currentScrollHeight = messagesContainer.scrollHeight;
                messagesContainer.innerHTML = html;
                if (messagesContainer.scrollHeight > currentScrollHeight) {
                    scrollToBottom();
                }
            })
            .catch(error => {
                console.error("Failed to refresh chat:", error);
                messagesContainer.innerHTML = '<p class="text-center text-danger">Could not load chat.</p>';
            });
    };

    // --- Function to refresh chat list ---
    const refreshChatList = () => {
        fetch('chatfunctions/GET_CHAT_LIST.php')
            .then(response => response.text())
            .then(html => {
                const chatBody = document.querySelector('.chat-body');
                chatBody.innerHTML = html;
                
                // Reattach event listeners to new chat items
                document.querySelectorAll('.chat-item').forEach(item => {
                    item.addEventListener('click', handleChatItemClick);
                });
                
                updateNotificationBadge();
            })
            .catch(error => console.error("Failed to refresh chat list:", error));
    };

    // --- Handle chat item click ---
    const handleChatItemClick = function() {
        if (chatInterval) clearInterval(chatInterval);

        const userEmail = this.dataset.email;
        const hasUnread = parseInt(this.dataset.unread || '0') > 0;
        
        targetEmailInput.value = userEmail;
        conversationName.textContent = userEmail;
        conversationAvatar.innerHTML = `<span>${userEmail.charAt(0).toUpperCase()}</span>`;
        
        chatListView.style.display = 'none';
        chatConversationView.style.display = 'flex';
        messageInput.focus();

        // Mark messages as read if there are unread messages
        if (hasUnread) {
            markMessagesAsRead(userEmail);
            updateChatItemReadStatus(userEmail);
        }

        refreshChat(userEmail);
        chatInterval = setInterval(() => refreshChat(userEmail), 2500);
    };

    // --- Event Listeners ---
    const toggleChatPopup = (show) => {
        if (show) { 
            chatPopup.classList.add('show'); 
        } else { 
            chatPopup.classList.remove('show');
            if (chatInterval) clearInterval(chatInterval);
            setTimeout(() => { 
                chatListView.style.display = 'block'; 
                chatConversationView.style.display = 'none'; 
            }, 200);
        }
    };

    chatToggleButton.addEventListener('click', (e) => { 
        e.stopPropagation(); 
        toggleChatPopup(!chatPopup.classList.contains('show')); 
    });

    closeChatButton.addEventListener('click', () => toggleChatPopup(false));

    document.addEventListener('click', (e) => { 
        if (!chatPopup.contains(e.target) && !chatToggleButton.contains(e.target)) {
            toggleChatPopup(false); 
        }
    });

    chatPopup.addEventListener('click', (e) => e.stopPropagation());

    // Clicking on a user in the list
    chatItems.forEach(item => {
        item.addEventListener('click', handleChatItemClick);
    });

    // Back button
    backToChatListButton.addEventListener('click', () => {
        if (chatInterval) clearInterval(chatInterval);
        chatConversationView.style.display = 'none';
        chatListView.style.display = 'block';
        targetEmailInput.value = '';
        
        // Refresh chat list when going back to show updated read status
        refreshChatList();
    });

    // Message form submission
    messageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const messageText = messageInput.value.trim();
        const targetEmail = targetEmailInput.value;
        if (messageText === '' || targetEmail === '') return;

        const originalInputValue = messageText;
        messageInput.value = '';

        const payload = { recipient: targetEmail, message: messageText };
        fetch('chatfunctions/SEND_MESSAGE.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify(payload) 
        })
        .then(response => response.json())
        .then(result => { 
            if (result.success) {
                refreshChat(targetEmail);
            } else {
                messageInput.value = originalInputValue; 
                alert('Failed to send message.'); 
            }
        })
        .catch(error => { 
            messageInput.value = originalInputValue; 
            alert('A network error occurred.'); 
        });
    });

    // Refresh chat list every 10 seconds to check for new messages
    setInterval(refreshChatList, 10000);
});
</script>