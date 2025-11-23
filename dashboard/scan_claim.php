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

// ADMIN RESTRICTION
if ($is_admin != 1) {
    header("Location: user_dashboard.php");
    exit;
}

// --- ACTIVITY LOG FUNCTION ---
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
            'action' => $action,
            'table_name' => $table_name,
            'record_id' => $record_id,
            'details' => $details
        ]);
    } catch (PDOException $e) {
        // Optional: ignore or log somewhere else
    }
}

// --- HANDLE AJAX REQUEST TO GET CLAIM DETAILS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_details') {
    $ticket_code = $_POST['ticket_code'];
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
            // LOG: admin viewed claim details
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

// --- HANDLE AJAX REQUEST TO CLAIM TICKET ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_code'])) {
    $ticket_code = $_POST['ticket_code'];
    try {
        $database = new Database();
        $conn = $database->getConnect();

        $stmt = $conn->prepare("SELECT request_id, status, claimer_name, claimer_id FROM claim_request WHERE ticket_code = :ticket_code LIMIT 1");
        $stmt->execute(['ticket_code' => $ticket_code]);
        $claim = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($claim) {
            if ($claim['status'] === 'claimed') {
                echo json_encode(['success' => false, 'message' => 'Ticket already claimed.']);
            } else {
                // Update status to 'claimed'
                $update = $conn->prepare("UPDATE claim_request SET status = 'claimed' WHERE request_id = :id");
                $update->execute(['id' => $claim['request_id']]);

                // LOG: admin marked claim as claimed
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