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

// Handle AJAX fetch for discard details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fetch_discard'])) {
  $fnd_id = $_POST['fnd_id'];
  $stmt = $conn->prepare("SELECT * FROM discard_table WHERE fnd_id = ?");
  $stmt->execute([$fnd_id]);
  $discard = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($discard) {
    echo "<ul class='list-group'>";
    echo "<li class='list-group-item'><strong>Discarded By:</strong> " . htmlspecialchars($discard['discarded_by']) . "</li>";
    echo "<li class='list-group-item'><strong>Discard Location:</strong> " . htmlspecialchars($discard['discard_location']) . "</li>";
    echo "<li class='list-group-item'><strong>Discarded At:</strong> " . date("F j, Y, g:i A", strtotime($discard['discarded_at'])) . "</li>";
    echo "</ul>";
  } else {
    echo "<div class='text-center text-muted'>No discard details found.</div>";
  }
  exit;
}

// GET CATEGORIES
$catStmt = $conn->prepare("SELECT category_id, category_name FROM item_category ORDER BY category_name");
$catStmt->execute();
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// FETCH DECAYED ITEMS
$query = "
    SELECT d.fnd_id, d.fnd_name, d.fnd_desc, d.fnd_datetime, d.fnd_status, c.category_name, l.location_name
    FROM decayed_table d
    INNER JOIN item_category c ON d.category_id = c.category_id
    INNER JOIN location_table l ON d.location_id = l.location_id
    ORDER BY d.fnd_datetime DESC
";
$stmt = $conn->prepare($query);
$stmt->execute();
$decayed_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// HANDLE DISCARD POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['discard_item'])) {
  $fnd_id = $_POST['fnd_id'];
  $discarded_by = $_POST['discarded_by'];
  $discard_location = $_POST['discard_location'];
  $discarded_at = date('Y-m-d H:i:s');

  try {
    $conn->beginTransaction();

    // Update decayed_table status
    $updateStmt = $conn->prepare("UPDATE decayed_table SET fnd_status = 'discarded' WHERE fnd_id = ?");
    $updateStmt->execute([$fnd_id]);

    // Insert into discard_table
    $insertStmt = $conn->prepare("INSERT INTO discard_table (fnd_id, discarded_by, discard_location, discarded_at) VALUES (?, ?, ?, ?)");
    $insertStmt->execute([$fnd_id, $discarded_by, $discard_location, $discarded_at]);

    // Log action
    $logStmt = $conn->prepare("INSERT INTO activity_log (user_id, action, table_name, record_id, details) VALUES (?, 'DISCARD', 'decayed_table', ?, ?)");
    $logStmt->execute([$_SESSION['user_id'], $fnd_id, "Item discarded by $discarded_by to $discard_location"]);

    $conn->commit();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
  } catch (PDOException $e) {
    $conn->rollBack();
    die("Error discarding item: " . $e->getMessage());
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FOUND-IT | Decayed Items Dashboard</title>
  <?php include '../imports.php'; ?>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
</head>

<body class="bg-light">

  <nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm fixed-top">
    <div class="container">
      <a class="navbar-brand fw-bold" href="#">FOUND-IT</a>
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
      <h2 class="fw-bold text-danger">Decayed Items Dashboard</h2>
      <p class="text-muted">Items that have been decayed after 1 week in the system.</p>
    </div>

    <div class="table-responsive bg-white p-3 shadow-sm rounded">
      <table id="decayedTable" class="table table-striped table-hover align-middle">
        <thead>
          <tr>
            <th colspan="6" class="bg-white">
              <div class="d-flex justify-content-center flex-wrap align-items-center gap-2">
                <select id="categoryFilter" class="form-select w-auto">
                  <option value="">All Categories</option>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['category_name']) ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                  <?php endforeach; ?>
                </select>
                <div class="btn-group " role="group">
                  <button type="button" class="btn btn-outline-dark status-btn" data-status="" style="border-radius:0;">All Statuses</button>
                  <button type="button" class="btn btn-outline-warning status-btn" data-status="unclaimed" style="border-radius:0;">Unclaimed</button>
                  <button type="button" class="btn btn-outline-success status-btn" data-status="claimed" style="border-radius:0;">Claimed</button>
                  <button type="button" class="btn btn-outline-secondary status-btn" data-status="discarded" style="border-radius:0;">Discarded</button>
                </div>
              </div>
    </div>
    </th>
    </tr>
    <tr class="table-danger">
      <th>Item Name</th>
      <th>Category</th>
      <th>Location</th>
      <th>Date Found</th>
      <th>Status</th>
      <th>Action</th>
    </tr>
    </thead>
    <tbody>
      <?php foreach ($decayed_items as $item): ?>
        <?php
        $status = strtolower(trim($item['fnd_status']));
        $hasDiscardStmt = $conn->prepare("SELECT COUNT(*) FROM discard_table WHERE fnd_id = ?");
        $hasDiscardStmt->execute([$item['fnd_id']]);
        $hasDiscard = $hasDiscardStmt->fetchColumn() > 0;
        ?>
        <tr>
          <td><?= htmlspecialchars($item['fnd_name']) ?></td>
          <td><?= htmlspecialchars($item['category_name']) ?></td>
          <td><?= htmlspecialchars($item['location_name']) ?></td>
          <td><?= date("F j, Y, g:i A", strtotime($item['fnd_datetime'])) ?></td>
          <td><?= ucfirst($status) ?></td>
          <td>
            <?php if ($status !== 'discarded'): ?>
              <button class="btn btn-sm btn-danger discardBtn"
                data-id="<?= $item['fnd_id'] ?>"
                data-name="<?= htmlspecialchars($item['fnd_name']) ?>">
                Discard
              </button>
            <?php elseif ($hasDiscard): ?>
              <button class="btn btn-sm btn-outline-primary showDiscardBtn " data-id="<?= $item['fnd_id'] ?>">
                Show Details
              </button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    </table>
  </div>
  </div>

 <!-- Discard Modal -->
