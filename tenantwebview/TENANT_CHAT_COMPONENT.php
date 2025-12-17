<?php
// chatfunctions/TENANT_CHAT_COMPONENT.php - Reusable Tenant Chat Component
// Make sure this file is included after the main PHP logic and database connection

// If database connection is not set, include it
if (!isset($conn)) {
    require_once __DIR__ . '/../db_connect.php';
}

// Function to get admin email dynamically
function getAdminEmail($conn) {
    $sql = "SELECT email_account FROM accounts WHERE user_type = 'admin' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['email_account'];
    }
    return null; // Return null if no admin found
}

// Function to get unread messages count from admin
function getUnreadMessagesCount($conn, $user_email) {
    $admin_email = getAdminEmail($conn);
    if (!$admin_email) return 0;
    
    $sql = "SELECT COUNT(*) as unread_count 
            FROM chat_box 
            WHERE recipient = ? AND email_account = ? AND sender_type = 'admin' AND read_status = FALSE";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $user_email, $admin_email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['unread_count'] ?? 0;
    }
    return 0;
}

// Get unread count if user is logged in
$unreadCount = 0;
if (isset($_SESSION['email_account'])) {
    $unreadCount = getUnreadMessagesCount($conn, $_SESSION['email_account']);
}
?>

<!-- Chat Widget CSS -->
<style>
/* ===== TENANT CHAT COMPONENT CSS ===== */
#chat-fab {
    position: fixed;
    bottom: 25px;
    right: 25px;
    background: linear-gradient(135deg, #1e3c72, #2a5298);
    color: white;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(30, 60, 114, 0.4);
    z-index: 998;
    transition: all 0.3s ease;
    border: none;
}

#chat-fab:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 25px rgba(30, 60, 114, 0.6);
}

.chat-notification-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: linear-gradient(135deg, #ff4444, #cc0000);
    color: white;
    border-radius: 50%;
    min-width: 24px;
    height: 24px;
    font-size: 12px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2px 6px;
    box-shadow: 0 2px 8px rgba(255, 68, 68, 0.4);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

#chat-widget {
    position: fixed;
    bottom: 100px;
    right: 25px;
    width: 380px;
    max-width: 90vw;
    height: 500px;
    max-height: 80vh;
    background-color: #fff;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    display: flex;
    flex-direction: column;
    z-index: 999;
    overflow: hidden;
    transform: scale(0);
    transform-origin: bottom right;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

#chat-widget.open {
    transform: scale(1);
}

.chat-background {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0.05;
    z-index: -1;
}

.chat-header {
    display: flex;
    align-items: center;
    padding: 18px 20px;
    background: linear-gradient(135deg, #1e3c72, #2a5298);
    color: white;
    flex-shrink: 0;
}

.chat-header-logo {
    height: 40px;
    width: 45px;
    margin-right: 10px;
    border-radius: 30px;
}

.chat-header-text {
    flex-grow: 1;
}

.chat-header-text h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: white;
}

.chat-header-text p {
    margin: 0;
    font-size: 12px;
    color: rgba(255,255,255,0.8);
    position: relative;
    top: 3px;
}

.chat-header-controls {
    display: flex;
    align-items: center;
    gap: 10px;
}

.chat-close-btn {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: rgba(255,255,255,0.8);
    transition: color 0.3s;
    padding: 5px;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chat-close-btn:hover {
    color: white;
    background: rgba(255,255,255,0.1);
}

.chat-messages {
    flex-grow: 1;
    padding: 20px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 10px;
    background: #f8fafc;
}

.message-bubble {
    max-width: 80%;
    padding: 12px 16px;
    border-radius: 18px;
    line-height: 1.4;
    word-wrap: break-word;
}

.assistant-bubble {
    background-color: #e2e8f0;
    color: #334155;
    align-self: flex-start;
    border-bottom-left-radius: 4px;
}

.user-bubble {
    background: linear-gradient(135deg, #79B1FC, #4A90E2);
    color: #fff;
    align-self: flex-end;
    border-bottom-right-radius: 4px;
}

.chat-input-area {
    border-top: 1px solid #e2e8f0;
    padding: 15px;
    background-color: white;
    flex-shrink: 0;
}

.chat-input-area form {
    display: flex;
    align-items: center;
    gap: 10px;
}

#chat-input {
    flex-grow: 1;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 10px 15px;
    font-size: 14px;
    resize: none;
    transition: border-color 0.3s;
    outline: none;
}

#chat-input:focus {
    border-color: #79B1FC;
}

#chat-send-btn {
    background: linear-gradient(135deg, #79B1FC, #4A90E2);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px;
    border-radius: 50%;
    color: white;
    width: 40px;
    height: 40px;
    transition: all 0.3s;
}

