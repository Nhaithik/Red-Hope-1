<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'blood_donation_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$active_chat = isset($_GET['to']) ? (int) $_GET['to'] : null;

// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message']) && $active_chat) {
    $msg_text = trim($conn->real_escape_string($_POST['message']));
    if ($msg_text) {
        $conn->query("INSERT INTO messages (sender_id, receiver_id, message) VALUES ($user_id, $active_chat, '$msg_text')");
        // Notify receiver
        $sender_email_r = $conn->query("SELECT email FROM users WHERE id = $user_id");
        $sender_email = $sender_email_r->fetch_assoc()['email'];
        $sender_name = ucfirst(explode('@', $sender_email)[0]);
        $notif_msg = "New message from $sender_name";
        $conn->query("INSERT INTO notifications (user_id, message) VALUES ($active_chat, '$notif_msg')");
    }
    // Mark incoming messages from active_chat as read
    $conn->query("UPDATE messages SET is_read = true WHERE sender_id = $active_chat AND receiver_id = $user_id");
    header("Location: messages.php?to=$active_chat");
    exit();
}

// Mark messages as read when opening a chat
if ($active_chat) {
    $conn->query("UPDATE messages SET is_read = true WHERE sender_id = $active_chat AND receiver_id = $user_id");
}

// Get all conversations (distinct users this person has chatted with)
$convos = [];
$res = $conn->query("
    SELECT DISTINCT u.id, u.email,
        (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = $user_id AND is_read = false) AS unread_count,
        (SELECT message FROM messages WHERE (sender_id = u.id AND receiver_id = $user_id) OR (sender_id = $user_id AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) AS last_message,
        (SELECT created_at FROM messages WHERE (sender_id = u.id AND receiver_id = $user_id) OR (sender_id = $user_id AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) AS last_time
    FROM users u
    WHERE u.id IN (
        SELECT CASE WHEN sender_id = $user_id THEN receiver_id ELSE sender_id END
        FROM messages WHERE sender_id = $user_id OR receiver_id = $user_id
    )
    ORDER BY last_time DESC
");
while ($row = $res->fetch_assoc()) {
    $row['display_name'] = ucfirst(explode('@', $row['email'])[0]);
    $convos[] = $row;
}

// Start a new conversation if ?to= is set but no history yet
if ($active_chat && !in_array($active_chat, array_column($convos, 'id'))) {
    $r = $conn->query("SELECT id, email FROM users WHERE id = $active_chat LIMIT 1");
    if ($partner = $r->fetch_assoc()) {
        $partner['display_name'] = ucfirst(explode('@', $partner['email'])[0]);
        $partner['unread_count'] = 0;
        $partner['last_message'] = null;
        array_unshift($convos, $partner);
    }
}

// Get chat history with active partner
$chat_history = [];
$partner = null;
if ($active_chat) {
    $pr = $conn->query("SELECT id, email FROM users WHERE id = $active_chat LIMIT 1");
    if ($pr)
        $partner = $pr->fetch_assoc();
    if ($partner)
        $partner['display_name'] = ucfirst(explode('@', $partner['email'])[0]);

    $res = $conn->query("
        SELECT m.*, u.email as sender_email
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = $user_id AND m.receiver_id = $active_chat)
           OR (m.sender_id = $active_chat AND m.receiver_id = $user_id)
        ORDER BY m.created_at ASC
    ");
    while ($row = $res->fetch_assoc()) {
        $chat_history[] = $row;
    }
}

$page_title = "Red Hope | Messages";
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<style>
    .messages-layout {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 20px;
        height: 75vh;
    }

    .conversations-panel {
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border-radius: 20px;
    }

    .convo-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 15px;
        cursor: pointer;
        text-decoration: none;
        color: var(--text-color);
        border-bottom: 1px solid var(--border-color);
        transition: background 0.2s;
    }

    .convo-item:hover,
    .convo-item.active {
        background: var(--hover-bg);
    }

    .convo-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        flex-shrink: 0;
    }

    .chat-panel {
        display: flex;
        flex-direction: column;
        border-radius: 20px;
        overflow: hidden;
    }

    .chat-header {
        padding: 15px 20px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--card-bg);
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        background: var(--card-bg);
    }

    .bubble {
        max-width: 70%;
        padding: 10px 15px;
        border-radius: 18px;
        font-size: 0.95rem;
        line-height: 1.4;
    }

    .bubble.sent {
        background: var(--primary-color);
        color: white;
        align-self: flex-end;
        border-bottom-right-radius: 4px;
    }

    .bubble.received {
        background: var(--border-color);
        color: var(--text-color);
        align-self: flex-start;
        border-bottom-left-radius: 4px;
    }

    .bubble-time {
        font-size: 0.7rem;
        opacity: 0.65;
        margin-top: 3px;
        display: block;
        text-align: right;
    }

    .chat-input-area {
        padding: 15px 20px;
        border-top: 1px solid var(--border-color);
        display: flex;
        gap: 10px;
        background: var(--card-bg);
    }

    .chat-input {
        flex: 1;
        padding: 10px 15px;
        border-radius: 25px;
        border: 1px solid var(--border-color);
        background: var(--bg-color);
        color: var(--text-color);
        font-size: 0.95rem;
        outline: none;
    }

    .chat-send-btn {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: var(--primary-color);
        color: white;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        transition: transform 0.2s;
        flex-shrink: 0;
    }

    .chat-send-btn:hover {
        transform: scale(1.1);
    }
