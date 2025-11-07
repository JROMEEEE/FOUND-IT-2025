<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../accounts/login.php");
    exit;
}

if (!isset($_POST['request_id'])) {
    header("Location: admin_claimrep.php");
    exit;
}

$request_id = intval($_POST['request_id']);

try {
    $database = new Database();
    $conn = $database->getConnect();

    $conn->prepare("DELETE FROM claim_verification WHERE request_id=?")->execute([$request_id]);
    $conn->prepare("DELETE FROM claim_request WHERE request_id=?")->execute([$request_id]);

    $_SESSION['claim_status_msg'] = "Claim removed successfully.";
} catch (Exception $e) {
    $_SESSION['claim_status_msg'] = "Error: " . $e->getMessage();
}

header("Location: admin_claimrep.php");
exit;

?>
