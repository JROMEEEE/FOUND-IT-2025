<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../accounts/login.php");
    exit;
}

$database = new Database();
$conn = $database->getConnect();

// SESSION TIMEOUT (1 hour)
$session_lifetime = 3600;
if (!isset($_SESSION['user_id']) || (time() - $_SESSION['last_activity'] > $session_lifetime)) {
    session_unset();
    session_destroy();
    header("Location: ../accounts/login.php");
    exit;
}
$_SESSION['last_activity'] = time();

// FETCH USER INFO
$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name']);
$email = htmlspecialchars($_SESSION['email']);
$is_admin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 0;

$showPrivacyModal = !isset($_SESSION['privacy_acknowledged']);


// ADMINS ONLY
if ($is_admin != 1) {
    header("Location: user_dashboard.php");
    exit;
}

// GET CATEGORY & LOCATIONS
$categories = $conn->query("SELECT category_id, category_name FROM item_category")->fetchAll(PDO::FETCH_ASSOC);
$locations = $conn->query("SELECT location_id, location_name FROM location_table")->fetchAll(PDO::FETCH_ASSOC);

// ------------------ ACTIVITY LOG FUNCTION ------------------
function log_activity($conn, $user_id, $action, $table_name, $record_id = null, $details = null)
{
    $stmt = $conn->prepare("
        INSERT INTO activity_log (user_id, action, table_name, record_id, details)
        VALUES (:user_id, :action, :table_name, :record_id, :details)
    ");
    $stmt->execute([
        'user_id' => $user_id,
        'action' => $action,
        'table_name' => $table_name,
        'record_id' => $record_id,
        'details' => $details
    ]);
}

// FORM SUBMISSION
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fnd_name = trim($_POST['fnd_name']);
    $fnd_desc = trim($_POST['fnd_desc']);
    $location_id = $_POST['location_id'];
    $category_id = $_POST['category_id'];
    $user_id = $_SESSION['user_id'];
    $fnd_datetime = date('Y-m-d H:i:s');
    $fnd_status = 'unclaimed';

    $uploadDir = '../uploads/found_items/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $image_path = null;

    // HANDLE CAMERA CAPTURE / FILE UPLOAD
    if (!empty($_POST['fnd_image_data'])) {
        $img = str_replace('data:image/png;base64,', '', $_POST['fnd_image_data']);
        $img = base64_decode($img);
        $fileName = 'found_' . time() . '.png';
        $filePath = $uploadDir . $fileName;
        file_put_contents($filePath, $img);
        $image_path = 'uploads/found_items/' . $fileName;
    } elseif (!empty($_FILES['fnd_image']['name'])) {
        $fileName = basename($_FILES['fnd_image']['name']);
        $targetFile = $uploadDir . time() . '_' . $fileName;
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($imageFileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['fnd_image']['tmp_name'], $targetFile)) {
                $image_path = 'uploads/found_items/' . time() . '_' . $fileName;
            } else {
                $error = "Failed to upload image.";
                log_activity($conn, $user_id, 'UPLOAD_FAILED', 'found_report', null, $error);
            }
        } else {
            $error = "Invalid image type. Only JPG, PNG, GIF allowed.";
            log_activity($conn, $user_id, 'UPLOAD_FAILED', 'found_report', null, $error);
        }
    }

    // INSERT INTO found_report
    if (empty($error)) {
        $sql = "INSERT INTO found_report 
                (fnd_name, fnd_desc, location_id, fnd_datetime, user_id, image_path, category_id, fnd_status)
                VALUES (:fnd_name, :fnd_desc, :location_id, :fnd_datetime, :user_id, :image_path, :category_id, :fnd_status)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':fnd_name', $fnd_name);
        $stmt->bindParam(':fnd_desc', $fnd_desc);
        $stmt->bindParam(':location_id', $location_id);
        $stmt->bindParam(':fnd_datetime', $fnd_datetime);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':image_path', $image_path);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':fnd_status', $fnd_status);

        if ($stmt->execute()) {
            $fnd_id = $conn->lastInsertId();
            $log_details = json_encode([
                'fnd_name' => $fnd_name,
                'fnd_desc' => $fnd_desc,
                'location_id' => $location_id,
                'category_id' => $category_id,
                'image_path' => $image_path
            ]);
            log_activity($conn, $user_id, 'INSERT', 'found_report', $fnd_id, $log_details);
            $_SESSION['report_submitted'] = true;
            $success = "Found item successfully reported!";
        } else {
            $error = "Error submitting report. Please try again.";
            log_activity($conn, $user_id, 'INSERT_FAILED', 'found_report', null, $error);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Report Found Item | FOUND-IT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel = "stylesheet" href="../css/report.css">

    <style>
        video {
            border: 1px solid #ccc;
            width: 320px;
            height: 240px;
            display: block;
            margin-bottom: 5px;
        }

        canvas {
            display: block;
            margin-top: 10px;
            border: 1px solid #ccc;
        }
    </style>
</head>

<body class="bg-light">

    <div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 30%;">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold" id="logoutModalLabel">Privacy Notice</h5>
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
            <div class="card-header bg-danger text-white text-center fw-bold" style="padding: 1.5rem; border-radius: 0;">Report Found Item</div>
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
                        <label class="form-label fw-semibold">Capture Image from Camera (optional)</label>
                        <video id="video" autoplay playsinline muted></video>
                        <button type="button" id="snap" class="btn btn-secondary btn-sm mt-2">Take Photo</button>
                        <canvas id="canvas" width="320" height="240"></canvas>
                        <input type="hidden" name="fnd_image_data" id="fnd_image_data">
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="admin_dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                        <button type="submit" class="btn btn-danger fw-semibold">Submit Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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


        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const context = canvas.getContext('2d');
        const hiddenInput = document.getElementById('fnd_image_data');

        async function startCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: "environment"
                    }
                });
                video.srcObject = stream;
            } catch (e) {
                alert('Cannot access camera: ' + e.message);
            }
        }
        startCamera();

        document.getElementById('snap').addEventListener('click', () => {
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            const dataURL = canvas.toDataURL('image/png');
            hiddenInput.value = dataURL;
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>