<?php
session_start();
require_once '../dbconnect.php';
require_once '../phpqrcode/qrlib.php';

if (!isset($_POST['request_id'], $_POST['action'])) {
    header("Location: admin_claimrep.php");
    exit;
}

$request_id = intval($_POST['request_id']);
$action = $_POST['action'];

$database = new Database();
$conn = $database->getConnect();

// Fetch claim request
$stmt = $conn->prepare("SELECT * FROM claim_request WHERE request_id = ?");
$stmt->execute([$request_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    $_SESSION['claim_status_msg'] = "Request not found.";
    header("Location: admin_claimrep.php");
    exit;
}

$user_id = $request['user_id'];
$fnd_id = $request['fnd_id'];
$ticket_code = $request['ticket_code'];

// REJECT CLAIM
if ($action === "reject") {
    $update = $conn->prepare("UPDATE claim_request SET status = 'rejected' WHERE request_id = ?");
    $update->execute([$request_id]);

    $_SESSION['claim_status_msg'] = "Claim Request #$request_id has been declined.";
    header("Location: admin_claimrep.php");
    exit;
}

// APPROVE CLAIM
try {
    $conn->beginTransaction();

    // Update claim_request status
    $updateClaim = $conn->prepare("UPDATE claim_request SET status = 'approved' WHERE request_id = ?");
    $updateClaim->execute([$request_id]);

    // Update found_report status to 'claimed'
    $updateFound = $conn->prepare("UPDATE found_report SET fnd_status = 'claimed' WHERE fnd_id = ?");
    $updateFound->execute([$fnd_id]);

    // Generate QR code
    $qrFolder = "../qrcodes/";
    if (!file_exists($qrFolder)) mkdir($qrFolder, 0777, true);

    $qrFileName = $ticket_code . ".png";
    $qrPath = $qrFolder . $qrFileName;

    QRcode::png($ticket_code, $qrPath, QR_ECLEVEL_L, 5);

    // Insert claim verification
    $insert = $conn->prepare("
        INSERT INTO claim_verification (request_id, user_id, fnd_id, ticket_code, qr_image_path)
        VALUES (?, ?, ?, ?, ?)
    ");
    $insert->execute([$request_id, $user_id, $fnd_id, $ticket_code, $qrPath]);

    $conn->commit();

    $_SESSION['claim_status_msg'] = "Claim Request #$request_id approved. QR code generated and item marked as claimed.";
} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['claim_status_msg'] = "Error approving claim: " . $e->getMessage();
}

header("Location: admin_claimrep.php");
exit;
?>
