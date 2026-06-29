<?php
require_once 'includes/init.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$conversation_id = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;

// Create tables if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_one INT NOT NULL,
    user_two INT NOT NULL,
    last_message TEXT,
    last_message_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users (user_one, user_two)
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT DEFAULT 0,
    delivered_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_conversation (conversation_id),
    INDEX idx_messages_users (sender_id, receiver_id)
)");

// Get all conversations with unread counts
$stmt = $pdo->prepare("SELECT c.*, 
                       CASE WHEN c.user_one = ? THEN u2.full_name ELSE u1.full_name END as other_user_name,
                       CASE WHEN c.user_one = ? THEN u2.id ELSE u1.id END as other_user_id,
                       CASE WHEN c.user_one = ? THEN u2.email ELSE u1.email END as other_user_email,
                       (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND receiver_id = ? AND is_read = 0) as unread_count
                       FROM conversations c
                       JOIN users u1 ON c.user_one = u1.id
                       JOIN users u2 ON c.user_two = u2.id
                       WHERE c.user_one = ? OR c.user_two = ?
                       ORDER BY c.last_message_time DESC");
$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
$conversations = $stmt->fetchAll();

$messages = [];
$other_user = null;

// Load messages if conversation is selected
if ($conversation_id > 0) {
    // Verify user has access to this conversation
    $stmt = $pdo->prepare("SELECT id FROM conversations WHERE id = ? AND (user_one = ? OR user_two = ?)");
    $stmt->execute([$conversation_id, $user_id, $user_id]);
    $has_access = $stmt->fetch();
    
    if ($has_access) {
        // Mark all unread messages as read
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1, read_at = NOW() 
                               WHERE conversation_id = ? AND receiver_id = ? AND is_read = 0");
        $stmt->execute([$conversation_id, $user_id]);
        
        // Get all messages for this conversation
        $stmt = $pdo->prepare("SELECT m.*, u.full_name as sender_name 
                               FROM messages m 
                               JOIN users u ON m.sender_id = u.id 
                               WHERE m.conversation_id = ? 
                               ORDER BY m.created_at ASC");
        $stmt->execute([$conversation_id]);
        $messages = $stmt->fetchAll();
        
        // Get other user info
        foreach ($conversations as $conv) {
            if ($conv['id'] == $conversation_id) {
                $other_user = [
                    'id' => $conv['other_user_id'],
                    'name' => $conv['other_user_name'],
                    'email' => $conv['other_user_email']
                ];
                break;
            }
        }
    } else {
        // Invalid conversation ID, reset to 0
        $conversation_id = 0;
    }
}

include 'includes/header.php';
?>

<style>
.messages-container {
    display: flex;
    height: calc(100vh - 180px);
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    margin: 30px 0;
}

/* Conversations Sidebar */
.conversations-sidebar {
    width: 350px;
    background: #f8f9fa;
    border-right: 1px solid #e0e0e0;
    display: flex;
    flex-direction: column;
}

.conversations-header {
    padding: 20px;
    background: var(--primary-color);
    color: white;
}

.conversations-header h3 {
    margin: 0;
    font-size: 1.2rem;
}

.conversations-header p {
    margin: 5px 0 0;
    font-size: 12px;
    opacity: 0.8;
}

.conversations-search {
    padding: 15px;
    border-bottom: 1px solid #e0e0e0;
}

.conversations-search input {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 25px;
    font-size: 14px;
    outline: none;
}

.conversations-list {
    flex: 1;
    overflow-y: auto;
}

.conversation-item {
    padding: 15px 20px;
    border-bottom: 1px solid #e0e0e0;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 12px;
}

.conversation-item:hover {
    background: #e8e8e8;
}

.conversation-item.active {
    background: white;
    border-left: 3px solid var(--secondary-color);
}

.conversation-avatar {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.conversation-avatar i {
    font-size: 24px;
    color: white;
}

.conversation-details {
    flex: 1;
    min-width: 0;
}

.conversation-name {
    font-weight: 600;
    margin-bottom: 5px;
    color: var(--primary-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.conversation-last-message {
    font-size: 12px;
    color: #666;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.unread-badge {
    background: var(--secondary-color);
    color: white;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
    margin-left: 8px;
}

/* Chat Area */
.chat-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #e5ddd5;
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="%23e5ddd5"/><circle cx="20" cy="20" r="2" fill="%23c4b9af"/><circle cx="60" cy="30" r="1.5" fill="%23c4b9af"/></svg>');
    background-repeat: repeat;
}

.chat-header {
    padding: 15px 20px;
    background: white;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
}

.chat-header-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.chat-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chat-avatar i {
    font-size: 20px;
    color: white;
}

.chat-header-info h3 {
    margin: 0;
    font-size: 1rem;
    color: var(--primary-color);
}

.chat-header-info p {
    margin: 2px 0 0;
    font-size: 11px;
    color: #666;
}

.messages-area {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.message {
    display: flex;
    flex-direction: column;
    max-width: 70%;
    animation: messageAppear 0.2s ease;
    position: relative;
}

.message:hover .message-actions {
    display: flex;
}

@keyframes messageAppear {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.message.sent {
    align-self: flex-end;
}

.message.received {
    align-self: flex-start;
}

.message-bubble {
    padding: 10px 12px;
    border-radius: 18px;
    word-wrap: break-word;
    position: relative;
}

.message.sent .message-bubble {
    background: #dcf8c5;
    color: #333;
    border-bottom-right-radius: 4px;
}

.message.received .message-bubble {
    background: white;
    color: #333;
    border-bottom-left-radius: 4px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.05);
}

.message-time {
    font-size: 10px;
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 5px;
    justify-content: flex-end;
}

.message-status {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    margin-left: 5px;
}

.status-read { color: #34b7f1; }
.status-delivered { color: #9e9e9e; }
.status-sent { color: #9e9e9e; }

.message-actions {
    position: absolute;
    right: 0;
    top: -25px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: none;
    gap: 5px;
    padding: 5px;
    z-index: 10;
}

.message-action-btn {
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 5px 8px;
    border-radius: 50%;
    transition: background 0.2s;
}

.message-action-btn:hover {
    background: #f0f0f0;
}

/* Reply Preview */
.reply-preview {
    background: #f8f9fa;
    padding: 10px 15px;
    border-left: 3px solid var(--secondary-color);
    margin: 0 20px;
    border-radius: 8px;
    display: none;
    align-items: center;
    justify-content: space-between;
}

.reply-preview.show {
    display: flex;
}

.reply-content {
    flex: 1;
}

.reply-sender {
    font-size: 12px;
    font-weight: 600;
    color: var(--secondary-color);
}

.reply-text {
    font-size: 12px;
    color: #666;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cancel-reply {
    cursor: pointer;
    color: #999;
    padding: 5px;
}

/* Input Area */
.message-input-area {
    padding: 15px 20px;
    background: white;
    border-top: 1px solid #e0e0e0;
    display: flex;
    gap: 10px;
    align-items: center;
    flex-shrink: 0;
}

.message-input {
    flex: 1;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 25px;
    resize: none;
    font-family: inherit;
    font-size: 14px;
    max-height: 100px;
}

.message-input:focus {
    outline: none;
    border-color: var(--secondary-color);
}

.btn-send {
    width: 45px;
    height: 45px;
    background: var(--secondary-color);
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-send:hover {
    background: #c0392b;
    transform: scale(1.05);
}

/* Emoji Picker */
.emoji-btn {
    width: 40px;
    height: 40px;
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 20px;
    border-radius: 50%;
    transition: all 0.3s;
}

.emoji-btn:hover {
    background: #f0f0f0;
    transform: scale(1.1);
}

.emoji-picker {
    position: absolute;
    bottom: 70px;
    left: 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.15);
    width: 300px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
    padding: 10px;
}

.emoji-picker.show {
    display: block;
}

.emoji-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.emoji-item {
    font-size: 24px;
    cursor: pointer;
    padding: 5px;
    transition: transform 0.2s;
    border-radius: 8px;
}

.emoji-item:hover {
    transform: scale(1.2);
    background: #f0f0f0;
}

.typing-indicator {
    padding: 10px 20px;
    font-size: 12px;
    color: #666;
    font-style: italic;
    display: none;
}

.typing-indicator.show {
    display: block;
}

.empty-chat {
    text-align: center;
    padding: 60px;
    color: #999;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
}

.empty-chat i {
    font-size: 60px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-conversations {
    text-align: center;
    padding: 40px;
    color: #999;
}

/* Scrollbar */
.messages-area::-webkit-scrollbar { width: 6px; }
.messages-area::-webkit-scrollbar-track { background: #f1f1f1; }
.messages-area::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 3px; }

@media (max-width: 768px) {
    .messages-container { flex-direction: column; height: auto; }
    .conversations-sidebar { width: 100%; max-height: 300px; }
    .message { max-width: 85%; }
}
</style>

<div class="container">
    <div class="messages-container">
        <!-- Conversations Sidebar -->
        <div class="conversations-sidebar">
            <div class="conversations-header">
                <h3><i class="fas fa-comments"></i> Chats</h3>
                <p><?php echo count($conversations); ?> conversations</p>
            </div>
            <div class="conversations-search">
                <input type="text" id="searchConversation" placeholder="Search chats..." onkeyup="filterConversations()">
            </div>
            <div class="conversations-list">
                <?php if(empty($conversations)): ?>
                    <div class="empty-conversations">
                        <i class="fas fa-inbox"></i>
                        <p>No conversations yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach($conversations as $conv): ?>
                        <div class="conversation-item <?php echo ($conversation_id == $conv['id']) ? 'active' : ''; ?>" 
                             onclick="window.location.href='messages.php?conversation_id=<?php echo $conv['id']; ?>'">
                            <div class="conversation-avatar">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <div class="conversation-details">
                                <div class="conversation-name">
                                    <?php echo htmlspecialchars($conv['other_user_name']); ?>
                                    <?php if($conv['unread_count'] > 0): ?>
                                        <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="conversation-last-message">
                                    <?php echo htmlspecialchars(substr($conv['last_message'] ?? 'No messages', 0, 50)); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Chat Area -->
        <div class="chat-area">
            <?php if($conversation_id > 0 && $other_user): ?>
                <div class="chat-header">
                    <div class="chat-header-info">
                        <div class="chat-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div>
                            <h3><?php echo htmlspecialchars($other_user['name']); ?></h3>
                            <p><?php echo htmlspecialchars($other_user['email']); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Reply Preview -->
                <div class="reply-preview" id="replyPreview">
                    <div class="reply-content">
                        <div class="reply-sender">Replying to:</div>
                        <div class="reply-text" id="replyText"></div>
                    </div>
                    <div class="cancel-reply" onclick="cancelReply()">✕</div>
                </div>
                
                <div class="messages-area" id="messagesArea">
                    <?php if(empty($messages)): ?>
                        <div class="empty-chat">
                            <i class="fas fa-comment-dots"></i>
                            <h3>No messages yet</h3>
                            <p>Send a message to start the conversation</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($messages as $msg): ?>
                            <div class="message <?php echo $msg['sender_id'] == $user_id ? 'sent' : 'received'; ?>" 
                                 data-message-id="<?php echo $msg['id']; ?>"
                                 data-message-text="<?php echo htmlspecialchars($msg['message']); ?>"
                                 data-sender-name="<?php echo htmlspecialchars($msg['sender_name']); ?>">
                                <div class="message-bubble">
                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                </div>
                                <div class="message-time">
                                    <?php echo date('H:i', strtotime($msg['created_at'])); ?>
                                    <?php if($msg['sender_id'] == $user_id): ?>
                                        <span class="message-status">
                                            <?php if($msg['is_read'] && $msg['read_at']): ?>
                                                <i class="fas fa-check-double" style="color: #34b7f1;" title="Read"></i>
                                            <?php elseif($msg['delivered_at']): ?>
                                                <i class="fas fa-check-double" style="color: #9e9e9e;" title="Delivered"></i>
                                            <?php else: ?>
                                                <i class="fas fa-check" style="color: #9e9e9e;" title="Sent"></i>
                                            <?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="message-actions">
                                    <button class="message-action-btn" onclick="replyToMessage(<?php echo $msg['id']; ?>, '<?php echo addslashes($msg['sender_name']); ?>', '<?php echo addslashes(substr($msg['message'], 0, 60)); ?>')" title="Reply">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="typing-indicator" id="typingIndicator">
                    <i class="fas fa-ellipsis-h"></i> <?php echo htmlspecialchars($other_user['name']); ?> is typing...
                </div>
                
                <div class="message-input-area">
                    <div style="position: relative;">
                        <button class="emoji-btn" onclick="toggleEmojiPicker()">😊</button>
                        <div class="emoji-picker" id="emojiPicker">
                            <div class="emoji-list">
                                <span class="emoji-item" onclick="addEmoji('😀')">😀</span>
                                <span class="emoji-item" onclick="addEmoji('😂')">😂</span>
                                <span class="emoji-item" onclick="addEmoji('🥰')">🥰</span>
                                <span class="emoji-item" onclick="addEmoji('😍')">😍</span>
                                <span class="emoji-item" onclick="addEmoji('👍')">👍</span>
                                <span class="emoji-item" onclick="addEmoji('❤️')">❤️</span>
                                <span class="emoji-item" onclick="addEmoji('🎉')">🎉</span>
                                <span class="emoji-item" onclick="addEmoji('🔥')">🔥</span>
                            </div>
                        </div>
                    </div>
                    <textarea class="message-input" id="messageInput" rows="1" placeholder="Type a message..."></textarea>
                    <button class="btn-send" onclick="sendMessage()">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            <?php else: ?>
                <div class="empty-chat">
                    <i class="fas fa-comments"></i>
                    <h3>Select a conversation</h3>
                    <p>Choose a conversation from the left to start messaging</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
var conversationId = <?php echo $conversation_id; ?>;
var userId = <?php echo $user_id; ?>;
var otherUserId = <?php echo $other_user['id'] ?? 0; ?>;
var replyToId = null;
var typingTimeout;

// Load messages
function loadMessages() {
    if (!conversationId) return;
    
    $.ajax({
        url: 'ajax/get-messages-with-status.php',
        method: 'GET',
        data: { conversation_id: conversationId },
        cache: false,
        success: function(response) {
            if (response) {
                $('#messagesArea').html(response);
                scrollToBottom();
                markMessagesAsRead();
                // Re-attach reply functionality to new messages
                attachReplyHandlers();
            }
        }
    });
}

// Attach reply handlers to dynamically loaded messages
function attachReplyHandlers() {
    $('.message-action-btn').off('click').on('click', function(e) {
        e.stopPropagation();
        var messageDiv = $(this).closest('.message');
        var msgId = messageDiv.data('message-id');
        var msgText = messageDiv.data('message-text');
        var senderName = messageDiv.data('sender-name');
        replyToMessage(msgId, senderName, msgText);
    });
}

// Send message
function sendMessage() {
    var message = $('#messageInput').val().trim();
    if (!message) {
        showNotification('error', 'Please enter a message');
        return;
    }
    
    $('.btn-send').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    
    $.ajax({
        url: 'ajax/send-message.php',
        method: 'POST',
        data: {
            receiver_id: otherUserId,
            message: message,
            conversation_id: conversationId,
            reply_to_id: replyToId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                if (conversationId == 0 && response.conversation_id) {
                    conversationId = response.conversation_id;
                    window.history.pushState({}, '', 'messages.php?conversation_id=' + conversationId);
                }
                $('#messageInput').val('');
                cancelReply();
                loadMessages();
                scrollToBottom();
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Failed to send message');
        },
        complete: function() {
            $('.btn-send').prop('disabled', false).html('<i class="fas fa-paper-plane"></i>');
        }
    });
}

// Reply to message
function replyToMessage(messageId, senderName, messageText) {
    replyToId = messageId;
    $('#replyText').html('<strong>' + senderName + ':</strong> ' + messageText);
    $('#replyPreview').addClass('show');
    $('#messageInput').focus();
}

function cancelReply() {
    replyToId = null;
    $('#replyPreview').removeClass('show');
    $('#replyText').html('');
}

// Mark messages as read
function markMessagesAsRead() {
    $.ajax({
        url: 'ajax/mark-messages-read.php',
        method: 'POST',
        data: { conversation_id: conversationId }
    });
}

// Typing indicator
$('#messageInput').on('input', function() {
    clearTimeout(typingTimeout);
    $.ajax({
        url: 'ajax/typing-indicator.php',
        method: 'POST',
        data: { receiver_id: otherUserId, is_typing: true }
    });
    typingTimeout = setTimeout(function() {
        $.ajax({
            url: 'ajax/typing-indicator.php',
            method: 'POST',
            data: { receiver_id: otherUserId, is_typing: false }
        });
    }, 1000);
});

function checkTyping() {
    $.ajax({
        url: 'ajax/check-typing.php',
        method: 'GET',
        data: { sender_id: otherUserId },
        success: function(response) {
            $('#typingIndicator').toggleClass('show', response === 'typing');
        }
    });
}

// Emoji functions
function toggleEmojiPicker() {
    $('#emojiPicker').toggleClass('show');
}

function addEmoji(emoji) {
    var input = $('#messageInput');
    input.val(input.val() + emoji);
    input.focus();
    $('#emojiPicker').removeClass('show');
}

// Filter conversations
function filterConversations() {
    var search = $('#searchConversation').val().toLowerCase();
    $('.conversation-item').each(function() {
        var name = $(this).find('.conversation-name').text().toLowerCase();
        $(this).toggle(name.indexOf(search) > -1);
    });
}

// Scroll to bottom
function scrollToBottom() {
    var messagesArea = $('#messagesArea');
    if (messagesArea.length) {
        messagesArea.scrollTop(messagesArea[0].scrollHeight);
    }
}

// Send on Enter
$('#messageInput').on('keypress', function(e) {
    if (e.which === 13 && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

// Close emoji picker on outside click
$(document).on('click', function(e) {
    if (!$(e.target).closest('.emoji-picker, .emoji-btn').length) {
        $('#emojiPicker').removeClass('show');
    }
});

// Initial load
$(document).ready(function() {
    if (conversationId > 0) {
        loadMessages();
        scrollToBottom();
        setInterval(loadMessages, 3000);
        setInterval(checkTyping, 2000);
    }
    attachReplyHandlers();
});

function showNotification(type, message) {
    if (typeof toastr !== 'undefined') {
        if (type === 'success') toastr.success(message);
        else if (type === 'error') toastr.error(message);
        else toastr.info(message);
    } else {
        alert(message);
    }
}
</script>

<?php include 'includes/footer.php'; ?>