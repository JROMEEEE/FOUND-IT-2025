<?php
session_start();
include '../dbconnect.php';

// SESSION TIMEOUT (1 hour)
$session_lifetime = 3600;
if (!isset($_SESSION['user_id']) || (time() - $_SESSION['last_activity'] > $session_lifetime)) {
    session_unset();
    session_destroy();
    header("Location: ../accounts/login.php");
    exit;
}
$_SESSION['last_activity'] = time();

// USER INFO
$user_name = htmlspecialchars($_SESSION['user_name']);
$is_admin = $_SESSION['is_admin'] ?? 0;

// ADMIN ONLY
if ($is_admin != 1) {
    header("Location: user_dashboard.php");
    exit;
}

// ACTIVITY LOG FUNCTION
function logActivity($user_id, $action, $table_name, $record_id = null, $details = null) {
    try {
        $database = new Database();
        $conn = $database->getConnect();

        $stmt = $conn->prepare("
            INSERT INTO activity_log (user_id, action, table_name, record_id, details)
            VALUES (:user_id, :action, :table_name, :record_id, :details)
        ");
        $stmt->execute([
            'user_id' => $user_id,
            'action'  => $action,
            'table_name' => $table_name,
            'record_id'  => $record_id,
            'details' => $details
        ]);
    } catch (PDOException $e) {
        // Silent fail
    }
}

// AJAX: GET CLAIM DETAILS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_details') {

    $ticket_code = $_POST['ticket_code'] ?? '';

    try {
        $database = new Database();
        $conn = $database->getConnect();

        $stmt = $conn->prepare("
            SELECT cr.claimer_name, cr.claimer_id, cr.request_date, fr.image_path, fr.fnd_name, cr.request_id
            FROM claim_request cr
            JOIN found_report fr ON cr.fnd_id = fr.fnd_id
            WHERE cr.ticket_code = :ticket_code LIMIT 1
        ");
        $stmt->execute(['ticket_code' => $ticket_code]);
        $claim = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($claim) {

            // LOG VIEW
            $details = json_encode([
                'ticket_code' => $ticket_code,
                'claimer_name' => $claim['claimer_name'],
                'claimer_id' => $claim['claimer_id']
            ]);

            logActivity($_SESSION['user_id'], 'VIEW', 'claim_request', $claim['request_id'], $details);

            echo json_encode([
                'success' => true,
                'claimer_name' => $claim['claimer_name'],
                'claimer_id' => $claim['claimer_id'],
                'request_date' => $claim['request_date'],
                'image_path' => $claim['image_path'],
                'item_name' => $claim['fnd_name']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ticket code not found.']);
        }

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }

    exit;
}

// AJAX: MARK CLAIM AS CLAIMED
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_code'])) {

    $ticket_code = $_POST['ticket_code'] ?? '';

    try {
        $database = new Database();
        $conn = $database->getConnect();

        $stmt = $conn->prepare("
            SELECT request_id, status, claimer_name, claimer_id
            FROM claim_request
            WHERE ticket_code = :ticket_code LIMIT 1
        ");
        $stmt->execute(['ticket_code' => $ticket_code]);
        $claim = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($claim) {
            if ($claim['status'] === 'claimed') {
                echo json_encode(['success' => false, 'message' => 'Ticket already claimed.']);
            } else {

                // UPDATE STATUS TO CLAIMED
                $update = $conn->prepare("UPDATE claim_request SET status = 'claimed' WHERE request_id = :id");
                $update->execute(['id' => $claim['request_id']]);

                // LOG UPDATE
                $details = json_encode([
                    'ticket_code' => $ticket_code,
                    'claimer_name' => $claim['claimer_name'],
                    'claimer_id' => $claim['claimer_id']
                ]);

                logActivity($_SESSION['user_id'], 'UPDATE', 'claim_request', $claim['request_id'], $details);

                echo json_encode(['success' => true, 'message' => 'Claim marked as claimed!']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Ticket code not found.']);
        }

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }

    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>FOUND-IT | Scan Claim QR</title>
  <link rel="stylesheet" href="../css/scanner.css">
<?php include '../imports.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>

<style>
#video { width: 100%; max-width: 450px; border-radius: 10px; background: #000; }
#result-box { max-width: 450px; margin: 20px auto; }
#qr-status { font-weight: bold; }
</style>
</head>

<body class="bg-light">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm fixed-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="admin_dashboard.php">FOUND-IT Admin</a>
    <div class="collapse navbar-collapse justify-content-end">
      <ul class="navbar-nav align-items-center">
        <li class="nav-item mx-2">
          <span class="text-white fw-semibold">Hello, <?= $user_name ?></span>
        </li>
        <li class="nav-item mx-2">
          <a class="btn btn-light btn-sm fw-semibold text-danger" href="../accounts/logout.php">
            <i class="bi bi-box-arrow-right"></i> Logout
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container py-5 mt-5">

  <div class="text-center mb-4">
    <h2 class="fw-bold text-danger"><i class="bi bi-upc-scan"></i> Scan Claim QR</h2>
    <p class="text-muted">Point the camera at a QR code to claim items.</p>
  </div>

  <div class="card shadow border-0 p-4 mx-auto" style="max-width:600px;">
      <div class="text-center">
          <video id="video" autoplay playsinline></video>
          <canvas id="canvas" style="display:none;"></canvas>
      </div>

      <div class="mt-3">
        <h6 class="fw-bold text-danger">Or Enter Ticket Code Manually:</h6>
        <div class="input-group mb-3">
          <input type="text" id="manual-ticket-code" class="form-control" placeholder="Enter ticket code">
          <button class="btn btn-outline-danger" id="manual-submit">Submit</button>
        </div>
      </div>

      <div id="result-box" class="text-center border rounded p-3 bg-white mt-3">
        <h6 class="fw-bold text-danger">Scanned Result:</h6>
        <p id="qr-result" class="text-muted mb-0">No QR scanned yet.</p>
        <p id="qr-status" class="mt-2"></p>
      </div>
  </div>

  <div class="text-center mt-4">
    <a href="admin_db_dashboard.php" class="btn btn-outline-secondary fw-semibold">
      <i class="bi bi-arrow-left"></i> Return to Dashboard
    </a>
  </div>

</div>

<!-- CONFIRM MODAL -->
<div class="modal fade" id="confirmModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Confirm Claim</h5>
      </div>

      <div class="modal-body text-center">

        <div class="d-flex justify-content-center mb-3">
          <img id="modal-item-image"
               src=""
               class="rounded"
               style="max-height:200px; max-width:100%; display:none; object-fit:contain;">
        </div>

        <p class="mb-3">Do you want to mark this ticket as claimed?</p>

        <h5 id="modal-ticket-code" class="fw-bold text-danger mb-3"></h5>
        
        <div class="text-start d-inline-block">
          <p class="mb-2"><strong>Item:</strong> <span id="modal-item-name"></span></p>
          <p class="mb-2"><strong>Name:</strong> <span id="modal-claimer-name"></span></p>
          <p class="mb-2"><strong>ID:</strong> <span id="modal-claimer-id"></span></p>
          <p class="mb-0"><strong>Date:</strong> <span id="modal-request-date"></span></p>
        </div>

      </div>

      <div class="modal-footer justify-content-center">
        <button type="button" id="confirm-claim" class="btn btn-success">Accept Claim</button>
        <button type="button" class="btn btn-secondary">Cancel</button>
      </div>

    </div>
  </div>
</div>
</div>

<script>
/* FULL JS — unchanged but tied to backend with logging */
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const context = canvas.getContext('2d');
const qrResult = document.getElementById('qr-result');
const qrStatus = document.getElementById('qr-status');

let lastScanned = null;

async function startCamera() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } });
        video.srcObject = stream;
        video.play();
        requestAnimationFrame(scanFrame);
    } catch (e) {
        alert("Camera access denied.");
    }
}

const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));

