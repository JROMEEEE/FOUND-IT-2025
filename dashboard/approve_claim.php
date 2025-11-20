<?php
session_start();
require_once '../dbconnect.php';
require_once '../phpqrcode/qrlib.php';
require_once '../apikeys.php'; // IPROG_API_TOKEN

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

// Fetch user's phone number
$userStmt = $conn->prepare("SELECT contact_no, user_name FROM users_table WHERE user_id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['claim_status_msg'] = "User not found.";
    header("Location: admin_claimrep.php");
    exit;
}

$userPhone = $user['contact_no'];
$userName = $user['user_name'];

// Function to send SMS via iProgSMS
function sendSMS($phone, $message) {
    $url = 'https://www.iprogsms.com/api/v1/sms_messages';
    $data = [
        'api_token' => IPROG_API_TOKEN,
        'phone_number' => $phone,
        'message' => $message
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $result = curl_exec($ch);
    curl_close($ch);

    return json_decode($result, true);
}

// REJECT CLAIM
if ($action === "reject") {
    $update = $conn->prepare("UPDATE claim_request SET status = 'rejected' WHERE request_id = ?");
    $update->execute([$request_id]);

    // Send SMS notification
    $smsMessage = "Hello $userName, your claim request #$ticket_code has been rejected.";
    sendSMS($userPhone, $smsMessage);

    $_SESSION['claim_status_msg'] = "Claim Request #$request_id has been declined and user notified.";
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

    // Send SMS notification
    $smsMessage = "Hello $userName, your claim request #$ticket_code has been approved. Check your FOUND-IT account for more details.";
    sendSMS($userPhone, $smsMessage);

    $_SESSION['claim_status_msg'] = "Claim Request #$request_id approved. User has been notified via SMS.";
} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['claim_status_msg'] = "Error approving claim: " . $e->getMessage();
}

header("Location: admin_claimrep.php");
exit;
?>