#chat-send-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 15px rgba(121, 177, 252, 0.4);
}

/* Responsive adjustments */
@media screen and (max-width: 768px) {
    #chat-widget {
        width: 95vw;
        height: 80vh;
        bottom: 80px;
        right: 2.5vw;
    }

    #chat-fab {
        bottom: 15px;
        right: 15px;
        width: 50px;
        height: 50px;
        font-size: 24px;
    }
    
    .chat-notification-badge {
        top: -6px;
        right: -6px;
        min-width: 20px;
        height: 20px;
        font-size: 11px;
    }
}
</style>

<!-- Chat Widget HTML -->
<button id="chat-fab" title="Chat with Admin">
    <span class="material-symbols-outlined">mail</span>
    <?php if ($unreadCount > 0): ?>
        <span class="chat-notification-badge"><?php echo $unreadCount > 99 ? '99+' : $unreadCount; ?></span>
    <?php endif; ?>
</button>

<div id="chat-widget">
    <img src="../staticImages/userhomepagebg.png" class="chat-background" alt="background">
    <div class="chat-header">
        <img src="../otherIcons/systemLogo.png" alt="Logo" class="chat-header-logo">
        <div class="chat-header-text">
            <h3>RYC Dormitelle</h3>
            <p>Chat with Admin</p>
        </div>
        <div class="chat-header-controls">
            <button class="chat-close-btn" title="Close">×</button>
        </div>
    </div>
    <div class="chat-messages" id="chat-messages-container">
        <div class="message-bubble assistant-bubble">
            Hi there! Welcome to RYC Dormitelle.<br>How can we assist you today?
        </div>
    </div>
    <div class="chat-input-area">
        <form id="chat-form">
            <input type="text" id="chat-input" placeholder="Send us a message" autocomplete="off">
            <button type="submit" id="chat-send-btn">
                <span class="material-symbols-outlined">send</span>
            </button>
        </form>
    </div>
</div>

<!-- Chat Widget JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatFab = document.getElementById('chat-fab');
    const chatWidget = document.getElementById('chat-widget');
    
    if (chatFab && chatWidget) {
        const closeBtn = document.querySelector('.chat-close-btn');
        const chatForm = document.getElementById('chat-form');
        const chatInput = document.getElementById('chat-input');
        const messagesContainer = document.getElementById('chat-messages-container');

        const toggleChat = () => {
            chatWidget.classList.toggle('open');
            if(chatWidget.classList.contains('open')) {
                fetchMessages();
                markMessagesAsRead();
            }
        };

        const closeChat = () => {
            chatWidget.classList.remove('open');
        };

        // Event listeners
        chatFab.addEventListener('click', toggleChat);
        closeBtn.addEventListener('click', closeChat);

        const scrollToBottom = () => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        };
        
        const fetchMessages = async () => {
            try {
                const response = await fetch('TENANT_FETCH_MESSAGES.php');
                const messagesHtml = await response.text();
                
                const welcomeMessageHtml = '<div class="message-bubble assistant-bubble">Hi there! Welcome to RYC Dormitelle.<br>How can we assist you today?</div>';
                
                messagesContainer.innerHTML = welcomeMessageHtml + messagesHtml;
                scrollToBottom();
            } catch (error) {
                console.error('Error fetching messages:', error);
            }
        };

        const markMessagesAsRead = async () => {
            try {
                await fetch('TENANT_MARK_MESSAGES_READ.php', {
                    method: 'POST'
                });
                // Update the notification badge
                updateNotificationBadge();
            } catch (error) {
                console.error('Error marking messages as read:', error);
            }
        };

        const updateNotificationBadge = () => {
            const badge = chatFab.querySelector('.chat-notification-badge');
            if (badge) {
                badge.remove();
            }
        };

        const sendMessageToServer = async (messageText) => {
            if (!messageText.trim()) return;

            const userBubble = document.createElement('div');
            userBubble.className = 'message-bubble user-bubble';
            userBubble.textContent = messageText;
            messagesContainer.appendChild(userBubble);
            scrollToBottom();

            const formData = new FormData();
            formData.append('message', messageText);

            try {
                await fetch('TENANT_SEND_MESSAGES.php', {
                    method: 'POST',
                    body: formData
                });
                setTimeout(fetchMessages, 1000);
            } catch (error) {
                console.error('Error sending message:', error);
            }
        };

        chatForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const messageText = chatInput.value;
            sendMessageToServer(messageText);
            chatInput.value = '';
        });

        // Auto-refresh messages every 5 seconds when chat is open
        setInterval(() => {
            if (chatWidget.classList.contains('open')) {
                fetchMessages();
            }
        }, 5000);
    }
});
</script>