<div class="modal fade" id="discardModal" tabindex="-1" aria-labelledby="discardModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">

      <!-- Header -->
      <div class="modal-header" style="background-color: #0d3b66; color: #ffffff;">
        <h5 class="modal-title fw-bold" id="discardModalLabel">Discard Item</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Body & Form -->
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="fnd_id" id="modalFndId">

          <div class="mb-3">
            <label class="form-label">Item Name</label>
            <input type="text" class="form-control" id="modalFndName" disabled>
          </div>

          <div class="mb-3">
            <label class="form-label">Discarded By</label>
            <input type="text" name="discarded_by" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Discard Location</label>
            <select name="discard_location" class="form-select" required>
              <option value="OSD">OSD</option>
              <option value="Security">Security</option>
              <option value="SAO/SSC">SAO/SSC</option>
              <option value="PNP">PNP</option>
            </select>
          </div>

          <hr>
          <p class="small text-muted mb-0">
            <strong>Note:</strong> Certain discarded items are redirected to the appropriate offices as follows:
          </p>
          <ul class="small text-muted">
            <li><strong>OSD</strong> – General Items</li>
            <li><strong>SAO/SSC</strong> – Money/Sensitive Items</li>
            <li><strong>PNP</strong> – Contraband/Weapons</li>
          </ul>
          <p class="small text-muted mb-0">
            The administration is not responsible for any loss or damage once items have been transferred.
          </p>

        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <button type="submit" name="discard_item" class="btn fw-semibold" style="background-color: #0d3b66; color: #ffffff;">
            Discard
          </button>
          <button type="button" class="btn btn-secondary fw-semibold" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>

    </div>
  </div>
</div>



  <!-- Discard Details Modal -->
<div class="modal fade" id="discardDetailsModal" tabindex="-1" aria-labelledby="discardDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">

      <!-- Header -->
      <div class="modal-header" style="background-color: #0d3b66; color: #ffffff;">
        <h5 class="modal-title fw-bold" id="discardDetailsModalLabel">Discarded Item Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Body -->
      <div class="modal-body" id="discardDetailsContent">
        <div class="text-center text-muted mb-3">Loading details...</div>
        <hr>
        <p class="small text-muted mb-2">
          <strong>Note:</strong> Certain discarded items are redirected to the appropriate offices as follows:
        </p>
        <ul class="small text-muted mb-3">
          <li><strong>OSD</strong> – General Items</li>
          <li><strong>SAO/SSC</strong> – Money/Sensitive Items</li>
          <li><strong>PNP</strong> – Contraband/Weapons</li>
        </ul>
        <p class="small text-muted mb-0">
          The administration is not responsible for any loss or damage once items have been transferred.
        </p>
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="btn fw-semibold" style="background-color: #0d3b66; color: #ffffff;" data-bs-dismiss="modal">
          Close
        </button>
      </div>

    </div>
  </div>
</div>


  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    $(document).ready(function() {
      var table = $('#decayedTable').DataTable({
        "order": [
          [3, "desc"]
        ]
      });

      // Filters
      $('#categoryFilter').on('change', function() {
        table.column(1).search($(this).val(), true, false).draw();
      });

      $('.status-btn').on('click', function() {
        $('.status-btn').removeClass('btn-danger text-white');
        $(this).addClass('btn-danger text-white');
        table.column(4).search($(this).data('status'), true, false).draw();
      });

      var discardModal = new bootstrap.Modal(document.getElementById('discardModal'));
      var discardDetailsModal = new bootstrap.Modal(document.getElementById('discardDetailsModal'));

      $(document).on('click', '.discardBtn', function() {
        $('#modalFndId').val($(this).data('id'));
        $('#modalFndName').val($(this).data('name'));
        discardModal.show();
      });

      $(document).on('click', '.showDiscardBtn', function() {
        var fnd_id = $(this).data('id');
        $('#discardDetailsContent').html('<div class="text-center text-muted">Loading...</div>');

        $.post('', {
          fetch_discard: 1,
          fnd_id: fnd_id
        }, function(response) {
          $('#discardDetailsContent').html(response);
          discardDetailsModal.show();
        });
      });
    });
  </script>
</body>

</html>