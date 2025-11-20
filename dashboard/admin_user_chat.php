<?php
session_start();
require_once '../dbconnect.php';

// SESSION CHECK
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../accounts/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name']);
$is_admin = 1;

$database = new Database();
$conn = $database->getConnect();

// Fetch users who actually sent messages to admin (ignore admin accounts)
$stmt = $conn->prepare("
    SELECT DISTINCT u.user_id, u.user_name
    FROM user_admin_msgs m
    JOIN users_table u 
      ON (u.user_id = m.sender_id OR u.user_id = m.receiver_id)
    WHERE u.user_id != ? 
      AND u.is_admin = 0       -- 🔥 REMOVE ADMIN USERS
    ORDER BY u.user_name ASC
");
$stmt->execute([$user_id]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Chat | FOUND-IT</title>
<?php include '../imports.php'; ?>
<style>
body { padding-top: 80px; }
#chatBox { max-height: 500px; overflow-y: auto; }
.chat-card { border-radius: 10px; margin-bottom: 10px; padding: 10px; background-color: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.chat-sender { font-weight: 600; color: #dc3545; margin-bottom: 5px; }
.chat-message { white-space: pre-wrap; margin-bottom: 5px; }
.timestamp { font-size: 0.75rem; color: #777; }
.user-cards { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px; }
.user-card { padding: 10px 15px; border-radius: 10px; background-color: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); cursor: pointer; transition: 0.2s; }
.user-card:hover { box-shadow: 0 2px 6px rgba(0,0,0,0.2); }
.user-card.active { border-left: 5px solid #dc3545; }
</style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm fixed-top">
<div class="container">
    <a class="navbar-brand fw-bold" href="admin_dashboard.php">FOUND-IT</a>
    <div class="d-flex gap-2">
        <a href="admin_dashboard.php" class="btn btn-outline-light btn-sm fw-semibold">
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
        <i class="bi bi-chat-dots"></i> User Support Chat
        <span id="wsStatus" class="badge bg-secondary ms-2">Connecting...</span>
    </div>
    <div class="card-body">

        <div class="user-cards mb-3">
            <?php foreach($users as $u): ?>
                <div class="user-card" data-user-id="<?= $u['user_id'] ?>"><?= htmlspecialchars($u['user_name']) ?></div>
            <?php endforeach; ?>
        </div>

        <div id="chatBox" class="mb-3"></div>

        <textarea id="chatInput" class="form-control mb-2" placeholder="Type your message..." rows="3"></textarea>
        <button id="sendBtn" class="btn btn-danger fw-semibold"><i class="bi bi-send"></i> Send</button>
    </div>
</div>
</div>

<script>
const adminId = 9999; // Use unified admin ID for messages
const adminName = <?= json_encode($user_name) ?>;
const wsStatus = document.getElementById("wsStatus");
const chatBox = document.getElementById("chatBox");
const chatInput = document.getElementById("chatInput");
const sendBtn = document.getElementById("sendBtn");
const userCards = document.querySelectorAll('.user-card');

let ws = null;
let targetUserId = null;

function connectWS() {
    ws = new WebSocket("ws://localhost:8080");

    ws.onopen = () => {
        wsStatus.textContent = "Online";
        wsStatus.className = "badge bg-success ms-2";
        sendBtn.disabled = false;
        chatInput.disabled = false;
    };

    ws.onclose = () => {
        wsStatus.textContent = "Offline - Reconnecting...";
        wsStatus.className = "badge bg-danger ms-2";
        sendBtn.disabled = true;
        chatInput.disabled = true;
        setTimeout(connectWS, 3000);
    };

    ws.onmessage = (event) => {
        const data = JSON.parse(event.data);
        if(!data || data.channel !== 'support') return;
        if(targetUserId && (data.sender_id != targetUserId && data.receiver_id != targetUserId && data.sender_id != adminId)) return;

        const card = document.createElement("div");
        card.className = "chat-card";

        const sender = document.createElement("div");
        sender.className = "chat-sender";
        sender.textContent = data.sender_name;

        const msg = document.createElement("div");
        msg.className = "chat-message";
        msg.textContent = data.message;

        const timestamp = document.createElement("div");
        timestamp.className = "timestamp";
        timestamp.textContent = new Date(data.created_at || Date.now()).toLocaleString();

        card.appendChild(sender);
        card.appendChild(msg);
        card.appendChild(timestamp);
        chatBox.appendChild(card);
        chatBox.scrollTop = chatBox.scrollHeight;
    };
}

userCards.forEach(card => {
    card.addEventListener('click', () => {
        userCards.forEach(c => c.classList.remove('active'));
        card.classList.add('active');

        targetUserId = card.dataset.userId;
        chatBox.innerHTML = "";

        if(ws && ws.readyState === WebSocket.OPEN){
            ws.send(JSON.stringify({
                type: "register",
                user_id: adminId,
                user_name: adminName,
                is_admin: 1,
                room: "support",
                target_user_id: targetUserId
            }));
        }
    });
});

sendBtn.onclick = () => {
    if(!chatInput.value.trim() || !targetUserId || ws.readyState !== WebSocket.OPEN) return;

    ws.send(JSON.stringify({
        type: "chat_message",
        sender_id: adminId, // unified admin ID
        sender_name: adminName,
        receiver_id: targetUserId,
        is_admin: 1,
        message: chatInput.value.trim()
    }));

    chatInput.value = "";
};

connectWS();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>