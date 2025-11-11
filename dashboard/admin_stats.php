<?php
session_start();
include '../dbconnect.php';

// SESSION TIMEOUT
$session_lifetime = 3600;
if (!isset($_SESSION['user_id']) || (time() - $_SESSION['last_activity'] > $session_lifetime)) {
    session_unset();
    session_destroy();
    header("Location: ../accounts/login.php");
    exit;
}
$_SESSION['last_activity'] = time();

$is_admin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 0;
if ($is_admin != 1) {
    header("Location: user_dashboard.php");
    exit;
}

$db = new Database();
$conn = $db->getConnect();

// TOTAL COUNTS
$totalLost = $conn->query("SELECT COUNT(*) AS total FROM lost_report")->fetch(PDO::FETCH_ASSOC)['total'];
$totalFound = $conn->query("SELECT COUNT(*) AS total FROM found_report")->fetch(PDO::FETCH_ASSOC)['total'];
$totalClaims = $conn->query("SELECT COUNT(*) AS total FROM claim_request WHERE status='approved'")->fetch(PDO::FETCH_ASSOC)['total'];

// MONTHLY DATA
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

// Prepare monthly arrays
$monthNames = [1=>'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$months = $lostData = $foundData = [];
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

// LOST ITEMS BY LOCATION (include all locations)
$locStmt = $conn->query("
    SELECT l.location_name, COUNT(lr.lost_id) AS lost_count
    FROM location_table l
    LEFT JOIN lost_report lr ON lr.location_id = l.location_id
    GROUP BY l.location_id
    ORDER BY lost_count DESC
");
$locData = $locStmt->fetchAll(PDO::FETCH_ASSOC);

$locationNames = [];
$locationCounts = [];
foreach ($locData as $row) {
    $locationNames[] = $row['location_name'];
    $locationCounts[] = (int)$row['lost_count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FOUND-IT | Statistics</title>
<?php include '../imports.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">

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

<div class="container py-5 mt-5">
  <div class="text-center mb-5">
    <h2 class="fw-bold text-danger">System Statistics Overview</h2>
    <p class="text-muted">Visual analytics of LOST, FOUND, and CLAIMED items.</p>
  </div>

  <!-- SUMMARY CARDS -->
  <div class="row text-center mb-5">
    <div class="col-md-4 mb-3">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5 class="fw-bold text-danger">Total Lost Items</h5>
          <h2 class="fw-bold"><?= $totalLost ?></h2>
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-3">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5 class="fw-bold text-warning">Total Found Items</h5>
          <h2 class="fw-bold"><?= $totalFound ?></h2>
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-3">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5 class="fw-bold text-success">Total Claimed</h5>
          <h2 class="fw-bold"><?= $totalClaims ?></h2>
        </div>
      </div>
    </div>
  </div>

  <!-- CHARTS -->
  <div class="row g-4 justify-content-center">
    <!-- PIE CHART: ITEM STATUS -->
    <div class="col-md-6 d-flex">
      <div class="card shadow border-0 w-100">
        <div class="card-body d-flex flex-column justify-content-center">
          <h5 class="text-center fw-bold text-danger mb-3">Item Status Breakdown</h5>
          <div style="height: 300px;"><canvas id="itemChart"></canvas></div>
          <div class="text-center mt-3">
            <button id="downloadItemChart" class="btn btn-outline-danger btn-sm fw-semibold">
              <i class="bi bi-download"></i> Download PNG
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- BAR CHART: MONTHLY REPORT -->
    <div class="col-md-6 d-flex">
      <div class="card shadow border-0 w-100">
        <div class="card-body d-flex flex-column justify-content-center">
          <h5 class="text-center fw-bold text-danger mb-3">Monthly Item Reports</h5>
          <div style="height: 300px;"><canvas id="monthlyChart"></canvas></div>
          <div class="text-center mt-3">
            <button id="downloadMonthlyChart" class="btn btn-outline-danger btn-sm fw-semibold">
              <i class="bi bi-download"></i> Download PNG
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- BAR CHART: LOST ITEMS BY LOCATION -->
    <div class="col-md-6 d-flex mt-4">
      <div class="card shadow border-0 w-100">
        <div class="card-body d-flex flex-column justify-content-center">
          <h5 class="text-center fw-bold text-danger mb-3">Lost Items by Location</h5>
          <div style="height: 300px;"><canvas id="locationChart"></canvas></div>
          <div class="text-center mt-3">
            <button id="downloadLocationChart" class="btn btn-outline-danger btn-sm fw-semibold">
              <i class="bi bi-download"></i> Download PNG
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
const itemChart = new Chart(document.getElementById('itemChart'), {
  type: 'pie',
  data: {
    labels: ['Lost Items', 'Found Items', 'Claimed Items'],
    datasets: [{ data: [<?= $totalLost ?>, <?= $totalFound ?>, <?= $totalClaims ?>], backgroundColor: ['#dc3545','#ffc107','#198754'] }]
  },
  options: { plugins: { legend: { position: 'bottom' } }, maintainAspectRatio: false }
});

const monthlyChart = new Chart(document.getElementById('monthlyChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($months) ?>,
    datasets: [
      { label: 'Lost Items', data: <?= json_encode($lostData) ?>, backgroundColor: '#dc3545' },
      { label: 'Found Items', data: <?= json_encode($foundData) ?>, backgroundColor: '#ffc107' }
    ]
  },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
});

const locationChart = new Chart(document.getElementById('locationChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($locationNames) ?>,
    datasets: [{ label: 'Lost Items', data: <?= json_encode($locationCounts) ?>, backgroundColor: '#dc3545' }]
  },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

// DOWNLOAD BUTTONS
document.getElementById('downloadItemChart').addEventListener('click', () => {
  const link = document.createElement('a');
  link.download = 'item_chart.png';
  link.href = itemChart.toBase64Image();
  link.click();
});
document.getElementById('downloadMonthlyChart').addEventListener('click', () => {
  const link = document.createElement('a');
  link.download = 'monthly_chart.png';
  link.href = monthlyChart.toBase64Image();
  link.click();
});
document.getElementById('downloadLocationChart').addEventListener('click', () => {
  const link = document.createElement('a');
  link.download = 'lost_items_by_location.png';
  link.href = locationChart.toBase64Image();
  link.click();
});
</script>
</body>
</html>