const modalTicketCode = document.getElementById('modal-ticket-code');
const modalItemName = document.getElementById('modal-item-name');
const modalClaimerName = document.getElementById('modal-claimer-name');
const modalClaimerId = document.getElementById('modal-claimer-id');
const modalRequestDate = document.getElementById('modal-request-date');
const modalItemImage = document.getElementById('modal-item-image');
const confirmButton = document.getElementById('confirm-claim');

function processTicketCode(ticketCode) {

    qrResult.textContent = ticketCode;
    lastScanned = ticketCode;

    fetch("", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "action=get_details&ticket_code=" + encodeURIComponent(ticketCode)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {

            modalTicketCode.textContent = ticketCode;
            modalItemName.textContent = data.item_name;
            modalClaimerName.textContent = data.claimer_name;
            modalClaimerId.textContent = data.claimer_id;
            modalRequestDate.textContent = new Date(data.request_date).toLocaleString();

            if (data.image_path) {
                modalItemImage.src = '../' + data.image_path;
                modalItemImage.style.display = 'block';
            } else {
                modalItemImage.style.display = 'none';
            }

            confirmModal.show();

            confirmButton.onclick = () => {
                fetch("", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "ticket_code=" + encodeURIComponent(ticketCode)
                })
                .then(res => res.json())
                .then(data => {
                    qrStatus.textContent = data.message;
                    qrStatus.style.color = data.success ? "green" : "red";
                    confirmModal.hide();
                    lastScanned = null;
                });
            };

        } else {
            qrStatus.textContent = data.message;
            qrStatus.style.color = "red";
            lastScanned = null;
        }
    });
}

document.querySelector('#confirmModal .btn-secondary').addEventListener('click', () => {
    lastScanned = null;
    confirmModal.hide();
});

function scanFrame() {
    requestAnimationFrame(scanFrame);

    if (video.readyState !== video.HAVE_ENOUGH_DATA) return;

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    const img = context.getImageData(0, 0, canvas.width, canvas.height);

    const code = jsQR(img.data, img.width, img.height);

    if (code && code.data !== lastScanned) {
        processTicketCode(code.data);
    }
}

document.getElementById('manual-submit').onclick = () => {
    const val = document.getElementById('manual-ticket-code').value.trim();
    if (!val) return;
    processTicketCode(val);
};

startCamera();
</script>

</body>
</html>