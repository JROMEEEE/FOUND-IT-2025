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

// 1-WEEK DECAY PROCESS
$oneWeekAgo = date('Y-m-d H:i:s', strtotime('-1 week'));

try {
    $conn->beginTransaction();

    // Select items older than 1 week and are unclaimed or claimed
    $selectStmt = $conn->prepare("
        SELECT * 
        FROM found_report
        WHERE fnd_datetime <= :oneWeekAgo
          AND fnd_status IN ('unclaimed', 'claimed')
    ");
    $selectStmt->execute(['oneWeekAgo' => $oneWeekAgo]);
    $itemsToDecay = $selectStmt->fetchAll(PDO::FETCH_ASSOC);

    if ($itemsToDecay) {
        $insertStmt = $conn->prepare("
            INSERT INTO decayed_table 
            (fnd_id, fnd_name, fnd_desc, location_id, fnd_datetime, user_id, image_path, category_id, fnd_status)
            VALUES (:fnd_id, :fnd_name, :fnd_desc, :location_id, :fnd_datetime, :user_id, :image_path, :category_id, :fnd_status)
        ");

        $logStmt = $conn->prepare("
            INSERT INTO activity_log (user_id, action, table_name, record_id, details)
            VALUES (:user_id, 'DECAY', 'found_report', :record_id, :details)
        ");

        foreach ($itemsToDecay as $item) {
            // Move to decayed_table
            $insertStmt->execute([
                'fnd_id' => $item['fnd_id'],
                'fnd_name' => $item['fnd_name'],
                'fnd_desc' => $item['fnd_desc'],
                'location_id' => $item['location_id'],
                'fnd_datetime' => $item['fnd_datetime'],
                'user_id' => $item['user_id'],
                'image_path' => $item['image_path'],
                'category_id' => $item['category_id'],
                'fnd_status' => $item['fnd_status']
            ]);

            // Log action
            $logStmt->execute([
                'user_id' => $_SESSION['user_id'],
                'record_id' => $item['fnd_id'],
                'details' => json_encode($item)
            ]);
        }

        // Delete decayed items from found_report
        $ids = array_column($itemsToDecay, 'fnd_id');
        $deleteStmt = $conn->prepare("DELETE FROM found_report WHERE fnd_id IN (" . implode(',', $ids) . ")");
        $deleteStmt->execute();
    }

    $conn->commit();
} catch (PDOException $e) {
    $conn->rollBack();
    die("Decay Error: " . $e->getMessage());
}

// GET CATEGORIES
$catStmt = $conn->prepare("SELECT category_id, category_name FROM item_category ORDER BY category_name");
$catStmt->execute();
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// GET SELECTED CATEGORY AND STATUS (OPTIONAL)
$selectedCategoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
$selectedStatus = isset($_GET['status']) ? $_GET['status'] : null;

// FETCH FOUND ITEMS

$query = "
    SELECT f.fnd_id, f.fnd_name, f.fnd_datetime, f.fnd_status, c.category_name, l.location_name
    FROM found_report f
    INNER JOIN item_category c ON f.category_id = c.category_id
    INNER JOIN location_table l ON f.location_id = l.location_id
    WHERE 1
";

$params = [];

// Filter by category
if ($selectedCategoryId) {
    $query .= " AND f.category_id = :category_id";
    $params['category_id'] = $selectedCategoryId;
}

// Filter by status
if ($selectedStatus) {
    $query .= " AND f.fnd_status = :status";
    $params['status'] = $selectedStatus;
}

$query .= " ORDER BY f.fnd_datetime DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
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
    <p class="text-muted">Search items, filter by category or claim status, or submit a claim.</p>
  </div>

  <div class="text-left " style="margin-top: 5px; margin-bottom: 20px;">
               <div class="btn-group mb-3" role="group">
    <a href="found_dashboard.php?status=&category_id=<?= $selectedCategoryId ?>"
       class="btn <?= $selectedStatus === null || $selectedStatus === '' ? 'btn-danger text-white' : 'btn-outline-danger' ?>">
        All
    </a>

    <a href="found_dashboard.php?status=unclaimed&category_id=<?= $selectedCategoryId ?>"
       class="btn <?= $selectedStatus === 'unclaimed' ? 'btn-danger text-white' : 'btn-outline-danger' ?>">
        Unclaimed
    </a>

    <a href="found_dashboard.php?status=claimed&category_id=<?= $selectedCategoryId ?>"
       class="btn <?= $selectedStatus === 'claimed' ? 'btn-danger text-white' : 'btn-outline-danger' ?>">
        Claimed
    </a>
</div>

                </div>

  <!-- TABLE -->
  <div class="table-responsive bg-white p-3 shadow-sm ">
    <table id="foundTable" class="table table-striped table-hover align-middle">
      <thead>
        <tr>
          <th colspan="6" class="bg-white">
            <div class="d-flex justify-content-center flex-wrap align-items-center gap-2">
              <!-- Category Dropdown -->
              <select id="categoryFilter" class="form-select w-auto">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['category_id'] ?>" <?= $cat['category_id'] == $selectedCategoryId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['category_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>

              <!-- Status Buttons -->
               
                
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
        <?php foreach ($found_items as $item): ?>
          <tr>
            <td><?= htmlspecialchars($item['fnd_name']) ?></td>
            <td><?= htmlspecialchars($item['category_name']) ?></td>
            <td><?= htmlspecialchars($item['location_name']) ?></td>
            <td><?= date("F j, Y, g:i A", strtotime($item['fnd_datetime'])) ?></td>
            <td style="color: <?= $item['fnd_status'] === 'pending' ? 'gray' : ($item['fnd_status'] === 'unclaimed' ? 'red' : ($item['fnd_status'] === 'claimed' ? 'green' : 'black')); ?>; font-weight: bold; text-transform: uppercase;">
                                                    <?= htmlspecialchars($item['fnd_status']); ?>
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
                      <label class="form-label fw-semibold">SR-Code / ID Number</label>
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
    $('#foundTable').DataTable({
        "order": [[3, "desc"]]
    });

    $('#categoryFilter').on('change', function () {
        var selectedCat = $(this).val();
        var status = "<?= $selectedStatus ?>";
        var url = "found_dashboard.php?";
        if (selectedCat) url += "category_id=" + selectedCat + "&";
        if (status) url += "status=" + status;
        window.location.href = url;
    });
});
</script>
</body>
</html>