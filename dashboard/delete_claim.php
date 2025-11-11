<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['user_id'], $_POST['request_id'])) {
    header("Location: admin_claimrep.php");
    exit;
}

$request_id = intval($_POST['request_id']);

// ADMIN CAN ONLY DELETE
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    $_SESSION['claim_status_msg'] = "Unauthorized action.";
    header("Location: admin_claimrep.php");
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnect();

    // DELETE CLAIM REQ (CASCADE DELETES CLAIM VERIFICATION IF FK ON DELETE CASCADE EXISTS)
    $stmt = $conn->prepare("DELETE FROM claim_request WHERE request_id = ?");
    $stmt->execute([$request_id]);

    $_SESSION['claim_status_msg'] = "Claim Request #$request_id has been deleted.";
} catch (PDOException $e) {
    $_SESSION['claim_status_msg'] = "Error deleting claim: " . $e->getMessage();
}

header("Location: admin_claimrep.php");
exit;
