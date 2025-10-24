<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../accounts/login.php");
    exit;
}

$database = new Database();
$conn = $database->getConnect();

// GET CATEGORY & LOCATIONS
$categories = $conn->query("SELECT category_id, category_name FROM item_category")->fetchAll(PDO::FETCH_ASSOC);
$locations = $conn->query("SELECT location_id, location_name FROM location_table")->fetchAll(PDO::FETCH_ASSOC);

// FORM SUBMISSION HANDLING PROCESS
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fnd_name = trim($_POST['fnd_name']);
    $fnd_desc = trim($_POST['fnd_desc']);
    $location_id = $_POST['location_id'];
    $category_id = $_POST['category_id'];
    $user_id = $_SESSION['user_id'];
    $fnd_datetime = date('Y-m-d H:i:s');
    $fnd_status = 'unclaimed';

    // IMAGE UPLOAD
    $fnd_image = null;
    if (!empty($_FILES['fnd_image']['name'])) {
        $imageData = file_get_contents($_FILES['fnd_image']['tmp_name']);
        $fnd_image = $imageData;
    }

    // INSERT INTO found_report
    $sql = "INSERT INTO found_report 
            (fnd_name, fnd_desc, location_id, fnd_datetime, user_id, fnd_image, category_id, fnd_status)
            VALUES (:fnd_name, :fnd_desc, :location_id, :fnd_datetime, :user_id, :fnd_image, :category_id, :fnd_status)";
    $stmt = $conn->prepare($sql);

    $stmt->bindParam(':fnd_name', $fnd_name);
    $stmt->bindParam(':fnd_desc', $fnd_desc);
    $stmt->bindParam(':location_id', $location_id);
    $stmt->bindParam(':fnd_datetime', $fnd_datetime);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':fnd_image', $fnd_image, PDO::PARAM_LOB);
    $stmt->bindParam(':category_id', $category_id);
    $stmt->bindParam(':fnd_status', $fnd_status);

    if ($stmt->execute()) {
        $success = "Found item successfully reported!";
    } else {
        $error = "Error submitting report. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Found Item | FOUND-IT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="card shadow border-0">
        <div class="card-header bg-danger text-white text-center fw-bold">
            Report Found Item
        </div>
        <div class="card-body">
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php elseif (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Item Name</label>
                    <input type="text" name="fnd_name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Item Description</label>
                    <textarea name="fnd_desc" class="form-control" rows="3" required></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Location Found</label>
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
                    <input type="file" name="fnd_image" class="form-control" accept="image/*">
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
