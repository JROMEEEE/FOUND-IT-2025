<?php
session_start();
require_once '../dbconnect.php';

// CHECK LOGIN
if (!isset($_SESSION['user_id'])) {
    header("Location: ../accounts/login.php");
    exit;
}

$database = new Database();
$conn = $database->getConnect();
if (!$conn) die("Database connection failed.");

// GET CATEGORIES
$catStmt = $conn->prepare("SELECT category_id, category_name FROM item_category ORDER BY category_name");
$catStmt->execute();
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// GET SELECTED CATEGORY (OPTIONAL)
// Can come from query string: ?category_id=123
$selectedCategoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;

// FETCH FOUND ITEMS
if ($selectedCategoryId) {
    $stmt = $conn->prepare("
        SELECT f.fnd_id, f.fnd_name, f.fnd_datetime, f.fnd_status, c.category_name, l.location_name
        FROM found_report f
        INNER JOIN item_category c ON f.category_id = c.category_id
        INNER JOIN location_table l ON f.location_id = l.location_id
        WHERE c.category_id = :category_id
        ORDER BY f.fnd_datetime DESC
    ");
    $stmt->execute(['category_id' => $selectedCategoryId]);
} else {
    $stmt = $conn->prepare("
        SELECT f.fnd_id, f.fnd_name, f.fnd_datetime, f.fnd_status, c.category_name, l.location_name
        FROM found_report f
        INNER JOIN item_category c ON f.category_id = c.category_id
        INNER JOIN location_table l ON f.location_id = l.location_id
        ORDER BY f.fnd_datetime DESC
    ");
    $stmt->execute();
}
$found_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>FOUND-IT | Found Items Dashboard</title>
<?php include '../imports.php'; ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
</head>
<body class="bg-light">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm fixed-top">
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

<div class="container py-5 mt-5">
  <div class="text-center mb-4">
    <h2 class="fw-bold text-danger">Found Items Dashboard</h2>
    <p class="text-muted">Search items, filter by category, or submit a claim.</p>
  </div>

  <!-- CATEGORY FILTER -->
  <div class="row justify-content-center mb-3">
    <div class="col-md-4">
      <select id="categoryFilter" class="form-select">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['category_id'] ?>" <?= $cat['category_id'] == $selectedCategoryId ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat['category_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- TABLE -->
  <div class="table-responsive bg-white p-3 shadow-sm rounded">
    <table id="foundTable" class="table table-striped table-hover align-middle">
      <thead class="table-danger">
        <tr>
          <th>Item Name</th>
          <th>Category</th>
          <th>Location</th>
          <th>Date Found</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($found_items as $item): ?>
          <tr>
            <td><?= htmlspecialchars($item['fnd_name']) ?></td>
            <td><?= htmlspecialchars($item['category_name']) ?></td>
            <td><?= htmlspecialchars($item['location_name']) ?></td>
            <td><?= date("F j, Y, g:i A", strtotime($item['fnd_datetime'])) ?></td>
            <td>
              <span class="badge <?= $item['fnd_status'] === 'unclaimed' ? 'bg-warning text-dark' : ($item['fnd_status'] === 'claimed' ? 'bg-success' : 'bg-secondary') ?>">
                <?= ucfirst($item['fnd_status']) ?>
              </span>
            </td>
            <td>
              <?php if($item['fnd_status'] === 'unclaimed'): ?>
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#claimModal<?= $item['fnd_id'] ?>">
                  Request Claim
                </button>
              <?php else: ?>
                <span class="text-muted">N/A</span>
              <?php endif; ?>
            </td>
          </tr>

          <!-- CLAIM MODAL -->
          <div class="modal fade" id="claimModal<?= $item['fnd_id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                  <h5 class="modal-title fw-bold">Claim: <?= htmlspecialchars($item['fnd_name']) ?></h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <form action="claim_item.php" method="POST">
                    <input type="hidden" name="fnd_id" value="<?= $item['fnd_id'] ?>">
                    <div class="mb-3">
                      <label class="form-label fw-semibold">Full Name</label>
                      <input type="text" name="claimer_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label fw-semibold">Student ID / Valid ID</label>
                      <input type="text" name="claimer_id" class="form-control" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label fw-semibold">Email</label>
                      <input type="email" name="claimer_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label fw-semibold">Proof of Ownership</label>
                      <textarea name="claimer_proof_desc" class="form-control" rows="3" placeholder="Describe proof of ownership..." required></textarea>
                    </div>
                    <div class="d-grid">
                      <button type="submit" class="btn btn-success fw-semibold">Submit Claim</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>

        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function () {
    var table = $('#foundTable').DataTable({
        "order": [[3, "desc"]]
    });

    $('#categoryFilter').on('change', function () {
        var selected = $(this).val();
        if (selected) {
            // Filter by category_id and reload page with query param
            window.location.href = "found_dashboard.php?category_id=" + selected;
        } else {
            window.location.href = "found_dashboard.php";
        }
    });
});
</script>
</body>
</html>
