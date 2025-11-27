<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE); // suppress GD warnings
require_once '../dbconnect.php';
require_once '../TCPDF-main/tcpdf.php';

$type  = $_GET['filter'] ?? 'daily';
$value = $_GET['value'] ?? date('Y-m-d');

$database = new Database();
$conn = $database->getConnect();

// DATETIME
if ($type === 'daily') {
    $start = $value . ' 00:00:00';
    $end   = $value . ' 23:59:59';
} elseif ($type === 'monthly') {
    $start = $value . '-01 00:00:00';
    $end   = date("Y-m-t", strtotime($value)) . ' 23:59:59';
} elseif ($type === 'yearly') {
    $start = $value . '-01-01 00:00:00';
    $end   = $value . '-12-31 23:59:59';
}

// IMAGE FROM Quickchart.io
function generateChart($chartData, $width=500, $height=500) {
    $chartData['width'] = $width;
    $chartData['height'] = $height;
    $chartUrl = 'https://quickchart.io/chart?c=' . urlencode(json_encode($chartData));
    $chartFile = tempnam(sys_get_temp_dir(), 'chart') . '.png';
    file_put_contents($chartFile, file_get_contents($chartUrl));
    return $chartFile;
}

// FOOTER
class CustomPDF extends TCPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 9);
        $this->SetTextColor(128,128,128);
        $this->Cell(0, 10, 'Generated on '.date('F d, Y \a\t h:i A').' | FOUND-IT System | Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, 0, 'C');
    }
}


// 1. FOUND ITEMS
$foundRows = $conn->prepare("SELECT fnd_status FROM found_report WHERE fnd_datetime BETWEEN ? AND ?");
$foundRows->execute([$start, $end]);
$foundRows = $foundRows->fetchAll(PDO::FETCH_ASSOC);

$claimed = $unclaimed = 0;
foreach ($foundRows as $r) {
    strtolower($r['fnd_status']) === 'claimed' ? $claimed++ : $unclaimed++;
}

$foundChart = generateChart([
    'type' => 'pie',
    'data' => ['labels'=>['Claimed','Unclaimed'],'datasets'=>[['data'=>[$claimed,$unclaimed],'backgroundColor'=>['#28a745','#dc3545']]]],
    'options'=>['plugins'=>['legend'=>['position'=>'bottom','labels'=>['font'=>['size'=>10]]]]]
]);

// 2. LOST ITEMS
$lostRows = $conn->prepare("SELECT lost_status FROM lost_report WHERE lost_datetime BETWEEN ? AND ?");
$lostRows->execute([$start,$end]);
$lostRows = $lostRows->fetchAll(PDO::FETCH_ASSOC);

$active=$expired=$closed=0;
foreach($lostRows as $r){
    switch(strtolower($r['lost_status'])){
        case 'active': $active++; break;
        case 'expired': $expired++; break;
        case 'closed': $closed++; break;
        default: $active++; break;
    }
}

$lostChart = generateChart([
    'type'=>'pie',
    'data'=>['labels'=>['Active','Expired','Closed'],'datasets'=>[['data'=>[$active,$expired,$closed],'backgroundColor'=>['#ffc107','#6c757d','#17a2b8']]]],
    'options'=>['plugins'=>['legend'=>['position'=>'bottom','labels'=>['font'=>['size'=>10]]]]]
]);

