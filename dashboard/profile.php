<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../accounts/login.php");
    exit;
}

$database = new Database();
$conn = $database->getConnect();
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT user_name, sr_code, email FROM users_table WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// FOUND REPORTS
$stmt = $conn->prepare("
    SELECT fnd_id, fnd_name, fnd_datetime, image_path, fnd_status
    FROM found_report
    WHERE user_id = ?
    ORDER BY fnd_datetime DESC
");
$stmt->execute([$user_id]);
$found_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// CLAIM REQUESTS
$stmt = $conn->prepare("
    SELECT cr.request_id, cr.ticket_code, cr.status, cr.request_date, fr.fnd_name
    FROM claim_request cr
    LEFT JOIN found_report fr ON cr.fnd_id = fr.fnd_id
    WHERE cr.user_id = ?
    ORDER BY cr.request_date DESC
");
$stmt->execute([$user_id]);
$claim_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Profile | FOUND-IT</title>
  <?php include '../imports.php'; ?>

  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

  <style>
    body {
        padding-top: 80px;
    }
    div.dataTables_wrapper div.dataTables_filter {
        margin-bottom: 15px;
    }
    div.dataTables_wrapper div.dataTables_paginate {
        margin-top: 15px;
    }
    table.dataTable td, table.dataTable th {
        vertical-align: middle;
    }
    .card {
        border-radius: 10px;
    }
  </style>

</head>

<body class="bg-light">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm fixed-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="user_dashboard.php">FOUND-IT</a>
    <div class="d-flex">
      <a href="../accounts/logout.php" class="btn btn-light btn-sm text-danger fw-semibold">
        <i class="bi bi-box-arrow-right"></i> Logout
      </a>
    </div>
  </div>
</nav>

<div class="container py-5">
<!-- USER INFO -->
<div class="card shadow-sm mb-5 mx-auto" style="max-width: 600px;">
    <div class="card-body">
        <h4 class="fw-bold text-danger text-center">
            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($user['user_name']); ?>
        </h4>
        <hr>
        <p class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($user['email']); ?></p>
        <p class="mb-0"><strong>SR Code:</strong> <?= $user['sr_code'] ? htmlspecialchars($user['sr_code']) : '<span class="text-muted">Not Provided</span>'; ?></p>
    </div>
</div>



  <!-- FOUND REPORTS -->
  <div class="card shadow-sm mb-5">
    <div class="card-header bg-danger text-white fw-semibold">
      <i class="bi bi-binoculars"></i> Items You Reported Found
    </div>
    <div class="card-body">
      <?php if (empty($found_reports)): ?>
        <div class="text-muted fst-italic text-center py-3">You have not reported any found items.</div>
      <?php else: ?>
        <div class="table-responsive mt-3">
          <table id="foundReportsTable" class="table table-hover align-middle">
            <thead class="table-danger">
              <tr>
                <th>Item</th>
                <th>Status</th>
                <th>Date Reported</th>
                <th>Image</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($found_reports as $row): ?>
                <tr>
                  <td><?= htmlspecialchars($row['fnd_name']); ?></td>
                  <td><span class="badge bg-dark text-uppercase"><?= htmlspecialchars($row['fnd_status']); ?></span></td>
                  <td><?= date("M d, Y h:i A", strtotime($row['fnd_datetime'])); ?></td>
                  <td>
                    <?php if ($row['image_path'] && file_exists("../" . $row['image_path'])): ?>
                      <img src="../<?= htmlspecialchars($row['image_path']); ?>" class="rounded" style="height:60px; object-fit:cover;">
                    <?php else: ?>
                      <span class="text-muted">No Image</span>
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

  <!-- CLAIM REQUESTS -->
  <div class="card shadow-sm mb-5">
    <div class="card-header bg-danger text-white fw-semibold">
      <i class="bi bi-ticket-perforated"></i> Your Claim Requests
    </div>
    <div class="card-body">
      <?php if (empty($claim_requests)): ?>
        <div class="text-muted fst-italic text-center py-3">You have not submitted any claim requests.</div>
      <?php else: ?>
        <div class="table-responsive mt-3">
          <table id="claimRequestsTable" class="table table-hover align-middle">
            <thead class="table-danger">
              <tr>
                <th>Ticket Code</th>
                <th>Item</th>
                <th>Status</th>
                <th>Date Requested</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($claim_requests as $row): ?>
                <?php
                $badgeClass = match($row['status']) {
                    'approved' => 'success',
                    'rejected' => 'danger',
                    default => 'secondary',
                };
                ?>
                <tr>
                  <td><span class="badge bg-dark"><?= htmlspecialchars($row['ticket_code']); ?></span></td>
                  <td><?= htmlspecialchars($row['fnd_name']); ?></td>
                  <td><span class="badge bg-<?= $badgeClass ?> text-uppercase"><?= htmlspecialchars($row['status']); ?></span></td>
                  <td><?= date("M d, Y h:i A", strtotime($row['request_date'])); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<script>
$(document).ready(function() {
    $('#foundReportsTable').DataTable({
        pageLength: 5,
        order: [[2, 'desc']],
        responsive: true,
        language: { search: "_INPUT_", searchPlaceholder: "Search found items..." }
    });

    $('#claimRequestsTable').DataTable({
        pageLength: 5,
        order: [[3, 'desc']],
        responsive: true,
        language: { search: "_INPUT_", searchPlaceholder: "Search claims..." }
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
