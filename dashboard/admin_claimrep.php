<?php
session_start();
require_once '../dbconnect.php';

// SESSION TIMEOUT (1 hour)
$session_lifetime = 3600;

if (!isset($_SESSION['user_id']) || (time() - $_SESSION['last_activity'] > $session_lifetime)) {
    session_unset();
    session_destroy();
    header("Location: ../accounts/login.php");
    exit;
}
$_SESSION['last_activity'] = time();

// FETCH SESSION DATA
$user_name = htmlspecialchars($_SESSION['user_name']);
$is_admin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 0;

// RESTRICT ACCESS
if ($is_admin != 1) {
    header("Location: user_dashboard.php");
    exit;
}

// FETCH CLAIM REQUESTS
try {
    $database = new Database();
    $conn = $database->getConnect();

    $query = "
        SELECT cr.*, 
               fr.fnd_name, fr.image_path,
               u.user_name AS claimer_name, 
               u.email AS claimer_email
        FROM claim_request cr
        LEFT JOIN found_report fr ON cr.fnd_id = fr.fnd_id
        LEFT JOIN users_table u ON cr.user_id = u.user_id
        ORDER BY cr.request_date DESC
    ";
    $stmt = $conn->query($query);
    $claims = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FOUND-IT | Claim Review</title>
  <?php include '../imports.php'; ?>
</head>

<body class="bg-light">
  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold" href="admin_dashboard.php">FOUND-IT Admin</a>
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

  <?php if (isset($_SESSION['claim_status_msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
      <?= htmlspecialchars($_SESSION['claim_status_msg']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['claim_status_msg']); ?>
  <?php endif; ?>

  <!-- PAGE HEADER -->
  <div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="fw-bold text-danger mb-0"><i class="bi bi-clipboard-check"></i> Claim Request Management</h3>
      <a href="admin_dashboard.php" class="btn btn-outline-danger fw-semibold">
        <i class="bi bi-arrow-left"></i> Back
      </a>
    </div>

    <!-- CLAIM REQUEST TABLE -->
    <div class="card shadow border-0">
      <div class="card-header bg-danger text-white fw-semibold">
        <i class="bi bi-list-ul"></i> Claim Requests
      </div>
      <div class="card-body">
        <?php if (empty($claims)): ?>
          <div class="alert alert-info text-center">No claim requests found.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-danger">
                <tr>
                  <th>ID</th>
                  <th>Ticket Code</th>
                  <th>Item</th>
                  <th>Claimer</th>
                  <th>Email</th>  
                  <th>Status</th>
                  <th>Requested</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($claims as $row): ?>
                  <?php
                    $status = $row['status'] ?? 'pending';
                    $badgeClass = ($status === 'approved') ? 'success' :
                                  (($status === 'rejected') ? 'danger' : 'warning');
                  ?>
                  <tr>
                    <td><?= $row['request_id'] ?></td>
                    <td><span class="badge bg-dark"><?= $row['ticket_code'] ?></span></td>
                    <td><?= htmlspecialchars($row['fnd_name']) ?></td>
                    <td><?= htmlspecialchars($row['claimer_name']) ?></td>
                    <td><?= htmlspecialchars($row['claimer_email']) ?></td>
                    <td><span class="badge bg-<?= $badgeClass ?>"><?= strtoupper($status) ?></span></td>
                    <td><?= date("M d, Y h:i A", strtotime($row['request_date'])) ?></td>
                    <td>
                      <?php if ($status === 'pending'): ?>
                        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#reviewModal<?= $row['request_id'] ?>">
                          <i class="bi bi-search"></i> Review
                        </button>
                      <?php else: ?>
                        <form method="POST" action="delete_claim.php" class="d-inline">
                          <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                          <button type="submit" class="btn btn-outline-secondary btn-sm" onclick="return confirm('Remove this claim permanently?');">
                            <i class="bi bi-trash"></i> Remove
                          </button>
                        </form>
                      <?php endif; ?>
                    </td>
                  </tr>

                  <!-- REVIEW MODAL -->
                  <div class="modal fade" id="reviewModal<?= $row['request_id'] ?>" tabindex="-1" aria-labelledby="modalLabel<?= $row['request_id'] ?>" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                      <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                          <h5 class="modal-title" id="modalLabel<?= $row['request_id'] ?>">
                            Review Claim Request #<?= htmlspecialchars($row['request_id']) ?>
                          </h5>
                          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                          <div class="row g-4">
                            <!-- LEFT COLUMN -->
                            <div class="col-md-6 text-center">
                              <h6 class="fw-bold text-danger mb-3">Found Item</h6>
                              <?php
                                $imgPath = "../" . ($row['image_path'] ?? '');
                                if (!empty($row['image_path']) && file_exists($imgPath)):
                              ?>
                                <img src="<?= htmlspecialchars($imgPath) ?>" class="img-fluid rounded shadow-sm mb-3" alt="Found Item" style="max-height: 250px; object-fit: cover;">
                              <?php else: ?>
                                <div class="alert alert-secondary small">No image available.</div>
                              <?php endif; ?>
                              <p class="small mb-1"><strong>Item Name:</strong> <?= htmlspecialchars($row['fnd_name']) ?></p>
                            </div>

                            <!-- RIGHT COLUMN -->
                            <div class="col-md-6">
                              <h6 class="fw-bold text-danger mb-3">Claimer’s Statement</h6>
                              <div class="border rounded bg-light p-3 mb-3" style="min-height: 200px; overflow-y: auto;">
                                <?php if (!empty($row['proof_of_ownership'])): ?>
                                  <p class="mb-0"><?= nl2br(htmlspecialchars($row['proof_of_ownership'])) ?></p>
                                <?php else: ?>
                                  <p class="text-muted fst-italic mb-0">No statement provided by the claimer.</p>
                                <?php endif; ?>
                              </div>

                              <?php if (!empty($row['proof_image'])): ?>
                                <div class="text-center mb-3">
                                  <img src="../<?= htmlspecialchars($row['proof_image']) ?>" class="img-fluid rounded shadow-sm" alt="Proof Image" style="max-height: 200px; object-fit: cover;">
                                </div>
                              <?php endif; ?>

                              <div class="border-top pt-2">
                                <p class="small mb-1"><strong>Claimer:</strong> <?= htmlspecialchars($row['claimer_name']) ?></p>
                                <p class="small mb-1"><strong>Email:</strong> <?= htmlspecialchars($row['claimer_email']) ?></p>
                              </div>
                            </div>
                          </div>
                        </div>

                        <div class="modal-footer justify-content-center">
                          <form method="POST" action="approve_claim.php" class="d-flex gap-3 m-0">
                            <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                            <input type="hidden" name="fnd_id" value="<?= $row['fnd_id'] ?>">
                            <button type="submit" name="action" value="approve" class="btn btn-success px-4">
                              <i class="bi bi-check-circle"></i> Approve
                            </button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger px-4">
                              <i class="bi bi-x-circle"></i> Reject
                            </button>
                          </form>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!-- END MODAL -->
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
