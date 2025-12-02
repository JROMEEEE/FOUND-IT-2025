<?php
session_start();
include '../dbconnect.php';

$database = new Database();
$conn = $database->getConnect();

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
$user_id   = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name']);
$email     = htmlspecialchars($_SESSION['email']);
$is_admin  = $_SESSION['is_admin'] ?? 0;

// RESTRICT ACCESS TO ADMINS ONLY
if ($is_admin != 1) {
    header("Location: user_dashboard.php");
    exit;
}

// GET CURRENT WEEK LOST ITEMS (LIMIT 4)
$lostStmt = $conn->prepare("
    SELECT l.lost_name, l.lost_datetime, u.user_name
    FROM lost_report l
    JOIN users_table u ON l.user_id = u.user_id
    WHERE YEARWEEK(l.lost_datetime, 1) = YEARWEEK(NOW(), 1)
    ORDER BY l.lost_datetime DESC
    LIMIT 4
");
$lostStmt->execute();
$new_lost_items = $lostStmt->fetchAll(PDO::FETCH_ASSOC);

// GET CURRENT WEEK FOUND ITEMS (LIMIT 4)
$foundStmt = $conn->prepare("
    SELECT f.fnd_name, f.fnd_datetime, u.user_name
    FROM found_report f
    JOIN users_table u ON f.user_id = u.user_id
    WHERE YEARWEEK(f.fnd_datetime, 1) = YEARWEEK(NOW(), 1)
    ORDER BY f.fnd_datetime DESC
    LIMIT 4
");
$foundStmt->execute();
$new_found_items = $foundStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FOUND-IT | Admin Dashboard</title>
  <link rel="stylesheet" href="../css/dashboard.css">
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
          <a class="nav-link text-white fw-semibold" href="../index.php">
            <i class="bi bi-house-door"></i> Dashboard
          </a>
        </li>
        <li class="nav-item mx-2">
          <a class="nav-link text-white fw-semibold" href="profile.php">
            <i class="bi bi-person-circle"></i> Profile
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

  <!-- WELCOME BANNER -->
  <div class="container admin-container">
    <div class="card shadow-sm mb-5 admin-card">
      <div class="text-left mb-5">
        <h2 class="fw-bold text-white" style="font-size: 40px;">Welcome, <?php echo $user_name; ?>!</h2>
        <p class="text-white">Manage FOUND-IT system data and review user submissions here.</p>
      </div>
    </div>
  </div>

  <!-- MAIN ACTIONS -->
  <div class="row g-4 justify-content-center">

    <!-- MANAGE USERS -->
    <div class="col-md-4">
      <div class="card shadow border-0 h-100 d-flex flex-column">
        <div class="card-body text-center d-flex flex-column">
          <i class="bi bi-people display-4 text-danger mb-3"></i>
          <h5 class="fw-bold mb-1">Manage Users</h5>
          <p class="text-muted small mb-3">View, approve, or remove registered users.</p>
          <div class="mt-auto">
            <a href="admin_manage_user.php" class="btn btn-danger btn-sm fw-semibold">
              <i class="bi bi-person-gear"></i> Manage
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- LOGBOOK -->
    <div class="col-md-4">
      <div class="card shadow border-0 h-100 d-flex flex-column">
        <div class="card-body text-center d-flex flex-column">
          <i class="bi bi-box-seam display-4 text-danger mb-3"></i>
          <h5 class="fw-bold mb-1">Transaction Logbook</h5>
          <p class="text-muted small mb-3">View all database transactions.</p>
          <div class="mt-auto">
            <a href="logbook.php" class="btn btn-outline-danger btn-sm fw-semibold">
              <i class="bi bi-archive"></i> Logbook
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- ITEM DATABASE MANAGEMENT -->
    <div class="col-md-4">
      <div class="card shadow border-0 h-100 d-flex flex-column">
        <div class="card-body text-center d-flex flex-column">
          <i class="bi bi-collection display-4 text-danger mb-3"></i>
          <h5 class="fw-bold mb-1">Item Database Management</h5>
          <p class="text-muted small mb-3">Manage Items and Claims.</p>
          <div class="mt-auto">
            <a href="admin_db_dashboard.php" class="btn btn-danger btn-sm fw-semibold">
              <i class="bi bi-box"></i> Open Management Panel
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- CHAT SUPPORT -->
    <div class="col-md-4">
      <div class="card shadow border-0 h-100 d-flex flex-column">
        <div class="card-body text-center d-flex flex-column">
          <i class="bi bi-chat-dots display-4 text-danger mb-3"></i>
          <h5 class="fw-bold mb-1">Chat Support</h5>
          <p class="text-muted small mb-3">Respond to user messages.</p>
          <div class="mt-auto">
            <a href="admin_user_chat.php" class="btn btn-danger btn-sm fw-semibold">
              <i class="bi bi-chat-dots-fill"></i> Open Chat
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- STATISTICS -->
    <div class="col-md-4">
      <div class="card shadow border-0 h-100 d-flex flex-column">
        <div class="card-body text-center d-flex flex-column">
          <i class="bi bi-bar-chart-line display-4 text-danger mb-3"></i>
          <h5 class="fw-bold mb-1">System Statistics</h5>
          <p class="text-muted small mb-3">Visualize data trends.</p>
          <div class="mt-auto">
            <a href="admin_stats.php" class="btn btn-outline-danger btn-sm fw-semibold">
              <i class="bi bi-graph-up"></i> View Analytics
            </a>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- COMPACT LOST & FOUND ANNOUNCEMENTS -->
  <div class="row mt-5">

    <!-- LOST ITEMS -->
    <div class="col-md-6">
      <h6 class="fw-bold text-danger mb-2">Recent Lost Items</h6>
      <?php if (!empty($new_lost_items)): ?>
        <?php foreach ($new_lost_items as $lost): ?>
          <div class="alert alert-danger py-1 px-2 mb-2 d-flex justify-content-between align-items-center" role="alert" style="font-size: 0.85rem;">
            <span><?php echo htmlspecialchars($lost['lost_name']); ?></span>
            <span class="badge bg-dark rounded-pill" style="font-size: 0.75rem;"><?php echo date('M d', strtotime($lost['lost_datetime'])); ?></span>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="alert alert-secondary py-1 px-2 text-center" role="alert" style="font-size: 0.85rem;">
          No lost items this week
        </div>
      <?php endif; ?>
    </div>

    <!-- FOUND ITEMS -->
    <div class="col-md-6">
      <h6 class="fw-bold text-success mb-2">Recent Found Items</h6>
      <?php if (!empty($new_found_items)): ?>
        <?php foreach ($new_found_items as $found): ?>
          <div class="alert alert-success py-1 px-2 mb-2 d-flex justify-content-between align-items-center" role="alert" style="font-size: 0.85rem;">
            <span><?php echo htmlspecialchars($found['fnd_name']); ?></span>
            <span class="badge bg-dark rounded-pill" style="font-size: 0.75rem;"><?php echo date('M d', strtotime($found['fnd_datetime'])); ?></span>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="alert alert-secondary py-1 px-2 text-center" role="alert" style="font-size: 0.85rem;">
          No found items this week
        </div>
      <?php endif; ?>
    </div>

  </div>

  <!-- BACK BUTTON -->
  <div class="text-center mt-5">
    <a href="user_dashboard.php" class="btn btn-outline-secondary fw-semibold">
      <i class="bi bi-arrow-left"></i> Back to Item Dashboard
    </a>
  </div>

</div>
</body>
</html>