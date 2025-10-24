<?php
session_start();
include '../dbconnect.php';

// SESSION TIMEOUT: 1h (3600s)
$session_lifetime = 3600;

// CHECK IF USER IS LOGGED IN
if (!isset($_SESSION['user_id']) || (time() - $_SESSION['last_activity'] > $session_lifetime)) {
    session_unset(); // CLEAR ALL SESSION VARIABLES
    session_destroy(); // DESTROY SESSION
    header("Location: login.php"); // REDIRECT TO LOGIN
    exit;
}

// UPDATE ACTIVITY TIME
$_SESSION['last_activity'] = time();

$user_name = htmlspecialchars($_SESSION['user_name']);
$email = htmlspecialchars($_SESSION['email']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FOUND-IT | User Dashboard</title>
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

  <!-- DASHBOARD CONTENT -->
  <div class="container py-5">
    <div class="text-center mb-5">
      <h2 class="fw-bold text-danger">Welcome, <?php echo $user_name; ?>!</h2>
      <p class="text-muted">Here’s your FOUND-IT user dashboard overview.</p>
    </div>

    <div class="row g-4 justify-content-center">

      <!-- PROFILE -->
      <div class="col-md-4">
        <div class="card shadow border-0">
          <div class="card-body text-center">
            <i class="bi bi-person-circle display-4 text-danger mb-3"></i>
            <h5 class="fw-bold mb-1"><?php echo $user_name; ?></h5>
            <p class="text-muted small mb-3"><?php echo $email; ?></p>
            <a href="profile.php" class="btn btn-outline-danger btn-sm fw-semibold">
              <i class="bi bi-pencil"></i> Edit Profile
            </a>
          </div>
        </div>
      </div>

      <!-- ITEMS -->
      <div class="col-md-4">
        <div class="card shadow border-0">
          <div class="card-body text-center">
            <i class="bi bi-box-seam display-4 text-danger mb-3"></i>
            <h5 class="fw-bold mb-1">Lost & Found</h5>
            <p class="text-muted small mb-3">View or report lost and found items easily.</p>
            <a href="item_dashboard.php" class="btn btn-danger btn-sm fw-semibold">
              <i class="bi bi-search"></i> View Items
            </a>
          </div>
        </div>
      </div>

      <!-- ANNOUNCEMENTS -->
      <div class="col-md-4">
        <div class="card shadow border-0">
          <div class="card-body text-center">
            <i class="bi bi-megaphone display-4 text-danger mb-3"></i>
            <h5 class="fw-bold mb-1">Announcements</h5>
            <p class="text-muted small mb-3">Stay updated with campus announcements and events.</p>
            <a href="announcements.php" class="btn btn-outline-danger btn-sm fw-semibold">
              <i class="bi bi-newspaper"></i> View Updates
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- BUTTON HOME -->
    <div class="text-center mt-5">
      <a href="../index.php" class="btn btn-outline-secondary fw-semibold">
        <i class="bi bi-house-door"></i> Back to Home
      </a>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
