<?php
session_start();
require_once __DIR__ . '/../dbconnect.php'; // Adjust path

// SESSION CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: ../accounts/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name']);
$is_admin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 0;

$database = new Database();
$conn = $database->getConnect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Support Chat | FOUND-IT</title>
<?php include '../imports.php'; ?>
<style>
body { padding-top: 80px; }
#chatBox { max-height: 500px; overflow-y: auto; }
.chat-card { border-radius: 10px; margin-bottom: 12px; padding: 12px; background-color: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.chat-header { font-weight: 600; margin-bottom: 5px; }
.chat-body { white-space: pre-wrap; font-size: 0.95rem; }
.chat-timestamp { font-size: 0.75rem; color: #777; text-align: right; }
.user-msg { border-left: 5px solid #dc3545; }
.admin-msg { border-left: 5px solid #0d6efd; }
</style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm fixed-top">
<div class="container">
    <a class="navbar-brand fw-bold" href="user_dashboard.php">FOUND-IT</a>
    <div class="d-flex gap-2">
        <a href="user_dashboard.php" class="btn btn-outline-light btn-sm fw-semibold">
            <i class="bi bi-house-door"></i> Dashboard
        </a>
        <a href="../accounts/logout.php" class="btn btn-light btn-sm text-danger fw-semibold">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</div>
</nav>

<div class="container py-5">
    <div class="card shadow-sm">
        <div class="card-header bg-danger text-white fw-semibold">
            <i class="bi bi-chat-dots"></i> Support Chat
            <span id="wsStatus" class="badge bg-secondary ms-2">Connecting...</span>
        </div>
        <div class="card-body">
            <div id="chatBox"></div>
            <div class="input-group mt-3">
                <input type="text" id="chatInput" class="form-control" placeholder="Type your message..." />
                <button id="sendBtn" class="btn btn-danger">
                    <i class="bi bi-send"></i> Send
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const userId = <?= json_encode($user_id) ?>;
const userName = <?= json_encode($user_name) ?>;
const chatInput = document.getElementById("chatInput");
const sendBtn = document.getElementById("sendBtn");
const chatBox = document.getElementById("chatBox");
const statusBadge = document.getElementById("wsStatus");

// Admin is always 9999 in DB for live chat
const SUPPORT_ADMIN_ID = 9999;

let ws = null;

function connectWS() {
    ws = new WebSocket("ws://localhost:8080"); // WebSocket server

    ws.onopen = () => {
        statusBadge.textContent = "Online";
        statusBadge.className = "badge bg-success ms-2";
        chatInput.disabled = false;
        sendBtn.disabled = false;

        // Register user for support channel
        ws.send(JSON.stringify({
            type: "register",
            user_id: userId,
            user_name: userName,
            is_admin: 0,
            room: "support"
        }));
    };

    ws.onclose = () => {
        statusBadge.textContent = "Offline - Reconnecting...";
        statusBadge.className = "badge bg-danger ms-2";
        chatInput.disabled = true;
        sendBtn.disabled = true;
        setTimeout(connectWS, 3000);
    };

    ws.onmessage = (event) => {
        const data = JSON.parse(event.data);
        if (!data || data.channel !== 'support' || !data.message) return;

        // Only show messages from admin (9999) or sent by this user
        if (data.sender_id !== userId && data.sender_id !== SUPPORT_ADMIN_ID && data.receiver_id !== userId) return;

        const card = document.createElement("div");
        card.className = "card chat-card " + (data.sender_id === userId ? "user-msg" : "admin-msg");

        const header = document.createElement("div");
        header.className = "chat-header";
        header.textContent = data.sender_name;

        const body = document.createElement("div");
        body.className = "chat-body";
        body.textContent = data.message;

        const timestamp = document.createElement("div");
        timestamp.className = "chat-timestamp";
        timestamp.textContent = new Date(data.created_at || Date.now()).toLocaleString();

        card.appendChild(header);
        card.appendChild(body);
        card.appendChild(timestamp);
        chatBox.appendChild(card);
        chatBox.scrollTop = chatBox.scrollHeight;
    };
}

// Send user message
sendBtn.onclick = () => {
    if (!chatInput.value.trim()) return;

    ws.send(JSON.stringify({
        type: "chat_message",
        sender_id: userId,
        sender_name: userName,
        receiver_id: SUPPORT_ADMIN_ID,
        message: chatInput.value.trim(),
        is_admin: 0,
        room: "support"
    }));

    chatInput.value = "";
};

// Allow Enter key to send message
chatInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter") sendBtn.click();
});

connectWS();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>