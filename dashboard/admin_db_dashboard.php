<?php
session_start();
include '../dbconnect.php';

$database = new Database();
$conn = $database->getConnect();

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
$is_admin  = $_SESSION['is_admin'] ?? 0;

if ($is_admin != 1) {
    header("Location: user_dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FOUND-IT | Item Management Panel</title>
  <?php include '../imports.php'; ?>
</head>

<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm fixed-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="admin_dashboard.php">FOUND-IT Admin</a>
  </div>
</nav>

<div class="container py-5 mt-5">

  <div class="text-center mb-4">
    <h2 class="fw-bold text-danger">Item Database Management</h2>
    <p class="text-muted">Manage found items, claims, QR scanning, and automatically decayed items.</p>
  </div>

  <div class="row g-4 justify-content-center">

    <!-- ADD FOUND ITEM -->
    <div class="col-md-4">
      <div class="card shadow border-0 h-100">
        <div class="card-body text-center">
          <i class="bi bi-box2-heart display-4 text-danger mb-3"></i>
          <h5 class="fw-bold mb-1">Add Found Item</h5>
          <p class="text-muted small mb-3">Record a newly found item.</p>
          <a href="founditem_form.php" class="btn btn-danger fw-semibold">
            <i class="bi bi-plus-circle"></i> Add Found Item
          </a>
        </div>
      </div>
    </div>

    <!-- CLAIM REQUESTS -->
    <div class="col-md-4">
      <div class="card shadow border-0 h-100">
        <div class="card-body text-center">
          <i class="bi bi-clipboard-check display-4 text-danger mb-3"></i>
          <h5 class="fw-bold mb-1">Claim Requests</h5>
          <p class="text-muted small mb-3">Review and approve user claim requests.</p>
          <a href="admin_claimrep.php" class="btn btn-danger fw-semibold">
            <i class="bi bi-check-circle"></i> View Requests
          </a>
        </div>
      </div>
    </div>

    <!-- SCAN CLAIM QR -->
    <div class="col-md-4">
      <div class="card shadow border-0 h-100">
        <div class="card-body text-center">
          <i class="bi bi-upc-scan display-4 text-danger mb-3"></i>
          <h5 class="fw-bold mb-1">Scan Claim QR</h5>
          <p class="text-muted small mb-3">Scan QR codes to validate item release.</p>
          <a href="scan_claim.php" class="btn btn-danger fw-semibold">
            <i class="bi bi-upc-scan"></i> Scan QR
          </a>
        </div>
      </div>
    </div>

    <!-- DECAYED ITEMS -->
    <div class="col-md-4">
      <div class="card shadow border-0 h-100">
        <div class="card-body text-center">
          <i class="bi bi-trash display-4 text-danger mb-3"></i>
          <h5 class="fw-bold mb-1">Decayed Items</h5>
          <p class="text-muted small mb-3">View items automatically removed after 7 days.</p>
          <a href="admin_item_decay.php" class="btn btn-danger fw-semibold">
            <i class="bi bi-eye"></i> View Decayed
          </a>
        </div>
      </div>
    </div>

  </div>

  <div class="text-center mt-5">
    <a href="admin_dashboard.php" class="btn btn-outline-secondary fw-semibold">
      <i class="bi bi-arrow-left"></i> Back to Admin Dashboard
    </a>
  </div>

</div>

</body>
</html>