// 3. LOCATIONS
$locRows = $conn->prepare("
    SELECT l.location_name, 
           COUNT(f.fnd_id) AS found_count, 
           (SELECT COUNT(lr.lost_id) FROM lost_report lr WHERE lr.location_id = l.location_id AND lr.lost_datetime BETWEEN ? AND ?) AS lost_count
    FROM location_table l
    LEFT JOIN found_report f ON f.location_id = l.location_id AND f.fnd_datetime BETWEEN ? AND ?
    GROUP BY l.location_id
");
$locRows->execute([$start,$end,$start,$end]);
$locRows = $locRows->fetchAll(PDO::FETCH_ASSOC);

$locLabels = $foundData = $lostData = [];
foreach($locRows as $r){
    $locLabels[] = $r['location_name'];
    $foundData[] = $r['found_count'];
    $lostData[] = $r['lost_count'];
}

$barHeightPx = max(200, count($locRows)*50);
$locationChart = generateChart([
    'type'=>'bar',
    'data'=>['labels'=>$locLabels,'datasets'=>[['label'=>'Found','data'=>$foundData,'backgroundColor'=>'#28a745'],['label'=>'Lost','data'=>$lostData,'backgroundColor'=>'#dc3545']]], 
    'options'=>['indexAxis'=>'y','plugins'=>['legend'=>['position'=>'bottom','labels'=>['font'=>['size'=>10]]]],'scales'=>['x'=>['beginAtZero'=>true,'ticks'=>['font'=>['size'=>10]]],'y'=>['ticks'=>['font'=>['size'=>10]]]]]
], 800, $barHeightPx);

// 4. CLAIM REQUESTS
$claimRows = $conn->prepare("SELECT status, COUNT(*) AS count FROM claim_request WHERE request_date BETWEEN ? AND ? GROUP BY status");
$claimRows->execute([$start,$end]);
$claimRows = $claimRows->fetchAll(PDO::FETCH_ASSOC);

$claimLabels=$claimData=$claimColors=[];
foreach($claimRows as $r){
    $claimLabels[] = ucfirst($r['status']);
    $claimData[] = $r['count'];
    switch(strtolower($r['status'])){
        case 'pending': $claimColors[]='#ffc107'; break;
        case 'approved': $claimColors[]='#28a745'; break;
        case 'rejected': $claimColors[]='#dc3545'; break;
        case 'claimed': $claimColors[]='#17a2b8'; break;
        default: $claimColors[]='#6c757d'; break;
    }
}

$claimChart = generateChart([
    'type'=>'pie',
    'data'=>['labels'=>$claimLabels,'datasets'=>[['data'=>$claimData,'backgroundColor'=>$claimColors]]],
    'options'=>['plugins'=>['legend'=>['position'=>'bottom','labels'=>['font'=>['size'=>10]]]]]
]);

// PDF GENERATION
$pdf = new CustomPDF();
$pdf->SetMargins(15,20,15);
$pdf->AddPage();

// LOGO
$logoFile = '../assets/foundit-logo.png';
$logoPath = realpath(__DIR__ . '/' . $logoFile);
if ($logoPath && file_exists($logoPath)) {
    $pageWidth = $pdf->getPageWidth();
    list($logoWidthPx, $logoHeightPx) = getimagesize($logoPath);
    $logoWidthMM = 40;
    $logoHeightMM = ($logoHeightPx / $logoWidthPx) * $logoWidthMM;
    $logoX = ($pageWidth - $logoWidthMM) / 2;

    $logoY = 15;
    $pdf->Image($logoPath, $logoX, $logoY, $logoWidthMM, $logoHeightMM, 'PNG');
    $pdf->SetY($logoY + $logoHeightMM + 2);
}

// TITLE & FILTER INFO
$pdf->SetFont('helvetica','B',20);
$pdf->Cell(0,12,'SYSTEM REPORT',0,1,'C');

$pdf->SetFont('helvetica','',11);
$pdf->SetFillColor(240,240,240);
$pdf->Cell(90,7,"Report Type: ".strtoupper($type),1,0,'L',true);
$pdf->Cell(90,7,"Filter: ".$value,1,1,'L',true);
$pdf->Ln(3); // spacing before sections


// LAYOUT FUNCTION
function addSection($pdf,$title,$chartFile,$text,$fullWidth=false,$isBar=false){
    list($imgW,$imgH) = getimagesize($chartFile);
    $aspect = $imgH / $imgW;
    $pdfWidth = $isBar ? 180 : 80;
    $pdfHeight = $pdfWidth * $aspect;

    if($pdf->GetY() + $pdfHeight + 20 > 280) $pdf->AddPage();

    $pdf->SetFont('helvetica','B',14);
    $pdf->SetTextColor(0,102,204);
    $pdf->Cell(0,8,$title,0,1,'L');
    $pdf->SetTextColor(0,0,0);

    $pdf->SetDrawColor(200,200,200);
    $pdf->Line($pdf->GetX(),$pdf->GetY(),$pdf->GetX()+180,$pdf->GetY());
    $pdf->Ln(3);

    $startX = $pdf->GetX();
    $startY = $pdf->GetY();

    $pdf->Image($chartFile,$startX,$startY,$pdfWidth,$pdfHeight,'PNG');
    $textHeight = $pdfHeight;

    if($fullWidth){
        $pdf->Ln($textHeight+5);
        $pdf->SetFont('helvetica','',10);
        $pdf->MultiCell(180,5,$text,0,'L');
        $pdf->Ln(8);
    } else {
        $textX = $startX + $pdfWidth + 5;
        $textY = $startY;
        $textWidth = 180 - $pdfWidth - 5;

        $pdf->SetXY($textX,$textY);
        $pdf->SetFont('helvetica','',10);
        $pdf->SetFillColor(250,250,250);
        $pdf->Rect($textX,$textY,$textWidth,$textHeight,'F');
        $pdf->SetXY($textX+3,$textY+3);
        $pdf->MultiCell($textWidth-6,5,$text,0,'L');

        $pdf->SetY(max($startY+$textHeight,$pdf->GetY())+8);
    }
}

// SECTIONS
$totalFound = $claimed+$unclaimed;
$foundText = "Total Found Items: $totalFound\nClaimed: $claimed (".($totalFound>0?round($claimed/$totalFound*100,2):0) . "%)\nUnclaimed: $unclaimed (".($totalFound>0?round($unclaimed/$totalFound*100,2):0)."%)\n\nSystem effectiveness in returning items to owners.";
addSection($pdf,"1. Found Items Status",$foundChart,$foundText);

$totalLost = $active+$expired+$closed;
$lostText = "Total Lost Reports: $totalLost\nActive: $active (".($totalLost>0?round($active/$totalLost*100,2):0)."%)\nExpired: $expired (".($totalLost>0?round($expired/$totalLost*100,2):0)."%)\nClosed: $closed (".($totalLost>0?round($closed/$totalLost*100,2):0)."%)\n\nOngoing searches vs resolved cases.";
addSection($pdf,"2. Lost Items Status",$lostChart,$lostText);

$locText="Locations breakdown:\n\n";
foreach($locRows as $r){
    $locText.="• ".$r['location_name']." | Found: ".$r['found_count']." | Lost: ".$r['lost_count']."\n";
}
$pdf->AddPage();
addSection($pdf,"3. Lost & Found per Location",$locationChart,$locText,true,true);

$totalClaims=array_sum($claimData);
$claimText="Total Claims: $totalClaims\n\n";
foreach($claimRows as $r){
    $perc=$totalClaims>0?round($r['count']/$totalClaims*100,2):0;
    $claimText.=ucfirst($r['status']).": ".$r['count']." ($perc%)\n";
}
addSection($pdf,"4. Claim Requests Status",$claimChart,$claimText);

// OUTPUT & CLEANUP
$filterName = strtoupper($type);
$today = date('Y-m-d');

$filename = "FOUND-IT_REPORT_{$filterName}_{$today}.pdf";

$pdf->Output($filename, 'I');
unlink($foundChart); unlink($lostChart); unlink($locationChart); unlink($claimChart);
?>