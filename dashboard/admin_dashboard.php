<?php
session_start();
include '../dbconnect.php';

// SESSION TIMEOUT (1 hour)
$session_lifetime = 3600;

// CHECK LOGIN + SESSION TIME
if (!isset($_SESSION['user_id']) || (time() - $_SESSION['last_activity'] > $session_lifetime)) {
    session_unset();
    session_destroy();
    header("Location: ../accounts/login.php");
    exit;
}
$_SESSION['last_activity'] = time(); // Refresh session time

// FETCH USER INFO
$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name']);
$email = htmlspecialchars($_SESSION['email']);
$is_admin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 0;

// RESTRICT ACCESS TO ADMINS ONLY
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
  <title>FOUND-IT | Admin Dashboard</title>
  <?php include '../imports.php'; ?>
</head>

<body class="bg-light">
  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm fixed-top">
    <div class="container">
      <a class="navbar-brand fw-bold" href="../index.php">FOUND-IT Admin</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
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

  <!-- ADMIN DASHBOARD CONTENT -->
  <div class="container py-5 mt-5">
    <div class="text-center mb-5">
      <h2 class="fw-bold text-danger">Welcome Admin, <?php echo $user_name; ?>!</h2>
      <p class="text-muted">Manage FOUND-IT system data and review user submissions here.</p>
    </div>

    <div class="row g-4 justify-content-center">
      <!-- USER MANAGEMENT -->
      <div class="col-md-4">
        <div class="card shadow border-0 h-100">
          <div class="card-body text-center">
            <i class="bi bi-people display-4 text-danger mb-3"></i>
            <h5 class="fw-bold mb-1">Manage Users</h5>
            <p class="text-muted small mb-3">View, approve, or remove registered users.</p>
            <a href="manage_users.php" class="btn btn-danger btn-sm fw-semibold">
              <i class="bi bi-person-gear"></i> Manage
            </a>
          </div>
        </div>
      </div>

      <!-- ITEM MANAGEMENT -->
      <div class="col-md-4">
        <div class="card shadow border-0 h-100">
          <div class="card-body text-center">
            <i class="bi bi-box-seam display-4 text-danger mb-3"></i>
            <h5 class="fw-bold mb-1">Manage Found/Lost Items</h5>
            <p class="text-muted small mb-3">Review and verify reported items in the database.</p>
            <a href="manage_items.php" class="btn btn-outline-danger btn-sm fw-semibold">
              <i class="bi bi-archive"></i> Review Items
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
            <p class="text-muted small mb-3">Verify and approve claim requests from users.</p>
            <a href="admin_claimrep.php" class="btn btn-danger btn-sm fw-semibold">
              <i class="bi bi-check-circle"></i> View Requests
            </a>
          </div>
        </div>
      </div>

      <!-- SYSTEM STATISTICS (Chart.js) -->
      <div class="col-md-4">
        <div class="card shadow border-0 h-100">
          <div class="card-body text-center">
            <i class="bi bi-bar-chart-line display-4 text-danger mb-3"></i>
            <h5 class="fw-bold mb-1">System Statistics</h5>
            <p class="text-muted small mb-3">Visualize data trends and system performance.</p>
            <a href="admin_stats.php" class="btn btn-outline-danger btn-sm fw-semibold">
              <i class="bi bi-graph-up"></i> View Analytics
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- BACK BUTTON -->
    <div class="text-center mt-5">
      <a href="user_dashboard.php" class="btn btn-outline-secondary fw-semibold">
        <i class="bi bi-arrow-left"></i> Back to Item Dashboard
      </a>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