</style>

<div class="main-content">
    <div class="top-header" style="margin-bottom: 20px;">
        <div>
            <h1><i class="fa-solid fa-comments" style="color: var(--primary-color);"></i> Messages</h1>
            <p style="color: #666;">Securely connect with donors and recipients.</p>
        </div>
    </div>

    <div class="messages-layout">
        <!-- Conversations List -->
        <div class="glass-card conversations-panel" style="padding: 0;">
            <div style="padding: 15px; border-bottom: 1px solid var(--border-color); font-weight: 600;">
                <i class="fa-solid fa-inbox"></i> Conversations
            </div>
            <?php if (empty($convos)): ?>
                <div style="padding: 30px; text-align: center; color: #888; font-style: italic; font-size: 0.9rem;">
                    No conversations yet. Click "I Can Help" on an urgent request to start one!
                </div>
            <?php else: ?>
                <?php foreach ($convos as $c): ?>
                    <a href="messages.php?to=<?= $c['id'] ?>"
                        class="convo-item <?= $active_chat == $c['id'] ? 'active' : '' ?>">
                        <div class="convo-avatar">
                            <?= strtoupper(substr($c['display_name'], 0, 1)) ?>
                        </div>
                        <div style="flex: 1; overflow: hidden;">
                            <div style="font-weight: 600; display: flex; justify-content: space-between;">
                                <?= htmlspecialchars($c['display_name']) ?>
                                <?php if ($c['unread_count'] > 0): ?>
                                    <span
                                        style="background: var(--primary-color); color: white; font-size: 0.7rem; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center;">
                                        <?= $c['unread_count'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div
                                style="font-size: 0.8rem; color: #888; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?= $c['last_message'] ? htmlspecialchars(substr($c['last_message'], 0, 35)) . '...' : 'Start a conversation' ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Chat Panel -->
        <?php if ($active_chat && $partner): ?>
            <div class="glass-card chat-panel" style="padding: 0;">
                <div class="chat-header">
                    <div class="convo-avatar" style="width: 38px; height: 38px; font-size: 0.9rem;">
                        <?= strtoupper(substr($partner['display_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;">
                            <?= htmlspecialchars($partner['display_name']) ?>
                        </div>
                        <div style="font-size: 0.8rem; color: #2ecc71;">● Active Donor</div>
                    </div>
                </div>

                <div class="chat-messages" id="chatMessages">
                    <?php if (empty($chat_history)): ?>
                        <div style="margin: auto; text-align: center; color: #888;">
                            <i class="fa-solid fa-hand-holding-heart" style="font-size: 2rem; opacity: 0.3;"></i>
                            <p style="margin-top: 10px; font-style: italic;">Say hello! You can discuss donation details here
                                safely.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($chat_history as $msg): ?>
                            <div
                                style="display: flex; flex-direction: column; align-items: <?= $msg['sender_id'] == $user_id ? 'flex-end' : 'flex-start' ?>;">
                                <div class="bubble <?= $msg['sender_id'] == $user_id ? 'sent' : 'received' ?>">
                                    <?= htmlspecialchars($msg['message']) ?>
                                    <span class="bubble-time">
                                        <?= date("g:i a", strtotime($msg['created_at'])) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <form method="POST" class="chat-input-area">
                    <input type="text" name="message" class="chat-input" placeholder="Type a message..." autocomplete="off"
                        required>
                    <button type="submit" name="send_message" class="chat-send-btn">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="glass-card chat-panel" style="align-items: center; justify-content: center; text-align: center;">
                <i class="fa-solid fa-comment-dots" style="font-size: 4rem; color: var(--primary-color); opacity: 0.2;"></i>
                <h3 style="margin-top: 20px; color: #888;">Select a conversation</h3>
                <p style="color: #aaa; margin-top: 10px; font-size: 0.9rem;">Or click "I Can Help" on an urgent request to
                    start chatting!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Auto-scroll chat to bottom
    var chatMessages = document.getElementById('chatMessages');
    if (chatMessages) { chatMessages.scrollTop = chatMessages.scrollHeight; }
</script>

<?php include 'includes/footer.php'; ?>