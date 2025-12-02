<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../accounts/login.php");
    exit;
}

$showPrivacyModal = false;
if (!isset($_SESSION['privacy_acknowledged'])) {
    $showPrivacyModal = true;
}

$database = new Database();
$conn = $database->getConnect();



// GET CATEGORIES & LOCATIONS
$categories = $conn->query("SELECT category_id, category_name FROM item_category")->fetchAll(PDO::FETCH_ASSOC);
$locations = $conn->query("SELECT location_id, location_name FROM location_table")->fetchAll(PDO::FETCH_ASSOC);

$error = null;

// HANDLE FORM SUBMISSION
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $lost_name = trim($_POST['lost_name']);
    $lost_desc = trim($_POST['lost_desc']);
    $location_id = $_POST['location_id'];
    $category_id = $_POST['category_id'];
    $user_id = $_SESSION['user_id'];
    $lost_datetime = date('Y-m-d H:i:s');
    $lost_status = 'active';

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
        try {
            $conn->beginTransaction();

            // INSERT LOST REPORT
            $stmt = $conn->prepare("
                INSERT INTO lost_report (lost_name, lost_desc, location_id, lost_datetime, user_id, image_path, category_id, lost_status)
                VALUES (:lost_name,:lost_desc,:location_id,:lost_datetime,:user_id,:image_path,:category_id,:lost_status)
            ");
            $stmt->execute([
                ':lost_name' => $lost_name,
                ':lost_desc' => $lost_desc,
                ':location_id' => $location_id,
                ':lost_datetime' => $lost_datetime,
                ':user_id' => $user_id,
                ':image_path' => $image_path,
                ':category_id' => $category_id,
                ':lost_status' => $lost_status
            ]);

            $lost_id = $conn->lastInsertId();

            // LOG TO ACTIVITY_LOG
            $logStmt = $conn->prepare("
                INSERT INTO activity_log (user_id, action, table_name, record_id, details)
                VALUES (:user_id, 'INSERT', 'lost_report', :record_id, :details)
            ");
            $details = json_encode([
                'lost_name' => $lost_name,
                'lost_desc' => $lost_desc,
                'location_id' => $location_id,
                'category_id' => $category_id,
                'image_path' => $image_path
            ]);
            $logStmt->execute([
                ':user_id' => $user_id,
                ':record_id' => $lost_id,
                ':details' => $details
            ]);

            $conn->commit();

            // REDIRECT
            header("Location: found_dashboard.php?category_id={$category_id}");
            exit;

        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Error submitting report: " . $e->getMessage();
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
<link rel = "stylesheet" href="../css/report.css">
</head>
<body class="bg-light">


<div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 30%;">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold" id="logoutModalLabel">Privacy Notice</h5>
                    <!-- <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button> -->
                </div>
                <div class="modal-body">
                    <p>
                        <strong>Your privacy is important to us. Any images captured via your device's camera are used solely for reporting found items and will not be stored or shared without your consent.</strong>
                    </p>
                    <p>
                        All data submitted is accessible only to authorized staff. Contact information is used only for communication regarding lost and found items.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" id="acknowledgeBtn" class="btn btn-danger fw-semibold">I Understand</button>



                </div>
            </div>
        </div>
    </div>

     <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm fixed-top ">
        <div class="container">
            <a class="navbar-brand fw-bold" href="user_dashboard.php">FOUND-IT</a>
            <div class="d-flex gap-2">
                <a href="user_dashboard.php" class="btn btn-outline-light btn-sm fw-semibold">
                    <i class="bi bi-house-door"></i> Dashboard
                </a>
                <a href="../accounts/logout.php" class="btn btn-light btn-sm text-danger fw-semibold">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

<div class="container py-5">
<div class="card shadow-sm mb-5 mx-auto" style="max-width: 80% ; margin-top: 50px; border-radius:0; max-height: 70%">    
            <div class="card-header bg-danger text-white text-center fw-bold" style="padding: 1.5rem; border-radius: 0;">Report Lost Item</div>
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


<script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($showPrivacyModal): ?>
                const privacyModal = new bootstrap.Modal(document.getElementById('privacyModal'), {
                    backdrop: 'static',
                    keyboard: false
                });
                privacyModal.show();

                document.querySelector('#privacyModal .btn-danger').addEventListener('click', function() {
                    fetch('acknowledge_privacy.php') // set session variable server-side
                    .then(response=>{
                        if (response.ok) {
                            privacyModal.hide();
                        } else {
                            alert('Error acknowledging privacy notice. Please try again.');
                        }
                    })
                    .catch(error=>{
                        alert('Network error: ' + error.message);
                    });
                    // Modal acknowledged
                });

            <?php endif; ?>
        });
        </script>


</body>
</html>