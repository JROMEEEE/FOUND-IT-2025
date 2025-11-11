<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../accounts/login.php");
    exit;
}

$database = new Database();
$conn = $database->getConnect();

// GET CATEGORIES & LOCATIONS
$categories = $conn->query("SELECT category_id, category_name FROM item_category")->fetchAll(PDO::FETCH_ASSOC);
$locations = $conn->query("SELECT location_id, location_name FROM location_table")->fetchAll(PDO::FETCH_ASSOC);

// HANDLE FORM SUBMISSION
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $lost_name = trim($_POST['lost_name']);
    $lost_desc = trim($_POST['lost_desc']);
    $location_id = $_POST['location_id'];
    $category_id = $_POST['category_id'];
    $user_id = $_SESSION['user_id'];
    $lost_datetime = date('Y-m-d H:i:s');
    $lost_status = 'unclaimed';

    // IMAGE UPLOAD
    $uploadDir = '../uploads/lost_items/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $image_path = null;
    if (!empty($_FILES['lost_image']['name'])) {
        $fileName = time() . '_' . basename($_FILES['lost_image']['name']);
        $targetFile = $uploadDir . $fileName;
        $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif'])) {
            if (move_uploaded_file($_FILES['lost_image']['tmp_name'], $targetFile)) {
                $image_path = 'uploads/lost_items/' . $fileName;
            } else $error = "Failed to upload image.";
        } else $error = "Invalid image type.";
    }

    if (empty($error)) {
        $stmt = $conn->prepare("
            INSERT INTO lost_report (lost_name, lost_desc, location_id, lost_datetime, user_id, image_path, category_id, lost_status)
            VALUES (:lost_name,:lost_desc,:location_id,:lost_datetime,:user_id,:image_path,:category_id,:lost_status)
        ");
        $stmt->bindParam(':lost_name', $lost_name);
        $stmt->bindParam(':lost_desc', $lost_desc);
        $stmt->bindParam(':location_id', $location_id);
        $stmt->bindParam(':lost_datetime', $lost_datetime);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':image_path', $image_path);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':lost_status', $lost_status);

        if ($stmt->execute()) {
            // Redirect to found_dashboard.php and pass category_id
            header("Location: found_dashboard.php?category_id={$category_id}");
            exit;
        } else {
            $error = "Error submitting report.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Report Lost Item</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
  <div class="card shadow border-0">
    <div class="card-header bg-danger text-white text-center fw-bold">
      Report Lost Item
    </div>
    <div class="card-body">
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
          <label class="form-label fw-semibold">Item Name</label>
          <input type="text" name="lost_name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Item Description</label>
          <textarea name="lost_desc" class="form-control" rows="3" required></textarea>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Location Lost</label>
            <select name="location_id" class="form-select" required>
              <option value="">Select Location</option>
              <?php foreach ($locations as $loc): ?>
                <option value="<?= $loc['location_id'] ?>"><?= htmlspecialchars($loc['location_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Item Category</label>
            <select name="category_id" class="form-select" required>
              <option value="">Select Category</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Upload Image (optional)</label>
          <input type="file" name="lost_image" class="form-control" accept="image/*">
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4">
          <a href="user_dashboard.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
          </a>
          <button type="submit" class="btn btn-danger fw-semibold">
            <i class="bi bi-send"></i> Submit Report
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
