<?php
session_start();
include '../dbconnect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../accounts/login.php");
    exit;
}

$user_name = htmlspecialchars($_SESSION['user_name']);
$email = htmlspecialchars($_SESSION['email']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FOUND-IT | Item Dashboard</title>
  <?php include '../imports.php'; ?>
</head>

<body class="bg-light">
  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold" href="../index.php">FOUND-IT</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
        <ul class="navbar-nav align-items-center">
          <li class="nav-item mx-2">
            <a class="nav-link text-white fw-semibold" href="user_dashboard.php">
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

  <div class="container py-5">
    <div class="text-center mb-5">
      <h2 class="fw-bold text-danger">Item Dashboard</h2>
      <p class="text-muted">Manage your lost and found reports easily.</p>
    </div>

    <div class="row g-4 justify-content-center">
      <!-- REPORT FOUND ITEM
      <div class="col-md-4 d-flex">
        <div class="card shadow border-0 flex-fill">
          <div class="card-body d-flex flex-column text-center">
            <i class="bi bi-box2-heart display-4 text-danger mb-3"></i>
            <h5 class="fw-bold mb-2">Report a Found Item</h5>
            <p class="text-muted small mb-4">Found something on campus? Help return it to its rightful owner.</p>
            <a href="founditem_form.php" class="btn btn-danger fw-semibold mt-auto">
              <i class="bi bi-plus-circle"></i> Report Found Item
            </a>
          </div>
        </div>
      </div> -->

      <!-- SEARCH FOUND ITEM -->
      <div class="col-md-4 d-flex">
        <div class="card shadow border-0 flex-fill">
          <div class="card-body d-flex flex-column text-center">
            <i class="bi bi-search-heart display-4 text-danger mb-3"></i>
            <h5 class="fw-bold mb-2">Search Found Items</h5>
            <p class="text-muted small mb-4">Browse reported found items and check if yours has been turned in.</p>
            <a href="found_dashboard.php" class="btn btn-danger fw-semibold mt-auto">
              <i class="bi bi-search"></i> Search Items
            </a>
          </div>
        </div>
      </div>

      <!-- REPORT LOST ITEM -->
      <div class="col-md-4 d-flex">
        <div class="card shadow border-0 flex-fill">
          <div class="card-body d-flex flex-column text-center">
            <i class="bi bi-flag display-4 text-danger mb-3"></i>
            <h5 class="fw-bold mb-2">Report a Lost Item</h5>
            <p class="text-muted small mb-4">Can’t find something? Create a report so others can help you locate it.</p>
            <a href="report_lost.php" class="btn btn-danger fw-semibold mt-auto">
              <i class="bi bi-flag-fill"></i> Report Lost Item
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- BACK BUTTON -->
    <div class="text-center mt-5">
      <a href="user_dashboard.php" class="btn btn-outline-secondary fw-semibold">
        <i class="bi bi-arrow-left"></i> Back to User Dashboard
      </a>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
