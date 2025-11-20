<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE); // suppress GD warnings
session_start();
require_once '../dbconnect.php';
require_once '../TCPDF-main/tcpdf.php'; // TCPDF integration

if (!isset($_SESSION['user_id']) || !isset($_POST['request_id'])) {
    header("Location: user_dashboard.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$request_id = intval($_POST['request_id']);

$database = new Database();
$conn = $database->getConnect();

// FETCH DETAILS FROM DB
$stmt = $conn->prepare("
    SELECT cr.ticket_code, cr.status, fr.fnd_name, cv.qr_image_path
    FROM claim_request cr
    LEFT JOIN found_report fr ON cr.fnd_id = fr.fnd_id
    LEFT JOIN claim_verification cv ON cr.request_id = cv.request_id
    WHERE cr.request_id = ? AND cr.user_id = ?
");
$stmt->execute([$request_id, $user_id]);
$claim = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$claim || $claim['status'] !== 'approved') {
    die("Invalid or unapproved claim request.");
}

// ENSURE QR FILE EXISTS
$qrFile = $claim['qr_image_path'];
$qrPath = realpath(__DIR__ . '/' . $qrFile);
if (!$qrPath || !file_exists($qrPath)) {
    die("QR code image not found at: $qrPath");
}

// ENSURE LOGO FILE EXISTS
$logoFile = '../assets/foundit-logo.png';
$logoPath = realpath(__DIR__ . '/' . $logoFile);
if (!$logoPath || !file_exists($logoPath)) {
    die("Logo image not found at: $logoPath");
}

// CREATE TCPDF INSTANCE
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('FOUND-IT');
$pdf->SetAuthor('FOUND-IT');
$pdf->SetTitle('Claim Ticket - ' . $claim['ticket_code']);
$pdf->SetMargins(20, 20, 20);
$pdf->SetAutoPageBreak(TRUE, 20);

$pdf->AddPage();
$pageWidth = $pdf->getPageWidth();

// --- LOGO ---
list($logoWidthPx, $logoHeightPx) = getimagesize($logoPath);
$logoWidthMM = 75; // desired width
$logoHeightMM = ($logoHeightPx / $logoWidthPx) * $logoWidthMM;
$logoX = ($pageWidth - $logoWidthMM) / 2;
$pdf->Image($logoPath, $logoX, 10, $logoWidthMM, $logoHeightMM, 'PNG');

// --- QR CODE ---
$qrWidth = 70;
$qrX = ($pageWidth - $qrWidth) / 2;
$pdf->Image($qrPath, $qrX, 10 + $logoHeightMM + 5, $qrWidth, $qrWidth, 'PNG');

// HEADER
$pdf->SetY(10 + $logoHeightMM + $qrWidth + 10); // below logo + QR
$pdf->SetFont('helvetica', 'B', 20);
$pdf->SetTextColor(220, 50, 50);
$pdf->Cell(0, 15, 'FOUND-IT CLAIM TICKET', 0, 1, 'C');

// TICKET CODE
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 10, 'Ticket Code: ' . $claim['ticket_code'], 0, 1, 'C');

// ITEM NAME
$pdf->Ln(3);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(50, 50, 50);
$pdf->Cell(0, 10, 'Item: ' . $claim['fnd_name'], 0, 1, 'C');

// TEXT
$pdf->Ln(5);
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(100, 100, 100);
$pdftext = "Disclaimer: This claim ticket is valid only for the item indicated above. Please present it at the FOUND-IT counter for verification.";
$pdf->MultiCell(0, 5, $pdftext, 0, 'C', 0, 1, '', '', true);

// OUTPUT PDF
$pdf->Output('Claim_' . $claim['ticket_code'] . '.pdf', 'I');
?>