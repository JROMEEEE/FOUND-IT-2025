<?php
session_start();
require_once '../dbconnect.php';

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

// Fetch existing announcements
$stmt = $conn->prepare("SELECT announcement_id, user_name, message, created_at 
                        FROM announcements ORDER BY created_at DESC");
$stmt->execute();
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Announcements | FOUND-IT</title>
<?php include '../imports.php'; ?>
<style>
body { padding-top: 80px; }
#announcements { max-height: 500px; overflow-y: auto; }
.announcement-card { border-radius: 10px; margin-bottom: 20px; border-left: 6px solid #dc3545; padding: 15px; background-color: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.announcement-header { font-weight: 700; color: #dc3545; margin-bottom: 10px; font-size: 1.1rem; }
.announcement-body { font-size: 1rem; margin-bottom: 8px; white-space: pre-wrap; }
.timestamp { font-size: 0.75rem; color: #777; }
.disclaimer { font-size: 0.9rem; color: #555; }
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

<div class="card shadow-sm mb-5">
    <div class="card-header bg-danger text-white fw-semibold">
        <i class="bi bi-megaphone"></i> Announcements
        <span id="wsStatus" class="badge bg-secondary ms-2">Connecting...</span>
    </div>
    <div class="card-body">
        <div id="announcements" class="mb-3">
            <?php if(empty($announcements)): ?>
                <div class="text-muted fst-italic text-center py-3">No announcements yet.</div>
            <?php else: ?>
                <?php foreach($announcements as $a): ?>
                    <div class="card announcement-card" id="announcement-<?= $a['announcement_id'] ?>">
                        <div class="card-body">
                            <div class="announcement-header"><?= htmlspecialchars($a['user_name']) ?> Announcement</div>
                            <div class="announcement-body"><?= htmlspecialchars($a['message']) ?></div>
                            <div class="timestamp"><?= date("M d, Y h:i A", strtotime($a['created_at'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($is_admin == 1): ?>
            <textarea id="announcementInput" class="form-control mb-2" placeholder="Type a new announcement..." rows="4"></textarea>
            <button id="sendBtn" class="btn btn-danger fw-semibold">
                <i class="bi bi-megaphone"></i> Send
            </button>
        <?php else: ?>
            <div class="alert alert-info text-center fw-semibold mt-3 disclaimer">
                ⚠️ Lost/Found Items will not be posted here. Please refer to the <a href="item_dashboard.php" class="alert-link">Item Dashboard</a>.
            </div>
        <?php endif; ?>
    </div>
</div>

</div>

<script>
const userId = <?= json_encode($user_id) ?>;
const userName = <?= json_encode($user_name) ?>;
const isAdmin = <?= json_encode($is_admin) ?>;
const input = document.getElementById("announcementInput");
const sendBtn = document.getElementById("sendBtn");
const announcementsDiv = document.getElementById("announcements");
const statusBadge = document.getElementById("wsStatus");

let ws = null;

function connectWS() {
    ws = new WebSocket("ws://localhost:8080");

    ws.onopen = () => {
        statusBadge.textContent = "Online";
        statusBadge.className = "badge bg-success ms-2";
        if(isAdmin && input && sendBtn){ input.disabled = false; sendBtn.disabled = false; }
        ws.send(JSON.stringify({ type:"register", user_id:userId, user_name:userName, is_admin:isAdmin?1:0, room:"announcements" }));
    };

    ws.onclose = () => {
        statusBadge.textContent = "Offline - Reconnecting...";
        statusBadge.className = "badge bg-danger ms-2";
        if(input && sendBtn){ input.disabled = true; sendBtn.disabled = true; }
        setTimeout(connectWS, 3000);
    };

    ws.onmessage = (event) => {
        const data = JSON.parse(event.data);
        if(!data || data.channel!=='announcements' || !data.message) return;

        if(document.getElementById("announcement-" + data.announcement_id)) return;

        const card = document.createElement("div");
        card.className = "card announcement-card";
        card.id = "announcement-" + data.announcement_id;

        const cardBody = document.createElement("div");
        cardBody.className = "card-body";

        const header = document.createElement("div");
        header.className = "announcement-header";
        header.textContent = data.user_name + " Announcement";

        const body = document.createElement("div");
        body.className = "announcement-body";
        body.textContent = data.message;

        const timestamp = document.createElement("div");
        timestamp.className = "timestamp";
        timestamp.textContent = new Date(data.created_at || Date.now()).toLocaleString();

        cardBody.appendChild(header);
        cardBody.appendChild(body);
        cardBody.appendChild(timestamp);
        card.appendChild(cardBody);

        announcementsDiv.prepend(card);
    };
}

if(sendBtn){
    sendBtn.onclick = () => {
        if(!isAdmin || !input.value.trim()) return;
        ws.send(JSON.stringify({ type:"admin_announcement", user_id:userId, user_name:userName, message: input.value.trim() }));
        input.value = "";
    }
}

connectWS();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>