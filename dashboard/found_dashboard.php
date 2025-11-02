<?php
session_start();
require_once '../dbconnect.php';

// CHECK IF LOGGED IN
if (!isset($_SESSION['user_id'])) {
    header("Location: ../accounts/login.php");
    exit;
}

$database = new Database();
$conn = $database->getConnect();

if (!$conn) {
    die("Database connection failed.");
}

// FETCH ITEMS THEN JOIN W/ CATEGORY & LOCATION
$query = "
    SELECT f.fnd_id, f.fnd_name, f.fnd_datetime, 
           f.fnd_status, c.category_name, l.location_name
    FROM found_report f
    INNER JOIN item_category c ON f.category_id = c.category_id
    INNER JOIN location_table l ON f.location_id = l.location_id
    ORDER BY f.fnd_datetime DESC
";
$stmt = $conn->prepare($query);
$stmt->execute();
$found_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FOUND-IT | Found Items Dashboard</title>
  <?php include '../imports.php'; ?>
</head>

<body class="bg-light">
  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold" href="#">FOUND-IT</a>
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

  <!-- HEADER -->
  <div class="container py-5">
    <div class="text-center mb-5">
      <h2 class="fw-bold text-danger">Found Items Dashboard</h2>
      <p class="text-muted">Browse reported found items and submit a claim for verification.</p>
    </div>

    <div class="row g-4 justify-content-center">
      <?php if (count($found_items) > 0): ?>
        <?php foreach ($found_items as $item): ?>
          <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
              <div class="card-body text-center">
                <h5 class="fw-bold text-danger mb-2"><?= htmlspecialchars($item['fnd_name']) ?></h5>
                <p class="text-muted small mb-1">
                  <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($item['location_name']) ?>
                </p>
                <p class="text-muted small mb-1">
                  <i class="bi bi-tag"></i> <?= htmlspecialchars($item['category_name']) ?>
                </p>
                <p class="text-muted small mb-2">
                  <i class="bi bi-clock"></i> <?= date("F j, Y, g:i A", strtotime($item['fnd_datetime'])) ?>
                </p>

                <span class="badge 
                  <?= $item['fnd_status'] === 'unclaimed' ? 'bg-warning text-dark' : 
                      ($item['fnd_status'] === 'claimed' ? 'bg-success' : 'bg-secondary'); ?>">
                  <?= ucfirst($item['fnd_status']); ?>
                </span>

                <div class="d-grid mt-3">
                  <button type="button" 
                          class="btn btn-danger btn-sm fw-semibold" 
                          data-bs-toggle="modal" 
                          data-bs-target="#itemModal<?= $item['fnd_id'] ?>">
                    <i class="bi bi-eye"></i> Verify Claim
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- MODAL -->
          <div class="modal fade" id="itemModal<?= $item['fnd_id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
              <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                  <h5 class="modal-title fw-bold"><?= htmlspecialchars($item['fnd_name']) ?></h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                  <p><strong>Category:</strong> <?= htmlspecialchars($item['category_name']) ?></p>
                  <p><strong>Location Found:</strong> <?= htmlspecialchars($item['location_name']) ?></p>
                  <p><strong>Date/Time:</strong> <?= date("F j, Y, g:i A", strtotime($item['fnd_datetime'])) ?></p>
                  <p>
                    <strong>Status:</strong> 
                    <span class="badge 
                      <?= $item['fnd_status'] === 'unclaimed' ? 'bg-warning text-dark' : 
                          ($item['fnd_status'] === 'claimed' ? 'bg-success' : 'bg-secondary'); ?>">
                      <?= ucfirst($item['fnd_status']); ?>
                    </span>
                  </p>

                  <?php if ($item['fnd_status'] === 'unclaimed'): ?>
                    <hr>
                    <h6 class="fw-bold text-danger mb-3">Claim Verification Form</h6>
                    <form action="claim_item.php" method="POST" class="text-start">
                      <input type="hidden" name="fnd_id" value="<?= $item['fnd_id'] ?>">

                      <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" name="claimer_name" class="form-control" required>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-semibold">Student ID / Valid ID No.</label>
                        <input type="text" name="claimer_id" class="form-control" required>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="claimer_email" class="form-control" required>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-semibold">Describe Proof of Ownership</label>
                        <textarea name="claimer_proof_desc" class="form-control" rows="3" placeholder="Provide a short description proving that the item belongs to you..." required></textarea>
                      </div>

                      <div class="d-grid">
                        <button type="submit" class="btn btn-success fw-semibold">
                          <i class="bi bi-check-circle"></i> Submit Claim for Verification
                        </button>
                      </div>
                    </form>
                  <?php endif; ?>
                </div>

                <div class="modal-footer">
                  <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12 text-center">
          <div class="alert alert-info">No found items reported yet.</div>
        </div>
      <?php endif; ?>
    </div>

    <!-- BACK BUTTON -->
    <div class="text-center mt-5">
      <a href="item_dashboard.php" class="btn btn-outline-secondary fw-semibold">
        <i class="bi bi-arrow-left"></i> Back to Item Dashboard
      </a>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
