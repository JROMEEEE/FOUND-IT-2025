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
        echo "<hr>";
        echo "<p class='small text-muted mb-0'>
            Disclaimer: Certain discarded items are redirected to the appropriate offices: 
            <br>• OSD – General Items
            <br>• SAO/SSC – Money/Sensitive Items
            <br>• PNP – Contraband/Weapons
            <br>The administration is not responsible for any loss or damage once items have been transferred.
        </p>";
    } else {
        echo "<div class='text-center text-muted'>No discard details found.</div>";
    }
    exit;
}

// GET CATEGORIES
$catStmt = $conn->prepare("SELECT category_id, category_name FROM item_category ORDER BY category_name");
$catStmt->execute();
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// FETCH ONLY DISCARDED ITEMS
$query = "
    SELECT d.fnd_id, d.fnd_name, d.fnd_desc, d.fnd_datetime, d.fnd_status, c.category_name, l.location_name
    FROM decayed_table d
    INNER JOIN item_category c ON d.category_id = c.category_id
    INNER JOIN location_table l ON d.location_id = l.location_id
    WHERE d.fnd_status = 'discarded'
    ORDER BY d.fnd_datetime DESC
";
$stmt = $conn->prepare($query);
$stmt->execute();
$decayed_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>FOUND-IT | Discarded Items</title>
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
    <h2 class="fw-bold text-danger">Discarded Items</h2>
    <p class="text-muted">Items that have been discarded from the system.</p>
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
            </div>
          </th>
        </tr>
        <tr class="table-danger">
          <th>Item Name</th>
          <th>Category</th>
          <th>Location</th>
          <th>Date Found</th>
          <th>Status</th>
          <th>Details</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($decayed_items as $item): ?>
          <tr>
            <td><?= htmlspecialchars($item['fnd_name']) ?></td>
            <td><?= htmlspecialchars($item['category_name']) ?></td>
            <td><?= htmlspecialchars($item['location_name']) ?></td>
            <td><?= date("F j, Y, g:i A", strtotime($item['fnd_datetime'])) ?></td>
            <td><?= ucfirst($item['fnd_status']) ?></td>
            <td>
              <button class="btn btn-sm btn-info showDiscardBtn" data-id="<?= $item['fnd_id'] ?>">
                Show Details
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Discard Details Modal -->
<div class="modal fade" id="discardDetailsModal" tabindex="-1" aria-labelledby="discardDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="discardDetailsModalLabel">Discard Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="discardDetailsContent">
        <div class="text-center text-muted">Loading...</div>
        <hr>
        <p class="small text-muted mb-0">
          Disclaimer: Certain discarded items are redirected to the appropriate offices: 
          <br>• OSD – General Items
          <br>• SAO/SSC – Money/Sensitive Items
          <br>• PNP – Contraband/Weapons
          <br>The administration is not responsible for any loss or damage once items have been transferred.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function () {
    var table = $('#decayedTable').DataTable({"order": [[3, "desc"]]});

    // Category filter
    $('#categoryFilter').on('change', function () {
        table.column(1).search($(this).val(), true, false).draw();
    });

    var discardDetailsModal = new bootstrap.Modal(document.getElementById('discardDetailsModal'));

    $(document).on('click', '.showDiscardBtn', function() {
        var fnd_id = $(this).data('id');
        $('#discardDetailsContent').html('<div class="text-center text-muted">Loading...</div><hr><p class="small text-muted mb-0">Disclaimer: Certain discarded items are redirected to the appropriate offices: <br>• OSD – General Items<br>• SAO/SSC – Money/Sensitive Items<br>• PNP – Contraband/Weapons<br>The administration is not responsible for any loss or damage once items have been transferred.</p>');

        $.post('', { fetch_discard: 1, fnd_id: fnd_id }, function(response){
            $('#discardDetailsContent').html(response);
            discardDetailsModal.show();
        });
    });
});
</script>
</body>
</html>