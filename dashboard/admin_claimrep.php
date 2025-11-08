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
  
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
  
  <!-- jQuery & DataTables JS -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

  <!-- Custom spacing for DataTables -->
  <style>
      /* Space between search box and table */
      div.dataTables_wrapper div.dataTables_filter {
          margin-bottom: 15px;
      }

      /* Space above pagination */
      div.dataTables_wrapper div.dataTables_paginate {
          margin-top: 15px;
      }
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
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- DATATABLE SCRIPT -->
  <script>
    $(document).ready(function () {
      $('#claimsTable').DataTable({
        pageLength: 10,
        order: [[6, 'desc']], // Sort by Requested date column
        responsive: true,
        language: {
          search: "_INPUT_",
          searchPlaceholder: "Search claims..."
        }
      });
    });
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
