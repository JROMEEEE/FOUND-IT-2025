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

$user_name = htmlspecialchars($_SESSION['user_name']);
$is_admin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 0;

if ($is_admin != 1) {
    header("Location: user_dashboard.php");
    exit;
}

$db = new Database();
$conn = $db->getConnect();

try {
    // TOTAL COUNTS
    $totalLost = $conn->query("SELECT COUNT(*) AS total FROM lost_report")->fetch(PDO::FETCH_ASSOC)['total'];
    $totalFound = $conn->query("SELECT COUNT(*) AS total FROM found_report")->fetch(PDO::FETCH_ASSOC)['total'];
    $totalClaims = $conn->query("SELECT COUNT(*) AS total FROM claim_request WHERE status='approved'")->fetch(PDO::FETCH_ASSOC)['total'];
    $totalClaimed = $conn->query("SELECT COUNT(*) AS total FROM claim_request WHERE status='claimed'")->fetch(PDO::FETCH_ASSOC)['total'];

    // MONTHLY LOST & FOUND DATA
    $stmt = $conn->query("
        SELECT MONTH(lost_datetime) AS month, COUNT(*) AS lost_count, 0 AS found_count
        FROM lost_report
        GROUP BY MONTH(lost_datetime)
        UNION ALL
        SELECT MONTH(fnd_datetime) AS month, 0 AS lost_count, COUNT(*) AS found_count
        FROM found_report
        GROUP BY MONTH(fnd_datetime)
    ");
    $rawData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // MERGE MONTH DATA
    $monthNames = [1=>'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    $months = [];
    $lostData = [];
    $foundData = [];

    foreach ($monthNames as $num => $name) {
        $lost = 0;
        $found = 0;
        foreach ($rawData as $row) {
            if ($row['month'] == $num) {
                $lost += $row['lost_count'];
                $found += $row['found_count'];
            }
        }
        $months[] = $name;
        $lostData[] = $lost;
        $foundData[] = $found;
    }

    //  LOST ITEMS BY LOCATION //
    $lostByLocation = $conn->query("
        SELECT lt.location_name AS location, COALESCE(COUNT(lr.lost_id), 0) AS count 
        FROM location_table lt 
        LEFT JOIN lost_report lr ON lt.location_id = lr.location_id 
        GROUP BY lt.location_name 
        ORDER BY lt.location_name
    ")->fetchAll(PDO::FETCH_ASSOC);
    $lostLocations = array_column($lostByLocation, 'location');
    $lostCounts = array_column($lostByLocation, 'count');

    // FOUND ITEMS BY LOCATION //
    $foundByLocation = $conn->query("
        SELECT lt.location_name AS location, COALESCE(COUNT(fr.fnd_id), 0) AS count 
        FROM location_table lt 
        LEFT JOIN found_report fr ON lt.location_id = fr.location_id 
        GROUP BY lt.location_name 
        ORDER BY lt.location_name
    ")->fetchAll(PDO::FETCH_ASSOC);
    $foundLocations = array_column($foundByLocation, 'location');
    $foundCounts = array_column($foundByLocation, 'count');

    // CLAIM STATUS DATA  //
    $claimStatus = $conn->query("
        SELECT status, COUNT(*) AS count 
        FROM claim_request 
        GROUP BY status
    ")->fetchAll(PDO::FETCH_ASSOC);
    $claimLabels = array_column($claimStatus, 'status');
    $claimCounts = array_column($claimStatus, 'count');

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FOUND-IT | Statistics</title>
  <?php include '../imports.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm fixed-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="admin_dashboard.php">FOUND-IT Admin</a>
    <div class="collapse navbar-collapse justify-content-end">
      <ul class="navbar-nav align-items-center">
        <li class="nav-item mx-2">
          <a class="nav-link text-white fw-semibold" href="admin_dashboard.php">
            <i class="bi bi-speedometer2"></i> Dashboard
          </a>
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

<!-- CONTENT -->
<div class="container py-5 mt-5">
  <div class="text-center mb-5">
    <h2 class="fw-bold text-danger">System Statistics Overview</h2>
    <p class="text-muted">Visual analytics of LOST, FOUND, and CLAIMED items.</p>
  </div>

  <!-- SUMMARY CARDS -->
  <div class="row text-center mb-5">
    <div class="col-md-3 mb-3">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5 class="fw-bold text-danger">Total Lost Items</h5>
          <h2 class="fw-bold"><?= $totalLost ?></h2>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5 class="fw-bold text-warning">Total Found Items</h5>
          <h2 class="fw-bold"><?= $totalFound ?></h2>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5 class="fw-bold text-success">Total Claims Approved</h5>
          <h2 class="fw-bold"><?= $totalClaims ?></h2>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5 class="fw-bold text-primary">Total Items Claimed</h5>
          <h2 class="fw-bold"><?= $totalClaimed ?></h2>
        </div>
      </div>
    </div>
  </div>

  <!-- CHART CARDS -->
  <div class="row g-4 justify-content-center mb-4">
    <!-- PIE CHART -->
    <div class="col-md-6 d-flex">
      <div class="card shadow border-0 w-100">
        <div class="card-body d-flex flex-column justify-content-center">
          <h5 class="text-center fw-bold text-danger mb-3">Item Status Breakdown</h5>
          <div style="height: 300px;">
            <canvas id="itemChart"></canvas>
          </div>
          <div class="text-center mt-3">
            <button id="downloadItemChart" class="btn btn-outline-danger btn-sm fw-semibold">
              <i class="bi bi-download"></i> Download as PNG
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- BAR CHART -->
    <div class="col-md-6 d-flex">
      <div class="card shadow border-0 w-100">
        <div class="card-body d-flex flex-column justify-content-center">
          <h5 class="text-center fw-bold text-danger mb-3">Monthly Item Reports</h5>
          <div style="height: 300px;">
            <canvas id="monthlyChart"></canvas>
          </div>
          <div class="text-center mt-3">
            <button id="downloadMonthlyChart" class="btn btn-outline-danger btn-sm fw-semibold">
              <i class="bi bi-download"></i> Download as PNG
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- LOCATION CHARTS -->
  <div class="row g-4 justify-content-center">
    <!-- LOST BY LOCATION -->
    <div class="col-md-6 d-flex">
      <div class="card shadow border-0 w-100">
        <div class="card-body d-flex flex-column justify-content-center">
          <h5 class="text-center fw-bold text-danger mb-3">Lost Items by Location</h5>
          <div style="height: 300px;">
            <canvas id="lostLocationChart"></canvas>
          </div>
          <div class="text-center mt-3">
            <button id="downloadLostLocationChart" class="btn btn-outline-danger btn-sm fw-semibold">
              <i class="bi bi-download"></i> Download as PNG
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- FOUND BY LOCATION -->
    <div class="col-md-6 d-flex">
      <div class="card shadow border-0 w-100">
        <div class="card-body d-flex flex-column justify-content-center">
          <h5 class="text-center fw-bold text-warning mb-3">Found Items by Location</h5>
          <div style="height: 300px;">
            <canvas id="foundLocationChart"></canvas>
          </div>
          <div class="text-center mt-3">
            <button id="downloadFoundLocationChart" class="btn btn-outline-danger btn-sm fw-semibold">
              <i class="bi bi-download"></i> Download as PNG
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- CLAIM STATUS CHART -->
  <div class="row g-4 justify-content-center mt-4">
    <div class="col-md-6 d-flex">
      <div class="card shadow border-0 w-100">
        <div class="card-body d-flex flex-column justify-content-center">
          <h5 class="text-center fw-bold text-primary mb-3">Claims by Status</h5>
          <div style="height: 300px;">
            <canvas id="claimStatusChart"></canvas>
          </div>
          <div class="text-center mt-3">
            <button id="downloadClaimStatusChart" class="btn btn-outline-primary btn-sm fw-semibold">
              <i class="bi bi-download"></i> Download as PNG
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- BACK BUTTON -->
  <div class="text-center mt-5">
    <a href="admin_dashboard.php" class="btn btn-outline-secondary fw-semibold">
      <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>
  </div>
  
</div>

<!-- CHART.JS -->
<script>
// SHOWS TOTAL DATA FOR PIE CHART
const itemChart = new Chart(document.getElementById('itemChart'), {
  type: 'pie',
  data: {
    labels: ['Lost Items', 'Found Items', 'Claims Approved', 'Items Claimed'],
    datasets: [{
      data: [<?= $totalLost ?>, <?= $totalFound ?>, <?= $totalClaims ?>, <?= $totalClaimed ?>],
      backgroundColor: ['#dc3545', '#ffc107', '#198754', '#0d6efd'] // Red, Yellow, Green, Blue
    }]
  },
  options: { 
    plugins: { legend: { position: 'bottom' } }, // Display legend at bottom
    maintainAspectRatio: false
  }
});

// MONTHLY ITEM REPORTS
const monthlyChart = new Chart(document.getElementById('monthlyChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($months) ?>, // Month names Jan-Dec
    datasets: [
      { label: 'Lost Items', data: <?= json_encode($lostData) ?>, backgroundColor: '#dc3545' },
      { label: 'Found Items', data: <?= json_encode($foundData) ?>, backgroundColor: '#ffc107' }
    ]
  },
  options: { 
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom' } },
    scales: { y: { beginAtZero: true } } // Y-axis starts at 0
  }
});

// LOST ITEMS BY LOC.
const lostLocationChart = new Chart(document.getElementById('lostLocationChart'), {
  type: 'bar',
  data: { 
    labels: <?= json_encode($lostLocations) ?>, // Location names
    datasets: [{ label: 'Lost Items', data: <?= json_encode($lostCounts) ?>, backgroundColor: '#dc3545' }] 
  },
  options: { 
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom' } },
    scales: { y: { beginAtZero: true } }
  }
});

// FOUND ITEM BY LOC.
const foundLocationChart = new Chart(document.getElementById('foundLocationChart'), {
  type: 'bar',
  data: { 
    labels: <?= json_encode($foundLocations) ?>, 
    datasets: [{ label: 'Found Items', data: <?= json_encode($foundCounts) ?>, backgroundColor: '#ffc107' }] 
  },
  options: { 
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom' } },
    scales: { y: { beginAtZero: true } }
  }
});

// CLAIM BY STATUS (CLAIMED/APPROVED/REJECTED/PENDING)
const claimStatusChart = new Chart(document.getElementById('claimStatusChart'), {
  type: 'pie',
  data: { 
    labels: <?= json_encode($claimLabels) ?>, // Status names
    datasets: [{ data: <?= json_encode($claimCounts) ?>, backgroundColor: ['#198754','#0d6efd','#ffc107','#dc3545'] }] 
  },
  options: { 
    plugins: { legend: { position: 'bottom' } },
    maintainAspectRatio: false
  }
});

// TO ALLOW DL CHARTS
document.getElementById('downloadItemChart').addEventListener('click', () => { // FIND CLICK & WHAT TO DL
  const link = document.createElement('a'); // CREATE TEMP LINK TO TRIGGER DL
  link.download = 'item_chart.png';  // FILE NAME
  link.href = itemChart.toBase64Image();  // BASE64 TO PNG
  link.click(); // CLICKS TEMP LINK AUTOMATICALLY
});

document.getElementById('downloadMonthlyChart').addEventListener('click', () => { 
  const link = document.createElement('a'); 
  link.download = 'monthly_chart.png'; 
  link.href = monthlyChart.toBase64Image(); 
  link.click(); 
});

document.getElementById('downloadLostLocationChart').addEventListener('click', () => { 
  const link = document.createElement('a'); 
  link.download = 'lost_location_chart.png'; 
  link.href = lostLocationChart.toBase64Image(); 
  link.click(); 
});

document.getElementById('downloadFoundLocationChart').addEventListener('click', () => { 
  const link = document.createElement('a'); 
  link.download = 'found_location_chart.png'; 
  link.href = foundLocationChart.toBase64Image(); 
  link.click(); 
});

document.getElementById('downloadClaimStatusChart').addEventListener('click', () => { 
  const link = document.createElement('a'); 
  link.download = 'claim_status_chart.png'; 
  link.href = claimStatusChart.toBase64Image(); 
  link.click(); 
});

</script>
</body>
</html>