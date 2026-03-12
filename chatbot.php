<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$page_title = "Red Hope | Assistant";
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-header">
        <div>
            <h1>Donation Assistant</h1>
            <p style="color: #666;">Ask our AI chatbot anything about blood donation, eligibility, and finding blood banks.</p>
        </div>
    </div>

    <!-- Chat Interface -->
    <div class="glass-card"
        style="display: flex; flex-direction: column; max-width: 800px; height: 70vh; padding: 0; overflow: hidden;">

        <!-- Chat Header -->
        <div
            style="background: var(--primary-color); padding: 15px 25px; color: white; font-weight: 600; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-robot"></i> Red Hope Assistant
        </div>

        <!-- Chat History -->
        <div id="chatbox"
            style="flex: 1; padding: 25px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px; background: rgba(0,0,0,0.01);">
            <!-- Messages will be appended here -->
            <div class="message bot"
                style="align-self: flex-start; max-width: 75%; background: var(--hover-bg); padding: 12px 18px; border-radius: 18px; border-top-left-radius: 4px; line-height: 1.5;">
                Hello! 👋 I am here to help you understand the blood donation process. What would you like to
                know?
            </div>
        </div>

        <!-- Chat Input Area -->
        <div
            style="padding: 15px 25px; border-top: 1px solid var(--border-color); display: flex; gap: 10px; background: rgba(255,255,255,0.2);">
            <input type="text" id="userInput" placeholder="Type your message here..."
                style="flex: 1; padding: 12px 20px; border-radius: 30px; border: 1px solid var(--border-color); background: rgba(255,255,255,0.7); outline: none; color: var(--text-color); font-size: 1rem;">
            <button id="sendBtn" onclick="sendMessage()"
                style="width: 48px; height: 48px; border-radius: 50%; background: var(--primary-color); color: white; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; box-shadow: 0 4px 10px rgba(231, 76, 60, 0.4); transition: transform 0.2s;">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

<style>
    /* Message specific styles injected via JS */
    .message.user {
        align-self: flex-end;
        max-width: 75%;
        background: var(--primary-color);
        color: white;
        padding: 12px 18px;
        border-radius: 18px;
        border-top-right-radius: 4px;
        box-shadow: 0 2px 10px rgba(231, 76, 60, 0.2);
    }

    .message.bot {
        align-self: flex-start;
        max-width: 75%;
        background: var(--hover-bg);
        color: var(--text-color);
        padding: 12px 18px;
        border-radius: 18px;
        border-top-left-radius: 4px;
        border: 1px solid var(--border-color);
    }
</style>

<script>
    const chatbox = document.getElementById('chatbox');
    const userInput = document.getElementById('userInput');

    const faqResponses = {
        "what is blood donation": "Blood donation is the process of giving blood to help others in need. It is completely safe, highly regulated, and saves millions of lives each year.",
        
        "where to find blood banks": "You can visit our <b>Donation Map</b> page by clicking the map icon in the sidebar to see nearby blood banks and camps.",
        "eligibility": "Donors must be healthy, at least 18 years old, and meet specific health criteria (like high enough hemoglobin levels).",
        "benefits": "Aside from saving lives, donating blood can help reduce excess iron in your blood, and provides a mini physical examination.",
        "register": "You can register your preferences right here in the Red Hope app via your Profile page! We also recommend registering with your national database."
    };

    function appendMessage(text, sender) {
        const div = document.createElement('div');
        div.innerHTML = text; // Allow HTML formatting like bold
        div.className = `message ${sender}`;

        // Add subtle animation
        div.style.opacity = '0';
        div.style.transform = 'translateY(10px)';
        div.style.transition = 'all 0.3s ease';

        chatbox.appendChild(div);

        // Trigger reflow to play animation
        setTimeout(() => {
            div.style.opacity = '1';
            div.style.transform = 'translateY(0)';
            chatbox.scrollTop = chatbox.scrollHeight; // Auto scroll
        }, 10);
    }

    function sendMessage() {
        const message = userInput.value.trim();
        if (!message) return;

        // Add user message
        appendMessage(message, 'user');

        // Disable input while "thinking"
        userInput.value = '';
        userInput.disabled = true;

        const lower = message.toLowerCase();

        // Simple keyword matching chatbot logic
        let response = "I'm still learning! Right now I can answer questions about <b>blood donation</b>, <b>eligibility</b>, <b>blood types</b>, <b>how often to donate</b>, and <b>finding blood banks</b>. Try asking one of those!";

        for (const key in faqResponses) {
            if (lower.includes(key)) {
                response = faqResponses[key];
                break;
            }
        }

        // Simulate thinking delay
        setTimeout(() => {
            appendMessage(response, 'bot');
            userInput.disabled = false;
            userInput.focus();
        }, 600);
    }

    userInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
</script>

<?php include 'includes/footer.php'; ?>