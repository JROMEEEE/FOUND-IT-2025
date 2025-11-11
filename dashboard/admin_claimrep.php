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
               cv.qr_image_path,
               u.user_name AS claimer_name, 
               u.email AS claimer_email
        FROM claim_request cr
        LEFT JOIN found_report fr ON cr.fnd_id = fr.fnd_id
        LEFT JOIN claim_verification cv ON cr.request_id = cv.request_id
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

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
    div.dataTables_wrapper div.dataTables_filter { margin-bottom: 15px; }
    div.dataTables_wrapper div.dataTables_paginate { margin-top: 15px; }
    .qr-preview { width: 100px; height: 100px; object-fit: contain; }
</style>
</head>
<body class="bg-light">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="admin_dashboard.php">FOUND-IT Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav align-items-center">
                <li class="nav-item mx-2">
                    <a class="nav-link text-white fw-semibold" href="admin_dashboard.php">
                        <i class="bi bi-house-door"></i> Dashboard
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
<div class="alert alert-success alert-dismissible fade show m-3 mt-5" role="alert">
    <?= htmlspecialchars($_SESSION['claim_status_msg']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['claim_status_msg']); endif; ?>

<div class="container py-5 mt-5">
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
                <div class="table-responsive mt-3">
                    <table id="claimsTable" class="table table-hover align-middle">
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
                                <th>Delete</th>
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
                                    <div class="d-flex gap-1">
                                        <?php if ($status === 'pending'): ?>
                                            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#reviewModal<?= $row['request_id'] ?>">
                                                <i class="bi bi-search"></i> Review
                                            </button>
                                        <?php elseif ($status === 'approved'): ?>
                                            <?php if (!empty($row['qr_image_path'])): ?>
                                                <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#qrModal<?= $row['request_id'] ?>">
                                                    <i class="bi bi-upc-scan"></i> QR
                                                </button>
                                            <?php else: ?>
                                                <span class="btn btn-outline-secondary btn-sm disabled">
                                                    <i class="bi bi-upc-scan"></i> QR
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <form action="delete_claim.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this claim?');">
                                        <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            <!-- REVIEW MODAL -->
                            <div class="modal fade" id="reviewModal<?= $row['request_id'] ?>" tabindex="-1" aria-labelledby="reviewModalLabel<?= $row['request_id'] ?>" aria-hidden="true">
                              <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                  <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title" id="reviewModalLabel<?= $row['request_id'] ?>">Review Claim #<?= $row['request_id'] ?></h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                  </div>
                                  <div class="modal-body">
                                    <div class="row">
                                      <!-- ITEM IMAGE -->
                                      <div class="col-md-5 text-center">
                                          <?php 
                                              $imgPath = !empty($row['image_path']) ? '../' . $row['image_path'] : '';
                                          ?>
                                          <?php if ($imgPath && file_exists($imgPath)): ?>
                                              <img src="<?= htmlspecialchars($imgPath) ?>" alt="Item Image" class="img-fluid rounded border">
                                          <?php else: ?>
                                              <div class="border rounded p-5 text-muted">No Image Available</div>
                                          <?php endif; ?>
                                      </div>

                                      <!-- ITEM DETAILS & STATEMENT -->
                                      <div class="col-md-7">
                                          <h5 class="fw-bold"><?= htmlspecialchars($row['fnd_name']) ?></h5>
                                          <p><strong>Claimer:</strong> <?= htmlspecialchars($row['claimer_name']) ?></p>
                                          <p><strong>Email:</strong> <?= htmlspecialchars($row['claimer_email']) ?></p>
                                          <p><strong>Ticket Code:</strong> <?= htmlspecialchars($row['ticket_code']) ?></p>
                                          <p><strong>Claim Date:</strong> <?= date("M d, Y h:i A", strtotime($row['request_date'])) ?></p>
                                          <hr>
                                          <p><strong>Claimer's Statement:</strong></p>
                                          <p><?= nl2br(htmlspecialchars($row['proof_of_ownership'] ?? 'No statement provided')) ?></p>
                                      </div>
                                    </div>
                                  </div>
                                  <div class="modal-footer">
                                    <!-- APPROVE FORM -->
                                    <form action="approve_claim.php" method="POST" class="d-inline">
                                        <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-success"><i class="bi bi-check-circle"></i> Approve</button>
                                    </form>

                                    <!-- REJECT FORM -->
                                    <form action="approve_claim.php" method="POST" class="d-inline">
                                        <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle"></i> Reject</button>
                                    </form>

                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                  </div>
                                </div>
                              </div>
                            </div>

                            <!-- QR MODAL -->
                            <?php if (!empty($row['qr_image_path'])): ?>
                            <div class="modal fade" id="qrModal<?= $row['request_id'] ?>" tabindex="-1" aria-labelledby="qrModalLabel<?= $row['request_id'] ?>" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title" id="qrModalLabel<?= $row['request_id'] ?>">QR Code for Claim #<?= $row['request_id'] ?></h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body text-center">
                                            <img src="<?= htmlspecialchars($row['qr_image_path']) ?>" alt="QR Code" class="img-fluid" style="max-width:300px;">
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function () {
    $('#claimsTable').DataTable({
        pageLength: 10,
        order: [[6, 'desc']],
        responsive: true,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search claims..."
        }
    });
});
</script>
</body>
</html>
