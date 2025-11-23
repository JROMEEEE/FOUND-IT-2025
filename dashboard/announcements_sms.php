<?php
session_start();
require_once '../dbconnect.php';
include '../apikeys.php';

header('Content-Type: application/json');

// Only allow admin
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1){
    echo json_encode(['status'=>'error','msg'=>'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');
if(empty($message)){
    echo json_encode(['status'=>'error','msg'=>'Empty message']);
    exit;
}

$database = new Database();
$conn = $database->getConnect();

// Get all approved users with valid contact numbers
$stmt = $conn->prepare("SELECT user_name, contact_no FROM users_table WHERE is_approved = 1 AND contact_no IS NOT NULL AND contact_no != ''");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(empty($users)){
    echo json_encode(['status'=>'error','msg'=>'No users found']);
    exit;
}

$api_token = IPROG_API_TOKEN; // your real token here
$url = 'https://www.iprogsms.com/api/v1/sms_messages';

$results = [];
foreach($users as $u){
    // Formal announcement template
    $formalMessage = "Dear {$u['user_name']}, a new announcement has been made:\n\n";
    $formalMessage .= "{$message}\n\n";
    $formalMessage .= "– FOUND-IT Administration";

    $smsData = [
        'api_token' => $api_token,
        'message' => $formalMessage,
        'phone_number' => $u['contact_no']
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($smsData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    $results[] = [
        'user' => $u['user_name'],
        'number' => $u['contact_no'],
        'response' => $response,
        'error' => $error
    ];
}

echo json_encode(['status'=>'ok','results'=>$results], JSON_PRETTY_PRINT);
?>